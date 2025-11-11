# LINE WORKS Bot API 実装ガイド

## 📋 概要

このドキュメントは、LINE WORKS Bot API を使用したメッセージ送信・ファイル送信機能の実装方法をまとめたものです。他のプロジェクトでも流用可能な汎用的な実装ガイドとして作成されています。

### 対象読者
- LINE WORKS Bot APIを初めて実装する開発者
- 既存のBotに機能を追加したい開発者
- Laravel環境でLINE WORKS連携を実装したい開発者

---

## 🔧 前提条件

### 必要な環境
- PHP 8.2以上
- Laravel 12以上
- LINE WORKS Developer Console アクセス権限
- Bot登録済み

### 必要なパッケージ
```bash
composer require firebase/php-jwt
```

---

## 🔐 認証方式

LINE WORKS Bot APIは **JWT（JSON Web Token）認証** を使用します。

### 認証フロー
1. **JWT生成**: RS256アルゴリズムでJWTを生成
2. **Access Token取得**: JWTを使ってAccess Tokenを取得
3. **API呼び出し**: Access TokenをBearerトークンとして使用

### 環境変数設定

`.env`
```bash
# LINE WORKS BOT API Settings
LINEWORKS_API_BASE_URL=https://www.worksapis.com/v1.0
LINEWORKS_AUTH_URL=https://auth.worksmobile.com/oauth2/v2.0/token
LINEWORKS_BOT_ID=your_bot_id
LINEWORKS_BOT_SECRET=your_bot_secret
LINEWORKS_DB_CLIENT_ID=your_client_id
LINEWORKS_DB_CLIENT_SECRET=your_client_secret
LINEWORKS_SERVICE_ACCOUNT=serviceaccount@yourdomain
LINEWORKS_PRIVATE_KEY_PATH=lineworks/private_key.pem
```

### config/services.php
```php
'lineworks' => [
    // Bot API Settings
    'bot_id' => env('LINEWORKS_BOT_ID'),
    'bot_secret' => env('LINEWORKS_BOT_SECRET'),
    'bot_client_id' => env('LINEWORKS_DB_CLIENT_ID'),
    'bot_client_secret' => env('LINEWORKS_DB_CLIENT_SECRET'),
    'service_account' => env('LINEWORKS_SERVICE_ACCOUNT'),
    'private_key_path' => env('LINEWORKS_PRIVATE_KEY_PATH', 'lineworks/private_key.pem'),
    'api_base_url' => env('LINEWORKS_API_BASE_URL', 'https://www.worksapis.com/v1.0'),
    'auth_url' => env('LINEWORKS_AUTH_URL', 'https://auth.worksmobile.com/oauth2/v2.0/token'),
],
```

---

## 📦 Botサービスクラスの実装

### 基本構造

`app/Services/LineWorksBotService.php`

```php
<?php

namespace App\Services;

use Exception;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LineWorksBotService
{
    private string $clientId;
    private string $clientSecret;
    private string $serviceAccount;
    private string $privateKeyPath;
    private string $apiBaseUrl;
    private string $authUrl;
    private string $botId;

    public function __construct()
    {
        $this->clientId = config('services.lineworks.bot_client_id');
        $this->clientSecret = config('services.lineworks.bot_client_secret');
        $this->serviceAccount = config('services.lineworks.service_account');
        $this->privateKeyPath = storage_path('app/'.config('services.lineworks.private_key_path'));
        $this->apiBaseUrl = config('services.lineworks.api_base_url');
        $this->authUrl = config('services.lineworks.auth_url');
        $this->botId = config('services.lineworks.bot_id');
    }
}
```

---

## 🔑 JWT生成とAccess Token取得

### JWT生成メソッド

```php
/**
 * JWT (JSON Web Token) を生成
 *
 * @throws Exception
 */
private function generateJWT(): string
{
    // Private Keyの読み込み
    if (!file_exists($this->privateKeyPath)) {
        throw new Exception("Private key file not found: {$this->privateKeyPath}");
    }

    $privateKey = file_get_contents($this->privateKeyPath);

    // JWTペイロード作成
    $now = time();
    $payload = [
        'iss' => $this->clientId,       // Client ID
        'sub' => $this->serviceAccount,  // Service Account
        'iat' => $now,                   // 発行時刻
        'exp' => $now + 3600,            // 有効期限（1時間後）
    ];

    // JWT生成（RS256アルゴリズム）
    try {
        $jwt = JWT::encode($payload, $privateKey, 'RS256');
        return $jwt;
    } catch (Exception $e) {
        Log::error('LINE WORKS JWT generation failed', [
            'error' => $e->getMessage(),
        ]);
        throw new Exception('Failed to generate JWT: ' . $e->getMessage());
    }
}
```

### Access Token取得メソッド（キャッシュ付き）

```php
/**
 * Access Token を取得（キャッシュ機能付き）
 *
 * @throws Exception
 */
public function getAccessToken(): string
{
    $cacheKey = 'lineworks_bot_access_token';

    return Cache::remember($cacheKey, 3540, function () {
        $jwt = $this->generateJWT();

        // Access Token発行リクエスト
        $response = Http::asForm()->post($this->authUrl, [
            'assertion' => $jwt,
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'scope' => 'bot',
        ]);

        if (!$response->successful()) {
            Log::error('LINE WORKS Access Token request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new Exception('Failed to get access token: ' . $response->body());
        }

        $data = $response->json();
        return $data['access_token'];
    });
}
```

**ポイント**:
- キャッシュ有効期間: 3540秒（59分）
- Access Tokenの実際の有効期限は1時間なので、少し短めに設定

---

## 💬 テキストメッセージ送信

### 基本的なメッセージ送信

```php
/**
 * Botメッセージを送信
 *
 * @param string $userId LINE WORKS ID (例: user@domain)
 * @param string $message 送信するメッセージ
 * @throws Exception
 */
public function sendMessage(string $userId, string $message): bool
{
    $accessToken = $this->getAccessToken();

    // メッセージ送信API呼び出し
    $url = "{$this->apiBaseUrl}/bots/{$this->botId}/users/{$userId}/messages";

    $response = Http::withHeaders([
        'Authorization' => 'Bearer ' . $accessToken,
        'Content-Type' => 'application/json',
    ])->post($url, [
        'content' => [
            'type' => 'text',
            'text' => $message,
        ],
    ]);

    if (!$response->successful()) {
        Log::error('LINE WORKS Bot message send failed', [
            'user_id' => $userId,
            'status' => $response->status(),
            'body' => $response->body(),
        ]);
        throw new Exception('Failed to send message: ' . $response->body());
    }

    Log::info('LINE WORKS Bot message sent successfully', [
        'user_id' => $userId,
    ]);

    return true;
}
```

### 使用例

```php
$botService = app(LineWorksBotService::class);
$botService->sendMessage('user@yourdomain', 'こんにちは！');
```

---

## 📄 ファイル送信機能

ファイル送信は **3ステップ** で行います：

### ステップ1: アップロードURL取得

```php
/**
 * ファイルアップロード用のURLを取得
 *
 * @param string $fileName アップロードするファイル名
 * @return array ['fileId' => string, 'uploadUrl' => string]
 * @throws Exception
 */
public function getUploadUrl(string $fileName): array
{
    $accessToken = $this->getAccessToken();

    $url = "{$this->apiBaseUrl}/bots/{$this->botId}/attachments";

    $response = Http::withHeaders([
        'Authorization' => 'Bearer ' . $accessToken,
        'Content-Type' => 'application/json',
    ])->post($url, [
        'fileName' => $fileName,
    ]);

    if (!$response->successful()) {
        Log::error('LINE WORKS Bot getUploadUrl failed', [
            'fileName' => $fileName,
            'status' => $response->status(),
            'body' => $response->body(),
        ]);
        throw new Exception('Failed to get upload URL: ' . $response->body());
    }

    $data = $response->json();

    return [
        'fileId' => $data['fileId'],
        'uploadUrl' => $data['uploadUrl'],
    ];
}
```

### ステップ2: ファイルアップロード実行

```php
/**
 * ファイルをアップロード
 *
 * @param string $uploadUrl getUploadUrlで取得したアップロードURL
 * @param string $filePath アップロードするファイルのパス
 * @param string|null $originalFileName 元のファイル名（指定しない場合はファイルパスから取得）
 * @return array ['fileId' => string, 'fileName' => string, 'fileSize' => string]
 * @throws Exception
 */
public function uploadFile(string $uploadUrl, string $filePath, ?string $originalFileName = null): array
{
    $accessToken = $this->getAccessToken();

    if (!file_exists($filePath)) {
        throw new Exception("File not found: {$filePath}");
    }

    $fileName = $originalFileName ?? basename($filePath);

    $response = Http::withHeaders([
        'Authorization' => 'Bearer ' . $accessToken,
    ])->attach('resourceName', $fileName)
      ->attach('FileData', file_get_contents($filePath), $fileName)
      ->post($uploadUrl);

    if (!$response->successful()) {
        Log::error('LINE WORKS Bot uploadFile failed', [
            'fileName' => $fileName,
            'status' => $response->status(),
            'body' => $response->body(),
        ]);
        throw new Exception('Failed to upload file: ' . $response->body());
    }

    $data = $response->json();

    return [
        'fileId' => $data['fileId'],
        'fileName' => $data['fileName'],
        'fileSize' => $data['fileSize'],
    ];
}
```

### ステップ3: ファイルメッセージ送信

```php
/**
 * ファイルメッセージを送信
 *
 * @param string $userId LINE WORKS ID
 * @param string $fileId アップロードしたファイルのID
 * @throws Exception
 */
public function sendFileMessage(string $userId, string $fileId): bool
{
    $accessToken = $this->getAccessToken();

    $url = "{$this->apiBaseUrl}/bots/{$this->botId}/users/{$userId}/messages";

    $response = Http::withHeaders([
        'Authorization' => 'Bearer ' . $accessToken,
        'Content-Type' => 'application/json',
    ])->post($url, [
        'content' => [
            'type' => 'file',
            'fileId' => $fileId,
        ],
    ]);

    if (!$response->successful()) {
        Log::error('LINE WORKS Bot file message send failed', [
            'user_id' => $userId,
            'fileId' => $fileId,
            'status' => $response->status(),
            'body' => $response->body(),
        ]);
        throw new Exception('Failed to send file message: ' . $response->body());
    }

    return true;
}
```

### 統合メソッド（ファイル送信を一括実行）

```php
/**
 * PDFファイルをLINE WORKSユーザーに送信（統合メソッド）
 *
 * @param string $userId LINE WORKS ID
 * @param string $filePath 送信するPDFファイルのパス
 * @param string $fileName ファイル名
 * @throws Exception
 */
public function sendPdfToUser(string $userId, string $filePath, string $fileName): bool
{
    try {
        // 1. アップロードURL取得
        $uploadData = $this->getUploadUrl($fileName);

        // 2. ファイルアップロード（元のファイル名を指定）
        $this->uploadFile($uploadData['uploadUrl'], $filePath, $fileName);

        // 3. ファイルメッセージ送信
        $this->sendFileMessage($userId, $uploadData['fileId']);

        Log::info('LINE WORKS Bot PDF sent successfully', [
            'user_id' => $userId,
            'fileName' => $fileName,
        ]);

        return true;
    } catch (Exception $e) {
        Log::error('LINE WORKS Bot PDF send failed', [
            'user_id' => $userId,
            'fileName' => $fileName,
            'error' => $e->getMessage(),
        ]);
        throw $e;
    }
}
```

---

## 🎯 実装例：フェーズPDF送信機能

### コントローラー実装

`app/Http/Controllers/PhaseController.php`

```php
use App\Services\LineWorksBotService;
use Illuminate\Support\Facades\Storage;

public function sendPdfToLineWorks(Phase $phase): RedirectResponse
{
    $tempFilePath = null;

    try {
        // ユーザー情報取得
        $user = auth()->user();

        if (!$user->lineworks_id) {
            return redirect()->route('phases.show', $phase)
                ->with('error', 'LINE WORKS IDが設定されていません。');
        }

        // PDF生成処理（省略）
        // ...

        // 一時ディレクトリに保存
        $tempDir = 'temp';
        if (!Storage::exists($tempDir)) {
            Storage::makeDirectory($tempDir);
        }

        $tempFileName = uniqid('phase_pdf_') . '.pdf';
        $tempFilePath = storage_path("app/{$tempDir}/{$tempFileName}");

        // PDFを一時ファイルとして保存
        file_put_contents($tempFilePath, $pdf->output());

        // LINE WORKSに送信
        $botService = app(LineWorksBotService::class);
        $botService->sendPdfToUser($user->lineworks_id, $tempFilePath, $filename);

        return redirect()->route('phases.show', $phase)
            ->with('success', 'PDFファイルをLINE WORKSに送信しました。');
    } catch (\Exception $e) {
        Log::error('Failed to send Phase PDF to LINE WORKS', [
            'user_id' => auth()->id(),
            'error' => $e->getMessage(),
        ]);

        return redirect()->route('phases.show', $phase)
            ->with('error', 'PDFの送信に失敗しました: ' . $e->getMessage());
    } finally {
        // 一時ファイルを削除
        if ($tempFilePath && file_exists($tempFilePath)) {
            unlink($tempFilePath);
        }
    }
}
```

### ルート定義

`routes/web.php`

```php
Route::post('phases/{phase}/send-lineworks', [PhaseController::class, 'sendPdfToLineWorks'])
    ->name('phases.send-lineworks')
    ->middleware(['auth', 'performance.access']);
```

---

## ⚠️ 注意事項・制限事項

### API制限
- **アップロードURL有効期限**: 24時間
- **fileId有効期限**: 24時間
- **uploadURL再利用**: 不可（1回限り）
- **ファイルサイズ上限**: 管理画面設定に依存（通常10MB程度）
- **プッシュ通知**: 参加者500名以上のトークルームでは動作しない

### セキュリティ
- Private Keyは `storage/app/` に保存し、Gitにコミットしない
- `.gitignore` に `storage/app/lineworks/` を追加
- Access Tokenは必ずキャッシュを使用（API負荷軽減）

### パフォーマンス
- Access Tokenのキャッシュ時間: 59分（有効期限1時間より短く設定）
- 大量送信時はJob Queue推奨
- ファイルアップロードは同期処理が推奨（エラー検知しやすい）

---

## 🐛 トラブルシューティング

### 1. JWT生成エラー

**エラー**: `Private key file not found`

**原因**: Private Keyファイルが存在しない

**解決策**:
```bash
# storage/app/lineworks/private_key.pem が存在するか確認
ls -la storage/app/lineworks/
```

### 2. Access Token取得失敗

**エラー**: `Failed to get access token`

**原因**:
- Client IDまたはClient Secretが間違っている
- Service Accountが間違っている
- Private Keyが正しくない

**解決策**:
```bash
# .envの設定を確認
php artisan config:clear
php artisan cache:clear
```

### 3. ファイル送信失敗

**エラー**: `Failed to upload file`

**原因**:
- uploadURLの有効期限切れ
- ファイルサイズが大きすぎる
- ファイルが存在しない

**解決策**:
- uploadURLを再取得して即座にアップロード
- ファイルサイズを確認（10MB以下推奨）
- ファイルパスを確認

### 4. メッセージが届かない

**原因**:
- ユーザーIDが間違っている
- Botがユーザーと友達になっていない
- ユーザーがBotをブロックしている

**解決策**:
- LINE WORKS ID（`lineworks_id`）を確認
- ユーザーにBotを友達追加してもらう

---

## 📚 参考リンク

### 公式ドキュメント
- [LINE WORKS API ドキュメント](https://developers.worksmobile.com/jp/docs/api)
- [Bot API リファレンス](https://developers.worksmobile.com/jp/docs/bot-api)
- [認証ガイド](https://developers.worksmobile.com/jp/docs/auth)

### 関連ドキュメント
- [LINE WORKS SSO仕様書](./LINE_WORKS_SSO_Specification.md)
- [環境設定ガイド](../setup/environment.md)

---

## 📝 更新履歴

| 日付 | バージョン | 内容 |
|------|-----------|------|
| 2025-11-11 | 1.0.0 | 初版作成（フェーズPDF送信機能実装に伴う） |

---

**作成者**: Claude Code
**最終更新**: 2025年11月11日
