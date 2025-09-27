<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PhaseEquipment extends Model
{
    use HasFactory;

    protected $table = 'phase_equipment';

    protected $fillable = [
        'phase_id',
        'equipment_id',
        'quantity',
        'checkout_date',
        'checkin_date',
        'checkout_user_id',
        'checkin_user_id',
        'status',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'checkout_date' => 'date',
            'checkin_date' => 'date',
            'quantity' => 'integer',
        ];
    }

    /**
     * フェーズとの関連
     */
    public function phase(): BelongsTo
    {
        return $this->belongsTo(Phase::class);
    }

    /**
     * 機材との関連
     */
    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    /**
     * 出庫者との関連
     */
    public function checkoutUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checkout_user_id');
    }

    /**
     * 返却者との関連
     */
    public function checkinUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checkin_user_id');
    }

    /**
     * 機材移動履歴との関連
     */
    public function movements(): HasMany
    {
        return $this->hasMany(EquipmentMovement::class, 'phase_id', 'phase_id')
            ->where('equipment_id', $this->equipment_id);
    }

    /**
     * スコープ: ステータス別フィルタ
     */
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * スコープ: 予約済み
     */
    public function scopeReserved($query)
    {
        return $query->where('status', 'reserved');
    }

    /**
     * スコープ: 出庫済み
     */
    public function scopeCheckedOut($query)
    {
        return $query->where('status', 'checked_out');
    }

    /**
     * スコープ: 返却済み
     */
    public function scopeCheckedIn($query)
    {
        return $query->where('status', 'checked_in');
    }

    /**
     * スコープ: 特定の機材
     */
    public function scopeForEquipment($query, $equipmentId)
    {
        return $query->where('equipment_id', $equipmentId);
    }

    /**
     * スコープ: 特定のフェーズ
     */
    public function scopeForPhase($query, $phaseId)
    {
        return $query->where('phase_id', $phaseId);
    }

    /**
     * スコープ: 期間重複チェック（指定された機材との重複確認）
     */
    public function scopeOverlappingEquipment($query, $equipmentId, $phaseStartDate, $phaseEndDate, $excludeId = null)
    {
        $query->where('equipment_id', $equipmentId)
            ->whereHas('phase', function ($phaseQuery) use ($phaseStartDate, $phaseEndDate) {
                $phaseQuery->where('start_date', '<', $phaseEndDate)
                    ->where('end_date', '>', $phaseStartDate);
            })
            ->whereNotIn('status', ['checked_in']);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query;
    }

    /**
     * ステータスのラベル取得
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'reserved' => '予約済み',
            'checked_out' => '出庫中',
            'checked_in' => '返却済み',
            default => '不明',
        };
    }

    /**
     * ステータスの色クラス取得
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'reserved' => 'text-blue-600 bg-blue-100',
            'checked_out' => 'text-orange-600 bg-orange-100',
            'checked_in' => 'text-green-600 bg-green-100',
            default => 'text-gray-600 bg-gray-100',
        };
    }

    /**
     * 出庫可能かチェック
     */
    public function canCheckout(): bool
    {
        return in_array($this->status, ['reserved', 'checked_in']);
    }

    /**
     * 返却可能かチェック
     */
    public function canCheckin(): bool
    {
        return $this->status === 'checked_out';
    }

    /**
     * 機材使用期間の取得
     */
    public function getUsagePeriodAttribute(): array
    {
        return [
            'start_date' => $this->phase->start_date,
            'end_date' => $this->phase->end_date,
            'duration_days' => $this->phase->duration_days,
        ];
    }

    /**
     * 期間重複チェック（静的メソッド）
     */
    public static function hasEquipmentConflict($equipmentId, $phaseStartDate, $phaseEndDate, $excludeId = null): bool
    {
        return self::overlappingEquipment($equipmentId, $phaseStartDate, $phaseEndDate, $excludeId)->exists();
    }

    /**
     * 機材の使用可能数量計算
     */
    public static function getAvailableQuantity($equipmentId, $phaseStartDate, $phaseEndDate, $excludeId = null): int
    {
        $equipment = Equipment::find($equipmentId);
        if (! $equipment || $equipment->management_type !== 'quantity') {
            return 0;
        }

        $usedQuantity = self::overlappingEquipment($equipmentId, $phaseStartDate, $phaseEndDate, $excludeId)
            ->sum('quantity');

        return max(0, $equipment->quantity - $usedQuantity);
    }

    /**
     * 指定期間と重複する機材使用記録を取得
     */
    private static function overlappingEquipment($equipmentId, $phaseStartDate, $phaseEndDate, $excludeId = null)
    {
        $query = self::where('equipment_id', $equipmentId)
            ->whereHas('phase', function ($phaseQuery) use ($phaseStartDate, $phaseEndDate) {
                $phaseQuery->where(function ($q) use ($phaseStartDate, $phaseEndDate) {
                    // 期間が重複する条件
                    $q->where(function ($subQuery) use ($phaseStartDate) {
                        // 新しい期間の開始日が既存期間内にある
                        $subQuery->where('start_date', '<=', $phaseStartDate)
                            ->where('end_date', '>=', $phaseStartDate);
                    })->orWhere(function ($subQuery) use ($phaseEndDate) {
                        // 新しい期間の終了日が既存期間内にある
                        $subQuery->where('start_date', '<=', $phaseEndDate)
                            ->where('end_date', '>=', $phaseEndDate);
                    })->orWhere(function ($subQuery) use ($phaseStartDate, $phaseEndDate) {
                        // 新しい期間が既存期間を完全に包含する
                        $subQuery->where('start_date', '>=', $phaseStartDate)
                            ->where('end_date', '<=', $phaseEndDate);
                    });
                });
            });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query;
    }
}
