<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TwoFactorLog extends Model
{
    /**
     * updated_atカラムを使用しない
     */
    const UPDATED_AT = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'action',
        'ip_address',
        'user_agent',
    ];

    /**
     * ユーザーとのリレーション
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * ログを記録する静的メソッド
     *
     * @param  string  $action  アクション (sent, verified, failed, locked, resent)
     */
    public static function log(
        ?int $userId,
        string $action,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): void {
        self::create([
            'user_id' => $userId,
            'action' => $action,
            'ip_address' => $ipAddress ?? request()->ip(),
            'user_agent' => $userAgent ?? request()->userAgent(),
        ]);
    }
}
