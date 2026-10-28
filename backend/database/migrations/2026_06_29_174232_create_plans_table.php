<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();

            // اسم الباقة المعروض للشركات (مثلاً: "باقة 10 بوستات")
            $table->string('name');

            // عدد البوستات المميزة المشمولة بهاد الباقة
            $table->unsignedInteger('posts_count');

            // سعر الباقة كاملة (مخفض عن سعر البوست الفردي)
            $table->decimal('price', 10, 2);

            // سعر البوست المميز الواحد بدون باقة (من المحفظة مباشرة)
            // ثابت لكل الشركات، يحدده الأدمن
            $table->decimal('price_per_post', 10, 2);

            // هل الباقة متاحة للشراء حالياً أم لا (يتحكم فيها الأدمن)
            $table->boolean('is_active')->default(true);

            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
