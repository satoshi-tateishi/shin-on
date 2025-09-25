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
        Schema::table('locations', function (Blueprint $table) {
            $table->boolean('is_inventory_visible')->default(true)->after('is_active')
                  ->comment('在庫管理フィルタに表示するか');
            $table->boolean('is_transfer_visible')->default(true)->after('is_inventory_visible')
                  ->comment('倉庫間移動フィルタに表示するか');

            // インデックス追加
            $table->index(['is_inventory_visible', 'is_active'], 'locations_inventory_visible_active_index');
            $table->index(['is_transfer_visible', 'is_active'], 'locations_transfer_visible_active_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropIndex('locations_inventory_visible_active_index');
            $table->dropIndex('locations_transfer_visible_active_index');
            $table->dropColumn(['is_inventory_visible', 'is_transfer_visible']);
        });
    }
};
