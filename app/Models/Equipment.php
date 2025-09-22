<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        'is_schedule_visible',
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
            'is_schedule_visible' => 'boolean',
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

    // スコープ: 有効な機材（廃棄されていない）
    public function scopeActive($query)
    {
        return $query->where('is_discard', false);
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

    /**
     * この機材を含む機材セットとの関連
     */
    public function equipmentSets(): BelongsToMany
    {
        return $this->belongsToMany(EquipmentSet::class, 'equipment_set_items')
            ->withPivot(['quantity', 'sort_order', 'is_required', 'notes'])
            ->withTimestamps()
            ->orderByPivot('sort_order')
            ->orderByPivot('id');
    }

    /**
     * この機材が必須として含まれる機材セット
     */
    public function requiredInSets(): BelongsToMany
    {
        return $this->equipmentSets()->wherePivot('is_required', true);
    }

    /**
     * この機材が任意として含まれる機材セット
     */
    public function optionalInSets(): BelongsToMany
    {
        return $this->equipmentSets()->wherePivot('is_required', false);
    }

    /**
     * フェーズ機材使用記録との関連
     */
    public function phaseEquipments(): HasMany
    {
        return $this->hasMany(PhaseEquipment::class);
    }

    /**
     * 機材移動履歴との関連
     */
    public function equipmentMovements(): HasMany
    {
        return $this->hasMany(EquipmentMovement::class);
    }

    /**
     * 修理記録との関連
     */
    public function repairRecords(): HasMany
    {
        return $this->hasMany(RepairRecord::class);
    }

    /**
     * 現在修理中の記録
     */
    public function currentRepairs(): HasMany
    {
        return $this->repairRecords()->whereIn('status', ['reported', 'in_progress']);
    }

    /**
     * 修理中かどうかをチェック
     */
    public function isUnderRepair(): bool
    {
        return $this->status === 'repair' || $this->currentRepairs()->exists();
    }

    /**
     * 修理履歴を取得（期間指定可能）
     */
    public function getRepairHistory($startDate = null, $endDate = null)
    {
        $query = $this->repairRecords()->with('reportedBy');

        if ($startDate && $endDate) {
            $query->reportedBetween($startDate, $endDate);
        }

        return $query->orderBy('reported_at', 'desc')->get();
    }

    /**
     * 修理コストの合計を取得（期間指定可能）
     */
    public function getTotalRepairCost($startDate = null, $endDate = null): float
    {
        $query = $this->repairRecords()->whereNotNull('repair_cost');

        if ($startDate && $endDate) {
            $query->reportedBetween($startDate, $endDate);
        }

        return $query->sum('repair_cost') ?? 0.0;
    }

    /**
     * 現在使用中のフェーズ機材記録
     */
    public function currentUsages(): HasMany
    {
        return $this->phaseEquipments()->whereIn('status', ['reserved', 'checked_out']);
    }

    /**
     * 機材使用履歴（期間指定）
     */
    public function getUsageHistory($startDate = null, $endDate = null)
    {
        return EquipmentMovement::getUsageHistory($this->id, $startDate, $endDate);
    }

    /**
     * 指定期間での使用可能性チェック
     */
    public function isAvailableInPeriod($startDate, $endDate): bool
    {
        if ($this->status !== 'available' || $this->is_discard) {
            return false;
        }

        return ! PhaseEquipment::hasEquipmentConflict($this->id, $startDate, $endDate);
    }

    /**
     * 指定期間での使用可能数量（数量管理機材のみ）
     */
    public function getAvailableQuantityInPeriod($startDate, $endDate): int
    {
        if ($this->management_type !== 'quantity') {
            return $this->isAvailableInPeriod($startDate, $endDate) ? 1 : 0;
        }

        return PhaseEquipment::getAvailableQuantity($this->id, $startDate, $endDate);
    }

    /**
     * この機材の代替機候補を検索
     */
    public function findAlternatives(array $excludeIds = []): \Illuminate\Database\Eloquent\Collection
    {
        $excludeIds[] = $this->id; // 自分自身を除外

        // 1. 同一機材名の代替機を検索（最優先）
        $sameNameAlternatives = static::where('name', $this->name)
            ->whereNotIn('id', $excludeIds)
            ->available()
            ->ordered()
            ->get();

        if ($sameNameAlternatives->isNotEmpty()) {
            return $sameNameAlternatives;
        }

        // 2. 同一サブカテゴリの代替機を検索
        $sameSubcategoryAlternatives = static::where('subcategory_id', $this->subcategory_id)
            ->whereNotIn('id', $excludeIds)
            ->available()
            ->ordered()
            ->get();

        if ($sameSubcategoryAlternatives->isNotEmpty()) {
            return $sameSubcategoryAlternatives;
        }

        // 3. 同一カテゴリの代替機を検索
        return static::whereHas('subcategory', function ($query) {
            $query->where('category_id', $this->subcategory->category_id);
        })
            ->whereNotIn('id', $excludeIds)
            ->available()
            ->ordered()
            ->get();
    }

    /**
     * 将来の使用予約を取得
     */
    public function getFutureReservations(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->phaseEquipments()
            ->whereHas('phase', function ($query) {
                $query->where('start_date', '>=', now()->format('Y-m-d'));
            })
            ->with(['phase.performance'])
            ->get()
            ->sortBy('phase.start_date');
    }

    /**
     * 将来予約があるかチェック
     */
    public function hasFutureReservations(): bool
    {
        return $this->getFutureReservations()->isNotEmpty();
    }
}
