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
        Schema::create('performance_staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('performance_id')->constrained()->onDelete('cascade')->comment('公演ID');
            $table->foreignId('user_id')->constrained()->onDelete('cascade')->comment('ユーザーID');
            $table->foreignId('position_id')->constrained()->onDelete('restrict')->comment('ポジションID');
            $table->foreignId('production_id')->nullable()->constrained()->onDelete('set null')->comment('プロダクションID');
            $table->text('note')->nullable()->comment('備考');
            $table->timestamps();

            // 同一公演での重複登録防止
            $table->unique(['performance_id', 'user_id', 'position_id'], 'unique_performance_user_position');

            // インデックス
            $table->index('performance_id', 'idx_performance_id');
            $table->index('user_id', 'idx_user_id');
            $table->index('position_id', 'idx_position_id');
            $table->index('production_id', 'idx_production_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('performance_staff');
    }
};
