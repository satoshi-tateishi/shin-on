<?php

namespace Tests\Feature\Api\V1;

use App\Auth\PortalJwtException;
use App\Auth\PortalJwtService;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InternalApiLocationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'viewer']);
    }

    /**
     * PortalJwtService をモックして Bearer トークンの検証をバイパスする。
     */
    private function mockValidJwt(): void
    {
        $user = $this->user;
        $this->mock(PortalJwtService::class, function ($mock) use ($user): void {
            $mock->shouldReceive('validateToken')->andReturn((object) ['sub' => 'test-uuid']);
            $mock->shouldReceive('findOrCreateUser')->andReturn($user);
        });
    }

    private function mockInvalidJwt(): void
    {
        $this->mock(PortalJwtService::class, function ($mock): void {
            $mock->shouldReceive('validateToken')->andThrow(new PortalJwtException('Invalid token'));
        });
    }

    // ─── 認証テスト ──────────────────────────────────────────────────────────

    public function test_index_requires_bearer_token(): void
    {
        $response = $this->getJson('/api/v1/locations');

        $response->assertStatus(401);
    }

    public function test_index_rejects_invalid_token(): void
    {
        $this->mockInvalidJwt();

        $response = $this->withToken('invalid-token')
            ->getJson('/api/v1/locations');

        $response->assertStatus(401);
    }

    public function test_show_requires_bearer_token(): void
    {
        $location = Location::factory()->create();

        $response = $this->getJson("/api/v1/locations/{$location->id}");

        $response->assertStatus(401);
    }

    // ─── index テスト ────────────────────────────────────────────────────────

    public function test_index_returns_active_locations_only(): void
    {
        $this->mockValidJwt();
        Location::factory()->create(['name' => '有効場所', 'is_active' => true]);
        Location::factory()->create(['name' => '無効場所', 'is_active' => false]);

        $response = $this->withToken('valid-portal-jwt')
            ->getJson('/api/v1/locations');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', '有効場所');
    }

    public function test_index_filters_by_type(): void
    {
        $this->mockValidJwt();
        Location::factory()->create(['type' => '倉庫', 'name' => '倉庫A']);
        Location::factory()->create(['type' => '劇場', 'name' => '劇場A']);

        $response = $this->withToken('valid-portal-jwt')
            ->getJson('/api/v1/locations?type=倉庫');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', '倉庫');
    }

    public function test_index_returns_expected_fields(): void
    {
        $this->mockValidJwt();
        Location::factory()->create();

        $response = $this->withToken('valid-portal-jwt')
            ->getJson('/api/v1/locations');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id', 'sort', 'type', 'name', 'furigana',
                        'tel1_name', 'tel1', 'tel2_name', 'tel2',
                        'fax', 'email1_name', 'email1', 'email2_name', 'email2',
                        'postal_code', 'address', 'note',
                        'is_active', 'is_inventory_visible', 'is_transfer_visible', 'is_main_warehouse',
                        'created_at', 'updated_at',
                    ],
                ],
            ]);
    }

    // ─── show テスト ─────────────────────────────────────────────────────────

    public function test_show_returns_location(): void
    {
        $this->mockValidJwt();
        $location = Location::factory()->create(['name' => 'テスト倉庫', 'type' => '倉庫']);

        $response = $this->withToken('valid-portal-jwt')
            ->getJson("/api/v1/locations/{$location->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $location->id)
            ->assertJsonPath('data.name', 'テスト倉庫')
            ->assertJsonPath('data.type', '倉庫');
    }

    public function test_show_returns_404_for_inactive_location(): void
    {
        $this->mockValidJwt();
        $location = Location::factory()->inactive()->create();

        $response = $this->withToken('valid-portal-jwt')
            ->getJson("/api/v1/locations/{$location->id}");

        $response->assertStatus(404);
    }

    public function test_show_returns_404_for_nonexistent_location(): void
    {
        $this->mockValidJwt();

        $response = $this->withToken('valid-portal-jwt')
            ->getJson('/api/v1/locations/99999');

        $response->assertStatus(404);
    }
}
