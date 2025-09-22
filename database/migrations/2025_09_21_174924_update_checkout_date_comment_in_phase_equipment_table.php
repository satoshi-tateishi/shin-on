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
        DB::statement("ALTER TABLE phase_equipment MODIFY COLUMN checkout_date datetime NULL COMMENT '出庫日'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE phase_equipment MODIFY COLUMN checkout_date datetime NULL COMMENT '貸出日'");
    }
};
