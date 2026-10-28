<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DestroyAccountRequest extends FormRequest
{
    /**
     * حساب الأدمن لا يُحذف من الـ API
     */
    public function authorize(): bool
    {
        return ! $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            // مطلوبة للحسابات التي لها كلمة مرور (يتحقق منها الـ Service)
            'password' => ['nullable', 'string'],
        ];
    }
}
