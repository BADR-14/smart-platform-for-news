<?php

namespace App\Services\Verifications;

use App\Enums\Account\AccountRole;
use App\Enums\Verification\VerificationType;
use App\Exceptions\ApiException;
use App\Jobs\ProcessVerificationJob;
use App\Models\Account;
use App\Models\Company;
use App\Models\CompanyMembership;
use App\Services\NotificationService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CompanyVerificationService extends BaseVerificationService
{
    public function __construct(private readonly NotificationService $notificationService) {}

    /**
     * طلب إنشاء شركة: يحفظ بيانات الشركة ويقدّم طلب توثيقها بخطوة واحدة.
     * الحساب يبقى "مستخدم" حتى تتم الموافقة على الطلب، وعندها يتحول إلى حساب شركة.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, UploadedFile>  $documents
     */
    public function submitCompanyVerification(Account $account, array $data, UploadedFile $logo, array $documents): Company
    {
        if ($account->role() !== AccountRole::User) {
            throw new ApiException('حسابك مسجّل كشركة مسبقاً.', 422, 'invalid_account_type');
        }

        $this->ensureCanSubmit($account, VerificationType::Company);

        [$company, $verificationRequest, $isNewCompany] = DB::transaction(function () use ($account, $data, $logo, $documents) {
            $company = Company::firstOrNew(['account_id' => $account->id]);
            $isNewCompany = ! $company->exists;
            $oldLogo = $company->logo_url;

            // إعادة التقديم بعد الرفض تحدّث بيانات نفس الشركة
            $company->fill([
                'name' => $data['name'],
                'website_url' => $data['website_url'] ?? null,
                'category_id' => $data['category_id'] ?? null,
                'description' => $data['description'] ?? null,
                'address' => $data['address'] ?? null,
                'phone' => $data['phone'],
                'company_registration_number' => $data['company_registration_number'],
                'tax_id' => $data['tax_id'],
                'logo_url' => $logo->store('logos', 'public'),
            ])->save();

            if ($oldLogo) {
                Storage::disk('public')->delete($oldLogo);
            }

            CompanyMembership::firstOrCreate(
                ['company_id' => $company->id, 'user_id' => $account->profile()->id],
                ['role' => 'owner'],
            );

            $verificationRequest = $this->createVerificationRequest($account, VerificationType::Company);
            $this->storeDocuments($verificationRequest, $documents);

            return [$company, $verificationRequest, $isNewCompany];
        });

        if ($isNewCompany) {
            $this->notificationService->notifyNewCompanyRegistered($company);
        }

        ProcessVerificationJob::dispatch($verificationRequest->id);

        return $company->load(['account', 'category']);
    }
}
