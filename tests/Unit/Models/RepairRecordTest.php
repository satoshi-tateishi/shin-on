<?php

namespace Tests\Unit\Models;

use App\Models\RepairRecord;
use PHPUnit\Framework\TestCase;

class RepairRecordTest extends TestCase
{
    /**
     * 報告済みステータスの表示名が正しい
     */
    public function test_status_display_for_reported(): void
    {
        $record = new RepairRecord(['status' => 'reported']);

        $this->assertEquals('報告済み', $record->status_display);
    }

    /**
     * 修理中ステータスの表示名が正しい
     */
    public function test_status_display_for_in_progress(): void
    {
        $record = new RepairRecord(['status' => 'in_progress']);

        $this->assertEquals('修理中', $record->status_display);
    }

    /**
     * 完了ステータスの表示名が正しい
     */
    public function test_status_display_for_completed(): void
    {
        $record = new RepairRecord(['status' => 'completed']);

        $this->assertEquals('完了', $record->status_display);
    }

    /**
     * キャンセルステータスの表示名が正しい
     */
    public function test_status_display_for_cancelled(): void
    {
        $record = new RepairRecord(['status' => 'cancelled']);

        $this->assertEquals('キャンセル', $record->status_display);
    }

    /**
     * 不明なステータスはそのまま返す
     */
    public function test_status_display_for_unknown(): void
    {
        $record = new RepairRecord(['status' => 'unknown_status']);

        $this->assertEquals('unknown_status', $record->status_display);
    }

    /**
     * 完了状態の判定が正しい
     */
    public function test_is_completed_returns_true_when_completed(): void
    {
        $record = new RepairRecord(['status' => 'completed']);

        $this->assertTrue($record->isCompleted());
    }

    /**
     * 未完了状態の判定が正しい
     */
    public function test_is_completed_returns_false_when_not_completed(): void
    {
        $record = new RepairRecord(['status' => 'in_progress']);

        $this->assertFalse($record->isCompleted());
    }

    /**
     * 修理中状態の判定が正しい
     */
    public function test_is_in_progress_returns_true_when_in_progress(): void
    {
        $record = new RepairRecord(['status' => 'in_progress']);

        $this->assertTrue($record->isInProgress());
    }

    /**
     * 修理中でない状態の判定が正しい
     */
    public function test_is_in_progress_returns_false_when_not_in_progress(): void
    {
        $record = new RepairRecord(['status' => 'completed']);

        $this->assertFalse($record->isInProgress());
    }

    /**
     * バリデーションルールが正しく取得できる（通常作成時）
     */
    public function test_validation_rules_for_create(): void
    {
        $rules = RepairRecord::getValidationRules(false);

        $this->assertArrayHasKey('equipment_id', $rules);
        $this->assertArrayHasKey('staff_user_id', $rules);
        $this->assertArrayHasKey('problem_description', $rules);
        $this->assertArrayNotHasKey('status', $rules);
    }

    /**
     * バリデーションルールが正しく取得できる（更新時）
     */
    public function test_validation_rules_for_update(): void
    {
        $rules = RepairRecord::getValidationRules(true);

        $this->assertArrayHasKey('equipment_id', $rules);
        $this->assertArrayHasKey('status', $rules);
        $this->assertArrayHasKey('repair_cost', $rules);
        $this->assertArrayHasKey('completed_at', $rules);
    }
}
