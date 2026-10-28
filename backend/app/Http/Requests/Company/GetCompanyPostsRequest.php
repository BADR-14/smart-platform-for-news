<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;

class GetCompanyPostsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // زوار + مستخدمين مسجلين
    }

    public function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ];
    }
}
