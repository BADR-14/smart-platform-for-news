<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class VerifyOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['email' => strtolower(trim((string) $this->email))]);
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'otp_code' => ['required', 'digits:'.config('platform.otp.length', 6)],
            'device_name' => ['nullable', 'string', 'max:100'],
        ];
    }
}
