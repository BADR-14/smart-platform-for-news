<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DestroyCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $comment = $this->route('comment');
        $post = $this->route('post');

        return $comment
            && $post
            && (int) $comment->post_id === (int) $post->id
            && $this->user()->can('delete', $comment);
    }

    public function rules(): array
    {
        return [];
    }
}
