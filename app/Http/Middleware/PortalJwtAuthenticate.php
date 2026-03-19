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
