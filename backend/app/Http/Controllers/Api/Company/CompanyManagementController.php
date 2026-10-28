<?php

namespace App\Http\Controllers\Api\Company;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\CompanyResource;
use App\Models\Company;
use App\Services\AdminService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * إدارة الشركات من لوحة الأدمن
 */
class CompanyManagementController extends Controller
{
    public function __construct(private readonly AdminService $adminService) {}

    /**
     * GET /admin/companies?search=&category_id=&status=active|blocked
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'category_id' => ['nullable', 'integer'],
            'status' => ['nullable', 'in:active,blocked'],
        ]);

        return $this->paginated(
            $this->adminService->getAllCompanies($filters),
            CompanyResource::class,
            ['stats' => $this->adminService->getCompanyStats()],
        );
    }

    public function ban(Request $request, Company $company): JsonResponse
    {
        $validated = $request->validate(['ban_reason' => ['required', 'string', 'max:255']]);

        $this->adminService->banAccount($company->account, $validated['ban_reason']);

        return $this->message('تم حظر الشركة.');
    }

    public function unban(Company $company): JsonResponse
    {
        $this->adminService->unbanAccount($company->account);

        return $this->message('تم إلغاء حظر الشركة.');
    }
}
