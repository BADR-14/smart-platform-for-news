<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('featured_posts', function (Blueprint $table) {
            // طريقة الدفع: من رصيد المحفظة مباشرة أو من رصيد الباقة
            $table->enum('payment_method', ['wallet', 'plan'])->default('wallet')->after('cost');
            $table->foreignId('company_plan_id')->nullable()->after('payment_method')
                ->constrained('company_plans')->nullOnDelete();
            $table->index(['status', 'ends_at']);
        });

        Schema::table('company_plans', function (Blueprint $table) {
            // القيد القديم كان يمنع الشركة من امتلاك أكثر من باقة منتهية واحدة.
            // فهرس عادي على company_id يُضاف أولاً لأن الـ FK يعتمد على الفهرس المحذوف.
            $table->index('company_id');
            $table->dropUnique(['company_id', 'status']);
            $table->index(['company_id', 'status']);
        });

        Schema::table('plans', function (Blueprint $table) {
            // سعر المنشور المميز المفرد أصبح إعداداً عاماً يحدده الأدمن (platform_settings)
            $table->dropColumn('price_per_post');
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->decimal('price_per_post', 10, 2)->default(0);
        });

        Schema::table('company_plans', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'status']);
            $table->unique(['company_id', 'status']);
            $table->dropIndex(['company_id']);
        });

        Schema::table('featured_posts', function (Blueprint $table) {
            $table->dropIndex(['status', 'ends_at']);
            $table->dropConstrainedForeignId('company_plan_id');
            $table->dropColumn('payment_method');
        });
    }
};
