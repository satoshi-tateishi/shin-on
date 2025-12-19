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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->integer('sort')->default(0)->comment('ソート順');
            $table->string('name')->comment('氏名');
            $table->string('furigana')->nullable()->comment('ふりがな');
            $table->string('email')->unique()->comment('メールアドレス');
            $table->string('lineworks_id')->nullable()->unique()->comment('LINE WORKS SSO用ID');
            $table->string('icon')->nullable()->comment('アイコンURL');
            $table->string('mobile_phone', 20)->nullable()->comment('携帯電話');
            $table->text('address')->nullable()->comment('住所');
            $table->string('emergency_contact_name')->nullable()->comment('緊急連絡先氏名');
            $table->string('emergency_contact_phone', 20)->nullable()->comment('緊急連絡先電話番号');
            $table->text('notes')->nullable()->comment('備考');
            $table->string('postal_code', 8)->nullable()->comment('郵便番号');
            $table->date('birthday')->nullable()->comment('生年月日');
            $table->date('hired_at')->nullable()->comment('入社日');
            $table->date('resigned_at')->nullable()->comment('退職日');
            $table->boolean('is_active')->default(true)->comment('有効フラグ');
            $table->enum('role', ['viewer', 'editor', 'admin'])->default('viewer')->comment('ユーザーロール');
            $table->enum('affiliation', ['employee', 'partner'])->default('employee')->comment('所属');
            $table->boolean('is_designer')->default(false)->comment('サウンドデザイナー選択に表示するか');
            $table->boolean('is_staff')->default(false)->comment('公演担当者選択に表示するか（担当者フラグ）');
            $table->boolean('is_driver')->default(false)->comment('ドライバーフラグ');
            $table->boolean('is_on_leave')->default(false)->comment('休職フラグ');
            $table->boolean('is_resigned')->default(false)->comment('退職フラグ');
            $table->timestamps();

            // インデックス
            $table->index('sort');
            $table->index('is_active');
            $table->index('lineworks_id');
            $table->index('role');
            $table->index('affiliation');
            $table->index('is_designer');
            $table->index('is_staff');
            $table->index('is_resigned');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
