<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();

            $table->foreignId('reporter_account_id') // الحساب صاحب الابلاغ
                ->constrained('accounts')
                ->onDelete('cascade');

            $table->enum('reportable_type', ['post', 'account']); // نوع الابلاغ على منشور او على حساب
            $table->unsignedBigInteger('reportable_id'); // ال id للمنشور او الحساب المبلغ عنو
            $table->string('reason');
            $table->enum('status', ['pending', 'resolved', 'dismissed'])->default('pending');
            $table->foreignId('resolved_by')// الادمن اللي عالج الابلاغ
                ->nullable()
                ->constrained('accounts')
                ->onDelete('set null');
            $table->string('admin_notes')->nullable();

            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['reportable_type', 'reportable_id']); // فهرس لتسريع البحث لأنو الاستعلام رح يكون على الحقلين مع بعض دائماً
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
