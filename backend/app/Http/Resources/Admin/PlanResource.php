<?php

namespace App\Http\Resources\Admin;

use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Plan
 */
class PlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'posts_count' => (int) $this->posts_count,
            'price' => (float) $this->price,
            // تكلفة المنشور المميز الواحد ضمن الباقة (للمقارنة مع سعر المنشور المفرد)
            'price_per_post' => $this->posts_count > 0 ? round((float) $this->price / $this->posts_count, 2) : null,
            'is_active' => (bool) $this->is_active,
            'subscriptions_count' => $this->whenCounted('companyPlans'),
            'created_at' => $this->created_at,
        ];
    }
}
