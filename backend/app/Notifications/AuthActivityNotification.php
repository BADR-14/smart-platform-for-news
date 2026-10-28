<?php

namespace App\Notifications;

use App\Enums\Notification\NotificationType;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AuthActivityNotification extends Notification
{
    use Queueable;

    /**
     * @param  'register'|'login'  $action
     */
    public function __construct(private readonly string $action) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        [$type, $title, $body] = match ($this->action) {
            'register' => [
                NotificationType::AccountCreated->value,
                'مرحباً بك',
                'تم إنشاء حسابك بنجاح.',
            ],
            default => [
                NotificationType::LoginSuccess->value,
                'تسجيل دخول جديد',
                'تم تسجيل الدخول إلى حسابك.',
            ],
        };

        return [
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'action' => $this->action,
        ];
    }
}
