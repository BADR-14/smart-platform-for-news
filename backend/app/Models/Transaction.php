<?php

// app/Models/Transaction.php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Transaction extends Model
{
    protected $fillable = [
        'wallet_id', 'payment_id', 'type', 'amount',
        'currency', 'status', 'description', 'uuid', 'meta',
    ];

    protected $casts = [
        'meta' => 'array',
        'amount' => 'decimal:2',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(fn ($t) => $t->uuid = (string) Str::uuid());
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function scopeCredits(Builder $q): Builder
    {
        return $q->where('type', 'credit');
    }

    public function scopeDebits(Builder $q): Builder
    {
        return $q->where('type', 'debit');
    }

    public function scopeCompleted(Builder $q): Builder
    {
        return $q->where('status', 'completed');
    }
}
