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
        Schema::create('phase_equipment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('phase_id')->constrained('phases')->onDelete('cascade')->comment('フェーズID');
            $table->foreignId('equipment_id')->constrained('equipments')->onDelete('cascade')->comment('機材ID');
            $table->integer('quantity')->default(1)->comment('使用数量');
            $table->datetime('checkout_date')->nullable()->comment('出庫日');
            $table->date('checkin_date')->nullable()->comment('返却日');
            $table->foreignId('checkout_user_id')->nullable()->constrained('users')->onDelete('set null')->comment('貸出者ID');
            $table->foreignId('checkin_user_id')->nullable()->constrained('users')->onDelete('set null')->comment('返却者ID');
            $table->enum('status', ['reserved', 'checked_out', 'checked_in', 'cancelled'])->default('reserved')->comment('ステータス');
            $table->text('note')->nullable()->comment('備考');
            $table->timestamps();

            // 期間重複チェック用の重要なインデックス
            $table->index(['equipment_id', 'phase_id'], 'idx_equipment_phase');
            $table->index('phase_id', 'idx_phase_id');
            $table->index('status', 'idx_status');
            $table->index('checkout_date', 'idx_checkout_date');
            $table->index('checkin_date', 'idx_checkin_date');
        });

        // 数量チェック（正の値のみ）を追加
        DB::statement('ALTER TABLE phase_equipment ADD CONSTRAINT chk_phase_equipment_quantity CHECK (quantity > 0)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phase_equipment');
    }
};