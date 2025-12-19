<?php

namespace Tests\Unit\Models;

use App\Models\Location;
use PHPUnit\Framework\TestCase;

class LocationTest extends TestCase
{
    /**
     * 表示名はタイプと名前の組み合わせ
     */
    public function test_display_name_includes_type_and_name(): void
    {
        $location = new Location([
            'type' => '倉庫',
            'name' => '本社倉庫',
        ]);

        $this->assertEquals('[倉庫] 本社倉庫', $location->display_name);
    }

    /**
     * タイプが会場の場合の表示名
     */
    public function test_display_name_for_venue(): void
    {
        $location = new Location([
            'type' => '会場',
            'name' => 'ホールA',
        ]);

        $this->assertEquals('[会場] ホールA', $location->display_name);
    }

    /**
     * フォーマット済み表示名にHTMLタグが含まれる
     */
    public function test_formatted_display_name_contains_html(): void
    {
        $location = new Location([
            'type' => '倉庫',
            'name' => '本社倉庫',
        ]);

        $formattedName = $location->formatted_display_name;

        $this->assertStringContainsString('<span class="text-gray-500">', $formattedName);
        $this->assertStringContainsString('倉庫', $formattedName);
        $this->assertStringContainsString('本社倉庫', $formattedName);
    }

    /**
     * 有効な場所のステータスラベルが「有効」
     */
    public function test_status_label_for_active_location(): void
    {
        $location = new Location(['is_active' => true]);

        $this->assertEquals('有効', $location->status_label);
    }

    /**
     * 無効な場所のステータスラベルが「無効」
     */
    public function test_status_label_for_inactive_location(): void
    {
        $location = new Location(['is_active' => false]);

        $this->assertEquals('無効', $location->status_label);
    }

    /**
     * getCapacityStatus が critical を返す（90%以上）
     */
    public function test_capacity_status_critical(): void
    {
        $location = new Location;

        $reflection = new \ReflectionClass($location);
        $method = $reflection->getMethod('getCapacityStatus');
        $method->setAccessible(true);

        $this->assertEquals('critical', $method->invoke($location, 95.0));
        $this->assertEquals('critical', $method->invoke($location, 90.0));
    }

    /**
     * getCapacityStatus が warning を返す（75%以上90%未満）
     */
    public function test_capacity_status_warning(): void
    {
        $location = new Location;

        $reflection = new \ReflectionClass($location);
        $method = $reflection->getMethod('getCapacityStatus');
        $method->setAccessible(true);

        $this->assertEquals('warning', $method->invoke($location, 85.0));
        $this->assertEquals('warning', $method->invoke($location, 75.0));
    }

    /**
     * getCapacityStatus が normal を返す（50%以上75%未満）
     */
    public function test_capacity_status_normal(): void
    {
        $location = new Location;

        $reflection = new \ReflectionClass($location);
        $method = $reflection->getMethod('getCapacityStatus');
        $method->setAccessible(true);

        $this->assertEquals('normal', $method->invoke($location, 60.0));
        $this->assertEquals('normal', $method->invoke($location, 50.0));
    }

    /**
     * getCapacityStatus が low を返す（50%未満）
     */
    public function test_capacity_status_low(): void
    {
        $location = new Location;

        $reflection = new \ReflectionClass($location);
        $method = $reflection->getMethod('getCapacityStatus');
        $method->setAccessible(true);

        $this->assertEquals('low', $method->invoke($location, 30.0));
        $this->assertEquals('low', $method->invoke($location, 0.0));
    }
}
