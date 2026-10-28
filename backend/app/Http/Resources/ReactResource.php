<?php

namespace App\Http\Resources;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Post
 */
class ReactResource extends JsonResource
{
    public function __construct(mixed $resource, private readonly string $action, private readonly ?int $reactTypeId)
    {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        return [
            'action' => $this->action,
            'post_id' => $this->id,
            'likes_count' => (int) $this->likes_count,
            'my_reaction' => $this->reactTypeId,
        ];
    }
}
