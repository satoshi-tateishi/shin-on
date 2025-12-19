<?php

namespace Tests\Unit\Models;

use App\Models\Equipment;
use PHPUnit\Framework\TestCase;

class EquipmentTest extends TestCase
{
    /**
     * 機材名のみの場合、表示名は機材名と同じ
     */
    public function test_display_name_returns_name_when_no_company_number(): void
    {
        $equipment = new Equipment([
            'name' => 'カメラA',
            'company_number' => null,
        ]);

        $this->assertEquals('カメラA', $equipment->display_name);
    }

    /**
     * 社番がある場合、表示名に社番が付与される
     */
    public function test_display_name_includes_company_number(): void
    {
        $equipment = new Equipment([
            'name' => 'カメラA',
            'company_number' => 'CAM-001',
        ]);

        $this->assertEquals('カメラA (CAM-001)', $equipment->display_name);
    }

    /**
     * 利用可能ステータスのラベルが正しい
     */
    public function test_status_label_for_available(): void
    {
        $equipment = new Equipment(['status' => 'available']);

        $this->assertEquals('利用可能', $equipment->status_label);
    }

    /**
     * 使用中ステータスのラベルが正しい
     */
    public function test_status_label_for_in_use(): void
    {
        $equipment = new Equipment(['status' => 'in_use']);

        $this->assertEquals('使用中', $equipment->status_label);
    }

    /**
     * 修理中ステータスのラベルが正しい
     */
    public function test_status_label_for_repair(): void
    {
        $equipment = new Equipment(['status' => 'repair']);

        $this->assertEquals('修理中', $equipment->status_label);
    }

    /**
     * 廃棄ステータスのラベルが正しい
     */
    public function test_status_label_for_retired(): void
    {
        $equipment = new Equipment(['status' => 'retired']);

        $this->assertEquals('廃棄', $equipment->status_label);
    }

    /**
     * 紛失ステータスのラベルが正しい
     */
    public function test_status_label_for_lost(): void
    {
        $equipment = new Equipment(['status' => 'lost']);

        $this->assertEquals('紛失', $equipment->status_label);
    }

    /**
     * 不明なステータスのラベルは「不明」
     */
    public function test_status_label_for_unknown_status(): void
    {
        $equipment = new Equipment(['status' => 'unknown_status']);

        $this->assertEquals('不明', $equipment->status_label);
    }

    /**
     * 個体管理のラベルが正しい
     */
    public function test_management_type_label_for_individual(): void
    {
        $equipment = new Equipment(['management_type' => 'individual']);

        $this->assertEquals('個体管理', $equipment->management_type_label);
    }

    /**
     * 数量管理のラベルが正しい
     */
    public function test_management_type_label_for_quantity(): void
    {
        $equipment = new Equipment(['management_type' => 'quantity']);

        $this->assertEquals('数量管理', $equipment->management_type_label);
    }

    /**
     * 不明な管理方式のラベルは「不明」
     */
    public function test_management_type_label_for_unknown_type(): void
    {
        $equipment = new Equipment(['management_type' => 'unknown']);

        $this->assertEquals('不明', $equipment->management_type_label);
    }
}
