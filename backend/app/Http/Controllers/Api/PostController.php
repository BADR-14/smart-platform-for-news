<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DestroyPostRequest;
use App\Http\Requests\GetPostsRequest;
use App\Http\Requests\GetUserPostsRequest;
use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Http\Resources\PostResource;
use App\Models\Account;
use App\Models\Post;
use App\Services\PostService;
use App\Services\PostViewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PostController extends Controller
{
    public function __construct(
        private readonly PostService $postService,
        private readonly PostViewService $postViewService,
    ) {}

    /**
     * GET /posts — الصفحة الرئيسية (عامة) مع البحث والفلاتر
     */
    public function index(GetPostsRequest $request): JsonResponse
    {
        $posts = $this->postService->getPosts($request->validated(), $request->user());

        return $this->paginated($posts, PostResource::class);
    }

    /**
     * GET /posts/featured — المنشورات المميزة السارية
     */
    public function featured(Request $request): JsonResponse
    {
        return $this->ok(PostResource::collection($this->postService->getFeaturedPosts($request->user())));
    }

    /**
     * GET /posts/{post}
     */
    public function show(Request $request, Post $post): JsonResponse
    {
        Gate::forUser($request->user())->authorize('view', $post);

        return $this->ok(new PostResource($this->postService->getPost($post, $request->user())));
    }

    public function store(StorePostRequest $request): JsonResponse
    {
        $post = $this->postService->storePost($request->user(), $request->validated());

        return $this->created(new PostResource($post), 'تم إنشاء المنشور بنجاح.');
    }

    public function update(UpdatePostRequest $request, Post $post): JsonResponse
    {
        $post = $this->postService->updatePost($post, $request->user(), $request->validated());

        return $this->ok(new PostResource($post), 'تم تعديل المنشور بنجاح.');
    }

    public function destroy(DestroyPostRequest $request, Post $post): JsonResponse
    {
        // DestroyPostRequest مسؤول عن التحقق من الصلاحية فقط
        $this->postService->destroyPost($post);

        return $this->message('تم حذف المنشور.');
    }

    /**
     * GET /posts/my-posts?status=&search= — منشورات صاحب الحساب بكل حالاتها
     */
    public function myPosts(GetUserPostsRequest $request): JsonResponse
    {
        $posts = $this->postService->getOwnPosts(
            $request->user(),
            $request->validated('status'),
            $request->validated('search'),
            $request->integer('per_page', 10),
        );

        return $this->paginated($posts, PostResource::class);
    }

    /**
     * GET /accounts/{account}/posts — منشورات حساب آخر (المنشورة فقط)
     */
    public function accountPosts(Request $request, Account $account): JsonResponse
    {
        $posts = $this->postService->getAccountPosts($account, $request->user());

        return $this->paginated($posts, PostResource::class);
    }

    /**
     * POST /posts/{post}/view — تسجيل مشاهدة (زائر أو مستخدم)
     */
    public function recordView(Request $request, Post $post): JsonResponse
    {
        $this->postViewService->record($post, $request->user(), $request->ip());

        return $this->ok(['views_count' => (int) $post->fresh()->views_count]);
    }
}
