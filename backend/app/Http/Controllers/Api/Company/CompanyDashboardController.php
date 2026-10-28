<?php

namespace App\Http\Controllers\Api\Company;

use App\Http\Requests\Company\UpdateCompanyProfileRequest;
use App\Http\Resources\Company\CompanyDashboardResource;
use App\Services\Company\CompanyDashboardService;
use App\Services\Company\CompanyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyDashboardController extends CompanyAreaController
{
    public function __construct(
        private readonly CompanyDashboardService $dashboardService,
        private readonly CompanyService $companyService,
    ) {}

    /**
     * GET /company/dashboard — أرقام الصفحة الرئيسية للوحة الشركة
     */
    public function overview(Request $request): JsonResponse
    {
        return $this->ok($this->dashboardService->getOverview($this->company($request)));
    }

    /**
     * GET /company/profile
     */
    public function profile(Request $request): JsonResponse
    {
        return $this->ok(new CompanyDashboardResource(
            $this->company($request)->load(['account', 'category'])
        ));
    }

    /**
     * POST /company/profile (multipart بسبب الشعار)
     */
    public function updateProfile(UpdateCompanyProfileRequest $request): JsonResponse
    {
        $company = $this->companyService->updateProfile(
            $this->company($request),
            $request->safe()->except('logo'),
            $request->file('logo'),
        );

        return $this->ok(new CompanyDashboardResource($company), 'تم تحديث بيانات الشركة.');
    }
}
