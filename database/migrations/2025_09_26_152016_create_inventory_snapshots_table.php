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
            $table->string('equipment_name')->comment('機材名（グループ化キー）');
            $table->text('company_numbers')->nullable()->comment('新音番号（カンマ区切り）');
            $table->string('category_name')->nullable()->comment('カテゴリ名');
            $table->string('subcategory_name')->nullable()->comment('サブカテゴリ名');
            $table->foreignId('sample_equipment_id')->constrained('equipments')->onDelete('cascade')->comment('サンプル機材ID（参考用）');
            $table->foreignId('location_id')->nullable()->constrained('locations')->onDelete('set null')->comment('場所ID');
            $table->integer('quantity')->comment('在庫数量');
            $table->timestamps();

            // インデックス
            $table->index('snapshot_date', 'idx_snapshot_date');
            $table->index(['snapshot_date', 'equipment_name'], 'idx_snapshot_equipment_name');
            $table->index(['snapshot_date', 'location_id'], 'idx_snapshot_location');

            // ユニーク制約（機材名 + 倉庫でグループ化）
            $table->unique(['snapshot_date', 'equipment_name', 'location_id'], 'uk_snapshot_name_location');
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