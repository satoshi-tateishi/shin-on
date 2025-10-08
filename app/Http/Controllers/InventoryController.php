<?php

namespace App\Http\Controllers;

use App\Models\CompanyLogo;
use App\Models\Equipment;
use App\Models\EquipmentCategory;
use App\Models\EquipmentMovement;
use App\Models\Location;
use App\Models\PhaseEquipment;
use App\Models\RepairRecord;
use Barryvdh\DomPDF\Facade\Pdf;
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

        // 基本統計の取得（倉庫保管機材のみ）
        $totalEquipments = Equipment::active()
            ->whereHas('location', function ($query) {
                $query->where('type', '倉庫');
            })
            ->count();
        $totalLocations = Location::active()->warehouses()->count(); // 倉庫のみをカウント


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
            'categoryStats',
            'locationStats'
        ));
    }

    /**
     * 在庫データ取得API
     *
     * 機材をname + now_location_idでグループ化し、基準日時点での利用可能数量を算出する
     */
    public function getInventory(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'as_of_date' => 'required|date',
                'location_id' => [
                    'required',
                    'exists:locations,id',
                    function ($attribute, $value, $fail) {
                        $location = Location::find($value);
                        if (!$location || !$location->is_inventory_visible) {
                            $fail('選択された倉庫は在庫表示対象外です。');
                        }
                    }
                ],
                'category_id' => 'nullable|exists:equipment_categories,id',
                'search' => 'nullable|string|max:255',
            ]);

            $asOfDate = Carbon::parse($validated['as_of_date']);

            // 機材を name + now_location_id でグループ化して在庫情報を取得
            $query = Equipment::active()
                ->with(['subcategory.category', 'location'])
                ->select([
                    'name',
                    'now_location_id',
                    DB::raw('MIN(sort) as sort'),
                    DB::raw('COUNT(*) as total_count'),
                    DB::raw('SUM(CASE WHEN management_type = "quantity" THEN quantity ELSE 1 END) as total_quantity'),
                    DB::raw('GROUP_CONCAT(DISTINCT company_number ORDER BY sort SEPARATOR ", ") as company_numbers'),
                    DB::raw('MIN(id) as sample_equipment_id')
                ]);

            // 特定倉庫フィルター適用
            $query->where('now_location_id', $validated['location_id']);

            if (!empty($validated['category_id'])) {
                $query->whereHas('subcategory.category', function ($q) use ($validated) {
                    $q->where('id', $validated['category_id']);
                });
            }

            if (!empty($validated['search'])) {
                $search = $validated['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('company_number', 'LIKE', "%{$search}%");
                });
            }

            $equipments = $query->groupBy(['name', 'now_location_id'])
                               ->orderBy('sort')
                               ->get();

            // 基準日時点での使用中・修理中数量を計算して在庫数量を算出
            $inventoryData = $equipments->map(function ($equipment) use ($asOfDate) {
                // サンプル機材から詳細情報取得
                $sampleEquipment = Equipment::with(['subcategory.category', 'location'])
                    ->find($equipment->sample_equipment_id);

                // 基準日時点での使用中数量を計算
                $inUseCount = DB::table('phase_equipment')
                    ->join('equipments', 'phase_equipment.equipment_id', '=', 'equipments.id')
                    ->where('equipments.name', $equipment->name)
                    ->where('equipments.now_location_id', $equipment->now_location_id)
                    ->where('phase_equipment.status', 'checked_out')
                    ->where('phase_equipment.checkout_date', '<=', $asOfDate->format('Y-m-d H:i:s'))
                    ->where(function ($query) use ($asOfDate) {
                        $query->whereNull('phase_equipment.checkin_date')
                            ->orWhere('phase_equipment.checkin_date', '>', $asOfDate->format('Y-m-d H:i:s'));
                    })
                    ->sum(DB::raw('CASE WHEN equipments.management_type = "quantity" THEN phase_equipment.quantity ELSE 1 END'));

                // 基準日時点での修理中数量を計算
                $repairCount = DB::table('repair_records')
                    ->join('equipments', 'repair_records.equipment_id', '=', 'equipments.id')
                    ->where('equipments.name', $equipment->name)
                    ->where('equipments.now_location_id', $equipment->now_location_id)
                    ->where('repair_records.status', 'in_progress')
                    ->where('repair_records.failure_occurred_at', '<=', $asOfDate->format('Y-m-d H:i:s'))
                    ->where(function ($query) use ($asOfDate) {
                        $query->whereNull('repair_records.completed_at')
                            ->orWhere('repair_records.completed_at', '>', $asOfDate->format('Y-m-d H:i:s'));
                    })
                    ->count();

                $availableQuantity = max(0, $equipment->total_quantity - $inUseCount - $repairCount);

                return [
                    'equipment' => [
                        'id' => $equipment->sample_equipment_id,
                        'name' => $equipment->name,
                        'company_number' => $equipment->company_numbers,
                        'subcategory' => [
                            'name' => $sampleEquipment->subcategory->name ?? '未設定',
                            'category' => [
                                'name' => $sampleEquipment->subcategory->category->name ?? '未設定'
                            ]
                        ]
                    ],
                    'quantity' => $availableQuantity,
                    'equipment_id' => $equipment->sample_equipment_id
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
     * 倉庫一覧取得API
     * 倉庫間移動画面で使用
     */
    public function getWarehouses(): JsonResponse
    {
        try {
            // is_transfer_visible が 1 の倉庫のみ取得
            $warehouses = Location::active()
                ->where('is_transfer_visible', 1)
                ->ordered()
                ->select(['id', 'name', 'type', 'sort'])
                ->get();

            return response()->json([
                'success' => true,
                'warehouses' => $warehouses
            ]);

        } catch (\Exception $e) {
            \Log::error('Warehouse retrieval failed', [
                'error' => $e->getMessage(),
                'user' => auth()->id() ?? 'guest',
            ]);

            return response()->json([
                'success' => false,
                'message' => '倉庫情報の取得に失敗しました。',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * 在庫一覧PDF出力
     */
    public function exportPdf(Request $request)
    {
        try {
            $validated = $request->validate([
                'as_of_date' => 'required|date',
                'location_id' => 'nullable|exists:locations,id', // nullableに変更
                'category_id' => 'nullable|exists:equipment_categories,id',
                'search' => 'nullable|string|max:255',
                'all_locations' => 'nullable|boolean', // 全倉庫出力フラグ
            ]);

            $asOfDate = Carbon::parse($validated['as_of_date']);

            // 全倉庫出力 or 単一倉庫出力
            if (!empty($validated['all_locations'])) {
                // 全倉庫の在庫を取得
                $locations = Location::forInventoryFilter()->get();
            } else {
                // 単一倉庫の在庫を取得
                $location = Location::findOrFail($validated['location_id']);
                if (!$location->is_inventory_visible) {
                    throw new \Exception('選択された倉庫は在庫表示対象外です。');
                }
                $locations = collect([$location]);
            }

            // 各倉庫ごとに在庫データを取得
            $locationInventories = $locations->map(function ($location) use ($asOfDate, $validated) {
                // 在庫データ取得
                $query = Equipment::active()
                    ->with(['subcategory.category', 'location'])
                    ->select([
                        'name',
                        'now_location_id',
                        DB::raw('MIN(sort) as sort'),
                        DB::raw('COUNT(*) as total_count'),
                        DB::raw('SUM(CASE WHEN management_type = "quantity" THEN quantity ELSE 1 END) as total_quantity'),
                        DB::raw('GROUP_CONCAT(DISTINCT company_number ORDER BY sort SEPARATOR ", ") as company_numbers'),
                        DB::raw('MIN(id) as sample_equipment_id')
                    ]);

                // 特定倉庫フィルター適用
                $query->where('now_location_id', $location->id);

                if (!empty($validated['category_id'])) {
                    $query->whereHas('subcategory.category', function ($q) use ($validated) {
                        $q->where('id', $validated['category_id']);
                    });
                }

                if (!empty($validated['search'])) {
                    $search = $validated['search'];
                    $query->where(function ($q) use ($search) {
                        $q->where('name', 'LIKE', "%{$search}%")
                          ->orWhere('company_number', 'LIKE', "%{$search}%");
                    });
                }

                $equipments = $query->groupBy(['name', 'now_location_id'])
                                   ->orderBy('sort')
                                   ->get();

                // 基準日時点での使用中・修理中数量を計算して在庫数量を算出
                $inventoryData = $equipments->map(function ($equipment) use ($asOfDate) {
                // サンプル機材から詳細情報取得
                $sampleEquipment = Equipment::with(['subcategory.category', 'location'])
                    ->find($equipment->sample_equipment_id);

                // 基準日時点での使用中数量を計算
                $inUseCount = DB::table('phase_equipment')
                    ->join('equipments', 'phase_equipment.equipment_id', '=', 'equipments.id')
                    ->where('equipments.name', $equipment->name)
                    ->where('equipments.now_location_id', $equipment->now_location_id)
                    ->where('phase_equipment.status', 'checked_out')
                    ->where('phase_equipment.checkout_date', '<=', $asOfDate->format('Y-m-d H:i:s'))
                    ->where(function ($query) use ($asOfDate) {
                        $query->whereNull('phase_equipment.checkin_date')
                            ->orWhere('phase_equipment.checkin_date', '>', $asOfDate->format('Y-m-d H:i:s'));
                    })
                    ->sum(DB::raw('CASE WHEN equipments.management_type = "quantity" THEN phase_equipment.quantity ELSE 1 END'));

                // 基準日時点での修理中数量を計算
                $repairCount = DB::table('repair_records')
                    ->join('equipments', 'repair_records.equipment_id', '=', 'equipments.id')
                    ->where('equipments.name', $equipment->name)
                    ->where('equipments.now_location_id', $equipment->now_location_id)
                    ->where('repair_records.status', 'in_progress')
                    ->where('repair_records.failure_occurred_at', '<=', $asOfDate->format('Y-m-d H:i:s'))
                    ->where(function ($query) use ($asOfDate) {
                        $query->whereNull('repair_records.completed_at')
                            ->orWhere('repair_records.completed_at', '>', $asOfDate->format('Y-m-d H:i:s'));
                    })
                    ->count();

                $availableQuantity = max(0, $equipment->total_quantity - $inUseCount - $repairCount);

                return [
                    'equipment' => [
                        'id' => $equipment->sample_equipment_id,
                        'name' => $equipment->name,
                        'company_number' => $equipment->company_numbers,
                        'subcategory' => [
                            'name' => $sampleEquipment->subcategory->name ?? '未設定',
                            'category' => [
                                'name' => $sampleEquipment->subcategory->category->name ?? '未設定'
                            ]
                        ]
                    ],
                    'quantity' => $availableQuantity,
                    'equipment_id' => $equipment->sample_equipment_id
                ];
            })->toArray();

                return [
                    'locationName' => $location->name,
                    'inventoryData' => $inventoryData,
                ];
            })->toArray();

            // アクティブなロゴを取得
            $companyLogo = CompanyLogo::getActiveLogo();
            $logoPath = $companyLogo ? public_path('storage/' . $companyLogo->file_path) : null;

            // PDFデータ準備
            $data = [
                'asOfDate' => now()->format('Y年m月d日 H:i'),
                'locationInventories' => $locationInventories,
                'logoPath' => $logoPath,
            ];

            // PDF生成
            $pdf = Pdf::loadView('inventory.pdf', $data);

            // DOMPDFの設定を取得して日本語フォントを設定
            $pdf->getDomPDF()->set_option('isHtml5ParserEnabled', true);
            $pdf->getDomPDF()->set_option('isFontSubsettingEnabled', true);
            $pdf->getDomPDF()->set_option('isPhpEnabled', true); // PHPスクリプト有効化

            $pdf->setPaper('A4', 'portrait');

            // ファイル名生成
            if (!empty($validated['all_locations'])) {
                $filename = sprintf('在庫一覧_全倉庫_%s.pdf', $asOfDate->format('Ymd'));
            } else {
                $filename = sprintf('在庫一覧_%s_%s.pdf', $locations->first()->name, $asOfDate->format('Ymd'));
            }

            return $pdf->download($filename);

        } catch (\Exception $e) {
            \Log::error('PDF export failed', [
                'error' => $e->getMessage(),
                'request' => $validated ?? $request->all(),
                'user' => auth()->id() ?? 'guest',
            ]);

            return back()->with('error', 'PDF出力に失敗しました: ' . $e->getMessage());
        }
    }
}