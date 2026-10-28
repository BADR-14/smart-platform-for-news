<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserReact extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['user_id', 'react_type_id', 'post_id'];

    protected $casts = [
        'react_type_id' => 'integer',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reactType(): BelongsTo
    {
        return $this->belongsTo(ReactType::class);
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
}
