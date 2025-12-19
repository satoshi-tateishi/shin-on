<?php

namespace Tests\Feature;

use App\Models\Equipment;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocationTest extends TestCase
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

    public function test_location_index_displays(): void
    {
        Location::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)->get('/master/locations');

        $response->assertStatus(200);
        $response->assertViewIs('master.locations.index');
    }

    public function test_location_create_form_displays(): void
    {
        $response = $this->actingAs($this->admin)->get('/master/locations/create');

        $response->assertStatus(200);
        $response->assertViewIs('master.locations.create');
    }

    public function test_location_can_be_created(): void
    {
        $response = $this->actingAs($this->admin)->post('/master/locations', [
            'type' => '倉庫',
            'name' => 'テスト倉庫',
            'is_active' => true,
        ]);

        $response->assertRedirect(route('locations.index'));
        $this->assertDatabaseHas('locations', [
            'name' => 'テスト倉庫',
            'type' => '倉庫',
        ]);
    }

    public function test_location_can_be_updated(): void
    {
        $location = Location::factory()->create([
            'name' => '旧名称',
            'type' => '倉庫',
        ]);

        $response = $this->actingAs($this->admin)
            ->put("/master/locations/{$location->id}", [
                'type' => '劇場',
                'name' => '新名称',
                'is_active' => true,
            ]);

        $response->assertRedirect(route('locations.index'));
        $this->assertDatabaseHas('locations', [
            'id' => $location->id,
            'name' => '新名称',
            'type' => '劇場',
        ]);
    }

    public function test_location_can_be_deleted(): void
    {
        $location = Location::factory()->create();

        $response = $this->actingAs($this->admin)
            ->delete("/master/locations/{$location->id}");

        $response->assertRedirect(route('locations.index'));
        $this->assertDatabaseMissing('locations', [
            'id' => $location->id,
        ]);
    }

    public function test_location_with_equipment_cannot_be_deleted(): void
    {
        $location = Location::factory()->create();
        Equipment::factory()->create(['location_id' => $location->id]);

        $response = $this->actingAs($this->admin)
            ->delete("/master/locations/{$location->id}");

        $response->assertRedirect(route('locations.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('locations', [
            'id' => $location->id,
        ]);
    }

    public function test_location_main_warehouse_flag_can_be_set(): void
    {
        $response = $this->actingAs($this->admin)->post('/master/locations', [
            'type' => '倉庫',
            'name' => '主要倉庫テスト',
            'is_active' => true,
            'is_main_warehouse' => true,
        ]);

        $response->assertRedirect(route('locations.index'));
        $this->assertDatabaseHas('locations', [
            'name' => '主要倉庫テスト',
            'is_main_warehouse' => true,
        ]);
    }

    public function test_location_main_warehouse_flag_can_be_updated(): void
    {
        $location = Location::factory()->create([
            'is_main_warehouse' => false,
        ]);

        $response = $this->actingAs($this->admin)
            ->put("/master/locations/{$location->id}", [
                'type' => $location->type,
                'name' => $location->name,
                'is_active' => true,
                'is_main_warehouse' => true,
            ]);

        $response->assertRedirect(route('locations.index'));
        $this->assertDatabaseHas('locations', [
            'id' => $location->id,
            'is_main_warehouse' => true,
        ]);
    }

    public function test_location_requires_name(): void
    {
        $response = $this->actingAs($this->admin)->post('/master/locations', [
            'type' => '倉庫',
            'name' => '',
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_location_requires_type(): void
    {
        $response = $this->actingAs($this->admin)->post('/master/locations', [
            'type' => '',
            'name' => 'テスト',
        ]);

        $response->assertSessionHasErrors('type');
    }

    public function test_location_type_must_be_valid(): void
    {
        $response = $this->actingAs($this->admin)->post('/master/locations', [
            'type' => '無効なタイプ',
            'name' => 'テスト',
        ]);

        $response->assertSessionHasErrors('type');
    }

    public function test_location_show_displays(): void
    {
        $location = Location::factory()->create();

        $response = $this->actingAs($this->admin)
            ->get("/master/locations/{$location->id}");

        $response->assertStatus(200);
        $response->assertViewIs('master.locations.show');
    }

    public function test_location_edit_form_displays(): void
    {
        $location = Location::factory()->create();

        $response = $this->actingAs($this->admin)
            ->get("/master/locations/{$location->id}/edit");

        $response->assertStatus(200);
        $response->assertViewIs('master.locations.edit');
    }

    public function test_location_index_filters_by_type(): void
    {
        Location::factory()->create(['type' => '倉庫']);
        Location::factory()->create(['type' => '劇場']);

        $response = $this->actingAs($this->admin)
            ->get('/master/locations?type=倉庫');

        $response->assertStatus(200);
    }

    public function test_main_warehouse_locations_can_be_queried(): void
    {
        Location::factory()->mainWarehouse()->count(2)->create();
        Location::factory()->count(3)->create();

        $mainWarehouseIds = Location::getMainWarehouseIds();

        $this->assertCount(2, $mainWarehouseIds);
    }
}
