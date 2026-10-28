<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SendOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public const PURPOSE_VERIFY_EMAIL = 'verify_email';

    public const PURPOSE_RESET_PASSWORD = 'reset_password';

    public function __construct(
        public string $otpCode,
        public string $purpose = self::PURPOSE_VERIFY_EMAIL,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->purpose === self::PURPOSE_RESET_PASSWORD
                ? 'رمز إعادة تعيين كلمة المرور'
                : 'رمز تأكيد البريد الإلكتروني',
        );
    }

    public function content(): Content
    {
        $minutes = (int) config('platform.otp.expires_in_minutes', 10);

        $intro = $this->purpose === self::PURPOSE_RESET_PASSWORD
            ? 'استلمنا طلباً لإعادة تعيين كلمة المرور لحسابك.'
            : 'أهلاً بك في Smart Platform for News.';

        return new Content(
            htmlString: <<<HTML
                <div dir="rtl" style="font-family:Tahoma,Arial,sans-serif;color:#1B365D;line-height:1.8">
                    <h3 style="margin:0 0 12px">{$intro}</h3>
                    <p style="margin:0 0 8px">رمز التحقق الخاص بك:</p>
                    <p style="font-size:28px;font-weight:bold;letter-spacing:6px;color:#E07B39;margin:0 0 12px">{$this->otpCode}</p>
                    <p style="margin:0;color:#5B6B7F">الرمز صالح لمدة {$minutes} دقائق. إذا لم تطلب هذا الرمز تجاهل هذه الرسالة.</p>
                </div>
                HTML
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
