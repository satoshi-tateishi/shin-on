<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // MySQLで直接カラム順序を変更
        DB::statement('ALTER TABLE equipments MODIFY COLUMN now_location_id BIGINT UNSIGNED NULL AFTER location_id');
        DB::statement('ALTER TABLE equipments MODIFY COLUMN is_schedule_visible BOOLEAN NOT NULL DEFAULT 1 AFTER now_location_id');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 元の位置に戻す（is_schedule_visible を is_discard の後、now_location_id を最後）
        DB::statement('ALTER TABLE equipments MODIFY COLUMN is_schedule_visible BOOLEAN NOT NULL DEFAULT 1 AFTER is_discard');
        DB::statement('ALTER TABLE equipments MODIFY COLUMN now_location_id BIGINT UNSIGNED NULL AFTER updated_at');
    }
};
