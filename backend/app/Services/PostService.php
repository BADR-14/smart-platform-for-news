<?php

namespace App\Services;

use App\Enums\Post\PostStatus;
use App\Http\Resources\PostResource;
use App\Http\Resources\PostShareResource;
use App\Models\Account;
use App\Models\Media;
use App\Models\MediaType;
use App\Models\Post;
use App\Models\PostShare;
use App\Models\Tag;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PostService
{
    private const PER_PAGE = 10;

    // ==================== Queries ====================

    /**
     * الصفحة الرئيسية.
     * بدون فلاتر: المنشورات + المشاركات مرتبة زمنياً. مع الفلاتر: المنشورات المطابقة فقط.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getPosts(array $filters, ?Account $viewer = null): LengthAwarePaginator
    {
        $hasFilters = ! empty(array_filter($filters, fn ($value) => $value !== null && $value !== ''));

        if (! $hasFilters) {
            return $this->getFeedWithShares($viewer);
        }

        return Post::query()
            ->published()
            ->forResource($viewer)
            ->filterByDate($filters['date'] ?? null)
            ->filterByCategory(isset($filters['category_id']) ? (int) $filters['category_id'] : null)
            ->filterByPostType($filters['post_type'] ?? null)
            ->filterByRating(isset($filters['rating']) ? (float) $filters['rating'] : null)
            ->filterByIsOnline(
                isset($filters['is_online'])
                    ? filter_var($filters['is_online'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
                    : null
            )
            ->searchByKeyword($filters['keyword'] ?? null)
            ->latest('posts.created_at')
            ->paginate(self::PER_PAGE);
    }

    /**
     * المنشورات المميزة السارية حالياً — تظهر أعلى الصفحة الرئيسية
     */
    public function getFeaturedPosts(?Account $viewer = null, int $limit = 10): Collection
    {
        return Post::query()
            ->published()
            ->featured()
            ->forResource($viewer)
            ->latest('posts.created_at')
            ->limit($limit)
            ->get();
    }

    public function getPost(Post $post, ?Account $viewer = null): Post
    {
        return Post::query()->forResource($viewer)->findOrFail($post->id);
    }

    /**
     * منشورات حساب معيّن كما يراها الآخرون (المنشورة فقط)
     */
    public function getAccountPosts(Account $account, ?Account $viewer = null): LengthAwarePaginator
    {
        return $account->posts()
            ->published()
            ->forResource($viewer)
            ->latest('posts.created_at')
            ->paginate(self::PER_PAGE);
    }

    /**
     * منشورات صاحب الحساب نفسه بكل حالاتها (لوحة الشركة وصفحة "منشوراتي")
     */
    public function getOwnPosts(Account $account, ?string $status = null, ?string $search = null, int $perPage = self::PER_PAGE): LengthAwarePaginator
    {
        return $account->posts()
            ->forResource($account)
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($search, fn ($q) => $q->where('title', 'LIKE', '%'.addcslashes($search, '%_\\').'%'))
            ->latest('posts.created_at')
            ->paginate($perPage);
    }

    // ==================== Commands ====================

    /**
     * @param  array<string, mixed>  $data
     */
    public function storePost(Account $account, array $data): Post
    {
        $post = DB::transaction(function () use ($account, $data) {
            $post = Post::create([
                'account_id' => $account->id,
                'status' => $this->statusFromAction($data['action']),
                ...$this->attributes($data),
            ]);

            $this->syncTags($post, $data['tags'] ?? []);
            $this->storeMedia($post, $account, $data['media'] ?? []);

            return $post;
        });

        return $this->getPost($post, $account);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updatePost(Post $post, Account $account, array $data): Post
    {
        DB::transaction(function () use ($post, $account, $data) {
            $attributes = $this->attributes($data);

            // المنشور الأونلاين لا يحمل موقعاً جغرافياً
            if (($data['is_online'] ?? null) === true) {
                $attributes = [...$attributes, 'location' => null, 'latitude' => null, 'longitude' => null];
            }

            // المنشور الموضوع قيد المراجعة أو المرفوض يبقى على حالته: صاحبه يعدّل المحتوى فقط،
            // وإعادة نشره قرار الأدمن (وإلا أمكن تجاوز المراجعة بإعادة النشر).
            $isModerated = in_array($post->status, [PostStatus::UnderReview->value, PostStatus::Rejected->value], true);

            if (! empty($data['action']) && ! $isModerated) {
                $attributes['status'] = $this->statusFromAction($data['action']);
            }

            $post->update($attributes);

            if (array_key_exists('tags', $data)) {
                $this->syncTags($post, $data['tags'] ?? []);
            }

            $this->removeMedia($post, $data['remove_media_ids'] ?? []);
            $this->storeMedia($post, $account, $data['media'] ?? []);
        });

        return $this->getPost($post, $account);
    }

    public function destroyPost(Post $post): void
    {
        $post->delete();
    }

    // ==================== Internals ====================

    /**
     * يحوّل بيانات الطلب إلى أعمدة المنشور (فقط الحقول المرسلة فعلاً)
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function attributes(array $data): array
    {
        $columns = [
            'category_id', 'post_type', 'title', 'description', 'is_online',
            'location', 'latitude', 'longitude', 'start_date', 'end_date',
        ];

        return array_intersect_key($data, array_flip($columns));
    }

    /**
     * صاحب المنشور ينشر أو يحفظ كمسودة أو يؤرشف.
     * حالتا "قيد المراجعة" و"مرفوض" يضعهما الأدمن فقط.
     */
    private function statusFromAction(string $action): string
    {
        return match ($action) {
            'publish' => PostStatus::Published->value,
            'archive' => PostStatus::Archived->value,
            default => PostStatus::Draft->value,
        };
    }

    /**
     * الوسوم تُرسل كأسماء، وننشئ غير الموجود منها
     *
     * @param  list<string>  $names
     */
    private function syncTags(Post $post, array $names): void
    {
        $ids = collect($names)
            ->map(fn ($name) => mb_strtolower(trim((string) $name)))
            ->filter()
            ->unique()
            ->map(fn (string $name) => Tag::firstOrCreate(['name' => $name])->id);

        $post->tags()->sync($ids->all());
    }

    /**
     * @param  array<int, UploadedFile>  $files
     */
    private function storeMedia(Post $post, Account $account, array $files): void
    {
        foreach ($files as $file) {
            $typeName = str_starts_with((string) $file->getMimeType(), 'video') ? 'video' : 'image';

            Media::create([
                'account_id' => $account->id,
                'post_id' => $post->id,
                'media_type_id' => MediaType::firstOrCreate(['name' => $typeName])->id,
                'media_url' => $file->store("posts/{$post->id}", 'public'),
            ]);
        }
    }

    /**
     * @param  list<int>  $mediaIds
     */
    private function removeMedia(Post $post, array $mediaIds): void
    {
        if (empty($mediaIds)) {
            return;
        }

        $post->media()->whereIn('id', $mediaIds)->get()->each(function (Media $media) {
            Storage::disk('public')->delete($media->media_url);
            $media->forceDelete();
        });
    }

    /**
     * يدمج المنشورات والمشاركات في قائمة زمنية واحدة مقسّمة لصفحات
     */
    private function getFeedWithShares(?Account $viewer): LengthAwarePaginator
    {
        $posts = DB::table('posts')
            ->select('id', DB::raw("'post' as type"), 'created_at')
            ->where('status', PostStatus::Published->value)
            ->whereNull('deleted_at');

        // المشاركة تظهر فقط ما دام المنشور الأصلي منشوراً
        $shares = DB::table('post_shares')
            ->join('posts', 'posts.id', '=', 'post_shares.post_id')
            ->select('post_shares.id', DB::raw("'share' as type"), 'post_shares.created_at')
            ->where('posts.status', PostStatus::Published->value)
            ->whereNull('posts.deleted_at');

        $feed = DB::query()
            ->fromSub($posts->unionAll($shares), 'feed')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE);

        $items = collect($feed->items());

        $sharesMap = PostShare::whereIn('id', $items->where('type', 'share')->pluck('id'))
            ->with(['account.accountType', 'account.company'])
            ->get()
            ->keyBy('id');

        $postIds = $items->where('type', 'post')->pluck('id')
            ->merge($sharesMap->pluck('post_id'))
            ->unique();

        $postsMap = Post::query()->forResource($viewer)->whereIn('id', $postIds)->get()->keyBy('id');

        $sharesMap->each(fn (PostShare $share) => $share->setRelation('post', $postsMap->get($share->post_id)));

        $feed->setCollection(
            $items->map(fn ($item) => $item->type === 'post'
                ? ($postsMap->has($item->id) ? new PostResource($postsMap->get($item->id)) : null)
                : ($sharesMap->has($item->id) ? new PostShareResource($sharesMap->get($item->id)) : null)
            )->filter()->values()
        );

        return $feed;
    }
}
