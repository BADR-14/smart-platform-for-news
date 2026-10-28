<?php

namespace App\Http\Resources\Admin;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * الشركة كما تظهر في لوحة الأدمن
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
            'email' => $this->account?->email,
            'logo' => $this->logoUrl(),
            'category' => $this->category ? [
                'id' => $this->category->id,
                'name' => $this->category->name,
            ] : null,
            'is_active' => (bool) $this->account?->is_active,
            'ban_reason' => $this->account?->ban_reason,
            'wallet_balance' => (float) ($this->wallet?->balance ?? 0),
            'posts_count' => (int) $this->posts_count,
            'followers_count' => (int) $this->followers_count,
            'active_plan' => $this->whenLoaded('activePlan', fn () => $this->activePlan ? [
                'name' => $this->activePlan->plan?->name,
                'posts_remaining' => $this->activePlan->posts_remaining,
            ] : null),
            'created_at' => $this->created_at,
        ];
    }
}
