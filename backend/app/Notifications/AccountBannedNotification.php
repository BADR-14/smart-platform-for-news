<?php

namespace App\Notifications;

use App\Enums\Notification\NotificationType;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AccountBannedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly string $reason) {}

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
            'type' => NotificationType::AccountBanned->value,
            'title' => 'تم حظر حسابك',
            'body' => "تم حظر حسابك من قبل الإدارة. السبب: {$this->reason}",
            'reason' => $this->reason,
        ];
    }
}
