<?php

namespace App\Http\Middleware;

use App\Models\Performance;
use App\Models\Phase;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPerformanceAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        // admin と editor は常にアクセス可能
        if ($user->role === 'admin' || $user->role === 'editor') {
            return $next($request);
        }

        // ルートから Performance または Phase を取得
        $performance = $request->route('performance');
        $phase = $request->route('phase');

        // Phase から Performance を取得
        if ($phase && ! $performance) {
            $performance = $phase->performance;
        }

        // Performance が存在し、ユーザーが担当者の場合はアクセス可能
        if ($performance instanceof Performance) {
            if ($performance->staff->contains('user_id', $user->id)) {
                return $next($request);
            }
        }

        // Phase が存在し、ユーザーが担当者の場合はアクセス可能
        if ($phase instanceof Phase) {
            // Phase の所属する Performance の担当者かチェック
            if ($phase->performance->staff->contains('user_id', $user->id)) {
                return $next($request);
            }
        }

        // 権限がない場合は403エラー
        abort(403, 'この操作を実行する権限がありません。');
    }
}
