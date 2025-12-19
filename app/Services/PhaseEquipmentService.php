<?php

namespace App\Services;

use App\Models\Equipment;
use App\Models\Location;
use App\Models\Phase;
use App\Models\PhaseEquipment;
use Illuminate\Support\Facades\DB;

class PhaseEquipmentService
{
    public function getPhaseEquipmentWithStats(Phase $phase, ?string $statusFilter = null, int $perPage = 20): array
    {
        // フェーズの機材使用記録を取得
        $query = $phase->phaseEquipments()
            ->with(['equipment.subcategory.category', 'checkoutUser', 'checkinUser'])
            ->join('equipments', 'phase_equipment.equipment_id', '=', 'equipments.id');

        // ステータスフィルタリングを適用
        if ($statusFilter && $statusFilter !== 'all') {
            $query->where('phase_equipment.status', $statusFilter);
        }

        $phaseEquipments = $query
            ->orderBy('equipments.sort')
            ->orderByDesc('phase_equipment.created_at')
            ->select('phase_equipment.*')
            ->paginate($perPage);

        // 統計情報を計算
        $stats = $this->calculateEquipmentStats($phase);

        return [
            'equipments' => $phaseEquipments,
            'stats' => $stats,
        ];
    }

    public function calculateEquipmentStats(Phase $phase): array
    {
        return [
            'total' => PhaseEquipment::forPhase($phase->id)->count(),
            'reserved' => PhaseEquipment::forPhase($phase->id)->reserved()->count(),
            'checked_out' => PhaseEquipment::forPhase($phase->id)->checkedOut()->count(),
            'checked_in' => PhaseEquipment::forPhase($phase->id)->checkedIn()->count(),
        ];
    }

    public function createPhaseEquipment(Phase $phase, Equipment $equipment, array $data): PhaseEquipment
    {
        // 機材の使用可能性をチェック
        $this->validateEquipmentAvailability($equipment, $phase, $data['quantity']);

        return DB::transaction(function () use ($phase, $equipment, $data) {
            return PhaseEquipment::create([
                'phase_id' => $phase->id,
                'equipment_id' => $equipment->id,
                'quantity' => $data['quantity'],
                'status' => 'reserved',
                'note' => $data['note'] ?? null,
            ]);
        });
    }

    public function bulkCheckoutReserved(Phase $phase): int
    {
        return DB::transaction(function () use ($phase) {
            $updated = PhaseEquipment::forPhase($phase->id)
                ->reserved()
                ->update([
                    'status' => 'checked_out',
                    'checkout_date' => now()->toDateString(),
                    'checkout_user_id' => auth()->id(),
                ]);

            return $updated;
        });
    }

    public function bulkCheckinEquipments(Phase $phase, array $equipmentIds): int
    {
        return DB::transaction(function () use ($phase, $equipmentIds) {
            $updated = PhaseEquipment::forPhase($phase->id)
                ->whereIn('id', $equipmentIds)
                ->checkedOut()
                ->update([
                    'status' => 'checked_in',
                    'checkin_date' => now()->toDateString(),
                    'checkin_user_id' => auth()->id(),
                ]);

            return $updated;
        });
    }

    public function checkoutEquipment(PhaseEquipment $phaseEquipment, string $checkoutDate): bool
    {
        return DB::transaction(function () use ($phaseEquipment, $checkoutDate) {
            return $phaseEquipment->update([
                'status' => 'checked_out',
                'checkout_date' => $checkoutDate,
                'checkout_user_id' => auth()->id(),
            ]);
        });
    }

    public function checkinEquipment(PhaseEquipment $phaseEquipment, string $checkinDate, ?int $toLocationId = null): bool
    {
        return DB::transaction(function () use ($phaseEquipment, $checkinDate, $toLocationId) {
            $updateData = [
                'status' => 'checked_in',
                'checkin_date' => $checkinDate,
                'checkin_user_id' => auth()->id(),
            ];

            // 必要に応じて機材の場所を更新（主要倉庫の機材のみ）
            if ($toLocationId && in_array($phaseEquipment->equipment->location_id, Location::getMainWarehouseIds())) {
                $phaseEquipment->equipment->update(['location_id' => $toLocationId]);
            }

            return $phaseEquipment->update($updateData);
        });
    }

    public function getCheckedOutEquipments(Phase $phase): array
    {
        return PhaseEquipment::forPhase($phase->id)
            ->checkedOut()
            ->with(['equipment'])
            ->get()
            ->toArray();
    }

    private function validateEquipmentAvailability(Equipment $equipment, Phase $phase, int $quantity): void
    {
        // 個体管理機材の場合のみ期間重複チェック
        if ($equipment->management_type === 'individual') {
            $hasConflict = PhaseEquipment::hasEquipmentConflict(
                $equipment->id,
                $phase->start_date,
                $phase->end_date
            );

            if ($hasConflict) {
                throw new \InvalidArgumentException('指定された機材は、この期間中に他のフェーズで使用予定です。');
            }
        }

        // 数量管理機材の場合、既存レコードの重複チェック
        if ($equipment->management_type === 'quantity') {
            $existingRecord = PhaseEquipment::where('phase_id', $phase->id)
                ->where('equipment_id', $equipment->id)
                ->first();

            if ($existingRecord) {
                throw new \InvalidArgumentException('この機材は既にこのフェーズに登録されています。数量を変更する場合は編集画面をご利用ください。');
            }

            $availableQuantity = PhaseEquipment::getAvailableQuantity(
                $equipment->id,
                $phase->start_date,
                $phase->end_date
            );

            if ($quantity > $availableQuantity) {
                throw new \InvalidArgumentException("使用可能数量は最大 {$availableQuantity} 個です。");
            }
        }
    }
}
