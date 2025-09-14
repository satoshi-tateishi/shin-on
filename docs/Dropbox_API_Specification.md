# Dropbox API 仕様書

## 概要

shin-onアプリケーションは、Dropbox OAuth 2.0 Authorization Code Flowとリフレッシュトークンを使用して、長期運用に対応した自動バックアップシステムを実装します。

## 認証フロー

### OAuth 2.0 Authorization Code Flow (Refresh Token対応)

```
管理者 → 認証URL → Dropbox → Authorization Code → Access Token + Refresh Token → 長期運用
```

### トークン更新フロー

```
Access Token期限切れ → Refresh Token使用 → 新しいAccess Token取得 → 継続運用
```

## エンドポイント

### Dropbox OAuth 2.0 エンドポイント

| 項目 | URL |
|------|-----|
| 認証エンドポイント | `https://www.dropbox.com/oauth2/authorize` |
| トークンエンドポイント | `https://api.dropbox.com/oauth2/token` |

### Dropbox API エンドポイント

| 項目 | URL |
|------|-----|
| アカウント情報取得 | `https://api.dropbox.com/2/users/get_current_account` |
| ファイルアップロード | `https://content.dropbox.com/2/files/upload` |
| ファイルダウンロード | `https://content.dropbox.com/2/files/download` |
| フォルダ作成 | `https://api.dropbox.com/2/files/create_folder_v2` |
| ファイル一覧取得 | `https://api.dropbox.com/2/files/list_folder` |

### アプリケーション内エンドポイント

| 項目 | URL | 説明 | 権限 |
|------|-----|------|------|
| 認証開始 | `/auth/dropbox/redirect` | Dropbox認証画面へリダイレクト | admin |
| コールバック | `/auth/dropbox/callback` | Dropboxからの認証結果を受信 | admin |
| 認証状態取得 | `/api/dropbox/auth-status` | 現在の認証状態をJSON取得 | admin |
| トークンリフレッシュ | `/api/dropbox/refresh-token` | 手動トークンリフレッシュ | admin |
| 認証解除 | `/api/dropbox/revoke-auth` | 認証の無効化 | admin |

## 認証パラメータ

### Authorization URL パラメータ

```php
$params = [
    'client_id' => 'YOUR_CLIENT_ID',
    'redirect_uri' => 'https://your-app.com/auth/dropbox/callback',
    'response_type' => 'code',
    'state' => 'RANDOM_STATE_VALUE',
    'token_access_type' => 'offline',  // リフレッシュトークン取得
    'scope' => 'files.content.write files.content.read account_info.read'
];
```

### 必須パラメータ

| パラメータ | 説明 | 例 |
|-----------|------|-----|
| `client_id` | Dropbox アプリのクライアントID | `your_client_id` |
| `redirect_uri` | 認証後のリダイレクト先URL | `https://app.com/auth/dropbox/callback` |
| `response_type` | レスポンスタイプ（Authorization Code） | `code` |
| `state` | CSRF攻撃防止用のランダム値 | `abc123xyz` |
| `token_access_type` | トークンアクセスタイプ | `offline` |
| `scope` | 要求するスコープ | `files.content.write files.content.read account_info.read` |

## スコープ（権限）

### 使用スコープ

| スコープ | 説明 | 用途 |
|----------|------|------|
| `files.content.write` | ファイル書き込み権限 | バックアップファイルアップロード |
| `files.content.read` | ファイル読み込み権限 | バックアップファイルダウンロード |
| `account_info.read` | アカウント情報読み取り | 接続テスト・アカウント名取得 |

## トークン管理

### データベーステーブル: dropbox_tokens

```sql
CREATE TABLE dropbox_tokens (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    service_name VARCHAR(255) DEFAULT 'backup',
    access_token TEXT NOT NULL,
    access_token_expires_at TIMESTAMP,
    refresh_token TEXT,
    account_id VARCHAR(255),
    account_name VARCHAR(255),
    scope TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    last_refreshed_at TIMESTAMP,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### トークンモデル

**ファイル**: `app/Models/DropboxToken.php`

```php
class DropboxToken extends Model
{
    public function isAccessTokenExpired(): bool
    {
        if (!$this->access_token_expires_at) {
            return false;
        }
        return Carbon::now()->isAfter($this->access_token_expires_at);
    }

    public function hasValidRefreshToken(): bool
    {
        return !empty($this->refresh_token);
    }

    public static function getActiveToken(?string $serviceName = 'backup'): ?self
    {
        return self::where('service_name', $serviceName)
                   ->where('is_active', true)
                   ->first();
    }
}
```

## API実装詳細

### 1. OAuth 2.0 認証フロー

**コントローラー**: `app/Http/Controllers/DropboxAuthController.php`

```php
// Authorization Code → Access Token交換
private function exchangeCodeForTokens(string $code): array
{
    $response = Http::withBasicAuth(
        config('backup.dropbox.client_id'),
        config('backup.dropbox.client_secret')
    )->asForm()->post('https://api.dropbox.com/oauth2/token', [
        'code' => $code,
        'grant_type' => 'authorization_code',
        'redirect_uri' => config('backup.dropbox.redirect_uri'),
    ]);

    return $response->json();
}
```

### 2. トークンリフレッシュ処理

**サービス**: `app/Services/DropboxService.php`

```php
private function refreshAccessToken(): string
{
    $response = Http::withBasicAuth(
        config('backup.dropbox.client_id'),
        config('backup.dropbox.client_secret')
    )->asForm()->post('https://api.dropbox.com/oauth2/token', [
        'grant_type' => 'refresh_token',
        'refresh_token' => $this->tokenModel->refresh_token,
    ]);

    $data = $response->json();

    $this->tokenModel->update([
        'access_token' => $data['access_token'],
        'access_token_expires_at' => Carbon::now()->addSeconds($data['expires_in'] ?? 14400),
        'last_refreshed_at' => Carbon::now(),
    ]);

    return $data['access_token'];
}
```

### 3. ファイルアップロード

```php
public function uploadFile(string $filePath, string $remotePath): bool
{
    $fileContent = file_get_contents($filePath);
    $fileSizeMB = strlen($fileContent) / 1024 / 1024;

    // ファイルサイズ制限チェック（150MB）
    if (strlen($fileContent) >= 150 * 1024 * 1024) {
        throw new Exception("File too large: " . number_format($fileSizeMB, 2) . "MB");
    }

    // 階層フォルダを作成
    $this->ensureFoldersExist($remotePath);

    // Dropbox API v2を使用してアップロード
    $this->client->upload($remotePath, $fileContent, 'overwrite');

    return true;
}
```

### 4. ファイルダウンロード

```php
public function downloadFile(string $remotePath): string
{
    $stream = $this->client->download($remotePath);
    return stream_get_contents($stream);
}
```

### 5. アカウント情報取得

```php
public function getAccountInfo(): array
{
    try {
        $accountInfo = $this->client->getAccountInfo();

        // アカウント名をトークンモデルに保存
        if ($this->tokenModel && empty($this->tokenModel->account_name)) {
            $this->tokenModel->update([
                'account_name' => $accountInfo['name']['display_name'],
                'account_id' => $accountInfo['account_id'],
            ]);
        }

        return $accountInfo;
    } catch (Exception $e) {
        // アクセストークンが無効な場合、リフレッシュを試行
        if ($this->tokenModel && $this->tokenModel->hasValidRefreshToken()) {
            $newAccessToken = $this->refreshAccessToken();
            $this->client = new DropboxClient($newAccessToken);
            return $this->client->getAccountInfo();
        }
        throw $e;
    }
}
```

## バックアップシステム統合

### バックアップファイル階層構造

```
Dropbox Root
└── /shin-on-backup/
    └── YYYY/
        └── MM/
            └── DD/
                └── YYYY-MM-DD_HH-mm-ss/
                    ├── database_backup_YYYY-MM-DD_HH-mm-ss.sql
                    ├── files_backup_YYYY-MM-DD_HH-mm-ss.zip
                    └── test_YYYY-MM-DD_HH-mm-ss.txt
```

### パス生成

```php
public function generateBackupPath(string $timestamp, string $fileName): string
{
    $now = Carbon::now(config('backup.timezone', 'Asia/Tokyo'));

    return sprintf(
        '/%s/%s/%s/%s/%s',
        $now->format('Y'),
        $now->format('m'),
        $now->format('d'),
        $timestamp,
        $fileName
    );
}
```

### フォルダ自動作成

```php
private function ensureFoldersExist(string $filePath): void
{
    $pathParts = explode('/', dirname($filePath));
    $currentPath = '';

    foreach ($pathParts as $part) {
        if (empty($part)) continue;

        $currentPath .= '/' . $part;
        try {
            $this->client->createFolder($currentPath);
        } catch (Exception $e) {
            // フォルダが既に存在する場合は無視
        }
    }
}
```

## バックアップリストア機能

### バックアップフォルダ検索

```php
public function findBackupFolders(string $path = '/'): array
{
    $backups = [];

    try {
        $contents = $this->listFolder($path);

        foreach ($contents['entries'] as $entry) {
            if ($entry['.tag'] === 'folder') {
                $folderName = basename($entry['path_lower']);

                // タイムスタンプフォルダかチェック（YYYY-MM-DD_HH-mm-ss形式）
                if (preg_match('/^\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}$/', $folderName)) {
                    $backups[] = trim($entry['path_lower'], '/');
                } else {
                    // 年/月/日フォルダの場合は再帰的に検索
                    $subBackups = $this->findBackupFolders($entry['path_lower']);
                    $backups = array_merge($backups, $subBackups);
                }
            }
        }
    } catch (Exception $e) {
        // フォルダが存在しない場合は無視
    }

    return $backups;
}
```

## 環境設定

### 必須環境変数

```env
# Dropbox OAuth 2.0設定（リフレッシュトークン対応）
DROPBOX_CLIENT_ID=your_client_id
DROPBOX_CLIENT_SECRET=your_client_secret
DROPBOX_REDIRECT_URI="${APP_URL}/auth/dropbox/callback"

# Dropboxバックアップ設定
DROPBOX_BACKUP_FOLDER=/shin-on-backup
DROPBOX_ACCESS_TOKEN_LIFETIME=14400
BACKUP_TIMEZONE=Asia/Tokyo

# 従来のアクセストークン（フォールバック用）
DROPBOX_ACCESS_TOKEN=your_fallback_access_token
```

### config/backup.php 設定

```php
'dropbox' => [
    // 従来のアクセストークン（フォールバック用）
    'access_token' => env('DROPBOX_ACCESS_TOKEN'),

    // OAuth 2.0設定（リフレッシュトークン対応）
    'client_id' => env('DROPBOX_CLIENT_ID'),
    'client_secret' => env('DROPBOX_CLIENT_SECRET'),
    'redirect_uri' => env('DROPBOX_REDIRECT_URI'),

    // バックアップフォルダ
    'folder_path' => env('DROPBOX_BACKUP_FOLDER', '/shin-on-backups'),

    // トークンの有効期限設定（秒）
    'access_token_lifetime' => env('DROPBOX_ACCESS_TOKEN_LIFETIME', 14400),
],
```

## セキュリティ実装

### 1. State検証（CSRF防止）

```php
// 認証開始時
$state = Str::random(40);
session(['dropbox_oauth_state' => $state]);

// コールバック処理時
if ($request->state !== session('dropbox_oauth_state')) {
    throw new \Exception('Invalid state parameter');
}
```

### 2. 権限制限

- OAuth認証: **admin権限のみ**
- バックアップ実行: **editor・admin権限**
- リストア実行: **admin権限のみ**

### 3. トークン保護

```php
protected $fillable = [
    'service_name',
    'access_token',        // 暗号化推奨
    'access_token_expires_at',
    'refresh_token',       // 暗号化推奨
    'account_id',
    'account_name',
    'scope',
    'is_active',
    'last_refreshed_at',
];

protected $hidden = [
    'access_token',
    'refresh_token',
];
```

## エラーハンドリング

### 主要エラーパターン

| エラータイプ | 説明 | 対応方法 |
|-------------|------|----------|
| `401 Unauthorized` | アクセストークンが無効 | 自動リフレッシュ実行 |
| `403 Forbidden` | 権限不足 | 管理者に権限確認要請 |
| `429 Too Many Requests` | レート制限 | 指数バックオフで再試行 |
| `507 Insufficient Storage` | ストレージ不足 | ユーザーに容量確認要請 |
| `400 Bad Request` | 不正なパラメータ | パラメータ検証とログ出力 |

### エラーレスポンス例

```json
{
    "error": {
        ".tag": "invalid_access_token",
        "summary": "invalid_access_token/..."
    }
}
```

### エラーハンドリング実装

```php
try {
    $result = $this->client->upload($remotePath, $fileContent);
} catch (Exception $e) {
    // アクセストークンエラーの場合はリフレッシュを試行
    if (strpos($e->getMessage(), 'invalid_access_token') !== false) {
        if ($this->tokenModel && $this->tokenModel->hasValidRefreshToken()) {
            $newAccessToken = $this->refreshAccessToken();
            $this->client = new DropboxClient($newAccessToken);
            $result = $this->client->upload($remotePath, $fileContent);
        } else {
            throw new Exception('Access token expired and no refresh token available');
        }
    } else {
        throw $e;
    }
}
```

## API レート制限

### Dropbox API 制限

| API種別 | 制限 |
|---------|------|
| 通常のAPI | 300 requests/app/minute |
| アップロード/ダウンロード | 200 requests/app/minute |
| OAuth | 20 requests/app/minute |

### 制限対応実装

```php
private function handleRateLimit(\Exception $e): void
{
    if (strpos($e->getMessage(), '429') !== false) {
        $retryAfter = $this->extractRetryAfter($e->getMessage()) ?: 60;
        Log::warning("Rate limited, waiting {$retryAfter} seconds");
        sleep($retryAfter);
    }
}
```

## 監視・ログ

### 重要ログポイント

```php
// 認証成功
Log::info('Dropbox OAuth authentication successful', [
    'account_id' => $tokenData['account_id']
]);

// トークンリフレッシュ
Log::info('Successfully refreshed Dropbox access token');

// アップロード成功
Log::info('Backup uploaded successfully', [
    'file_path' => $remotePath,
    'file_size_mb' => round($fileSizeMB, 2)
]);

// エラー
Log::error('Dropbox operation failed', [
    'operation' => 'upload',
    'error' => $e->getMessage(),
    'file_path' => $remotePath
]);
```

## パフォーマンス最適化

### 1. 大容量ファイル対応

```php
// ファイルサイズ制限
if (strlen($fileContent) >= 150 * 1024 * 1024) {
    throw new Exception("File too large: " . number_format($fileSizeMB, 2) . "MB");
}

// 将来的な改善: チャンクアップロード実装
// https://www.dropbox.com/developers/documentation/http/documentation#files-upload_session-start
```

### 2. 接続プール・キープアライブ

```php
// HTTP クライアント最適化
$response = Http::timeout(300)
    ->withOptions([
        'verify' => true,
        'http_errors' => false,
    ])
    ->post($endpoint, $data);
```

## 運用考慮事項

### 1. バックアップ保持期間

```php
'retention' => [
    'days' => env('BACKUP_RETENTION_DAYS', 30),
],
```

### 2. 自動削除（実装予定）

```php
public function cleanupOldBackups(int $retentionDays = 30): int
{
    $cutoffDate = Carbon::now()->subDays($retentionDays);
    // 古いバックアップフォルダを削除する実装
}
```

### 3. モニタリング

- トークン有効期限の監視
- バックアップ成功/失敗率
- ストレージ使用量の追跡
- API使用量の監視

## トラブルシューティング

### よくある問題

1. **"Access token has expired"**
   - 原因: アクセストークンの期限切れ
   - 解決: 自動リフレッシュが動作しているか確認

2. **"Invalid refresh token"**
   - 原因: リフレッシュトークンが無効
   - 解決: 再認証が必要（`/auth/dropbox/redirect`）

3. **"Rate limited"**
   - 原因: API呼び出し制限に達した
   - 解決: 時間を置いて再試行

4. **"Insufficient storage"**
   - 原因: Dropboxストレージ容量不足
   - 解決: 古いバックアップ削除または容量追加

### デバッグコマンド

```bash
# 認証状態確認
./vendor/bin/sail artisan tinker
>>> App\Models\DropboxToken::getActiveToken()

# 接続テスト
>>> app(App\Services\DropboxService::class)->testConnection()

# バックアップテスト
./vendor/bin/sail artisan backup:dropbox --test
```

---

**更新日**: 2025年1月
**バージョン**: 1.0
**対象アプリケーション**: shin-on v1.0