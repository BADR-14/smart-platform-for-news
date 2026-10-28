<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // إعادة النشر أصبحت عبر post_shares فقط (مع نص اختياري وتظهر في الصفحة الرئيسية)
        Schema::dropIfExists('reposts');
    }

    public function down(): void
    {
        Schema::create('reposts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['post_id', 'account_id']);
        });
    }
};
