<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EquipmentSet extends Model
{
    use HasFactory;

    protected $fillable = [
        'sort',
        'name',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sort' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    // スコープ: 有効なセット
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // スコープ: ソート順で並び替え
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort')->orderBy('name');
    }

    // 表示名取得
    public function getDisplayNameAttribute(): string
    {
        return $this->name;
    }

    // ステータスラベル
    public function getStatusLabelAttribute(): string
    {
        return $this->is_active ? '有効' : '無効';
    }

    /**
     * セット構成機材（中間テーブル）との関連
     */
    public function equipmentItems(): HasMany
    {
        return $this->hasMany(EquipmentSetItem::class);
    }

    /**
     * セット構成機材（中間テーブル、ソート済み）との関連
     */
    public function equipmentItemsOrdered(): HasMany
    {
        return $this->hasMany(EquipmentSetItem::class)->ordered();
    }

    /**
     * セット内の機材（多対多）との関連
     */
    public function equipments(): BelongsToMany
    {
        return $this->belongsToMany(Equipment::class, 'equipment_set_items')
            ->withPivot(['quantity', 'sort_order', 'is_required', 'notes'])
            ->withTimestamps()
            ->orderByPivot('sort_order')
            ->orderByPivot('id');
    }

    /**
     * セット内の必須機材のみ
     */
    public function requiredEquipments(): BelongsToMany
    {
        return $this->equipments()->wherePivot('is_required', true);
    }

    /**
     * セット内の任意機材のみ
     */
    public function optionalEquipments(): BelongsToMany
    {
        return $this->equipments()->wherePivot('is_required', false);
    }

    /**
     * セット内機材数の取得
     */
    public function getTotalItemsAttribute(): int
    {
        return $this->equipmentItems()->count();
    }

    /**
     * セット内必須機材数の取得
     */
    public function getRequiredItemsAttribute(): int
    {
        return $this->equipmentItems()->required()->count();
    }
}
