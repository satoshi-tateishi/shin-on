<?php

namespace Tests\Feature;

use App\Models\Equipment;
use App\Models\EquipmentCategory;
use App\Models\EquipmentSubcategory;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EquipmentTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $viewer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->viewer = User::factory()->create(['role' => 'viewer']);
    }

    public function test_equipment_index_displays_equipment_list(): void
    {
        $equipment = Equipment::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)->get('/equipments');

        $response->assertStatus(200);
        $response->assertViewIs('master.equipments.index');
    }

    public function test_equipment_can_be_created(): void
    {
        $category = EquipmentCategory::factory()->create();
        $subcategory = EquipmentSubcategory::factory()->create(['category_id' => $category->id]);
        $location = Location::factory()->warehouse()->create();

        $equipmentData = [
            'subcategory_id' => $subcategory->id,
            'name' => 'テスト機材',
            'manufacturer' => 'テストメーカー',
            'management_type' => 'individual',
            'quantity' => 1,
            'unit' => '台',
            'status' => 'available',
            'location_id' => $location->id,
            'now_location_id' => $location->id,
        ];

        $response = $this->actingAs($this->admin)
            ->post('/master/equipments', $equipmentData);

        $response->assertRedirect();
        $this->assertDatabaseHas('equipments', [
            'name' => 'テスト機材',
            'manufacturer' => 'テストメーカー',
        ]);
    }

    public function test_equipment_can_be_updated(): void
    {
        $equipment = Equipment::factory()->create();

        $response = $this->actingAs($this->admin)
            ->put("/master/equipments/{$equipment->id}", [
                'subcategory_id' => $equipment->subcategory_id,
                'name' => '更新後の機材名',
                'manufacturer' => $equipment->manufacturer,
                'management_type' => $equipment->management_type,
                'quantity' => $equipment->quantity,
                'unit' => $equipment->unit,
                'status' => $equipment->status,
                'location_id' => $equipment->location_id,
                'now_location_id' => $equipment->now_location_id,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('equipments', [
            'id' => $equipment->id,
            'name' => '更新後の機材名',
        ]);
    }

    public function test_equipment_can_be_deleted_by_admin(): void
    {
        $equipment = Equipment::factory()->create();

        $response = $this->actingAs($this->admin)
            ->delete("/master/equipments/{$equipment->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('equipments', [
            'id' => $equipment->id,
        ]);
    }

    public function test_equipment_show_displays_details(): void
    {
        $equipment = Equipment::factory()->create();

        $response = $this->actingAs($this->admin)
            ->get("/master/equipments/{$equipment->id}");

        $response->assertStatus(200);
        $response->assertSee($equipment->name);
    }

    public function test_equipment_filter_by_status(): void
    {
        Equipment::factory()->available()->count(2)->create();
        Equipment::factory()->underRepair()->count(1)->create();

        $response = $this->actingAs($this->admin)
            ->get('/equipments?status=available');

        $response->assertStatus(200);
    }

    public function test_equipment_search_by_name(): void
    {
        Equipment::factory()->create(['name' => 'スピーカーA']);
        Equipment::factory()->create(['name' => 'マイクB']);

        $response = $this->actingAs($this->admin)
            ->get('/equipments?search=スピーカー');

        $response->assertStatus(200);
    }
}
