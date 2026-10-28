<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // بيانات أساسية يحتاجها النظام ليعمل
        $this->call([
            AccountTypeSeeder::class,
            ReactTypeSeeder::class,
            MediaTypeSeeder::class,
            CategorySeeder::class,
            AdminSeeder::class,
            PlanSeeder::class,
        ]);

        // بيانات تجريبية للعرض — لا تُزرع في بيئة الإنتاج
        if (! app()->environment('production')) {
            $this->call(DemoSeeder::class);
            $this->call(PostCoverSeeder::class);
        }
    }
}
