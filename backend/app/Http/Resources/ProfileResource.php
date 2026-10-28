<?php

namespace App\Http\Resources;

use App\Http\Resources\Company\CompanyResource;
use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * الملف الشخصي لصاحب الحساب نفسه (يتضمن البيانات الخاصة)
 *
 * @mixin Account
 */
class ProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $profile = $this->user;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'avatar' => $this->avatarUrl(),
            'type' => $this->role()->slug(),
            'verification_status' => $this->verification_status,
            'is_verified' => $this->isVerified(),

            'phone_number' => $profile?->phone_number,
            'linkedin_url' => $profile?->linkedin_url,
            'short_bio' => $profile?->short_bio,
            'national_id' => $profile?->national_id,

            'posts_count' => (int) ($this->published_posts_count ?? 0),
            'following_count' => (int) ($this->following_count ?? 0),
            'reviews_count' => (int) ($profile?->reviews_count ?? 0),
            'reacts_count' => (int) ($profile?->reacts_count ?? 0),
            'bookmarks_count' => (int) ($profile?->bookmarks_count ?? 0),

            'company' => $this->isCompany() && $this->company ? new CompanyResource($this->company) : null,
            'created_at' => $this->created_at,
        ];
    }
}
