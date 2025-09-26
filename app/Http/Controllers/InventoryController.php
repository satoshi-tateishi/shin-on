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
use Illuminate\Support\Facades\Cache;
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

        // 在庫状況の再計算を毎回実施（エラー時は無視してビュー表示を継続）
        try {
            InventorySnapshot::generateSnapshot($today);
        } catch (\Exception $e) {
            // エラーが発生してもビューの表示は継続
            \Log::warning('Inventory regeneration failed in index view', [
                'error' => $e->getMessage(),
                'user' => auth()->id() ?? 'guest',
            ]);
        }

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

        // 倉庫別統計（在庫フィルタ表示対象のみ）
        $locationStats = Location::forInventoryFilter()
            ->withCount(['equipments' => function ($query) {
                $query->active();
            }])
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
     * 基準日指定在庫一覧API（グループ化済みデータから取得）
     */
    public function getInventory(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'as_of_date' => 'required|date',
                'location_id' => 'nullable|exists:locations,id',
                'category_id' => 'nullable|exists:equipment_categories,id',
                'search' => 'nullable|string|max:255',
            ]);

            $asOfDate = Carbon::parse($validated['as_of_date']);
            $this->ensureSnapshotExists($asOfDate);

            // スナップショットから在庫情報を取得（既にグループ化済み）
            $query = InventorySnapshot::where('snapshot_date', $asOfDate->format('Y-m-d'));

            // フィルター適用
            if (!empty($validated['location_id'])) {
                $query->where('location_id', $validated['location_id']);
            }

            if (!empty($validated['category_id'])) {
                $query->where('category_name', function ($q) use ($validated) {
                    $category = \App\Models\EquipmentCategory::find($validated['category_id']);
                    return $category ? $category->name : '';
                });
            }

            if (!empty($validated['search'])) {
                $search = $validated['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('equipment_name', 'LIKE', "%{$search}%")
                      ->orWhere('company_numbers', 'LIKE', "%{$search}%");
                });
            }

            $snapshots = $query->orderBy('equipment_name')->get();

            // データ構造をフロントエンド用に変換
            $inventoryData = $snapshots->map(function ($snapshot) {
                return [
                    'equipment' => [
                        'id' => $snapshot->sample_equipment_id,
                        'name' => $snapshot->equipment_name,
                        'company_number' => $snapshot->company_numbers,
                        'subcategory' => [
                            'name' => $snapshot->subcategory_name ?? '未設定',
                            'category' => [
                                'name' => $snapshot->category_name ?? '未設定'
                            ]
                        ]
                    ],
                    'quantity' => $snapshot->quantity,
                    'equipment_id' => $snapshot->sample_equipment_id
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $inventoryData,
                'as_of_date' => $asOfDate->format('Y-m-d'),
            ]);

        } catch (\Exception $e) {
            \Log::error('Inventory retrieval failed', [
                'error' => $e->getMessage(),
                'request' => $validated ?? $request->all(),
                'user' => auth()->id() ?? 'guest',
            ]);

            return response()->json([
                'success' => false,
                'message' => '在庫情報の取得に失敗しました。',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * 在庫統計情報API
     */
    public function getInventoryStats(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'as_of_date' => 'required|date',
                'location_id' => 'nullable|exists:locations,id',
                'category_id' => 'nullable|exists:equipment_categories,id',
            ]);

            $asOfDate = Carbon::parse($validated['as_of_date']);
            $this->ensureSnapshotExists($asOfDate);

            // 基本統計
            $query = InventorySnapshot::where('snapshot_date', $asOfDate->format('Y-m-d'));

            if (! empty($validated['location_id'])) {
                $query->whereHas('equipment.location', function ($q) use ($validated) {
                    $q->where('id', $validated['location_id']);
                });
            }

            if (! empty($validated['category_id'])) {
                $query->whereHas('equipment.subcategory.category', function ($q) use ($validated) {
                    $q->where('id', $validated['category_id']);
                });
            }

            $totalEquipments = $query->count();

            // 数量ベースで統計を計算（statusカラムは存在しないため）
            $availableCount = $query->where('quantity', '>', 0)->count();
            $inUseCount = $query->where('quantity', '=', 0)->count();
            $repairCount = 0; // 修理中は別途RepairRecordテーブルで管理

            return response()->json([
                'success' => true,
                'data' => [
                    'total' => $totalEquipments,
                    'available' => $availableCount,
                    'in_use' => $inUseCount,
                    'repair' => $repairCount,
                    'utilization_rate' => $totalEquipments > 0 ? round(($inUseCount / $totalEquipments) * 100, 1) : 0,
                ],
                'as_of_date' => $asOfDate->format('Y-m-d'),
            ]);

        } catch (\Exception $e) {
            \Log::error('Inventory stats retrieval failed', [
                'error' => $e->getMessage(),
                'request' => $validated ?? $request->all(),
                'user' => auth()->id() ?? 'guest',
            ]);

            return response()->json([
                'success' => false,
                'message' => '在庫統計の取得に失敗しました。',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * 機材個別在庫情報API
     */
    public function getEquipmentInventory(Request $request, Equipment $equipment): JsonResponse
    {
        try {
            $validated = $request->validate([
                'as_of_date' => 'required|date',
            ]);

            $asOfDate = Carbon::parse($validated['as_of_date']);
            $this->ensureSnapshotExists($asOfDate);

            $snapshot = InventorySnapshot::where('snapshot_date', $asOfDate->format('Y-m-d'))
                ->where('equipment_id', $equipment->id)
                ->with([
                    'equipment.subcategory.category',
                    'equipment.location',
                ])
                ->first();

            if (! $snapshot) {
                return response()->json([
                    'success' => false,
                    'message' => '指定された日付の機材在庫情報が見つかりません。',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $snapshot,
                'as_of_date' => $asOfDate->format('Y-m-d'),
            ]);

        } catch (\Exception $e) {
            \Log::error('Equipment inventory retrieval failed', [
                'error' => $e->getMessage(),
                'equipment_id' => $equipment->id,
                'request' => $validated ?? $request->all(),
                'user' => auth()->id() ?? 'guest',
            ]);

            return response()->json([
                'success' => false,
                'message' => '機材在庫情報の取得に失敗しました。',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * 場所別在庫情報API
     */
    public function getLocationInventory(Request $request, Location $location): JsonResponse
    {
        try {
            $validated = $request->validate([
                'as_of_date' => 'required|date',
                'category_id' => 'nullable|exists:equipment_categories,id',
                'search' => 'nullable|string|max:255',
            ]);

            $asOfDate = Carbon::parse($validated['as_of_date']);
            $this->ensureSnapshotExists($asOfDate);

            $query = InventorySnapshot::where('snapshot_date', $asOfDate->format('Y-m-d'))
                ->whereHas('equipment.location', function ($q) use ($location) {
                    $q->where('id', $location->id);
                })
                ->with([
                    'equipment.subcategory.category',
                    'equipment.location',
                ]);

            // フィルター適用
            if (! empty($validated['category_id'])) {
                $query->whereHas('equipment.subcategory.category', function ($q) use ($validated) {
                    $q->where('id', $validated['category_id']);
                });
            }

            if (! empty($validated['search'])) {
                $search = $validated['search'];
                $query->whereHas('equipment', function ($q) use ($search) {
                    $q->where(function ($query) use ($search) {
                        $query->where('name', 'LIKE', "%{$search}%")
                            ->orWhere('company_number', 'LIKE', "%{$search}%")
                            ->orWhere('model_number', 'LIKE', "%{$search}%");
                    });
                });
            }

            $snapshots = $query->orderBy('equipment_id')->get();

            return response()->json([
                'success' => true,
                'data' => $snapshots,
                'location' => $location,
                'as_of_date' => $asOfDate->format('Y-m-d'),
            ]);

        } catch (\Exception $e) {
            \Log::error('Location inventory retrieval failed', [
                'error' => $e->getMessage(),
                'location_id' => $location->id,
                'request' => $validated ?? $request->all(),
                'user' => auth()->id() ?? 'guest',
            ]);

            return response()->json([
                'success' => false,
                'message' => '場所別在庫情報の取得に失敗しました。',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * 倉庫一覧API
     */
    public function getWarehouses(): JsonResponse
    {
        try {
            $warehouses = Location::active()
                ->warehouses()
                ->ordered()
                ->get(['id', 'name', 'type']);

            return response()->json([
                'success' => true,
                'data' => $warehouses,
            ]);

        } catch (\Exception $e) {
            \Log::error('Warehouses retrieval failed', [
                'error' => $e->getMessage(),
                'user' => auth()->id() ?? 'guest',
            ]);

            return response()->json([
                'success' => false,
                'message' => '倉庫一覧の取得に失敗しました。',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * 移動対象カテゴリ一覧API
     */
    public function getTransferableCategories(): JsonResponse
    {
        try {
            $categories = EquipmentCategory::active()
                ->with(['subcategories' => function ($query) {
                    $query->active()->ordered();
                }])
                ->ordered()
                ->get(['id', 'name']);

            return response()->json([
                'success' => true,
                'data' => $categories,
            ]);

        } catch (\Exception $e) {
            \Log::error('Transferable categories retrieval failed', [
                'error' => $e->getMessage(),
                'user' => auth()->id() ?? 'guest',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'カテゴリ一覧の取得に失敗しました。',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * スナップショット存在確認（必要に応じて生成）
     */
    private function ensureSnapshotExists(Carbon $date): void
    {
        $snapshotExists = InventorySnapshot::where('snapshot_date', $date->format('Y-m-d'))->exists();

        if (! $snapshotExists) {
            InventorySnapshot::generateSnapshot($date);
        }
    }

    /**
     * ステータスフィルター適用
     */
    private function applyStatusFilter($snapshots, string $statusFilter)
    {
        switch ($statusFilter) {
            case 'available':
                return $snapshots->where('status', 'available');
            case 'low_stock':
                return $snapshots->where('status', 'low_stock');
            case 'out_of_stock':
                return $snapshots->where('status', 'out_of_stock');
            default:
                return $snapshots;
        }
    }

}