<?php

namespace App\Services\Verifications;

use App\Enums\Account\AccountRole;
use App\Enums\Account\VerificationStatus;
use App\Enums\Verification\VerificationType;
use App\Exceptions\ApiException;
use App\Jobs\ProcessVerificationJob;
use App\Models\Account;
use App\Models\VerificationRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class UserVerificationService extends BaseVerificationService
{
    /**
     * @param  array{national_id: string, linkedin_url?: string|null}  $data
     * @param  array<string, UploadedFile>  $documents
     */
    public function submitUserVerification(Account $account, array $data, array $documents): VerificationRequest
    {
        if ($account->role() !== AccountRole::User) {
            throw new ApiException('توثيق المستخدم متاح لحسابات المستخدمين فقط.', 422, 'invalid_account_type');
        }

        if ($account->isVerified()) {
            throw new ApiException('حسابك موثّق مسبقاً.', 422, 'already_verified');
        }

        $this->ensureCanSubmit($account, VerificationType::User);

        $verificationRequest = DB::transaction(function () use ($account, $data, $documents) {
            $verificationRequest = $this->createVerificationRequest($account, VerificationType::User);

            $account->profile()->update(array_filter([
                'national_id' => $data['national_id'],
                'linkedin_url' => $data['linkedin_url'] ?? null,
            ], fn ($value) => $value !== null));

            $account->update(['verification_status' => VerificationStatus::Pending->value]);

            $this->storeDocuments($verificationRequest, $documents);

            return $verificationRequest;
        });

        ProcessVerificationJob::dispatch($verificationRequest->id);

        return $verificationRequest;
    }
}
