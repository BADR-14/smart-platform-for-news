<?php

namespace App\Http\Resources;

use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Comment
 */
class CommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $viewer = $request->user();
        $author = $this->user?->account;

        return [
            'id' => $this->id,
            'content' => $this->content,
            'post_id' => $this->post_id,
            'parent_id' => $this->parent_id,
            'replies_count' => (int) ($this->replies_count ?? 0),
            'author' => $author ? new AuthorResource($author) : null,
            'is_owner' => $viewer !== null && (int) $author?->id === (int) $viewer->id,
            'can_delete' => $viewer !== null && $viewer->can('delete', $this->resource),
            'created_at' => $this->created_at,
        ];
    }
}
