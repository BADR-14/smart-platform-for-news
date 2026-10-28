<?php

namespace App\Services\Auth;

use App\Enums\Account\AccountRole;
use App\Exceptions\ApiException;
use App\Models\Account;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * تسجيل الدخول بـ Google لتطبيق الموبايل:
 * التطبيق يحصل على id_token من Google ويرسله هنا، ونحن نتحقق منه لدى Google
 * ونتأكد أنه صادر لأحد تطبيقاتنا (aud) قبل إنشاء الحساب أو تسجيل الدخول.
 */
class GoogleAuthService
{
    private const TOKEN_INFO_URL = 'https://oauth2.googleapis.com/tokeninfo';

    public function __construct(
        private readonly AuthService $authService,
        private readonly NotificationService $notificationService,
    ) {}

    /**
     * @return array{account: Account, token: string, is_new: bool}
     */
    public function loginWithIdToken(string $idToken, ?string $deviceName = null): array
    {
        $googleUser = $this->verifyIdToken($idToken);

        [$account, $isNew] = DB::transaction(function () use ($googleUser) {
            $account = Account::where('email', $googleUser['email'])->first();
            $isNew = false;

            // حساب الأدمن يدخل بالرابط السحري فقط
            if ($account?->isAdmin()) {
                throw new ApiException('لا يمكن استخدام هذا الحساب لتسجيل الدخول هنا.', 403, 'forbidden');
            }

            if (! $account) {
                $account = Account::create([
                    'account_type_id' => AccountRole::User->value,
                    'name' => $googleUser['name'],
                    'email' => $googleUser['email'],
                    'email_verified_at' => now(),
                ]);
                $account->user()->create(['system_role' => 'user']);
                $isNew = true;
            }

            $account->authCredentials()->firstOrCreate(
                ['provider' => 'google'],
                ['provider_identifier' => $googleUser['sub'], 'password_hash' => null],
            );

            // Google أكّد ملكية البريد، فنعتبره مؤكداً عندنا أيضاً
            if (! $account->hasVerifiedEmail()) {
                $account->forceFill(['email_verified_at' => now()])->save();
            }

            return [$account, $isNew];
        });

        $this->authService->ensureCanSignIn($account);

        $this->notificationService->notifyAuthActivity($account, $isNew ? 'register' : 'login');

        if ($isNew) {
            $this->notificationService->notifyNewUserRegistered($account);
        }

        return [
            'account' => $account,
            'token' => $this->authService->issueToken($account, $deviceName ?: 'google_token'),
            'is_new' => $isNew,
        ];
    }

    /**
     * @return array{sub: string, email: string, name: string}
     */
    private function verifyIdToken(string $idToken): array
    {
        $response = Http::timeout(10)->get(self::TOKEN_INFO_URL, ['id_token' => $idToken]);

        $payload = $response->json() ?? [];
        $allowedAudiences = config('services.google.allowed_client_ids', []);

        $isValid = $response->successful()
            && ! empty($payload['email'])
            && in_array($payload['aud'] ?? null, $allowedAudiences, true)
            && filter_var($payload['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if (! $isValid) {
            throw new ApiException('تعذّر التحقق من حساب Google.', 401, 'invalid_google_token');
        }

        return [
            'sub' => (string) $payload['sub'],
            'email' => (string) $payload['email'],
            'name' => (string) ($payload['name'] ?? strstr($payload['email'], '@', true)),
        ];
    }
}
