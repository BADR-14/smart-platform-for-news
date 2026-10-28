<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('verification_requests', function (Blueprint $table) {
            // نوع الطلب: توثيق مستخدم أو توثيق شركة
            $table->enum('type', ['user', 'company'])->default('user')->after('account_id');
            $table->timestamp('reviewed_at')->nullable();
            $table->index(['status', 'type']);
        });

        Schema::table('verification_documents', function (Blueprint $table) {
            // النص المستخرج من الوثيقة قد يتجاوز 255 محرفاً
            $table->text('extracted_text')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('verification_requests', function (Blueprint $table) {
            $table->dropIndex(['status', 'type']);
            $table->dropColumn(['type', 'reviewed_at']);
        });

        Schema::table('verification_documents', function (Blueprint $table) {
            $table->string('extracted_text')->nullable()->change();
        });
    }
};
