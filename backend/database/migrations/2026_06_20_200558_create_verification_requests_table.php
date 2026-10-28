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
        Schema::create('verification_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('accounts');
            $table->enum('status', ['pending', 'approved', 'rejected'])
                ->default('pending');
            $table->unsignedTinyInteger('confidence_score')->nullable();
            $table->json('rejection_reasons')->nullable(); // هيك منحسن نخزن اكثر من سبب سوا 
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('verification_requests');
    }
};
