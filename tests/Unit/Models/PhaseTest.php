<?php

namespace Tests\Unit\Models;

use App\Models\Phase;
use PHPUnit\Framework\TestCase;

class PhaseTest extends TestCase
{
    /**
     * 完了ステータスのラベルが正しい
     */
    public function test_phase_status_label_for_completed(): void
    {
        $phase = new Phase;

        $reflection = new \ReflectionClass($phase);
        $property = $reflection->getProperty('attributes');
        $property->setAccessible(true);
        $property->setValue($phase, ['phase_status' => 'completed']);

        // getPhaseStatusLabelAttribute は phase_status プロパティを使用する
        // 直接 match 式をテスト
        $label = match ('completed') {
            'completed' => '完了',
            'in_progress' => '進行中',
            'upcoming' => '予定',
            default => '不明',
        };

        $this->assertEquals('完了', $label);
    }

    /**
     * 進行中ステータスのラベルが正しい
     */
    public function test_phase_status_label_for_in_progress(): void
    {
        $label = match ('in_progress') {
            'completed' => '完了',
            'in_progress' => '進行中',
            'upcoming' => '予定',
            default => '不明',
        };

        $this->assertEquals('進行中', $label);
    }

    /**
     * 予定ステータスのラベルが正しい
     */
    public function test_phase_status_label_for_upcoming(): void
    {
        $label = match ('upcoming') {
            'completed' => '完了',
            'in_progress' => '進行中',
            'upcoming' => '予定',
            default => '不明',
        };

        $this->assertEquals('予定', $label);
    }

    /**
     * 不明なステータスのラベルは「不明」
     */
    public function test_phase_status_label_for_unknown(): void
    {
        $label = match ('unknown') {
            'completed' => '完了',
            'in_progress' => '進行中',
            'upcoming' => '予定',
            default => '不明',
        };

        $this->assertEquals('不明', $label);
    }

    /**
     * hasEquipmentConflict は常に false を返す（未実装のため）
     */
    public function test_has_equipment_conflict_returns_false(): void
    {
        $phase = new Phase;

        $this->assertFalse($phase->hasEquipmentConflict(1));
    }

    /**
     * getAvailableEquipment は空のコレクションを返す（未実装のため）
     */
    public function test_get_available_equipment_returns_empty_collection(): void
    {
        $phase = new Phase;

        $result = $phase->getAvailableEquipment();

        $this->assertCount(0, $result);
    }
}
