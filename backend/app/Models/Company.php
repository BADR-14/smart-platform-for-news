<?php

namespace App\Models;

use App\Enums\Account\AccountRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'account_id',
        'name',
        'category_id',
        'phone',
        'address',
        'description',
        'logo_url',
        'posts_count',
        'followers_count',
        'last_active_at',
        'company_registration_number',
        'tax_id',
        'website_url',
    ];

    protected $casts = [
        'last_active_at' => 'datetime',
        'posts_count' => 'integer',
        'followers_count' => 'integer',
    ];

    // ==================== Relations ====================

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(Account::class, 'follows', 'company_id', 'account_id');
    }

    public function follows(): HasMany
    {
        return $this->hasMany(Follow::class);
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'company_memberships')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(CompanyMembership::class);
    }

    public function featuredPosts(): HasMany
    {
        return $this->hasMany(FeaturedPost::class);
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    public function plans(): HasMany
    {
        return $this->hasMany(CompanyPlan::class);
    }

    /**
     * الباقة النشطة التي ما زال فيها رصيد منشورات مميزة
     */
    public function activePlan(): HasOne
    {
        return $this->hasOne(CompanyPlan::class)->ofMany(
            ['id' => 'max'],
            fn (Builder $q) => $q->where('status', 'active')->where('posts_remaining', '>', 0)
        );
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    // ==================== Scopes ====================

    /**
     * الشركات التي تمت الموافقة على طلبها وتحوّل حسابها إلى حساب شركة
     */
    public function scopeVerified(Builder $query): Builder
    {
        return $query->whereHas('account', fn (Builder $q) => $q
            ->where('verification_status', 'approved')
            ->where('account_type_id', AccountRole::Company->value));
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereHas('account', fn (Builder $q) => $q->where('is_active', true));
    }

    // ==================== Helpers ====================

    public function displayName(): string
    {
        return $this->name ?: (string) $this->account?->name;
    }

    public function logoUrl(): ?string
    {
        return $this->logo_url ? asset('storage/'.$this->logo_url) : null;
    }
}
