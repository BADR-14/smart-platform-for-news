<?php

namespace App\Listeners;

use App\Models\Account;
use App\Services\External\FcmService;
use Illuminate\Notifications\Events\NotificationSent;

/**
 * كل إشعار يُحفظ في قاعدة البيانات يُرسل أيضاً كـ Push لجهاز صاحبه (إن كان له fcm_token).
 * بهذا لا يحتاج أي صنف إشعار أن يعرف شيئاً عن FCM.
 *
 * يُسجَّل تلقائياً عبر Event Discovery في Laravel (لا حاجة لتسجيله في AppServiceProvider).
 */
class SendPushNotification
{
    public function __construct(private readonly FcmService $fcmService) {}

    public function handle(NotificationSent $event): void
    {
        if ($event->channel !== 'database' || ! $event->notifiable instanceof Account) {
            return;
        }

        $token = $event->notifiable->fcm_token;

        if (! $token || ! method_exists($event->notification, 'toDatabase')) {
            return;
        }

        $payload = $event->notification->toDatabase($event->notifiable);

        $this->fcmService->send(
            $token,
            (string) ($payload['title'] ?? ''),
            (string) ($payload['body'] ?? ''),
            collect($payload)->except(['title', 'body'])->all(),
        );
    }
}
