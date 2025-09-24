<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use App\Models\EquipmentCategory;
use App\Models\EquipmentMovement;
use App\Models\InventorySnapshot;
use App\Models\Location;
use App\Models\PhaseEquipment;
use App\Models\RepairRecord;
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

        // 基本統計の取得（倉庫保管機材のみ）
        $totalEquipments = Equipment::active()
            ->whereHas('location', function ($query) {
                $query->where('type', '倉庫');
            })
            ->count();
        $totalLocations = Location::active()->warehouses()->count(); // 倉庫のみをカウント

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

        // 倉庫別統計（倉庫のみに限定）
        $locationStats = Location::active()
            ->warehouses() // 倉庫のみに限定
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
            if (! empty($validated['status_filter'])) {
                $snapshots = $this->applyStatusFilter($snapshots, $validated['status_filter']);
            }

            // 機材名でグループ化
            $groupedSnapshots = $snapshots->groupBy('equipment.name');

            // ページネーション（グループ単位で）
            $perPage = $validated['per_page'] ?? 50;
            $page = $validated['page'] ?? 1;
            $total = $groupedSnapshots->count();
            $paginatedGroups = $groupedSnapshots->slice(($page - 1) * $perPage, $perPage);

            $inventoryData = $paginatedGroups->map(function ($snapshots, $equipmentName) {
                // 同じ名前の機材の情報を集計
                $firstSnapshot = $snapshots->first();
                $companyNumbers = $snapshots->pluck('equipment.company_number')->filter()->toArray();
                $totalAvailable = $snapshots->sum('quantity');
                $totalCapacity = $snapshots->sum('total_quantity');

                // ステータス判定（最も制限的なステータスを採用）
                $statusColors = $snapshots->pluck('status_color');
                if ($statusColors->contains('red')) {
                    $statusColor = 'red';
                    $statusText = '不足';
                } elseif ($statusColors->contains('yellow')) {
                    $statusColor = 'yellow';
                    $statusText = '注意';
                } else {
                    $statusColor = 'green';
                    $statusText = '十分';
                }

                return [
                    'id' => $firstSnapshot->equipment_id, // 実際の機材ID使用
                    'equipment_id' => $firstSnapshot->equipment_id, // 機材ID明示
                    'equipment_name' => $equipmentName,
                    'company_numbers' => implode(', ', $companyNumbers),
                    'category_name' => $firstSnapshot->equipment->subcategory->category->name ?? 'その他',
                    'subcategory_name' => $firstSnapshot->equipment->subcategory->name ?? 'その他',
                    'total_quantity' => $totalCapacity,
                    'available_quantity' => $totalAvailable,
                    'status_color' => $statusColor,
                    'status_text' => $statusText,
                    'location' => [
                        'name' => $firstSnapshot->location->name ?? 'その他',
                        'type' => $firstSnapshot->location->type ?? 'その他',
                    ],
                    'equipment_count' => $snapshots->count(), // グループ内機材数
                ];
            })->values();

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
                'error' => '在庫データの取得に失敗しました: '.$e->getMessage(),
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
                'error' => '統計データの取得に失敗しました: '.$e->getMessage(),
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
                'error' => '機材詳細の取得に失敗しました: '.$e->getMessage(),
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
                'error' => '場所別在庫の取得に失敗しました: '.$e->getMessage(),
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
                'error' => 'スナップショットの生成に失敗しました: '.$e->getMessage(),
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
            if (! empty($validated['severity'])) {
                $alerts = array_filter($alerts, function ($alert) use ($validated) {
                    return $alert['severity'] === $validated['severity'];
                });
            }

            return response()->json([
                'success' => true,
                'alerts' => array_values($alerts),
                'summary' => [
                    'total' => count($alerts),
                    'error' => count(array_filter($alerts, fn ($a) => $a['severity'] === 'error')),
                    'warning' => count(array_filter($alerts, fn ($a) => $a['severity'] === 'warning')),
                    'info' => count(array_filter($alerts, fn ($a) => $a['severity'] === 'info')),
                ],
                'as_of_date' => $asOfDate->format('Y-m-d'),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'アラートの取得に失敗しました: '.$e->getMessage(),
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

        if (! $exists) {
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
     * 機材使用状況取得API（グループ化対応版）
     */
    public function getEquipmentUsageTest(Request $request, int $equipmentId): JsonResponse
    {
        try {
            \Log::info('Equipment usage API called', [
                'equipment_id' => $equipmentId,
                'as_of_date' => $request->input('as_of_date'),
            ]);

            $asOfDate = Carbon::parse($request->input('as_of_date', now()->format('Y-m-d')));

            $equipment = Equipment::with(['subcategory.category', 'location'])->findOrFail($equipmentId);

            // 同じ名前の機材をすべて取得（グループ化対応）
            $allEquipments = Equipment::with(['subcategory.category', 'location'])
                ->where('name', $equipment->name)
                ->whereHas('location', function ($query) {
                    $query->where('type', '倉庫');
                })
                ->orderBy('company_number')
                ->get();

            $usageInfo = [];

            foreach ($allEquipments as $eq) {
                // 1. 修理中かチェック（in_progressのみ）
                $activeRepair = RepairRecord::where('equipment_id', $eq->id)
                    ->where('status', 'in_progress')
                    ->where('failure_occurred_at', '<=', $asOfDate)
                    ->where(function ($query) use ($asOfDate) {
                        $query->whereNull('completed_at')
                            ->orWhere('completed_at', '>', $asOfDate);
                    })
                    ->first();

                if ($activeRepair) {
                    $usageInfo[] = [
                        'type' => 'repair',
                        'status' => '修理中',
                        'details' => '修理開始: '.($activeRepair->started_at ? Carbon::parse($activeRepair->started_at)->format('Y/m/d') : '未開始'),
                        'company_number' => $eq->company_number,
                    ];

                    continue; // 修理中の場合は使用状況チェックをスキップ
                }

                // 2. フェーズで実際に使用中かチェック（予約済みは除外）
                $activeUsage = PhaseEquipment::with(['phase.performance', 'equipment'])
                    ->where('equipment_id', $eq->id)
                    ->where('status', 'checked_out') // 実際に貸出中のもののみ
                    ->where('checkout_date', '<=', $asOfDate) // 基準日以前に貸出
                    ->where(function ($query) use ($asOfDate) {
                        $query->whereNull('checkin_date') // まだ返却されていない
                            ->orWhere('checkin_date', '>', $asOfDate); // または基準日以降に返却予定
                    })
                    ->get();

                foreach ($activeUsage as $usage) {
                    $usageInfo[] = [
                        'type' => 'phase',
                        'status' => '使用中',
                        'details' => $usage->phase->performance->title.' - '.$usage->phase->name,
                        'company_number' => $usage->equipment->company_number,
                    ];
                }

                // 利用可能な機材はモーダルに表示しない（テーブルに表示されているため）
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'equipment' => [
                        'id' => $equipment->id,
                        'name' => $equipment->name,
                        'company_number' => 'グループ',
                        'subcategory' => $equipment->subcategory->name ?? '',
                        'location' => $equipment->location->name ?? '',
                    ],
                    'usage_info' => $usageInfo,
                    'as_of_date' => $asOfDate->format('Y-m-d'),
                ],
            ]);

        } catch (\Exception $e) {
            \Log::error('Equipment usage API error', [
                'equipment_id' => $equipmentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => '機材使用状況の取得に失敗しました: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * 機材使用状況取得API
     */
    public function getEquipmentUsage(Request $request, int $equipmentId): JsonResponse
    {
        try {
            \Log::info('Equipment usage API called', [
                'equipment_id' => $equipmentId,
                'as_of_date' => $request->input('as_of_date'),
                'user' => auth()->user()?->name ?? 'not authenticated',
            ]);

            $asOfDate = Carbon::parse($request->input('as_of_date', now()->format('Y-m-d')));

            $equipment = Equipment::with(['subcategory', 'location'])->findOrFail($equipmentId);

            $usageInfo = [];

            // 1. 修理中かチェック（in_progressのみ）
            $activeRepair = RepairRecord::where('equipment_id', $equipmentId)
                ->where('status', 'in_progress')
                ->where('failure_occurred_at', '<=', $asOfDate)
                ->where(function ($query) use ($asOfDate) {
                    $query->whereNull('completed_at')
                        ->orWhere('completed_at', '>', $asOfDate);
                })
                ->first();

            if ($activeRepair) {
                $usageInfo[] = [
                    'type' => 'repair',
                    'status' => '修理中',
                    'details' => '修理開始: '.($activeRepair->started_at ? Carbon::parse($activeRepair->started_at)->format('Y/m/d') : '未開始'),
                    'company_number' => $equipment->company_number,
                ];
            }

            // 2. フェーズで使用中かチェック
            $activeUsage = PhaseEquipment::with(['phase.performance', 'equipment'])
                ->where('equipment_id', $equipmentId)
                ->whereIn('status', ['reserved', 'checked_out'])
                ->where('start_date', '<=', $asOfDate)
                ->where(function ($query) use ($asOfDate) {
                    $query->whereNull('end_date')
                        ->orWhere('end_date', '>=', $asOfDate);
                })
                ->get();

            foreach ($activeUsage as $usage) {
                $statusText = $usage->status === 'reserved' ? '予約済み' : '使用中';
                $usageInfo[] = [
                    'type' => 'phase',
                    'status' => $statusText,
                    'details' => $usage->phase->performance->title.' - '.$usage->phase->name,
                    'company_number' => $usage->equipment->company_number,
                ];
            }

            // 3. 利用可能な場合
            if (empty($usageInfo)) {
                $usageInfo[] = [
                    'type' => 'available',
                    'status' => '利用可能',
                    'details' => $equipment->location->name.'保管',
                    'company_number' => $equipment->company_number,
                ];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'equipment' => [
                        'id' => $equipment->id,
                        'name' => $equipment->name,
                        'company_number' => $equipment->company_number,
                        'subcategory' => $equipment->subcategory->name ?? '',
                        'location' => $equipment->location->name ?? '',
                    ],
                    'usage_info' => $usageInfo,
                    'as_of_date' => $asOfDate->format('Y-m-d'),
                ],
            ]);

        } catch (\Exception $e) {
            \Log::error('Equipment usage API error', [
                'equipment_id' => $equipmentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => '機材使用状況の取得に失敗しました: '.$e->getMessage(),
                'debug' => config('app.debug') ? $e->getTraceAsString() : null,
            ], 500);
        }
    }

    /**
     * 倉庫間移動API
     */
    public function transferEquipment(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'equipment_id' => 'required|exists:equipments,id',
                'to_location_id' => 'required|exists:locations,id',
                'note' => 'nullable|string|max:500',
            ]);

            $equipment = Equipment::findOrFail($validated['equipment_id']);

            // 個体管理機材のみ対象
            if ($equipment->management_type !== 'individual') {
                return response()->json([
                    'success' => false,
                    'error' => '数量管理機材の倉庫間移動はサポートされていません。',
                ], 400);
            }

            $fromLocationId = $equipment->location_id;
            $toLocationId = $validated['to_location_id'];

            // 同じ場所への移動はエラー
            if ($fromLocationId == $toLocationId) {
                return response()->json([
                    'success' => false,
                    'error' => '同じ場所への移動はできません。',
                ], 400);
            }

            // 機材の現在地を更新
            $equipment->update(['location_id' => $toLocationId]);

            return response()->json([
                'success' => true,
                'message' => '機材の倉庫間移動が完了しました。',
                'equipment' => [
                    'id' => $equipment->id,
                    'name' => $equipment->name,
                    'from_location' => Location::find($fromLocationId)?->name,
                    'to_location' => Location::find($toLocationId)?->name,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => '倉庫間移動に失敗しました: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * 倉庫一覧取得API（共通利用）
     */
    public function getWarehouses(): JsonResponse
    {
        try {
            $warehouses = Location::active()
                ->warehouses()
                ->ordered()
                ->get(['id', 'name', 'address', 'type'])
                ->map(function ($location) {
                    return [
                        'id' => $location->id,
                        'name' => $location->name,
                        'address' => $location->address,
                        'type' => $location->type,
                    ];
                });

            return response()->json([
                'success' => true,
                'warehouses' => $warehouses,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => '倉庫一覧の取得に失敗しました: '.$e->getMessage(),
            ], 500);
        }
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

        if (! $lastMovement) {
            return null;
        }

        return [
            'movement_type' => $lastMovement->movement_type,
            'moved_at' => Carbon::parse($lastMovement->moved_at)->toISOString(),
            'days_ago' => Carbon::parse($lastMovement->moved_at)->diffInDays($asOfDate),
        ];
    }

    /**
     * 倉庫間移動専用画面表示
     */
    public function transferIndex(): View
    {
        return view('equipment-transfer.index');
    }

    /**
     * 移動可能機材一覧API
     */
    public function getTransferableEquipment(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'location_id' => 'nullable|exists:locations,id',
                'category_id' => 'nullable|exists:equipment_categories,id',
                'search' => 'nullable|string|max:255',
                'status' => 'nullable|in:available,maintenance,repair',
            ]);

            $query = Equipment::with([
                'location',
                'subcategory.category'
            ])
            ->where('management_type', 'individual') // 個体管理機材のみ
            ->where('is_discard', false); // 廃棄されていないもののみ

            // フィルタ適用
            if (!empty($validated['location_id'])) {
                $query->where('location_id', $validated['location_id']);
            }

            if (!empty($validated['category_id'])) {
                $query->whereHas('subcategory.category', function ($q) use ($validated) {
                    $q->where('id', $validated['category_id']);
                });
            }

            if (!empty($validated['search'])) {
                $query->where(function ($q) use ($validated) {
                    $q->where('name', 'like', '%' . $validated['search'] . '%')
                      ->orWhere('company_number', 'like', '%' . $validated['search'] . '%');
                });
            }

            if (!empty($validated['status'])) {
                $query->where('status', $validated['status']);
            }

            $equipment = $query->orderBy('sort')->get();

            // レスポンス用にデータを整形
            $equipmentData = $equipment->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'company_number' => $item->company_number,
                    'manufacturer' => $item->manufacturer,
                    'status' => $item->status,
                    'location' => [
                        'id' => $item->location->id,
                        'name' => $item->location->name,
                        'type' => $item->location->type,
                        'display_name' => $item->location->name
                    ],
                    'subcategory' => [
                        'id' => $item->subcategory->id,
                        'name' => $item->subcategory->name,
                        'category' => [
                            'id' => $item->subcategory->category->id,
                            'name' => $item->subcategory->category->name
                        ]
                    ]
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $equipmentData
            ]);

        } catch (\Exception $e) {
            \Log::error('Get transferable equipment error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => '機材データの取得に失敗しました: ' . $e->getMessage()
            ], 500);
        }
    }
}
