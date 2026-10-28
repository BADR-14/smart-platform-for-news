<?php

namespace App\Notifications;

use App\Enums\Notification\NotificationType;
use App\Enums\Post\PostStatus;
use App\Models\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * يُرسل لصاحب المنشور عندما يغيّر الأدمن حالة منشوره (قيد المراجعة / مرفوض / أُعيد نشره / مؤرشف)
 */
class PostModeratedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Post $post) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        [$title, $body] = match ($this->post->status) {
            PostStatus::UnderReview->value => [
                'منشورك قيد المراجعة',
                "وضعت الإدارة منشورك \"{$this->post->title}\" قيد المراجعة وتم إخفاؤه مؤقتاً.",
            ],
            PostStatus::Rejected->value => [
                'تم رفض منشورك',
                "رفضت الإدارة منشورك \"{$this->post->title}\" لمخالفته سياسات المنصة.",
            ],
            PostStatus::Published->value => [
                'تمت إعادة نشر منشورك',
                "أعادت الإدارة نشر منشورك \"{$this->post->title}\".",
            ],
            default => [
                'تم تغيير حالة منشورك',
                "غيّرت الإدارة حالة منشورك \"{$this->post->title}\".",
            ],
        };

        return [
            'type' => NotificationType::PostModerated->value,
            'title' => $title,
            'body' => $body,
            'post_id' => $this->post->id,
            'status' => $this->post->status,
            'note' => $this->post->moderation_note,
        ];
    }
}
