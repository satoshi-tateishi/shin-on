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
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'category_id' => 'integer',
            'sort' => 'integer',
            'is_active' => 'boolean',
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

    // スコープ: 有効なレコードのみ
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // 表示名取得（N+1クエリを避けるため条件付き）
    public function getDisplayNameAttribute(): string
    {
        // リレーションがロード済みの場合のみ使用、そうでない場合はIDを表示
        if ($this->relationLoaded('category') && $this->category) {
            return $this->category->name.' > '.$this->name;
        }

        return $this->name.' (ID: '.$this->category_id.')';
    }

    // 機材数を取得（withCount使用を推奨）
    public function getEquipmentsCountAttribute(): int
    {
        // equipments_countがすでにロードされている場合はそれを使用
        if (isset($this->attributes['equipments_count'])) {
            return $this->attributes['equipments_count'];
        }

        // そうでない場合はクエリ実行（パフォーマンス注意）
        return $this->equipments()->count();
    }
}
