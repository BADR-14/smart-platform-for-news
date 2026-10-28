<?php

namespace App\Http\Resources\Admin;

use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Account
 */
class UsersManagementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'avatar' => $this->avatarUrl(),
            'account_type' => $this->role()->slug(),
            'verification_status' => $this->verification_status,
            'is_active' => (bool) $this->is_active,
            'ban_reason' => $this->ban_reason,
            'email_verified' => $this->hasVerifiedEmail(),
            'phone_number' => $this->user?->phone_number,
            'posts_count' => (int) ($this->posts_count ?? 0),
            'created_at' => $this->created_at,
        ];
    }
}
