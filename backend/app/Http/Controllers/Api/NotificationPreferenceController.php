<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Notification\UpdateNotificationPreferenceRequest;
use App\Http\Resources\NotificationPreferenceResource;
use App\Services\NotificationPreferenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationPreferenceController extends Controller
{
    public function __construct(private readonly NotificationPreferenceService $preferenceService) {}

    /**
     * GET /notification-settings
     */
    public function show(Request $request): JsonResponse
    {
        return $this->ok(new NotificationPreferenceResource(
            $this->preferenceService->getPreference($request->user())
        ));
    }

    /**
     * PATCH /notification-settings
     */
    public function update(UpdateNotificationPreferenceRequest $request): JsonResponse
    {
        $preference = $this->preferenceService->updatePreference($request->user(), $request->validated());

        return $this->ok(new NotificationPreferenceResource($preference), 'تم تحديث إعدادات الإشعارات.');
    }
}
