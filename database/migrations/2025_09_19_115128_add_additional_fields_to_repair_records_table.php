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
        Schema::table('repair_records', function (Blueprint $table) {
            $table->datetime('failure_occurred_at')->nullable()->comment('故障発生日時')->after('equipment_id');
            $table->foreignId('staff_user_id')->constrained('users')->onDelete('restrict')->comment('担当者ID')->after('failure_occurred_at');
            $table->string('performance_name')->nullable()->comment('公演名')->after('staff_user_id');
            $table->string('usage_location')->nullable()->comment('使用場所')->after('performance_name');
            $table->json('photos')->nullable()->comment('故障箇所写真パス配列')->after('usage_location');

            // インデックス追加
            $table->index('failure_occurred_at', 'idx_failure_occurred_at');
            $table->index('staff_user_id', 'idx_staff_user_id');
            $table->index('performance_name', 'idx_performance_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('repair_records', function (Blueprint $table) {
            // インデックス削除
            $table->dropIndex('idx_failure_occurred_at');
            $table->dropIndex('idx_staff_user_id');
            $table->dropIndex('idx_performance_name');

            // カラム削除
            $table->dropForeign(['staff_user_id']);
            $table->dropColumn([
                'failure_occurred_at',
                'staff_user_id',
                'performance_name',
                'usage_location',
                'photos'
            ]);
        });
    }
};
