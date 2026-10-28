<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            // ملاحظة الأدمن عند وضع المنشور قيد المراجعة أو رفضه
            $table->string('moderation_note', 500)->nullable()->after('status');
        });

        Schema::table('reports', function (Blueprint $table) {
            // الإجراء الذي اتخذه الأدمن عند معالجة البلاغ
            $table->string('action_taken', 30)->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn('moderation_note');
        });

        Schema::table('reports', function (Blueprint $table) {
            $table->dropColumn('action_taken');
        });
    }
};
