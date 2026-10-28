<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserVerificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'national_id' => ['required', 'digits:11'],
            'linkedin_url' => ['nullable', 'url', 'max:255'],
            'identity_front' => ['required', 'file', 'mimes:jpg,jpeg,png', 'max:10240'],
            'identity_back' => ['required', 'file', 'mimes:jpg,jpeg,png', 'max:10240'],
        ];
    }
}
