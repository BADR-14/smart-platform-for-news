<?php

namespace App\Notifications;

use App\Enums\Notification\NotificationType;
use App\Models\Account;
use App\Models\Report;

class ReportCreatedNotification extends BaseNotification
{
    public function __construct(
        private readonly Report $report,
        private readonly Account $reporter,
        private readonly string $reportedLabel,
    ) {}

    public function toDatabase(object $notifiable): array
    {
        $subject = $this->report->reportable_type === 'post' ? 'المنشور' : 'الحساب';

        return [
            'type' => NotificationType::ReportCreated->value,
            'title' => 'بلاغ جديد',
            'body' => "قام {$this->reporter->name} بالإبلاغ عن {$subject} \"{$this->reportedLabel}\".",
            'report_id' => $this->report->id,
            'reportable_type' => $this->report->reportable_type,
            'reportable_id' => $this->report->reportable_id,
            'reporter_account_id' => $this->reporter->id,
            'reason' => $this->report->reason,
        ];
    }
}
