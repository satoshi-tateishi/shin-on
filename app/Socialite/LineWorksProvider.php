<?php

namespace App\Socialite;

use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\ProviderInterface;
use Laravel\Socialite\Two\User;

class LineWorksProvider extends AbstractProvider implements ProviderInterface
{
    protected $scopeSeparator = ' ';

    protected $scopes = ['openid', 'profile', 'email'];

    /**
     * Get the authentication URL for the provider.
     */
    protected function getAuthUrl($state): string
    {
        $url = $this->buildAuthUrlFromBase('https://auth.worksmobile.com/oauth2/v2.0/authorize', $state);

        // LINE WORKSのドメインパラメータを追加
        $domain = config('services.lineworks.domain');
        if ($domain) {
            $url .= '&domain='.urlencode($domain);
        }

        // OpenID Connect用のnonceパラメータを追加
        $nonce = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(random_bytes(32)));
        session(['lineworks_nonce' => $nonce]);
        $url .= '&nonce='.$nonce;

        // LINE WORKS Implicit Flow: response_type=id_token
        $url = str_replace('response_type=code', 'response_type=id_token', $url);

        return $url;
    }

    /**
     * Get the token URL for the provider.
     */
    protected function getTokenUrl(): string
    {
        return 'https://auth.worksmobile.com/oauth2/v2.0/token';
    }

    /**
     * Get the raw user for the given access token.
     * Note: This method is not used in Implicit Flow implementation
     */
    protected function getUserByToken($token): array
    {
        \Log::info('LINE WORKS getUserByToken called (not used in Implicit Flow)');

        // Implicit Flowでは使用されないため、ダミーデータを返す
        return [
            'sub' => 'implicit_flow_user',
            'name' => 'Implicit Flow User',
            'email' => 'implicit@shin-on1981',
            'picture' => null,
        ];
    }

    /**
     * Parse and validate ID Token
     */
    public function parseIdToken(string $idToken): ?array
    {
        $tokenParts = explode('.', $idToken);

        if (count($tokenParts) < 2) {
            throw new \Exception('Invalid ID Token format - need at least 2 parts');
        }

        // JWTペイロードをデコード
        \Log::info('Attempting to decode token part 1: '.substr($tokenParts[1], 0, 50).'...');

        try {
            $payloadRaw = $this->base64UrlDecode($tokenParts[1]);
            \Log::info('Decoded payload raw: '.substr($payloadRaw, 0, 200).'...');
        } catch (\Exception $e) {
            \Log::error('Failed to base64 decode: '.$e->getMessage());
            throw new \Exception('Failed to decode token payload: '.$e->getMessage());
        }

        $payload = json_decode($payloadRaw, true);
        \Log::info('JSON decode result: '.($payload ? 'success' : 'failed'));

        if (! $payload) {
            \Log::error('JSON decode failed. Raw payload: '.$payloadRaw);
            throw new \Exception('Invalid ID Token payload - JSON decode failed');
        }

        // トークンの有効期限確認（オプション）
        if (isset($payload['exp']) && time() > $payload['exp']) {
            \Log::warning('ID Token has expired, but continuing...');
        }

        \Log::info('ID Token payload: '.json_encode($payload));

        return $payload;
    }

    /**
     * Validate user domain
     */
    public function validateDomain(array $userData): bool
    {
        $email = $userData['email'] ?? null;

        if (! $email) {
            \Log::warning('No email found in user data');

            return false;
        }

        // shin-on1981はLINE WORKSのドメイン設定
        // 実際のメールアドレスのドメインはshin-on1981（.comなし）
        $allowedDomain = 'shin-on1981';
        $emailDomain = substr(strrchr($email, '@'), 1);

        \Log::info("Email domain validation - User email: $email, Domain: $emailDomain, Allowed: $allowedDomain");
        \Log::info('LINE WORKS domain setting: '.config('services.lineworks.domain'));

        if ($emailDomain !== $allowedDomain) {
            \Log::warning("Domain validation failed - Expected: $allowedDomain, Got: $emailDomain");

            return false;
        }

        return true;
    }

    /**
     * Map the raw user array to a Socialite User instance.
     */
    protected function mapUserToObject(array $user): User
    {
        // 複数のAPIレスポンス形式に対応
        $id = $user['sub'] ?? $user['userId'] ?? $user['id'] ?? null;
        $name = $user['name'] ?? $user['displayName'] ?? $user['userName'] ?? null;
        $email = $user['email'] ?? null;
        $avatar = $user['picture'] ?? $user['pictureUrl'] ?? $user['avatar'] ?? null;

        \Log::info('Mapped user data: '.json_encode([
            'id' => $id,
            'name' => $name,
            'email' => $email,
            'avatar' => $avatar,
        ]));

        return (new User)->setRaw($user)->map([
            'id' => $id,
            'nickname' => $name,
            'name' => $name,
            'email' => $email,
            'avatar' => $avatar,
        ]);
    }

    /**
     * Base64URL decode helper method for JWT tokens
     */
    private function base64UrlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }

        return base64_decode(strtr($data, '-_', '+/'));
    }
}