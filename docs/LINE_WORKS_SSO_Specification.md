# LINE WORKS SSO 仕様書

## 概要

shin-onアプリケーションは、LINE WORKS OAuth 2.0 OpenID Connect Implicit Flowを使用してシングルサインオン（SSO）認証を実装します。

## 認証フロー

### 1. Implicit Flow (OpenID Connect)

LINE WORKSでは、セキュリティとパフォーマンスの観点から **Implicit Flow** を採用しています。

```
ユーザー → 認証URL → LINE WORKS → コールバック(id_token) → JavaScript処理 → サーバー検証 → ログイン
```

## エンドポイント

### LINE WORKS OAuth 2.0 エンドポイント

| 項目 | URL |
|------|-----|
| 認証エンドポイント | `https://auth.worksmobile.com/oauth2/v2.0/authorize` |
| トークンエンドポイント | `https://auth.worksmobile.com/oauth2/v2.0/token` |

### アプリケーション内エンドポイント

| 項目 | URL | 説明 |
|------|-----|------|
| 認証開始 | `/auth/lineworks` | LINE WORKS認証画面へリダイレクト |
| コールバック | `/auth/lineworks/callback` | LINE WORKSからの認証結果を受信 |
| ID Token処理 | `/auth/lineworks/process-token` | JavaScript経由でID Tokenを処理 |
| ログアウト | `/auth/lineworks/logout` | ログアウト処理 |

## 認証パラメータ

### 認証URL パラメータ

```php
$params = [
    'client_id' => 'YOUR_CLIENT_ID',
    'redirect_uri' => 'https://your-app.com/auth/lineworks/callback',
    'response_type' => 'id_token',  // Implicit Flow
    'scope' => 'openid profile email',
    'state' => 'RANDOM_STATE_VALUE',
    'nonce' => 'RANDOM_NONCE_VALUE',
    'domain' => 'YOUR_LINEWORKS_DOMAIN'
];
```

### 必須パラメータ

| パラメータ | 説明 | 例 |
|-----------|------|-----|
| `client_id` | LINE WORKS アプリのクライアントID | `your_client_id` |
| `redirect_uri` | 認証後のリダイレクト先URL | `https://app.com/auth/lineworks/callback` |
| `response_type` | レスポンスタイプ（Implicit Flow） | `id_token` |
| `scope` | 要求するスコープ | `openid profile email` |
| `state` | CSRF攻撃防止用のランダム値 | `abc123xyz` |
| `nonce` | リプレイ攻撃防止用のランダム値 | `nonce_abc123` |
| `domain` | LINE WORKSドメイン | `shin-on1981` |

## ID Token 構造

LINE WORKSから返されるID TokenはJWT（JSON Web Token）形式です。

### JWT構造

```
Header.Payload.Signature
```

### Payload（ペイロード）

```json
{
    "sub": "unique_user_id",
    "name": "立石 智史",
    "family_name": "立石",
    "given_name": "智史",
    "email": "user@shin-on1981",
    "picture": "https://profile-image-url",
    "iss": "https://auth.worksmobile.com",
    "aud": "your_client_id",
    "iat": 1640995200,
    "exp": 1640998800,
    "nonce": "nonce_abc123"
}
```

### トークンフィールド説明

| フィールド | 説明 | 例 |
|------------|------|-----|
| `sub` | ユーザーの一意識別子 | `unique_user_id_123` |
| `name` | フルネーム（日本語姓名順） | `立石 智史` |
| `family_name` | 姓 | `立石` |
| `given_name` | 名 | `智史` |
| `email` | メールアドレス | `user@shin-on1981` |
| `picture` | プロフィール画像URL | `https://...` |
| `iss` | トークン発行者 | `https://auth.worksmobile.com` |
| `aud` | トークン対象者（Client ID） | `your_client_id` |
| `iat` | 発行時刻（UNIX timestamp） | `1640995200` |
| `exp` | 有効期限（UNIX timestamp） | `1640998800` |
| `nonce` | リプレイ攻撃防止値 | `nonce_abc123` |

## 実装詳細

### 1. カスタム Socialite Provider

**ファイル**: `app/Socialite/LineWorksProvider.php`

```php
class LineWorksProvider extends AbstractProvider implements ProviderInterface
{
    protected $scopes = ['openid', 'profile', 'email'];

    // Implicit Flow用の認証URL生成
    protected function getAuthUrl($state): string
    {
        $url = $this->buildAuthUrlFromBase('https://auth.worksmobile.com/oauth2/v2.0/authorize', $state);
        $url .= '&domain=' . urlencode(config('services.lineworks.domain'));
        $url .= '&nonce=' . $nonce;
        $url = str_replace('response_type=code', 'response_type=id_token', $url);
        return $url;
    }
}
```

### 2. ID Token 検証処理

```php
public function parseIdToken(string $idToken): ?array
{
    $tokenParts = explode('.', $idToken);
    $payloadRaw = $this->base64UrlDecode($tokenParts[1]);
    $payload = json_decode($payloadRaw, true);

    // 有効期限チェック
    if (isset($payload['exp']) && time() > $payload['exp']) {
        throw new \Exception('Token expired');
    }

    return $payload;
}
```

### 3. ドメイン検証

```php
public function validateDomain(array $userData): bool
{
    $email = $userData['email'] ?? null;
    $emailDomain = substr(strrchr($email, '@'), 1);
    $allowedDomain = 'shin-on1981';

    return $emailDomain === $allowedDomain;
}
```

### 4. JavaScript処理（コールバック）

**ファイル**: `resources/views/auth/lineworks-callback.blade.php`

```javascript
// URLフラグメントからid_tokenを取得
const fragment = window.location.hash.substring(1);
const params = new URLSearchParams(fragment);
const idToken = params.get('id_token');
const state = params.get('state');

if (idToken) {
    // サーバーにID Tokenを送信
    fetch('/auth/lineworks/process-token', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            id_token: idToken,
            state: state
        })
    });
}
```

## セキュリティ実装

### 1. State検証（CSRF防止）

```php
// 認証開始時
$state = Str::random(40);
session(['lineworks_oauth_state' => $state]);

// コールバック処理時
if (session('lineworks_oauth_state') !== $request->state) {
    throw new \Exception('State validation failed');
}
```

### 2. Nonce検証（リプレイ攻撃防止）

```php
// 認証開始時
$nonce = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(random_bytes(32)));
session(['lineworks_nonce' => $nonce]);

// ID Token検証時
if ($payload['nonce'] !== session('lineworks_nonce')) {
    throw new \Exception('Nonce validation failed');
}
```

### 3. ドメイン制限

- 許可ドメイン: `shin-on1981`
- 不正ドメインからのアクセスを自動拒否
- エラーメッセージでドメイン要件を明示

## 環境設定

### 必須環境変数

```env
# LINE WORKS OAuth設定
LINEWORKS_CLIENT_ID=your_client_id
LINEWORKS_CLIENT_SECRET=your_client_secret
LINEWORKS_REDIRECT_URI="${APP_URL}/auth/lineworks/callback"
LINEWORKS_DOMAIN=shin-on1981
```

### config/services.php 設定

```php
'lineworks' => [
    'client_id' => env('LINEWORKS_CLIENT_ID'),
    'client_secret' => env('LINEWORKS_CLIENT_SECRET'),
    'redirect' => env('LINEWORKS_REDIRECT_URI'),
    'domain' => env('LINEWORKS_DOMAIN'),
],
```

## ユーザーデータ処理

### lineworks_id フィールドについて

`users` テーブルの `lineworks_id` カラムには、LINE WORKS ID Token の `sub` フィールドの値が格納されます。

#### 格納データ詳細

| 項目 | 内容 |
|------|------|
| **データ型** | VARCHAR(255) |
| **制約** | UNIQUE, NULL許可 |
| **格納値** | ID Token の `sub` フィールド（ユーザー一意識別子） |
| **取得方法** | `$lineWorksUser->getId()` |

#### ID Token内での対応関係

```json
{
    "sub": "unique_user_id_123",  ← この値がlineworks_idに格納
    "name": "立石 智史",
    "email": "user@shin-on1981",
    ...
}
```

#### 利用用途

1. **ユーザー検索**: 既存ユーザーの特定
   ```php
   $user = User::where('lineworks_id', $lineWorksUser->getId())->first();
   ```

2. **アカウント連携**: LINE WORKSアカウントとローカルアカウントの紐付け
3. **重複防止**: 同一LINE WORKSユーザーの重複登録防止

#### 重要な特徴

- **永続性**: ユーザーのメールアドレスや名前が変更されても `sub` は変わらない
- **一意性**: LINE WORKS内でユーザーを一意に識別する
- **セキュリティ**: 外部から推測困難な値

### データベース保存

```php
$user = User::updateOrCreate(
    ['lineworks_id' => $lineWorksUser->getId()],
    [
        'name' => $lineWorksUser->getName(),
        'email' => $this->fixEmailDomain($lineWorksUser->getEmail()),
        'lineworks_token' => $lineWorksUser->token,
        'lineworks_refresh_token' => $lineWorksUser->refreshToken,
        'password' => Hash::make(uniqid()),
        'is_active' => true,
    ]
);
```

### メールドメイン修正

```php
private function fixEmailDomain(?string $email): ?string
{
    if (str_ends_with($email, '@shin-on1981')) {
        return $email . '.com';  // shin-on1981.com に修正
    }
    return $email;
}
```

## エラーハンドリング

### 主要エラーパターン

| エラータイプ | 説明 | ユーザー表示メッセージ |
|-------------|------|----------------------|
| ドメイン検証エラー | 許可されていないドメイン | `アクセス権限がありません。shin-on1981ドメインのメールアドレスでLINE WORKSにログインしてください。` |
| State検証エラー | CSRF攻撃の可能性 | `LINE WORKS認証に失敗しました: State validation failed` |
| トークン解析エラー | 不正なID Token | `LINE WORKS認証に失敗しました: Invalid ID Token format` |
| 期限切れエラー | トークンが期限切れ | `LINE WORKS認証に失敗しました: Token expired` |

## ログ出力

### 認証プロセス監視

```php
\Log::info('LINE WORKS callback started (Implicit Flow)');
\Log::info('ID Token received', ['token_preview' => substr($idToken, 0, 50)]);
\Log::info('Domain validation', ['email' => $email, 'domain' => $emailDomain]);
\Log::info("User logged in successfully: {$user->id}");
```

## テスト要件

### 手動テスト項目

1. **正常認証フロー**
   - LINE WORKS認証画面表示
   - ドメイン内ユーザーでログイン成功
   - ダッシュボードへリダイレクト

2. **セキュリティテスト**
   - ドメイン外ユーザーでアクセス拒否
   - State改ざんで認証失敗
   - 期限切れトークンで認証失敗

3. **エラーハンドリング**
   - ネットワークエラー時の適切な表示
   - 不正なパラメータでの安全な処理

## 運用考慮事項

### 1. モニタリング

- 認証成功/失敗率の監視
- 不正アクセス試行の検知
- パフォーマンス監視（認証完了時間）

### 2. メンテナンス

- LINE WORKS API仕様変更への対応
- セキュリティアップデート適用
- 証明書更新（HTTPS）

### 3. スケーラビリティ

- セッション管理の最適化
- 認証キャッシュ戦略
- 負荷分散環境での状態管理

## トラブルシューティング

### よくある問題

1. **"Domain validation failed"**
   - 原因: LINE WORKSドメイン設定の不一致
   - 解決: `LINEWORKS_DOMAIN`環境変数を確認

2. **"Invalid state parameter"**
   - 原因: セッション設定やCSRF トークンの問題
   - 解決: セッション設定とCSRF設定を確認

3. **"ID Token not found"**
   - 原因: JavaScript処理の失敗
   - 解決: ブラウザコンソールエラーを確認

---

**更新日**: 2025年1月
**バージョン**: 1.0
**対象アプリケーション**: shin-on v1.0