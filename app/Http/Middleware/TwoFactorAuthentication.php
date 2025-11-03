<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TwoFactorAuthentication
{
    /**
     * Handle an incoming request.
     *
     * 2FA認証が完了していない場合、2FA入力画面にリダイレクト
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 認証済みユーザーがいる場合はスキップ（2FA完了済み）
        if (auth()->check()) {
            return $next($request);
        }

        // 2FA検証中のセッションがある場合
        if (session()->has('two_factor:user_id')) {
            // 2FA画面へのアクセスは許可
            if ($request->routeIs('two-factor.show') ||
                $request->routeIs('two-factor.verify') ||
                $request->routeIs('two-factor.resend')) {
                return $next($request);
            }

            // それ以外のページへのアクセスは2FA画面にリダイレクト
            return redirect()->route('two-factor.show');
        }

        // 2FAセッションがなく、未認証の場合はログイン画面へ
        return redirect()->route('login')
            ->withErrors(['auth' => 'ログインが必要です。']);
    }
}
