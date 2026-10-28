<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\CompanyPlanResource;
use App\Http\Resources\Admin\PlanResource;
use App\Http\Resources\FeaturedPostResource;
use App\Models\Plan;
use App\Services\Featured\FeaturedPostService;
use App\Services\PlanService;
use App\Services\PlatformSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlanManagementController extends Controller
{
    public function __construct(
        private readonly PlanService $planService,
        private readonly PlatformSettingsService $settings,
        private readonly FeaturedPostService $featuredPostService,
    ) {}

    /**
     * GET /admin/plans — الباقات + الإحصائيات + إعدادات المنشور المميز
     */
    public function index(): JsonResponse
    {
        return $this->ok([
            'plans' => PlanResource::collection($this->planService->getPlans()),
            'stats' => $this->planService->getStats(),
            'settings' => $this->settings->all(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $plan = $this->planService->createPlan($this->validatePlan($request));

        return $this->created(new PlanResource($plan), 'تم إنشاء الباقة.');
    }

    public function update(Request $request, Plan $plan): JsonResponse
    {
        $plan = $this->planService->updatePlan($plan, $this->validatePlan($request));

        return $this->ok(new PlanResource($plan), 'تم تعديل الباقة.');
    }

    public function toggleStatus(Plan $plan): JsonResponse
    {
        $plan = $this->planService->togglePlanStatus($plan);

        return $this->ok(new PlanResource($plan), $plan->is_active ? 'تم تفعيل الباقة.' : 'تم تعطيل الباقة.');
    }

    public function destroy(Plan $plan): JsonResponse
    {
        $this->planService->deletePlan($plan);

        return $this->message('تم حذف الباقة.');
    }

    /**
     * GET /admin/subscriptions?search=&status=active|expired
     */
    public function subscriptions(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:active,expired'],
        ]);

        return $this->paginated(
            $this->planService->getCompanySubscriptions($filters['search'] ?? null, $filters['status'] ?? null),
            CompanyPlanResource::class,
        );
    }

    /**
     * GET /admin/featured-posts?status=
     */
    public function featuredPosts(Request $request): JsonResponse
    {
        $filters = $request->validate(['status' => ['nullable', 'in:queued,active,expired,cancelled']]);

        return $this->paginated(
            $this->featuredPostService->getAllFeaturedPosts($filters['status'] ?? null),
            FeaturedPostResource::class,
        );
    }

    /**
     * PATCH /admin/platform-settings — سعر ومدة المنشور المميز
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            PlatformSettingsService::FEATURED_POST_PRICE => ['sometimes', 'numeric', 'min:0', 'max:100000'],
            PlatformSettingsService::FEATURED_POST_DURATION_DAYS => ['sometimes', 'integer', 'min:1', 'max:365'],
        ]);

        return $this->ok($this->settings->update($validated), 'تم حفظ الإعدادات.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePlan(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'posts_count' => ['required', 'integer', 'min:1', 'max:1000'],
            'price' => ['required', 'numeric', 'min:0', 'max:1000000'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
    }
}
