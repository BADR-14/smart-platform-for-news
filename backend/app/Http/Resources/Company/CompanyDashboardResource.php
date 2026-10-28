<?php

namespace App\Http\Resources\Company;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * بيانات الشركة كما يراها مالكها في لوحة الشركة (تتضمن البيانات الخاصة)
 *
 * @mixin Company
 */
class CompanyDashboardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'account_id' => $this->account_id,
            'name' => $this->displayName(),
            'email' => $this->account?->email,
            'logo' => $this->logoUrl(),
            'phone' => $this->phone,
            'description' => $this->description,
            'address' => $this->address,
            'website_url' => $this->website_url,
            'category' => $this->category ? [
                'id' => $this->category->id,
                'name' => $this->category->name,
            ] : null,
            'company_registration_number' => $this->company_registration_number,
            'tax_id' => $this->tax_id,
            'verification_status' => $this->account?->verification_status,
            'is_verified' => (bool) $this->account?->isVerified(),
            'posts_count' => (int) $this->posts_count,
            'followers_count' => (int) $this->followers_count,
            'created_at' => $this->created_at,
        ];
    }
}
