<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EquipmentSetItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'equipment_set_id',
        'equipment_id',
        'quantity',
        'sort_order',
        'is_required',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'sort_order' => 'integer',
            'is_required' => 'boolean',
        ];
    }

    /**
     * 機材セットとの関連
     */
    public function equipmentSet(): BelongsTo
    {
        return $this->belongsTo(EquipmentSet::class);
    }

    /**
     * 機材との関連
     */
    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    /**
     * スコープ: ソート順で並び替え
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * スコープ: 必須機材のみ
     */
    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    /**
     * スコープ: 任意機材のみ
     */
    public function scopeOptional($query)
    {
        return $query->where('is_required', false);
    }
}
