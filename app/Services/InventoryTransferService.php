<?php

namespace App\Services;

use App\Models\Equipment;
use App\Models\EquipmentCategory;
use App\Models\EquipmentMovement;
use App\Models\Location;
use App\Models\PhaseEquipment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InventoryTransferService
{
    /**
     * 移動可能機材一覧を取得
     *
     * @param  array  $filters  フィルター条件
     */
    public function getTransferableEquipment(array $filters = []): Collection
    {
        $query = Equipment::with([
            'location',
            'nowLocation',
            'subcategory.category',
        ])
            ->where('management_type', 'individual')
            ->where('is_discard', false)
            ->whereIn('location_id', Location::getMainWarehouseIds())
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
        if (! empty($filters['location_id'])) {
            $query->where('now_location_id', $filters['location_id']);
        }

        if (! empty($filters['category_id'])) {
            $query->whereHas('subcategory.category', function ($q) use ($filters) {
                $q->where('id', $filters['category_id']);
            });
        }

        if (! empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', '%'.$filters['search'].'%')
                    ->orWhere('company_number', 'like', '%'.$filters['search'].'%');
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('sort')->get();
    }

    /**
     * 移動可能機材をAPI用に整形
     */
    public function formatEquipmentForApi(Collection $equipment): array
    {
        return $equipment->map(function ($item) {
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
        })->toArray();
    }

    /**
     * 一括返却処理を実行
     *
     * @param  array  $returns  返却データ配列
     * @param  int  $userId  実行ユーザーID
     * @return array ['results' => array, 'errors' => array]
     */
    public function processBulkReturn(array $returns, int $userId): array
    {
        $results = [];
        $errors = [];

        DB::transaction(function () use ($returns, $userId, &$results, &$errors) {
            foreach ($returns as $returnData) {
                $result = $this->processReturnItem($returnData, $userId);

                if ($result['success']) {
                    $results[] = $result['data'];
                } else {
                    $errors[] = $result['error'];
                }
            }
        });

        return [
            'results' => $results,
            'errors' => $errors,
        ];
    }

    /**
     * 個別の返却アイテムを処理
     */
    private function processReturnItem(array $returnData, int $userId): array
    {
        $equipmentId = $returnData['equipment_id'];
        $returnLocationId = $returnData['return_location_id'];

        // 機材取得・バリデーション
        $equipment = Equipment::find($equipmentId);

        if (! $equipment) {
            return [
                'success' => false,
                'error' => "機材ID {$equipmentId}: 機材が見つかりません",
            ];
        }

        if ($equipment->management_type !== 'individual') {
            return [
                'success' => false,
                'error' => "機材ID {$equipmentId}: 個体管理機材のみ返却可能です",
            ];
        }

        if (! in_array($equipment->location_id, Location::getMainWarehouseIds())) {
            return [
                'success' => false,
                'error' => "機材ID {$equipmentId}: 主要倉庫の機材のみが対象です",
            ];
        }

        // 返却先倉庫の確認
        $returnLocation = Location::find($returnLocationId);
        if (! $returnLocation || $returnLocation->type !== '倉庫') {
            return [
                'success' => false,
                'error' => "機材ID {$equipmentId}: 無効な返却先倉庫です",
            ];
        }

        // 返却処理実行
        $equipment->update(['now_location_id' => $returnLocationId]);

        // PhaseEquipmentのステータス更新
        $this->updatePhaseEquipmentStatus($returnData, $equipment, $returnLocationId, $userId);

        return [
            'success' => true,
            'data' => [
                'id' => $equipment->id,
                'name' => $equipment->name,
                'company_number' => $equipment->company_number,
                'return_location' => $returnLocation->name,
            ],
        ];
    }

    /**
     * PhaseEquipmentのステータスを更新し、移動履歴を作成
     */
    private function updatePhaseEquipmentStatus(
        array $returnData,
        Equipment $equipment,
        int $returnLocationId,
        int $userId
    ): void {
        $phaseEquipmentId = $returnData['phase_equipment_id'] ?? null;

        if ($phaseEquipmentId) {
            // 特定のPhaseEquipmentのみ更新
            $phaseEquipment = PhaseEquipment::find($phaseEquipmentId);

            if ($phaseEquipment && $phaseEquipment->equipment_id == $equipment->id && $phaseEquipment->status == 'checked_out') {
                $this->checkinPhaseEquipment($phaseEquipment, $equipment->id, $returnLocationId, $userId);
            }
        } else {
            // 該当機材のすべての出庫中PhaseEquipmentを更新
            $phaseEquipments = PhaseEquipment::where('equipment_id', $equipment->id)
                ->where('status', 'checked_out')
                ->get();

            foreach ($phaseEquipments as $phaseEquipment) {
                $this->checkinPhaseEquipment($phaseEquipment, $equipment->id, $returnLocationId, $userId);
            }
        }
    }

    /**
     * PhaseEquipmentをチェックインし、移動履歴を作成
     */
    private function checkinPhaseEquipment(
        PhaseEquipment $phaseEquipment,
        int $equipmentId,
        int $returnLocationId,
        int $userId
    ): void {
        $phaseEquipment->update([
            'status' => 'checked_in',
            'checkin_date' => now()->format('Y-m-d'),
            'checkin_user_id' => $userId,
        ]);

        // 移動履歴を作成
        EquipmentMovement::createCheckin(
            $equipmentId,
            $phaseEquipment->phase_id,
            $phaseEquipment->quantity,
            $userId,
            $returnLocationId,
            '返却先選択による返却'
        );
    }

    /**
     * 移動可能機材のカテゴリ一覧を取得
     */
    public function getTransferableCategories(): Collection
    {
        $subcategoryIds = Equipment::whereIn('location_id', Location::getMainWarehouseIds())
            ->where('management_type', 'individual')
            ->where('is_discard', false)
            ->distinct()
            ->pluck('subcategory_id');

        return EquipmentCategory::whereHas('subcategories', function ($query) use ($subcategoryIds) {
            $query->whereIn('id', $subcategoryIds);
        })
            ->active()
            ->ordered()
            ->get(['id', 'name']);
    }
}
