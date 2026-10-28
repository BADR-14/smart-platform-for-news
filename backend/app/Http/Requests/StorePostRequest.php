<?php

namespace App\Http\Requests;

use App\Http\Requests\Traits\PostValidationTrait;
use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
{
    use PostValidationTrait;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return array_merge(
            $this->commonRules(),
            $this->conditionalRules(),
            ['action' => ['required', 'in:publish,draft']],
        );
    }
}
