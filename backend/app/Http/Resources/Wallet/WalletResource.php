<?php

namespace App\Http\Resources\Wallet;

use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Wallet
 */
class WalletResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'balance' => (float) $this->balance,
            'formatted_balance' => $this->formatted_balance,
            'currency' => $this->currency,
            'is_active' => (bool) $this->is_active,
        ];
    }
}
