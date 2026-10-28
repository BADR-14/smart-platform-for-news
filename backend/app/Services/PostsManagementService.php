<?php

namespace App\Services;

use App\Enums\Post\PostStatus;
use App\Models\Post;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * إدارة المنشورات من لوحة الأدمن
 */
class PostsManagementService
{
    public function __construct(private readonly NotificationService $notificationService) {}

    /**
     * @return array<string, int>
     */
    public function getPostsStats(): array
    {
        $counts = Post::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'total_news' => (int) $counts->sum(),
            'published' => (int) ($counts[PostStatus::Published->value] ?? 0),
            'under_review' => (int) ($counts[PostStatus::UnderReview->value] ?? 0),
            'archived' => (int) ($counts[PostStatus::Archived->value] ?? 0),
            'rejected' => (int) ($counts[PostStatus::Rejected->value] ?? 0),
            'draft' => (int) ($counts[PostStatus::Draft->value] ?? 0),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function getFilteredPosts(array $filters): LengthAwarePaginator
    {
        $days = match ($filters['date_filter'] ?? null) {
            'last_7' => 7,
            'last_30' => 30,
            'last_90' => 90,
            default => null,
        };

        return Post::query()
            ->with(['account.accountType', 'category', 'activeFeature'])
            ->withCount(['reports as pending_reports_count' => fn (Builder $q) => $q->where('status', 'pending')])
            ->when($filters['search'] ?? null, fn (Builder $q, $search) => $q->where('title', 'LIKE', "%{$search}%"))
            ->when($filters['status'] ?? null, fn (Builder $q, $status) => $q->where('status', $status))
            ->when($filters['category_id'] ?? null, fn (Builder $q, $id) => $q->where('category_id', $id))
            ->when($days, fn (Builder $q) => $q->where('created_at', '>=', now()->subDays($days)))
            ->latest('id')
            ->paginate(15);
    }

    /**
     * الأدمن يضع المنشور قيد المراجعة، يرفضه، يعيد نشره، أو يؤرشفه — ويُبلَّغ صاحبه
     */
    public function changeStatus(Post $post, PostStatus $status, ?string $note = null): Post
    {
        if ($post->status === $status->value) {
            return $post;
        }

        $post->update([
            'status' => $status->value,
            'moderation_note' => $status === PostStatus::Published ? null : $note,
        ]);

        $this->notificationService->notifyPostModerated($post);

        return $post;
    }

    /**
     * تبديل بين مؤرشف ومنشور
     */
    public function toggleArchiveStatus(Post $post): Post
    {
        return $this->changeStatus(
            $post,
            $post->status === PostStatus::Archived->value ? PostStatus::Published : PostStatus::Archived,
        );
    }

    public function deletePost(Post $post): void
    {
        $post->loadMissing('account');

        if ($post->account) {
            $this->notificationService->notifyPostDeleted($post->account, $post->id, $post->title);
        }

        $post->delete();
    }
}
