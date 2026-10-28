<?php

namespace App\Notifications;

use App\Enums\Notification\NotificationType;
use App\Models\Account;
use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewFollowerNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Account $follower,
        private readonly Company $company,
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
            'type' => NotificationType::NewFollower->value,
            'title' => 'متابع جديد',
            'body' => "بدأ {$this->follower->name} بمتابعتك",
            'company_id' => $this->company->id,
            'follower_account_id' => $this->follower->id,
        ];
    }
}
