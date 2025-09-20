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
        // MySQLでは直接ENUMを変更できないため、カラムを再作成
        Schema::table('performances', function (Blueprint $table) {
            $table->dropColumn('performance_type');
        });

        Schema::table('performances', function (Blueprint $table) {
            $table->enum('performance_type', [
                '演劇',
                'ミュージカル',
                'リーディング',
                'ダンス',
                'イベント',
                'コンサート',
                'その他',
            ])->after('title')->comment('公演種別');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('performances', function (Blueprint $table) {
            $table->dropColumn('performance_type');
        });

        Schema::table('performances', function (Blueprint $table) {
            $table->enum('performance_type', ['演劇', 'ミュージカル', 'コンサート', 'その他'])
                ->after('title')
                ->comment('公演種別');
        });
    }
};
