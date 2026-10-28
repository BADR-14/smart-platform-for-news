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
        Schema::create('auth_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();

            $table->enum('provider', ['local', 'google', 'facebook']);
            $table->string('provider_identifier', 255)->nullable()->index();
            $table->string('password_hash', 255)->nullable();

            $table->timestamp('created_at')->useCurrent();
            $table->softDeletes();

            $table->unique(['account_id', 'provider']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auth_credentials');
    }
};
