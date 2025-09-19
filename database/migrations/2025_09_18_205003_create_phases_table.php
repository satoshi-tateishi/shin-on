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
        Schema::create('phases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('performance_id')->constrained()->onDelete('cascade')->comment('公演ID');
            $table->foreignId('location_id')->nullable()->constrained()->onDelete('set null')->comment('実施場所ID');
            $table->integer('sort')->default(0)->comment('ソート順（フェーズの順序）');
            $table->string('name')->comment('フェーズ名');
            $table->date('start_date')->comment('開始日');
            $table->date('end_date')->comment('終了日');
            $table->time('start_time')->nullable()->comment('開始時間');
            $table->time('end_time')->nullable()->comment('終了時間');
            $table->text('description')->nullable()->comment('説明');
            $table->text('note')->nullable()->comment('備考');
            $table->boolean('is_active')->default(true)->comment('有効フラグ');
            $table->timestamps();

            // インデックス（期間重複チェック用の重要なインデックス）
            $table->index(['start_date', 'end_date'], 'idx_date_range');
            $table->index('performance_id', 'idx_performance_id');
            $table->index('location_id', 'idx_location_id');
            $table->index('sort', 'idx_sort');
            $table->index('is_active', 'idx_is_active');

            // 日付の整合性チェック（MySQL レベルで実装予定）
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phases');
    }
};
