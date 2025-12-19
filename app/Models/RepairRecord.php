<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property Carbon|null $started_at
 * @property Carbon|null $completed_at
 * @property-read Equipment|null $equipment
 */
class RepairRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'equipment_id',
        'failure_occurred_at',
        'staff_user_id',
        'performance_name',
        'usage_location',
        'photos',
        'problem_description',
        'repair_description',
        'repair_cost',
        'repair_company',
        'reported_by',
        'repaired_by',
        'reported_at',
        'started_at',
        'completed_at',
        'status',
        'warranty_until',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'failure_occurred_at' => 'date',
            'photos' => 'array',
            'reported_at' => 'date',
            'started_at' => 'date',
            'completed_at' => 'date',
            'warranty_until' => 'date',
            'repair_cost' => 'decimal:2',
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
     * 報告者との関連
     */
    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    /**
     * 担当者との関連
     */
    public function staffUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_user_id');
    }

    /**
     * ステータス別スコープ
     */
    public function scopeReported(Builder $query): Builder
    {
        return $query->where('status', 'reported');
    }

    public function scopeInProgress(Builder $query): Builder
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * 期間指定スコープ
     */
    public function scopeReportedBetween(Builder $query, $startDate, $endDate): Builder
    {
        return $query->whereBetween('reported_at', [$startDate, $endDate]);
    }

    public function scopeCompletedBetween(Builder $query, $startDate, $endDate): Builder
    {
        return $query->whereBetween('completed_at', [$startDate, $endDate]);
    }

    /**
     * ステータス表示名を取得
     */
    public function getStatusDisplayAttribute(): string
    {
        return match ($this->status) {
            'reported' => '報告済み',
            'in_progress' => '修理中',
            'completed' => '完了',
            'cancelled' => 'キャンセル',
            default => $this->status,
        };
    }

    /**
     * 修理期間（日数）を計算
     */
    public function getRepairDurationAttribute(): ?int
    {
        if (! $this->started_at || ! $this->completed_at) {
            return null;
        }

        return (int) $this->started_at->diffInDays($this->completed_at);
    }

    /**
     * 修理が完了しているかチェック
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * 修理が進行中かチェック
     */
    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    /**
     * バリデーションルールを取得
     */
    public static function getValidationRules(bool $isUpdate = false): array
    {
        $rules = [
            'equipment_id' => 'required|exists:equipments,id',
            'failure_occurred_at' => 'nullable|date',
            'staff_user_id' => 'required|exists:users,id',
            'performance_name' => 'nullable|string|max:255',
            'usage_location' => 'nullable|string|max:255',
            'photos' => 'nullable|array|max:2',
            'photos.*' => 'nullable|file|image|max:10240', // 10MB max per image
            'problem_description' => 'required|string|max:10000',
            'note' => 'nullable|string|max:10000',
        ];

        // 更新時や詳細編集時のみ追加のバリデーション
        if ($isUpdate) {
            $rules = array_merge($rules, [
                'repair_description' => 'nullable|string|max:10000',
                'repair_cost' => 'nullable|numeric|min:0|max:999999999.99',
                'repair_company' => 'nullable|string|max:255',
                'repaired_by' => 'nullable|string|max:255',
                'started_at' => 'nullable|date',
                'completed_at' => 'nullable|date|after_or_equal:started_at',
                'status' => 'required|in:reported,in_progress,completed,cancelled',
                'warranty_until' => 'nullable|date|after_or_equal:today',
                'removed_photos' => 'nullable|string', // 削除する写真のインデックス（カンマ区切り）
            ]);
        }

        return $rules;
    }
}
