<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SharePostRequest;
use App\Http\Resources\PostShareResource;
use App\Models\Post;
use App\Models\PostShare;
use App\Services\PostShareService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PostShareController extends Controller
{
    public function __construct(private readonly PostShareService $postShareService) {}

    /**
     * POST /post-shares/{post} — مشاركة منشور مع نص اختياري
     */
    public function store(SharePostRequest $request, Post $post): JsonResponse
    {
        $share = $this->postShareService->store($request->validated('share_text'), $request->user(), $post);

        return $this->created(new PostShareResource($share), 'تمت مشاركة المنشور.');
    }

    public function update(SharePostRequest $request, PostShare $postShare): JsonResponse
    {
        $share = $this->postShareService->updateSharePost($postShare, $request->user(), $request->validated('share_text'));

        return $this->ok(new PostShareResource($share), 'تم تعديل المشاركة.');
    }

    public function destroy(Request $request, PostShare $postShare): JsonResponse
    {
        Gate::authorize('delete', $postShare);

        $this->postShareService->destroySharePost($postShare);

        return $this->message('تم حذف المشاركة.');
    }
}
