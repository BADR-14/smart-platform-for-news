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
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('accounts');
            $table->foreignId('post_id')->constrained('posts');
            $table->foreignId('media_type_id')->constrained('media_types');

            $table->string('media_url', 500);

            $table->timestamp('created_at')->useCurrent();
            $table->softDeletes();

            $table->index('account_id');
            $table->index('post_id');
            $table->index('media_type_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
