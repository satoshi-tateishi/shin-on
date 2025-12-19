<?php

namespace Tests\Feature;

use App\Models\Equipment;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EquipmentTransferTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    public function test_equipment_transfer_index_displays(): void
    {
        $response = $this->actingAs($this->admin)->get('/equipment-transfer');

        $response->assertStatus(200);
    }

    public function test_equipment_transfer_return_select_displays(): void
    {
        $response = $this->actingAs($this->admin)->get('/equipment-transfer/return-select');

        $response->assertStatus(200);
    }

    public function test_categories_api_returns_json(): void
    {
        $response = $this->actingAs($this->admin)
            ->get('/equipment-transfer/api/categories');

        $response->assertStatus(200);
        $response->assertJsonStructure(['categories']);
    }

    public function test_equipment_api_returns_json(): void
    {
        Equipment::factory()->available()->count(3)->create();

        $response = $this->actingAs($this->admin)
            ->get('/equipment-transfer/api/equipment');

        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);
    }

    public function test_equipment_can_be_transferred(): void
    {
        $equipment = Equipment::factory()->available()->create();
        $fromLocation = $equipment->location;
        $toLocation = Location::factory()->warehouse()->create();

        $response = $this->actingAs($this->admin)
            ->postJson('/equipment-transfer/api/transfer', [
                'equipment_id' => $equipment->id,
                'from_location_id' => $fromLocation->id,
                'to_location_id' => $toLocation->id,
                'note' => 'テスト移動',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('equipments', [
            'id' => $equipment->id,
            'now_location_id' => $toLocation->id,
        ]);
    }

    public function test_equipment_can_be_returned(): void
    {
        $baseLocation = Location::factory()->warehouse()->create();
        $currentLocation = Location::factory()->create();
        $equipment = Equipment::factory()->create([
            'location_id' => $baseLocation->id,
            'now_location_id' => $currentLocation->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson("/equipment-transfer/api/return/{$equipment->id}");

        $response->assertStatus(200);
        $this->assertDatabaseHas('equipments', [
            'id' => $equipment->id,
            'now_location_id' => $baseLocation->id,
        ]);
    }

    public function test_bulk_transfer_moves_multiple_equipment(): void
    {
        $fromLocation = Location::factory()->warehouse()->create();
        $toLocation = Location::factory()->warehouse()->create();
        $equipments = Equipment::factory()->count(3)->create([
            'location_id' => $fromLocation->id,
            'now_location_id' => $fromLocation->id,
        ]);

        // APIは transfers 配列形式を期待
        $transfers = $equipments->map(fn ($eq) => [
            'equipment_id' => $eq->id,
            'to_location_id' => $toLocation->id,
            'note' => '一括移動テスト',
        ])->toArray();

        $response = $this->actingAs($this->admin)
            ->postJson('/equipment-transfer/api/bulk-transfer', [
                'transfers' => $transfers,
            ]);

        $response->assertStatus(200);
        foreach ($equipments as $equipment) {
            $this->assertDatabaseHas('equipments', [
                'id' => $equipment->id,
                'now_location_id' => $toLocation->id,
            ]);
        }
    }

    public function test_bulk_return_returns_multiple_equipment(): void
    {
        // 主要倉庫（is_main_warehouse=true）を基本倉庫とする機材を返却
        $baseLocation = Location::factory()->mainWarehouse()->create();
        $currentLocation = Location::factory()->create();
        $equipments = Equipment::factory()->count(2)->create([
            'location_id' => $baseLocation->id,
            'now_location_id' => $currentLocation->id,
        ]);

        // APIは returns 配列形式を期待
        $returns = $equipments->map(fn ($eq) => [
            'equipment_id' => $eq->id,
            'return_location_id' => $baseLocation->id,
        ])->toArray();

        $response = $this->actingAs($this->admin)
            ->postJson('/equipment-transfer/api/bulk-return', [
                'returns' => $returns,
            ]);

        $response->assertStatus(200);
        foreach ($equipments as $equipment) {
            $this->assertDatabaseHas('equipments', [
                'id' => $equipment->id,
                'now_location_id' => $baseLocation->id,
            ]);
        }
    }

    public function test_bulk_return_requires_authentication(): void
    {
        $response = $this->postJson('/equipment-transfer/api/bulk-return', [
            'returns' => [],
        ]);

        $response->assertStatus(401);  // Unauthenticated
    }
}
