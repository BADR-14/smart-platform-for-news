<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\CompanyPlan;
use App\Models\Plan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * إدارة الباقات من لوحة الأدمن
 */
class PlanService
{
    public function getPlans(): Collection
    {
        return Plan::withCount('companyPlans')->latest('id')->get();
    }

    /**
     * @return array<string, int|float>
     */
    public function getStats(): array
    {
        return [
            'total_plans' => Plan::count(),
            'active_plans' => Plan::where('is_active', true)->count(),
            'total_subscriptions' => CompanyPlan::count(),
            'active_subscriptions' => CompanyPlan::where('status', 'active')->where('posts_remaining', '>', 0)->count(),
            'total_payments' => round((float) CompanyPlan::sum('price_paid'), 2),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createPlan(array $data): Plan
    {
        return Plan::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updatePlan(Plan $plan, array $data): Plan
    {
        $plan->update($data);

        return $plan;
    }

    public function togglePlanStatus(Plan $plan): Plan
    {
        $plan->update(['is_active' => ! $plan->is_active]);

        return $plan;
    }

    /**
     * الباقة التي عليها اشتراكات لا تُحذف (حتى لا يضيع سجل المشتريات) بل تُعطَّل
     */
    public function deletePlan(Plan $plan): void
    {
        if ($plan->companyPlans()->exists()) {
            throw new ApiException(
                'لا يمكن حذف باقة لها اشتراكات. يمكنك تعطيلها بدلاً من ذلك.',
                422,
                'plan_has_subscriptions',
            );
        }

        $plan->delete();
    }

    public function getCompanySubscriptions(?string $search = null, ?string $status = null): LengthAwarePaginator
    {
        return CompanyPlan::query()
            ->with(['company', 'plan'])
            ->when($search, fn ($q) => $q->whereHas('company', fn ($c) => $c->where('name', 'LIKE', "%{$search}%")))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->latest('id')
            ->paginate(10);
    }
}
