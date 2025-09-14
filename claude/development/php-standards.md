# 🎯 PHP・Laravel コーディング標準

## 📏 コーディング規約

### PSR標準準拠
- **PSR-1**: Basic Coding Standard
- **PSR-4**: Autoloader Standard
- **PSR-12**: Extended Coding Style Guide

### Laravel Pint設定
プロジェクトにはLaravel Pintが標準で含まれており、PSR-12準拠の自動フォーマットが適用されます。

```bash
# コードフォーマット実行
./vendor/bin/sail php ./vendor/bin/pint

# フォーマット確認（実際の変更はしない）
./vendor/bin/sail php ./vendor/bin/pint --test

# 特定ファイルのみフォーマット
./vendor/bin/sail php ./vendor/bin/pint app/Http/Controllers/UserController.php
```

## 🏗️ Laravel命名規則

### ファイル・クラス命名
| 要素 | 規則 | 例 |
|------|------|-----|
| コントローラー | 複数形 + Controller | `UsersController` |
| モデル | 単数形、PascalCase | `User`, `BlogPost` |
| マイグレーション | snake_case | `create_users_table` |
| ファクトリー | モデル名 + Factory | `UserFactory` |
| シーダー | クラス名 + Seeder | `UsersTableSeeder` |

### データベース命名
| 要素 | 規則 | 例 |
|------|------|-----|
| テーブル名 | snake_case、複数形 | `users`, `blog_posts` |
| カラム名 | snake_case | `first_name`, `created_at` |
| 外部キー | 単数形テーブル名_id | `user_id`, `blog_post_id` |
| インデックス | テーブル名_カラム名_index | `users_email_index` |

### ルート命名
```php
// 推奨パターン
Route::get('/users', [UserController::class, 'index'])->name('users.index');
Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
Route::post('/users', [UserController::class, 'store'])->name('users.store');
```

## 💻 PHPコーディングベストプラクティス

### クラス構造
```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class UserController extends Controller
{
    // プロパティ（public → protected → private順）
    protected $userService;

    // コンストラクタ
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    // publicメソッド
    public function index(): Response
    {
        // メソッド実装
    }

    // protectedメソッド
    protected function validateUser(array $data): array
    {
        // 実装
    }

    // privateメソッド
    private function formatUserData(User $user): array
    {
        // 実装
    }
}
```

### 型宣言
```php
// 推奨: 厳密な型宣言を使用
public function createUser(string $name, int $age, ?string $email = null): User
{
    return User::create([
        'name' => $name,
        'age' => $age,
        'email' => $email,
    ]);
}

// 配列型宣言
public function processUsers(array $users): array
{
    return array_map(fn($user) => $this->formatUser($user), $users);
}
```

### Docコメント
```php
/**
 * ユーザーを作成する
 *
 * @param  string  $name  ユーザー名
 * @param  int  $age  年齢
 * @param  string|null  $email  メールアドレス（オプション）
 * @return User  作成されたユーザーインスタンス
 *
 * @throws ValidationException  バリデーションエラー時
 */
public function createUser(string $name, int $age, ?string $email = null): User
{
    // 実装
}
```

## 🎨 Laravelコーディングパターン

### Eloquent モデル
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

    // テーブル名（必要時のみ）
    protected $table = 'users';

    // Mass Assignment保護
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    // 非表示にする属性
    protected $hidden = [
        'password',
        'remember_token',
    ];

    // キャスト
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    // リレーション
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    // アクセサ
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    // ミューテータ
    public function setPasswordAttribute(string $value): void
    {
        $this->attributes['password'] = bcrypt($value);
    }

    // スコープ
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
```

### コントローラー
```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::active()->paginate(15);

        return view('users.index', compact('users'));
    }

    public function show(User $user): View
    {
        return view('users.show', compact('user'));
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $user = User::create($request->validated());

        return redirect()->route('users.show', $user)
            ->with('success', 'ユーザーが作成されました。');
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $user->update($request->validated());

        return redirect()->route('users.show', $user)
            ->with('success', 'ユーザー情報を更新しました。');
    }
}
```

### フォームリクエスト
```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // または適切な認可ロジック
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'age' => 'required|integer|min:0|max:150',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => '名前は必須です。',
            'email.required' => 'メールアドレスは必須です。',
            'email.unique' => 'このメールアドレスは既に使用されています。',
        ];
    }
}
```

## 🔧 設定ファイル

### Laravel Pint設定 (pint.json)
```json
{
    "preset": "laravel",
    "rules": {
        "simplified_null_return": true,
        "braces": {
            "position_after_control_structures": "same"
        },
        "no_unused_imports": true
    }
}
```

## ✅ コード品質チェックリスト

### 基本項目
- [ ] PSR-12準拠（Laravel Pintでチェック）
- [ ] 適切な型宣言の使用
- [ ] DocBlockコメント記載
- [ ] 変数・メソッド名は分かりやすく
- [ ] 一つのメソッドは一つの責任のみ

### Laravel固有項目
- [ ] Eloquentリレーションの適切な使用
- [ ] Mass Assignment保護設定
- [ ] バリデーションルールの適用
- [ ] 適切なHTTPステータスコード返却
- [ ] ルート名前付けの一貫性

### セキュリティ項目
- [ ] SQLインジェクション対策（Eloquent使用）
- [ ] XSS対策（Bladeエスケープ）
- [ ] CSRF保護適用
- [ ] 機密情報のログ出力禁止
- [ ] 適切な認証・認可

## 🚨 よくある問題と対策

### N+1問題
```php
// 悪い例
foreach ($users as $user) {
    echo $user->posts->count(); // N+1問題発生
}

// 良い例
$users = User::withCount('posts')->get();
foreach ($users as $user) {
    echo $user->posts_count;
}
```

### Mass Assignment脆弱性
```php
// 危険: 全属性を許可
protected $guarded = [];

// 推奨: 明示的に許可する属性のみ指定
protected $fillable = [
    'name',
    'email',
    'password',
];
```

---
**[← README.md に戻る](../README.md)**