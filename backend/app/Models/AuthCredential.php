<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AuthCredential extends Model
{
    use SoftDeletes;

    public $timestamps = false;

    protected $fillable = [
        'account_id',
        'provider',
        'provider_identifier',
        'password_hash',
    ];

    protected $hidden = ['password_hash'];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
