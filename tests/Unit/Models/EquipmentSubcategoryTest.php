<?php

namespace Tests\Unit\Models;

use App\Models\EquipmentSubcategory;
use PHPUnit\Framework\TestCase;

class EquipmentSubcategoryTest extends TestCase
{
    /**
     * カテゴリ未ロード時は名前とIDを表示
     */
    public function test_display_name_shows_name_and_id_when_category_not_loaded(): void
    {
        $subcategory = new EquipmentSubcategory([
            'name' => 'ムービングライト',
            'category_id' => 1,
        ]);

        $this->assertEquals('ムービングライト (ID: 1)', $subcategory->display_name);
    }
}
