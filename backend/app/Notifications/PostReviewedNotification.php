<?php

namespace App\Notifications;

use App\Enums\Notification\NotificationType;
use App\Models\Review;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PostReviewedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Review $review) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $post = $this->review->post;
        $raterName = $this->review->user?->account?->name ?? 'مستخدم';

        return [
            'type' => NotificationType::PostReviewed->value,
            'title' => 'تقييم جديد على خبرك',
            'body' => "قام {$raterName} بتقييم خبرك \"{$post?->title}\" بـ {$this->review->rating} من 5",
            'post_id' => $post?->id,
            'review_id' => $this->review->id,
            'rating' => $this->review->rating,
            'actor_account_id' => $this->review->user?->account_id,
        ];
    }
}
