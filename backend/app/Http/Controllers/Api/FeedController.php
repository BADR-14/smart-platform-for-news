<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use App\Services\FeedService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FeedController extends Controller
{
    public function __construct(private readonly FeedService $feedService) {}

    /**
     * GET /feed — منشورات الشركات التي أتابعها
     */
    public function index(Request $request): JsonResponse
    {
        $posts = $this->feedService->getFollowedCompaniesPosts($request->user());

        return $this->paginated($posts, PostResource::class);
    }
}
