<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AdminService;
use App\Services\AnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        private readonly AdminService $adminService,
        private readonly AnalyticsService $analyticsService,
    ) {}

    /**
     * GET /admin/overview — أرقام الصفحة الرئيسية + مخطط نمو السنة الحالية
     */
    public function index(): JsonResponse
    {
        return $this->ok([
            'statistics' => $this->adminService->overviewStats(),
            'charts' => $this->analyticsService->getChartsData(),
        ]);
    }

    /**
     * GET /admin/analytics?year=&month=&page= — صفحة التقارير
     */
    public function analytics(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'year' => ['nullable', 'integer', 'between:2000,2100'],
            'month' => ['nullable', 'integer', 'between:1,12'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $monthly = $this->analyticsService->getMonthlyReportsTable($filters, (int) ($filters['page'] ?? 1));

        return $this->ok([
            'counters' => $this->analyticsService->getOverviewCounters(),
            'charts' => $this->analyticsService->getChartsData(isset($filters['year']) ? (int) $filters['year'] : null),
            'available_years' => $this->analyticsService->getAvailableYears(),
            'monthly_reports' => [
                'records' => $monthly['data'],
                'meta' => [
                    'current_page' => $monthly['current_page'],
                    'last_page' => $monthly['last_page'],
                    'per_page' => $monthly['per_page'],
                    'total' => $monthly['total'],
                ],
            ],
        ]);
    }
}
