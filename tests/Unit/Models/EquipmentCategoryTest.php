<?php

namespace Tests\Unit\Models;

use App\Models\EquipmentCategory;
use PHPUnit\Framework\TestCase;

class EquipmentCategoryTest extends TestCase
{
    /**
     * 表示名はカテゴリ名と同じ
     */
    public function test_display_name_returns_name(): void
    {
        $category = new EquipmentCategory(['name' => '照明機材']);

        $this->assertEquals('照明機材', $category->display_name);
    }
}
