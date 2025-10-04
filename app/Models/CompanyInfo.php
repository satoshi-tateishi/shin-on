<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyInfo extends Model
{
    protected $fillable = [
        'company_name',
        'postal_code',
        'address',
        'phone',
        'repair_contact_person',
        'repair_contact_email',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * アクティブな会社情報を取得（1件のみ）
     */
    public static function getActiveCompanyInfo(): ?self
    {
        return self::where('is_active', true)->first();
    }

    /**
     * 会社情報が登録されているかチェック
     */
    public static function hasCompanyInfo(): bool
    {
        return self::where('is_active', true)->exists();
    }
}
