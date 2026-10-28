<?php

namespace App\Services\Auth;

use App\Enums\Account\AccountRole;
use App\Enums\Account\VerificationStatus;
use App\Exceptions\ApiException;
use App\Models\Account;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function __construct(
        private readonly OtpService $otpService,
        private readonly NotificationService $notificationService,
    ) {}

    /**
     * إنشاء حساب مستخدم جديد. لا يُصدر توكن هنا: التوكن يُعطى فقط بعد تأكيد البريد.
     *
     * @param  array{name: string, email: string, password: string}  $data
     */
    public function register(array $data): Account
    {
        $account = DB::transaction(function () use ($data) {
            $account = Account::create([
                'account_type_id' => AccountRole::User->value,
                'name' => $data['name'],
                'email' => $data['email'],
                'verification_status' => VerificationStatus::NotSubmitted->value,
            ]);

            $account->authCredentials()->create([
                'provider' => 'local',
                'password_hash' => Hash::make($data['password']),
            ]);

            $account->user()->create(['system_role' => 'user']);

            return $account;
        });

        $this->otpService->sendEmailVerification($account);

        return $account;
    }

    /**
     * تأكيد البريد بالرمز ثم تسجيل الدخول مباشرة
     *
     * @return array{account: Account, token: string}
     */
    public function verifyEmail(string $email, string $code, ?string $deviceName = null): array
    {
        $account = Account::where('email', $email)->first();

        if (! $account || ! $this->otpService->isValidFor($account, $code)) {
            throw new ApiException('رمز التحقق غير صحيح أو منتهي الصلاحية.', 422, 'invalid_otp');
        }

        $isFirstVerification = ! $account->hasVerifiedEmail();

        $account->forceFill([
            'email_verified_at' => $account->email_verified_at ?? now(),
            'otp_code' => null,
            'otp_expires_at' => null,
        ])->save();

        if ($isFirstVerification) {
            $this->notificationService->notifyAuthActivity($account, 'register');
            $this->notificationService->notifyNewUserRegistered($account);
        }

        return [
            'account' => $account,
            'token' => $this->issueToken($account, $deviceName),
        ];
    }

    /**
     * إعادة إرسال رمز التأكيد. لا نكشف إن كان البريد مسجلاً أم لا.
     */
    public function resendOtp(string $email): void
    {
        $account = Account::where('email', $email)->first();

        if ($account && ! $account->hasVerifiedEmail()) {
            $this->otpService->sendEmailVerification($account);
        }
    }

    /**
     * @return array{account: Account, token: string}
     */
    public function login(string $email, string $password, ?string $fcmToken = null, ?string $deviceName = null): array
    {
        $account = Account::where('email', $email)->first();
        $credential = $account?->authCredentials()->where('provider', 'local')->first();

        // رسالة واحدة للحالتين حتى لا نكشف وجود البريد من عدمه
        if (! $account || ! $credential?->password_hash || ! Hash::check($password, $credential->password_hash)) {
            throw new ApiException('البريد الإلكتروني أو كلمة المرور غير صحيحة.', 401, 'invalid_credentials');
        }

        // الأدمن لا يدخل بكلمة مرور إطلاقاً: دخوله من موقع الإدارة بالرابط السحري فقط.
        // نرجع نفس رسالة الخطأ العامة حتى لا نكشف أن هذا البريد لحساب أدمن.
        if ($account->isAdmin()) {
            throw new ApiException('البريد الإلكتروني أو كلمة المرور غير صحيحة.', 401, 'invalid_credentials');
        }

        $this->ensureCanSignIn($account);

        if (! $account->hasVerifiedEmail()) {
            throw new ApiException(
                'يجب تأكيد بريدك الإلكتروني قبل تسجيل الدخول.',
                403,
                'email_unverified',
                ['email' => $account->email],
            );
        }

        if ($fcmToken) {
            $account->forceFill(['fcm_token' => $fcmToken])->save();
        }

        $this->notificationService->notifyAuthActivity($account, 'login');

        return [
            'account' => $account,
            'token' => $this->issueToken($account, $deviceName),
        ];
    }

    public function logout(Account $account): void
    {
        $account->currentAccessToken()?->delete();
    }

    public function ensureCanSignIn(Account $account): void
    {
        if (! $account->is_active) {
            throw new ApiException(
                'حسابك محظور أو معطّل. الرجاء التواصل مع الإدارة.',
                403,
                'account_banned',
                ['reason' => $account->ban_reason],
            );
        }
    }

    public function issueToken(Account $account, ?string $deviceName = null): string
    {
        return $account->createToken($deviceName ?: 'auth_token')->plainTextToken;
    }
}
