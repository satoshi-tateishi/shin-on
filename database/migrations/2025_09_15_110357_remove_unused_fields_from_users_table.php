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
            // 不要なカラムを削除
            $table->dropColumn([
                'email_verified_at',
                'password',
                'phone',
                'department',
                'position',
                'is_planner',
                'is_manager',
                'is_retired',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // カラムを復元（必要に応じて）
            $table->timestamp('email_verified_at')->nullable()->after('email');
            $table->string('password')->nullable()->after('email_verified_at');
            $table->string('phone', 20)->nullable()->after('mobile_phone');
            $table->string('department')->nullable()->after('position');
            $table->string('position')->nullable()->after('department');
            $table->boolean('is_planner')->default(false)->after('is_retired');
            $table->boolean('is_manager')->default(false)->after('is_planner');
            $table->boolean('is_retired')->default(false)->after('is_resigned');
        });
    }
};
