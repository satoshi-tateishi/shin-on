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
        Schema::create('inventory_snapshots', function (Blueprint $table) {
            $table->id();
            $table->date('snapshot_date')->comment('スナップショット日付');
            $table->foreignId('equipment_id')->constrained('equipments')->onDelete('cascade')->comment('機材ID');
            $table->foreignId('location_id')->nullable()->constrained('locations')->onDelete('set null')->comment('場所ID');
            $table->integer('quantity')->comment('在庫数量');
            $table->integer('total_quantity')->comment('総数量');
            $table->timestamps();

            // インデックス
            $table->index('snapshot_date', 'idx_snapshot_date');
            $table->index(['equipment_id', 'snapshot_date'], 'idx_equipment_snapshot');
            $table->index(['location_id', 'snapshot_date'], 'idx_location_snapshot');

            // ユニーク制約
            $table->unique(['snapshot_date', 'equipment_id', 'location_id'], 'uk_snapshot_equipment_location');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_snapshots');
    }
};
