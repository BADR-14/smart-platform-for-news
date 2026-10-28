<?php

namespace App\Http\Resources;

use App\Models\FeaturedPost;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin FeaturedPost
 */
class FeaturedPostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'post' => $this->post ? [
                'id' => $this->post->id,
                'title' => $this->post->title,
                'status' => $this->post->status,
            ] : null,
            'company' => $this->whenLoaded('company', fn () => $this->company ? [
                'id' => $this->company->id,
                'name' => $this->company->displayName(),
            ] : null),
            'cost' => (float) $this->cost,
            'payment_method' => $this->payment_method,
            // الحالة الفعلية: السجل قد يبقى active قليلاً بعد انتهاء مدته حتى يمر الأمر المجدول
            'status' => $this->status === 'active' && ! $this->resource->isRunning() ? 'expired' : $this->status,
            'starts_at' => $this->starts_at,
            'ends_at' => $this->ends_at,
            'created_at' => $this->created_at,
        ];
    }
}
