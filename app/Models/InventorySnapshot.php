<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class InventorySnapshot extends Model
{
    protected $fillable = [
        'snapshot_date',
        'equipment_name',
        'company_numbers',
        'category_name',
        'subcategory_name',
        'sample_equipment_id',
        'location_id',
        'quantity',
    ];

    protected $casts = [
        'snapshot_date' => 'date',
        'quantity' => 'integer',
    ];

    /**
     * サンプル機材とのリレーション（参考用）
     */
    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class, 'sample_equipment_id');
    }

    /**
     * 場所とのリレーション
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * 指定日時点での機材在庫スナップショットを生成（排他制御付き）
     */
    public static function generateSnapshot(Carbon $asOfDate, bool $skipDelete = false): void
    {
        $lockKey = 'inventory_regeneration_' . $asOfDate->format('Y-m-d');

        // 排他制御でロック取得（最大5分間）
        $lockAcquired = Cache::lock($lockKey, 300)->get(function () use ($asOfDate, $skipDelete) {
            DB::transaction(function () use ($asOfDate, $skipDelete) {
                $dateString = $asOfDate->format('Y-m-d');

                // 既存のスナップショットを削除（スキップオプションがfalseの場合のみ）
                if (! $skipDelete) {
                    self::where('snapshot_date', $dateString)->delete();
                }

                // 機材名 + 場所でグループ化した在庫情報を生成
                $groupedInventory = self::calculateGroupedInventoryAsOf($asOfDate);
                $snapshots = [];

                foreach ($groupedInventory as $group) {
                    $snapshots[] = [
                        'snapshot_date' => $dateString,
                        'equipment_name' => $group['equipment_name'],
                        'company_numbers' => $group['company_numbers'],
                        'category_name' => $group['category_name'],
                        'subcategory_name' => $group['subcategory_name'],
                        'sample_equipment_id' => $group['sample_equipment_id'],
                        'location_id' => $group['location_id'],
                        'quantity' => $group['quantity'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    // 一定数たまったらバッチ挿入
                    if (count($snapshots) >= 1000) {
                        self::insert($snapshots);
                        $snapshots = [];
                    }
                }

                // 残りのスナップショットを挿入
                if (!empty($snapshots)) {
                    self::insert($snapshots);
                }
            });
        });

        if (!$lockAcquired) {
            throw new \Exception('別のユーザーが在庫再計算を実行中です。しばらくしてから再度お試しください。');
        }
    }

    /**
     * 指定日時点での機材をグループ化した在庫情報を計算
     */
    public static function calculateGroupedInventoryAsOf(Carbon $asOfDate): array
    {
        $results = DB::select("
            SELECT
                e.name as equipment_name,
                GROUP_CONCAT(DISTINCT e.company_number ORDER BY e.company_number SEPARATOR ', ') as company_numbers,
                ec.name as category_name,
                es.name as subcategory_name,
                e.now_location_id as location_id,
                MIN(e.id) as sample_equipment_id,
                CASE
                    WHEN e.management_type = 'individual' THEN
                        COUNT(e.id) - COALESCE(usage.in_use_count, 0) - COALESCE(repairs.repair_count, 0)
                    ELSE
                        COALESCE(SUM(e.quantity), 0) - COALESCE(SUM(usage.in_use_quantity), 0)
                END as quantity
            FROM equipments e
            LEFT JOIN equipment_subcategories es ON e.subcategory_id = es.id
            LEFT JOIN equipment_categories ec ON es.category_id = ec.id
            LEFT JOIN (
                SELECT
                    equipment_id,
                    COUNT(*) as in_use_count,
                    COALESCE(SUM(quantity), 0) as in_use_quantity
                FROM phase_equipment
                WHERE status = 'checked_out'
                AND checkout_date <= ?
                AND (checkin_date IS NULL OR checkin_date > ?)
                GROUP BY equipment_id
            ) usage ON e.id = usage.equipment_id
            LEFT JOIN (
                SELECT
                    equipment_id,
                    COUNT(*) as repair_count
                FROM repair_records
                WHERE status = 'in_progress'
                AND failure_occurred_at <= ?
                AND (completed_at IS NULL OR completed_at > ?)
                GROUP BY equipment_id
            ) repairs ON e.id = repairs.equipment_id
            WHERE e.is_active = 1
            GROUP BY e.name, e.now_location_id, ec.name, es.name
            HAVING quantity > 0
            ORDER BY e.name, e.now_location_id
        ", [
            $asOfDate->format('Y-m-d H:i:s'),
            $asOfDate->format('Y-m-d H:i:s'),
            $asOfDate->format('Y-m-d H:i:s'),
            $asOfDate->format('Y-m-d H:i:s'),
        ]);

        return array_map(function ($row) {
            return [
                'equipment_name' => $row->equipment_name,
                'company_numbers' => $row->company_numbers,
                'category_name' => $row->category_name,
                'subcategory_name' => $row->subcategory_name,
                'sample_equipment_id' => $row->sample_equipment_id,
                'location_id' => $row->location_id,
                'quantity' => (int)$row->quantity,
            ];
        }, $results);
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
            ->where('equipment_id', $equipmentId)
            ->where('status', 'checked_out')
            ->where('checkout_date', '<=', $asOfDate)
            ->where(function ($query) use ($asOfDate) {
                $query->whereNull('checkin_date')
                    ->orWhere('checkin_date', '>', $asOfDate);
            })
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

        // 機材の現在地を取得
        $equipment = Equipment::findOrFail($equipmentId);
        $currentLocationId = $equipment->now_location_id;

        $quantity = 1;
        if ($isInUse || $isInRepair) {
            $quantity = 0; // 使用中または修理中
        }

        return [
            'total_quantity' => 1,
            'available_quantity' => $quantity,
            'locations' => [
                [
                    'location_id' => $currentLocationId,
                    'quantity' => $quantity,
                ],
            ],
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
            ->where('equipment_id', $equipmentId)
            ->where('status', 'checked_out')
            ->where('checkout_date', '<=', $asOfDate)
            ->where(function ($query) use ($asOfDate) {
                $query->whereNull('checkin_date')
                    ->orWhere('checkin_date', '>', $asOfDate);
            })
            ->sum('quantity');

        $availableQuantity = max(0, $totalQuantity - $inUseQuantity);

        // 機材の現在地に全て配置
        $currentLocationId = $equipment->now_location_id;

        return [
            'total_quantity' => $totalQuantity,
            'available_quantity' => $availableQuantity,
            'locations' => [
                [
                    'location_id' => $currentLocationId,
                    'quantity' => $availableQuantity,
                ],
            ],
        ];
    }

    /**
     * 指定日のスナップショット一覧を取得（倉庫保管機材のみ）
     */
    public static function getSnapshotsByDate(Carbon $date, array $filters = []): Collection
    {
        $query = self::with(['equipment.subcategory.category', 'location'])
            ->where('snapshot_date', $date->format('Y-m-d'));

        // フィルタリング
        if (! empty($filters['category_id'])) {
            $query->whereHas('equipment.subcategory', function ($q) use ($filters) {
                $q->where('category_id', $filters['category_id']);
            });
        }

        if (! empty($filters['location_id'])) {
            $query->where('location_id', $filters['location_id']);
        }

        if (! empty($filters['search'])) {
            $query->whereHas('equipment', function ($q) use ($filters) {
                $q->where('name', 'like', '%'.$filters['search'].'%');
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
