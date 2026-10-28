<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\CompanyPlan;
use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompanyPlan>
 */
class CompanyPlanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'plan_id' => Plan::factory(),
            'posts_remaining' => 10,
            'status' => 'active',
            'price_paid' => 80.00,
        ];
    }

    public function expired(): static
    {
        return $this->state(['status' => 'expired', 'posts_remaining' => 0]);
    }
}
