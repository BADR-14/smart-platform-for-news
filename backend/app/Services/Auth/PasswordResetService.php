<?php

namespace App\Services\Auth;

use App\Exceptions\ApiException;
use App\Mail\SendOtpMail;
use App\Models\Account;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;

/**
 * استعادة كلمة المرور بطريقتين:
 *  - رابط بالبريد (لوحة الويب)        : sendLink / resetWithToken
 *  - رمز OTP بالبريد (تطبيق الموبايل) : sendCode / assertValidCode / resetWithCode
 */
class PasswordResetService
{
    private const CODE_TABLE = 'password_reset_tokens';

    public function __construct(private readonly OtpService $otpService) {}

    // ==================== الرابط (ويب) ====================

    public function sendLink(string $email): void
    {
        // Laravel لا يرسل شيئاً إذا لم يوجد الحساب، ونحن لا نكشف ذلك للعميل
        if ($this->localAccount($email)) {
            Password::sendResetLink(['email' => $email]);
        }
    }

    /**
     * @param  array{email: string, token: string, password: string, password_confirmation: string}  $data
     */
    public function resetWithToken(array $data): void
    {
        $status = Password::reset($data, fn (Account $account, string $password) => $this->changePassword($account, $password));

        if ($status !== Password::PASSWORD_RESET) {
            throw new ApiException('رابط إعادة التعيين غير صالح أو منتهي الصلاحية.', 422, 'invalid_reset_token');
        }
    }

    // ==================== الرمز (موبايل) ====================

    public function sendCode(string $email): void
    {
        if (! $this->localAccount($email)) {
            return;
        }

        $code = $this->otpService->generate();

        DB::table(self::CODE_TABLE)->updateOrInsert(
            ['email' => $email],
            ['token' => Hash::make($code), 'created_at' => now()],
        );

        Mail::to($email)->send(new SendOtpMail($code, SendOtpMail::PURPOSE_RESET_PASSWORD));
    }

    public function assertValidCode(string $email, string $code): void
    {
        $record = DB::table(self::CODE_TABLE)->where('email', $email)->first();

        $expiresInMinutes = (int) config('platform.otp.expires_in_minutes', 10);

        $isValid = $record
            && now()->subMinutes($expiresInMinutes)->lessThanOrEqualTo($record->created_at)
            && Hash::check($code, $record->token);

        if (! $isValid) {
            throw new ApiException('رمز التحقق غير صحيح أو منتهي الصلاحية.', 422, 'invalid_otp');
        }
    }

    public function resetWithCode(string $email, string $code, string $password): void
    {
        $this->assertValidCode($email, $code);

        $account = $this->localAccount($email);

        if (! $account) {
            throw new ApiException('رمز التحقق غير صحيح أو منتهي الصلاحية.', 422, 'invalid_otp');
        }

        $this->changePassword($account, $password);

        DB::table(self::CODE_TABLE)->where('email', $email)->delete();
    }

    // ==================== مشترك ====================

    private function changePassword(Account $account, string $password): void
    {
        $account->authCredentials()
            ->where('provider', 'local')
            ->update(['password_hash' => Hash::make($password)]);

        // تغيير كلمة المرور يُنهي كل الجلسات القديمة
        $account->tokens()->delete();
    }

    private function localAccount(string $email): ?Account
    {
        return Account::where('email', $email)
            ->whereHas('authCredentials', fn ($q) => $q->where('provider', 'local'))
            ->first();
    }
}
