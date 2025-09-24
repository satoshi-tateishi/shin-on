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
        Schema::table('equipments', function (Blueprint $table) {
            // 現在地を管理するカラムを追加
            $table->foreignId('now_location_id')
                  ->nullable()
                  ->constrained('locations')
                  ->onDelete('set null')
                  ->comment('現在地ID（一時的な場所）')
                  ->after('location_id');

            // インデックス追加
            $table->index('now_location_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('equipments', function (Blueprint $table) {
            $table->dropForeign(['now_location_id']);
            $table->dropIndex(['now_location_id']);
            $table->dropColumn('now_location_id');
        });
    }
};
