<?php

namespace Tests\Unit\Models;

use App\Models\EquipmentSet;
use PHPUnit\Framework\TestCase;

class EquipmentSetTest extends TestCase
{
    /**
     * 表示名はセット名と同じ
     */
    public function test_display_name_returns_name(): void
    {
        $set = new EquipmentSet(['name' => '照明基本セット']);

        $this->assertEquals('照明基本セット', $set->display_name);
    }

    /**
     * 有効なセットのステータスラベルが「有効」
     */
    public function test_status_label_for_active_set(): void
    {
        $set = new EquipmentSet(['is_active' => true]);

        $this->assertEquals('有効', $set->status_label);
    }

    /**
     * 無効なセットのステータスラベルが「無効」
     */
    public function test_status_label_for_inactive_set(): void
    {
        $set = new EquipmentSet(['is_active' => false]);

        $this->assertEquals('無効', $set->status_label);
    }
}
