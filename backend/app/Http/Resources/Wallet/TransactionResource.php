<?php

namespace App\Http\Resources\Wallet;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Transaction
 */
class TransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'type' => $this->type,
            // نوع العملية بدقة أكبر: topup / withdrawal / plan_purchase / featured_post
            'kind' => $this->meta['kind'] ?? ($this->payment_id ? 'topup' : null),
            'amount' => (float) $this->amount,
            'currency' => $this->currency,
            'status' => $this->status,
            'description' => $this->description,
            'created_at' => $this->created_at,
        ];
    }
}
