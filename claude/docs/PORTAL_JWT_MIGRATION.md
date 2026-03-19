# Portal JWT SSO 移行手順 (shin-on)

LINE WORKS SSO + OTP 認証を shin-on Portal JWT SSO に切り替える作業手順書です。
既存の LINE WORKS 関連コードはすべて削除します。

## 移行前後の認証フロー

**移行前（LINE WORKS SSO + OTP）:**
```
/login → LINE WORKS OAuth → ID Token 検証 → OTP 生成・Bot送信 → OTP 入力 → ログイン
```

**移行後（Portal JWT SSO）:**
```
/login → Portal ログインページへリダイレクト → portal_jwt クッキー付きでリダイレクト → 自動ログイン
```

---

## 目次

1. [ファイルの削除](#1-ファイルの削除)
2. [DB マイグレーション](#2-db-マイグレーション)
3. [新規ファイルの作成](#3-新規ファイルの作成)
4. [既存ファイルの修正](#4-既存ファイルの修正)
5. [環境変数](#5-環境変数)
6. [動作確認](#6-動作確認)

---

## 1. ファイルの削除

以下のファイルをすべて削除します。

```bash
# コントローラー
rm app/Http/Controllers/Auth/LineWorksController.php
rm app/Http/Controllers/Auth/TwoFactorController.php

# サービス・プロバイダー
rm app/Services/LineWorksBotService.php
rm app/Socialite/LineWorksProvider.php

# モデル
rm app/Models/TwoFactorLog.php

# ミドルウェア
rm app/Http/Middleware/TwoFactorAuthentication.php

# ビュー
rm resources/views/auth/lineworks-callback.blade.php
rm resources/views/auth/two-factor-challenge.blade.php
```

`app/Socialite/` ディレクトリが空になる場合は削除してください:

```bash
rmdir app/Socialite/
```

---

## 2. DB マイグレーション

### マイグレーションファイルを作成

```bash
php artisan make:migration replace_lineworks_auth_with_portal_jwt
```

作成されたファイルに以下を記述します:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Portal JWT 用 ID カラムを追加（JWT の sub クレーム = UUID）
            $table->string('external_auth_id', 100)->nullable()->after('id');
            $table->index('external_auth_id');

            // LINE WORKS / OTP 関連カラムを削除
            $table->dropColumn([
                'lineworks_id',
                'two_factor_code',
                'two_factor_expires_at',
                'two_factor_locked_until',
                'two_factor_attempts',
            ]);
        });

        // OTP ログテーブルを削除
        Schema::dropIfExists('two_factor_logs');
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['external_auth_id']);
            $table->dropColumn('external_auth_id');

            $table->string('lineworks_id')->nullable()->unique();
            $table->string('two_factor_code')->nullable();
            $table->timestamp('two_factor_expires_at')->nullable();
            $table->timestamp('two_factor_locked_until')->nullable();
            $table->unsignedTinyInteger('two_factor_attempts')->default(0);
        });
    }
};
```

### マイグレーション実行

```bash
php artisan migrate
```

---

## 3. 新規ファイルの作成

### 3-1. `config/portal_jwt.php`

```php
<?php

return [

    // Portal JWKS エンドポイント（公開鍵取得）
    'jwks_url' => env('PORTAL_JWKS_URL', 'https://portal.shin-on1981.com/api/jwks/'),

    // JWT の iss クレームと照合する発行者 URL
    'issuer' => env('PORTAL_JWT_ISSUER', 'https://portal.shin-on1981.com'),

    // JWT の aud クレームと照合するアプリ識別子
    'audience' => env('PORTAL_JWT_AUDIENCE', 'shin-on-db'),

    // 未認証ユーザーのリダイレクト先
    'login_url' => env('PORTAL_LOGIN_URL', 'https://portal.shin-on1981.com/login/'),

    // ログアウト後のリダイレクト先
    'logout_url' => env('PORTAL_LOGOUT_URL', 'https://portal.shin-on1981.com/logout/'),

    // Portal が発行するクッキー名
    'cookie_name' => env('PORTAL_JWT_COOKIE', 'portal_jwt'),

    // JWKS キャッシュ TTL（秒）
    'jwks_cache_ttl' => 3600,

];
```

### 3-2. `app/Auth/PortalJwtException.php`

```php
<?php

namespace App\Auth;

class PortalJwtException extends \Exception
{
}
```

### 3-3. `app/Auth/PortalJwtService.php`

```php
<?php

namespace App\Auth;

use App\Models\User;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PortalJwtService
{
    /**
     * JWT トークンを検証してペイロードを返す。
     *
     * @throws PortalJwtException
     */
    public function validateToken(string $token): \stdClass
    {
        $keys = $this->getPublicKeys();

        try {
            $payload = JWT::decode($token, $keys);
        } catch (\Exception $e) {
            throw new PortalJwtException('JWT validation failed: ' . $e->getMessage());
        }

        // iss (Issuer) 検証
        $expectedIss = config('portal_jwt.issuer');
        if (($payload->iss ?? '') !== $expectedIss) {
            throw new PortalJwtException('Invalid issuer: ' . ($payload->iss ?? '(none)'));
        }

        // aud (Audience) 検証
        $expectedAud = config('portal_jwt.audience');
        $aud = isset($payload->aud) ? (array) $payload->aud : [];
        if (!in_array($expectedAud, $aud)) {
            throw new PortalJwtException('Invalid audience');
        }

        // is_active フラグ検証（Portal 側の無効化に対応）
        if (isset($payload->is_active) && !$payload->is_active) {
            throw new PortalJwtException('User account is inactive');
        }

        return $payload;
    }

    /**
     * JWT ペイロードからユーザーを検索、存在しなければ作成して返す。
     *
     * @throws PortalJwtException
     */
    public function findOrCreateUser(\stdClass $payload): User
    {
        $portalUuid = $payload->sub ?? null;
        $email      = $payload->email ?? null;

        if (!$portalUuid || !$email) {
            throw new PortalJwtException('JWT missing required claims (sub, email)');
        }

        // 1. external_auth_id（= Portal sub UUID）でユーザーを検索
        $user = User::where('external_auth_id', $portalUuid)->first();

        // 2. email で検索して external_auth_id を付与（既存ユーザーの初回移行）
        if (!$user) {
            $user = User::where('email', $email)->first();
            if ($user) {
                $user->external_auth_id = $portalUuid;
                $user->save();
            }
        }

        // 3. 新規ユーザー作成（デフォルト role: viewer）
        if (!$user) {
            $familyName = $payload->family_name ?? '';
            $givenName  = $payload->given_name ?? '';
            $name = trim($familyName . ' ' . $givenName) ?: ($payload->name ?? $email);

            $user = User::create([
                'name'             => $name,
                'email'            => $email,
                'external_auth_id' => $portalUuid,
                'role'             => 'viewer',
                'affiliation'      => 'employee',
                'is_active'        => true,
                'password'         => Hash::make(Str::random(32)),
            ]);
        }

        return $user;
    }

    /**
     * Portal の JWKS から公開鍵を取得する（キャッシュ付き）。
     *
     * @return array<string, \Firebase\JWT\Key>
     * @throws PortalJwtException
     */
    protected function getPublicKeys(): array
    {
        $cacheKey = 'portal_jwt_jwks';
        $ttl = config('portal_jwt.jwks_cache_ttl', 3600);

        // OpenSSLAsymmetricKey はシリアライズ不可のため JWKS JSON をキャッシュし、
        // 鍵オブジェクトへのパースはリクエストごとに行う。
        $jwks = Cache::remember($cacheKey, $ttl, function () {
            $jwksUrl = config('portal_jwt.jwks_url');
            try {
                $response = Http::timeout(5)->get($jwksUrl);
                return $response->json();
            } catch (\Exception $e) {
                throw new PortalJwtException('Failed to fetch JWKS: ' . $e->getMessage());
            }
        });

        if (empty($jwks['keys'])) {
            throw new PortalJwtException('Invalid JWKS response from portal');
        }

        return JWK::parseKeySet($jwks, 'RS256');
    }
}
```

### 3-4. `app/Http/Middleware/PortalJwtAuthenticate.php`

```php
<?php

namespace App\Http\Middleware;

use App\Auth\PortalJwtException;
use App\Auth\PortalJwtService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PortalJwtAuthenticate
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // 未認証の場合のみ JWT クッキーを検証
        if (!auth()->check()) {
            $cookieName = config('portal_jwt.cookie_name', 'portal_jwt');
            $token = $request->cookie($cookieName);

            if ($token) {
                try {
                    $service = app(PortalJwtService::class);
                    $payload = $service->validateToken($token);
                    $user    = $service->findOrCreateUser($payload);
                    auth()->login($user);
                    $request->session()->regenerate();
                } catch (PortalJwtException $e) {
                    Log::warning('portal_jwt validation failed: ' . $e->getMessage());
                }
            }
        }

        // 認証済みかチェック
        if (!auth()->check()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            // Portal のログインページにリダイレクト（?next= で元 URL を伝える）
            $loginUrl = config('portal_jwt.login_url');
            $nextUrl  = urlencode($request->fullUrl());
            return redirect($loginUrl . '?next=' . $nextUrl);
        }

        return $next($request);
    }
}
```

### 3-5. `app/Http/Controllers/Auth/PortalJwtController.php`

```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PortalJwtController extends Controller
{
    /**
     * ログインページ → Portal にリダイレクト
     */
    public function login(Request $request)
    {
        $loginUrl = config('portal_jwt.login_url');
        $next = urlencode($request->query('next', url('/dashboard')));
        return redirect($loginUrl . '?next=' . $next);
    }

    /**
     * ログアウト: セッション破棄 → クッキー削除 → Portal ログアウトページへ
     */
    public function logout(Request $request)
    {
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect(config('portal_jwt.logout_url'))
            ->withCookie(cookie()->forget('portal_jwt'));
    }
}
```

---

## 4. 既存ファイルの修正

### 4-1. `app/Models/User.php`

`$fillable` から LINE WORKS / OTP 関連フィールドを削除し、`external_auth_id` を追加します。
`casts()` から OTP 関連を削除します。`twoFactorLogs()` リレーションを削除します。

```php
// 変更前
protected $fillable = [
    'sort',
    'name',
    'furigana',
    'email',
    'lineworks_id',          // ← 削除
    'icon',
    'two_factor_code',       // ← 削除
    'two_factor_expires_at', // ← 削除
    'two_factor_locked_until', // ← 削除
    'two_factor_attempts',   // ← 削除
    'mobile_phone',
    ...
];

// 変更後
protected $fillable = [
    'sort',
    'name',
    'furigana',
    'email',
    'external_auth_id',      // ← 追加
    'icon',
    'mobile_phone',
    ...
];
```

```php
// 変更前の casts()
'two_factor_expires_at'   => 'datetime',  // ← 削除
'two_factor_locked_until' => 'datetime',  // ← 削除
'two_factor_attempts'     => 'integer',   // ← 削除

// twoFactorLogs() メソッドも削除:
public function twoFactorLogs(): HasMany   // ← 削除
{
    return $this->hasMany(TwoFactorLog::class);
}
```

`TwoFactorLog` の use 文も削除してください（存在する場合）。

### 4-2. `bootstrap/app.php`

**ミドルウェアエイリアスに `portal.auth` を追加**し、**`portal_jwt` クッキーを暗号化除外**します。

```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->prepend(\App\Http\Middleware\TrustProxies::class);
    $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

    // portal_jwt クッキーは Portal が署名済みのため暗号化しない
    $middleware->encryptCookies(except: [
        'portal_jwt',
    ]);

    $middleware->alias([
        'portal.auth'        => \App\Http\Middleware\PortalJwtAuthenticate::class,
        'performance.access' => \App\Http\Middleware\CheckPerformanceAccess::class,
        'role'               => \App\Http\Middleware\CheckRole::class,
    ]);
})
```

### 4-3. `routes/web.php`

**use 文の差し替え:**

```php
// 削除
use App\Http\Controllers\Auth\LineWorksController;
use App\Http\Controllers\Auth\TwoFactorController;

// 追加
use App\Http\Controllers\Auth\PortalJwtController;
```

**認証ルートの差し替え:**

```php
// 削除するルート
Route::prefix('auth/lineworks')->group(function () {
    Route::get('redirect', [LineWorksController::class, 'redirect'])->name('lineworks.redirect');
    Route::get('callback', [LineWorksController::class, 'callback'])->name('lineworks.callback');
    Route::post('process-id-token', [LineWorksController::class, 'processIdToken'])->name('lineworks.process-id-token');
});

Route::prefix('two-factor')->name('two-factor.')->group(function () {
    Route::get('challenge', [TwoFactorController::class, 'show'])->name('show');
    Route::post('verify', [TwoFactorController::class, 'verify'])->name('verify');
    Route::post('resend', [TwoFactorController::class, 'resend'])->name('resend');
});

// 追加するルート（ログイン・ログアウト）
Route::get('/login', [PortalJwtController::class, 'login'])
    ->name('login')
    ->middleware('guest');
```

**ログアウトルートの差し替え（`Route::middleware('auth')` の中）:**

```php
// 削除
Route::post('/logout', [LineWorksController::class, 'logout'])->name('logout');

// 追加
Route::post('/logout', [PortalJwtController::class, 'logout'])->name('logout');
```

**`auth` ミドルウェアを `portal.auth` に変更:**

```php
// 変更前
Route::middleware('auth')->group(function () {

// 変更後
Route::middleware('portal.auth')->group(function () {
```

> **注意:** `portal.auth` ミドルウェアは JWT 検証とゲストリダイレクトを内包しているため、
> `auth` ミドルウェアは不要です。

**既存の `/login` ルート（ファイル末尾付近）を削除:**

```php
// 削除
Route::get('/login', function () {
    return view('auth.login');
})->name('login')->middleware('guest');
```

### 4-4. `config/services.php`

`lineworks` セクションを削除します:

```php
// 削除するブロック
'lineworks' => [
    'client_id' => env('LINEWORKS_CLIENT_ID'),
    ...
],
```

### 4-5. `app/Providers/AppServiceProvider.php`

LINE WORKS 関連の use 文と Socialite 登録を削除します:

```php
// 削除する use 文
use App\Socialite\LineWorksProvider;
use Laravel\Socialite\Facades\Socialite;

// 削除するコード（boot() 内）
Socialite::extend('lineworks', function ($app) {
    $config = $app['config']['services.lineworks'];
    return Socialite::buildProvider(LineWorksProvider::class, $config);
});
```

Socialite を他で使用していない場合は `laravel/socialite` を削除可能です:

```bash
composer remove laravel/socialite
```

---

## 5. 環境変数

### `.env` の変更

**削除する変数:**

```env
LINEWORKS_CLIENT_ID
LINEWORKS_CLIENT_SECRET
LINEWORKS_REDIRECT_URI
LINEWORKS_DOMAIN
LINEWORKS_BOT_ID
LINEWORKS_BOT_SECRET
LINEWORKS_DB_CLIENT_ID
LINEWORKS_DB_CLIENT_SECRET
LINEWORKS_SERVICE_ACCOUNT
LINEWORKS_PRIVATE_KEY_PATH
LINEWORKS_API_BASE_URL
LINEWORKS_AUTH_URL
```

**追加する変数:**

```env
# ── Portal JWT SSO ──────────────────────────────────────────

# Portal JWKS エンドポイント（公開鍵取得）
PORTAL_JWKS_URL=https://portal.shin-on1981.com/api/jwks/

# JWT の iss クレーム検証値
PORTAL_JWT_ISSUER=https://portal.shin-on1981.com

# JWT の aud クレーム検証値（Portal に登録したこのアプリの識別子）
PORTAL_JWT_AUDIENCE=shin-on-db

# 未認証時のリダイレクト先
PORTAL_LOGIN_URL=https://portal.shin-on1981.com/login/

# ログアウト後のリダイレクト先
PORTAL_LOGOUT_URL=https://portal.shin-on1981.com/logout/
```

**開発環境用（Portal をローカルで動かす場合）:**

```env
PORTAL_JWKS_URL=http://host.docker.internal/api/jwks/
PORTAL_LOGIN_URL=http://localhost/login/
PORTAL_LOGOUT_URL=http://localhost/logout/
```

### 不要になるストレージファイル

```bash
# LINE WORKS Bot API の秘密鍵（不要になる）
rm storage/app/lineworks/private_key.pem
rmdir storage/app/lineworks/  # 空なら削除
```

### キャッシュのクリア

```bash
php artisan config:clear
php artisan cache:clear
```

---

## 6. 動作確認

### 設定確認

```bash
php artisan config:clear

# portal_jwt 設定が読めることを確認
php artisan tinker
>>> config('portal_jwt')
```

### JWT 検証テスト（Portal からクッキーを取得済みの場合）

```bash
php artisan tinker

>>> $service = app(\App\Auth\PortalJwtService::class);
>>> $payload = $service->validateToken('portal_jwtクッキーの値');
>>> var_dump($payload);
```

### ログの確認

JWT 検証失敗は `storage/logs/laravel.log` に記録されます:

```bash
tail -f storage/logs/laravel.log | grep portal_jwt
```

### よくあるエラーと対処

| エラーメッセージ | 原因 | 対処 |
|----------------|------|------|
| `Failed to fetch JWKS` | Portal に接続できない | `PORTAL_JWKS_URL` と Portal の疎通を確認 |
| `Invalid issuer` | `iss` クレームが不一致 | `PORTAL_JWT_ISSUER` を Portal の発行者 URL に合わせる |
| `Invalid audience` | `aud` クレームが不一致 | `PORTAL_JWT_AUDIENCE` を Portal に登録したアプリ識別子に合わせる |
| `User account is inactive` | `is_active: false` | Portal 管理画面でユーザーを有効化 |
| JWT は通るがログインできない | クッキーが暗号化されている | `bootstrap/app.php` の `encryptCookies` 設定を確認 |
| ログアウト後にすぐ再ログインされる | `portal_jwt` クッキーが残っている | `cookie()->forget('portal_jwt')` がレスポンスに含まれているか確認 |

---

## 移行後のファイル構成

```
app/
├── Auth/
│   ├── PortalJwtService.php       ← 新規
│   └── PortalJwtException.php     ← 新規
├── Http/
│   ├── Controllers/Auth/
│   │   └── PortalJwtController.php ← 新規
│   └── Middleware/
│       └── PortalJwtAuthenticate.php ← 新規
└── Models/
    └── User.php                   ← 修正（external_auth_id 追加）

config/
└── portal_jwt.php                 ← 新規
```

## 参考

- Portal JWT SSO の仕組みと JWT クレーム仕様: `shin-on_wiki/docs/portal-jwt-sso/README.md`
- 汎用 Laravel 移植ガイド: `shin-on_wiki/docs/portal-jwt-sso/implementation-guide.md`
- shin-on_wiki での実装コード: `shin-on_wiki/app/Access/PortalJwt/`
