<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PortalJwtController extends Controller
{
    /**
     * ログインページ → Portal にリダイレクト
     */
    public function login(Request $request)
    {
        $loginUrl = config('portal_jwt.login_url');
        $next = urlencode($request->query('next', url('/dashboard')));
        return redirect($loginUrl . '?next=' . $next);
    }

    /**
     * ログアウト: セッション破棄 → クッキー削除 → Portal ログアウトページへ
     */
    public function logout(Request $request)
    {
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect(config('portal_jwt.logout_url'))
            ->withCookie(cookie()->forget('portal_jwt'));
    }
}
