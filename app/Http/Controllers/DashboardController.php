<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\CompanyLogo;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * ダッシュボード表示
     */
    public function index(Request $request)
    {
        $companyLogo = CompanyLogo::getActiveLogo();

        // 最新のアクティビティログを取得（5件のみ）
        $recentActivities = ActivityLog::with('user')
            ->ordered()
            ->limit(5)
            ->get();

        // アクティビティの統計情報
        $activityStats = [
            'total' => ActivityLog::count(),
            'today' => ActivityLog::whereDate('created_at', today())->count(),
        ];

        return view('dashboard', [
            'companyLogo' => $companyLogo?->file_path,
            'recentActivities' => $recentActivities,
            'activityStats' => $activityStats,
        ]);
    }
}
