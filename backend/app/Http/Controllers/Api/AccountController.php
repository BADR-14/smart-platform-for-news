<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Resources\PublicProfileResource;
use App\Models\Account;
use App\Services\ProfileService;
use Illuminate\Http\JsonResponse;

class AccountController extends Controller
{
    public function __construct(private readonly ProfileService $profileService) {}

    /**
     * GET /accounts/{account} — الملف العام لحساب آخر
     */
    public function show(Account $account): JsonResponse
    {
        if (! $account->is_active || $account->isAdmin()) {
            throw new ApiException('العنصر المطلوب غير موجود.', 404, 'not_found');
        }

        return $this->ok(new PublicProfileResource($this->profileService->getProfile($account)));
    }
}
