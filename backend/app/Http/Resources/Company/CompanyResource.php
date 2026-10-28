<?php

namespace App\Http\Resources\Company;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * الشركة كما تظهر للعامة (قائمة الشركات وصفحة الشركة)
 *
 * @mixin Company
 */
class CompanyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'account_id' => $this->account_id,
            'name' => $this->displayName(),
            'logo' => $this->logoUrl(),
            'description' => $this->description,
            'phone' => $this->phone,
            'address' => $this->address,
            'website_url' => $this->website_url,
            'category' => $this->category ? [
                'id' => $this->category->id,
                'name' => $this->category->name,
            ] : null,
            'is_verified' => (bool) $this->account?->isVerified() && (bool) $this->account?->isCompany(),
            'posts_count' => (int) $this->posts_count,
            'followers_count' => (int) $this->followers_count,
            'is_following' => (bool) ($this->is_following ?? false),
            'is_owner' => (int) $this->account_id === (int) $request->user()?->id,
            'last_active_at' => $this->last_active_at,
            'created_at' => $this->created_at,
        ];
    }
}
