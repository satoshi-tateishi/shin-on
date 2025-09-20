<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'sort',
        'name',
        'furigana',
        'email',
        'lineworks_id',
        'lineworks_token',
        'lineworks_refresh_token',
        'icon',
        'mobile_phone',
        'is_active',
        'postal_code',
        'hired_at',
        'resigned_at',
        'birthday',
        'address',
        'emergency_contact_name',
        'emergency_contact_phone',
        'notes',
        'is_designer',
        'is_staff',
        'is_driver',
        'is_on_leave',
        'is_resigned',
        'role',
        'affiliation',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'remember_token',
        'lineworks_token',
        'lineworks_refresh_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'hired_at' => 'date',
            'resigned_at' => 'date',
            'birthday' => 'date',
            'sort' => 'integer',
            'is_active' => 'boolean',
            'is_designer' => 'boolean',
            'is_staff' => 'boolean',
            'is_driver' => 'boolean',
            'is_on_leave' => 'boolean',
            'is_resigned' => 'boolean',
        ];
    }

    // スコープ: 有効なユーザー
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('is_resigned', false);
    }

    // スコープ: スタッフのみ
    public function scopeStaff($query)
    {
        return $query->where('is_staff', true);
    }

    // スコープ: デザイナーのみ
    public function scopeDesigners($query)
    {
        return $query->where('is_designer', true);
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

    // ロールのラベル
    public function getRoleLabelAttribute(): string
    {
        return match ($this->role) {
            'admin' => '管理者',
            'editor' => '編集者',
            'viewer' => '閲覧者',
            default => '不明',
        };
    }

    // 所属のラベル
    public function getAffiliationLabelAttribute(): string
    {
        return match ($this->affiliation) {
            'employee' => '社員',
            'partner' => 'パートナー',
            default => '不明',
        };
    }

    /**
     * 報告した修理記録との関連
     */
    public function reportedRepairs(): HasMany
    {
        return $this->hasMany(RepairRecord::class, 'reported_by');
    }
}
