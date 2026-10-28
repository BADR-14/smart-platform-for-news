<?php

namespace App\Notifications;

use App\Models\Account;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * أساس إشعارات لوحة الأدمن: تُحفظ في قاعدة البيانات، وتُرسل بالبريد أيضاً
 * إذا كان الأدمن مفعّلاً خيار "إشعارات البريد" في إعداداته.
 */
abstract class BaseNotification extends Notification
{
    use Queueable;

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($this->shouldSendEmail($notifiable)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    protected function shouldSendEmail(object $notifiable): bool
    {
        if (! $notifiable instanceof Account || empty($notifiable->email)) {
            return false;
        }

        return $notifiable->notificationPreference?->email_notifications ?? true;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $data = $this->toDatabase($notifiable);

        return (new MailMessage)
            ->subject($data['title'] ?? 'إشعار جديد')
            ->greeting($data['title'] ?? '')
            ->line($data['body'] ?? '');
    }

    /**
     * @return array<string, mixed>
     */
    abstract public function toDatabase(object $notifiable): array;
}
