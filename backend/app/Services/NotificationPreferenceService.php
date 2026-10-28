<?php

namespace App\Services;

use App\Models\Account;
use App\Models\NotificationPreference;

class NotificationPreferenceService
{
    public function getPreference(Account $account): NotificationPreference
    {
        return $account->notificationPreference()->firstOrCreate([
            'account_id' => $account->id,
        ]);
    }

    public function updatePreference(Account $account, array $data): NotificationPreference
    {
        $preference = $this->getPreference($account);

        $preference->update([
            'email_notifications' => $data['email_notifications'],
            'login_notifications' => $data['login_notifications'],
        ]);

        return $preference;
    }
}