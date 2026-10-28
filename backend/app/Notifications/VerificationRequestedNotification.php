<?php

namespace App\Notifications;

use App\Enums\Notification\NotificationType;
use App\Models\VerificationRequest;

class VerificationRequestedNotification extends BaseNotification
{
    public function __construct(private readonly VerificationRequest $verificationRequest) {}

    public function toDatabase(object $notifiable): array
    {
        $accountName = $this->verificationRequest->account?->name ?? 'حساب';
        $kind = $this->verificationRequest->isForCompany() ? 'شركة' : 'مستخدم';

        return [
            'type' => NotificationType::VerificationRequested->value,
            'title' => 'طلب توثيق بانتظار المراجعة',
            'body' => "طلب توثيق {$kind} من {$accountName} يحتاج إلى مراجعتك.",
            'verification_request_id' => $this->verificationRequest->id,
            'verification_type' => $this->verificationRequest->type,
            'account_id' => $this->verificationRequest->account_id,
        ];
    }
}
