<?php

namespace Database\Factories;

use App\Enums\Account\AccountRole;
use App\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<Account>
 */
class AccountFactory extends Factory
{
    public function definition(): array
    {
        return [
            'account_type_id' => AccountRole::User->value,
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'verification_status' => 'not_submitted',
            'is_active' => true,
            'avatar' => null,
            'email_verified_at' => now(),
        ];
    }

    /**
     * كل حساب له ملف شخصي (users) وكلمة مرور محلية افتراضية "password"
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Account $account) {
            $account->user()->firstOrCreate([], [
                'system_role' => $account->isAdmin() ? 'admin' : 'user',
            ]);

            $account->authCredentials()->firstOrCreate(
                ['provider' => 'local'],
                ['password_hash' => Hash::make('password')],
            );
        });
    }

    public function verified(): static
    {
        return $this->state(['verification_status' => 'approved']);
    }

    public function unverifiedEmail(): static
    {
        return $this->state(['email_verified_at' => null]);
    }

    public function banned(string $reason = 'مخالفة سياسات المنصة'): static
    {
        return $this->state(['is_active' => false, 'ban_reason' => $reason]);
    }

    public function admin(): static
    {
        return $this->state([
            'account_type_id' => AccountRole::Admin->value,
            'verification_status' => 'approved',
        ]);
    }

    /**
     * حساب شركة موافق عليها (الشركة نفسها تُنشأ عبر CompanyFactory)
     */
    public function company(): static
    {
        return $this->state([
            'account_type_id' => AccountRole::Company->value,
            'verification_status' => 'approved',
        ]);
    }
}
