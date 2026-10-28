<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\DestroyAccountRequest;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;

class SettingsController extends Controller
{
    public function __construct(private readonly SettingsService $settingsService) {}

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $this->settingsService->changePassword(
            $request->user(),
            $request->validated('current_password'),
            $request->validated('new_password'),
        );

        return $this->message('تم تغيير كلمة المرور بنجاح.');
    }

    public function destroyAccount(DestroyAccountRequest $request): JsonResponse
    {
        $this->settingsService->destroyAccount($request->user(), $request->validated('password'));

        return $this->message('تم حذف الحساب.');
    }
}
