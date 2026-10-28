<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DestroyPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        $post = $this->route('post');

        return $post && $this->user()->can('delete', $post);
    }

    public function rules(): array
    {
        return [];
    }
}
