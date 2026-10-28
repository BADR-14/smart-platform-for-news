<?php

namespace App\Http\Resources\Admin;

use App\Models\CompanyPlan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * اشتراك شركة بباقة — يُستخدم في لوحة الأدمن ولوحة الشركة
 *
 * @mixin CompanyPlan
 */
class CompanyPlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'company_name' => $this->whenLoaded('company', fn () => $this->company?->displayName() ?? 'شركة محذوفة'),
            'plan_id' => $this->plan_id,
            'plan_name' => $this->plan?->name ?? 'باقة محذوفة',
            'posts_count' => (int) ($this->plan?->posts_count ?? 0),
            'posts_remaining' => (int) $this->posts_remaining,
            'status' => $this->status,
            'price_paid' => (float) $this->price_paid,
            'purchased_at' => $this->created_at,
        ];
    }
}
