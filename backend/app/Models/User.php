<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Model
{
    use SoftDeletes;

    public $timestamps = false;

    protected $fillable = [
        'account_id',
        'system_role',
        'national_id',
        'phone_number',
        'linkedin_url',
        'short_bio',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function companies(): BelongsToMany
    {
        // تم الاعتماد على الطريقة القياسية والأنظف المتوافقة مع ملف Company
        return $this->belongsToMany(Company::class, 'company_memberships')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function companyMemberships(): HasMany
    {
        return $this->hasMany(CompanyMembership::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function reacts(): HasMany
    {
        return $this->hasMany(UserReact::class);
    }

    public function bookmarks(): HasMany
    {
        return $this->hasMany(Bookmark::class);
    }
}
