<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;

class EnvEncryptionService
{
    private const CIPHER = 'AES-256-CBC';
    private const PBKDF2_ITERATIONS = 10000;
    private const PBKDF2_HASH_ALGO = 'sha256';
    private const KEY_LENGTH = 32;
    private const VERSION = 'v2';

    /**
     * .envファイルを強暗号化
     */
    public function encrypt(string $plaintext, string $password): array
    {
        try {
            // ランダムなソルトとIV生成
            $salt = random_bytes(16);
            $iv = random_bytes(16);

            // PBKDF2で鍵導出
            $key = hash_pbkdf2(
                self::PBKDF2_HASH_ALGO,
                $password,
                $salt,
                self::PBKDF2_ITERATIONS,
                self::KEY_LENGTH,
                true
            );

            // 暗号化
            $ciphertext = openssl_encrypt(
                $plaintext,
                self::CIPHER,
                $key,
                OPENSSL_RAW_DATA,
                $iv
            );

            if ($ciphertext === false) {
                throw new Exception('Encryption failed');
            }

            // HMAC生成（認証用）
            $hmac = hash_hmac('sha256', $ciphertext, $key, true);

            // 全要素を結合してBase64エンコード
            $package = [
                'version' => self::VERSION,
                'salt' => base64_encode($salt),
                'iv' => base64_encode($iv),
                'hmac' => base64_encode($hmac),
                'data' => base64_encode($ciphertext),
                'metadata' => [
                    'created_at' => now()->toIso8601String(),
                    'app_name' => config('app.name'),
                    'environment' => app()->environment(),
                    'php_version' => PHP_VERSION,
                ]
            ];

            Log::info('Environment file encrypted successfully', [
                'metadata' => $package['metadata'],
                'data_size' => strlen($plaintext),
            ]);

            return $package;

        } catch (Exception $e) {
            Log::error('Environment file encryption failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * 暗号化された.envファイルを復号化
     */
    public function decrypt(array $package, string $password): string
    {
        try {
            // バージョンチェック
            if (!isset($package['version']) || $package['version'] !== self::VERSION) {
                throw new Exception('Unsupported encryption version: ' . ($package['version'] ?? 'unknown'));
            }

            // 必要な要素の存在確認
            $required = ['salt', 'iv', 'hmac', 'data'];
            foreach ($required as $field) {
                if (!isset($package[$field])) {
                    throw new Exception("Missing required field: {$field}");
                }
            }

            // Base64デコード
            $salt = base64_decode($package['salt']);
            $iv = base64_decode($package['iv']);
            $hmac = base64_decode($package['hmac']);
            $ciphertext = base64_decode($package['data']);

            if ($salt === false || $iv === false || $hmac === false || $ciphertext === false) {
                throw new Exception('Invalid base64 encoding in encrypted data');
            }

            // 鍵導出
            $key = hash_pbkdf2(
                self::PBKDF2_HASH_ALGO,
                $password,
                $salt,
                self::PBKDF2_ITERATIONS,
                self::KEY_LENGTH,
                true
            );

            // HMAC検証
            $calculated_hmac = hash_hmac('sha256', $ciphertext, $key, true);
            if (!hash_equals($hmac, $calculated_hmac)) {
                throw new Exception('Authentication failed - data may be corrupted or password incorrect');
            }

            // 復号化
            $plaintext = openssl_decrypt(
                $ciphertext,
                self::CIPHER,
                $key,
                OPENSSL_RAW_DATA,
                $iv
            );

            if ($plaintext === false) {
                throw new Exception('Decryption failed - invalid password or corrupted data');
            }

            Log::info('Environment file decrypted successfully', [
                'metadata' => $package['metadata'] ?? [],
                'decrypted_size' => strlen($plaintext),
            ]);

            return $plaintext;

        } catch (Exception $e) {
            Log::warning('Environment file decryption failed', [
                'error' => $e->getMessage(),
                'metadata' => $package['metadata'] ?? [],
            ]);
            throw $e;
        }
    }

    /**
     * パスワード強度チェック
     */
    public function validatePasswordStrength(string $password): array
    {
        $errors = [];

        if (strlen($password) < 12) {
            $errors[] = 'パスワードは12文字以上必要です';
        }

        if (strlen($password) > 128) {
            $errors[] = 'パスワードは128文字以下である必要があります';
        }

        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = '大文字を含める必要があります';
        }

        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = '小文字を含める必要があります';
        }

        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = '数字を含める必要があります';
        }

        if (!preg_match('/[@$!%*?&#]/', $password)) {
            $errors[] = '特殊文字(@$!%*?&#)を含める必要があります';
        }

        // よくあるパスワードパターンをチェック
        $weakPatterns = [
            '/^(.)\1+$/',  // 同一文字の繰り返し
            '/^(123|abc|qwe)/i',  // 連続文字
            '/password/i',  // "password"が含まれる
            '/admin/i',     // "admin"が含まれる
        ];

        foreach ($weakPatterns as $pattern) {
            if (preg_match($pattern, $password)) {
                $errors[] = '予測しやすいパスワードパターンです';
                break;
            }
        }

        return $errors;
    }

    /**
     * パスワード強度スコア計算（0-100）
     */
    public function calculatePasswordScore(string $password): int
    {
        $score = 0;
        $length = strlen($password);

        // 長さによる基本スコア
        if ($length >= 12) $score += 25;
        if ($length >= 16) $score += 10;
        if ($length >= 20) $score += 10;

        // 文字種による加点
        if (preg_match('/[a-z]/', $password)) $score += 10;
        if (preg_match('/[A-Z]/', $password)) $score += 10;
        if (preg_match('/[0-9]/', $password)) $score += 10;
        if (preg_match('/[@$!%*?&#]/', $password)) $score += 15;

        // 多様性による加点
        $unique_chars = count(array_unique(str_split($password)));
        if ($unique_chars >= 8) $score += 10;
        if ($unique_chars >= 12) $score += 5;

        // 減点要素
        if (preg_match('/(.)\1{2,}/', $password)) $score -= 10; // 3文字以上の連続
        if (preg_match('/(123|abc|qwe)/i', $password)) $score -= 15;

        return max(0, min(100, $score));
    }

    /**
     * 暗号化データの整合性チェック
     */
    public function validateEncryptedData(array $package): bool
    {
        try {
            // 基本構造チェック
            $required = ['version', 'salt', 'iv', 'hmac', 'data'];
            foreach ($required as $field) {
                if (!isset($package[$field])) {
                    return false;
                }
            }

            // Base64デコードテスト
            $fields = ['salt', 'iv', 'hmac', 'data'];
            foreach ($fields as $field) {
                if (base64_decode($package[$field]) === false) {
                    return false;
                }
            }

            // 長さチェック
            if (strlen(base64_decode($package['salt'])) !== 16) return false;
            if (strlen(base64_decode($package['iv'])) !== 16) return false;
            if (strlen(base64_decode($package['hmac'])) !== 32) return false;

            return true;

        } catch (Exception $e) {
            return false;
        }
    }
}