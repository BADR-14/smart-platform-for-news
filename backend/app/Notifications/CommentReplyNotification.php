<?php

namespace App\Notifications;

use App\Enums\Notification\NotificationType;
use App\Models\Comment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CommentReplyNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Comment $reply,
        private readonly Comment $parentComment,
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
        $post = $this->parentComment->post;
        $replierName = $this->reply->user?->account?->name ?? 'مستخدم';

        return [
            'type' => NotificationType::CommentReply->value,
            'title' => 'رد جديد على تعليقك',
            'body' => "قام {$replierName} بالرد على تعليقك في خبر \"{$post?->title}\"",
            'post_id' => $post?->id,
            'comment_id' => $this->parentComment->id,
            'reply_id' => $this->reply->id,
            'actor_account_id' => $this->reply->user?->account_id,
        ];
    }
}
