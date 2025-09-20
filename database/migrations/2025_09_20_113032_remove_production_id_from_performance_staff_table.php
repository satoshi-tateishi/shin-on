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
        Schema::table('performance_staff', function (Blueprint $table) {
            // 外部キー制約を先に削除
            $table->dropForeign(['production_id']);
            // インデックスを削除
            $table->dropIndex('idx_production_id');
            // カラムを削除
            $table->dropColumn('production_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('performance_staff', function (Blueprint $table) {
            // 削除したカラムを復元
            $table->foreignId('production_id')->nullable()->constrained()->onDelete('set null')->comment('プロダクションID');
            $table->index('production_id', 'idx_production_id');
        });
    }
};
