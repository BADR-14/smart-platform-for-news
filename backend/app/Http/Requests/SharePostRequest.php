<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SharePostRequest extends FormRequest
{
    /**
     * إنشاء مشاركة متاح لأي حساب مسجّل، أما تعديلها فلصاحبها فقط
     */
    public function authorize(): bool
    {
        $postShare = $this->route('postShare');

        return $postShare === null || $this->user()->can('update', $postShare);
    }

    public function rules(): array
    {
        return [
            'share_text' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
