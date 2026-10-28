<?php

namespace App\Http\Controllers\Api;

use App\Enums\Post\PostStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AdminPostResource;
use App\Models\Post;
use App\Services\PostsManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PostsManagementController extends Controller
{
    public function __construct(private readonly PostsManagementService $postService) {}

    /**
     * GET /admin/posts?search=&status=&category_id=&date_filter=last_7|last_30|last_90
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(PostStatus::values())],
            'category_id' => ['nullable', 'integer'],
            'date_filter' => ['nullable', 'in:last_7,last_30,last_90'],
        ]);

        return $this->paginated(
            $this->postService->getFilteredPosts($filters),
            AdminPostResource::class,
            ['stats' => $this->postService->getPostsStats()],
        );
    }

    /**
     * PATCH /admin/posts/{post}/status — وضع المنشور قيد المراجعة / رفضه / إعادة نشره / أرشفته
     */
    public function updateStatus(Request $request, Post $post): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in([
                PostStatus::Published->value,
                PostStatus::UnderReview->value,
                PostStatus::Rejected->value,
                PostStatus::Archived->value,
            ])],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $post = $this->postService->changeStatus($post, PostStatus::from($validated['status']), $validated['note'] ?? null);

        return $this->ok(['id' => $post->id, 'status' => $post->status], 'تم تحديث حالة المنشور.');
    }

    public function toggleArchiveStatus(Post $post): JsonResponse
    {
        $post = $this->postService->toggleArchiveStatus($post);

        return $this->ok(['id' => $post->id, 'status' => $post->status], 'تم تحديث حالة المنشور.');
    }

    public function destroy(Post $post): JsonResponse
    {
        $this->postService->deletePost($post);

        return $this->message('تم حذف المنشور.');
    }
}
