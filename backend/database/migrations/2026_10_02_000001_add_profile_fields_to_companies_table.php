<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // اسم الشركة يُدخل عند تقديم الطلب، ويصبح اسم الحساب بعد الموافقة
            $table->string('name', 255)->nullable()->after('account_id');
            $table->timestamp('last_active_at')->nullable();
            $table->string('website_url', 255)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['name', 'last_active_at']);
        });
    }
};
