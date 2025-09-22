<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use App\Models\EquipmentCategory;
use App\Models\InventorySnapshot;
use App\Models\Location;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class InventoryController extends Controller
{
    /**
     * 在庫管理ダッシュボード表示
     */
    public function index(): View
    {
        $today = now();

        // 基本統計の取得
        $totalEquipments = Equipment::active()->count();
        $totalLocations = Location::active()->count();

        // 最新のスナップショット日付を取得
        $latestSnapshotDate = InventorySnapshot::max('snapshot_date');
        $isSnapshotCurrent = $latestSnapshotDate && Carbon::parse($latestSnapshotDate)->isToday();

        // カテゴリ別統計
        $categoryStats = EquipmentCategory::active()
            ->with(['subcategories' => function ($query) {
                $query->active()->withCount(['equipments' => function ($equipmentQuery) {
                    $equipmentQuery->active();
                }]);
            }])
            ->ordered()
            ->get()
            ->map(function ($category) {
                $category->equipments_count = $category->subcategories->sum('equipments_count');
                return $category;
            });

        // 場所別統計
        $locationStats = Location::active()
            ->withCount(['equipments' => function ($query) {
                $query->active();
            }])
            ->ordered()
            ->get()
            ->map(function ($location) {
                $location->capacity_usage = $location->capacity_usage;
                $location->display_name = $location->display_name; // アクセサー呼び出し
                return $location;
            });

        return view('inventory.index', compact(
            'totalEquipments',
            'totalLocations',
            'latestSnapshotDate',
            'isSnapshotCurrent',
            'categoryStats',
            'locationStats'
        ));
    }

    /**
     * 基準日指定在庫一覧API
     */
    public function getInventory(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'as_of_date' => 'required|date',
                'location_id' => 'nullable|exists:locations,id',
                'category_id' => 'nullable|exists:equipment_categories,id',
                'search' => 'nullable|string|max:255',
                'status_filter' => 'nullable|in:available,low_stock,out_of_stock',
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            $asOfDate = Carbon::parse($validated['as_of_date']);
            $filters = array_filter([
                'location_id' => $validated['location_id'] ?? null,
                'category_id' => $validated['category_id'] ?? null,
                'search' => $validated['search'] ?? null,
            ]);

            // スナップショットが存在しない場合は生成
            $this->ensureSnapshotExists($asOfDate);

            // 在庫データ取得
            $snapshots = InventorySnapshot::getSnapshotsByDate($asOfDate, $filters);

            // ステータスフィルタリング
            if (!empty($validated['status_filter'])) {
                $snapshots = $this->applyStatusFilter($snapshots, $validated['status_filter']);
            }

            // ページネーション
            $perPage = $validated['per_page'] ?? 50;
            $page = $validated['page'] ?? 1;
            $total = $snapshots->count();
            $paginatedSnapshots = $snapshots->slice(($page - 1) * $perPage, $perPage);

            // レスポンス構築
            $inventoryData = $paginatedSnapshots->map(function ($snapshot) use ($asOfDate) {
                return [
                    'equipment_id' => $snapshot->equipment_id,
                    'equipment_name' => $snapshot->equipment->name,
                    'category_name' => $snapshot->equipment->subcategory->category->name ?? 'その他',
                    'subcategory_name' => $snapshot->equipment->subcategory->name ?? 'その他',
                    'total_quantity' => $snapshot->total_quantity,
                    'available_quantity' => $snapshot->quantity,
                    'status_color' => $snapshot->status_color,
                    'status_text' => $snapshot->status_text,
                    'location' => [
                        'id' => $snapshot->location_id,
                        'name' => $snapshot->location->name ?? 'その他',
                        'type' => $snapshot->location->type ?? 'その他',
                    ],
                    'last_movement' => $this->getLastMovement($snapshot->equipment_id, $asOfDate),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $inventoryData,
                'meta' => [
                    'total' => $total,
                    'per_page' => $perPage,
                    'current_page' => $page,
                    'last_page' => ceil($total / $perPage),
                    'from' => ($page - 1) * $perPage + 1,
                    'to' => min($page * $perPage, $total),
                ],
                'as_of_date' => $asOfDate->format('Y-m-d'),
                'snapshot_generated_at' => now()->toISOString(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => '在庫データの取得に失敗しました: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 在庫統計API
     */
    public function getInventoryStats(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'as_of_date' => 'required|date',
            ]);

            $asOfDate = Carbon::parse($validated['as_of_date']);
            $this->ensureSnapshotExists($asOfDate);

            $snapshots = InventorySnapshot::getSnapshotsByDate($asOfDate);

            $stats = [
                'total_items' => $snapshots->count(),
                'total_quantity' => $snapshots->sum('quantity'),
                'total_capacity' => $snapshots->sum('total_quantity'),
                'utilization_rate' => $snapshots->sum('total_quantity') > 0
                    ? round(($snapshots->sum('quantity') / $snapshots->sum('total_quantity')) * 100, 1)
                    : 0,
                'status_distribution' => [
                    'sufficient' => $snapshots->where('status_color', 'green')->count(),
                    'caution' => $snapshots->where('status_color', 'yellow')->count(),
                    'shortage' => $snapshots->where('status_color', 'red')->count(),
                ],
                'category_distribution' => $snapshots->groupBy('equipment.subcategory.category.name')->map(function ($group) {
                    return [
                        'count' => $group->count(),
                        'quantity' => $group->sum('quantity'),
                        'total_quantity' => $group->sum('total_quantity'),
                    ];
                }),
                'location_distribution' => $snapshots->groupBy('location.name')->map(function ($group) {
                    return [
                        'count' => $group->count(),
                        'quantity' => $group->sum('quantity'),
                    ];
                }),
            ];

            return response()->json([
                'success' => true,
                'stats' => $stats,
                'as_of_date' => $asOfDate->format('Y-m-d'),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => '統計データの取得に失敗しました: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 機材別在庫詳細API
     */
    public function getEquipmentInventory(Request $request, Equipment $equipment): JsonResponse
    {
        try {
            $validated = $request->validate([
                'as_of_date' => 'required|date',
            ]);

            $asOfDate = Carbon::parse($validated['as_of_date']);
            $inventoryData = $equipment->getInventoryAsOf($asOfDate);

            // 移動履歴（直近30日）
            $recentMovements = $equipment->equipmentMovements()
                ->where('moved_at', '<=', $asOfDate)
                ->where('moved_at', '>=', $asOfDate->copy()->subDays(30))
                ->with(['fromLocation', 'toLocation', 'user'])
                ->orderBy('moved_at', 'desc')
                ->limit(10)
                ->get();

            // 使用予定（基準日以降）
            $futureUsages = $equipment->phaseEquipments()
                ->whereHas('phase', function ($query) use ($asOfDate) {
                    $query->where('start_date', '>=', $asOfDate);
                })
                ->with(['phase.performance'])
                ->orderBy('created_at')
                ->get();

            // アラート
            $alerts = $equipment->checkInventoryAlerts($asOfDate);

            return response()->json([
                'success' => true,
                'equipment' => [
                    'id' => $equipment->id,
                    'name' => $equipment->name,
                    'management_type' => $equipment->management_type,
                    'category' => $equipment->subcategory->category->name ?? 'その他',
                    'subcategory' => $equipment->subcategory->name ?? 'その他',
                ],
                'inventory' => $inventoryData,
                'recent_movements' => $recentMovements->map(function ($movement) {
                    return [
                        'id' => $movement->id,
                        'movement_type' => $movement->movement_type,
                        'moved_at' => $movement->moved_at->toISOString(),
                        'from_location' => $movement->fromLocation->name ?? null,
                        'to_location' => $movement->toLocation->name ?? null,
                        'user_name' => $movement->user->name ?? 'システム',
                        'note' => $movement->note,
                    ];
                }),
                'future_usages' => $futureUsages->map(function ($usage) {
                    return [
                        'phase_name' => $usage->phase->name,
                        'performance_title' => $usage->phase->performance->title ?? 'その他',
                        'start_date' => $usage->phase->start_date->format('Y-m-d'),
                        'end_date' => $usage->phase->end_date->format('Y-m-d'),
                        'quantity' => $usage->quantity,
                        'status' => $usage->status,
                    ];
                }),
                'alerts' => $alerts,
                'as_of_date' => $asOfDate->format('Y-m-d'),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => '機材詳細の取得に失敗しました: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 場所別在庫API
     */
    public function getLocationInventory(Request $request, Location $location): JsonResponse
    {
        try {
            $validated = $request->validate([
                'as_of_date' => 'required|date',
            ]);

            $asOfDate = Carbon::parse($validated['as_of_date']);
            $inventoryData = $location->getInventoryAsOf($asOfDate);
            $stats = $location->getInventoryStatsAsOf($asOfDate);
            $capacityUsage = $location->capacity_usage;
            $alerts = $location->checkInventoryAlerts($asOfDate);

            return response()->json([
                'success' => true,
                'location' => [
                    'id' => $location->id,
                    'name' => $location->name,
                    'type' => $location->type,
                ],
                'inventory_items' => $inventoryData->map(function ($snapshot) {
                    return [
                        'equipment_id' => $snapshot->equipment_id,
                        'equipment_name' => $snapshot->equipment->name,
                        'category_name' => $snapshot->equipment->subcategory->category->name ?? 'その他',
                        'quantity' => $snapshot->quantity,
                        'total_quantity' => $snapshot->total_quantity,
                        'status_color' => $snapshot->status_color,
                        'status_text' => $snapshot->status_text,
                    ];
                }),
                'stats' => $stats,
                'capacity_usage' => $capacityUsage,
                'alerts' => $alerts,
                'as_of_date' => $asOfDate->format('Y-m-d'),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => '場所別在庫の取得に失敗しました: ' . $e->getMessage(),
            ], 500);
        }
    }

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

            // 未来の日付は許可しない
            if ($snapshotDate->isFuture()) {
                return response()->json([
                    'success' => false,
                    'error' => '未来の日付のスナップショットは生成できません。',
                ], 422);
            }

            InventorySnapshot::generateSnapshot($snapshotDate);

            return response()->json([
                'success' => true,
                'message' => 'スナップショットを生成しました。',
                'snapshot_date' => $snapshotDate->format('Y-m-d'),
                'generated_at' => now()->toISOString(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'スナップショットの生成に失敗しました: ' . $e->getMessage(),
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
            return response()->json([
                'success' => false,
                'error' => 'アラートの取得に失敗しました: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * スナップショット存在確認・生成
     */
    private function ensureSnapshotExists(Carbon $date): void
    {
        $dateString = $date->format('Y-m-d');
        $exists = InventorySnapshot::where('snapshot_date', $dateString)->exists();

        if (!$exists) {
            InventorySnapshot::generateSnapshot($date);
        }
    }

    /**
     * ステータスフィルタ適用
     */
    private function applyStatusFilter($snapshots, string $statusFilter)
    {
        return match ($statusFilter) {
            'available' => $snapshots->where('status_color', 'green'),
            'low_stock' => $snapshots->where('status_color', 'yellow'),
            'out_of_stock' => $snapshots->where('status_color', 'red'),
            default => $snapshots,
        };
    }

    /**
     * 最後の移動記録取得
     */
    private function getLastMovement(int $equipmentId, Carbon $asOfDate): ?array
    {
        $lastMovement = DB::table('equipment_movements')
            ->where('equipment_id', $equipmentId)
            ->where('moved_at', '<=', $asOfDate)
            ->orderBy('moved_at', 'desc')
            ->first();

        if (!$lastMovement) {
            return null;
        }

        return [
            'movement_type' => $lastMovement->movement_type,
            'moved_at' => Carbon::parse($lastMovement->moved_at)->toISOString(),
            'days_ago' => Carbon::parse($lastMovement->moved_at)->diffInDays($asOfDate),
        ];
    }
}
