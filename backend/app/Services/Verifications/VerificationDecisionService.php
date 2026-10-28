<?php

namespace App\Services\Verifications;

use App\Enums\Account\AccountRole;
use App\Enums\Account\VerificationStatus;
use App\Models\Account;
use App\Models\VerificationRequest;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;

/**
 * المكان الوحيد الذي يطبّق نتيجة طلب التوثيق على الحساب،
 * سواء جاء القرار آلياً من الـ Job أو يدوياً من الأدمن.
 */
class VerificationDecisionService
{
    public function __construct(private readonly NotificationService $notificationService) {}

    /**
     * @param  list<string>|null  $rejectionReasons
     */
    public function apply(
        VerificationRequest $request,
        VerificationStatus $status,
        ?array $rejectionReasons = null,
        ?int $reviewerUserId = null,
        ?int $confidenceScore = null,
    ): VerificationRequest {
        $isFinal = in_array($status, [VerificationStatus::Approved, VerificationStatus::Rejected], true);

        DB::transaction(function () use ($request, $status, $rejectionReasons, $reviewerUserId, $confidenceScore, $isFinal) {
            $request->update([
                'status' => $status->value,
                'rejection_reasons' => $status === VerificationStatus::Approved ? null : $rejectionReasons,
                'confidence_score' => $confidenceScore ?? $request->confidence_score,
                'reviewed_by' => $reviewerUserId,
                'reviewed_at' => $isFinal ? now() : null,
            ]);

            $account = $request->account;

            $request->isForCompany()
                ? $this->applyToCompanyAccount($account, $status)
                : $account->update(['verification_status' => $status->value]);
        });

        if ($isFinal) {
            $this->notificationService->notifyVerificationReviewed($request->fresh('account'));
        }

        return $request;
    }

    /**
     * عند الموافقة على طلب الشركة يتحول نفس الحساب إلى حساب شركة موثّق
     * ويأخذ اسم الشركة وشعارها. عند الرفض يبقى الحساب كما كان (مستخدم).
     */
    private function applyToCompanyAccount(Account $account, VerificationStatus $status): void
    {
        if ($status !== VerificationStatus::Approved) {
            return;
        }

        $company = $account->company;

        $account->update([
            'account_type_id' => AccountRole::Company->value,
            'verification_status' => VerificationStatus::Approved->value,
            'name' => $company?->name ?: $account->name,
            'avatar' => $company?->logo_url ?: $account->avatar,
        ]);
    }
}
