<?php

namespace App\Http\Requests;

use App\Enums\Post\PostType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GetPostsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'post_type' => ['nullable', Rule::in(PostType::values())],
            'date' => ['nullable', 'date'],
            'category_id' => ['nullable', 'integer'],
            'is_online' => ['nullable', 'boolean'],
            'rating' => ['nullable', 'numeric', 'between:1,5'],
            'keyword' => ['nullable', 'string', 'max:50'],
        ];
    }
}
