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
        Schema::table('phases', function (Blueprint $table) {
            // 不要なカラムを削除
            $table->dropColumn(['start_time', 'end_time', 'description']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('phases', function (Blueprint $table) {
            // 削除したカラムを復元
            $table->time('start_time')->nullable()->comment('開始時間');
            $table->time('end_time')->nullable()->comment('終了時間');
            $table->text('description')->nullable()->comment('説明');
        });
    }
};
