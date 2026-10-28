<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'name',
        'posts_count',
        'price',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'price' => 'decimal:2',
        'posts_count' => 'integer',
        'created_at' => 'datetime',
    ];

    public function companyPlans(): HasMany
    {
        return $this->hasMany(CompanyPlan::class, 'plan_id');
    }
}
