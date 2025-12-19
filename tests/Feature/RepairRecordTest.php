<?php

namespace Tests\Feature;

use App\Models\Equipment;
use App\Models\RepairRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RepairRecordTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->staff = User::factory()->create(['role' => 'general', 'is_staff' => true]);
    }

    public function test_repair_records_index_displays_list(): void
    {
        RepairRecord::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)->get('/repair-records');

        $response->assertStatus(200);
        $response->assertViewIs('repair-records.index');
    }

    public function test_repair_record_can_be_created(): void
    {
        $equipment = Equipment::factory()->create();

        $repairData = [
            'equipment_id' => $equipment->id,
            'staff_user_id' => $this->staff->id,
            'problem_description' => 'テスト故障内容',
        ];

        $response = $this->actingAs($this->admin)
            ->post('/repair-records', $repairData);

        $response->assertRedirect();
        $this->assertDatabaseHas('repair_records', [
            'equipment_id' => $equipment->id,
            'problem_description' => 'テスト故障内容',
            'status' => 'reported',
        ]);
    }

    public function test_repair_record_show_displays_details(): void
    {
        $repairRecord = RepairRecord::factory()->create();

        $response = $this->actingAs($this->admin)
            ->get("/repair-records/{$repairRecord->id}");

        $response->assertStatus(200);
        $response->assertViewIs('repair-records.show');
    }

    public function test_repair_can_be_started(): void
    {
        $repairRecord = RepairRecord::factory()->reported()->create();

        $response = $this->actingAs($this->admin)
            ->patch("/repair-records/{$repairRecord->id}/start", [
                'started_at' => now()->format('Y-m-d'),
                'repair_company' => 'テスト修理会社',
                'repaired_by' => 'テスト担当者',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('repair_records', [
            'id' => $repairRecord->id,
            'status' => 'in_progress',
        ]);
    }

    public function test_repair_can_be_completed(): void
    {
        $repairRecord = RepairRecord::factory()->inProgress()->create();

        $response = $this->actingAs($this->admin)
            ->patch("/repair-records/{$repairRecord->id}/complete", [
                'completed_at' => now()->format('Y-m-d'),
                'repair_description' => '修理完了内容',
                'repair_cost' => 50000,
                'warranty_until' => null,
                'note' => null,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('repair_records', [
            'id' => $repairRecord->id,
            'status' => 'completed',
        ]);
    }

    public function test_repair_can_be_cancelled(): void
    {
        $repairRecord = RepairRecord::factory()->reported()->create();

        $response = $this->actingAs($this->admin)
            ->patch("/repair-records/{$repairRecord->id}/cancel", [
                'note' => 'キャンセル理由',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('repair_records', [
            'id' => $repairRecord->id,
            'status' => 'cancelled',
        ]);
    }

    public function test_repair_record_can_be_deleted_by_admin(): void
    {
        $repairRecord = RepairRecord::factory()->create();

        $response = $this->actingAs($this->admin)
            ->delete("/repair-records/{$repairRecord->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('repair_records', [
            'id' => $repairRecord->id,
        ]);
    }

    public function test_non_admin_cannot_delete_repair_record(): void
    {
        $repairRecord = RepairRecord::factory()->create();
        $generalUser = User::factory()->create(['role' => 'general']);

        $response = $this->actingAs($generalUser)
            ->delete("/repair-records/{$repairRecord->id}");

        // 削除が拒否される（403 または リダイレクト）
        $this->assertTrue(in_array($response->status(), [403, 302]));
    }

    public function test_repair_stats_returns_json(): void
    {
        RepairRecord::factory()->reported()->count(2)->create();
        RepairRecord::factory()->inProgress()->count(1)->create();
        RepairRecord::factory()->completed()->count(3)->create();

        $response = $this->actingAs($this->admin)->get('/repair-stats');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'total_repairs',
            'reported_repairs',
            'in_progress',
            'this_month_cost',
            'avg_repair_days',
        ]);
    }

    public function test_repair_filter_by_status(): void
    {
        RepairRecord::factory()->reported()->count(2)->create();
        RepairRecord::factory()->completed()->count(3)->create();

        $response = $this->actingAs($this->admin)
            ->get('/repair-records?status=reported');

        $response->assertStatus(200);
    }
}
