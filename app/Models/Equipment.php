<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class Equipment extends Model
{
    use HasFactory;

    protected $table = 'equipments';

    protected $fillable = [
        'subcategory_id',
        'sort',
        'manufacturer',
        'name',
        'company_number',
        'management_type',
        'quantity',
        'unit',
        'model_number',
        'serial_number',
        'supplier',
        'purchase_date',
        'warranty_expiry',
        'price',
        'status',
        'location_id',
        'is_discard',
        'discard_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'subcategory_id' => 'integer',
            'location_id' => 'integer',
            'sort' => 'integer',
            'quantity' => 'integer',
            'purchase_date' => 'date',
            'warranty_expiry' => 'date',
            'discard_at' => 'date',
            'price' => 'decimal:2',
            'is_discard' => 'boolean',
        ];
    }

    // リレーション: 機材サブカテゴリ
    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(EquipmentSubcategory::class, 'subcategory_id');
    }

    // リレーション: 基本倉庫
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    // リレーション: 機材カテゴリ（サブカテゴリ経由）
    public function category(): HasOneThrough
    {
        return $this->hasOneThrough(
            EquipmentCategory::class,
            EquipmentSubcategory::class,
            'id', // subcategoriesテーブルの主キー
            'id', // categoriesテーブルの主キー
            'subcategory_id', // equipmentsテーブルの外部キー
            'category_id' // subcategoriesテーブルの外部キー
        );
    }

    // スコープ: ソート順で並び替え
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort')->orderBy('name');
    }

    // スコープ: 利用可能な機材
    public function scopeAvailable($query)
    {
        return $query->where('status', 'available')
            ->where('is_discard', false);
    }

    // スコープ: 特定中分類の機材
    public function scopeBySubcategory($query, $subcategoryId)
    {
        return $query->where('subcategory_id', $subcategoryId);
    }

    // スコープ: 個体管理の機材
    public function scopeIndividualManagement($query)
    {
        return $query->where('management_type', 'individual');
    }

    // スコープ: 数量管理の機材
    public function scopeQuantityManagement($query)
    {
        return $query->where('management_type', 'quantity');
    }

    // スコープ: 必要なリレーションを事前ロード
    public function scopeWithRelations($query)
    {
        return $query->with(['subcategory.category', 'location']);
    }

    // スコープ: 検索用に最適化されたクエリ
    public function scopeForIndex($query)
    {
        return $query->withRelations()->ordered();
    }

    // スコープ: 特定の状態の機材
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    // 表示名取得
    public function getDisplayNameAttribute(): string
    {
        $name = $this->name;
        if ($this->company_number) {
            $name .= ' ('.$this->company_number.')';
        }

        return $name;
    }

    // ステータスの日本語表示
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'available' => '利用可能',
            'in_use' => '使用中',
            'repair' => '修理中',
            'maintenance' => 'メンテナンス中',
            'retired' => '廃棄',
            'lost' => '紛失',
            default => '不明',
        };
    }

    // 管理方式の日本語表示
    public function getManagementTypeLabelAttribute(): string
    {
        return match ($this->management_type) {
            'individual' => '個体管理',
            'quantity' => '数量管理',
            default => '不明',
        };
    }
}
