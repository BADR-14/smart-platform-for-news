<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    /**
     * نفس القيم الافتراضية في قاعدة البيانات، حتى تكون متوفرة فور إنشاء السجل
     */
    protected $attributes = [
        'email_notifications' => true,
        'login_notifications' => true,
    ];

    protected $fillable = [
        'account_id',
        'email_notifications',
        'login_notifications',
    ];

    protected $casts = [
        'email_notifications' => 'boolean',
        'login_notifications' => 'boolean',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}