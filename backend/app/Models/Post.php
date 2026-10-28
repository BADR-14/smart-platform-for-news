<?php

namespace App\Models;

use App\Enums\Post\PostStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * العلاقات التي يحتاجها PostResource دائماً
     */
    public const RESOURCE_RELATIONS = [
        'category',
        'account.accountType',
        'account.company',
        'media.mediaType',
        'tags',
        'activeFeature',
    ];

    protected $fillable = [
        'category_id',
        'account_id',
        'post_type',
        'title',
        'description',
        'is_online',
        'location',
        'latitude',
        'longitude',
        'start_date',
        'end_date',
        'status',
        'moderation_note',
    ];

    protected $casts = [
        'is_online' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
        'latitude' => 'float',
        'longitude' => 'float',
        'views_count' => 'integer',
        'likes_count' => 'integer',
        'comments_count' => 'integer',
        'shares_count' => 'integer',
    ];

    // ==================== Relations ====================

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class)->withTrashed();
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class)->withTrashed();
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'post_tags');
    }

    public function media(): HasMany
    {
        return $this->hasMany(Media::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function reacts(): HasMany
    {
        return $this->hasMany(UserReact::class);
    }

    public function bookmarks(): HasMany
    {
        return $this->hasMany(Bookmark::class);
    }

    public function featuredPosts(): HasMany
    {
        return $this->hasMany(FeaturedPost::class);
    }

    /**
     * سجل التمييز الساري حالياً (إن وجد)
     */
    public function activeFeature(): HasOne
    {
        return $this->hasOne(FeaturedPost::class)->ofMany(
            ['id' => 'max'],
            fn (Builder $q) => $q->running()
        );
    }

    public function reports(): MorphMany
    {
        return $this->morphMany(Report::class, 'reportable');
    }

    public function shares(): HasMany
    {
        return $this->hasMany(PostShare::class);
    }

    public function views(): HasMany
    {
        return $this->hasMany(PostView::class);
    }

    // ==================== Scopes ====================

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('posts.status', PostStatus::Published->value);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->whereHas('featuredPosts', fn (Builder $q) => $q->running());
    }

    /**
     * يحمّل كل ما يحتاجه PostResource باستعلامات ثابتة العدد (بدون N+1):
     * العلاقات + متوسط التقييم + حالة المشاهد الحالي (تفاعله، حفظه، تقييمه).
     */
    public function scopeForResource(Builder $query, ?Account $viewer = null): Builder
    {
        $query->with(self::RESOURCE_RELATIONS)
            ->withAvg('reviews as rating_avg', 'rating')
            ->withCount('reviews');

        $viewerUserId = $viewer?->user?->id;

        if ($viewerUserId) {
            $query
                ->withExists(['bookmarks as is_bookmarked' => fn (Builder $q) => $q->where('user_id', $viewerUserId)])
                ->addSelect([
                    'my_react_type_id' => UserReact::select('react_type_id')
                        ->whereColumn('post_id', 'posts.id')
                        ->where('user_id', $viewerUserId)
                        ->limit(1),
                    'my_rating' => Review::select('rating')
                        ->whereColumn('post_id', 'posts.id')
                        ->where('user_id', $viewerUserId)
                        ->limit(1),
                ]);
        }

        if ($viewer) {
            $query->withExists(['shares as is_shared' => fn (Builder $q) => $q->where('account_id', $viewer->id)]);
        }

        return $query;
    }

    // ---- Filters ----

    public function scopeFilterByDate(Builder $query, ?string $date): Builder
    {
        return $query->when($date, fn (Builder $q) => $q->whereDate('start_date', $date));
    }

    public function scopeFilterByCategory(Builder $query, ?int $categoryId): Builder
    {
        return $query->when($categoryId, fn (Builder $q) => $q->where('category_id', $categoryId));
    }

    public function scopeFilterByPostType(Builder $query, ?string $postType): Builder
    {
        return $query->when($postType, fn (Builder $q) => $q->where('post_type', $postType));
    }

    /**
     * المنشورات التي متوسط تقييمها يساوي أو يزيد عن القيمة المطلوبة
     */
    public function scopeFilterByRating(Builder $query, ?float $rating): Builder
    {
        // القيمة float مؤكدة (من الـ type-hint) فتُكتب مباشرة: ربطها كـ parameter يجعل SQLite يقارنها كنص
        $minimum = number_format((float) $rating, 1, '.', '');

        return $query->when($rating, fn (Builder $q) => $q->whereIn(
            'posts.id',
            Review::select('post_id')
                ->groupBy('post_id')
                ->havingRaw("AVG(rating) >= {$minimum}")
        ));
    }

    public function scopeFilterByIsOnline(Builder $query, ?bool $isOnline): Builder
    {
        return $query->when(! is_null($isOnline), fn (Builder $q) => $q->where('is_online', $isOnline));
    }

    public function scopeSearchByKeyword(Builder $query, ?string $keyword): Builder
    {
        $keyword = trim((string) $keyword);

        if (mb_strlen($keyword) < 2) {
            return $query;
        }

        // تهريب محارف LIKE الخاصة حتى لا يتحول البحث إلى wildcard
        $like = '%'.addcslashes($keyword, '%_\\').'%';

        return $query->where(function (Builder $query) use ($like) {
            $query->where('title', 'LIKE', $like)
                ->orWhere('description', 'LIKE', $like)
                ->orWhere('location', 'LIKE', $like)
                ->orWhereHas('tags', fn (Builder $q) => $q->where('name', 'LIKE', $like))
                ->orWhereHas('account', fn (Builder $q) => $q->where('name', 'LIKE', $like));
        });
    }

    // ==================== Helpers ====================

    public function isPublished(): bool
    {
        return $this->status === PostStatus::Published->value;
    }

    /**
     * ينقص عدّاداً مخزّناً (likes / comments / shares) دون أن ينزل تحت الصفر
     */
    public function decrementCounter(string $column, int $amount = 1): void
    {
        $current = (int) $this->newQuery()->whereKey($this->getKey())->value($column);

        $this->newQuery()->whereKey($this->getKey())->update([$column => max(0, $current - $amount)]);
    }

    public function isOwnedBy(?Account $account): bool
    {
        return $account !== null && (int) $this->account_id === (int) $account->id;
    }
}
