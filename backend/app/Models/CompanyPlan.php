<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyPlan extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'company_id',
        'plan_id',
        'posts_remaining',
        'status',
        'price_paid',
    ];

    protected $casts = [
        'posts_remaining' => 'integer',
        'price_paid' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function isUsable(): bool
    {
        return $this->status === 'active' && $this->posts_remaining > 0;
    }
}
