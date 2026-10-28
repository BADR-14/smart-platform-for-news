<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = $this->data ?? [];

        return [
            'id' => $this->id,
            // النوع (ثابت): يفيد الفرونت إند لتحديد شكل الأيقونة والتنقل عند الضغط
            'type' => $data['type'] ?? $this->type,
            // ثابت في كل الإشعارات
            'title' => $data['title'] ?? null,
            // ثابت في كل الإشعارات (التفاصيل)
            'body' => $data['body'] ?? null,
            // ثابت في كل الإشعارات (التاريخ)
            'created_at' => $this->created_at?->toDateTimeString(),
            // متغير حسب نوع الإشعار (post_id, comment_id, rating, ... إلخ)
            'meta' => collect($data)->except(['type', 'title', 'body'])->all(),
            'time_ago' => $this->created_at?->diffForHumans(),
            'is_read' => $this->isRead(),
            'read_at' => $this->read_at?->toDateTimeString(),
        ];
    }
}
