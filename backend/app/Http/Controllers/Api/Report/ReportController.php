<?php

namespace App\Http\Controllers\Api\Report;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReportRequest;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    public function __construct(private readonly ReportService $reportService) {}

    /**
     * POST /reports — بلاغ على منشور أو على حساب
     */
    public function store(StoreReportRequest $request): JsonResponse
    {
        $this->reportService->storeReport(
            reporter: $request->user(),
            reportableType: $request->validated('reportable_type'),
            reportableId: (int) $request->validated('reportable_id'),
            reason: $request->validated('reason'),
        );

        return $this->created(message: 'تم إرسال البلاغ وستراجعه الإدارة.');
    }
}
