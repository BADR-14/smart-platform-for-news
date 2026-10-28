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
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_type_id')->constrained('account_types');

            $table->string('name', 255);
            $table->string('email', 255)->unique();
            $table->string('avatar', 500)->nullable();
            $table->string('fcm_token', 255)->nullable();

            $table->unsignedInteger('attemots_count')->default(0);

            $table->enum('verification_status', [
                'not_submitted', 'pending', 'approved', 'rejected',
            ])->default('not_submitted');

            $table->boolean('is_active')->default(true);
            $table->string('ban_reason')->nullable();

            $table->timestamp('email_verified_at')->nullable();
            $table->string('otp_code')->nullable();
            $table->timestamp('otp_expires_at')->nullable();

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();

            $table->index('account_type_id');
            $table->index('verification_status');
            $table->index('deleted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');

    }
};
