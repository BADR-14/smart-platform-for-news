<?php

namespace App\Http\Resources;

use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * ملخص الحساب كما يظهر بجانب المنشورات والتعليقات والمشاركات
 *
 * @mixin Account
 */
class AuthorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'avatar' => $this->avatarUrl(),
            'type' => $this->role()->slug(),
            'is_verified' => $this->isVerified(),
            'company_id' => $this->isCompany() ? $this->company?->id : null,
        ];
    }
}
