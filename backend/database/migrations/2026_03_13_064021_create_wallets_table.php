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
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->unique()->constrained('companies');

            $table->string('name', 100);
            $table->string('slug', 100)->unique()->nullable();
            $table->uuid('uuid')->unique()->default(DB::raw('(UUID())'));
            $table->text('description')->nullable();

            $table->decimal('balance', 15, 2)->default(0.00);
            $table->string('currency', 10)->default('USD');

            $table->boolean('is_active')->default(true);

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->index('company_id');
            $table->index('slug');
            $table->index('uuid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
