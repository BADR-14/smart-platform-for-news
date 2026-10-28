<?php

namespace App\Models;

use App\Enums\Featured\FeaturedStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeaturedPost extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'post_id',
        'company_id',
        'cost',
        'payment_method',
        'company_plan_id',
        'status',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'cost' => 'decimal:2',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function companyPlan(): BelongsTo
    {
        return $this->belongsTo(CompanyPlan::class);
    }

    /**
     * التمييز الساري حالياً: حالته active ولم تنتهِ مدته بعد
     */
    public function scopeRunning(Builder $query): Builder
    {
        return $query->where('status', FeaturedStatus::Active->value)
            ->where(fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()));
    }

    public function isRunning(): bool
    {
        return $this->status === FeaturedStatus::Active->value
            && ($this->ends_at === null || $this->ends_at->isFuture());
    }
}
