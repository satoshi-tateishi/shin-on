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
