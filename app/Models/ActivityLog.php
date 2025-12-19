<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    // updated_atは使用しない（ログの不変性保持）
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'action',
        'subject_type',
        'subject_id',
        'subject_name',
        'description',
        'properties',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'properties' => 'array',
        ];
    }

    /**
     * 操作者との関連
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 対象モデルとの多態関連
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * スコープ: アクション別フィルタ
     */
    public function scopeAction($query, $action)
    {
        return $query->where('action', $action);
    }

    /**
     * スコープ: アクションプレフィックス別フィルタ（例: equipment.*）
     */
    public function scopeActionPrefix($query, $prefix)
    {
        return $query->where('action', 'like', $prefix.'%');
    }

    /**
     * スコープ: 機材操作のみ
     */
    public function scopeEquipmentActions($query)
    {
        return $query->actionPrefix('equipment.');
    }

    /**
     * スコープ: 公演操作のみ
     */
    public function scopePerformanceActions($query)
    {
        return $query->actionPrefix('performance.');
    }

    /**
     * スコープ: フェーズ操作のみ
     */
    public function scopePhaseActions($query)
    {
        return $query->actionPrefix('phase.');
    }

    /**
     * スコープ: 特定ユーザーの履歴
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * スコープ: 特定対象の履歴
     */
    public function scopeForSubject($query, $subjectType, $subjectId)
    {
        return $query->where('subject_type', $subjectType)
            ->where('subject_id', $subjectId);
    }

    /**
     * スコープ: 日付順での並び替え
     */
    public function scopeOrdered($query, $direction = 'desc')
    {
        return $query->orderBy('created_at', $direction)->orderBy('id', $direction);
    }

    /**
     * アクションタイプのラベル取得
     */
    public function getActionLabelAttribute(): string
    {
        return match ($this->action) {
            'equipment.checkout' => '出庫',
            'equipment.checkin' => '返却',
            'equipment.transfer' => '倉庫間移動',
            'equipment.maintenance' => 'メンテナンス',
            'equipment.disposal' => '廃棄',
            'equipment.repair_start' => '修理開始',
            'equipment.repair_complete' => '修理完了',
            'performance.create' => '公演作成',
            'performance.update' => '公演編集',
            'phase.create' => 'フェーズ作成',
            'phase.update' => 'フェーズ編集',
            'inheritance.execute' => '機材継承',
            'user.login' => 'ログイン',
            default => '操作',
        };
    }

    /**
     * アクションタイプの色クラス取得
     */
    public function getActionColorAttribute(): string
    {
        return match ($this->action) {
            'equipment.checkout' => 'text-orange-600 bg-orange-100',
            'equipment.checkin' => 'text-green-600 bg-green-100',
            'equipment.transfer' => 'text-blue-600 bg-blue-100',
            'equipment.maintenance' => 'text-yellow-600 bg-yellow-100',
            'equipment.disposal' => 'text-red-600 bg-red-100',
            'equipment.repair_start' => 'text-purple-600 bg-purple-100',
            'equipment.repair_complete' => 'text-teal-600 bg-teal-100',
            'performance.create' => 'text-indigo-600 bg-indigo-100',
            'performance.update' => 'text-indigo-600 bg-indigo-100',
            'phase.create' => 'text-pink-600 bg-pink-100',
            'phase.update' => 'text-pink-600 bg-pink-100',
            'inheritance.execute' => 'text-cyan-600 bg-cyan-100',
            'user.login' => 'text-emerald-600 bg-emerald-100',
            default => 'text-gray-600 bg-gray-100',
        };
    }

    /**
     * アクションカテゴリの取得
     */
    public function getActionCategoryAttribute(): string
    {
        $parts = explode('.', $this->action);

        return $parts[0] ?? 'unknown';
    }

    /**
     * 相対時間の取得
     */
    public function getRelativeTimeAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * 静的メソッド: アクティビティログの作成
     */
    public static function log(
        string $action,
        Model $subject,
        ?string $description = null,
        ?array $properties = null,
        ?int $userId = null
    ): self {
        return self::create([
            'user_id' => $userId ?? auth()->id(),
            'action' => $action,
            'subject_type' => get_class($subject),
            'subject_id' => $subject->getKey(),
            'subject_name' => self::getSubjectName($subject),
            'description' => $description,
            'properties' => $properties,
        ]);
    }

    /**
     * 対象モデルから名前を取得
     */
    protected static function getSubjectName(Model $subject): ?string
    {
        // 各モデルの名前属性を優先度順に検索
        $nameAttributes = ['name', 'title', 'display_name'];

        foreach ($nameAttributes as $attr) {
            if (isset($subject->{$attr})) {
                return $subject->{$attr};
            }
        }

        return null;
    }
}
