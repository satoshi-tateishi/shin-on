<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    /**
     * コンストラクタ - 権限チェック
     */
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            // admin権限のみ操作履歴にアクセス可能
            if (auth()->user()->role !== 'admin') {
                abort(403, 'この機能へのアクセス権限がありません。');
            }

            return $next($request);
        });
    }

    public function index(Request $request): View
    {
        $filter = $request->get('filter', 'all');
        $userId = $request->get('user_id');

        $query = ActivityLog::with('user')->ordered();

        // ユーザーフィルタリング
        if ($userId) {
            $query->where('user_id', $userId);
        }

        // アクションフィルタリング
        if ($filter !== 'all') {
            $actionPrefixes = match ($filter) {
                'equipment' => ['equipment.'],
                'performance' => ['performance.'],
                'phase' => ['phase.'],
                'login' => ['user.login'],
                default => [],
            };

            if (! empty($actionPrefixes)) {
                $query->where(function ($q) use ($actionPrefixes) {
                    foreach ($actionPrefixes as $prefix) {
                        $q->orWhere('action', 'like', $prefix.'%');
                    }
                });
            }
        }

        $activities = $query->paginate(20)->withQueryString();

        // 統計情報（ユーザーフィルター適用時は該当ユーザーの統計）
        $statsQuery = ActivityLog::query();
        if ($userId) {
            $statsQuery->where('user_id', $userId);
        }

        $stats = [
            'total' => (clone $statsQuery)->count(),
            'equipment' => (clone $statsQuery)->where('action', 'like', 'equipment.%')->count(),
            'performance' => (clone $statsQuery)->where('action', 'like', 'performance.%')->count(),
            'phase' => (clone $statsQuery)->where('action', 'like', 'phase.%')->count(),
            'login' => (clone $statsQuery)->where('action', 'user.login')->count(),
        ];

        // ユーザー一覧（アクティビティがあるユーザーのみ）
        $users = User::whereIn('id', ActivityLog::distinct()->pluck('user_id'))
            ->orderBy('name')
            ->get();

        $selectedUser = $userId ? User::find($userId) : null;

        return view('activity-logs.index', compact('activities', 'filter', 'stats', 'users', 'userId', 'selectedUser'));
    }
}
