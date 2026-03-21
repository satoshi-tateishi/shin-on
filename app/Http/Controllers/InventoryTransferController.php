<?php

namespace App\Http\Controllers;

use App\Http\Responses\ApiResponse;
use App\Models\Equipment;
use App\Models\Location;
use App\Services\InventoryTransferService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * 機材倉庫間移動コントローラー
 *
 * このコントローラーは機材の倉庫間移動に関する全ての機能を管理します。
 * 個体管理機材の移動、一括移動、返却処理などを含みます。
 */
class InventoryTransferController extends Controller
{
    public function __construct(
        private InventoryTransferService $transferService
    ) {}

    /**
     * 倉庫間移動専用画面表示
     *
     * @return View 倉庫間移動画面のビュー
     */
    public function transferIndex(): View
    {
        $defaultWarehouseId = Location::where('is_main_warehouse', true)->first()?->id;
        $apiConfig = $this->buildApiConfig();
        $constants = ['SUMIDA_WAREHOUSE_ID' => $defaultWarehouseId];

        return view('equipment-transfer.index', compact('apiConfig', 'constants'));
    }

    /**
     * 返却先選択画面表示
     *
     * @return View 返却先選択画面のビュー
     */
    public function returnSelectIndex(): View
    {
        $defaultWarehouseId = Location::where('is_main_warehouse', true)->first()?->id;
        $apiConfig = $this->buildApiConfig();
        $constants = ['SUMIDA_WAREHOUSE_ID' => $defaultWarehouseId];

        return view('equipment-transfer.return-select', compact('apiConfig', 'constants'));
    }

    private function buildApiConfig(): array
    {
        return [
            'warehouses'   => route('inventory.api.warehouses'),
            'categories'   => route('equipment-transfer.api.categories'),
            'equipment'    => route('equipment-transfer.api.equipment'),
            'transfer'     => route('equipment-transfer.api.transfer'),
            'bulkTransfer' => route('equipment-transfer.api.bulk-transfer'),
            'bulkReturn'   => route('equipment-transfer.api.bulk-return'),
        ];
    }

    /**
     * 倉庫間移動API
     *
     * 指定した機材を別の倉庫に移動します。
     * 個体管理機材のみが対象となります。
     *
     * @param  Request  $request  リクエストデータ
     * @return JsonResponse JSON応答
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
                return ApiResponse::error('数量管理機材の倉庫間移動はサポートされていません。');
            }

            $currentLocationId = $equipment->now_location_id;
            $toLocationId = $validated['to_location_id'];

            // 同じ場所への移動はエラー
            if ($currentLocationId == $toLocationId) {
                return ApiResponse::error('同じ場所への移動はできません。');
            }

            // 現在地を移動先に設定
            $equipment->update(['now_location_id' => $toLocationId]);

            return ApiResponse::success('機材の倉庫間移動が完了しました。', [
                'equipment' => [
                    'id' => $equipment->id,
                    'name' => $equipment->name,
                    'from_location' => Location::find($currentLocationId)?->name,
                    'to_location' => Location::find($toLocationId)?->name,
                ],
            ]);

        } catch (\Exception $e) {
            return ApiResponse::serverError('倉庫間移動に失敗しました: '.$e->getMessage());
        }
    }

    /**
     * 移動可能機材一覧API
     *
     * 倉庫間移動が可能な機材の一覧を取得します。
     * 以下の条件を満たす機材のみが表示されます：
     * - 個体管理機材（management_type = 'individual'）
     * - 廃棄されていない機材（is_discard = false）
     * - location_id が 92-94 の機材
     * - 現在使用中でない機材（phase_equipment.status != 'checked_out'）
     * - 現在修理中でない機材（repair_records.status != 'in_progress'）
     *
     * @param  Request  $request  リクエストデータ
     * @return JsonResponse JSON応答
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

            $equipment = $this->transferService->getTransferableEquipment($validated);
            $equipmentData = $this->transferService->formatEquipmentForApi($equipment);

            return ApiResponse::data(['data' => $equipmentData]);

        } catch (\Exception $e) {
            \Log::error('Get transferable equipment error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return ApiResponse::serverError('機材データの取得に失敗しました: '.$e->getMessage());
        }
    }

    /**
     * 一括倉庫間移動
     *
     * 複数の機材を一度に別の倉庫に移動します。
     * 各機材の移動処理は独立しており、一部が失敗しても他の処理は継続されます。
     *
     * @param  Request  $request  リクエストデータ
     * @return JsonResponse JSON応答
     */
    public function bulkTransferEquipment(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'transfers' => 'required|array|min:1',
                'transfers.*.equipment_id' => 'required|exists:equipments,id',
                'transfers.*.to_location_id' => 'required|exists:locations,id',
                'transfers.*.note' => 'nullable|string|max:500',
            ]);

            $results = [];
            $errors = [];

            DB::transaction(function () use ($validated, &$results, &$errors) {
                foreach ($validated['transfers'] as $index => $transferData) {
                    try {
                        $equipment = Equipment::findOrFail($transferData['equipment_id']);

                        // 個体管理機材のみ対象
                        if ($equipment->management_type !== 'individual') {
                            $errors[] = "機材「{$equipment->name}」: 数量管理機材の倉庫間移動はサポートされていません。";

                            continue;
                        }

                        $currentLocationId = $equipment->now_location_id;
                        $toLocationId = $transferData['to_location_id'];

                        // 同じ場所への移動はスキップ
                        if ($currentLocationId == $toLocationId) {
                            $errors[] = "機材「{$equipment->name}」: 同じ場所への移動はできません。";

                            continue;
                        }

                        // 現在地を移動先に設定
                        $equipment->update(['now_location_id' => $toLocationId]);

                        $results[] = [
                            'id' => $equipment->id,
                            'name' => $equipment->name,
                            'from_location' => Location::find($currentLocationId)?->name,
                            'to_location' => Location::find($toLocationId)?->name,
                        ];
                    } catch (\Exception $e) {
                        $errors[] = "機材ID {$transferData['equipment_id']}: {$e->getMessage()}";
                    }
                }
            });

            // 結果の処理
            $successCount = count($results);
            $errorCount = count($errors);

            if ($successCount > 0 && $errorCount === 0) {
                return ApiResponse::success("{$successCount}件の機材移動が完了しました。", ['transfers' => $results]);
            } elseif ($successCount > 0 && $errorCount > 0) {
                return response()->json([
                    'success' => true,
                    'message' => "{$successCount}件の機材移動が完了しました。{$errorCount}件でエラーが発生しました。",
                    'transfers' => $results,
                    'errors' => $errors,
                ], 206); // Partial Content
            } else {
                return ApiResponse::error('機材移動に失敗しました。', $errors);
            }
        } catch (\Exception $e) {
            return ApiResponse::serverError('一括移動処理でエラーが発生しました: '.$e->getMessage());
        }
    }

    /**
     * 機材を基本倉庫に返却API
     *
     * 指定した機材を基本倉庫（location_id）に返却します。
     * 現在地（now_location_id）を基本倉庫に変更します。
     *
     * @param  Request  $request  リクエストデータ
     * @param  Equipment  $equipment  返却対象の機材
     * @return JsonResponse JSON応答
     */
    public function returnEquipmentToBase(Request $request, Equipment $equipment): JsonResponse
    {
        try {
            $request->validate([
                'note' => 'nullable|string|max:500',
            ]);

            // 個体管理機材のみ対象
            if ($equipment->management_type !== 'individual') {
                return ApiResponse::error('数量管理機材の返却はサポートされていません。');
            }

            // 基本倉庫と現在地が同じ場合はエラー
            if ($equipment->now_location_id == $equipment->location_id) {
                return ApiResponse::error('機材は既に基本倉庫にあります。');
            }

            // 基本倉庫が設定されていない場合はエラー
            if (! $equipment->location_id) {
                return ApiResponse::error('基本倉庫が設定されていません。');
            }

            $fromLocationName = $equipment->nowLocation?->name ?? '不明';
            $toLocationName = $equipment->location?->name ?? '不明';

            // 現在地を基本倉庫に設定して返却
            $equipment->update(['now_location_id' => $equipment->location_id]);

            return ApiResponse::success('機材を基本倉庫に返却しました。', [
                'equipment' => [
                    'id' => $equipment->id,
                    'name' => $equipment->name,
                    'from_location' => $fromLocationName,
                    'to_location' => $toLocationName,
                ],
            ]);

        } catch (\Exception $e) {
            return ApiResponse::serverError('返却に失敗しました: '.$e->getMessage());
        }
    }

    /**
     * location_id 92-94の機材取得API（返却先選択用）
     *
     * 返却処理対象となる機材の一覧を取得します。
     * location_id が92-94の個体管理機材が対象です。
     *
     * @param  Request  $request  リクエストデータ
     * @return JsonResponse JSON応答
     */
    public function getEquipmentForReturn(Request $request): JsonResponse
    {
        try {
            $query = Equipment::query()
                ->where('management_type', 'individual')
                ->active()
                ->with([
                    'location',
                    'subcategory.category',
                ]);

            // 特定の機材IDで検索（セッションストレージから来た場合）
            if ($request->filled('equipment_id')) {
                $query->where('id', $request->equipment_id)
                    ->whereIn('location_id', Location::getMainWarehouseIds());
            } else {
                // 通常のフィルタリング（全体表示の場合）
                $query->whereIn('location_id', Location::getMainWarehouseIds());

                // カテゴリフィルタ
                if ($request->filled('category_id')) {
                    $query->whereHas('subcategory.category', function ($q) use ($request) {
                        $q->where('id', $request->category_id);
                    });
                }

                // 検索フィルタ
                if ($request->filled('search')) {
                    $search = $request->search;
                    $query->where(function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('company_number', 'like', "%{$search}%");
                    });
                }

                // location_idフィルタ
                if ($request->filled('location_id')) {
                    $query->where('location_id', $request->location_id);
                }
            }

            $equipments = $query->orderBy('subcategory_id')
                ->orderBy('sort', 'asc')
                ->orderBy('name')
                ->get();

            return ApiResponse::data([
                'data' => $equipments,
                'count' => $equipments->count(),
            ]);

        } catch (\Exception $e) {
            return ApiResponse::serverError('機材データの取得に失敗しました: '.$e->getMessage());
        }
    }

    /**
     * 一括返却実行API（指定された返却先へのnow_location_id更新）
     *
     * 複数の機材を指定された返却先倉庫に一括で返却します。
     * PhaseEquipmentのステータスも同時に更新し、移動履歴も作成します。
     *
     * @param  Request  $request  リクエストデータ
     * @return JsonResponse JSON応答
     */
    public function bulkReturn(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'returns' => 'required|array|min:1',
                'returns.*.equipment_id' => 'required|integer|exists:equipments,id',
                'returns.*.return_location_id' => 'required|integer|exists:locations,id',
                'returns.*.phase_equipment_id' => 'nullable|integer|exists:phase_equipment,id',
            ]);

            $result = $this->transferService->processBulkReturn(
                $validated['returns'],
                auth()->id()
            );

            $successCount = count($result['results']);
            $errorCount = count($result['errors']);

            if ($successCount > 0 && $errorCount === 0) {
                return ApiResponse::success("{$successCount}件の機材返却が完了しました。", ['returns' => $result['results']]);
            } elseif ($successCount > 0 && $errorCount > 0) {
                return response()->json([
                    'success' => true,
                    'message' => "{$successCount}件の機材返却が完了しました。{$errorCount}件でエラーが発生しました。",
                    'returns' => $result['results'],
                    'errors' => $result['errors'],
                ], 206); // Partial Content
            } else {
                return ApiResponse::error('機材返却に失敗しました。', $result['errors']);
            }
        } catch (\Exception $e) {
            return ApiResponse::serverError('一括返却処理でエラーが発生しました: '.$e->getMessage());
        }
    }

    /**
     * 倉庫一覧取得API（共通利用）
     *
     * 機材移動・返却処理で使用する倉庫の一覧を取得します。
     * アクティブな倉庫のみが対象です。
     *
     * @return JsonResponse JSON応答
     */
    public function getWarehouses(): JsonResponse
    {
        try {
            $warehouses = Location::active()
                ->warehouses()
                ->forTransferFilter()
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

            return ApiResponse::data(['warehouses' => $warehouses]);

        } catch (\Exception $e) {
            return ApiResponse::serverError('倉庫一覧の取得に失敗しました: '.$e->getMessage());
        }
    }

    /**
     * 倉庫間移動対象機材のカテゴリ一覧取得
     *
     * location_id が92-94の個体管理機材が属するカテゴリの一覧を取得します。
     * フィルター用として使用されます。
     *
     * @return JsonResponse JSON応答
     */
    public function getTransferableCategories(): JsonResponse
    {
        try {
            $categories = $this->transferService->getTransferableCategories();

            return ApiResponse::data(['categories' => $categories]);

        } catch (\Exception $e) {
            return ApiResponse::serverError('カテゴリ一覧の取得に失敗しました: '.$e->getMessage());
        }
    }

    /**
     * 最後の移動記録取得
     *
     * 指定した機材の最後の移動記録を取得します。
     * 基準日以前の最新の移動記録を返します。
     *
     * @param  int  $equipmentId  機材ID
     * @param  Carbon  $asOfDate  基準日
     * @return array|null 移動記録データまたはnull
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
     * フェーズ情報取得API
     *
     * @param  int  $phase  フェーズID
     */
    public function getPhaseInfo(int $phase): JsonResponse
    {
        try {
            $phaseModel = \App\Models\Phase::with(['performance', 'location'])->findOrFail($phase);

            return ApiResponse::data([
                'phase' => [
                    'id' => $phaseModel->id,
                    'name' => $phaseModel->name,
                    'start_date' => $phaseModel->start_date->format('Y/m/d'),
                    'end_date' => $phaseModel->end_date->format('Y/m/d'),
                    'location' => $phaseModel->location ? [
                        'id' => $phaseModel->location->id,
                        'name' => $phaseModel->location->name,
                    ] : null,
                    'performance' => [
                        'id' => $phaseModel->performance->id,
                        'title' => $phaseModel->performance->title,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return ApiResponse::notFound('フェーズ情報の取得に失敗しました: '.$e->getMessage());
        }
    }
}
