<?php

namespace App\Services\Verifications;

use App\Enums\Account\VerificationStatus;
use App\Enums\Verification\VerificationType;
use App\Exceptions\ApiException;
use App\Models\Account;
use App\Models\VerificationDocument;
use App\Models\VerificationRequest;
use Illuminate\Http\UploadedFile;

class BaseVerificationService
{
    /**
     * وثائق التوثيق حساسة (هويات وسجلات تجارية) فتُحفظ على القرص الخاص غير المتاح للعامة
     */
    public const DOCUMENTS_DISK = 'local';

    protected function ensureCanSubmit(Account $account, VerificationType $type): void
    {
        $requests = VerificationRequest::where('account_id', $account->id)->where('type', $type->value);

        if ((clone $requests)->where('status', VerificationStatus::Pending->value)->exists()) {
            throw new ApiException('لديك طلب توثيق قيد المراجعة حالياً.', 422, 'verification_pending');
        }

        if ($requests->count() >= (int) config('platform.verification.max_attempts', 3)) {
            throw new ApiException('لقد استنفدت كل محاولات التوثيق المتاحة.', 422, 'verification_attempts_exhausted');
        }
    }

    protected function createVerificationRequest(Account $account, VerificationType $type): VerificationRequest
    {
        return VerificationRequest::create([
            'account_id' => $account->id,
            'type' => $type->value,
            'status' => VerificationStatus::Pending->value,
        ]);
    }

    /**
     * @param  array<string, UploadedFile>  $documents  نوع الوثيقة => الملف
     */
    protected function storeDocuments(VerificationRequest $verificationRequest, array $documents): void
    {
        foreach ($documents as $type => $file) {
            VerificationDocument::create([
                'verification_request_id' => $verificationRequest->id,
                'document_type' => $type,
                'file_url' => $file->store("verification/{$verificationRequest->id}", self::DOCUMENTS_DISK),
            ]);
        }
    }
}
