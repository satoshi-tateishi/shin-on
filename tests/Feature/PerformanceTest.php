<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\Performance;
use App\Models\Phase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PerformanceTest extends TestCase
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

    public function test_performance_index_displays_list(): void
    {
        Performance::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)->get('/performances');

        $response->assertStatus(200);
        $response->assertViewIs('performances.index');
    }

    public function test_performance_can_be_created(): void
    {
        $performanceData = [
            'title' => 'テスト公演',
            'short_name' => 'テスト',
            'performance_type' => '演劇',
            'director' => 'テスト演出家',
            'status' => 'planning',
        ];

        $response = $this->actingAs($this->admin)
            ->post('/performances', $performanceData);

        $response->assertRedirect();
        $this->assertDatabaseHas('performances', [
            'title' => 'テスト公演',
            'short_name' => 'テスト',
        ]);
    }

    public function test_performance_show_displays_details(): void
    {
        $performance = Performance::factory()->create();

        $response = $this->actingAs($this->admin)
            ->get("/performances/{$performance->id}");

        $response->assertStatus(200);
        $response->assertViewIs('performances.show');
        $response->assertSee($performance->title);
    }

    public function test_performance_can_be_updated(): void
    {
        $performance = Performance::factory()->create();

        $response = $this->actingAs($this->admin)
            ->put("/performances/{$performance->id}", [
                'title' => '更新後の公演名',
                'short_name' => $performance->short_name,
                'performance_type' => $performance->performance_type,
                'status' => $performance->status,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('performances', [
            'id' => $performance->id,
            'title' => '更新後の公演名',
        ]);
    }

    public function test_performance_can_be_deleted(): void
    {
        $performance = Performance::factory()->create();

        $response = $this->actingAs($this->admin)
            ->delete("/performances/{$performance->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('performances', [
            'id' => $performance->id,
        ]);
    }

    public function test_performance_with_phases_displays_phases(): void
    {
        $performance = Performance::factory()->create();
        $location = Location::factory()->create();
        Phase::factory()->count(3)->create([
            'performance_id' => $performance->id,
            'location_id' => $location->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->get("/performances/{$performance->id}");

        $response->assertStatus(200);
    }

    public function test_performance_filter_by_status(): void
    {
        Performance::factory()->planning()->count(2)->create();
        Performance::factory()->completed()->count(3)->create();

        $response = $this->actingAs($this->admin)
            ->get('/performances?status=planning');

        $response->assertStatus(200);
    }

    public function test_phase_can_be_created_for_performance(): void
    {
        $performance = Performance::factory()->create();
        $location = Location::factory()->create();

        $phaseData = [
            'name' => '稽古',
            'location_id' => $location->id,
            'start_date' => now()->addDays(7)->format('Y-m-d'),
            'end_date' => now()->addDays(14)->format('Y-m-d'),
        ];

        $response = $this->actingAs($this->admin)
            ->post("/performances/{$performance->id}/phases", $phaseData);

        $response->assertRedirect();
        $this->assertDatabaseHas('phases', [
            'performance_id' => $performance->id,
            'name' => '稽古',
        ]);
    }
}
