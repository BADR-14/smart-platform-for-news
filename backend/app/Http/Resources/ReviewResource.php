<?php

namespace App\Http\Resources;

use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Review
 */
class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'rating' => (float) $this->rating,
            'content' => $this->content,
            'author' => $this->whenLoaded('user', fn () => $this->user?->account
                ? new AuthorResource($this->user->account)
                : null),
            'post' => $this->whenLoaded('post', fn () => $this->post ? [
                'id' => $this->post->id,
                'title' => $this->post->title,
                'category' => $this->post->category?->name,
            ] : null),
            'created_at' => $this->created_at,
        ];
    }
}
