# アクティビティログ機能 仕様書

## 概要

システム上で行われた操作の履歴を記録・表示する機能。

### 主な機能
- アクティビティの自動記録
- カテゴリ別・ユーザー別フィルタリング
- 6ヶ月経過ログの自動削除

---

## データベース

### activity_logs テーブル

| カラム | 型 | 説明 |
|--------|-----|------|
| id | BIGINT | PK |
| user_id | BIGINT | 操作者ID |
| action | VARCHAR(255) | アクション種別 |
| subject_type | VARCHAR(255) | 対象モデルクラス |
| subject_id | BIGINT | 対象モデルID |
| subject_name | VARCHAR(255) | 対象名（検索用キャッシュ） |
| description | TEXT | 詳細説明 |
| properties | JSON | 追加データ |

---

## アクション種別

| アクション | ラベル | 色 |
|-----------|--------|-----|
| `equipment.checkout` | 出庫 | blue |
| `equipment.checkin` | 返却 | green |
| `equipment.transfer` | 移動 | yellow |
| `equipment.repair_start` | 修理開始 | orange |
| `equipment.repair_complete` | 修理完了 | teal |
| `performance.create` | 公演作成 | indigo |
| `performance.update` | 公演編集 | indigo |
| `phase.create` | フェーズ作成 | purple |
| `phase.update` | フェーズ編集 | purple |
| `inheritance.execute` | 継承 | cyan |
| `user.login` | ログイン | emerald |

---

## 画面仕様

### アクティビティログ一覧 (`/activity-logs`)

| 機能 | 説明 |
|------|------|
| ユーザーフィルター | ドロップダウンで特定ユーザーを絞り込み |
| カテゴリフィルター | 統計カードクリックでカテゴリ別表示 |
| 統計表示 | 各カテゴリの件数をリアルタイム表示 |
| ページネーション | 20件ずつ表示 |

### フィルター

| パラメータ | 説明 |
|-----------|------|
| `filter=all` | すべて |
| `filter=equipment` | 機材 |
| `filter=performance` | 公演 |
| `filter=phase` | フェーズ |
| `filter=login` | ログイン |
| `user_id={id}` | ユーザー指定 |

---

## 自動クリーンアップ

### コマンド

```bash
# 基本使用（6ヶ月経過分を削除）
php artisan activity-logs:cleanup

# 月数を指定
php artisan activity-logs:cleanup --months=3

# ドライラン（削除せず件数確認のみ）
php artisan activity-logs:cleanup --dry-run
```

### スケジュール

```
毎週日曜 03:00 に6ヶ月経過分を自動削除
```

---

## 使用方法

### ログ記録

```php
use App\Services\ActivityLogService;

// コンストラクタインジェクション
public function __construct(private ActivityLogService $activityLog) {}

// 機材出庫ログ
$this->activityLog->logEquipmentCheckout($phaseEquipment, $phase, $quantity);

// 機材返却ログ
$this->activityLog->logEquipmentCheckin($phaseEquipment, $phase, $quantity);

// ログインログ
$this->activityLog->logUserLogin($user);

// 継承ログ
$this->activityLog->logInheritanceExecute($sourcePhase, $targetPhase, $count);
```

---

## 関連ファイル

| 種別 | ファイル |
|------|----------|
| モデル | `app/Models/ActivityLog.php` |
| サービス | `app/Services/ActivityLogService.php` |
| コントローラー | `app/Http/Controllers/ActivityLogController.php` |
| コマンド | `app/Console/Commands/CleanupActivityLogs.php` |
| ビュー | `resources/views/activity-logs/index.blade.php` |
| スケジューラー | `routes/console.php` |

---

**最終更新**: 2025年12月5日
