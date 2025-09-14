# 🏆 Laravel開発ベストプラクティス

## 🎯 基本原則

### Single Responsibility Principle
各クラスは一つの責任のみを持つ

```php
// 悪い例: UserController が複数の責任を持つ
class UserController extends Controller
{
    public function store(Request $request)
    {
        // バリデーション
        $this->validate($request, [...]);

        // パスワードハッシュ化
        $password = bcrypt($request->password);

        // ユーザー作成
        $user = User::create([...]);

        // メール送信
        Mail::to($user)->send(new WelcomeMail());

        // ログ出力
        Log::info('User created', ['user_id' => $user->id]);
    }
}

// 良い例: 責任を分離
class UserController extends Controller
{
    public function store(CreateUserRequest $request, UserService $userService)
    {
        $user = $userService->createUser($request->validated());

        return redirect()->route('users.show', $user);
    }
}

class UserService
{
    public function createUser(array $data): User
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
        ]);

        Mail::to($user)->send(new WelcomeMail());
        Log::info('User created', ['user_id' => $user->id]);

        return $user;
    }
}
```

## 🗄️ データベース・Eloquent ベストプラクティス

### モデル設計
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Model
{
    use HasFactory, SoftDeletes;

    // Mass Assignment 保護
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    // リレーション
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    // スコープ
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // アクセサ
    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }
}
```

### N+1問題対策
```php
// 悪い例: N+1問題が発生
$users = User::all();
foreach ($users as $user) {
    echo $user->posts->count(); // 各ユーザーごとにクエリ実行
}

// 良い例: Eager Loading使用
$users = User::with('posts')->get();
foreach ($users as $user) {
    echo $user->posts->count();
}

// さらに良い例: withCount使用
$users = User::withCount('posts')->get();
foreach ($users as $user) {
    echo $user->posts_count;
}
```

### クエリ最適化
```php
// バルクインサート
User::insert([
    ['name' => 'User 1', 'email' => 'user1@example.com'],
    ['name' => 'User 2', 'email' => 'user2@example.com'],
    ['name' => 'User 3', 'email' => 'user3@example.com'],
]);

// バルクアップデート
User::whereIn('id', [1, 2, 3])->update(['is_active' => true]);

// チャンク処理（大量データ）
User::chunk(1000, function ($users) {
    foreach ($users as $user) {
        // 処理
    }
});
```

## 🎮 コントローラー ベストプラクティス

### RESTful リソースコントローラー
```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(private UserService $userService)
    {
    }

    public function index(): View
    {
        $users = User::paginate(15);
        return view('users.index', compact('users'));
    }

    public function show(User $user): View
    {
        return view('users.show', compact('user'));
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $user = $this->userService->createUser($request->validated());

        return redirect()->route('users.show', $user)
            ->with('success', 'ユーザーを作成しました。');
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->userService->updateUser($user, $request->validated());

        return redirect()->route('users.show', $user)
            ->with('success', 'ユーザー情報を更新しました。');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->userService->deleteUser($user);

        return redirect()->route('users.index')
            ->with('success', 'ユーザーを削除しました。');
    }
}
```

### APIコントローラー
```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserController extends Controller
{
    public function __construct(private UserService $userService)
    {
    }

    public function index(): AnonymousResourceCollection
    {
        $users = User::paginate(15);
        return UserResource::collection($users);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = $this->userService->createUser($request->validated());

        return response()->json([
            'message' => 'ユーザーを作成しました。',
            'data' => new UserResource($user)
        ], 201);
    }

    public function show(User $user): UserResource
    {
        return new UserResource($user);
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $user = $this->userService->updateUser($user, $request->validated());

        return response()->json([
            'message' => 'ユーザー情報を更新しました。',
            'data' => new UserResource($user)
        ]);
    }

    public function destroy(User $user): JsonResponse
    {
        $this->userService->deleteUser($user);

        return response()->json([
            'message' => 'ユーザーを削除しました。'
        ]);
    }
}
```

## 📝 フォームリクエスト

### バリデーション
```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('user'));
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($userId),
            ],
            'age' => ['nullable', 'integer', 'min:0', 'max:150'],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => '名前は必須です。',
            'email.required' => 'メールアドレスは必須です。',
            'email.unique' => 'このメールアドレスは既に使用されています。',
            'avatar.image' => 'アバターは画像ファイルを選択してください。',
            'avatar.max' => 'アバターのサイズは2MB以下にしてください。',
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim($this->name),
            'email' => strtolower($this->email),
        ]);
    }
}
```

## 🎨 Blade テンプレート

### レイアウト・コンポーネント
```blade
{{-- resources/views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased">
    <div class="min-h-screen bg-gray-100">
        @include('layouts.navigation')

        <!-- Page Heading -->
        @isset($header)
            <header class="bg-white shadow">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    {{ $header }}
                </div>
            </header>
        @endisset

        <!-- Page Content -->
        <main>
            @include('components.alerts')
            {{ $slot }}
        </main>
    </div>
</body>
</html>
```

```blade
{{-- resources/views/components/user-card.blade.php --}}
@props(['user'])

<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6 text-gray-900">
        <div class="flex items-center space-x-4">
            <img src="{{ $user->avatar_url }}"
                 alt="{{ $user->name }}"
                 class="w-12 h-12 rounded-full">
            <div>
                <h3 class="text-lg font-semibold">{{ $user->name }}</h3>
                <p class="text-gray-600">{{ $user->email }}</p>
            </div>
        </div>

        @if($slot->isNotEmpty())
            <div class="mt-4">
                {{ $slot }}
            </div>
        @endif
    </div>
</div>
```

### セキュア出力
```blade
{{-- XSS対策: 自動エスケープ --}}
<p>{{ $user->name }}</p>

{{-- HTML出力（信頼できるデータのみ） --}}
<div>{!! $trustedHtml !!}</div>

{{-- 古いBladeシンタックス（非推奨） --}}
<?php echo htmlspecialchars($user->name); ?>
```

## 🔧 サービス層パターン

### サービスクラス設計
```php
<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\UserWelcomeNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UserService
{
    public function createUser(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
            ]);

            $user->notify(new UserWelcomeNotification());

            Log::info('User created successfully', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);

            return $user;
        });
    }

    public function updateUser(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            $user->update([
                'name' => $data['name'],
                'email' => $data['email'],
            ]);

            if (isset($data['password'])) {
                $user->update([
                    'password' => Hash::make($data['password'])
                ]);
            }

            Log::info('User updated successfully', [
                'user_id' => $user->id
            ]);

            return $user->fresh();
        });
    }

    public function deleteUser(User $user): bool
    {
        return DB::transaction(function () use ($user) {
            // 関連データの処理
            $user->posts()->delete();

            // ユーザー削除（ソフトデリート）
            $deleted = $user->delete();

            Log::info('User deleted successfully', [
                'user_id' => $user->id
            ]);

            return $deleted;
        });
    }
}
```

## 🚦 ミドルウェア活用

### カスタムミドルウェア
```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LogRequests
{
    public function handle(Request $request, Closure $next)
    {
        $start = microtime(true);

        $response = $next($request);

        $duration = microtime(true) - $start;

        Log::info('HTTP Request', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'duration' => round($duration * 1000, 2) . 'ms',
            'status' => $response->status(),
        ]);

        return $response;
    }
}
```

## 🎯 キューと非同期処理

### ジョブクラス
```php
<?php

namespace App\Jobs;

use App\Models\User;
use App\Notifications\UserWelcomeNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendWelcomeEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private User $user)
    {
    }

    public function handle(): void
    {
        $this->user->notify(new UserWelcomeNotification());
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Failed to send welcome email', [
            'user_id' => $this->user->id,
            'error' => $exception->getMessage()
        ]);
    }
}
```

## 📊 イベント・リスナー

### イベント駆動設計
```php
<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserRegistered
{
    use Dispatchable, SerializesModels;

    public function __construct(public User $user)
    {
    }
}
```

```php
<?php

namespace App\Listeners;

use App\Events\UserRegistered;
use App\Jobs\SendWelcomeEmail;

class HandleUserRegistration
{
    public function handle(UserRegistered $event): void
    {
        // 非同期でウェルカムメール送信
        SendWelcomeEmail::dispatch($event->user);

        // その他の処理...
    }
}
```

## ✅ コード品質チェックリスト

### セキュリティ
- [ ] Mass Assignment攻撃対策
- [ ] CSRF保護適用
- [ ] XSS対策（Blade自動エスケープ）
- [ ] SQLインジェクション対策
- [ ] 適切な認証・認可

### パフォーマンス
- [ ] N+1問題の回避
- [ ] 適切なインデックス設定
- [ ] キャッシュ戦略の実装
- [ ] バルク操作の活用

### 可読性・保守性
- [ ] PSR-12準拠
- [ ] 適切な命名規則
- [ ] 単一責任原則の遵守
- [ ] 適切なコメント・ドキュメント

### テスト
- [ ] 適切なテストカバレッジ
- [ ] Feature/Unitテストの分離
- [ ] Factoryを使用したテストデータ
- [ ] モック・スタブの適切な使用

---
**[← README.md に戻る](../README.md)**