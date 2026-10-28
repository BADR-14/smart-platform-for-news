<?php

namespace App\Services;

use App\Exceptions\AlreadyReportedException;
use App\Exceptions\ApiException;
use App\Models\Account;
use App\Models\Post;
use App\Models\Report;

class ReportService
{
    public function __construct(private readonly NotificationService $notificationService) {}

    /**
     * البلاغ نوعان: على منشور (post) أو على حساب (account)
     */
    public function storeReport(Account $reporter, string $reportableType, int $reportableId, string $reason): Report
    {
        $label = $reportableType === 'post'
            ? $this->resolvePost($reporter, $reportableId)
            : $this->resolveAccount($reporter, $reportableId);

        $alreadyReported = Report::where('reporter_account_id', $reporter->id)
            ->where('reportable_type', $reportableType)
            ->where('reportable_id', $reportableId)
            ->exists();

        if ($alreadyReported) {
            throw new AlreadyReportedException;
        }

        $report = Report::create([
            'reporter_account_id' => $reporter->id,
            'reportable_type' => $reportableType,
            'reportable_id' => $reportableId,
            'reason' => $reason,
        ]);

        $this->notificationService->notifyReportCreated($report, $reporter, $label);

        return $report;
    }

    /**
     * @return string عنوان المنشور المبلَّغ عنه
     */
    private function resolvePost(Account $reporter, int $postId): string
    {
        $post = Post::published()->find($postId);

        if (! $post) {
            throw new ApiException('المنشور المطلوب غير موجود.', 404, 'not_found');
        }

        if ($post->isOwnedBy($reporter)) {
            throw new ApiException('لا يمكنك الإبلاغ عن منشورك.', 422, 'cannot_report_self');
        }

        return $post->title;
    }

    /**
     * @return string اسم الحساب المبلَّغ عنه
     */
    private function resolveAccount(Account $reporter, int $accountId): string
    {
        $account = Account::find($accountId);

        if (! $account || $account->isAdmin()) {
            throw new ApiException('الحساب المطلوب غير موجود.', 404, 'not_found');
        }

        if ((int) $account->id === (int) $reporter->id) {
            throw new ApiException('لا يمكنك الإبلاغ عن حسابك.', 422, 'cannot_report_self');
        }

        return $account->name;
    }
}
