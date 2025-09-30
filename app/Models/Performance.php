<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Performance extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'short_name',
        'performance_type',
        'director',
        'status',
        'note',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
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
        return $this->hasMany(PerformanceStaff::class)
            ->join('positions', 'performance_staff.position_id', '=', 'positions.id')
            ->orderBy('positions.sort')
            ->select('performance_staff.*');
    }

    /**
     * プロダクションとの多対多関連（中間テーブル経由）
     */
    public function productions(): BelongsToMany
    {
        return $this->belongsToMany(Production::class, 'performance_production')
            ->withTimestamps()
            ->orderByPivot('id', 'asc');
    }

    /**
     * プロダクション関連レコードとの関連
     */
    public function performanceProductions(): HasMany
    {
        return $this->hasMany(PerformanceProduction::class);
    }

    /**
     * 添付ファイルとの関連
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(PerformanceAttachment::class);
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
     * スコープ: 期間での絞り込み（フェーズの期間を基準）
     */
    public function scopeInPeriod($query, $startDate, $endDate)
    {
        return $query->whereHas('phases', function ($q) use ($startDate, $endDate) {
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
     * 表示名取得（略称があれば略称、なければタイトル）
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->short_name ?: $this->title;
    }

    /**
     * 公演全体の開始日（最初のフェーズの開始日）
     */
    public function getStartDateAttribute(): ?string
    {
        $firstPhase = $this->phases()->orderBy('start_date')->first();

        return $firstPhase?->start_date?->format('Y-m-d');
    }

    /**
     * 公演全体の終了日（最後のフェーズの終了日）
     */
    public function getEndDateAttribute(): ?string
    {
        $lastPhase = $this->phases()->orderBy('end_date', 'desc')->first();

        return $lastPhase?->end_date?->format('Y-m-d');
    }

    /**
     * 公演の会場一覧（重複除去）
     */
    public function getVenuesAttribute(): array
    {
        return $this->phases()
            ->with('location')
            ->get()
            ->pluck('location.name')
            ->filter()
            ->unique()
            ->values()
            ->toArray();
    }

    /**
     * メイン会場（最も多く使用される会場）
     */
    public function getMainVenueAttribute(): ?string
    {
        $venues = $this->phases()
            ->with('location')
            ->get()
            ->pluck('location.name')
            ->filter()
            ->countBy();

        return $venues->sortDesc()->keys()->first();
    }

    /**
     * 公演期間の日数計算（フェーズ基準）
     */
    public function getDurationDaysAttribute(): ?int
    {
        $startDate = $this->phases()->min('start_date');
        $endDate = $this->phases()->max('end_date');

        if ($startDate && $endDate) {
            return \Carbon\Carbon::parse($startDate)->diffInDays(\Carbon\Carbon::parse($endDate)) + 1;
        }

        return null;
    }
}
