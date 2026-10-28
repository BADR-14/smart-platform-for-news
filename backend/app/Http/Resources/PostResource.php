<?php

namespace App\Http\Resources;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * الشكل الموحّد للمنشور في كل الـ API (الرئيسية، الشركة، الملف الشخصي، المحفوظات ...).
 * يُفضَّل تحميل المنشور عبر Post::forResource($viewer) حتى تتوفر حالة المشاهد بدون N+1.
 *
 * @mixin Post
 */
class PostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $viewer = $request->user();
        $isOwner = $this->resource->isOwnedBy($viewer);
        $feature = $this->relationLoaded('activeFeature') ? $this->activeFeature : null;
        $media = $this->relationLoaded('media') ? $this->media : collect();

        return [
            'id' => $this->id,
            'type' => 'post',
            'title' => $this->title,
            'description' => $this->description,
            'post_type' => $this->post_type,
            'status' => $this->status,
            // ملاحظة الأدمن تظهر لصاحب المنشور فقط
            'moderation_note' => $this->when($isOwner || $viewer?->isAdmin(), $this->moderation_note),

            'is_online' => (bool) $this->is_online,
            'location' => $this->location,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),

            'category' => $this->category ? [
                'id' => $this->category->id,
                'name' => $this->category->name,
            ] : null,
            'tags' => $this->whenLoaded('tags', fn () => $this->tags->map(fn ($tag) => [
                'id' => $tag->id,
                'name' => $tag->name,
            ])->values(), []),

            'media' => $media->map(fn ($item) => [
                'id' => $item->id,
                'url' => $item->url(),
                'type' => $item->mediaType?->name ?? 'image',
            ])->values(),
            'cover_url' => $media->first(fn ($item) => ($item->mediaType?->name ?? 'image') === 'image')?->url(),

            'author' => $this->account ? new AuthorResource($this->account) : null,

            'views_count' => (int) $this->views_count,
            'likes_count' => (int) $this->likes_count,
            'comments_count' => (int) $this->comments_count,
            'shares_count' => (int) $this->shares_count,
            'reviews_count' => (int) ($this->reviews_count ?? 0),
            'rating_avg' => $this->rating_avg !== null ? round((float) $this->rating_avg, 1) : null,

            'is_featured' => $feature !== null,
            'featured_until' => $feature?->ends_at,

            // حالة المشاهد الحالي (false / null للزائر)
            'is_owner' => $isOwner,
            'is_bookmarked' => (bool) ($this->is_bookmarked ?? false),
            'is_shared' => (bool) ($this->is_shared ?? false),
            'my_reaction' => $this->my_react_type_id !== null ? (int) $this->my_react_type_id : null,
            'my_rating' => $this->my_rating !== null ? (float) $this->my_rating : null,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
