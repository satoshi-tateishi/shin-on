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
        Schema::create('performances', function (Blueprint $table) {
            $table->id();
            $table->string('title')->comment('公演タイトル');
            $table->string('short_name', 50)->nullable()->comment('公演略称');
            $table->enum('performance_type', [
                '演劇',
                'ミュージカル',
                'リーディング',
                'ダンス',
                'イベント',
                'コンサート',
                'その他',
            ])->comment('公演種別');
            $table->string('director')->nullable()->comment('演出');
            $table->enum('status', ['planning', 'preparation', 'in_progress', 'completed', 'cancelled'])
                ->default('planning')
                ->comment('ステータス');
            $table->text('note')->nullable()->comment('備考');
            $table->boolean('is_active')->default(true)->comment('有効フラグ');
            $table->timestamps();

            // インデックス
            $table->index('performance_type', 'idx_performance_type');
            $table->index('status', 'idx_status');
            $table->index('is_active', 'idx_is_active');
            $table->index('title', 'idx_title');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('performances');
    }
};
