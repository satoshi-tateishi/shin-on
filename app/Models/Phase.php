<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property Carbon $start_date
 * @property Carbon $end_date
 */
class Phase extends Model
{
    use HasFactory;

    protected $fillable = [
        'performance_id',
        'location_id',
        'name',
        'start_date',
        'end_date',
        'note',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    /**
     * 公演との関連
     */
    public function performance(): BelongsTo
    {
        return $this->belongsTo(Performance::class);
    }

    /**
     * 実施場所との関連
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * 機材使用記録との関連
     */
    public function equipmentUsages(): HasMany
    {
        return $this->hasMany(PhaseEquipment::class);
    }

    /**
     * 機材使用記録（エイリアス）
     */
    public function phaseEquipments(): HasMany
    {
        return $this->hasMany(PhaseEquipment::class);
    }

    /**
     * 機材移動履歴との関連
     */
    public function equipmentMovements(): HasMany
    {
        return $this->hasMany(EquipmentMovement::class);
    }

    /**
     * スコープ: アクティブなフェーズのみ
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * スコープ: 開始日順で並び替え
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('start_date');
    }

    /**
     * スコープ: 期間での絞り込み
     */
    public function scopeInPeriod($query, $startDate, $endDate)
    {
        return $query->where(function ($q) use ($startDate, $endDate) {
            $q->where('start_date', '<=', $endDate)
                ->where('end_date', '>=', $startDate);
        });
    }

    /**
     * スコープ: 期間重複チェック（特定の機材との重複確認）
     */
    public function scopeOverlappingWith($query, $startDate, $endDate, $excludePhaseId = null)
    {
        $query->where(function ($q) use ($startDate, $endDate) {
            $q->where('start_date', '<', $endDate)
                ->where('end_date', '>', $startDate);
        });

        if ($excludePhaseId) {
            $query->where('id', '!=', $excludePhaseId);
        }

        return $query;
    }

    /**
     * フェーズ期間の日数計算
     */
    public function getDurationDaysAttribute(): int
    {
        return (int) $this->start_date->diffInDays($this->end_date) + 1;
    }

    /**
     * フェーズの進行状況判定
     */
    public function getPhaseStatusAttribute(): string
    {
        $today = now()->toDateString();

        if ($this->end_date->format('Y-m-d') < $today) {
            return 'completed';
        } elseif ($this->start_date->format('Y-m-d') <= $today && $this->end_date->format('Y-m-d') >= $today) {
            return 'in_progress';
        } else {
            return 'upcoming';
        }
    }

    /**
     * フェーズ進行状況のラベル取得
     */
    public function getPhaseStatusLabelAttribute(): string
    {
        return match ($this->phase_status) {
            'completed' => '完了',
            'in_progress' => '進行中',
            'upcoming' => '予定',
            default => '不明',
        };
    }

    /**
     * 未返却の機材があるか
     */
    public function hasUnreturnedEquipment(): bool
    {
        return $this->phaseEquipments()
            ->where('status', 'checked_out')
            ->exists();
    }

    /**
     * 期間重複チェック（バリデーション用）
     */
    public function hasEquipmentConflict($equipmentId, $excludePhaseId = null): bool
    {
        // 将来実装: 同一機材を使用する他のフェーズとの期間重複チェック
        // 現在はPhaseEquipmentモデルが未実装のため、基本構造のみ
        return false;
    }

    /**
     * 使用可能機材の取得（将来実装予定）
     */
    public function getAvailableEquipment()
    {
        // 将来実装: このフェーズ期間で使用可能な機材を取得
        return collect();
    }
}
