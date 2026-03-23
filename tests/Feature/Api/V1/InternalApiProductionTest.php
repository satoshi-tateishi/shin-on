<?php

namespace Tests\Feature\Api\V1;

use App\Auth\PortalJwtException;
use App\Auth\PortalJwtService;
use App\Models\Production;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InternalApiProductionTest extends TestCase
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
        $response = $this->getJson('/api/v1/productions');

        $response->assertStatus(401);
    }

    public function test_index_rejects_invalid_token(): void
    {
        $this->mockInvalidJwt();

        $response = $this->withToken('invalid-token')
            ->getJson('/api/v1/productions');

        $response->assertStatus(401);
    }

    public function test_show_requires_bearer_token(): void
    {
        $production = Production::factory()->create();

        $response = $this->getJson("/api/v1/productions/{$production->id}");

        $response->assertStatus(401);
    }

    // ─── index テスト ────────────────────────────────────────────────────────

    public function test_index_returns_active_productions_only(): void
    {
        $this->mockValidJwt();
        Production::factory()->create(['name' => '有効プロダクション', 'is_active' => true]);
        Production::factory()->create(['name' => '無効プロダクション', 'is_active' => false]);

        $response = $this->withToken('valid-portal-jwt')
            ->getJson('/api/v1/productions');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', '有効プロダクション');
    }

    public function test_index_filters_by_type(): void
    {
        $this->mockValidJwt();
        Production::factory()->create(['type' => '株式会社', 'name' => '株式会社A']);
        Production::factory()->create(['type' => '有限会社', 'name' => '有限会社B']);

        $response = $this->withToken('valid-portal-jwt')
            ->getJson('/api/v1/productions?type=株式会社');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', '株式会社');
    }

    public function test_index_returns_expected_fields(): void
    {
        $this->mockValidJwt();
        Production::factory()->create();

        $response = $this->withToken('valid-portal-jwt')
            ->getJson('/api/v1/productions');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id', 'sort', 'type', 'name',
                        'postal_code', 'address', 'note',
                        'is_active', 'created_at', 'updated_at',
                    ],
                ],
            ]);
    }

    // ─── show テスト ─────────────────────────────────────────────────────────

    public function test_show_returns_production(): void
    {
        $this->mockValidJwt();
        $production = Production::factory()->create(['name' => 'テスト制作', 'type' => '株式会社']);

        $response = $this->withToken('valid-portal-jwt')
            ->getJson("/api/v1/productions/{$production->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $production->id)
            ->assertJsonPath('data.name', 'テスト制作')
            ->assertJsonPath('data.type', '株式会社');
    }

    public function test_show_returns_404_for_inactive_production(): void
    {
        $this->mockValidJwt();
        $production = Production::factory()->inactive()->create();

        $response = $this->withToken('valid-portal-jwt')
            ->getJson("/api/v1/productions/{$production->id}");

        $response->assertStatus(404);
    }

    public function test_show_returns_404_for_nonexistent_production(): void
    {
        $this->mockValidJwt();

        $response = $this->withToken('valid-portal-jwt')
            ->getJson('/api/v1/productions/99999');

        $response->assertStatus(404);
    }
}
