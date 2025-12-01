# アクティビティログ機能 仕様書

## 📋 概要

システム上で行われた操作の履歴を記録・表示する機能です。機材の出庫/返却、公演の作成/編集、フェーズの操作、ユーザーログインなどのアクティビティを追跡します。

### 主な機能
- アクティビティの自動記録
- カテゴリ別フィルタリング（機材/公演/フェーズ/ログイン）
- ユーザー別フィルタリング
- 6ヶ月経過ログの自動削除

---

## 🗃️ データベース設計

### activity_logs テーブル

```sql
CREATE TABLE activity_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,           -- 操作者（nullable: 移行データ対応）
    action VARCHAR(255) NOT NULL,            -- アクション種別
    subject_type VARCHAR(255) NOT NULL,      -- 対象モデルクラス
    subject_id BIGINT UNSIGNED NOT NULL,     -- 対象モデルID
    subject_name VARCHAR(255) NULL,          -- 対象名（検索用キャッシュ）
    description TEXT NULL,                   -- 詳細説明
    properties JSON NULL,                    -- 追加データ
    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX (action),
    INDEX (subject_type, subject_id),
    INDEX (created_at)
);
```

### アクション種別一覧

| アクション | ラベル | 色 | 説明 |
|-----------|--------|-----|------|
| `equipment.checkout` | 出庫 | blue | 機材を公演に出庫 |
| `equipment.checkin` | 返却 | green | 機材を倉庫に返却 |
| `equipment.transfer` | 移動 | yellow | 倉庫間移動 |
| `equipment.repair_start` | 修理開始 | orange | 修理開始 |
| `equipment.repair_complete` | 修理完了 | teal | 修理完了 |
| `performance.create` | 公演作成 | indigo | 新規公演作成 |
| `performance.update` | 公演編集 | indigo | 公演情報編集 |
| `phase.create` | フェーズ作成 | purple | 新規フェーズ作成 |
| `phase.update` | フェーズ編集 | purple | フェーズ情報編集 |
| `inheritance.execute` | 継承 | cyan | 機材継承実行 |
| `user.login` | ログイン | emerald | ユーザーログイン |

---

## 📁 ファイル構成

```
app/
├── Models/
│   └── ActivityLog.php              # Eloquentモデル
├── Services/
│   └── ActivityLogService.php       # ログ記録サービス
├── Http/Controllers/
│   └── ActivityLogController.php    # 表示コントローラー
└── Console/Commands/
    └── CleanupActivityLogs.php      # クリーンアップコマンド

resources/views/
└── activity-logs/
    └── index.blade.php              # 一覧表示ビュー

routes/
└── console.php                      # スケジューラー設定
```

---

## 🔧 実装詳細

### ActivityLog モデル

`app/Models/ActivityLog.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'subject_type',
        'subject_id',
        'subject_name',
        'description',
        'properties',
    ];

    protected $casts = [
        'properties' => 'array',
    ];

    // リレーション
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    // スコープ
    public function scopeOrdered($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    // アクセサ
    public function getActionLabelAttribute(): string
    {
        return match ($this->action) {
            'equipment.checkout' => '出庫',
            'equipment.checkin' => '返却',
            'equipment.transfer' => '移動',
            'equipment.repair_start' => '修理開始',
            'equipment.repair_complete' => '修理完了',
            'performance.create' => '公演作成',
            'performance.update' => '公演編集',
            'phase.create' => 'フェーズ作成',
            'phase.update' => 'フェーズ編集',
            'inheritance.execute' => '継承',
            'user.login' => 'ログイン',
            default => '操作',
        };
    }

    public function getActionColorAttribute(): string
    {
        return match ($this->action) {
            'equipment.checkout' => 'text-blue-600 bg-blue-100',
            'equipment.checkin' => 'text-green-600 bg-green-100',
            'equipment.transfer' => 'text-yellow-600 bg-yellow-100',
            'equipment.repair_start' => 'text-orange-600 bg-orange-100',
            'equipment.repair_complete' => 'text-teal-600 bg-teal-100',
            'performance.create', 'performance.update' => 'text-indigo-600 bg-indigo-100',
            'phase.create', 'phase.update' => 'text-purple-600 bg-purple-100',
            'inheritance.execute' => 'text-cyan-600 bg-cyan-100',
            'user.login' => 'text-emerald-600 bg-emerald-100',
            default => 'text-gray-600 bg-gray-100',
        };
    }

    public function getRelativeTimeAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }
}
```

### ActivityLogService

`app/Services/ActivityLogService.php`

```php
<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Equipment;
use App\Models\Performance;
use App\Models\Phase;
use App\Models\PhaseEquipment;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ActivityLogService
{
    // 汎用ログ記録
    public function log(string $action, Model $subject, ?string $description = null, ?array $properties = null): ActivityLog
    {
        return ActivityLog::log($action, $subject, $description, $properties);
    }

    // 機材出庫ログ
    public function logEquipmentCheckout(PhaseEquipment $phaseEquipment, Phase $phase, int $quantity): ActivityLog
    {
        $equipment = $phaseEquipment->equipment;
        $performance = $phase->performance;

        return $this->log(
            'equipment.checkout',
            $equipment,
            "{$equipment->name} × {$quantity} を「{$performance->title}」{$phase->name}に出庫",
            [
                'phase_equipment_id' => $phaseEquipment->id,
                'phase_id' => $phase->id,
                'quantity' => $quantity,
            ]
        );
    }

    // 機材返却ログ
    public function logEquipmentCheckin(PhaseEquipment $phaseEquipment, Phase $phase, int $quantity): ActivityLog
    {
        // ... 実装省略
    }

    // ログインログ
    public function logUserLogin(User $user): ActivityLog
    {
        return ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'user.login',
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'subject_name' => $user->name,
            'description' => "{$user->name} がログインしました",
            'properties' => [
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ],
        ]);
    }
}
```

---

## 🖥️ 画面仕様

### アクティビティログ一覧 (`/activity-logs`)

#### 機能
1. **ユーザーフィルター**: ドロップダウンで特定ユーザーのログを絞り込み
2. **カテゴリフィルター**: 統計カードをクリックしてカテゴリ別表示
3. **統計表示**: 各カテゴリの件数をリアルタイム表示
4. **ページネーション**: 20件ずつ表示

#### フィルターカード
| カード | フィルター | 色 |
|--------|-----------|-----|
| すべて | `filter=all` | グレー |
| 機材 | `filter=equipment` | 青 |
| 公演 | `filter=performance` | インディゴ |
| フェーズ | `filter=phase` | 紫 |
| ログイン | `filter=login` | エメラルド |

#### URLパラメータ
```
/activity-logs?filter=equipment&user_id=1&page=2
```

---

## 🗑️ 自動クリーンアップ

### Artisan コマンド

`app/Console/Commands/CleanupActivityLogs.php`

```bash
# 基本使用（6ヶ月経過分を削除）
php artisan activity-logs:cleanup

# 月数を指定
php artisan activity-logs:cleanup --months=3

# ドライラン（削除せず件数確認のみ）
php artisan activity-logs:cleanup --dry-run

# 確認なしで実行（スケジューラー用）
php artisan activity-logs:cleanup --no-interaction
```

### オプション

| オプション | デフォルト | 説明 |
|-----------|-----------|------|
| `--months` | 6 | 削除対象の経過月数 |
| `--dry-run` | false | 削除せず件数のみ表示 |
| `--no-interaction` | false | 確認プロンプトをスキップ |

### スケジューラー設定

`routes/console.php`

```php
// アクティビティログクリーンアップ（毎週日曜深夜3時に実行、6ヶ月経過分を削除）
Schedule::command('activity-logs:cleanup --months=6')->weeklyOn(0, '03:00');
```

---

## ⏰ Cron 設定

Laravelスケジューラーを動作させるために、サーバーのcronに以下を設定します。

### ローカル開発環境（Docker/Sail）

```bash
# スケジューラーを手動実行
./vendor/bin/sail artisan schedule:run

# スケジュール一覧を確認
./vendor/bin/sail artisan schedule:list
```

### 本番サーバー設定

#### 1. crontab を開く

```bash
crontab -e
```

#### 2. 以下の行を追加

```cron
* * * * * cd /var/www/shin-on && php artisan schedule:run >> /dev/null 2>&1
```

> **注意**: `/var/www/shin-on` はプロジェクトの実際のパスに置き換えてください。

#### 3. crontab を確認

```bash
crontab -l
```

### スケジュール実行タイミング

| コマンド | 実行タイミング | 説明 |
|---------|---------------|------|
| `logs:clear --days=7` | 毎日 02:00 | Laravelログファイルのクリーンアップ |
| `activity-logs:cleanup --months=6` | 毎週日曜 03:00 | アクティビティログのクリーンアップ |

### 実行ログの確認

```bash
# スケジューラーの実行状況を確認
tail -f /var/www/shin-on/storage/logs/laravel.log

# 手動でスケジュール実行（テスト用）
php artisan schedule:run --verbose
```

---

## 🔗 関連ファイル

- `app/Models/ActivityLog.php` - モデル
- `app/Services/ActivityLogService.php` - サービスクラス
- `app/Http/Controllers/ActivityLogController.php` - コントローラー
- `app/Console/Commands/CleanupActivityLogs.php` - クリーンアップコマンド
- `resources/views/activity-logs/index.blade.php` - 一覧ビュー
- `resources/views/dashboard.blade.php` - ダッシュボード（サマリー表示）
- `routes/console.php` - スケジューラー設定
- `database/migrations/2025_12_01_221731_create_activity_logs_table.php` - マイグレーション

---

## 📝 使用例

### ログ記録（コントローラーから）

```php
use App\Services\ActivityLogService;

class EquipmentController extends Controller
{
    public function checkout(Request $request, ActivityLogService $activityLog)
    {
        // 出庫処理...

        // ログ記録
        $activityLog->logEquipmentCheckout($phaseEquipment, $phase, $quantity);
    }
}
```

### ログ記録（認証コントローラーから）

```php
use App\Services\ActivityLogService;

// ログイン成功時
auth()->login($user);
app(ActivityLogService::class)->logUserLogin($user);
```

---

## 🚀 今後の拡張予定

- [ ] CSVエクスポート機能
- [ ] 日付範囲フィルター
- [ ] 操作の取り消し（Undo）機能
- [ ] Slack/LINE WORKS通知連携

---

**最終更新**: 2025年12月1日
