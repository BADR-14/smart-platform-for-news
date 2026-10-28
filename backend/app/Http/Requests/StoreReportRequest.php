<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // post = بلاغ على منشور، account = بلاغ على حساب (مستخدم أو شركة)
            'reportable_type' => ['required', 'string', 'in:post,account'],
            'reportable_id' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'min:3', 'max:255'],
        ];
    }
}
