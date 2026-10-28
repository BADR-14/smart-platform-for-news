<?php

namespace App\Services;

use App\Enums\Post\PostStatus;
use App\Enums\Report\ReportAction;
use App\Enums\Report\ReportStatus;
use App\Exceptions\ApiException;
use App\Models\Account;
use App\Models\Post;
use App\Models\Report;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class AdminReportManagementService
{
    private const RELATIONS = ['reporterAccount', 'reportable', 'resolver'];

    public function __construct(
        private readonly PostsManagementService $postsManagementService,
        private readonly AdminService $adminService,
    ) {}

    /**
     * @param  array{search?: string|null, status?: string|null, type?: string|null}  $filters
     */
    public function getReports(array $filters): LengthAwarePaginator
    {
        return Report::query()
            ->with(self::RELATIONS)
            ->when($filters['search'] ?? null, fn (Builder $q, $search) => $q->where(fn (Builder $w) => $w
                ->where('reason', 'LIKE', "%{$search}%")
                ->orWhereHas('reporterAccount', fn (Builder $a) => $a->where('name', 'LIKE', "%{$search}%"))))
            ->when($filters['status'] ?? null, fn (Builder $q, $status) => $q->where('status', $status))
            ->when($filters['type'] ?? null, fn (Builder $q, $type) => $q->where('reportable_type', $type))
            ->latest('id')
            ->paginate(10);
    }

    /**
     * @return array<string, int>
     */
    public function getStats(): array
    {
        $byStatus = Report::selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');
        $byType = Report::selectRaw('reportable_type, COUNT(*) as total')->groupBy('reportable_type')->pluck('total', 'reportable_type');

        return [
            'total' => (int) $byStatus->sum(),
            'pending' => (int) ($byStatus[ReportStatus::Pending->value] ?? 0),
            'resolved' => (int) ($byStatus[ReportStatus::Resolved->value] ?? 0),
            'dismissed' => (int) ($byStatus[ReportStatus::Dismissed->value] ?? 0),
            'posts' => (int) ($byType['post'] ?? 0),
            'accounts' => (int) ($byType['account'] ?? 0),
        ];
    }

    /**
     * معالجة البلاغ مع اتخاذ إجراء على المحتوى المبلَّغ عنه:
     *  - بلاغ منشور: وضعه قيد المراجعة / رفضه / حذفه
     *  - بلاغ حساب : حظر الحساب
     */
    public function resolve(Report $report, Account $admin, ReportAction $action, ?string $adminNotes): Report
    {
        $this->ensurePending($report);

        if (! in_array($action->value, ReportAction::forType($report->reportable_type), true)) {
            throw new ApiException('هذا الإجراء غير متاح لهذا النوع من البلاغات.', 422, 'invalid_report_action');
        }

        DB::transaction(function () use ($report, $admin, $action, $adminNotes) {
            $this->applyAction($report, $action, $adminNotes);

            $this->close($report, ReportStatus::Resolved, $admin, $adminNotes, $action);

            // أي بلاغات أخرى معلّقة على نفس الهدف تُغلق بنفس القرار
            if ($action !== ReportAction::None) {
                Report::where('reportable_type', $report->reportable_type)
                    ->where('reportable_id', $report->reportable_id)
                    ->where('status', ReportStatus::Pending->value)
                    ->get()
                    ->each(fn (Report $other) => $this->close($other, ReportStatus::Resolved, $admin, $adminNotes, $action));
            }
        });

        return $report->fresh(self::RELATIONS);
    }

    /**
     * رفض البلاغ (غير مبرر) دون أي إجراء
     */
    public function dismiss(Report $report, Account $admin, ?string $adminNotes): Report
    {
        $this->ensurePending($report);

        $this->close($report, ReportStatus::Dismissed, $admin, $adminNotes, ReportAction::None);

        return $report->fresh(self::RELATIONS);
    }

    private function applyAction(Report $report, ReportAction $action, ?string $adminNotes): void
    {
        if ($action === ReportAction::None) {
            return;
        }

        $target = $report->reportable;

        if (! $target) {
            throw new ApiException('المحتوى المبلَّغ عنه لم يعد موجوداً.', 422, 'report_target_missing');
        }

        match (true) {
            $action === ReportAction::UnderReview && $target instanceof Post => $this->postsManagementService->changeStatus($target, PostStatus::UnderReview, $adminNotes),
            $action === ReportAction::RejectPost && $target instanceof Post => $this->postsManagementService->changeStatus($target, PostStatus::Rejected, $adminNotes),
            $action === ReportAction::DeletePost && $target instanceof Post => $this->postsManagementService->deletePost($target),
            $action === ReportAction::BanAccount && $target instanceof Account => $target->is_active
                ? $this->adminService->banAccount($target, $adminNotes ?: 'مخالفة سياسات المنصة')
                : null,
            default => null,
        };
    }

    private function close(Report $report, ReportStatus $status, Account $admin, ?string $adminNotes, ReportAction $action): void
    {
        $report->update([
            'status' => $status->value,
            'action_taken' => $action->value,
            'resolved_by' => $admin->id,
            'admin_notes' => $adminNotes,
            'resolved_at' => now(),
        ]);
    }

    private function ensurePending(Report $report): void
    {
        if ($report->status !== ReportStatus::Pending->value) {
            throw new ApiException('تمت معالجة هذا البلاغ مسبقاً.', 422, 'report_already_handled');
        }
    }
}
