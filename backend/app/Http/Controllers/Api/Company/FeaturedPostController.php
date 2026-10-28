<?php

namespace App\Http\Controllers\Api\Company;

use App\Enums\Featured\FeaturePaymentMethod;
use App\Http\Resources\FeaturedPostResource;
use App\Models\Post;
use App\Services\Featured\FeaturedPostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FeaturedPostController extends CompanyAreaController
{
    public function __construct(private readonly FeaturedPostService $featuredPostService) {}

    /**
     * GET /company/featured — سجل المنشورات المميزة للشركة
     */
    public function index(Request $request): JsonResponse
    {
        return $this->paginated(
            $this->featuredPostService->getCompanyFeaturedPosts($this->company($request)),
            FeaturedPostResource::class,
        );
    }

    /**
     * GET /company/featured/options — السعر والمدة والرصيد والباقة النشطة
     */
    public function options(Request $request): JsonResponse
    {
        return $this->ok($this->featuredPostService->getOptions($this->company($request)));
    }

    /**
     * POST /company/posts/{post}/feature — تمييز منشور
     * payment_method: wallet (من الرصيد مباشرة) أو plan (من الباقة)
     */
    public function store(Request $request, Post $post): JsonResponse
    {
        $validated = $request->validate([
            'payment_method' => ['required', Rule::enum(FeaturePaymentMethod::class)],
        ]);

        $featured = $this->featuredPostService->feature(
            $this->company($request),
            $post,
            FeaturePaymentMethod::from($validated['payment_method']),
        );

        return $this->created(new FeaturedPostResource($featured), 'تم تمييز المنشور بنجاح.');
    }
}
