<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Wallet extends Model
{
    protected $fillable = [
        'company_id',
        'name',
        'slug',
        'uuid',
        'description',
        'balance',
        'currency',
        'is_active',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Wallet $wallet) {
            $wallet->uuid ??= (string) Str::uuid();
            $wallet->slug ??= Str::slug($wallet->name).'-'.Str::lower(Str::random(8));
        });
    }

    // ==================== Relations ====================

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class)->latest('id');
    }

    // ==================== Helpers ====================

    public function hasSufficientBalance(float $amount): bool
    {
        // مقارنة بالسنتات لتجنب أخطاء الفاصلة العائمة
        return (int) round((float) $this->balance * 100) >= (int) round($amount * 100);
    }

    public function getFormattedBalanceAttribute(): string
    {
        return number_format((float) $this->balance, 2).' '.$this->currency;
    }
}
