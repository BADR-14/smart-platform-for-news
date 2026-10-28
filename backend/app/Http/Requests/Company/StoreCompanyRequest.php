<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * طلب إنشاء شركة + توثيقها (multipart)
 */
class StoreCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'website_url' => ['nullable', 'url', 'max:255'],
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')->whereNull('deleted_at')],
            'description' => ['required', 'string', 'max:2000'],
            'address' => ['nullable', 'string', 'max:500'],
            'phone' => ['required', 'string', 'regex:/^\+?[0-9]{7,15}$/'],
            'logo' => ['required', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'company_registration_number' => ['required', 'string', 'max:100'],
            'tax_id' => ['required', 'string', 'max:100'],
            'documents' => ['required', 'array'],
            'documents.commercial_register' => ['required', 'file', 'mimes:jpeg,png,jpg,pdf', 'max:10240'],
            'documents.tax_document' => ['required', 'file', 'mimes:jpeg,png,jpg,pdf', 'max:10240'],
        ];
    }
}
