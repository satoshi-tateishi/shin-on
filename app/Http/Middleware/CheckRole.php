<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * 使用例:
     * - Route::middleware('role:admin') - adminのみ
     * - Route::middleware('role:editor,admin') - editorまたはadmin
     * - Route::middleware('role:general,editor,admin') - viewer以外
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles  許可するロール（カンマ区切り or 複数引数）
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = auth()->user();

        if (! $user) {
            abort(401, '認証が必要です。');
        }

        // カンマ区切りの場合を処理
        $allowedRoles = [];
        foreach ($roles as $role) {
            $allowedRoles = array_merge($allowedRoles, explode(',', $role));
        }
        $allowedRoles = array_map('trim', $allowedRoles);

        if (! in_array($user->role, $allowedRoles, true)) {
            // APIリクエストの場合はJSONで返す
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'この操作を実行する権限がありません。',
                ], 403);
            }

            abort(403, 'この操作を実行する権限がありません。');
        }

        return $next($request);
    }
}
