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
        Schema::create('equipment_set_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_set_id')->constrained('equipment_sets')->onDelete('cascade')->comment('機材セットID');
            $table->foreignId('equipment_id')->constrained('equipments')->onDelete('cascade')->comment('機材ID');
            $table->integer('quantity')->default(1)->comment('必要数量');
            $table->integer('sort_order')->default(0)->comment('セット内順序');
            $table->boolean('is_required')->default(true)->comment('必須機材フラグ');
            $table->text('notes')->nullable()->comment('備考');
            $table->timestamps();

            // インデックス
            $table->index('equipment_set_id');
            $table->index('equipment_id');
            $table->index('sort_order');

            // ユニーク制約（同じセット内で同じ機材は重複不可）
            $table->unique(['equipment_set_id', 'equipment_id'], 'unique_set_equipment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipment_set_items');
    }
};
