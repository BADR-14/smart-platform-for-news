<?php

namespace App\Notifications;

use App\Enums\Notification\NotificationType;
use App\Models\Account;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PostInteractionNotification extends Notification
{
    use Queueable;

    /**
     * @param  string  $action  'reacted' | 'commented'
     */
    public function __construct(
        private readonly Post $post,
        private readonly Account $actorAccount,
        private readonly string $action,
        private readonly ?Comment $comment = null,
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
        $isReact = $this->action === 'reacted';

        return [
            'type' => $isReact
                ? NotificationType::PostReacted->value
                : NotificationType::PostCommented->value,
            'title' => $isReact ? 'تفاعل جديد على خبرك' : 'تعليق جديد على خبرك',
            'body' => $isReact
                ? "قام {$this->actorAccount->name} بالتفاعل مع خبرك \"{$this->post->title}\""
                : "قام {$this->actorAccount->name} بالتعليق على خبرك \"{$this->post->title}\"",
            'post_id' => $this->post->id,
            'comment_id' => $this->comment?->id,
            'actor_account_id' => $this->actorAccount->id,
        ];
    }
}
 