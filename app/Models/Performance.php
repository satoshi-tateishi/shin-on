<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Performance extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'subtitle',
        'performance_type',
        'start_date',
        'end_date',
        'venue',
        'director',
        'producer',
        'status',
        'budget',
        'note',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'budget' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * フェーズとの関連
     */
    public function phases(): HasMany
    {
        return $this->hasMany(Phase::class);
    }

    /**
     * フェーズをソート順で取得
     */
    public function phasesOrdered(): HasMany
    {
        return $this->hasMany(Phase::class)->orderBy('sort')->orderBy('start_date');
    }

    /**
     * 担当者との関連
     */
    public function staff(): HasMany
    {
        return $this->hasMany(PerformanceStaff::class);
    }

    /**
     * スコープ: アクティブな公演のみ
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * スコープ: 公演種別での絞り込み
     */
    public function scopeByType($query, $type)
    {
        return $query->where('performance_type', $type);
    }

    /**
     * スコープ: ステータスでの絞り込み
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
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
     * ステータスのラベル取得
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'planning' => '企画中',
            'preparation' => '準備中',
            'in_progress' => '進行中',
            'completed' => '完了',
            'cancelled' => 'キャンセル',
            default => '不明',
        };
    }

    /**
     * 公演種別のラベル取得
     */
    public function getPerformanceTypeLabelAttribute(): string
    {
        return $this->performance_type;
    }

    /**
     * 公演期間の日数計算
     */
    public function getDurationDaysAttribute(): ?int
    {
        if ($this->start_date && $this->end_date) {
            return $this->start_date->diffInDays($this->end_date) + 1;
        }

        return null;
    }
}
