<?php

namespace App\Http\Controllers\Api\Report;

use App\Enums\Report\ReportAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\ReportResource;
use App\Models\Report;
use App\Services\AdminReportManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminReportController extends Controller
{
    public function __construct(private readonly AdminReportManagementService $adminReportManagementService) {}

    /**
     * GET /admin/reports?search=&status=&type=post|account
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:pending,resolved,dismissed'],
            'type' => ['nullable', 'in:post,account'],
        ]);

        return $this->paginated(
            $this->adminReportManagementService->getReports($filters),
            ReportResource::class,
            ['stats' => $this->adminReportManagementService->getStats()],
        );
    }

    /**
     * PATCH /admin/reports/{report}/resolve
     * action: none | under_review | reject_post | delete_post (لبلاغ منشور) — none | ban_account (لبلاغ حساب)
     */
    public function resolve(Request $request, Report $report): JsonResponse
    {
        $validated = $request->validate([
            'admin_notes' => ['nullable', 'string', 'max:255'],
            'action' => ['nullable', Rule::enum(ReportAction::class)],
        ]);

        $report = $this->adminReportManagementService->resolve(
            $report,
            $request->user(),
            ReportAction::from($validated['action'] ?? ReportAction::None->value),
            $validated['admin_notes'] ?? null,
        );

        return $this->ok(new ReportResource($report), 'تمت معالجة البلاغ.');
    }

    public function dismiss(Request $request, Report $report): JsonResponse
    {
        $validated = $request->validate(['admin_notes' => ['nullable', 'string', 'max:255']]);

        $report = $this->adminReportManagementService->dismiss($report, $request->user(), $validated['admin_notes'] ?? null);

        return $this->ok(new ReportResource($report), 'تم رفض البلاغ.');
    }
}
