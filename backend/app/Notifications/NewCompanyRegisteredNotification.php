<?php

namespace App\Notifications;

use App\Enums\Notification\NotificationType;
use App\Models\Company;

class NewCompanyRegisteredNotification extends BaseNotification
{
    public function __construct(private readonly Company $company) {}

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => NotificationType::NewCompanyRegistered->value,
            'title' => 'طلب شركة جديد',
            'body' => "قدّمت شركة {$this->company->displayName()} طلب إنشاء حساب شركة.",
            'company_id' => $this->company->id,
            'account_id' => $this->company->account_id,
        ];
    }
}
