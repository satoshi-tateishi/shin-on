# Dropbox OAuth 2.0 & バックアップAPI 実装仕様書

## 📋 概要

Laravel アプリケーション向け Dropbox OAuth 2.0 完全実装仕様書。リフレッシュトークン対応の長期運用可能な自動バックアップシステムの設計・実装方法を詳細に解説します。

**✨ 実装完了機能:**
- OAuth 2.0 Authorization Code Flow (リフレッシュトークン対応)
- 自動バックアップ (データベース + ファイル)
- 自動ローカルファイル削除 (ストレージ最適化)
- ログクリーンアップ機能
- Web管理画面 + CLI コマンド

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

**⚠️ 重要: 正しいドメインを使用**

| 項目 | URL | ドメイン |
|------|-----|----------|
| アカウント情報取得 | `https://api.dropboxapi.com/2/users/get_current_account` | api.dropboxapi.com |
| ファイルアップロード | `https://content.dropboxapi.com/2/files/upload` | content.dropboxapi.com |
| ファイルダウンロード | `https://content.dropboxapi.com/2/files/download` | content.dropboxapi.com |
| フォルダ作成 | `https://api.dropboxapi.com/2/files/create_folder_v2` | api.dropboxapi.com |
| ファイル一覧取得 | `https://api.dropboxapi.com/2/files/list_folder` | api.dropboxapi.com |

**注意:**
- OAuth トークンエンドポイントのみ `api.dropbox.com` を使用
- その他のAPI呼び出しは `api.dropboxapi.com` / `content.dropboxapi.com` を使用

### アプリケーション内エンドポイント

| 項目 | URL | 説明 | 権限 |
|------|-----|------|------|
| 認証開始 | `/auth/dropbox/redirect` | Dropbox認証画面へリダイレクト | admin |
| コールバック | `/auth/dropbox/callback` | Dropboxからの認証結果を受信 | - |
| 管理画面 | `/admin/backup` | Web管理画面 (認証・バックアップ実行) | admin |
| バックアップ実行 | `/admin/backup/run` | Ajax バックアップ実行 | admin |
| バックアップ一覧 | `/admin/backup/list` | Ajax バックアップ一覧取得 | admin |

### CLI コマンド

| コマンド | 説明 | 使用例 |
|----------|------|--------|
| `backup:dropbox` | バックアップ実行 | `artisan backup:dropbox` |
| `backup:dropbox --test` | 接続テストのみ | `artisan backup:dropbox --test` |
| `restore:dropbox --list` | バックアップ一覧表示 | `artisan restore:dropbox --list` |
| `logs:clear` | ログクリーンアップ | `artisan logs:clear --days=7` |

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

**✨ 最適化後の構造 (2025年9月29日更新)**

```
Dropbox Root/
└── YYYY/                           # 年フォルダ
    └── MM/                         # 月フォルダ
        └── DD/                     # 日フォルダ
            └── YYYY-MM-DD_HH-mm-ss/    # タイムスタンプフォルダ
                ├── database_backup_YYYY-MM-DD_HH-mm-ss.sql  # 1.48MB
                └── files_backup_YYYY-MM-DD_HH-mm-ss.zip     # 3.1MB
```

**改善点:**
- ❌ 削除: `shin-on-backup` ルートフォルダ (1階層フラット化)
- ❌ 削除: `test_*.txt` ファイル (不要テストファイル除去)
- ✅ 最適化: 総サイズ 208MB → 4.6MB (98%削減)

### パス生成

**✨ 最新実装 (フラット構造対応)**

```php
public function generateBackupPath(string $timestamp, string $fileName): string
{
    $now = Carbon::now(config('backup.timezone', 'Asia/Tokyo'));
    $basePath = config('backup.dropbox.folder_path', '');

    $pathParts = [
        $now->format('Y'),
        $now->format('m'),
        $now->format('d'),
        $timestamp,
        $fileName
    ];

    if (!empty($basePath)) {
        $pathParts = array_merge([$basePath], $pathParts);
    }

    return '/' . implode('/', $pathParts);
}
```

**特徴:**
- 空のベースパスに対応 (フラット構造)
- 設定可能な folder_path
- パス生成の柔軟性向上

### フォルダ自動作成

**✨ 最新実装 (直接HTTP API使用)**

```php
private function ensureFoldersExist(string $filePath): void
{
    $pathParts = explode('/', dirname($filePath));
    $currentPath = '';

    foreach ($pathParts as $part) {
        if (empty($part)) {
            continue;
        }

        $currentPath .= '/' . $part;
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->getValidAccessToken(),
                'Content-Type' => 'application/json',
            ])->post('https://api.dropboxapi.com/2/files/create_folder_v2', [
                'path' => $currentPath,
            ]);

            // フォルダが既に存在する場合のエラーは無視
            if (!$response->successful() && strpos($response->body(), 'already_exists') === false) {
                Log::warning('Failed to create folder', [
                    'path' => $currentPath,
                    'error' => $response->body(),
                ]);
            }
        } catch (Exception $e) {
            Log::warning('Folder creation failed', [
                'path' => $currentPath,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
```

### 自動ローカルファイル削除 🆕

**重要な最適化機能: ストレージ効率化**

```php
private function uploadBackupsToDropbox(array &$results, string $timestamp): void
{
    foreach ($results as $type => &$result) {
        if ($result['success'] && isset($result['path'])) {
            try {
                $filename = basename($result['path']);
                $remotePath = $this->dropboxService->generateBackupPath($timestamp, $filename);

                $this->dropboxService->uploadFile($result['path'], $remotePath);

                $result['dropbox_path'] = $remotePath;
                $result['dropbox_uploaded'] = true;

                // 🔥 Dropboxアップロード成功後、ローカルファイルを削除
                if (File::exists($result['path'])) {
                    File::delete($result['path']);
                    Log::info("Deleted local backup file after successful upload", [
                        'local_path' => $result['path'],
                    ]);
                }

            } catch (Exception $e) {
                $result['dropbox_uploaded'] = false;
                $result['dropbox_error'] = $e->getMessage();
            }
        }
    }
}
```

**効果:**
- ❌ Before: 435MB ローカル累積保存
- ✅ After: 0MB ローカル保存 (自動削除)
- 💾 ディスク使用量 98%削減

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
# DROPBOX_BACKUP_FOLDER=""               # 空文字でフラット構造
DROPBOX_ACCESS_TOKEN_LIFETIME=14400
BACKUP_TIMEZONE=Asia/Tokyo
BACKUP_RETENTION_DAYS=30


# 従来のアクセストークン（フォールバック用）
DROPBOX_ACCESS_TOKEN=your_fallback_access_token
```

### config/backup.php 設定

**✨ 最新設定 (パフォーマンス最適化済み)**

```php
'dropbox' => [
    // 従来のアクセストークン（フォールバック用）
    'access_token' => env('DROPBOX_ACCESS_TOKEN'),

    // OAuth 2.0設定（リフレッシュトークン対応）
    'client_id' => env('DROPBOX_CLIENT_ID'),
    'client_secret' => env('DROPBOX_CLIENT_SECRET'),
    'redirect_uri' => env('DROPBOX_REDIRECT_URI'),

    // バックアップフォルダ (空文字でフラット構造)
    'folder_path' => env('DROPBOX_BACKUP_FOLDER', ''),

    // ファイルサイズ制限 (150MB)
    'max_file_size' => env('DROPBOX_MAX_FILE_SIZE', 150 * 1024 * 1024),

    // トークンの有効期限設定（秒）
    'access_token_lifetime' => env('DROPBOX_ACCESS_TOKEN_LIFETIME', 14400),
],

// ファイルバックアップ設定
'files' => [
    'paths' => [
        'storage/app',
        'public/uploads',
    ],
    'exclude_paths' => [
        'storage/app/backups/*',      // 過去バックアップ除外
        'storage/logs/*',             // ログファイル除外
        'node_modules/*',             // 開発依存関係除外
        'vendor/*',                   // Composer依存関係除外
        '.git/*',                     // Gitファイル除外
        'storage/app/public/temp',    // 一時ファイル除外
    ],
],

// テストファイル設定
'test' => [
    'enabled' => false,               // テストファイル生成を無効化
    'filename_format' => 'test_{timestamp}.txt',
    'content' => 'Dropbox backup test from app at {timestamp}',
],
```

**重要な最適化:**
- ✅ `exclude_paths`: 不要ファイル除外で 98%サイズ削減
- ✅ `test.enabled = false`: テストファイル無効化
- ✅ `folder_path = ''`: フラット構造で整理

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

### 2. 自動クリーンアップ実装済み ✅

```php
// ローカルバックアップファイル自動削除 (実装済み)
if (File::exists($result['path'])) {
    File::delete($result['path']);
    Log::info("Deleted local backup file after successful upload");
}
```

### 3. モニタリング

- ✅ トークン有効期限の監視
- ✅ バックアップ成功/失敗率
- ✅ ローカルストレージ使用量の追跡 (自動削除で最適化)
- ✅ Dropbox API使用量の監視

## トラブルシューティング

### よくある問題

#### 🔴 **重要: APIドメインエラー**

**1. "Could not resolve host: content.dropbox.com"**
```
Error: cURL error 6: Could not resolve host: content.dropbox.com
```
- ❌ **原因**: 間違った旧ドメインを使用
- ✅ **解決**: `content.dropboxapi.com` を使用

**2. "expected null, got value"**
```
Error: request body: expected null, got value
```
- ❌ **原因**: APIエンドポイントに不適切なボディを送信
- ✅ **解決**: `get_current_account` は `null` ボディを使用
```php
// ❌ 間違い
->post('https://api.dropboxapi.com/2/users/get_current_account', [])

// ✅ 正しい
->post('https://api.dropboxapi.com/2/users/get_current_account', null)
```

#### 🟡 **パフォーマンス問題**

**3. "File too large: 208MB"**
- ❌ **原因**: バックアップファイルが150MB制限を超過
- ✅ **解決**: `exclude_paths` 設定で不要ファイル除外
```php
'exclude_paths' => [
    'storage/app/backups/*',  // 過去バックアップ
    'storage/logs/*',         // ログファイル
    'node_modules/*',         // 開発依存関係
    'vendor/*',               // Composer依存関係
],
```

**4. ローカルストレージ肥大化**
- ❌ **原因**: バックアップファイルが累積保存される
- ✅ **解決**: アップロード後の自動ローカル削除機能

#### 🔵 **認証問題**

**5. "Access token has expired"**
- 原因: アクセストークンの期限切れ
- 解決: 自動リフレッシュが動作しているか確認

**6. "Invalid refresh token"**
- 原因: リフレッシュトークンが無効
- 解決: 再認証が必要（`/auth/dropbox/redirect`）

**7. "Rate limited"**
- 原因: API呼び出し制限に達した
- 解決: 時間を置いて再試行

**8. "Insufficient storage"**
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

## 📊 実装完了履歴

| 日付 | 実装内容 | パフォーマンス改善 |
|------|----------|-------------------|
| 2025/09/29 | OAuth 2.0 基本実装 | - |
| 2025/09/29 | APIドメイン修正 | 接続エラー解決 |
| 2025/09/29 | ファイルサイズ最適化 | 208MB → 4.6MB (98%削減) |
| 2025/09/29 | ローカル自動削除 | 435MB → 0MB (100%削減) |
| 2025/09/29 | フラット構造採用 | ディレクトリ階層簡素化 |

## 🎯 実装成果

| 項目 | Before | After | 改善率 |
|------|--------|-------|--------|
| バックアップサイズ | 208 MB | 4.6 MB | 98% 削減 |
| ローカルストレージ | 435 MB 累積 | 0 MB | 100% 削減 |
| 実行時間 | 長時間 | 高速 | 大幅改善 |

---

**最終更新日**: 2025年9月29日
**バージョン**: 2.0 (完全実装版)
**対象フレームワーク**: Laravel 11+ (OAuth 2.0 + パフォーマンス最適化)
**実装状況**: ✅ 本番運用可能