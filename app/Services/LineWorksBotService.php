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

    /**
     * JWT (JSON Web Token) を生成
     *
     *
     * @throws Exception
     */
    private function generateJWT(): string
    {
        // Private Keyの読み込み
        if (! file_exists($this->privateKeyPath)) {
            throw new Exception("Private key file not found: {$this->privateKeyPath}");
        }

        $privateKey = file_get_contents($this->privateKeyPath);

        // JWTペイロード作成
        $now = time();
        $payload = [
            'iss' => $this->clientId,              // Client ID
            'sub' => $this->serviceAccount,        // Service Account
            'iat' => $now,                         // 発行時刻
            'exp' => $now + 3600,                  // 有効期限（1時間後）
        ];

        // JWT生成（RS256アルゴリズム）
        try {
            $jwt = JWT::encode($payload, $privateKey, 'RS256');

            return $jwt;
        } catch (Exception $e) {
            Log::error('LINE WORKS JWT generation failed', [
                'error' => $e->getMessage(),
            ]);
            throw new Exception('Failed to generate JWT: '.$e->getMessage());
        }
    }

    /**
     * Access Token を取得（キャッシュ機能付き）
     *
     *
     * @throws Exception
     */
    public function getAccessToken(): string
    {
        // キャッシュからAccess Tokenを取得（キー: lineworks_bot_access_token）
        $cacheKey = 'lineworks_bot_access_token';

        return Cache::remember($cacheKey, 3540, function () {
            // JWT生成
            $jwt = $this->generateJWT();

            // デバッグログ追加
            Log::info('LINE WORKS Bot API - Access Token Request', [
                'auth_url' => $this->authUrl,
                'client_id' => $this->clientId,
                'service_account' => $this->serviceAccount,
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'scope' => 'bot',
                'jwt_length' => strlen($jwt),
            ]);

            // Access Token発行リクエスト
            $response = Http::asForm()->post($this->authUrl, [
                'assertion' => $jwt,
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'scope' => 'bot',
            ]);

            if (! $response->successful()) {
                Log::error('LINE WORKS Access Token request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'request_data' => [
                        'client_id' => $this->clientId,
                        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                        'scope' => 'bot',
                    ],
                ]);
                throw new Exception('Failed to get access token: '.$response->body());
            }

            $data = $response->json();

            return $data['access_token'];
        });
    }

    /**
     * Botメッセージを送信
     *
     * @param  string  $userId  LINE WORKS ID (例: user@domain)
     * @param  string  $message  送信するメッセージ
     *
     * @throws Exception
     */
    public function sendMessage(string $userId, string $message): bool
    {
        $accessToken = $this->getAccessToken();

        // メッセージ送信API呼び出し
        $url = "{$this->apiBaseUrl}/bots/{$this->botId}/users/{$userId}/messages";

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$accessToken,
            'Content-Type' => 'application/json',
        ])->post($url, [
            'content' => [
                'type' => 'text',
                'text' => $message,
            ],
        ]);

        if (! $response->successful()) {
            Log::error('LINE WORKS Bot message send failed', [
                'user_id' => $userId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new Exception('Failed to send message: '.$response->body());
        }

        Log::info('LINE WORKS Bot message sent successfully', [
            'user_id' => $userId,
        ]);

        return true;
    }

    /**
     * OTP（ワンタイムパスワード）メッセージを送信
     *
     * @param  string  $userId  LINE WORKS ID
     * @param  string  $otp  6桁のOTP
     *
     * @throws Exception
     */
    public function sendOtpMessage(string $userId, string $otp): bool
    {
        $message = "ログイン認証コード:\n";
        $message .= $otp."\n\n";
        $message .= '有効期限:10分';

        return $this->sendMessage($userId, $message);
    }

    /**
     * ファイルアップロード用のURLを取得
     *
     * @param  string  $fileName  アップロードするファイル名
     * @return array ['fileId' => string, 'uploadUrl' => string]
     *
     * @throws Exception
     */
    public function getUploadUrl(string $fileName): array
    {
        $accessToken = $this->getAccessToken();

        $url = "{$this->apiBaseUrl}/bots/{$this->botId}/attachments";

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$accessToken,
            'Content-Type' => 'application/json',
        ])->post($url, [
            'fileName' => $fileName,
        ]);

        if (! $response->successful()) {
            Log::error('LINE WORKS Bot getUploadUrl failed', [
                'fileName' => $fileName,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new Exception('Failed to get upload URL: '.$response->body());
        }

        $data = $response->json();

        Log::info('LINE WORKS Bot upload URL obtained', [
            'fileName' => $fileName,
            'fileId' => $data['fileId'] ?? null,
        ]);

        return [
            'fileId' => $data['fileId'],
            'uploadUrl' => $data['uploadUrl'],
        ];
    }

    /**
     * ファイルをアップロード
     *
     * @param  string  $uploadUrl  getUploadUrlで取得したアップロードURL
     * @param  string  $filePath  アップロードするファイルのパス
     * @param  string|null  $originalFileName  元のファイル名（指定しない場合はファイルパスから取得）
     * @return array ['fileId' => string, 'fileName' => string, 'fileSize' => string]
     *
     * @throws Exception
     */
    public function uploadFile(string $uploadUrl, string $filePath, ?string $originalFileName = null): array
    {
        $accessToken = $this->getAccessToken();

        if (! file_exists($filePath)) {
            throw new Exception("File not found: {$filePath}");
        }

        $fileName = $originalFileName ?? basename($filePath);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$accessToken,
        ])->attach('resourceName', $fileName)
            ->attach('FileData', file_get_contents($filePath), $fileName)
            ->post($uploadUrl);

        if (! $response->successful()) {
            Log::error('LINE WORKS Bot uploadFile failed', [
                'fileName' => $fileName,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new Exception('Failed to upload file: '.$response->body());
        }

        $data = $response->json();

        Log::info('LINE WORKS Bot file uploaded', [
            'fileName' => $data['fileName'] ?? null,
            'fileSize' => $data['fileSize'] ?? null,
        ]);

        return [
            'fileId' => $data['fileId'],
            'fileName' => $data['fileName'],
            'fileSize' => $data['fileSize'],
        ];
    }

    /**
     * ファイルメッセージを送信
     *
     * @param  string  $userId  LINE WORKS ID
     * @param  string  $fileId  アップロードしたファイルのID
     *
     * @throws Exception
     */
    public function sendFileMessage(string $userId, string $fileId): bool
    {
        $accessToken = $this->getAccessToken();

        $url = "{$this->apiBaseUrl}/bots/{$this->botId}/users/{$userId}/messages";

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$accessToken,
            'Content-Type' => 'application/json',
        ])->post($url, [
            'content' => [
                'type' => 'file',
                'fileId' => $fileId,
            ],
        ]);

        if (! $response->successful()) {
            Log::error('LINE WORKS Bot file message send failed', [
                'user_id' => $userId,
                'fileId' => $fileId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new Exception('Failed to send file message: '.$response->body());
        }

        Log::info('LINE WORKS Bot file message sent successfully', [
            'user_id' => $userId,
            'fileId' => $fileId,
        ]);

        return true;
    }

    /**
     * PDFファイルをLINE WORKSユーザーに送信（統合メソッド）
     *
     * @param  string  $userId  LINE WORKS ID
     * @param  string  $filePath  送信するPDFファイルのパス
     * @param  string  $fileName  ファイル名
     *
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
}
