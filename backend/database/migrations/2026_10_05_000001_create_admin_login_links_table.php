<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * روابط الدخول السحرية للأدمن: كل رابط يُستعمل مرة واحدة وينتهي بعد دقائق.
     * نخزّن بصمة التوكن (hash) فقط، فتسريب الجدول لا يكشف رابطاً صالحاً.
     */
    public function up(): void
    {
        Schema::create('admin_login_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->string('requested_ip', 45)->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_login_links');
    }
};
