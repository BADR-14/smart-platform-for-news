<?php

namespace Database\Seeders;

use App\Enums\Account\AccountRole;
use App\Models\Account;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * بيانات الأدمن تُؤخذ من ملف .env (ADMIN_EMAIL / ADMIN_PASSWORD).
     * القيم الافتراضية للتطوير المحلي فقط ويجب تغييرها عند النشر.
     */
    public function run(): void
    {
        $password = env('ADMIN_PASSWORD');
        if (app()->isProduction() && (empty($password) || $password === 'change-me')) {
            throw new RuntimeException('Set a real ADMIN_PASSWORD in .env before seeding.');
}
        $account = Account::firstOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@system.com')],
            [
                'account_type_id' => AccountRole::Admin->value,
                'name' => 'مدير النظام',
                'verification_status' => 'approved',
                'email_verified_at' => now(),
            ],
        );

        $account->authCredentials()->firstOrCreate(
            ['provider' => 'local'],
            ['password_hash' => Hash::make(env('ADMIN_PASSWORD', 'admin1234'))],
        );

        // الملف الشخصي مطلوب لأن reviewed_by في طلبات التوثيق يشير إلى جدول users
        $account->user()->firstOrCreate([], ['system_role' => 'admin']);
    }
}
