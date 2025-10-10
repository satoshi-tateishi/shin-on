<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduleCellMemo extends Model
{
    protected $fillable = [
        'equipment_id',
        'schedule_date',
        'memo',
        'color',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'schedule_date' => 'date',
    ];

    /**
     * 機材リレーション
     */
    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    /**
     * 作成者リレーション
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * 更新者リレーション
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
