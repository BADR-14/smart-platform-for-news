<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained('wallets');
            $table->foreignId('payment_id')->nullable()->constrained('payments');

            $table->enum('type', ['credit', 'debit', 'gift']);
            $table->enum('status', ['pending', 'completed', 'failed'])->default('pending');

            $table->decimal('amount', 15, 2);
            $table->text('description')->nullable();
            $table->uuid('uuid')->unique()->default(DB::raw('(UUID())'));
            $table->json('meta')->nullable();

            $table->string('currency', 10)->default('USD');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->index('wallet_id');
            $table->index('status');
            $table->index('payment_id');
            $table->index('uuid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
