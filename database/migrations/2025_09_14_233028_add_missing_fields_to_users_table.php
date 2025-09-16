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
            $table->integer('sort')->default(0)->comment('ソート順')->after('id');
            $table->string('postal_code', 8)->nullable()->comment('郵便番号')->after('address');
            $table->date('hired_at')->nullable()->comment('入社日')->after('address');
            $table->date('resigned_at')->nullable()->comment('退職日')->after('hired_at');
            $table->date('birthday')->nullable()->comment('生年月日')->after('address');
            $table->boolean('is_designer')->default(false)->comment('サウンドデザイナー選択に表示するか')->after('is_retired');
            $table->boolean('is_staff')->default(false)->comment('公演担当者選択に表示するか（担当者フラグ）')->after('is_designer');
            $table->boolean('is_driver')->default(false)->comment('ドライバーフラグ')->after('is_staff');
            $table->boolean('is_on_leave')->default(false)->comment('休職フラグ')->after('is_driver');
            $table->boolean('is_resigned')->default(false)->comment('退職フラグ')->after('is_on_leave');

            // 既存フィールドの削除
            $table->dropColumn(['hire_date', 'birth_date']);

            // インデックスの追加
            $table->index('sort');
            $table->index('is_designer');
            $table->index('is_staff');
            $table->index('is_resigned');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // インデックスの削除
            $table->dropIndex(['sort']);
            $table->dropIndex(['is_designer']);
            $table->dropIndex(['is_staff']);
            $table->dropIndex(['is_resigned']);

            // カラムの削除
            $table->dropColumn([
                'sort',
                'postal_code',
                'hired_at',
                'resigned_at',
                'birthday',
                'is_designer',
                'is_staff',
                'is_driver',
                'is_on_leave',
                'is_resigned',
            ]);

            // 既存フィールドの復元
            $table->date('hire_date')->nullable()->after('is_active');
            $table->date('birth_date')->nullable()->after('hire_date');
        });
    }
};
