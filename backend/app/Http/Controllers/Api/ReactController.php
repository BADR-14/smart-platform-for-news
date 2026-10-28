<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReactRequest;
use App\Http\Resources\ReactResource;
use App\Models\Post;
use App\Models\ReactType;
use App\Services\ReactService;
use Illuminate\Http\JsonResponse;

class ReactController extends Controller
{
    public function __construct(private readonly ReactService $reactService) {}

    /**
     * GET /react-types — أنواع التفاعلات المتاحة
     */
    public function types(): JsonResponse
    {
        return $this->ok(ReactType::orderBy('id')->get(['id', 'type', 'icon']));
    }

    public function react(ReactRequest $request, Post $post): JsonResponse
    {
        $result = $this->reactService->toggleReact(
            post: $post,
            account: $request->user(),
            reactTypeId: (int) $request->validated('react_type_id'),
        );

        return $this->ok(new ReactResource($post->refresh(), $result['action'], $result['react_type_id']));
    }
}
