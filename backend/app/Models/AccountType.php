<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountType extends Model
{
    use SoftDeletes;

    protected $fillable = ['id', 'name', 'description'];

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }
}
