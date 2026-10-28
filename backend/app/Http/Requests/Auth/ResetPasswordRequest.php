<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * إعادة التعيين عبر الرابط (token) أو عبر الرمز (otp_code) — أحدهما مطلوب
 */
class ResetPasswordRequest extends FormRequest
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
            'token' => ['required_without:otp_code', 'nullable', 'string'],
            'otp_code' => ['required_without:token', 'nullable', 'digits:'.config('platform.otp.length', 6)],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ];
    }
}
