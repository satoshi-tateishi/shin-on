<?php

namespace Tests\Unit\Enums;

use App\Enums\PerformanceStatus;
use PHPUnit\Framework\TestCase;

class PerformanceStatusTest extends TestCase
{
    /**
     * 企画中ステータスのラベルが正しいことを確認
     */
    public function test_planning_status_has_correct_label(): void
    {
        $this->assertEquals('企画中', PerformanceStatus::PLANNING->label());
    }

    /**
     * 準備中ステータスのラベルが正しいことを確認
     */
    public function test_preparation_status_has_correct_label(): void
    {
        $this->assertEquals('準備中', PerformanceStatus::PREPARATION->label());
    }

    /**
     * 進行中ステータスのラベルが正しいことを確認
     */
    public function test_in_progress_status_has_correct_label(): void
    {
        $this->assertEquals('進行中', PerformanceStatus::IN_PROGRESS->label());
    }

    /**
     * 完了ステータスのラベルが正しいことを確認
     */
    public function test_completed_status_has_correct_label(): void
    {
        $this->assertEquals('完了', PerformanceStatus::COMPLETED->label());
    }

    /**
     * キャンセルステータスのラベルが正しいことを確認
     */
    public function test_cancelled_status_has_correct_label(): void
    {
        $this->assertEquals('キャンセル', PerformanceStatus::CANCELLED->label());
    }

    /**
     * 全てのステータスに色が設定されていることを確認
     */
    public function test_all_statuses_have_colors(): void
    {
        foreach (PerformanceStatus::cases() as $status) {
            $this->assertNotEmpty($status->color());
            $this->assertStringContainsString('bg-', $status->color());
            $this->assertStringContainsString('text-', $status->color());
        }
    }

    /**
     * options メソッドが5つのオプションを返すことを確認
     */
    public function test_options_returns_five_items(): void
    {
        $options = PerformanceStatus::options();

        $this->assertCount(5, $options);
    }

    /**
     * 文字列からEnumを生成できることを確認
     */
    public function test_can_create_from_string_value(): void
    {
        $this->assertEquals(PerformanceStatus::PLANNING, PerformanceStatus::from('planning'));
        $this->assertEquals(PerformanceStatus::COMPLETED, PerformanceStatus::from('completed'));
    }
}
