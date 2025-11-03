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
        Schema::table('users', function (Blueprint $table) {
            $table->string('two_factor_code', 255)->nullable()->after('icon')->comment('2FA認証コード（ハッシュ化）');
            $table->timestamp('two_factor_expires_at')->nullable()->after('two_factor_code')->comment('2FAコード有効期限');
            $table->timestamp('two_factor_locked_until')->nullable()->after('two_factor_expires_at')->comment('アカウントロック解除時刻');
            $table->unsignedTinyInteger('two_factor_attempts')->default(0)->after('two_factor_locked_until')->comment('2FA失敗試行回数');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'two_factor_code',
                'two_factor_expires_at',
                'two_factor_locked_until',
                'two_factor_attempts',
            ]);
        });
    }
};
