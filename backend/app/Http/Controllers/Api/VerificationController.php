<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserVerificationRequest;
use App\Services\Verifications\UserVerificationService;
use App\Services\Verifications\VerificationStatusService;
use Illuminate\Http\JsonResponse;

class VerificationController extends Controller
{
    public function __construct(
        private readonly UserVerificationService $userVerificationService,
        private readonly VerificationStatusService $verificationStatusService,
    ) {}

    /**
     * POST /verification/user — طلب توثيق حساب مستخدم
     */
    public function userVerification(StoreUserVerificationRequest $request): JsonResponse
    {
        $this->userVerificationService->submitUserVerification(
            $request->user(),
            $request->safe()->only(['national_id', 'linkedin_url']),
            [
                'identity_front' => $request->file('identity_front'),
                'identity_back' => $request->file('identity_back'),
            ],
        );

        return $this->created(
            $this->verificationStatusService->forAccount($request->user()->refresh()),
            'تم استلام طلبك وسيتم إشعارك بالنتيجة قريباً.',
        );
    }
}
