<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_plans', function (Blueprint $table) {
            $table->id();

            // الشركة يلي اشترت الباقة
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();

            // الباقة يلي اشترتها الشركة
            $table->foreignId('plan_id')->constrained('plans')->cascadeOnDelete();

            // عدد البوستات المميزة المتبقية من الباقة
            // بينقص بـ 1 كل ما نشرت الشركة بوست مميز
            $table->unsignedInteger('posts_remaining');

            // حالة الباقة:
            // active  → الباقة نشطة وفيها بوستات متبقية
            // expired → انتهت البوستات أو ألغيت الباقة
            $table->enum('status', ['active', 'expired'])->default('active');

            // السعر يلي دفعته الشركة وقت الشراء
            // نخزنه هون لأن سعر الباقة ممكن يتغير مستقبلاً
            // وبدنا نحتفظ بالسعر الأصلي وقت الشراء للمرجعية
            $table->decimal('price_paid', 10, 2);

            $table->timestamp('created_at')->useCurrent();

            // ضمان إنه ما يكون عند الشركة أكثر من باقة نشطة بنفس الوقت
            $table->unique(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_plans');
    }
};
