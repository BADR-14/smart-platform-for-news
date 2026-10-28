<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReviewRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Post;
use App\Services\ReviewService;
use Illuminate\Http\JsonResponse;

class ReviewController extends Controller
{
    public function __construct(private readonly ReviewService $reviewService) {}

    /**
     * GET /posts/{post}/reviews
     */
    public function index(Post $post): JsonResponse
    {
        return $this->paginated($this->reviewService->getPostReviews($post), ReviewResource::class);
    }

    /**
     * POST /posts/{post}/reviews
     */
    public function store(StoreReviewRequest $request, Post $post): JsonResponse
    {
        $review = $this->reviewService->storeReview($request->user(), $post, $request->validated());

        return $this->created(new ReviewResource($review), 'تم إرسال تقييمك بنجاح.');
    }
}
