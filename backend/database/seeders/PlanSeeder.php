<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * سعر المنشور المميز المفرد الافتراضي 10$، والباقات أرخص للمنشور الواحد
     */
    public function run(): void
    {
        $plans = [
            ['name' => 'الباقة الأساسية', 'posts_count' => 5, 'price' => 40.00],
            ['name' => 'الباقة المتقدمة', 'posts_count' => 15, 'price' => 105.00],
            ['name' => 'الباقة الاحترافية', 'posts_count' => 40, 'price' => 240.00],
        ];

        foreach ($plans as $plan) {
            Plan::firstOrCreate(['name' => $plan['name']], [...$plan, 'is_active' => true]);
        }
    }
}
