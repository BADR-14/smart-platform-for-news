<?php

namespace App\Models;

use App\Enums\Verification\VerificationType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VerificationRequest extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'account_id',
        'type',
        'status',
        'confidence_score',
        'rejection_reasons',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'rejection_reasons' => 'array',
        'confidence_score' => 'integer',
        'reviewed_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(VerificationDocument::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isForCompany(): bool
    {
        return $this->type === VerificationType::Company->value;
    }
}
