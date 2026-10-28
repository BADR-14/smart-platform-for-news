<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminMagicLinkMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $url, public int $minutes) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'رابط الدخول إلى لوحة الإدارة');
    }

    public function content(): Content
    {
        $url = e($this->url);

        return new Content(
            htmlString: <<<HTML
                <div dir="rtl" style="font-family:Tahoma,Arial,sans-serif;color:#1B365D;line-height:1.8">
                    <h3 style="margin:0 0 12px">الدخول إلى لوحة إدارة Smart Platform for News</h3>
                    <p style="margin:0 0 16px">اضغط الزر التالي لتسجيل الدخول. لا حاجة لكلمة مرور.</p>
                    <p style="margin:0 0 16px">
                        <a href="{$url}" style="display:inline-block;background:#EA7A26;color:#ffffff;text-decoration:none;padding:12px 28px;border-radius:10px;font-weight:bold">دخول لوحة الإدارة</a>
                    </p>
                    <p style="margin:0 0 8px;color:#5B6B7F">الرابط صالح لمدة {$this->minutes} دقائق ولمرة واحدة فقط.</p>
                    <p style="margin:0;color:#5B6B7F">إذا لم تطلب هذا الرابط تجاهل الرسالة؛ لن يتمكن أحد من الدخول بدونه.</p>
                </div>
                HTML
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
