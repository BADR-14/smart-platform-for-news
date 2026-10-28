<?php

namespace App\Http\Controllers\Api\Company;

use App\Http\Resources\Admin\CompanyPlanResource;
use App\Http\Resources\Admin\PlanResource;
use App\Models\Plan;
use App\Services\Company\CompanyPlanService;
use App\Services\PlatformSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyPlanController extends CompanyAreaController
{
    public function __construct(
        private readonly CompanyPlanService $companyPlanService,
        private readonly PlatformSettingsService $settings,
    ) {}

    /**
     * GET /company/plans — الباقات المتاحة + الباقة الحالية + سعر المنشور المفرد للمقارنة
     */
    public function index(Request $request): JsonResponse
    {
        $activePlan = $this->companyPlanService->getActivePlan($this->company($request));

        return $this->ok([
            'plans' => PlanResource::collection($this->companyPlanService->getAvailablePlans()),
            'active_plan' => $activePlan ? new CompanyPlanResource($activePlan) : null,
            'single_post_price' => $this->settings->featuredPostPrice(),
            'featured_duration_days' => $this->settings->featuredPostDurationDays(),
            'currency' => config('platform.currency', 'USD'),
        ]);
    }

    /**
     * POST /company/plans/{plan}/subscribe — شراء باقة من رصيد المحفظة
     */
    public function subscribe(Request $request, Plan $plan): JsonResponse
    {
        $companyPlan = $this->companyPlanService->subscribe($this->company($request), $plan);

        return $this->created(new CompanyPlanResource($companyPlan), 'تم الاشتراك بالباقة بنجاح.');
    }

    /**
     * GET /company/subscriptions — سجل اشتراكات الشركة
     */
    public function subscriptions(Request $request): JsonResponse
    {
        return $this->paginated(
            $this->companyPlanService->getSubscriptions($this->company($request)),
            CompanyPlanResource::class,
        );
    }
}
