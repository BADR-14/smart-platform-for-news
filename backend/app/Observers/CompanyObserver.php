<?php

namespace App\Observers;

use App\Models\Company;

class CompanyObserver
{
    /**
     * كل شركة جديدة تُنشأ معها محفظتها الرئيسية برصيد صفر
     */
    public function created(Company $company): void
    {
        $company->wallet()->firstOrCreate([], [
            'name' => 'Main Wallet',
            'description' => 'المحفظة الرئيسية للشركة',
            'balance' => 0,
            'currency' => config('platform.currency', 'USD'),
            'is_active' => true,
        ]);
    }
}
