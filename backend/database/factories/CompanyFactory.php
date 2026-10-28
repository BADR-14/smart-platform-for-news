<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Category;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    /**
     * الافتراضي: شركة موافق عليها (حسابها من نوع Company وموثّق)
     */
    public function definition(): array
    {
        return [
            'account_id' => Account::factory()->company(),
            'name' => $this->faker->unique()->company(),
            'category_id' => null,
            'phone' => $this->faker->numerify('+9639########'),
            'address' => $this->faker->address(),
            'description' => $this->faker->paragraph(),
            'logo_url' => null,
            'company_registration_number' => $this->faker->numerify('#####'),
            'tax_id' => $this->faker->numerify('############'),
            'website_url' => $this->faker->url(),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Company $company) {
            // اسم الحساب = اسم الشركة (كما يحدث عند الموافقة على الطلب)
            $company->account?->update(['name' => $company->name]);
        });
    }

    public function withCategory(): static
    {
        return $this->state(fn () => [
            'category_id' => Category::query()->whereIn('type', ['company', 'both'])->inRandomOrder()->value('id')
                ?? Category::factory()->state(['type' => 'company']),
        ]);
    }

    /**
     * طلب شركة لم يُوافَق عليه بعد: الحساب ما زال "مستخدم"
     */
    public function pendingApproval(): static
    {
        return $this->state(['account_id' => Account::factory()]);
    }
}
