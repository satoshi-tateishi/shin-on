<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    use HasFactory;

    protected $fillable = [
        'sort',
        'type',
        'name',
        'furigana',
        'tel1_name',
        'tel1',
        'tel2_name',
        'tel2',
        'fax',
        'email1_name',
        'email1',
        'email2_name',
        'email2',
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

    // リレーション: 機材
    public function equipments(): HasMany
    {
        return $this->hasMany(Equipment::class);
    }

    // スコープ: 有効な場所
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // スコープ: タイプ別
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    // スコープ: 倉庫のみ
    public function scopeWarehouses($query)
    {
        return $query->where('type', '倉庫');
    }

    // スコープ: ソート順で並び替え
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort')->orderBy('name');
    }

    // 表示名取得
    public function getDisplayNameAttribute(): string
    {
        return "[{$this->type}] {$this->name}";
    }

    // フォーマット済み表示名取得（HTMLタグ含む）
    public function getFormattedDisplayNameAttribute(): string
    {
        return '<span class="text-gray-500">' . $this->type . '</span><br>　' . $this->name;
    }

    // ステータスラベル
    public function getStatusLabelAttribute(): string
    {
        return $this->is_active ? '有効' : '無効';
    }

    // 機材数を取得
    public function getEquipmentsCountAttribute(): int
    {
        return $this->equipments()->count();
    }
}
