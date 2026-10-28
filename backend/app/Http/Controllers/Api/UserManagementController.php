<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\UsersManagementResource;
use App\Models\Account;
use App\Services\AdminService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserManagementController extends Controller
{
    public function __construct(private readonly AdminService $adminService) {}

    /**
     * GET /admin/users?search=&status=active|blocked&verification=
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:active,blocked'],
            'verification' => ['nullable', 'in:not_submitted,pending,approved,rejected'],
        ]);

        return $this->paginated(
            $this->adminService->getAllUsers($filters),
            UsersManagementResource::class,
            ['stats' => $this->adminService->getUserStats()],
        );
    }

    public function ban(Request $request, Account $account): JsonResponse
    {
        $validated = $request->validate(['ban_reason' => ['required', 'string', 'max:255']]);

        $this->adminService->banAccount($account, $validated['ban_reason']);

        return $this->message('تم حظر الحساب.');
    }

    public function unban(Account $account): JsonResponse
    {
        $this->adminService->unbanAccount($account);

        return $this->message('تم إلغاء حظر الحساب.');
    }

    public function destroy(Account $account): JsonResponse
    {
        $this->adminService->deleteAccount($account);

        return $this->message('تم حذف الحساب.');
    }
}
