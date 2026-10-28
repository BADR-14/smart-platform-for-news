<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Notification\NotificationIndexRequest;
use App\Http\Resources\NotificationResource;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(private readonly NotificationService $notificationService) {}

    /**
     * GET /notifications?unread_only=1&per_page=15
     */
    public function index(NotificationIndexRequest $request): JsonResponse
    {
        $notifications = $this->notificationService->getNotifications(
            account: $request->user(),
            unreadOnly: $request->boolean('unread_only'),
            perPage: $request->integer('per_page', 15),
        );

        return $this->paginated($notifications, NotificationResource::class, [
            'unread_count' => $this->notificationService->unreadCount($request->user()),
        ]);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return $this->ok(['unread_count' => $this->notificationService->unreadCount($request->user())]);
    }

    public function markAsRead(Request $request, string $notification): JsonResponse
    {
        $this->notificationService->markAsRead($request->user(), $notification);

        return $this->message('تم تحديد الإشعار كمقروء.');
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $this->notificationService->markAllAsRead($request->user());

        return $this->message('تم تحديد جميع الإشعارات كمقروءة.');
    }

    public function destroy(Request $request, string $notification): JsonResponse
    {
        $this->notificationService->delete($request->user(), $notification);

        return $this->message('تم حذف الإشعار.');
    }
}
