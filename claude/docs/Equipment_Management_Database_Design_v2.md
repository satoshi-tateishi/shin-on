# 機材管理システム データベース設計書 v2.0

## 設計概要

| 項目 | 値 |
|------|-----|
| DBMS | MySQL 8.0 |
| 文字セット | utf8mb4 |
| 照合順序 | utf8mb4_unicode_ci |
| エンジン | InnoDB |
| テーブル数 | 18テーブル |

---

## ER図（簡略版）

```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│   users     │────<│performance_ │>────│performances │
└─────────────┘     │   staff     │     └─────────────┘
                    └─────────────┘            │
                                               │
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│ equipments  │────<│   phase_    │>────│   phases    │
└─────────────┘     │ equipment   │     └─────────────┘
       │            └─────────────┘
       │
       ├────<equipment_movements
       ├────<repair_records
       └────<inventory_snapshots
```

---

## テーブル一覧

### マスタテーブル

| テーブル | 説明 | 主要カラム |
|----------|------|-----------|
| users | ユーザー | name, email, lineworks_id, role |
| positions | ポジション | name, sort |
| equipment_categories | 機材大分類 | name, sort |
| equipment_subcategories | 機材中分類 | category_id, name |
| equipments | 機材 | subcategory_id, name, company_number, management_type, location_id, now_location_id |
| equipment_sets | 機材セット | name |
| equipment_set_items | セット構成 | equipment_set_id, equipment_id, quantity |
| locations | 場所・倉庫 | type, name, address |
| productions | プロダクション | type, name |

### 業務テーブル

| テーブル | 説明 | 主要カラム |
|----------|------|-----------|
| performances | 公演 | title, performance_type, start_date, end_date, status |
| phases | フェーズ | performance_id, name, start_date, end_date, location_id |
| performance_staff | 公演担当者 | performance_id, user_id, position_id |
| phase_equipment | フェーズ機材 | phase_id, equipment_id, status, source_phase_equipment_id |

### 履歴・ログテーブル

| テーブル | 説明 | 主要カラム |
|----------|------|-----------|
| equipment_movements | 機材移動履歴 | equipment_id, movement_type, from_location_id, to_location_id |
| repair_records | 修理記録 | equipment_id, repair_type, status, repair_cost |
| inventory_snapshots | 在庫スナップショット | snapshot_date, equipment_id, location_id, quantity |
| activity_logs | 操作履歴 | user_id, action, target_type, target_id |

### システムテーブル

| テーブル | 説明 |
|----------|------|
| company_info | 会社設定 |

---

## 主要テーブル詳細

### users

| カラム | 型 | 説明 |
|--------|-----|------|
| id | BIGINT | PK |
| name | VARCHAR(255) | 氏名 |
| email | VARCHAR(255) | メールアドレス |
| lineworks_id | VARCHAR(255) | LINE WORKS SSO用ID |
| role | ENUM | viewer/general/editor/admin |
| is_designer | BOOLEAN | サウンドデザイナーフラグ |
| is_staff | BOOLEAN | 担当者フラグ |

### equipments

| カラム | 型 | 説明 |
|--------|-----|------|
| id | BIGINT | PK |
| subcategory_id | BIGINT | サブカテゴリID |
| name | VARCHAR(255) | 機材名 |
| company_number | VARCHAR(50) | 新音番号 |
| management_type | ENUM | individual/quantity |
| quantity | INT | 数量（数量管理時） |
| status | ENUM | available/in_use/repair/maintenance/retired/lost |
| location_id | BIGINT | 基本倉庫ID |
| now_location_id | BIGINT | 現在地ID |

### phases

| カラム | 型 | 説明 |
|--------|-----|------|
| id | BIGINT | PK |
| performance_id | BIGINT | 公演ID |
| name | VARCHAR(255) | フェーズ名 |
| start_date | DATE | 開始日 |
| end_date | DATE | 終了日 |
| location_id | BIGINT | 実施場所ID |

### phase_equipment

| カラム | 型 | 説明 |
|--------|-----|------|
| id | BIGINT | PK |
| phase_id | BIGINT | フェーズID |
| equipment_id | BIGINT | 機材ID |
| quantity | INT | 使用数量 |
| status | ENUM | reserved/checked_out/checked_in/cancelled |
| checkout_date | DATE | 出庫日 |
| checkin_date | DATE | 返却日 |
| source_phase_equipment_id | BIGINT | 継承元ID |

### repair_records

| カラム | 型 | 説明 |
|--------|-----|------|
| id | BIGINT | PK |
| equipment_id | BIGINT | 機材ID |
| repair_type | ENUM | preventive/corrective/emergency |
| status | ENUM | reported/in_progress/completed/cancelled |
| problem_description | TEXT | 問題内容 |
| repair_cost | DECIMAL | 修理費用 |
| repair_company | VARCHAR | 修理業者 |

### activity_logs

| カラム | 型 | 説明 |
|--------|-----|------|
| id | BIGINT | PK |
| user_id | BIGINT | ユーザーID |
| action | VARCHAR(50) | アクション種別 |
| target_type | VARCHAR(100) | 対象モデル |
| target_id | BIGINT | 対象ID |
| description | TEXT | 説明 |
| changes | JSON | 変更内容 |
| ip_address | VARCHAR(45) | IPアドレス |

---

## ENUM値一覧

### users.role
| 値 | 説明 |
|----|------|
| viewer | 閲覧のみ |
| general | 一般（担当公演のみ編集可） |
| editor | 編集者 |
| admin | 管理者 |

### equipments.management_type
| 値 | 説明 |
|----|------|
| individual | 個体管理（新音番号で識別） |
| quantity | 数量管理 |

### equipments.status
| 値 | 説明 |
|----|------|
| available | 利用可能 |
| in_use | 使用中 |
| repair | 修理中 |
| maintenance | メンテナンス中 |
| retired | 廃棄 |
| lost | 紛失 |

### phase_equipment.status
| 値 | 説明 |
|----|------|
| reserved | 予約済み |
| checked_out | 出庫中 |
| checked_in | 返却済み |
| cancelled | キャンセル |

### repair_records.repair_type
| 値 | 説明 |
|----|------|
| preventive | 予防保全 |
| corrective | 故障修理 |
| emergency | 緊急修理 |

### locations.type
| 値 | 説明 |
|----|------|
| 劇場 | 劇場 |
| 稽古場 | 稽古場 |
| 倉庫 | 倉庫 |

---

## 重要なインデックス

### 期間重複チェック用
```sql
-- phases
INDEX idx_date_range (start_date, end_date)

-- phase_equipment
INDEX idx_equipment_phase (equipment_id, phase_id)
```

### 在庫計算用
```sql
-- equipment_movements
INDEX idx_equipment_moved_at (equipment_id, moved_at)

-- inventory_snapshots
INDEX idx_snapshot_date (snapshot_date)
```

---

## 外部キー制約

| 制約 | 説明 |
|------|------|
| CASCADE | 親削除時に子も削除（phases→phase_equipment等） |
| RESTRICT | 子が存在する場合削除禁止（categories→subcategories等） |
| SET NULL | 親削除時にNULL設定（users→checkout_user_id等） |

---

**最終更新**: 2025年12月5日
**バージョン**: 2.2
