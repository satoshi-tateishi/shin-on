<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InventorySnapshot extends Model
{
    protected $fillable = [
        'snapshot_date',
        'equipment_id',
        'location_id',
        'quantity',
        'total_quantity',
    ];

    protected $casts = [
        'snapshot_date' => 'date',
        'quantity' => 'integer',
        'total_quantity' => 'integer',
    ];

    /**
     * 機材とのリレーション
     */
    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    /**
     * 場所とのリレーション
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * 指定日時点での機材在庫スナップショットを生成
     */
    public static function generateSnapshot(Carbon $asOfDate): void
    {
        DB::transaction(function () use ($asOfDate) {
            $dateString = $asOfDate->format('Y-m-d');

            // 既存のスナップショットを削除
            self::where('snapshot_date', $dateString)->delete();

            // 全機材の在庫状況を計算
            $equipments = Equipment::where('is_active', true)->get();

            foreach ($equipments as $equipment) {
                $inventoryData = self::calculateInventoryAsOf($equipment->id, $asOfDate);

                // 場所毎の在庫スナップショットを作成
                foreach ($inventoryData['locations'] as $locationData) {
                    if ($locationData['quantity'] > 0) {
                        self::create([
                            'snapshot_date' => $dateString,
                            'equipment_id' => $equipment->id,
                            'location_id' => $locationData['location_id'],
                            'quantity' => $locationData['quantity'],
                            'total_quantity' => $inventoryData['total_quantity'],
                        ]);
                    }
                }
            }
        });
    }

    /**
     * 指定機材の指定日時点での在庫状況を計算
     */
    public static function calculateInventoryAsOf(int $equipmentId, Carbon $asOfDate): array
    {
        $equipment = Equipment::findOrFail($equipmentId);

        if ($equipment->management_type === 'individual') {
            return self::calculateIndividualInventory($equipmentId, $asOfDate);
        } else {
            return self::calculateQuantityInventory($equipmentId, $asOfDate);
        }
    }

    /**
     * 個体管理機材の在庫計算
     */
    private static function calculateIndividualInventory(int $equipmentId, Carbon $asOfDate): array
    {
        // 基準日時点での機材状態を確認
        $isInUse = DB::table('phase_equipment')
            ->join('phases', 'phase_equipment.phase_id', '=', 'phases.id')
            ->where('phase_equipment.equipment_id', $equipmentId)
            ->where('phase_equipment.status', 'checked_out')
            ->where('phases.start_date', '<=', $asOfDate)
            ->where('phases.end_date', '>=', $asOfDate)
            ->exists();

        $isInRepair = DB::table('repair_records')
            ->where('equipment_id', $equipmentId)
            ->where('failure_occurred_at', '<=', $asOfDate)
            ->where(function ($query) use ($asOfDate) {
                $query->whereNull('completed_at')
                    ->orWhere('completed_at', '>', $asOfDate);
            })
            ->where('status', 'in_progress')
            ->exists();

        // デフォルト場所を取得（最後の移動記録から）
        $lastMovement = DB::table('equipment_movements')
            ->where('equipment_id', $equipmentId)
            ->where('moved_at', '<=', $asOfDate)
            ->orderBy('moved_at', 'desc')
            ->first();

        $defaultLocationId = $lastMovement ? $lastMovement->to_location_id : 1; // デフォルト倉庫

        $quantity = 1;
        if ($isInUse || $isInRepair) {
            $quantity = 0; // 使用中または修理中
        }

        return [
            'total_quantity' => 1,
            'available_quantity' => $quantity,
            'locations' => [
                [
                    'location_id' => $defaultLocationId,
                    'quantity' => $quantity,
                ]
            ]
        ];
    }

    /**
     * 数量管理機材の在庫計算
     */
    private static function calculateQuantityInventory(int $equipmentId, Carbon $asOfDate): array
    {
        $equipment = Equipment::findOrFail($equipmentId);
        $totalQuantity = $equipment->quantity ?? 0;

        // 基準日時点での使用中数量を計算
        $inUseQuantity = DB::table('phase_equipment')
            ->join('phases', 'phase_equipment.phase_id', '=', 'phases.id')
            ->where('phase_equipment.equipment_id', $equipmentId)
            ->where('phase_equipment.status', 'checked_out')
            ->where('phases.start_date', '<=', $asOfDate)
            ->where('phases.end_date', '>=', $asOfDate)
            ->sum('phase_equipment.quantity');

        $availableQuantity = max(0, $totalQuantity - $inUseQuantity);

        // デフォルト場所に全て配置
        $defaultLocationId = 1; // メイン倉庫

        return [
            'total_quantity' => $totalQuantity,
            'available_quantity' => $availableQuantity,
            'locations' => [
                [
                    'location_id' => $defaultLocationId,
                    'quantity' => $availableQuantity,
                ]
            ]
        ];
    }

    /**
     * 指定日のスナップショット一覧を取得
     */
    public static function getSnapshotsByDate(Carbon $date, array $filters = []): Collection
    {
        $query = self::with(['equipment.subcategory.category', 'location'])
            ->where('snapshot_date', $date->format('Y-m-d'));

        // フィルタリング
        if (!empty($filters['category_id'])) {
            $query->whereHas('equipment.subcategory', function ($q) use ($filters) {
                $q->where('category_id', $filters['category_id']);
            });
        }

        if (!empty($filters['location_id'])) {
            $query->where('location_id', $filters['location_id']);
        }

        if (!empty($filters['search'])) {
            $query->whereHas('equipment', function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%');
            });
        }

        return $query->orderBy('equipment_id')->get();
    }

    /**
     * 在庫状況の色分け判定
     */
    public function getStatusColorAttribute(): string
    {
        $percentage = $this->total_quantity > 0 ? ($this->quantity / $this->total_quantity) * 100 : 0;

        if ($percentage >= 80) {
            return 'green'; // 十分
        } elseif ($percentage >= 30) {
            return 'yellow'; // 注意
        } else {
            return 'red'; // 不足
        }
    }

    /**
     * 在庫状況のステータステキスト
     */
    public function getStatusTextAttribute(): string
    {
        $color = $this->status_color;

        return match ($color) {
            'green' => '十分',
            'yellow' => '注意',
            'red' => '不足',
            default => '不明'
        };
    }
}
