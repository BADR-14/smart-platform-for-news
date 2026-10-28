<?php

namespace App\Services\Auth;

use App\Mail\SendOtpMail;
use App\Models\Account;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

/**
 * توليد رموز التحقق (OTP) وإرسالها والتحقق منها.
 * الرمز يُخزَّن مشفّراً (hash) ولا يخرج في أي رد.
 */
class OtpService
{
    public function generate(): string
    {
        $length = (int) config('platform.otp.length', 6);

        return str_pad((string) random_int(0, (10 ** $length) - 1), $length, '0', STR_PAD_LEFT);
    }

    /**
     * يولّد رمز تأكيد البريد للحساب ويرسله بالإيميل
     */
    public function sendEmailVerification(Account $account): void
    {
        $code = $this->generate();

        $account->forceFill([
            'otp_code' => Hash::make($code),
            'otp_expires_at' => now()->addMinutes((int) config('platform.otp.expires_in_minutes', 10)),
        ])->save();

        Mail::to($account->email)->send(new SendOtpMail($code, SendOtpMail::PURPOSE_VERIFY_EMAIL));
    }

    public function isValidFor(Account $account, string $code): bool
    {
        if (! $account->otp_code || ! $account->otp_expires_at) {
            return false;
        }

        if ($account->otp_expires_at->isPast()) {
            return false;
        }

        return Hash::check($code, $account->otp_code);
    }

    public function clear(Account $account): void
    {
        $account->forceFill(['otp_code' => null, 'otp_expires_at' => null])->save();
    }
}
