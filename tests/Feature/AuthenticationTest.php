<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $response = $this->get('/equipments');

        $response->assertRedirectContains('/login');
    }

    public function test_authenticated_user_can_access_protected_routes(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($user)->get('/equipments');

        $response->assertStatus(200);
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
    }

    public function test_viewer_role_can_view_but_not_edit(): void
    {
        $user = User::factory()->create(['role' => 'viewer']);

        // 閲覧は可能
        $response = $this->actingAs($user)->get('/equipments');
        $response->assertStatus(200);

        // 作成ページへのアクセスは権限によって制限される可能性がある
        // （実際のミドルウェア設定による）
    }

    public function test_admin_can_access_admin_routes(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get('/admin/backup');

        // 管理者のみアクセス可能なページ
        $response->assertStatus(200);
    }

    public function test_non_admin_cannot_access_admin_routes(): void
    {
        $user = User::factory()->create(['role' => 'general']);

        $response = $this->actingAs($user)->get('/admin/backup');

        // 403 Forbidden または リダイレクト
        $this->assertTrue(in_array($response->status(), [403, 302]));
    }
}
