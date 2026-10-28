<?php

namespace App\Http\Requests;

use App\Enums\Post\PostType;
use App\Http\Requests\Traits\PostValidationTrait;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePostRequest extends FormRequest
{
    use PostValidationTrait;

    public function authorize(): bool
    {
        $post = $this->route('post');

        return $post && $this->user()->can('update', $post);
    }

    /**
     * تعديل جزئي: كل الحقول اختيارية، والموقع يصبح مطلوباً فقط إذا أُرسل is_online = false
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:10000'],
            'post_type' => ['sometimes', Rule::in(PostType::values())],
            'category_id' => ['sometimes', 'integer', Rule::exists('categories', 'id')->whereNull('deleted_at')],
            'is_online' => ['sometimes', 'boolean'],
            'start_date' => ['sometimes', 'nullable', 'date'],
            'end_date' => ['sometimes', 'nullable', 'date', 'after_or_equal:start_date'],
            'tags' => ['sometimes', 'nullable', 'array', 'max:5'],
            'tags.*' => ['string', 'max:50'],
            'media' => ['sometimes', 'array', 'max:5'],
            'media.*' => ['file', 'mimes:jpg,jpeg,png,webp,mp4', 'max:10240'],
            'remove_media_ids' => ['sometimes', 'array'],
            'remove_media_ids.*' => ['integer'],
            'action' => ['sometimes', 'in:publish,draft,archive'],
            ...$this->conditionalRules(),
        ];
    }
}
