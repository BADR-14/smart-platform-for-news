<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = ['name', 'type'];

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function companies(): HasMany
    {
        return $this->hasMany(Company::class);
    }

    /**
     * تصنيفات تصلح لنوع معيّن (post أو company) مع التصنيفات المشتركة both
     */
    public function scopeForType(Builder $query, ?string $type): Builder
    {
        return $query->when(
            in_array($type, ['post', 'company'], true),
            fn (Builder $q) => $q->whereIn('type', [$type, 'both'])
        );
    }
}
