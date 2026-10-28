<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Exceptions\PostNotPublishedException;
use App\Models\Account;
use App\Models\Post;
use App\Models\Review;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ReviewService
{
    public function __construct(private readonly NotificationService $notificationService) {}

    public function getPostReviews(Post $post): LengthAwarePaginator
    {
        return $post->reviews()
            ->with(['user.account.accountType', 'user.account.company'])
            ->latest()
            ->paginate(10);
    }

    /**
     * مستخدم واحد = تقييم واحد لكل منشور (إعادة التقييم تعدّل التقييم السابق)
     *
     * @param  array{rating: float|int|string, content?: string|null}  $data
     */
    public function storeReview(Account $account, Post $post, array $data): Review
    {
        if (! $post->isPublished()) {
            throw new PostNotPublishedException;
        }

        if ($post->isOwnedBy($account)) {
            throw new ApiException('لا يمكنك تقييم منشورك.', 422, 'cannot_review_own_post');
        }

        // withTrashed: القيد الفريد (user_id, post_id) يشمل التقييمات المحذوفة أيضاً
        $review = Review::withTrashed()->firstOrNew([
            'user_id' => $account->profile()->id,
            'post_id' => $post->id,
        ]);

        $review->fill(['rating' => $data['rating'], 'content' => $data['content'] ?? null]);
        $review->deleted_at = null;
        $review->save();

        $review->load(['post.account', 'post.category', 'user.account.accountType', 'user.account.company']);

        if ($review->wasRecentlyCreated) {
            $this->notificationService->notifyPostReviewed($review);
        }

        return $review;
    }
}
