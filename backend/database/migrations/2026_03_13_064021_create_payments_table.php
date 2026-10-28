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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');

            $table->string('gateway_name', 50);
            $table->string('gateway_payment_id', 255)->nullable();

            $table->decimal('amount', 15, 2);
            $table->string('currency', 10)->default('USD');

            // pending, completed, failed, refunded
            $table->string('status', 20)->default('pending');

            $table->string('receipt_url', 500)->nullable();
            $table->json('payload')->nullable();

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->index('company_id');
            $table->index('gateway_name');
            $table->index('gateway_payment_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
