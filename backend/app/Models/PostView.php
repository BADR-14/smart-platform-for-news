<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostView extends Model
{
    protected $fillable = ['post_id', 'account_id', 'ip_address'];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
}
