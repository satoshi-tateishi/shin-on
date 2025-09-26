<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use App\Models\InventorySnapshot;
use App\Models\Location;
use App\Models\PhaseEquipment;
use App\Models\RepairRecord;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class InventorySnapshotController extends Controller
{
    /**
     * スナップショット生成API
     */
    public function generateSnapshot(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'snapshot_date' => 'required|date',
            ]);

            $snapshotDate = Carbon::parse($validated['snapshot_date']);

            // 過去の日付は許可しない
            if ($snapshotDate->lt(now()->startOfDay())) {
                return response()->json([
                    'success' => false,
                    'message' => '基準日に過去の日付は指定できません。今日以降の日付を指定してください。',
                ], 400);
            }

            // 統一されたスナップショット生成処理を使用
            InventorySnapshot::generateSnapshot($snapshotDate);

            return response()->json([
                'success' => true,
                'message' => 'スナップショットを生成しました。',
                'snapshot_date' => $snapshotDate->format('Y-m-d'),
                'generated_at' => now()->toISOString(),
            ]);

        } catch (\Exception $e) {
            \Log::error('Snapshot generation failed', [
                'error' => $e->getMessage(),
                'request' => $validated ?? $request->all(),
                'user' => auth()->id() ?? 'guest',
            ]);

            // 競合エラーの場合は409 Conflictを返す
            if (str_contains($e->getMessage(), '別のユーザーが在庫再計算を実行中')) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 409);
            }

            // その他のエラーは500 Internal Server Error
            return response()->json([
                'success' => false,
                'message' => 'スナップショットの生成に失敗しました。',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * 在庫アラート一覧API
     */
    public function getInventoryAlerts(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'as_of_date' => 'nullable|date',
                'severity' => 'nullable|in:error,warning,info',
            ]);

            $asOfDate = Carbon::parse($validated['as_of_date'] ?? now());
            $alerts = [];

            // 機材別アラート
            $equipments = Equipment::active()->get();
            foreach ($equipments as $equipment) {
                $equipmentAlerts = $equipment->checkInventoryAlerts($asOfDate);
                foreach ($equipmentAlerts as $alert) {
                    $alerts[] = array_merge($alert, [
                        'equipment_id' => $equipment->id,
                        'equipment_name' => $equipment->name,
                        'type' => 'equipment',
                    ]);
                }
            }

            // 場所別アラート
            $locations = Location::active()->get();
            foreach ($locations as $location) {
                $locationAlerts = $location->checkInventoryAlerts($asOfDate);
                foreach ($locationAlerts as $alert) {
                    $alerts[] = array_merge($alert, [
                        'location_id' => $location->id,
                        'location_name' => $location->name,
                        'type' => 'location',
                    ]);
                }
            }

            // 重要度フィルタ
            if (!empty($validated['severity'])) {
                $alerts = array_filter($alerts, function ($alert) use ($validated) {
                    return $alert['severity'] === $validated['severity'];
                });
            }

            return response()->json([
                'success' => true,
                'alerts' => array_values($alerts),
                'summary' => [
                    'total' => count($alerts),
                    'error' => count(array_filter($alerts, fn($a) => $a['severity'] === 'error')),
                    'warning' => count(array_filter($alerts, fn($a) => $a['severity'] === 'warning')),
                    'info' => count(array_filter($alerts, fn($a) => $a['severity'] === 'info')),
                ],
                'as_of_date' => $asOfDate->format('Y-m-d'),
            ]);

        } catch (\Exception $e) {
            \Log::error('Inventory alerts retrieval failed', [
                'error' => $e->getMessage(),
                'request' => $validated ?? $request->all(),
                'user' => auth()->id() ?? 'guest',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'アラートの取得に失敗しました。',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * スナップショット一覧API
     */
    public function getSnapshots(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            $query = DB::table('inventory_snapshots')
                ->select('snapshot_date',
                    DB::raw('COUNT(*) as total_equipments'),
                    DB::raw('SUM(CASE WHEN status = "available" THEN 1 ELSE 0 END) as available_count'),
                    DB::raw('SUM(CASE WHEN status = "in_use" THEN 1 ELSE 0 END) as in_use_count'),
                    DB::raw('SUM(CASE WHEN status = "repair" THEN 1 ELSE 0 END) as repair_count'),
                    DB::raw('MAX(created_at) as generated_at')
                );

            if (!empty($validated['start_date'])) {
                $query->where('snapshot_date', '>=', $validated['start_date']);
            }

            if (!empty($validated['end_date'])) {
                $query->where('snapshot_date', '<=', $validated['end_date']);
            }

            $snapshots = $query->groupBy('snapshot_date')
                ->orderBy('snapshot_date', 'desc')
                ->paginate($validated['per_page'] ?? 20);

            return response()->json([
                'success' => true,
                'data' => $snapshots->items(),
                'pagination' => [
                    'current_page' => $snapshots->currentPage(),
                    'last_page' => $snapshots->lastPage(),
                    'per_page' => $snapshots->perPage(),
                    'total' => $snapshots->total(),
                ],
            ]);

        } catch (\Exception $e) {
            \Log::error('Snapshots retrieval failed', [
                'error' => $e->getMessage(),
                'request' => $validated ?? $request->all(),
                'user' => auth()->id() ?? 'guest',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'スナップショット一覧の取得に失敗しました。',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * スナップショット削除API
     */
    public function deleteSnapshot(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'snapshot_date' => 'required|date',
            ]);

            $snapshotDate = $validated['snapshot_date'];

            // 今日のスナップショットは削除不可
            if (Carbon::parse($snapshotDate)->isToday()) {
                return response()->json([
                    'success' => false,
                    'message' => '本日のスナップショットは削除できません。',
                ], 400);
            }

            $deletedCount = InventorySnapshot::where('snapshot_date', $snapshotDate)->delete();

            if ($deletedCount === 0) {
                return response()->json([
                    'success' => false,
                    'message' => '指定された日付のスナップショットが見つかりません。',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => "スナップショットを削除しました。（{$deletedCount}件）",
                'deleted_count' => $deletedCount,
            ]);

        } catch (\Exception $e) {
            \Log::error('Snapshot deletion failed', [
                'error' => $e->getMessage(),
                'request' => $validated ?? $request->all(),
                'user' => auth()->id() ?? 'guest',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'スナップショットの削除に失敗しました。',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

}