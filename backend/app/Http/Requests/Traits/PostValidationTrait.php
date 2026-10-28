<?php

namespace App\Http\Requests\Traits;

use App\Enums\Post\PostType;
use Illuminate\Validation\Rule;

trait PostValidationTrait
{
    /**
     * يحوّل is_online القادمة من multipart ("1"/"0"/"true"/"false") إلى boolean حقيقية
     * حتى تعمل قواعد required_if / prohibited_if بشكل صحيح.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('is_online')) {
            $this->merge([
                'is_online' => filter_var($this->input('is_online'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            ]);
        }
    }

    protected function commonRules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'post_type' => ['required', Rule::in(PostType::values())],
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')->whereNull('deleted_at')],
            'is_online' => ['required', 'boolean'],
            'start_date' => ['nullable', 'date', 'required_if:post_type,event'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date', 'required_if:post_type,event'],
            'tags' => ['nullable', 'array', 'max:5'],
            'tags.*' => ['string', 'max:50'],
            'media' => ['nullable', 'array', 'max:5'],
            'media.*' => ['file', 'mimes:jpg,jpeg,png,webp,mp4', 'max:10240'],
        ];
    }

    protected function conditionalRules(): array
    {
        return [
            'location' => ['nullable', 'string', 'max:255', 'required_if:is_online,false', 'prohibited_if:is_online,true'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_if:is_online,false', 'prohibited_if:is_online,true'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_if:is_online,false', 'prohibited_if:is_online,true'],
        ];
    }
}
