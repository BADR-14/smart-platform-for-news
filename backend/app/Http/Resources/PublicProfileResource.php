<?php

namespace App\Http\Resources;

use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * الملف الشخصي كما يراه الآخرون — بدون البريد أو الهاتف أو الرقم الوطني
 *
 * @mixin Account
 */
class PublicProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'avatar' => $this->avatarUrl(),
            'type' => $this->role()->slug(),
            'is_verified' => $this->isVerified(),
            'short_bio' => $this->user?->short_bio,
            'linkedin_url' => $this->user?->linkedin_url,
            'company_id' => $this->isCompany() ? $this->company?->id : null,
            'posts_count' => (int) ($this->published_posts_count ?? 0),
            'created_at' => $this->created_at,
        ];
    }
}
