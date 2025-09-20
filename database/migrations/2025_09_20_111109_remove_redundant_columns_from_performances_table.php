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
        Schema::table('performances', function (Blueprint $table) {
            // 重複・不要カラムを削除（正規化のため）
            // subtitle, producer, budget は削除
            $table->dropColumn(['subtitle', 'producer', 'budget']);

            // 公演期間・会場は phases テーブルで管理するため削除
            $table->dropIndex('idx_start_date'); // インデックスを先に削除
            $table->dropColumn(['start_date', 'end_date', 'venue']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('performances', function (Blueprint $table) {
            // 削除したカラムを復元
            $table->string('subtitle')->nullable()->comment('サブタイトル');
            $table->string('producer')->nullable()->comment('プロデューサー');
            $table->decimal('budget', 12, 2)->nullable()->comment('予算');
            $table->date('start_date')->nullable()->comment('公演開始日');
            $table->date('end_date')->nullable()->comment('公演終了日');
            $table->string('venue')->nullable()->comment('会場名');

            // インデックスを復元
            $table->index('start_date', 'idx_start_date');
        });
    }
};
