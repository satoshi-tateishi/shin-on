# 🧪 テスト実行・作成ガイド

## 🎯 テスト実行

### 基本コマンド
```bash
# 全テスト実行
./vendor/bin/sail artisan test

# 特定テストファイル実行
./vendor/bin/sail artisan test tests/Feature/UserTest.php

# 特定テストメソッド実行
./vendor/bin/sail artisan test --filter testUserCanBeCreated

# 詳細出力で実行
./vendor/bin/sail artisan test --verbose

# カバレッジレポート生成
./vendor/bin/sail artisan test --coverage
```

### テスト環境設定
テスト実行時は自動的に `.env.testing` ファイル（または環境変数）が使用されます。

```env
# .env.testing
APP_ENV=testing
DB_CONNECTION=sqlite
DB_DATABASE=:memory:
```

## 📝 テスト作成

### テストファイル作成
```bash
# Feature テスト作成
./vendor/bin/sail artisan make:test UserFeatureTest

# Unit テスト作成
./vendor/bin/sail artisan make:test UserUnitTest --unit

# モデルテスト作成
./vendor/bin/sail artisan make:test UserModelTest --unit
```

### テストディレクトリ構造
```
tests/
├── Feature/          # 統合テスト（複数コンポーネント）
│   ├── Auth/
│   ├── User/
│   └── ExampleTest.php
├── Unit/             # 単体テスト（個別クラス）
│   ├── Models/
│   ├── Services/
│   └── ExampleTest.php
├── CreatesApplication.php
└── TestCase.php
```

## 🏗️ Feature テスト例

### HTTPリクエストテスト
```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_user_list(): void
    {
        // Arrange
        User::factory()->count(3)->create();

        // Act
        $response = $this->get('/users');

        // Assert
        $response->assertStatus(200);
        $response->assertViewIs('users.index');
        $response->assertSee('ユーザー一覧');
    }

    public function test_user_can_create_new_user(): void
    {
        // Arrange
        $userData = [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        // Act
        $response = $this->post('/users', $userData);

        // Assert
        $response->assertStatus(302);
        $response->assertRedirect('/users');
        $this->assertDatabaseHas('users', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
        ]);
    }

    public function test_authenticated_user_can_access_dashboard(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $response = $this->actingAs($user)->get('/dashboard');

        // Assert
        $response->assertStatus(200);
    }

    public function test_guest_cannot_access_dashboard(): void
    {
        // Act
        $response = $this->get('/dashboard');

        // Assert
        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }
}
```

### APIテスト
```php
<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_fetch_users_via_api(): void
    {
        // Arrange
        User::factory()->count(5)->create();

        // Act
        $response = $this->getJson('/api/users');

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'email',
                    'created_at',
                    'updated_at',
                ]
            ]
        ]);
        $response->assertJsonCount(5, 'data');
    }

    public function test_can_create_user_via_api(): void
    {
        // Arrange
        $userData = [
            'name' => 'API User',
            'email' => 'api@example.com',
            'password' => 'password123',
        ];

        // Act
        $response = $this->postJson('/api/users', $userData);

        // Assert
        $response->assertStatus(201);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'email',
                'created_at',
                'updated_at',
            ]
        ]);
        $this->assertDatabaseHas('users', [
            'name' => 'API User',
            'email' => 'api@example.com',
        ]);
    }
}
```

## 🔧 Unit テスト例

### モデルテスト
```php
<?php

namespace Tests\Unit\Models;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_has_fillable_attributes(): void
    {
        $user = new User();

        $expected = ['name', 'email', 'password'];

        $this->assertEquals($expected, $user->getFillable());
    }

    public function test_user_has_hidden_attributes(): void
    {
        $user = new User();

        $expected = ['password', 'remember_token'];

        $this->assertEquals($expected, $user->getHidden());
    }

    public function test_user_can_have_posts(): void
    {
        $user = User::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $user->posts);
    }

    public function test_full_name_accessor(): void
    {
        $user = new User([
            'first_name' => '太郎',
            'last_name' => '田中',
        ]);

        $this->assertEquals('太郎 田中', $user->full_name);
    }
}
```

### サービスクラステスト
```php
<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserServiceTest extends TestCase
{
    use RefreshDatabase;

    private UserService $userService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userService = new UserService();
    }

    public function test_can_create_user_with_valid_data(): void
    {
        // Arrange
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
        ];

        // Act
        $user = $this->userService->createUser($userData);

        // Assert
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('Test User', $user->name);
        $this->assertEquals('test@example.com', $user->email);
        $this->assertTrue(\Hash::check('password123', $user->password));
    }

    public function test_throws_exception_for_duplicate_email(): void
    {
        // Arrange
        User::factory()->create(['email' => 'test@example.com']);

        $userData = [
            'name' => 'Another User',
            'email' => 'test@example.com',
            'password' => 'password123',
        ];

        // Assert
        $this->expectException(\Illuminate\Database\QueryException::class);

        // Act
        $this->userService->createUser($userData);
    }
}
```

## 🏭 ファクトリー・シーダー

### ファクトリー作成
```bash
# ユーザーファクトリー作成
./vendor/bin/sail artisan make:factory UserFactory --model=User

# 投稿ファクトリー作成
./vendor/bin/sail artisan make:factory PostFactory --model=Post
```

### ファクトリー実装例
```php
<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_admin' => true,
        ]);
    }
}
```

### ファクトリー使用例
```php
// テスト内でのファクトリー使用
public function test_example(): void
{
    // 1つのユーザー作成
    $user = User::factory()->create();

    // 複数ユーザー作成
    $users = User::factory()->count(10)->create();

    // 特定状態のユーザー作成
    $adminUser = User::factory()->admin()->create();
    $unverifiedUser = User::factory()->unverified()->create();

    // 属性指定での作成
    $specificUser = User::factory()->create([
        'name' => '特定のユーザー',
        'email' => 'specific@example.com',
    ]);
}
```

## 🎭 モック・スタブ

### 外部API モック
```php
use Illuminate\Support\Facades\Http;

public function test_external_api_call(): void
{
    // Arrange
    Http::fake([
        'api.example.com/*' => Http::response([
            'status' => 'success',
            'data' => ['id' => 1, 'name' => 'Test']
        ], 200)
    ]);

    // Act
    $response = $this->service->callExternalApi();

    // Assert
    Http::assertSent(function ($request) {
        return $request->url() === 'https://api.example.com/users';
    });
}
```

### メール送信テスト
```php
use Illuminate\Support\Facades\Mail;

public function test_welcome_email_is_sent(): void
{
    // Arrange
    Mail::fake();
    $user = User::factory()->create();

    // Act
    $this->service->sendWelcomeEmail($user);

    // Assert
    Mail::assertSent(WelcomeMail::class, function ($mail) use ($user) {
        return $mail->hasTo($user->email);
    });
}
```

## 📊 テストカバレッジ

### カバレッジ設定 (phpunit.xml)
```xml
<coverage>
    <include>
        <directory suffix=".php">./app</directory>
    </include>
    <exclude>
        <directory>./app/Console</directory>
        <file>./app/Http/Middleware/Authenticate.php</file>
    </exclude>
</coverage>
```

### カバレッジ実行
```bash
# HTML カバレッジレポート生成
./vendor/bin/sail artisan test --coverage-html=coverage

# テキスト カバレッジ表示
./vendor/bin/sail artisan test --coverage-text

# 最小カバレッジ指定（80%未満で失敗）
./vendor/bin/sail artisan test --min=80
```

## ✅ テストベストプラクティス

### テスト原則
1. **AAA パターン**: Arrange → Act → Assert
2. **独立性**: 各テストは独立して実行可能
3. **再現性**: 何度実行しても同じ結果
4. **高速性**: 素早く実行完了
5. **明確性**: テスト内容が理解しやすい

### 命名規約
```php
// 推奨パターン
public function test_user_can_create_post(): void
public function test_throws_exception_when_invalid_email(): void
public function test_returns_false_when_user_not_found(): void

// 日本語でも可
public function testユーザーが投稿を作成できる(): void
```

### データベーステスト
```php
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class UserTest extends TestCase
{
    // 毎テスト後にDBリセット（遅いが確実）
    use RefreshDatabase;

    // または、トランザクション使用（高速）
    use DatabaseTransactions;
}
```

---
**[← README.md に戻る](../README.md)**