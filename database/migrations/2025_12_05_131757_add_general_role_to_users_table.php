<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. ENUM に 'general' を追加
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('viewer', 'editor', 'admin', 'general') NOT NULL DEFAULT 'general'");

        // 2. 既存の 'viewer' ユーザーを 'general' に移行
        DB::table('users')->where('role', 'viewer')->update(['role' => 'general']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. 'general' ユーザーを 'viewer' に戻す
        DB::table('users')->where('role', 'general')->update(['role' => 'viewer']);

        // 2. ENUM から 'general' を削除
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('viewer', 'editor', 'admin') NOT NULL DEFAULT 'viewer'");
    }
};
