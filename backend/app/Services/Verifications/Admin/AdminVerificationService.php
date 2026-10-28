<?php

namespace App\Services\Verifications\Admin;

use App\Enums\Account\VerificationStatus;
use App\Exceptions\ApiException;
use App\Models\Account;
use App\Models\VerificationDocument;
use App\Models\VerificationRequest;
use App\Services\Verifications\BaseVerificationService;
use App\Services\Verifications\VerificationDecisionService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminVerificationService
{
    private const RELATIONS = ['account.accountType', 'account.user', 'account.company.category', 'documents', 'reviewer.account'];

    public function __construct(private readonly VerificationDecisionService $decisionService) {}

    /**
     * @param  array{status?: string|null, type?: string|null, search?: string|null}  $filters
     */
    public function getRequests(array $filters): LengthAwarePaginator
    {
        return VerificationRequest::query()
            ->with(self::RELATIONS)
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['type'] ?? null, fn ($q, $type) => $q->where('type', $type))
            ->when($filters['search'] ?? null, fn ($q, $search) => $q->whereHas(
                'account',
                fn ($a) => $a->where('name', 'LIKE', "%{$search}%")->orWhere('email', 'LIKE', "%{$search}%")
            ))
            ->latest('id')
            ->paginate(10);
    }

    /**
     * @return array<string, int>
     */
    public function getStats(): array
    {
        $counts = VerificationRequest::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'pending' => (int) ($counts['pending'] ?? 0),
            'approved' => (int) ($counts['approved'] ?? 0),
            'rejected' => (int) ($counts['rejected'] ?? 0),
            'total' => (int) $counts->sum(),
        ];
    }

    public function getRequest(VerificationRequest $verificationRequest): VerificationRequest
    {
        return $verificationRequest->load(self::RELATIONS);
    }

    /**
     * @param  list<string>|null  $rejectionReasons
     */
    public function reviewDecision(
        VerificationRequest $verificationRequest,
        string $decision,
        ?array $rejectionReasons,
        Account $admin,
    ): VerificationRequest {
        if ($verificationRequest->status !== VerificationStatus::Pending->value) {
            throw new ApiException('تمت مراجعة هذا الطلب مسبقاً.', 422, 'already_reviewed');
        }

        $this->decisionService->apply(
            $verificationRequest,
            VerificationStatus::from($decision),
            rejectionReasons: $rejectionReasons,
            reviewerUserId: $admin->profile()->id,
        );

        return $this->getRequest($verificationRequest->refresh());
    }

    public function getAccountHistory(Account $account): Collection
    {
        return $account->verificationRequests()
            ->with(self::RELATIONS)
            ->latest('id')
            ->get();
    }

    /**
     * الوثائق محفوظة على القرص الخاص، فتُعرض للأدمن عبر رابط موقّع مؤقت
     */
    public function streamDocument(VerificationDocument $document): StreamedResponse
    {
        $disk = Storage::disk(BaseVerificationService::DOCUMENTS_DISK);

        if (! $disk->exists($document->file_url)) {
            throw new ApiException('الملف غير موجود.', 404, 'not_found');
        }

        return $disk->response($document->file_url);
    }
}
