<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReviewVerificationRequest;
use App\Http\Resources\Verification\VerificationRequestResource;
use App\Models\Account;
use App\Models\VerificationDocument;
use App\Models\VerificationRequest;
use App\Services\Verifications\Admin\AdminVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminVerificationController extends Controller
{
    public function __construct(private readonly AdminVerificationService $adminVerificationService) {}

    /**
     * GET /admin/verification?status=pending&type=user|company&search=
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'status' => ['nullable', 'in:pending,approved,rejected'],
            'type' => ['nullable', 'in:user,company'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        return $this->paginated(
            $this->adminVerificationService->getRequests($filters),
            VerificationRequestResource::class,
            ['stats' => $this->adminVerificationService->getStats()],
        );
    }

    public function show(VerificationRequest $verificationRequest): JsonResponse
    {
        return $this->ok(new VerificationRequestResource(
            $this->adminVerificationService->getRequest($verificationRequest)
        ));
    }

    public function review(ReviewVerificationRequest $request, VerificationRequest $verificationRequest): JsonResponse
    {
        $reviewed = $this->adminVerificationService->reviewDecision(
            $verificationRequest,
            $request->validated('decision'),
            $request->validated('rejection_reasons'),
            $request->user(),
        );

        return $this->ok(new VerificationRequestResource($reviewed), 'تمت مراجعة الطلب بنجاح.');
    }

    public function accountHistory(Account $account): JsonResponse
    {
        return $this->ok(VerificationRequestResource::collection(
            $this->adminVerificationService->getAccountHistory($account)
        ));
    }

    /**
     * عرض ملف الوثيقة — المسار محمي بتوقيع مؤقت (signed) بدل التوكن
     */
    public function document(VerificationDocument $document): StreamedResponse
    {
        return $this->adminVerificationService->streamDocument($document);
    }
}
