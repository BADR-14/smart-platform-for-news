<?php

namespace App\Services\Company;

use App\Exceptions\ApiException;
use App\Models\Company;
use App\Models\CompanyPlan;
use App\Models\Plan;
use App\Services\Wallet\WalletService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * اشتراك الشركة بالباقات (الدفع من المحفظة)
 */
class CompanyPlanService
{
    public function __construct(private readonly WalletService $walletService) {}

    /**
     * الباقات المتاحة للشراء
     */
    public function getAvailablePlans(): Collection
    {
        return Plan::where('is_active', true)->orderBy('price')->get();
    }

    public function getActivePlan(Company $company): ?CompanyPlan
    {
        return $company->activePlan()->with('plan')->first();
    }

    public function getSubscriptions(Company $company, int $perPage = 10): LengthAwarePaginator
    {
        return $company->plans()->with('plan')->latest('id')->paginate($perPage);
    }

    /**
     * شراء باقة: يُخصم سعرها من المحفظة وتُضاف منشوراتها المميزة لرصيد الشركة.
     * لا يمكن شراء باقة جديدة ما دام في الباقة الحالية منشورات متبقية.
     */
    public function subscribe(Company $company, Plan $plan): CompanyPlan
    {
        if (! $plan->is_active) {
            throw new ApiException('هذه الباقة غير متاحة حالياً.', 422, 'plan_inactive');
        }

        return DB::transaction(function () use ($company, $plan) {
            // قفل سجل الشركة يمنع شراء باقتين بطلبين متزامنين
            Company::whereKey($company->id)->lockForUpdate()->first();

            if ($company->activePlan()->exists()) {
                throw new ApiException('لديك باقة نشطة فيها منشورات متبقية.', 422, 'active_plan_exists');
            }

            $this->walletService->debit(
                $this->walletService->getWallet($company),
                (float) $plan->price,
                "شراء باقة: {$plan->name}",
                ['kind' => 'plan_purchase', 'plan_id' => $plan->id],
            );

            // أي باقة قديمة بقيت "نشطة" برصيد صفر تُغلق
            $company->plans()->where('status', 'active')->update(['status' => 'expired']);

            return CompanyPlan::create([
                'company_id' => $company->id,
                'plan_id' => $plan->id,
                'posts_remaining' => $plan->posts_count,
                'status' => 'active',
                'price_paid' => $plan->price,
            ])->load('plan');
        });
    }
}
