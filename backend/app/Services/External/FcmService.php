<?php

namespace App\Services\External;

use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * إرسال إشعارات Push عبر Firebase Cloud Messaging (HTTP v1).
 * إذا لم يُضبط ملف مفتاح Firebase تبقى الإشعارات داخل التطبيق فقط دون أي خطأ.
 */
class FcmService
{
    private const SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';

    public function isConfigured(): bool
    {
        $path = config('services.fcm.credentials');

        return is_string($path) && $path !== '' && is_file($path) && filled($this->projectId());
    }

    /**
     * @param  array<string, mixed>  $data  بيانات إضافية يستخدمها التطبيق للتنقل عند الضغط على الإشعار
     */
    public function send(string $deviceToken, string $title, string $body, array $data = []): bool
    {
        if (! $this->isConfigured()) {
            return false;
        }

        try {
            $response = Http::withToken($this->accessToken())
                ->timeout(10)
                ->post("https://fcm.googleapis.com/v1/projects/{$this->projectId()}/messages:send", [
                    'message' => [
                        'token' => $deviceToken,
                        'notification' => ['title' => $title, 'body' => $body],
                        // FCM يقبل قيماً نصية فقط داخل data
                        'data' => array_map(
                            fn ($value) => is_scalar($value) ? (string) $value : (string) json_encode($value),
                            array_filter($data, fn ($value) => $value !== null),
                        ),
                    ],
                ]);

            return $response->successful();
        } catch (Throwable $e) {
            Log::warning('FCM push failed', ['error' => $e->getMessage()]);

            return false;
        }
    }

    private function projectId(): ?string
    {
        if ($id = config('services.fcm.project_id')) {
            return $id;
        }

        $credentials = json_decode((string) @file_get_contents((string) config('services.fcm.credentials')), true);

        return $credentials['project_id'] ?? null;
    }

    private function accessToken(): string
    {
        // توكن Google صالح لساعة، فنخزّنه 50 دقيقة بدل طلبه مع كل إشعار
        return Cache::remember('fcm_access_token', now()->addMinutes(50), function () {
            $credentials = new ServiceAccountCredentials(self::SCOPE, config('services.fcm.credentials'));

            return $credentials->fetchAuthToken()['access_token'];
        });
    }
}
