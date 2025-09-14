# 🚀 Laravel 12 新機能・特徴

## 📋 Laravel 12 概要

Laravel 12は2024年にリリースされた最新のメジャーバージョンで、パフォーマンス向上、開発者体験の改善、新機能の追加が行われています。

## ✨ 主な新機能

### PHP 8.4 サポート
- **最小要件**: PHP 8.1以上
- **推奨**: PHP 8.4
- **新しい言語機能**の活用が可能

### 新しいBladeコンポーネント機能
```blade
{{-- Class-based コンポーネントの改善 --}}
<x-user-card :user="$user" class="mb-4">
    <x-slot:actions>
        <x-button>編集</x-button>
        <x-button variant="danger">削除</x-button>
    </x-slot:actions>
</x-user-card>
```

### 改良されたEloquent
```php
// 新しいクエリビルダーメソッド
User::whereJsonContains('preferences->notifications', 'email')
    ->lazy()
    ->each(function ($user) {
        // 処理
    });

// 改良されたリレーション
public function posts(): HasMany
{
    return $this->hasMany(Post::class)
        ->withDefault([
            'title' => '新しい投稿',
            'content' => '',
        ]);
}
```

## 🎨 フロントエンド改善

### Vite統合の強化
```javascript
// vite.config.js - Laravel 12での設定例
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js'
            ],
            refresh: true,
        }),
    ],
    build: {
        rollupOptions: {
            output: {
                manualChunks: {
                    vendor: ['lodash'],
                    ui: ['alpinejs']
                }
            }
        }
    }
});
```

### Tailwind CSS との統合改善
```css
/* resources/css/app.css */
@import 'tailwindcss';

@layer components {
    .btn {
        @apply px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600;
    }

    .btn-danger {
        @apply bg-red-500 hover:bg-red-600;
    }
}
```

## 🔧 新しいArtisanコマンド

### 強化されたmakeコマンド
```bash
# より詳細なオプションを持つmakeコマンド
./vendor/bin/sail artisan make:controller UserController --type=api --requests
./vendor/bin/sail artisan make:model User --all --pest

# 新しいスタブカスタマイズ
./vendor/bin/sail artisan stub:publish
```

### 開発者体験の向上
```bash
# 改良されたログ表示
./vendor/bin/sail artisan log:tail --filter=error

# インタラクティブなマイグレーション
./vendor/bin/sail artisan migrate --step --interactive

# 詳細なルート情報
./vendor/bin/sail artisan route:list --sort=name --except-vendor
```

## 📊 パフォーマンス向上

### クエリ最適化
```php
// 新しいクエリ最適化機能
class User extends Model
{
    // 自動的にN+1問題を検出・警告
    protected $with = ['profile'];

    // 新しいキャッシュ機能
    public function getPostsAttribute()
    {
        return Cache::remember(
            "user.{$this->id}.posts",
            now()->addMinutes(10),
            fn() => $this->posts()->get()
        );
    }
}
```

### メモリ使用量の改善
```php
// 新しいLazyコレクション機能
User::query()
    ->where('created_at', '>', now()->subMonth())
    ->lazy(1000) // チャンクサイズ指定
    ->each(function ($user) {
        $this->processUser($user);
    });
```

## 🔒 セキュリティ強化

### 新しい認証機能
```php
// 改良されたRate Limiting
Route::middleware([
    'throttle:api:60,1', // より柔軟な設定
    'auth:sanctum'
])->group(function () {
    Route::apiResource('users', UserController::class);
});

// 新しいCSRF保護
class VerifyCsrfToken extends Middleware
{
    protected $except = [
        'stripe/*',
        'webhooks/*'
    ];

    // 新しい動的除外機能
    protected function shouldPassThrough($request)
    {
        return $request->is('api/webhook/*') &&
               $request->hasValidSignature();
    }
}
```

## 🧪 テスト機能の改善

### 新しいテストヘルパー
```php
// 改良されたHTTPテスト
class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_creation_with_new_assertions(): void
    {
        $response = $this->postJson('/api/users', [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // 新しいアサーションメソッド
        $response
            ->assertCreated()
            ->assertJsonPath('data.name', 'Test User')
            ->assertJsonMissingPath('data.password')
            ->assertDatabaseHas('users', ['email' => 'test@example.com']);
    }

    // パラレルテスト実行の改善
    public function test_can_run_in_parallel(): void
    {
        $this->parallel(); // テストの並列実行を許可

        // テスト内容
    }
}
```

### Factory改善
```php
class UserFactory extends Factory
{
    // 新しい状態定義方法
    public function configure(): static
    {
        return $this->afterCreating(function (User $user) {
            $user->profile()->create([
                'bio' => fake()->paragraph(),
            ]);
        });
    }

    // より柔軟な状態管理
    public function withPosts(int $count = 3): static
    {
        return $this->afterCreating(function (User $user) use ($count) {
            Post::factory()->count($count)->create([
                'user_id' => $user->id
            ]);
        });
    }
}
```

## 🌐 国際化の改善

### 多言語対応強化
```php
// 新しい翻訳機能
// resources/lang/ja/messages.php
return [
    'welcome' => 'ようこそ、:nameさん！',
    'posts' => [
        'count' => '{0} 投稿がありません|{1} :count件の投稿があります|[2,*] :count件の投稿があります',
    ],
];

// Bladeテンプレートでの使用
@lang('messages.welcome', ['name' => $user->name])
@choice('messages.posts.count', $posts->count(), ['count' => $posts->count()])
```

## 📱 API機能強化

### Laravel Sanctum改善
```php
// 改良されたAPIトークン管理
class User extends Authenticatable
{
    use HasApiTokens;

    public function createToken(string $name, array $abilities = ['*'])
    {
        return $this->tokens()->create([
            'name' => $name,
            'token' => hash('sha256', $plainTextToken = Str::random(40)),
            'abilities' => $abilities,
            'expires_at' => now()->addDays(30), // トークン有効期限
        ]);
    }
}
```

### API Resource改善
```php
class UserResource extends JsonResource
{
    // 新しい条件付きリソース
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->when($request->user()->can('view', $this->resource), $this->email),
            'posts' => PostResource::collection($this->whenLoaded('posts')),
            'meta' => $this->when($this->needsMeta($request), [
                'last_login' => $this->last_login_at,
                'created_ago' => $this->created_at->diffForHumans(),
            ]),
        ];
    }

    private function needsMeta($request): bool
    {
        return $request->user()->isAdmin() ||
               $request->user()->id === $this->id;
    }
}
```

## ⚡ キャッシュとセッション改善

### 新しいキャッシュタグ機能
```php
// キャッシュタグによる効率的な無効化
Cache::tags(['users', 'posts'])->put('user.posts.'.$userId, $posts, 3600);

// 特定タグのキャッシュをすべて無効化
Cache::tags(['users'])->flush();

// より詳細なキャッシュ制御
class UserService
{
    public function getUserPosts(User $user)
    {
        return Cache::tags(['users', 'posts'])
            ->remember(
                "user.{$user->id}.posts",
                now()->addHour(),
                fn() => $user->posts()->latest()->get()
            );
    }
}
```

## 📈 監視・デバッグ機能

### Laravel Telescope統合
```php
// 新しいTelescope監視項目
// config/telescope.php
'watchers' => [
    TelescopeServiceProvider::class => [
        'slow_queries' => [
            'enabled' => env('TELESCOPE_SLOW_QUERIES_ENABLED', true),
            'slow' => 100, // 100ms以上のクエリを監視
        ],
        'failed_jobs' => [
            'enabled' => env('TELESCOPE_FAILED_JOBS_ENABLED', true),
        ],
        'cache' => [
            'enabled' => env('TELESCOPE_CACHE_ENABLED', true),
        ],
    ],
],
```

## 🔄 マイグレーション機能

### 匿名マイグレーション
```php
<?php

// より簡潔なマイグレーション記述
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->constrained();
            $table->string('title');
            $table->text('content');
            $table->enum('status', ['draft', 'published', 'archived'])
                  ->default('draft');
            $table->timestamps();

            // 新しいインデックス記述方法
            $table->index(['status', 'created_at']);
            $table->fullText(['title', 'content']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
```

## 🚀 Laravel 12 導入時の注意点

### 破壊的変更
- **PHP 8.1未満のサポート終了**
- **一部のヘルパー関数の削除**
- **古いMiddlewareシンタックスの非推奨化**

### 推奨アップグレード手順
```bash
# 1. 現在のコードベースのバックアップ
git add . && git commit -m "Pre-Laravel 12 upgrade backup"

# 2. 段階的アップグレード
composer update laravel/framework --with-dependencies

# 3. 設定ファイルの更新確認
php artisan config:clear
php artisan view:clear
php artisan route:clear

# 4. テスト実行
php artisan test

# 5. Laravel Pintによるコード修正
./vendor/bin/pint
```

---
**[← README.md に戻る](../README.md)**