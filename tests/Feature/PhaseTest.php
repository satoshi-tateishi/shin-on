<?php

namespace Tests\Feature;

use App\Models\Equipment;
use App\Models\Location;
use App\Models\Performance;
use App\Models\Phase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Performance $performance;

    private Phase $phase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->performance = Performance::factory()->create();
        $this->phase = Phase::factory()->create([
            'performance_id' => $this->performance->id,
        ]);
    }

    public function test_phase_show_displays_details(): void
    {
        $response = $this->actingAs($this->admin)
            ->get("/phases/{$this->phase->id}");

        $response->assertStatus(200);
        $response->assertViewIs('phases.show');
    }

    public function test_phase_can_be_updated(): void
    {
        $location = Location::factory()->create();

        $response = $this->actingAs($this->admin)
            ->put("/phases/{$this->phase->id}", [
                'name' => '更新後のフェーズ名',
                'location_id' => $location->id,
                'start_date' => $this->phase->start_date->format('Y-m-d'),
                'end_date' => $this->phase->end_date->format('Y-m-d'),
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('phases', [
            'id' => $this->phase->id,
            'name' => '更新後のフェーズ名',
        ]);
    }

    public function test_phase_can_be_deleted(): void
    {
        $response = $this->actingAs($this->admin)
            ->delete("/phases/{$this->phase->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('phases', [
            'id' => $this->phase->id,
        ]);
    }

    public function test_phase_equipment_index_displays_equipment(): void
    {
        $response = $this->actingAs($this->admin)
            ->get("/phases/{$this->phase->id}/equipment");

        $response->assertStatus(200);
    }

    public function test_available_equipment_api_returns_json(): void
    {
        Equipment::factory()->available()->count(3)->create();

        $response = $this->actingAs($this->admin)
            ->get("/phases/{$this->phase->id}/available-equipment");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            '*' => ['id', 'name'],
        ]);
    }
}
