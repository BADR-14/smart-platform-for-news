<?php

namespace App\Http\Resources\Admin;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * المنشور كما يظهر في جدول إدارة المنشورات بلوحة الأدمن
 *
 * @mixin Post
 */
class AdminPostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'post_type' => $this->post_type,
            'status' => $this->status,
            'moderation_note' => $this->moderation_note,
            'author' => $this->account ? [
                'id' => $this->account->id,
                'name' => $this->account->name,
                'type' => $this->account->role()->slug(),
            ] : null,
            'category' => $this->category?->name,
            'views_count' => (int) $this->views_count,
            'likes_count' => (int) $this->likes_count,
            'comments_count' => (int) $this->comments_count,
            'pending_reports_count' => (int) ($this->pending_reports_count ?? 0),
            'is_featured' => $this->relationLoaded('activeFeature') && $this->activeFeature !== null,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'created_at' => $this->created_at,
        ];
    }
}
