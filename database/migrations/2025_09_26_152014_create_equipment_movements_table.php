<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('equipment_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_id')->constrained('equipments')->onDelete('cascade')->comment('機材ID');
            $table->enum('movement_type', ['checkout', 'checkin', 'transfer', 'maintenance', 'disposal', 'repair_start', 'repair_complete'])->comment('移動タイプ');
            $table->foreignId('phase_id')->nullable()->constrained('phases')->onDelete('set null')->comment('フェーズID（使用時）');
            $table->foreignId('from_location_id')->nullable()->constrained('locations')->onDelete('set null')->comment('移動元場所ID');
            $table->foreignId('to_location_id')->nullable()->constrained('locations')->onDelete('set null')->comment('移動先場所ID');
            $table->integer('quantity')->default(1)->comment('移動数量');
            $table->foreignId('moved_by')->nullable()->constrained('users')->onDelete('set null')->comment('実行者ID');
            $table->timestamp('moved_at')->useCurrent()->comment('移動日時');
            $table->text('note')->nullable()->comment('備考');
            $table->timestamp('created_at')->nullable();

            // 在庫計算用の重要なインデックス
            $table->index(['equipment_id', 'moved_at'], 'idx_equipment_moved_at');
            $table->index('movement_type', 'idx_movement_type');
            $table->index('phase_id', 'idx_phase_id');
            $table->index('moved_at', 'idx_moved_at');
        });

        // 数量チェック（正の値のみ）を追加
        DB::statement('ALTER TABLE equipment_movements ADD CONSTRAINT chk_equipment_movements_quantity CHECK (quantity > 0)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipment_movements');
    }
};