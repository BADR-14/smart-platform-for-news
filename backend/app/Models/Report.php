<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Report extends Model
{
    protected $fillable = [
        'reporter_account_id',
        'reportable_type',
        'reportable_id',
        'reason',
        'status',
        'action_taken',
        'resolved_by',
        'admin_notes',
        'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    /**
     * الهدف المبلَّغ عنه: Post أو Account (حسب morphMap في AppServiceProvider)
     */
    public function reportable(): MorphTo
    {
        return $this->morphTo();
    }

    public function reporterAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'reporter_account_id')->withTrashed();
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'resolved_by');
    }
}
