<?php

namespace App\Notifications;

use App\Enums\Notification\NotificationType;
use App\Models\Account;

class NewUserRegisteredNotification extends BaseNotification
{
    public function __construct(private readonly Account $account) {}

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => NotificationType::NewUserRegistered->value,
            'title' => 'مستخدم جديد',
            'body' => "انضم {$this->account->name} إلى المنصة.",
            'account_id' => $this->account->id,
        ];
    }
}
