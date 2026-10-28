<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReactType extends Model
{
    public $timestamps = false;
    
    protected $fillable = ['type', 'icon'];

    public function userReacts(): HasMany
    {
        return $this->hasMany(UserReact::class);
    }
}
