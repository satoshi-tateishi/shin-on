<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Portal JWT 用 ID カラムを追加（JWT の sub クレーム = UUID）
            $table->string('external_auth_id', 100)->nullable()->after('id');
            $table->index('external_auth_id');

            // OTP 関連カラムを削除（lineworks_id は Bot PDF 送信機能で引き続き使用）
            $table->dropColumn([
                'two_factor_code',
                'two_factor_expires_at',
                'two_factor_locked_until',
                'two_factor_attempts',
            ]);
        });

        // OTP ログテーブルを削除
        Schema::dropIfExists('two_factor_logs');
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['external_auth_id']);
            $table->dropColumn('external_auth_id');

            $table->string('two_factor_code')->nullable();
            $table->timestamp('two_factor_expires_at')->nullable();
            $table->timestamp('two_factor_locked_until')->nullable();
            $table->unsignedTinyInteger('two_factor_attempts')->default(0);
        });
    }
};
