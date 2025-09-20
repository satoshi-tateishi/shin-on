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
        Schema::table('equipment_categories', function (Blueprint $table) {
            $table->index('is_active', 'idx_equipment_categories_is_active');
        });

        Schema::table('equipment_subcategories', function (Blueprint $table) {
            $table->index('is_active', 'idx_equipment_subcategories_is_active');
        });

        // パフォーマンス向上のための追加インデックス
        Schema::table('equipments', function (Blueprint $table) {
            $table->index(['status', 'is_discard'], 'idx_equipments_status_discard');
        });

        Schema::table('repair_records', function (Blueprint $table) {
            $table->index(['status', 'reported_at'], 'idx_repair_records_status_reported');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('equipment_categories', function (Blueprint $table) {
            $table->dropIndex('idx_equipment_categories_is_active');
        });

        Schema::table('equipment_subcategories', function (Blueprint $table) {
            $table->dropIndex('idx_equipment_subcategories_is_active');
        });

        Schema::table('equipments', function (Blueprint $table) {
            $table->dropIndex('idx_equipments_status_discard');
        });

        Schema::table('repair_records', function (Blueprint $table) {
            $table->dropIndex('idx_repair_records_status_reported');
        });
    }
};
