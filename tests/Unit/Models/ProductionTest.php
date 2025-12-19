<?php

namespace Tests\Unit\Models;

use App\Models\Production;
use PHPUnit\Framework\TestCase;

class ProductionTest extends TestCase
{
    /**
     * 表示名はタイプと名前の組み合わせ
     */
    public function test_display_name_includes_type_and_name(): void
    {
        $production = new Production([
            'type' => '劇団',
            'name' => 'テスト劇団',
        ]);

        $this->assertEquals('劇団 テスト劇団', $production->display_name);
    }

    /**
     * 有効なプロダクションのステータスラベルが「有効」
     */
    public function test_status_label_for_active_production(): void
    {
        $production = new Production(['is_active' => true]);

        $this->assertEquals('有効', $production->status_label);
    }

    /**
     * 無効なプロダクションのステータスラベルが「無効」
     */
    public function test_status_label_for_inactive_production(): void
    {
        $production = new Production(['is_active' => false]);

        $this->assertEquals('無効', $production->status_label);
    }
}
