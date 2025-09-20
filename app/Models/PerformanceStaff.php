<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerformanceStaff extends Model
{
    use HasFactory;

    protected $table = 'performance_staff';

    protected $fillable = [
        'performance_id',
        'user_id',
        'position_id',
        'note',
    ];

    /**
     * 公演との関連
     */
    public function performance(): BelongsTo
    {
        return $this->belongsTo(Performance::class);
    }

    /**
     * ユーザーとの関連
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * ポジションとの関連
     */
    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    /**
     * スコープ: 特定の公演の担当者
     */
    public function scopeForPerformance($query, $performanceId)
    {
        return $query->where('performance_id', $performanceId);
    }

    /**
     * スコープ: 特定のユーザーの担当公演
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * スコープ: 特定のポジションの担当者
     */
    public function scopeByPosition($query, $positionId)
    {
        return $query->where('position_id', $positionId);
    }

    /**
     * スコープ: デザイナーのみ
     */
    public function scopeDesignersOnly($query)
    {
        return $query->whereHas('user', function ($q) {
            $q->where('is_designer', true);
        });
    }

    /**
     * スコープ: スタッフのみ
     */
    public function scopeStaffOnly($query)
    {
        return $query->whereHas('user', function ($q) {
            $q->where('is_staff', true);
        });
    }
}
