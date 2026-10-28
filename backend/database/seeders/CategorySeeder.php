<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Tag;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'تقنية المعلومات', 'type' => 'both'],
            ['name' => 'الذكاء الاصطناعي', 'type' => 'both'],
            ['name' => 'ريادة الأعمال', 'type' => 'both'],
            ['name' => 'التسويق', 'type' => 'both'],
            ['name' => 'التصميم', 'type' => 'both'],
            ['name' => 'الهندسة', 'type' => 'both'],
            ['name' => 'الصحة', 'type' => 'both'],
            ['name' => 'التعليم والتدريب', 'type' => 'both'],
            ['name' => 'المال والأعمال', 'type' => 'both'],
            ['name' => 'تطوير الذات', 'type' => 'post'],
            ['name' => 'خدمات واستشارات', 'type' => 'company'],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(['name' => $category['name']], $category);
        }

        $tags = [
            'برمجة', 'فلاتر', 'لارافيل', 'رياكت', 'بايثون', 'أمن المعلومات', 'علم البيانات',
            'تعلم الآلة', 'شركات ناشئة', 'تسويق رقمي', 'تصميم واجهات', 'إدارة مشاريع',
            'ورشة عمل', 'مجاني', 'شهادة', 'للمبتدئين',
        ];

        foreach ($tags as $tag) {
            Tag::firstOrCreate(['name' => $tag]);
        }
    }
}
