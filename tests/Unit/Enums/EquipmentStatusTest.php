<?php

namespace Tests\Unit\Enums;

use App\Enums\EquipmentStatus;
use PHPUnit\Framework\TestCase;

class EquipmentStatusTest extends TestCase
{
    /**
     * 予約済みステータスのラベルが正しいことを確認
     */
    public function test_reserved_status_has_correct_label(): void
    {
        $status = EquipmentStatus::RESERVED;

        $this->assertEquals('予約済み', $status->label());
    }

    /**
     * 出庫中ステータスのラベルが正しいことを確認
     */
    public function test_checked_out_status_has_correct_label(): void
    {
        $status = EquipmentStatus::CHECKED_OUT;

        $this->assertEquals('出庫中', $status->label());
    }

    /**
     * 返却済みステータスのラベルが正しいことを確認
     */
    public function test_checked_in_status_has_correct_label(): void
    {
        $status = EquipmentStatus::CHECKED_IN;

        $this->assertEquals('返却済み', $status->label());
    }

    /**
     * 各ステータスに色が設定されていることを確認
     */
    public function test_all_statuses_have_colors(): void
    {
        foreach (EquipmentStatus::cases() as $status) {
            $this->assertNotEmpty($status->color());
            $this->assertStringContainsString('bg-', $status->color());
        }
    }

    /**
     * options メソッドが正しい形式を返すことを確認
     */
    public function test_options_returns_correct_format(): void
    {
        $options = EquipmentStatus::options();

        $this->assertCount(3, $options);

        foreach ($options as $option) {
            $this->assertArrayHasKey('value', $option);
            $this->assertArrayHasKey('label', $option);
        }
    }

    /**
     * 文字列からEnumを生成できることを確認
     */
    public function test_can_create_from_string_value(): void
    {
        $status = EquipmentStatus::from('reserved');

        $this->assertEquals(EquipmentStatus::RESERVED, $status);
    }
}
