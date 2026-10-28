<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            // nullable لأن الزوار بدون حساب ممكن يشوفوا
            $table->foreignId('account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->string('ip_address', 45)->nullable(); // للزوار
            $table->timestamps();

            // نمنع العد المتكرر لنفس المستخدم على نفس المنشور
            $table->unique(['post_id', 'account_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_views');
    }
};
