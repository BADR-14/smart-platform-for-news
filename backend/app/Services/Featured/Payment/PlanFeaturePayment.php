<?php

namespace App\Services\Featured\Payment;

use App\Enums\Featured\FeaturePaymentMethod;
use App\Exceptions\ApiException;
use App\Models\Company;
use App\Models\CompanyPlan;
use App\Models\Post;

/**
 * الدفع من رصيد الباقة: يُخصم منشور واحد من المنشورات المتبقية في الباقة النشطة
 */
class PlanFeaturePayment implements FeaturePaymentStrategy
{
    public function method(): FeaturePaymentMethod
    {
        return FeaturePaymentMethod::Plan;
    }

    public function pay(Company $company, Post $post): FeaturePaymentResult
    {
        $companyPlan = CompanyPlan::where('company_id', $company->id)
            ->where('status', 'active')
            ->where('posts_remaining', '>', 0)
            ->latest('id')
            ->lockForUpdate()
            ->first();

        if (! $companyPlan) {
            throw new ApiException('لا توجد باقة نشطة فيها منشورات متبقية.', 422, 'no_active_plan');
        }

        $remaining = $companyPlan->posts_remaining - 1;

        $companyPlan->update([
            'posts_remaining' => $remaining,
            'status' => $remaining === 0 ? 'expired' : 'active',
        ]);

        $postsInPlan = max(1, (int) $companyPlan->plan?->posts_count);

        return new FeaturePaymentResult(
            cost: round((float) $companyPlan->price_paid / $postsInPlan, 2),
            companyPlanId: $companyPlan->id,
        );
    }
}
