<?php

namespace App\Models;

use App\Enums\Account\AccountRole;
use App\Enums\Account\VerificationStatus;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Account extends Authenticatable implements CanResetPasswordContract
{
    use CanResetPassword;
    use HasApiTokens;
    use HasFactory;
    use Notifiable;
    use SoftDeletes;

    protected $fillable = [
        'account_type_id',
        'name',
        'email',
        'verification_status',
        'is_active',
        'ban_reason',
        'avatar',
        'email_verified_at',
        'otp_code',
        'otp_expires_at',
        'fcm_token',
    ];

    /**
     * نفس القيم الافتراضية في قاعدة البيانات، حتى تكون متوفرة على الكائن فور إنشائه
     */
    protected $attributes = [
        'is_active' => true,
        'verification_status' => 'not_submitted',
    ];

    /**
     * حقول حساسة لا يجب أن تخرج بأي رد JSON
     */
    protected $hidden = [
        'otp_code',
        'otp_expires_at',
        'fcm_token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'email_verified_at' => 'datetime',
        'otp_expires_at' => 'datetime',
    ];

    // ==================== Relations ====================

    public function accountType(): BelongsTo
    {
        return $this->belongsTo(AccountType::class);
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function company(): HasOne
    {
        return $this->hasOne(Company::class);
    }

    public function authCredentials(): HasMany
    {
        return $this->hasMany(AuthCredential::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(Media::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function reports(): MorphMany
    {
        return $this->morphMany(Report::class, 'reportable');
    }

    public function shares(): HasMany
    {
        return $this->hasMany(PostShare::class);
    }

    public function verificationRequests(): HasMany
    {
        return $this->hasMany(VerificationRequest::class);
    }

    public function notificationPreference(): HasOne
    {
        return $this->hasOne(NotificationPreference::class);
    }

    public function followedCompanies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'follows', 'account_id', 'company_id');
    }

    // ==================== Helpers ====================

    public function role(): AccountRole
    {
        return AccountRole::from((int) $this->account_type_id);
    }

    public function isAdmin(): bool
    {
        return $this->role() === AccountRole::Admin;
    }

    public function isCompany(): bool
    {
        return $this->role() === AccountRole::Company;
    }

    public function isVerified(): bool
    {
        return $this->verification_status === VerificationStatus::Approved->value;
    }

    public function hasVerifiedEmail(): bool
    {
        return $this->email_verified_at !== null;
    }

    /**
     * الملف الشخصي (جدول users) المرتبط بالحساب — يُنشأ إن لم يكن موجوداً
     * حتى تبقى التعليقات والتفاعلات والتقييمات تعمل لكل أنواع الحسابات.
     */
    public function profile(): User
    {
        return $this->user ?? $this->user()->create([
            'system_role' => $this->isAdmin() ? 'admin' : 'user',
        ]);
    }

    public function avatarUrl(): ?string
    {
        return $this->avatar ? asset('storage/'.$this->avatar) : null;
    }

    public function getEmailForPasswordReset()
    {
        return $this->email;
    }
}
