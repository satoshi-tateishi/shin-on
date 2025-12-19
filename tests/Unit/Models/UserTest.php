<?php

namespace Tests\Unit\Models;

use App\Models\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    /**
     * 表示名はユーザー名と同じ
     */
    public function test_display_name_returns_name(): void
    {
        $user = new User(['name' => '山田太郎']);

        $this->assertEquals('山田太郎', $user->display_name);
    }

    /**
     * 管理者ロールのラベルが正しい
     */
    public function test_role_label_for_admin(): void
    {
        $user = new User(['role' => 'admin']);

        $this->assertEquals('管理者', $user->role_label);
    }

    /**
     * 編集者ロールのラベルが正しい
     */
    public function test_role_label_for_editor(): void
    {
        $user = new User(['role' => 'editor']);

        $this->assertEquals('編集者', $user->role_label);
    }

    /**
     * 一般ロールのラベルが正しい
     */
    public function test_role_label_for_general(): void
    {
        $user = new User(['role' => 'general']);

        $this->assertEquals('一般', $user->role_label);
    }

    /**
     * 閲覧者ロールのラベルが正しい
     */
    public function test_role_label_for_viewer(): void
    {
        $user = new User(['role' => 'viewer']);

        $this->assertEquals('閲覧者', $user->role_label);
    }

    /**
     * 不明なロールのラベルは「不明」
     */
    public function test_role_label_for_unknown(): void
    {
        $user = new User(['role' => 'unknown']);

        $this->assertEquals('不明', $user->role_label);
    }

    /**
     * 社員の所属ラベルが正しい
     */
    public function test_affiliation_label_for_employee(): void
    {
        $user = new User(['affiliation' => 'employee']);

        $this->assertEquals('社員', $user->affiliation_label);
    }

    /**
     * パートナーの所属ラベルが正しい
     */
    public function test_affiliation_label_for_partner(): void
    {
        $user = new User(['affiliation' => 'partner']);

        $this->assertEquals('パートナー', $user->affiliation_label);
    }

    /**
     * 不明な所属のラベルは「不明」
     */
    public function test_affiliation_label_for_unknown(): void
    {
        $user = new User(['affiliation' => 'unknown']);

        $this->assertEquals('不明', $user->affiliation_label);
    }
}
