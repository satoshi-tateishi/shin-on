<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Production extends Model
{
    use HasFactory;

    protected $fillable = [
        'sort',
        'type',
        'name',
        'postal_code',
        'address',
        'note',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sort' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    // スコープ: 有効なプロダクション
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // スコープ: タイプ別
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    // スコープ: ソート順で並び替え
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort')->orderBy('name');
    }

    // 表示名取得
    public function getDisplayNameAttribute(): string
    {
        return "{$this->type} {$this->name}";
    }

    // ステータスラベル
    public function getStatusLabelAttribute(): string
    {
        return $this->is_active ? '有効' : '無効';
    }
}
