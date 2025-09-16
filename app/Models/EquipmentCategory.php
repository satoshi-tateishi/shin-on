<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EquipmentCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'sort',
        'name',
    ];

    protected function casts(): array
    {
        return [
            'sort' => 'integer',
        ];
    }

    // リレーション: 機材中分類
    public function subcategories(): HasMany
    {
        return $this->hasMany(EquipmentSubcategory::class, 'category_id')->orderBy('sort');
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

    // 中分類数を取得
    public function getSubcategoriesCountAttribute(): int
    {
        return $this->subcategories()->count();
    }
}
