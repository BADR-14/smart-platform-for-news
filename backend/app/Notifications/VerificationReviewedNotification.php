<?php

namespace App\Notifications;

use App\Enums\Notification\NotificationType;
use App\Models\VerificationRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class VerificationReviewedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly VerificationRequest $verificationRequest,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $isApproved = $this->verificationRequest->status === 'approved';

        return [
            'type' => NotificationType::VerificationReviewed->value,
            'title' => $isApproved ? 'تم توثيق حسابك بنجاح' : 'تم رفض طلب توثيق حسابك',
            'body' => $isApproved
                ? 'تهانينا! تمت مراجعة طلبك والموافقة على توثيق حسابك.'
                : 'نأسف، تم رفض طلب توثيق حسابك بعد المراجعة. يمكنك الاطلاع على الأسباب وإعادة التقديم.',
            'verification_request_id' => $this->verificationRequest->id,
            'status' => $this->verificationRequest->status,
            'rejection_reasons' => $this->verificationRequest->rejection_reasons,
        ];
    }
}
