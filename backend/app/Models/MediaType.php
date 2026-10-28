<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MediaType extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['name'];

    public function media(): HasMany
    {
        return $this->hasMany(Media::class);
    }
}
