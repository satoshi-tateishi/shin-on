<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use App\Models\EquipmentCategory;
use App\Models\EquipmentMovement;
use App\Models\Location;
use App\Models\PhaseEquipment;
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
 *
 * @package App\Http\Controllers
 */
class InventoryTransferController extends Controller
{
    /**
     * 倉庫間移動専用画面表示
     *
     * @return View 倉庫間移動画面のビュー
     */
    public function transferIndex(): View
    {
        return view('equipment-transfer.index');
    }

    /**
     * 倉庫間移動API
     *
     * 指定した機材を別の倉庫に移動します。
     * 個体管理機材のみが対象となります。
     *
     * @param Request $request リクエストデータ
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
                return response()->json([
                    'success' => false,
                    'error' => '数量管理機材の倉庫間移動はサポートされていません。',
                ], 400);
            }

            $currentLocationId = $equipment->now_location_id;
            $toLocationId = $validated['to_location_id'];

            // 同じ場所への移動はエラー
            if ($currentLocationId == $toLocationId) {
                return response()->json([
                    'success' => false,
                    'error' => '同じ場所への移動はできません。',
                ], 400);
            }

            // 現在地を移動先に設定
            $equipment->update(['now_location_id' => $toLocationId]);

            return response()->json([
                'success' => true,
                'message' => '機材の倉庫間移動が完了しました。',
                'equipment' => [
                    'id' => $equipment->id,
                    'name' => $equipment->name,
                    'from_location' => Location::find($currentLocationId)?->name,
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
     * @param Request $request リクエストデータ
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

            $query = Equipment::with([
                'location',
                'nowLocation',
                'subcategory.category',
            ])
                ->where('management_type', 'individual') // 個体管理機材のみ
                ->where('is_discard', false) // 廃棄されていないもののみ
                ->whereIn('location_id', [92, 93, 94]) // location_idが92-94の機材のみ表示対象
                // 使用中の機材を除外
                ->whereNotExists(function ($subQuery) {
                    $subQuery->select(DB::raw(1))
                        ->from('phase_equipment')
                        ->whereColumn('phase_equipment.equipment_id', 'equipments.id')
                        ->where('phase_equipment.status', 'checked_out');
                })
                // 修理中の機材を除外
                ->whereNotExists(function ($subQuery) {
                    $subQuery->select(DB::raw(1))
                        ->from('repair_records')
                        ->whereColumn('repair_records.equipment_id', 'equipments.id')
                        ->where('repair_records.status', 'in_progress');
                });

            // フィルタ適用
            if (! empty($validated['location_id'])) {
                $query->where('now_location_id', $validated['location_id']);
            }

            if (! empty($validated['category_id'])) {
                $query->whereHas('subcategory.category', function ($q) use ($validated) {
                    $q->where('id', $validated['category_id']);
                });
            }

            if (! empty($validated['search'])) {
                $query->where(function ($q) use ($validated) {
                    $q->where('name', 'like', '%'.$validated['search'].'%')
                        ->orWhere('company_number', 'like', '%'.$validated['search'].'%');
                });
            }

            if (! empty($validated['status'])) {
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
                    'location_id' => $item->location_id,
                    'now_location_id' => $item->now_location_id,
                    'location' => [
                        'id' => $item->location->id,
                        'name' => $item->location->name,
                        'type' => $item->location->type,
                        'display_name' => $item->location->name,
                    ],
                    'subcategory' => [
                        'id' => $item->subcategory->id,
                        'name' => $item->subcategory->name,
                        'category' => [
                            'id' => $item->subcategory->category->id,
                            'name' => $item->subcategory->category->name,
                        ],
                    ],
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $equipmentData,
            ]);

        } catch (\Exception $e) {
            \Log::error('Get transferable equipment error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => '機材データの取得に失敗しました: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * 一括倉庫間移動
     *
     * 複数の機材を一度に別の倉庫に移動します。
     * 各機材の移動処理は独立しており、一部が失敗しても他の処理は継続されます。
     *
     * @param Request $request リクエストデータ
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
                return response()->json([
                    'success' => true,
                    'message' => "{$successCount}件の機材移動が完了しました。",
                    'transfers' => $results,
                ]);
            } elseif ($successCount > 0 && $errorCount > 0) {
                return response()->json([
                    'success' => true,
                    'message' => "{$successCount}件の機材移動が完了しました。{$errorCount}件でエラーが発生しました。",
                    'transfers' => $results,
                    'errors' => $errors,
                ], 206); // Partial Content
            } else {
                return response()->json([
                    'success' => false,
                    'error' => '機材移動に失敗しました。',
                    'errors' => $errors,
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => '一括移動処理でエラーが発生しました: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * 機材を基本倉庫に返却API
     *
     * 指定した機材を基本倉庫（location_id）に返却します。
     * 現在地（now_location_id）を基本倉庫に変更します。
     *
     * @param Request $request リクエストデータ
     * @param Equipment $equipment 返却対象の機材
     * @return JsonResponse JSON応答
     */
    public function returnEquipmentToBase(Request $request, Equipment $equipment): JsonResponse
    {
        try {
            $validated = $request->validate([
                'note' => 'nullable|string|max:500',
            ]);

            // 個体管理機材のみ対象
            if ($equipment->management_type !== 'individual') {
                return response()->json([
                    'success' => false,
                    'error' => '数量管理機材の返却はサポートされていません。',
                ], 400);
            }

            // 基本倉庫と現在地が同じ場合はエラー
            if ($equipment->now_location_id == $equipment->location_id) {
                return response()->json([
                    'success' => false,
                    'error' => '機材は既に基本倉庫にあります。',
                ], 400);
            }

            // 基本倉庫が設定されていない場合はエラー
            if (! $equipment->location_id) {
                return response()->json([
                    'success' => false,
                    'error' => '基本倉庫が設定されていません。',
                ], 400);
            }

            $fromLocationName = $equipment->nowLocation?->name ?? '不明';
            $toLocationName = $equipment->location?->name ?? '不明';

            // 現在地を基本倉庫に設定して返却
            $equipment->update(['now_location_id' => $equipment->location_id]);

            return response()->json([
                'success' => true,
                'message' => '機材を基本倉庫に返却しました。',
                'equipment' => [
                    'id' => $equipment->id,
                    'name' => $equipment->name,
                    'from_location' => $fromLocationName,
                    'to_location' => $toLocationName,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => '返却に失敗しました: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * location_id 92-94の機材取得API（返却先選択用）
     *
     * 返却処理対象となる機材の一覧を取得します。
     * location_id が92-94の個体管理機材が対象です。
     *
     * @param Request $request リクエストデータ
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
                    ->whereIn('location_id', [92, 93, 94]);
            } else {
                // 通常のフィルタリング（全体表示の場合）
                $query->whereIn('location_id', [92, 93, 94]);

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

            return response()->json([
                'success' => true,
                'data' => $equipments,
                'count' => $equipments->count(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => '機材データの取得に失敗しました: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * 一括返却実行API（指定された返却先へのnow_location_id更新）
     *
     * 複数の機材を指定された返却先倉庫に一括で返却します。
     * PhaseEquipmentのステータスも同時に更新し、移動履歴も作成します。
     *
     * @param Request $request リクエストデータ
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

            $returns = $validated['returns'];
            $results = [];
            $errors = [];

            DB::transaction(function () use ($returns, &$results, &$errors) {
                foreach ($returns as $returnData) {
                    try {
                        $equipmentId = $returnData['equipment_id'];
                        $returnLocationId = $returnData['return_location_id'];

                        // 機材取得・バリデーション
                        $equipment = Equipment::find($equipmentId);

                        if (! $equipment) {
                            $errors[] = "機材ID {$equipmentId}: 機材が見つかりません";
                            continue;
                        }

                        if ($equipment->management_type !== 'individual') {
                            $errors[] = "機材ID {$equipmentId}: 個体管理機材のみ返却可能です";
                            continue;
                        }

                        if (! in_array($equipment->location_id, [92, 93, 94])) {
                            $errors[] = "機材ID {$equipmentId}: 基本倉庫ID 92-94の機材のみが対象です";
                            continue;
                        }

                        // 返却先倉庫の確認
                        $returnLocation = Location::find($returnLocationId);
                        if (! $returnLocation || $returnLocation->type !== '倉庫') {
                            $errors[] = "機材ID {$equipmentId}: 無効な返却先倉庫です";
                            continue;
                        }

                        // 返却処理実行
                        $equipment->update(['now_location_id' => $returnLocationId]);

                        // PhaseEquipmentのステータスを「返却済み」に更新
                        $phaseEquipmentId = $returnData['phase_equipment_id'] ?? null;

                        if ($phaseEquipmentId) {
                            // 特定のPhaseEquipmentのみ更新
                            $phaseEquipment = PhaseEquipment::find($phaseEquipmentId);

                            if ($phaseEquipment && $phaseEquipment->equipment_id == $equipmentId && $phaseEquipment->status == 'checked_out') {
                                $phaseEquipment->update([
                                    'status' => 'checked_in',
                                    'checkin_date' => now()->format('Y-m-d'),
                                    'checkin_user_id' => auth()->id(),
                                ]);

                                // 移動履歴を作成
                                EquipmentMovement::createCheckin(
                                    $equipment->id,
                                    $phaseEquipment->phase_id,
                                    $phaseEquipment->quantity,
                                    auth()->id(),
                                    $returnLocationId,
                                    '返却先選択による返却'
                                );
                            }
                        } else {
                            // 従来の処理：該当機材のすべての出庫中PhaseEquipmentを更新
                            $phaseEquipments = PhaseEquipment::where('equipment_id', $equipmentId)
                                ->where('status', 'checked_out')
                                ->get();

                            foreach ($phaseEquipments as $phaseEquipment) {
                                $phaseEquipment->update([
                                    'status' => 'checked_in',
                                    'checkin_date' => now()->format('Y-m-d'),
                                    'checkin_user_id' => auth()->id(),
                                ]);

                                // 移動履歴を作成
                                EquipmentMovement::createCheckin(
                                    $equipment->id,
                                    $phaseEquipment->phase_id,
                                    $phaseEquipment->quantity,
                                    auth()->id(),
                                    $returnLocationId,
                                    '返却先選択による返却'
                                );
                            }
                        }

                        $results[] = [
                            'id' => $equipment->id,
                            'name' => $equipment->name,
                            'company_number' => $equipment->company_number,
                            'return_location' => $returnLocation->name,
                        ];
                    } catch (\Exception $e) {
                        $errors[] = "機材ID {$returnData['equipment_id']}: {$e->getMessage()}";
                    }
                }
            });

            // 結果の処理
            $successCount = count($results);
            $errorCount = count($errors);

            if ($successCount > 0 && $errorCount === 0) {
                return response()->json([
                    'success' => true,
                    'message' => "{$successCount}件の機材返却が完了しました。",
                    'returns' => $results,
                ]);
            } elseif ($successCount > 0 && $errorCount > 0) {
                return response()->json([
                    'success' => true,
                    'message' => "{$successCount}件の機材返却が完了しました。{$errorCount}件でエラーが発生しました。",
                    'returns' => $results,
                    'errors' => $errors,
                ], 206); // Partial Content
            } else {
                return response()->json([
                    'success' => false,
                    'error' => '機材返却に失敗しました。',
                    'errors' => $errors,
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => '一括返却処理でエラーが発生しました: '.$e->getMessage(),
            ], 500);
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
            // location_id が 92-94 の機材の subcategory_id を取得
            $subcategoryIds = Equipment::whereIn('location_id', [92, 93, 94])
                ->where('management_type', 'individual')
                ->where('is_discard', false)
                ->distinct()
                ->pluck('subcategory_id');

            // subcategory から category を取得
            $categories = EquipmentCategory::whereHas('subcategories', function ($query) use ($subcategoryIds) {
                $query->whereIn('id', $subcategoryIds);
            })
                ->active()
                ->ordered()
                ->get(['id', 'name']);

            return response()->json([
                'success' => true,
                'categories' => $categories,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'カテゴリ一覧の取得に失敗しました: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * 最後の移動記録取得
     *
     * 指定した機材の最後の移動記録を取得します。
     * 基準日以前の最新の移動記録を返します。
     *
     * @param int $equipmentId 機材ID
     * @param Carbon $asOfDate 基準日
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
}