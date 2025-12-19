<?php

namespace Tests\Unit\Models;

use App\Models\Performance;
use PHPUnit\Framework\TestCase;

class PerformanceTest extends TestCase
{
    /**
     * 企画中ステータスのラベルが正しい
     */
    public function test_status_label_for_planning(): void
    {
        $performance = new Performance(['status' => 'planning']);

        $this->assertEquals('企画中', $performance->status_label);
    }

    /**
     * 準備中ステータスのラベルが正しい
     */
    public function test_status_label_for_preparation(): void
    {
        $performance = new Performance(['status' => 'preparation']);

        $this->assertEquals('準備中', $performance->status_label);
    }

    /**
     * 進行中ステータスのラベルが正しい
     */
    public function test_status_label_for_in_progress(): void
    {
        $performance = new Performance(['status' => 'in_progress']);

        $this->assertEquals('進行中', $performance->status_label);
    }

    /**
     * 完了ステータスのラベルが正しい
     */
    public function test_status_label_for_completed(): void
    {
        $performance = new Performance(['status' => 'completed']);

        $this->assertEquals('完了', $performance->status_label);
    }

    /**
     * キャンセルステータスのラベルが正しい
     */
    public function test_status_label_for_cancelled(): void
    {
        $performance = new Performance(['status' => 'cancelled']);

        $this->assertEquals('キャンセル', $performance->status_label);
    }

    /**
     * 不明なステータスのラベルは「不明」
     */
    public function test_status_label_for_unknown(): void
    {
        $performance = new Performance(['status' => 'unknown']);

        $this->assertEquals('不明', $performance->status_label);
    }

    /**
     * 略称がある場合、表示名は略称
     */
    public function test_display_name_returns_short_name_when_present(): void
    {
        $performance = new Performance([
            'title' => '第10回記念コンサート',
            'short_name' => '10周年',
        ]);

        $this->assertEquals('10周年', $performance->display_name);
    }

    /**
     * 略称がない場合、表示名はタイトル
     */
    public function test_display_name_returns_title_when_no_short_name(): void
    {
        $performance = new Performance([
            'title' => '第10回記念コンサート',
            'short_name' => null,
        ]);

        $this->assertEquals('第10回記念コンサート', $performance->display_name);
    }

    /**
     * 略称が空文字の場合、表示名はタイトル
     */
    public function test_display_name_returns_title_when_short_name_is_empty(): void
    {
        $performance = new Performance([
            'title' => '第10回記念コンサート',
            'short_name' => '',
        ]);

        $this->assertEquals('第10回記念コンサート', $performance->display_name);
    }

    /**
     * 公演種別ラベルは種別名をそのまま返す
     */
    public function test_performance_type_label_returns_type(): void
    {
        $performance = new Performance(['performance_type' => 'コンサート']);

        $this->assertEquals('コンサート', $performance->performance_type_label);
    }
}
