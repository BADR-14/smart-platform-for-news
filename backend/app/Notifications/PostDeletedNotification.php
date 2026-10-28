<?php

namespace App\Notifications;

use App\Enums\Notification\NotificationType;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PostDeletedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly int $postId,
        private readonly string $postTitle,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => NotificationType::PostDeleted->value,
            'title' => 'تم حذف خبرك',
            'body' => "قامت الإدارة بحذف خبرك \"{$this->postTitle}\" لمخالفته سياسات المنصة.",
            'post_id' => $this->postId,
            'post_title' => $this->postTitle,
        ];
    }
}
