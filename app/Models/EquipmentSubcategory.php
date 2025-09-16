<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EquipmentSubcategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'sort',
        'name',
    ];

    protected function casts(): array
    {
        return [
            'category_id' => 'integer',
            'sort' => 'integer',
        ];
    }

    // リレーション: 機材大分類
    public function category(): BelongsTo
    {
        return $this->belongsTo(EquipmentCategory::class, 'category_id');
    }

    // リレーション: 機材
    public function equipments(): HasMany
    {
        return $this->hasMany(Equipment::class, 'subcategory_id');
    }

    // スコープ: ソート順で並び替え
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort')->orderBy('name');
    }

    // スコープ: 特定大分類の中分類
    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    // 表示名取得
    public function getDisplayNameAttribute(): string
    {
        return $this->category->name.' > '.$this->name;
    }

    // 機材数を取得
    public function getEquipmentsCountAttribute(): int
    {
        return $this->equipments()->count();
    }
}
