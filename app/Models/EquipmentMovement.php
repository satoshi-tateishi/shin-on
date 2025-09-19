<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EquipmentMovement extends Model
{
    use HasFactory;

    // updated_atは使用しない（移動履歴の不変性保持）
    public $timestamps = false;

    protected $dates = ['moved_at', 'created_at'];

    protected $fillable = [
        'equipment_id',
        'movement_type',
        'phase_id',
        'from_location_id',
        'to_location_id',
        'quantity',
        'moved_by',
        'moved_at',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'moved_at' => 'datetime',
            'created_at' => 'datetime',
            'quantity' => 'integer',
        ];
    }

    /**
     * 機材との関連
     */
    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    /**
     * フェーズとの関連（使用時のみ）
     */
    public function phase(): BelongsTo
    {
        return $this->belongsTo(Phase::class);
    }

    /**
     * 移動元場所との関連
     */
    public function fromLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'from_location_id');
    }

    /**
     * 移動先場所との関連
     */
    public function toLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'to_location_id');
    }

    /**
     * 実行者との関連
     */
    public function movedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moved_by');
    }

    /**
     * スコープ: 移動タイプ別フィルタ
     */
    public function scopeType($query, $type)
    {
        return $query->where('movement_type', $type);
    }

    /**
     * スコープ: 貸出記録
     */
    public function scopeCheckout($query)
    {
        return $query->where('movement_type', 'checkout');
    }

    /**
     * スコープ: 返却記録
     */
    public function scopeCheckin($query)
    {
        return $query->where('movement_type', 'checkin');
    }

    /**
     * スコープ: 倉庫間移動記録
     */
    public function scopeTransfer($query)
    {
        return $query->where('movement_type', 'transfer');
    }

    /**
     * スコープ: メンテナンス記録
     */
    public function scopeMaintenance($query)
    {
        return $query->where('movement_type', 'maintenance');
    }

    /**
     * スコープ: 修理開始記録
     */
    public function scopeRepairStart($query)
    {
        return $query->where('movement_type', 'repair_start');
    }

    /**
     * スコープ: 修理完了記録
     */
    public function scopeRepairComplete($query)
    {
        return $query->where('movement_type', 'repair_complete');
    }

    /**
     * スコープ: 廃棄記録
     */
    public function scopeDisposal($query)
    {
        return $query->where('movement_type', 'disposal');
    }

    /**
     * スコープ: 特定機材の履歴
     */
    public function scopeForEquipment($query, $equipmentId)
    {
        return $query->where('equipment_id', $equipmentId);
    }

    /**
     * スコープ: 特定フェーズの履歴
     */
    public function scopeForPhase($query, $phaseId)
    {
        return $query->where('phase_id', $phaseId);
    }

    /**
     * スコープ: 期間での絞り込み
     */
    public function scopeInPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('moved_at', [$startDate, $endDate]);
    }

    /**
     * スコープ: 日付順での並び替え
     */
    public function scopeOrdered($query, $direction = 'desc')
    {
        return $query->orderBy('moved_at', $direction)->orderBy('id', $direction);
    }

    /**
     * 移動タイプのラベル取得
     */
    public function getMovementTypeLabelAttribute(): string
    {
        return match ($this->movement_type) {
            'checkout' => '貸出',
            'checkin' => '返却',
            'transfer' => '倉庫間移動',
            'maintenance' => 'メンテナンス',
            'disposal' => '廃棄',
            'repair_start' => '修理開始',
            'repair_complete' => '修理完了',
            default => '不明',
        };
    }

    /**
     * 移動タイプの色クラス取得
     */
    public function getMovementTypeColorAttribute(): string
    {
        return match ($this->movement_type) {
            'checkout' => 'text-orange-600 bg-orange-100',
            'checkin' => 'text-green-600 bg-green-100',
            'transfer' => 'text-blue-600 bg-blue-100',
            'maintenance' => 'text-yellow-600 bg-yellow-100',
            'disposal' => 'text-red-600 bg-red-100',
            'repair_start' => 'text-purple-600 bg-purple-100',
            'repair_complete' => 'text-teal-600 bg-teal-100',
            default => 'text-gray-600 bg-gray-100',
        };
    }

    /**
     * 移動方向の取得（in/out/transfer）
     */
    public function getMovementDirectionAttribute(): string
    {
        return match ($this->movement_type) {
            'checkout', 'repair_start', 'maintenance', 'disposal' => 'out',
            'checkin', 'repair_complete' => 'in',
            'transfer' => 'transfer',
            default => 'neutral',
        };
    }

    /**
     * 在庫増減の取得（プラス/マイナス）
     */
    public function getInventoryImpactAttribute(): int
    {
        return match ($this->movement_direction) {
            'out' => -$this->quantity,
            'in' => $this->quantity,
            'transfer' => 0, // 倉庫間移動は総在庫に影響なし
            default => 0,
        };
    }

    /**
     * 静的メソッド: 機材貸出記録の作成
     */
    public static function createCheckout($equipmentId, $phaseId, $quantity, $userId, $fromLocationId = null, $note = null): self
    {
        return self::create([
            'equipment_id' => $equipmentId,
            'movement_type' => 'checkout',
            'phase_id' => $phaseId,
            'from_location_id' => $fromLocationId,
            'quantity' => $quantity,
            'moved_by' => $userId,
            'moved_at' => now(),
            'note' => $note,
        ]);
    }

    /**
     * 静的メソッド: 機材返却記録の作成
     */
    public static function createCheckin($equipmentId, $phaseId, $quantity, $userId, $toLocationId = null, $note = null): self
    {
        return self::create([
            'equipment_id' => $equipmentId,
            'movement_type' => 'checkin',
            'phase_id' => $phaseId,
            'to_location_id' => $toLocationId,
            'quantity' => $quantity,
            'moved_by' => $userId,
            'moved_at' => now(),
            'note' => $note,
        ]);
    }

    /**
     * 静的メソッド: 倉庫間移動記録の作成
     */
    public static function createTransfer($equipmentId, $quantity, $userId, $fromLocationId, $toLocationId, $note = null): self
    {
        return self::create([
            'equipment_id' => $equipmentId,
            'movement_type' => 'transfer',
            'from_location_id' => $fromLocationId,
            'to_location_id' => $toLocationId,
            'quantity' => $quantity,
            'moved_by' => $userId,
            'moved_at' => now(),
            'note' => $note,
        ]);
    }

    /**
     * 静的メソッド: 特定基準日時点での機材在庫計算
     */
    public static function calculateInventoryAt($equipmentId, $asOfDate, $locationId = null): int
    {
        $query = self::forEquipment($equipmentId)
            ->where('moved_at', '<=', $asOfDate);

        if ($locationId) {
            $query->where(function ($q) use ($locationId) {
                $q->where('to_location_id', $locationId)
                    ->orWhere('from_location_id', $locationId);
            });
        }

        $movements = $query->ordered('asc')->get();

        $inventory = 0;
        foreach ($movements as $movement) {
            $inventory += $movement->inventory_impact;
        }

        return max(0, $inventory);
    }

    /**
     * 静的メソッド: 機材使用履歴の取得（期間指定）
     */
    public static function getUsageHistory($equipmentId, $startDate = null, $endDate = null, $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        $query = self::forEquipment($equipmentId)
            ->with(['phase', 'fromLocation', 'toLocation', 'movedBy'])
            ->ordered();

        if ($startDate) {
            $query->where('moved_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('moved_at', '<=', $endDate);
        }

        return $query->limit($limit)->get();
    }
}
