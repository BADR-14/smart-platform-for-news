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
        Schema::create('post_shares', function (Blueprint $table) {
            $table->id();

            // ✅ تم تصحيح السلسلة وحذف التكرار والفاصلة المنقوطة المبكرة
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();

            // ✅ تم تغيير foreign إلى foreignId وتصحيح الربط بجداول الـ posts
            $table->foreignId('post_id')->constrained('posts')->cascadeOnDelete();

            $table->text('share_text')->nullable();
            $table->timestamp('created_at');

            $table->unique(['post_id', 'account_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('post_shares');
    }
};
