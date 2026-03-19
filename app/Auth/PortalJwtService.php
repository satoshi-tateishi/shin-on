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
