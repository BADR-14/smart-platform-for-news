<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCompanyProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'min:2', 'max:255'],
            'website_url' => ['sometimes', 'nullable', 'url', 'max:255'],
            'category_id' => ['sometimes', 'integer', Rule::exists('categories', 'id')->whereNull('deleted_at')],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'address' => ['sometimes', 'nullable', 'string', 'max:500'],
            'phone' => ['sometimes', 'string', 'regex:/^\+?[0-9]{7,15}$/'],
            'logo' => ['sometimes', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
        ];
    }
}
