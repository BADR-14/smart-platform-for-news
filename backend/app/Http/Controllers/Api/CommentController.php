<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DestroyCommentRequest;
use App\Http\Requests\StoreCommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Models\Post;
use App\Services\CommentService;
use Illuminate\Http\JsonResponse;

class CommentController extends Controller
{
    public function __construct(private readonly CommentService $commentService) {}

    /**
     * GET /posts/{post}/comments
     */
    public function index(Post $post): JsonResponse
    {
        return $this->paginated($this->commentService->getComments($post), CommentResource::class);
    }

    /**
     * GET /posts/{post}/comments/{comment}/replies
     */
    public function replies(Post $post, Comment $comment): JsonResponse
    {
        return $this->paginated($this->commentService->getReplies($post, $comment), CommentResource::class);
    }

    public function store(StoreCommentRequest $request, Post $post): JsonResponse
    {
        $comment = $this->commentService->storeComment($request->user(), $post, $request->validated('content'));

        return $this->created(new CommentResource($comment), 'تمت إضافة التعليق.');
    }

    public function reply(StoreCommentRequest $request, Post $post, Comment $comment): JsonResponse
    {
        $reply = $this->commentService->storeComment(
            $request->user(),
            $post,
            $request->validated('content'),
            parent: $comment,
        );

        return $this->created(new CommentResource($reply), 'تمت إضافة الرد.');
    }

    public function destroy(DestroyCommentRequest $request, Post $post, Comment $comment): JsonResponse
    {
        // DestroyCommentRequest مسؤول عن التحقق من الصلاحية فقط
        $this->commentService->deleteComment($comment);

        return $this->message('تم حذف التعليق.');
    }
}
