<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReviewVerificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'decision' => ['required', 'in:approved,rejected'],
            'rejection_reasons' => ['nullable', 'array', 'required_if:decision,rejected', 'max:10'],
            'rejection_reasons.*' => ['string', 'max:255'],
        ];
    }
}
