<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        // admin権限のみ操作履歴にアクセス可能
        if (auth()->user()->role !== 'admin') {
            abort(403, 'この機能へのアクセス権限がありません。');
        }

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

        $statsResult = (clone $statsQuery)
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN action LIKE 'equipment.%' THEN 1 ELSE 0 END) as equipment,
                SUM(CASE WHEN action LIKE 'performance.%' THEN 1 ELSE 0 END) as performance,
                SUM(CASE WHEN action LIKE 'phase.%' THEN 1 ELSE 0 END) as phase_count,
                SUM(CASE WHEN action = 'user.login' THEN 1 ELSE 0 END) as login
            ")
            ->first();

        $stats = [
            'total' => (int) ($statsResult->total ?? 0),
            'equipment' => (int) ($statsResult->equipment ?? 0),
            'performance' => (int) ($statsResult->performance ?? 0),
            'phase' => (int) ($statsResult->phase_count ?? 0),
            'login' => (int) ($statsResult->login ?? 0),
        ];

        // ユーザー一覧（アクティビティがあるユーザーのみ）
        $users = User::whereIn('id', ActivityLog::distinct()->pluck('user_id'))
            ->orderBy('name')
            ->get();

        $selectedUser = $userId ? User::find($userId) : null;

        return view('activity-logs.index', compact('activities', 'filter', 'stats', 'users', 'userId', 'selectedUser'));
    }
}
