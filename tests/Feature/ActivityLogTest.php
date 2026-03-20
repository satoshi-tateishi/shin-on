<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLogTest extends TestCase
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

    public function test_activity_log_index_displays(): void
    {
        ActivityLog::factory()->count(5)->forUser($this->admin)->create();

        $response = $this->actingAs($this->admin)->get('/activity-logs');

        $response->assertStatus(200);
        $response->assertViewIs('activity-logs.index');
    }

    public function test_activity_log_requires_authentication(): void
    {
        $response = $this->get('/activity-logs');

        $response->assertRedirectContains('/login');
    }

    public function test_activity_log_requires_admin_role(): void
    {
        $response = $this->actingAs($this->viewer)->get('/activity-logs');

        $response->assertStatus(403);
    }

    public function test_activity_log_filters_by_user(): void
    {
        $user1 = User::factory()->create(['role' => 'admin']);
        $user2 = User::factory()->create(['role' => 'admin']);

        ActivityLog::factory()->count(3)->forUser($user1)->create();
        ActivityLog::factory()->count(2)->forUser($user2)->create();

        $response = $this->actingAs($this->admin)
            ->get('/activity-logs?user_id='.$user1->id);

        $response->assertStatus(200);
        $response->assertViewHas('userId', (string) $user1->id);
        $response->assertViewHas('selectedUser');
    }

    public function test_activity_log_filters_by_equipment_action(): void
    {
        ActivityLog::factory()->equipmentAction()->forUser($this->admin)->create();
        ActivityLog::factory()->performanceAction()->forUser($this->admin)->create();

        $response = $this->actingAs($this->admin)
            ->get('/activity-logs?filter=equipment');

        $response->assertStatus(200);
        $response->assertViewHas('filter', 'equipment');
    }

    public function test_activity_log_filters_by_performance_action(): void
    {
        ActivityLog::factory()->performanceAction()->forUser($this->admin)->create();

        $response = $this->actingAs($this->admin)
            ->get('/activity-logs?filter=performance');

        $response->assertStatus(200);
        $response->assertViewHas('filter', 'performance');
    }

    public function test_activity_log_filters_by_phase_action(): void
    {
        ActivityLog::factory()->phaseAction()->forUser($this->admin)->create();

        $response = $this->actingAs($this->admin)
            ->get('/activity-logs?filter=phase');

        $response->assertStatus(200);
        $response->assertViewHas('filter', 'phase');
    }

    public function test_activity_log_filters_by_login_action(): void
    {
        ActivityLog::factory()->loginAction()->forUser($this->admin)->create();

        $response = $this->actingAs($this->admin)
            ->get('/activity-logs?filter=login');

        $response->assertStatus(200);
        $response->assertViewHas('filter', 'login');
    }

    public function test_activity_log_shows_statistics(): void
    {
        ActivityLog::factory()->count(2)->equipmentAction()->forUser($this->admin)->create();
        ActivityLog::factory()->count(3)->performanceAction()->forUser($this->admin)->create();
        ActivityLog::factory()->loginAction()->forUser($this->admin)->create();

        $response = $this->actingAs($this->admin)->get('/activity-logs');

        $response->assertStatus(200);
        $response->assertViewHas('stats');
    }

    public function test_activity_log_paginates_results(): void
    {
        ActivityLog::factory()->count(25)->forUser($this->admin)->create();

        $response = $this->actingAs($this->admin)->get('/activity-logs');

        $response->assertStatus(200);
        $response->assertViewHas('activities');
    }

    public function test_activity_log_shows_users_with_activity(): void
    {
        $activeUser = User::factory()->create(['role' => 'admin']);
        ActivityLog::factory()->forUser($activeUser)->create();

        $response = $this->actingAs($this->admin)->get('/activity-logs');

        $response->assertStatus(200);
        $response->assertViewHas('users');
    }
}
