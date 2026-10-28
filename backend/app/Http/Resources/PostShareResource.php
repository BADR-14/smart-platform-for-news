<?php

namespace App\Http\Resources;

use App\Models\PostShare;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PostShare
 */
class PostShareResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => 'share',
            'share_text' => $this->share_text,
            'shared_by' => $this->account ? new AuthorResource($this->account) : null,
            'is_owner' => (int) $this->account_id === (int) $request->user()?->id,
            'post' => $this->post ? new PostResource($this->post) : null,
            'created_at' => $this->created_at,
        ];
    }
}
