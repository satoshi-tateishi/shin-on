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
        Schema::create('performance_production', function (Blueprint $table) {
            $table->id();
            $table->foreignId('performance_id')->constrained()->onDelete('cascade')->comment('公演ID');
            $table->foreignId('production_id')->constrained()->onDelete('cascade')->comment('プロダクションID');
            $table->timestamps();

            // 同一組み合わせの重複防止
            $table->unique(['performance_id', 'production_id'], 'unique_performance_production');

            // インデックス
            $table->index('performance_id', 'idx_performance_id');
            $table->index('production_id', 'idx_production_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('performance_production');
    }
};