# 在庫管理システム仕様書

## 概要
倉庫別の機材在庫状況を管理するシステム。基準日指定での在庫照会、倉庫間移動、返却機能を提供。

**実装状況**: 完了（2025年9月）

## 主要機能

### 1. 基準日指定在庫表示
- 任意の日付時点での在庫状況を表示
- 倉庫・カテゴリ・機材名でフィルタリング
- PDF出力対応（単一倉庫/全倉庫）

### 2. 倉庫間移動
- 個体管理機材の倉庫間移動
- `equipments.location_id`の直接更新方式
- 対象: `management_type='individual'`の機材のみ

### 3. 基本倉庫と現在地の分離
- `location_id`: 基本倉庫（返却先）
- `now_location_id`: 現在の所在地
- 一発返却機能（現在地→基本倉庫）

## データベース設計

### 機材テーブル拡張
```sql
-- equipments テーブル
location_id      -- 基本倉庫（返却先）
now_location_id  -- 現在の所在地（NULL可）
```

### 在庫スナップショット
```sql
-- inventory_snapshots テーブル
snapshot_date    DATE NOT NULL
equipment_id     BIGINT UNSIGNED NOT NULL
location_id      BIGINT UNSIGNED NULL
quantity         INT NOT NULL
```

## API エンドポイント

| メソッド | パス | 説明 |
|----------|------|------|
| GET | `/inventory/api/inventory` | 在庫一覧取得 |
| GET | `/inventory/export-pdf` | PDF出力 |
| POST | `/inventory/transfer` | 倉庫間移動 |

### パラメータ例
```
GET /inventory/api/inventory?as_of_date=2025-09-22&location_id=92&category_id=1
```

## 画面構成

| 画面 | パス | 説明 |
|------|------|------|
| 在庫一覧 | `/inventory` | 倉庫別在庫表示・検索 |
| 倉庫間移動 | `/inventory/transfer` | 機材移動登録 |

## 技術仕様

### 在庫計算ロジック
1. 基準日時点の全機材を取得
2. 各機材の`now_location_id`（なければ`location_id`）で所在地判定
3. フェーズ使用中（`checked_out`）の機材は除外

### パフォーマンス目標
- 在庫一覧表示: 2秒以内
- 同時ユーザー: 30人
- 対応機材数: 5,000個

## 関連ファイル

### コントローラー
- `app/Http/Controllers/InventoryController.php`

### ビュー
- `resources/views/inventory/index.blade.php`
- `resources/views/inventory/transfer.blade.php`

### モデル
- `app/Models/Equipment.php` - 在庫関連メソッド
- `app/Models/InventorySnapshot.php`

---
**最終更新**: 2025年9月
