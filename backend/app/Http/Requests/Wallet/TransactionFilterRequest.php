<?php

namespace App\Http\Requests\Wallet;

use Illuminate\Foundation\Http\FormRequest;

class TransactionFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['nullable', 'in:credit,debit,gift'],
            'status' => ['nullable', 'in:pending,completed,failed'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.in' => 'نوع المعاملة يجب أن يكون: credit أو debit أو gift.',
            'status.in' => 'الحالة يجب أن تكون: pending أو completed أو failed.',
        ];
    }
}
