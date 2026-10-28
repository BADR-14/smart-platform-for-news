<?php

namespace App\Services\Verifications;

use App\Enums\Account\VerificationStatus;
use App\Enums\Verification\VerificationType;
use App\Models\Account;
use App\Models\VerificationRequest;

class VerificationStatusService
{
    /**
     * حالة التوثيق كما تحتاجها شاشة التوثيق في التطبيق:
     * حالة الحساب العامة + تفاصيل آخر طلب لكل نوع (مستخدم / شركة).
     *
     * @return array<string, mixed>
     */
    public function forAccount(Account $account): array
    {
        return [
            'status' => $account->verification_status,
            'is_verified' => $account->isVerified(),
            'account_type' => $account->role()->slug(),
            'user' => $this->summary($account, VerificationType::User),
            'company' => $this->summary($account, VerificationType::Company),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function summary(Account $account, VerificationType $type): array
    {
        $requests = VerificationRequest::where('account_id', $account->id)
            ->where('type', $type->value)
            ->latest('id')
            ->get();

        $latest = $requests->first();
        $maxAttempts = (int) config('platform.verification.max_attempts', 3);
        $attemptsLeft = max(0, $maxAttempts - $requests->count());

        $status = $latest?->status ?? VerificationStatus::NotSubmitted->value;

        return [
            'status' => $status,
            'rejection_reasons' => $status === VerificationStatus::Rejected->value ? ($latest->rejection_reasons ?? []) : [],
            'attempts_left' => $attemptsLeft,
            'can_submit' => $attemptsLeft > 0
                && ! in_array($status, [VerificationStatus::Pending->value, VerificationStatus::Approved->value], true),
            'submitted_at' => $latest?->created_at,
        ];
    }
}
