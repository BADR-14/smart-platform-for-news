<?php

namespace App\Http\Resources;

use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * بيانات الحساب المسجّل دخوله (ترجع مع login و /me)
 *
 * @mixin Account
 */
class AccountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'avatar' => $this->avatarUrl(),
            'type' => $this->role()->slug(),
            'verification_status' => $this->verification_status,
            'is_verified' => $this->isVerified(),
            'is_active' => (bool) $this->is_active,
            'email_verified' => $this->hasVerifiedEmail(),
            'user_id' => $this->user?->id,
            'company_id' => $this->isCompany() ? $this->company?->id : null,
            'created_at' => $this->created_at,
        ];
    }
}
