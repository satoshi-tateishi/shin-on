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
        Schema::create('schedule_cell_memos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('equipment_id')->comment('機材ID');
            $table->date('schedule_date')->comment('スケジュール日付');
            $table->text('memo')->nullable()->comment('メモ内容');
            $table->string('color', 20)->nullable()->comment('背景色（HEXコード）');
            $table->unsignedBigInteger('created_by')->comment('作成者ユーザーID');
            $table->unsignedBigInteger('updated_by')->nullable()->comment('更新者ユーザーID');
            $table->timestamps();

            // 複合ユニークキー（機材ID + 日付で一意）
            $table->unique(['equipment_id', 'schedule_date'], 'equipment_date_unique');

            // 外部キー制約
            $table->foreign('equipment_id')->references('id')->on('equipments')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('cascade');

            // インデックス
            $table->index('schedule_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedule_cell_memos');
    }
};
