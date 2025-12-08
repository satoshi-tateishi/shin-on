<?php

namespace App\Services;

use App\Models\Equipment;
use App\Models\PhaseEquipment;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

/**
 * 機材分析・統計サービス
 *
 * Equipment モデルから分離された複雑なビジネスロジックを管理
 */
class EquipmentAnalyticsService
{
    /**
     * 指定期間での使用可能性チェック
     */
    public function isAvailableInPeriod(Equipment $equipment, $startDate, $endDate): bool
    {
        if ($equipment->status !== 'available' || $equipment->is_discard) {
            return false;
        }

        return ! PhaseEquipment::hasEquipmentConflict($equipment->id, $startDate, $endDate);
    }

    /**
     * 指定期間での使用可能数量（数量管理機材のみ）
     */
    public function getAvailableQuantityInPeriod(Equipment $equipment, $startDate, $endDate): int
    {
        if ($equipment->management_type !== 'quantity') {
            return $this->isAvailableInPeriod($equipment, $startDate, $endDate) ? 1 : 0;
        }

        return PhaseEquipment::getAvailableQuantity($equipment->id, $startDate, $endDate);
    }

    /**
     * 代替機候補を検索
     */
    public function findAlternatives(Equipment $equipment, array $excludeIds = []): Collection
    {
        $excludeIds[] = $equipment->id; // 自分自身を除外

        // 1. 同一機材名の代替機を検索（最優先）
        $sameNameAlternatives = Equipment::where('name', $equipment->name)
            ->whereNotIn('id', $excludeIds)
            ->available()
            ->ordered()
            ->get();

        if ($sameNameAlternatives->isNotEmpty()) {
            return $sameNameAlternatives;
        }

        // 2. 同一サブカテゴリの代替機を検索
        $sameSubcategoryAlternatives = Equipment::where('subcategory_id', $equipment->subcategory_id)
            ->whereNotIn('id', $excludeIds)
            ->available()
            ->ordered()
            ->get();

        if ($sameSubcategoryAlternatives->isNotEmpty()) {
            return $sameSubcategoryAlternatives;
        }

        // 3. 同一カテゴリの代替機を検索
        return Equipment::whereHas('subcategory', function ($query) use ($equipment) {
            $query->where('category_id', $equipment->subcategory->category_id);
        })
            ->whereNotIn('id', $excludeIds)
            ->available()
            ->ordered()
            ->get();
    }

    /**
     * 将来の使用予約を取得
     */
    public function getFutureReservations(Equipment $equipment): Collection
    {
        return $equipment->phaseEquipments()
            ->whereHas('phase', function ($query) {
                $query->where('start_date', '>=', now()->format('Y-m-d'));
            })
            ->with(['phase.performance'])
            ->get()
            ->sortBy('phase.start_date');
    }

    /**
     * 在庫アラート判定（不足・過剰在庫チェック）
     */
    public function checkInventoryAlerts(Equipment $equipment, ?Carbon $asOfDate = null): array
    {
        $asOfDate = $asOfDate ?? now();
        $inventory = $equipment->getInventoryAsOf($asOfDate);
        $alerts = [];

        // 在庫不足チェック（最小在庫数設定があれば）
        $minStockLevel = $equipment->min_stock_level ?? 1;
        if ($inventory['available_quantity'] < $minStockLevel) {
            $alerts[] = [
                'type' => 'low_stock',
                'message' => "在庫不足: {$inventory['available_quantity']}個 (最小: {$minStockLevel}個)",
                'severity' => 'warning',
            ];
        }

        // 長期未使用チェック（90日以上未使用）
        $lastUsage = $equipment->equipmentMovements()
            ->where('movement_type', 'checkout')
            ->orderBy('moved_at', 'desc')
            ->first();

        if (! $lastUsage || $lastUsage->moved_at->lt(now()->subDays(90))) {
            $days = $lastUsage ? $lastUsage->moved_at->diffInDays(now()) : '不明';
            $alerts[] = [
                'type' => 'unused',
                'message' => "長期未使用: {$days}日間未使用",
                'severity' => 'info',
            ];
        }

        return $alerts;
    }

    /**
     * 機材の移動履歴統計を取得
     */
    public function getMovementStats(Equipment $equipment, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->subYear();
        $endDate = $endDate ?? now();

        $movements = $equipment->equipmentMovements()
            ->whereBetween('moved_at', [$startDate, $endDate])
            ->get();

        return [
            'total_movements' => $movements->count(),
            'checkout_count' => $movements->where('movement_type', 'checkout')->count(),
            'checkin_count' => $movements->where('movement_type', 'checkin')->count(),
            'transfer_count' => $movements->where('movement_type', 'transfer')->count(),
            'repair_count' => $movements->whereIn('movement_type', ['repair_start', 'repair_complete'])->count(),
            'most_frequent_location' => $movements->groupBy('to_location_id')->sortByDesc(function ($group) {
                return $group->count();
            })->keys()->first(),
        ];
    }

    /**
     * 機材の使用パターン分析
     */
    public function analyzeUsagePattern(Equipment $equipment, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->subYear();
        $endDate = $endDate ?? now();

        $usages = $equipment->phaseEquipments()
            ->whereHas('phase', function ($query) use ($startDate, $endDate) {
                $query->whereBetween('start_date', [$startDate, $endDate]);
            })
            ->with(['phase.performance'])
            ->get();

        $monthlyUsage = $usages->groupBy(function ($usage) {
            return $usage->phase->start_date->format('Y-m');
        })->map(function ($group) {
            return $group->count();
        });

        $performanceTypes = $usages->groupBy(function ($usage) {
            return $usage->phase->performance->type ?? 'その他';
        })->map(function ($group) {
            return $group->count();
        });

        return [
            'total_usage_count' => $usages->count(),
            'monthly_usage' => $monthlyUsage->toArray(),
            'performance_types' => $performanceTypes->toArray(),
            'average_usage_per_month' => $monthlyUsage->avg(),
            'peak_usage_month' => $monthlyUsage->keys()->first(),
        ];
    }

    /**
     * 修理履歴を取得（期間指定可能）
     */
    public function getRepairHistory(Equipment $equipment, $startDate = null, $endDate = null): Collection
    {
        $query = $equipment->repairRecords()->with('reportedBy');

        if ($startDate && $endDate) {
            $query->reportedBetween($startDate, $endDate);
        }

        return $query->orderBy('reported_at', 'desc')->get();
    }

    /**
     * 修理コストの合計を取得（期間指定可能）
     */
    public function getTotalRepairCost(Equipment $equipment, $startDate = null, $endDate = null): float
    {
        $query = $equipment->repairRecords()->whereNotNull('repair_cost');

        if ($startDate && $endDate) {
            $query->reportedBetween($startDate, $endDate);
        }

        return $query->sum('repair_cost') ?? 0.0;
    }
}
