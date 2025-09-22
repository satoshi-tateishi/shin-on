<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    use HasFactory;

    protected $fillable = [
        'sort',
        'type',
        'name',
        'furigana',
        'tel1_name',
        'tel1',
        'tel2_name',
        'tel2',
        'fax',
        'email1_name',
        'email1',
        'email2_name',
        'email2',
        'postal_code',
        'address',
        'note',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sort' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    // リレーション: 機材
    public function equipments(): HasMany
    {
        return $this->hasMany(Equipment::class);
    }

    // スコープ: 有効な場所
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // スコープ: タイプ別
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    // スコープ: 倉庫のみ
    public function scopeWarehouses($query)
    {
        return $query->where('type', '倉庫');
    }

    // スコープ: ソート順で並び替え
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort')->orderBy('name');
    }

    // 表示名取得
    public function getDisplayNameAttribute(): string
    {
        return "[{$this->type}] {$this->name}";
    }

    // フォーマット済み表示名取得（HTMLタグ含む）
    public function getFormattedDisplayNameAttribute(): string
    {
        return '<span class="text-gray-500">'.$this->type.'</span><br>　'.$this->name;
    }

    // ステータスラベル
    public function getStatusLabelAttribute(): string
    {
        return $this->is_active ? '有効' : '無効';
    }

    // 機材数を取得
    public function getEquipmentsCountAttribute(): int
    {
        return $this->equipments()->count();
    }

    /**
     * 在庫スナップショットとの関連
     */
    public function inventorySnapshots(): HasMany
    {
        return $this->hasMany(InventorySnapshot::class);
    }

    /**
     * 指定日時点での在庫一覧を取得
     */
    public function getInventoryAsOf(\Carbon\Carbon $asOfDate): \Illuminate\Support\Collection
    {
        return InventorySnapshot::getSnapshotsByDate($asOfDate, ['location_id' => $this->id]);
    }

    /**
     * 指定日時点での在庫統計を取得
     */
    public function getInventoryStatsAsOf(\Carbon\Carbon $asOfDate): array
    {
        $snapshots = $this->getInventoryAsOf($asOfDate);

        return [
            'total_items' => $snapshots->count(),
            'total_quantity' => $snapshots->sum('quantity'),
            'categories' => $snapshots->groupBy('equipment.subcategory.category.name')->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'quantity' => $group->sum('quantity')
                ];
            })->toArray(),
            'status_distribution' => $snapshots->groupBy('status_color')->map(function ($group) {
                return $group->count();
            })->toArray()
        ];
    }

    /**
     * 倉庫容量管理関連メソッド
     */
    public function getCapacityUsageAttribute(): array
    {
        $maxCapacity = $this->max_capacity ?? 1000; // デフォルト容量
        $currentUsage = $this->equipments()->sum('quantity') ?? $this->equipments()->count();
        $usagePercentage = $maxCapacity > 0 ? ($currentUsage / $maxCapacity) * 100 : 0;

        return [
            'max_capacity' => $maxCapacity,
            'current_usage' => $currentUsage,
            'usage_percentage' => round($usagePercentage, 1),
            'available_space' => max(0, $maxCapacity - $currentUsage),
            'status' => $this->getCapacityStatus($usagePercentage)
        ];
    }

    /**
     * 容量ステータス判定
     */
    private function getCapacityStatus(float $percentage): string
    {
        if ($percentage >= 90) {
            return 'critical'; // 満杯
        } elseif ($percentage >= 75) {
            return 'warning'; // 注意
        } elseif ($percentage >= 50) {
            return 'normal'; // 普通
        } else {
            return 'low'; // 余裕
        }
    }

    /**
     * 機材移動履歴（この場所への出入り）
     */
    public function getMovementHistory(\Carbon\Carbon $startDate = null, \Carbon\Carbon $endDate = null): \Illuminate\Support\Collection
    {
        $startDate = $startDate ?? now()->subMonth();
        $endDate = $endDate ?? now();

        return \App\Models\EquipmentMovement::where(function ($query) {
                $query->where('from_location_id', $this->id)
                    ->orWhere('to_location_id', $this->id);
            })
            ->whereBetween('moved_at', [$startDate, $endDate])
            ->with(['equipment', 'fromLocation', 'toLocation', 'user'])
            ->orderBy('moved_at', 'desc')
            ->get();
    }

    /**
     * 倉庫間移動統計
     */
    public function getTransferStats(\Carbon\Carbon $startDate = null, \Carbon\Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->subMonth();
        $endDate = $endDate ?? now();

        $inboundMovements = \App\Models\EquipmentMovement::where('to_location_id', $this->id)
            ->whereBetween('moved_at', [$startDate, $endDate])
            ->count();

        $outboundMovements = \App\Models\EquipmentMovement::where('from_location_id', $this->id)
            ->whereBetween('moved_at', [$startDate, $endDate])
            ->count();

        return [
            'inbound_count' => $inboundMovements,
            'outbound_count' => $outboundMovements,
            'net_movement' => $inboundMovements - $outboundMovements,
            'total_activity' => $inboundMovements + $outboundMovements
        ];
    }

    /**
     * 在庫アラート判定
     */
    public function checkInventoryAlerts(\Carbon\Carbon $asOfDate = null): array
    {
        $asOfDate = $asOfDate ?? now();
        $alerts = [];

        // 容量アラート
        $capacityUsage = $this->capacity_usage;
        if ($capacityUsage['status'] === 'critical') {
            $alerts[] = [
                'type' => 'capacity_full',
                'message' => "容量超過: {$capacityUsage['usage_percentage']}%使用中",
                'severity' => 'error'
            ];
        } elseif ($capacityUsage['status'] === 'warning') {
            $alerts[] = [
                'type' => 'capacity_high',
                'message' => "容量注意: {$capacityUsage['usage_percentage']}%使用中",
                'severity' => 'warning'
            ];
        }

        // 長期間移動がない場合のアラート
        $lastMovement = $this->getMovementHistory(now()->subWeek(), now());
        if ($lastMovement->isEmpty()) {
            $alerts[] = [
                'type' => 'no_activity',
                'message' => '1週間以上機材の移動がありません',
                'severity' => 'info'
            ];
        }

        return $alerts;
    }
}
