<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Services\BookmarkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookmarkController extends Controller
{
    public function __construct(private readonly BookmarkService $bookmarkService) {}

    public function toggleBookmark(Request $request, Post $post): JsonResponse
    {
        $isBookmarked = $this->bookmarkService->toggleBookmark($request->user(), $post);

        return $this->ok(
            ['post_id' => $post->id, 'is_bookmarked' => $isBookmarked],
            $isBookmarked ? 'تم حفظ المنشور.' : 'تم إلغاء حفظ المنشور.',
        );
    }
}
