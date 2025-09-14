# 機材管理システム データベース設計書

## 📋 設計概要

### プロジェクト名
**shin-on 機材管理システム データベース設計**

### 対象システム
演劇・ミュージカル公演における機材の入出庫管理及び機材スケジュール管理

### データベース仕様
- **DBMS**: MySQL 8.0
- **文字セット**: utf8mb4
- **照合順序**: utf8mb4_unicode_ci
- **エンジン**: InnoDB
- **総テーブル数**: 17テーブル

### 設計方針
- **正規化**: 第3正規形まで実施
- **パフォーマンス**: 5,000機材・100公演対応
- **拡張性**: 将来機能追加に対応
- **整合性**: 外部キー制約による参照整合性確保

---

## 🗄️ テーブル構成

### テーブル分類
| 分類 | テーブル数 | 説明 |
|------|-----------|------|
| **マスタテーブル** | 8 | 基準データ管理 |
| **業務テーブル** | 4 | 公演・フェーズ管理 |
| **セット管理テーブル** | 2 | 機材セット管理 |
| **履歴・ログテーブル** | 3 | 移動・修理履歴 |

---

## 🎭 マスタテーブル設計

### 1. users - ユーザーマスタ

**概要**: LINE WORKS SSO連携によるユーザー管理

```sql
CREATE TABLE users (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    sort INT DEFAULT 0 COMMENT 'ソート順',
    name VARCHAR(255) NOT NULL COMMENT '氏名',
    furigana VARCHAR(255) NULL COMMENT 'ふりがな',
    email VARCHAR(255) UNIQUE NOT NULL COMMENT 'メールアドレス',
    lineworks_id VARCHAR(255) UNIQUE NULL COMMENT 'LINE WORKS SSO用ID',
    lineworks_token TEXT NULL COMMENT 'LINE WORKSアクセストークン',
    lineworks_refresh_token TEXT NULL COMMENT 'LINE WORKSリフレッシュトークン',
    mobile_phone VARCHAR(255) NULL COMMENT '携帯電話',
    hired_at DATE NULL COMMENT '入社日',
    resigned_at DATE NULL COMMENT '退職日',
    birthday DATE NULL COMMENT '生年月日',
    address TEXT NULL COMMENT '住所',
    postal_code VARCHAR(8) NULL COMMENT '郵便番号',
    emergency_contact_name VARCHAR(255) NULL COMMENT '緊急連絡先名',
    emergency_contact_phone VARCHAR(255) NULL COMMENT '緊急連絡先電話',
    notes TEXT NULL COMMENT '備考',
    is_designer BOOLEAN DEFAULT FALSE COMMENT 'サウンドデザイナー選択に表示するか',
    is_staff BOOLEAN DEFAULT FALSE COMMENT '公演担当者選択に表示するか（担当者フラグ）',
    is_driver BOOLEAN DEFAULT FALSE COMMENT 'ドライバーフラグ',
    is_on_leave BOOLEAN DEFAULT FALSE COMMENT '休職フラグ',
    is_resigned BOOLEAN DEFAULT FALSE COMMENT '退職フラグ',
    affiliation ENUM('employee', 'partner') DEFAULT 'employee' COMMENT '所属',
    role ENUM('viewer', 'editor', 'admin') DEFAULT 'viewer' COMMENT '権限',
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,

    INDEX idx_lineworks_id (lineworks_id),
    INDEX idx_email (email),
    INDEX idx_affiliation (affiliation),
    INDEX idx_role (role),
    INDEX idx_is_designer (is_designer),
    INDEX idx_is_staff (is_staff),
    INDEX idx_is_resigned (is_resigned)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 2. positions - ポジションマスタ

**概要**: 公演での役割・ポジション管理

```sql
CREATE TABLE positions (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    sort INT DEFAULT 0 COMMENT 'ソート順',
    name VARCHAR(255) NOT NULL COMMENT 'ポジション名',
    is_active BOOLEAN DEFAULT TRUE COMMENT '有効フラグ',
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,

    INDEX idx_sort (sort),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**初期データ例**:
- サウンドデザイナー
- チーフ：再生
- チーフ：PA
- サブ：再生
- サブ：PA
- サブ：ステージ
- サブ

### 3. equipment_categories - 機材大分類マスタ

**概要**: 機材の大分類管理

```sql
CREATE TABLE equipment_categories (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    sort INT DEFAULT 0 COMMENT 'ソート順',
    name VARCHAR(255) NOT NULL COMMENT '大分類名',
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    INDEX idx_sort (sort)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 4. equipment_subcategories - 機材中分類マスタ

**概要**: 機材の中分類管理（大分類に紐づく）

```sql
CREATE TABLE equipment_subcategories (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    category_id BIGINT UNSIGNED NOT NULL COMMENT '大分類ID',
    sort INT DEFAULT 0 COMMENT 'ソート順',
    name VARCHAR(255) NOT NULL COMMENT '中分類名',
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,

    FOREIGN KEY (category_id) REFERENCES equipment_categories(id) ON DELETE CASCADE,
    INDEX idx_category_id (category_id),
    INDEX idx_sort (sort)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**階層構造例**:
```
ミキシングコンソール (大分類: equipment_categories)
├── デジタルミキサー (中分類: equipment_subcategories)
└── パワーサプライ (中分類: equipment_subcategories)
└── I/O Rack (中分類: equipment_subcategories)

スピーカー (大分類: equipment_categories)
├── Meyer Sound (中分類: equipment_subcategories)
└── JBL (中分類: equipment_subcategories)
└── TOA (中分類: equipment_subcategories)
```

### 5. equipments - 機材マスタ

**概要**: 機材の統合管理（個体管理・数量管理）

```sql
CREATE TABLE equipments (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    subcategory_id BIGINT UNSIGNED NOT NULL COMMENT '中分類ID',
    sort INT DEFAULT 0 COMMENT 'ソート順',
    manufacturer VARCHAR(255) NULL COMMENT 'メーカー名',
    name VARCHAR(255) NOT NULL COMMENT '機材名',
    company_number VARCHAR(50) NULL COMMENT '新音番号',
    management_type ENUM('individual', 'quantity') DEFAULT 'individual' COMMENT '管理方式',
    quantity INT DEFAULT 1 COMMENT '在庫数量',
    unit ENUM('台', '個', '本', '箱', 'ケース', 'ラック', 'セット') DEFAULT '台' COMMENT '単位',
    model_number VARCHAR(100) NULL COMMENT '型番',
    serial_number VARCHAR(100) NULL COMMENT 'シリアル番号',
    supplier VARCHAR(255) NULL COMMENT '仕入先',
    purchase_date DATE NULL COMMENT '購入日',
    warranty_expiry DATE NULL COMMENT '保証期限',
    price DECIMAL(12,2) NULL COMMENT '価格',
    status ENUM('available', 'in_use', 'repair', 'maintenance', 'retired', 'lost') DEFAULT 'available' COMMENT '状態',
    location_id BIGINT UNSIGNED NULL COMMENT '基本倉庫ID',
    is_discard BOOLEAN DEFAULT FALSE COMMENT '廃棄フラグ',
    discard_at DATE NULL COMMENT '廃棄日',
    notes TEXT NULL COMMENT '備考',
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,

    FOREIGN KEY (subcategory_id) REFERENCES equipment_subcategories(id) ON DELETE RESTRICT,
    FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE SET NULL,
    UNIQUE KEY unique_company_number (company_number),
    INDEX idx_subcategory_id (subcategory_id),
    INDEX idx_management_type (management_type),
    INDEX idx_status (status),
    INDEX idx_manufacturer (manufacturer),
    INDEX idx_name (name),
    INDEX idx_location_id (location_id),
    INDEX idx_is_discard (is_discard)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**管理方式の区別**:
- `individual`: 個体管理（新音番号による識別）
- `quantity`: 数量管理（在庫数による管理）

**状態管理**:
- `available`: 利用可能
- `in_use`: 使用中
- `repair`: 修理中
- `maintenance`: メンテナンス中
- `retired`: 廃棄
- `lost`: 紛失

### 6. equipment_sets - 機材セットマスタ

**概要**: 機材セット定義

```sql
CREATE TABLE equipment_sets (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    sort INT DEFAULT 0 COMMENT 'ソート順',
    name VARCHAR(255) NOT NULL COMMENT 'セット名',
    description TEXT NULL COMMENT '説明',
    is_active BOOLEAN DEFAULT TRUE COMMENT '有効フラグ',
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,

    INDEX idx_sort (sort),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 7. locations - 使用場所・倉庫マスタ

**概要**: 劇場・稽古場・倉庫の管理

```sql
CREATE TABLE locations (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    sort INT DEFAULT 0 COMMENT 'ソート順',
    type ENUM('劇場', '稽古場', '倉庫') NOT NULL COMMENT '場所タイプ',
    name VARCHAR(255) NOT NULL COMMENT '場所名',
    furigana VARCHAR(255) NULL COMMENT 'ふりがな',
    tel1_name VARCHAR(255) NULL COMMENT '電話1名称',
    tel1 VARCHAR(255) NULL COMMENT '電話1',
    tel2_name VARCHAR(255) NULL COMMENT '電話2名称',
    tel2 VARCHAR(255) NULL COMMENT '電話2',
    fax VARCHAR(255) NULL COMMENT 'FAX',
    email1_name VARCHAR(255) NULL COMMENT 'メール1名称',
    email1 VARCHAR(255) NULL COMMENT 'メール1',
    email2_name VARCHAR(255) NULL COMMENT 'メール2名称',
    email2 VARCHAR(255) NULL COMMENT 'メール2',
    postal_code VARCHAR(8) NULL COMMENT '郵便番号',
    address TEXT NULL COMMENT '住所',
    note TEXT NULL COMMENT '備考',
    is_active BOOLEAN DEFAULT TRUE COMMENT '有効フラグ',
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,

    INDEX idx_type (type),
    INDEX idx_sort (sort),
    INDEX idx_name (name),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 8. productions - プロダクションマスタ

**概要**: 制作プロダクション管理

```sql
CREATE TABLE productions (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    sort INT DEFAULT 0 COMMENT 'ソート順',
    type ENUM('株式会社', '有限会社', '合同会社', '財団法人', '公益財団法人', 'その他') DEFAULT '株式会社' COMMENT '法人種別',
    name VARCHAR(255) NOT NULL COMMENT 'プロダクション名',
    postal_code VARCHAR(8) NULL COMMENT '郵便番号',
    address TEXT NULL COMMENT '住所',
    note TEXT NULL COMMENT '備考',
    is_active BOOLEAN DEFAULT TRUE COMMENT '有効フラグ',
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,

    INDEX idx_sort (sort),
    INDEX idx_type (type),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 🎪 業務テーブル設計

### 9. performances - 公演テーブル

**概要**: 演劇・ミュージカル公演の基本情報

```sql
CREATE TABLE performances (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL COMMENT '公演タイトル',
    subtitle VARCHAR(255) NULL COMMENT 'サブタイトル',
    performance_type ENUM('演劇', 'ミュージカル', 'コンサート', 'その他') NOT NULL COMMENT '公演種別',
    start_date DATE NULL COMMENT '公演開始日',
    end_date DATE NULL COMMENT '公演終了日',
    venue VARCHAR(255) NULL COMMENT '会場名',
    director VARCHAR(255) NULL COMMENT '演出',
    producer VARCHAR(255) NULL COMMENT 'プロデューサー',
    status ENUM('planning', 'preparation', 'in_progress', 'completed', 'cancelled') DEFAULT 'planning' COMMENT 'ステータス',
    budget DECIMAL(12,2) NULL COMMENT '予算',
    note TEXT NULL COMMENT '備考',
    is_active BOOLEAN DEFAULT TRUE COMMENT '有効フラグ',
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,

    INDEX idx_performance_type (performance_type),
    INDEX idx_start_date (start_date),
    INDEX idx_status (status),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 10. phases - フェーズテーブル

**概要**: 公演の制作フェーズ管理（期間重複チェックの核心）

```sql
CREATE TABLE phases (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    performance_id BIGINT UNSIGNED NOT NULL COMMENT '公演ID',
    location_id BIGINT UNSIGNED NULL COMMENT '実施場所ID',
    sort INT DEFAULT 0 COMMENT 'ソート順（フェーズの順序）',
    name VARCHAR(255) NOT NULL COMMENT 'フェーズ名',
    start_date DATE NOT NULL COMMENT '開始日',
    end_date DATE NOT NULL COMMENT '終了日',
    start_time TIME NULL COMMENT '開始時間',
    end_time TIME NULL COMMENT '終了時間',
    description TEXT NULL COMMENT '説明',
    note TEXT NULL COMMENT '備考',
    is_active BOOLEAN DEFAULT TRUE COMMENT '有効フラグ',
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,

    FOREIGN KEY (performance_id) REFERENCES performances(id) ON DELETE CASCADE,
    FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE SET NULL,

    -- 期間重複チェック用のインデックス（重要）
    INDEX idx_date_range (start_date, end_date),
    INDEX idx_performance_id (performance_id),
    INDEX idx_location_id (location_id),
    INDEX idx_sort (sort),
    INDEX idx_is_active (is_active),

    -- 日付の整合性チェック
    CHECK (start_date <= end_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**フェーズ例**:
- 稽古場1
- 録音
- 記者会見
- 稽古場2
- 本番
- 旅公演

### 11. performance_staff - 公演担当者

**概要**: 公演とスタッフの関連付け（中間テーブル）

```sql
CREATE TABLE performance_staff (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    performance_id BIGINT UNSIGNED NOT NULL COMMENT '公演ID',
    user_id BIGINT UNSIGNED NOT NULL COMMENT 'ユーザーID',
    position_id BIGINT UNSIGNED NOT NULL COMMENT 'ポジションID',
    production_id BIGINT UNSIGNED NULL COMMENT 'プロダクションID',
    note TEXT NULL COMMENT '備考',
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,

    FOREIGN KEY (performance_id) REFERENCES performances(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (position_id) REFERENCES positions(id) ON DELETE RESTRICT,
    FOREIGN KEY (production_id) REFERENCES productions(id) ON DELETE SET NULL,

    -- 同一公演での重複登録防止
    UNIQUE KEY unique_performance_user_position (performance_id, user_id, position_id),

    INDEX idx_performance_id (performance_id),
    INDEX idx_user_id (user_id),
    INDEX idx_position_id (position_id),
    INDEX idx_production_id (production_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 12. phase_equipment - フェーズ機材使用

**概要**: フェーズでの機材使用記録（期間重複チェックの対象）

```sql
CREATE TABLE phase_equipment (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    phase_id BIGINT UNSIGNED NOT NULL COMMENT 'フェーズID',
    equipment_id BIGINT UNSIGNED NOT NULL COMMENT '機材ID',
    quantity INT DEFAULT 1 COMMENT '使用数量',
    checkout_date DATE NULL COMMENT '貸出日',
    checkin_date DATE NULL COMMENT '返却日',
    checkout_user_id BIGINT UNSIGNED NULL COMMENT '貸出者ID',
    checkin_user_id BIGINT UNSIGNED NULL COMMENT '返却者ID',
    status ENUM('reserved', 'checked_out', 'checked_in', 'cancelled') DEFAULT 'reserved' COMMENT 'ステータス',
    note TEXT NULL COMMENT '備考',
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,

    FOREIGN KEY (phase_id) REFERENCES phases(id) ON DELETE CASCADE,
    FOREIGN KEY (equipment_id) REFERENCES equipments(id) ON DELETE CASCADE,
    FOREIGN KEY (checkout_user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (checkin_user_id) REFERENCES users(id) ON DELETE SET NULL,

    -- 期間重複チェック用の重要なインデックス
    INDEX idx_equipment_phase (equipment_id, phase_id),
    INDEX idx_phase_id (phase_id),
    INDEX idx_status (status),

    -- 数量チェック（正の値のみ）
    CHECK (quantity > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 🔧 セット管理テーブル設計

### 13. equipment_set_items - セット構成機材

**概要**: 機材セットの構成要素（動的変更対応）

```sql
CREATE TABLE equipment_set_items (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    equipment_set_id BIGINT UNSIGNED NOT NULL COMMENT 'セットID',
    equipment_id BIGINT UNSIGNED NOT NULL COMMENT '機材ID',
    quantity INT DEFAULT 1 COMMENT '構成数量',
    is_required BOOLEAN DEFAULT TRUE COMMENT '必須フラグ',
    note TEXT NULL COMMENT '備考',
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,

    FOREIGN KEY (equipment_set_id) REFERENCES equipment_sets(id) ON DELETE CASCADE,
    FOREIGN KEY (equipment_id) REFERENCES equipments(id) ON DELETE CASCADE,

    -- 同一セット内での機材重複防止
    UNIQUE KEY unique_set_equipment (equipment_set_id, equipment_id),

    INDEX idx_equipment_set_id (equipment_set_id),
    INDEX idx_equipment_id (equipment_id),

    CHECK (quantity > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 14. equipment_set_versions - セット構成履歴

**概要**: セット構成変更の履歴管理（バージョニング）

```sql
CREATE TABLE equipment_set_versions (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    equipment_set_id BIGINT UNSIGNED NOT NULL COMMENT 'セットID',
    version INT NOT NULL COMMENT 'バージョン番号',
    change_reason VARCHAR(255) NULL COMMENT '変更理由',
    changed_by BIGINT UNSIGNED NULL COMMENT '変更者ID',
    configuration JSON NOT NULL COMMENT 'セット構成（JSON形式）',
    created_at TIMESTAMP NULL DEFAULT NULL,

    FOREIGN KEY (equipment_set_id) REFERENCES equipment_sets(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL,

    -- セット・バージョンの組み合わせは一意
    UNIQUE KEY unique_set_version (equipment_set_id, version),

    INDEX idx_equipment_set_id (equipment_set_id),
    INDEX idx_version (version),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**configuration JSON例**:
```json
{
  "items": [
    {"equipment_id": 123, "quantity": 1, "required": true},
    {"equipment_id": 456, "quantity": 2, "required": false}
  ],
  "change_summary": "ミキサー故障により代替機に変更"
}
```

---

## 📊 履歴・ログテーブル設計

### 15. equipment_movements - 機材移動履歴

**概要**: 機材の移動・使用履歴（イベントソーシング）

```sql
CREATE TABLE equipment_movements (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    equipment_id BIGINT UNSIGNED NOT NULL COMMENT '機材ID',
    movement_type ENUM('checkout', 'checkin', 'transfer', 'maintenance', 'disposal') NOT NULL COMMENT '移動タイプ',
    phase_id BIGINT UNSIGNED NULL COMMENT 'フェーズID（使用時）',
    from_location_id BIGINT UNSIGNED NULL COMMENT '移動元場所ID',
    to_location_id BIGINT UNSIGNED NULL COMMENT '移動先場所ID',
    quantity INT DEFAULT 1 COMMENT '移動数量',
    moved_by BIGINT UNSIGNED NULL COMMENT '実行者ID',
    moved_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '移動日時',
    note TEXT NULL COMMENT '備考',
    created_at TIMESTAMP NULL DEFAULT NULL,

    FOREIGN KEY (equipment_id) REFERENCES equipments(id) ON DELETE CASCADE,
    FOREIGN KEY (phase_id) REFERENCES phases(id) ON DELETE SET NULL,
    FOREIGN KEY (from_location_id) REFERENCES locations(id) ON DELETE SET NULL,
    FOREIGN KEY (to_location_id) REFERENCES locations(id) ON DELETE SET NULL,
    FOREIGN KEY (moved_by) REFERENCES users(id) ON DELETE SET NULL,

    -- 在庫計算用の重要なインデックス
    INDEX idx_equipment_moved_at (equipment_id, moved_at),
    INDEX idx_movement_type (movement_type),
    INDEX idx_phase_id (phase_id),
    INDEX idx_moved_at (moved_at),

    CHECK (quantity > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 16. inventory_snapshots - 在庫スナップショット

**概要**: 基準日時点での在庫状況記録

```sql
CREATE TABLE inventory_snapshots (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    snapshot_date DATE NOT NULL COMMENT 'スナップショット日付',
    equipment_id BIGINT UNSIGNED NOT NULL COMMENT '機材ID',
    location_id BIGINT UNSIGNED NULL COMMENT '場所ID',
    quantity INT NOT NULL COMMENT '在庫数量',
    status ENUM('available', 'in_use', 'maintenance') NOT NULL COMMENT 'ステータス',
    created_at TIMESTAMP NULL DEFAULT NULL,

    FOREIGN KEY (equipment_id) REFERENCES equipments(id) ON DELETE CASCADE,
    FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE SET NULL,

    -- 日付・機材・場所の組み合わせは一意
    UNIQUE KEY unique_snapshot (snapshot_date, equipment_id, location_id),

    -- 基準日検索用の重要なインデックス
    INDEX idx_snapshot_date (snapshot_date),
    INDEX idx_equipment_id (equipment_id),
    INDEX idx_location_id (location_id),

    CHECK (quantity >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 17. repair_records - 修理記録

**概要**: 機材の修理・メンテナンス履歴

```sql
CREATE TABLE repair_records (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    equipment_id BIGINT UNSIGNED NOT NULL COMMENT '機材ID',
    repair_type ENUM('preventive', 'corrective', 'emergency') NOT NULL COMMENT '修理タイプ',
    problem_description TEXT NOT NULL COMMENT '問題内容',
    repair_description TEXT NULL COMMENT '修理内容',
    repair_cost DECIMAL(10,2) NULL COMMENT '修理費用',
    repair_company VARCHAR(255) NULL COMMENT '修理業者',
    reported_by BIGINT UNSIGNED NULL COMMENT '報告者ID',
    repaired_by VARCHAR(255) NULL COMMENT '修理担当者',
    reported_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '報告日時',
    started_at TIMESTAMP NULL COMMENT '修理開始日時',
    completed_at TIMESTAMP NULL COMMENT '修理完了日時',
    status ENUM('reported', 'in_progress', 'completed', 'cancelled') DEFAULT 'reported' COMMENT 'ステータス',
    note TEXT NULL COMMENT '備考',
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,

    FOREIGN KEY (equipment_id) REFERENCES equipments(id) ON DELETE CASCADE,
    FOREIGN KEY (reported_by) REFERENCES users(id) ON DELETE SET NULL,

    INDEX idx_equipment_id (equipment_id),
    INDEX idx_repair_type (repair_type),
    INDEX idx_status (status),
    INDEX idx_reported_at (reported_at),
    INDEX idx_completed_at (completed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 🔗 ER図・リレーション設計

### 主要なリレーション

#### **1対多 (1:N) リレーション**
```
users (1) ←→ (N) performance_staff
positions (1) ←→ (N) performance_staff
productions (1) ←→ (N) performance_staff
performances (1) ←→ (N) phases
phases (1) ←→ (N) phase_equipment
equipments (1) ←→ (N) phase_equipment
equipment_categories (1) ←→ (N) equipment_subcategories
equipment_subcategories (1) ←→ (N) equipments
locations (1) ←→ (N) equipments (location_id)
equipment_sets (1) ←→ (N) equipment_set_items
```

#### **多対多 (N:M) リレーション**
```
performances (N) ←→ (M) users (through performance_staff)
phases (N) ←→ (M) equipments (through phase_equipment)
equipment_sets (N) ←→ (M) equipments (through equipment_set_items)
```

#### **階層リレーション**
```
equipment_categories (1) ←→ (N) equipment_subcategories (category_id)
equipment_subcategories (1) ←→ (N) equipmentss (subcategory_id)
```

### CASCADE設定方針

#### **CASCADE DELETE**
- `performance_staff` → `performances` 削除時に関連スタッフも削除
- `phases` → `performances` 削除時に関連フェーズも削除
- `phase_equipment` → `phases` 削除時に関連機材使用も削除

#### **SET NULL**
- `equipments.location_id` → `locations` 削除時はNULLに設定
- `phases.location_id` → `locations` 削除時はNULLに設定

#### **RESTRICT**
- `equipments.subcategory_id` → `equipment_subcategories` 機材が存在する場合は削除不可
- `equipment_subcategories.category_id` → `equipment_categories` 中分類が存在する場合は削除不可

---

## ⚡ パフォーマンス最適化

### インデックス戦略

#### **期間重複チェック最適化**
```sql
-- phases テーブル
INDEX idx_date_range (start_date, end_date)

-- phase_equipment テーブル
INDEX idx_equipment_phase (equipment_id, phase_id)

-- 重複チェッククエリ例
SELECT COUNT(*) FROM phase_equipment pe
JOIN phases p1 ON pe.phase_id = p1.id
JOIN phases p2 ON p2.id = ?
WHERE pe.equipment_id = ?
AND p1.start_date < p2.end_date
AND p1.end_date > p2.start_date;
```

#### **在庫計算最適化**
```sql
-- equipment_movements テーブル
INDEX idx_equipment_moved_at (equipment_id, moved_at)

-- inventory_snapshots テーブル
INDEX idx_snapshot_date (snapshot_date)
```

#### **検索最適化**
```sql
-- 機材検索用
INDEX idx_equipment_name_category (name, category_id)

-- 公演検索用
INDEX idx_performance_date_type (start_date, performance_type)
```

### クエリパフォーマンス目標

| 操作 | 目標レスポンス時間 | 対象データ量 |
|------|------------------|-------------|
| 機材検索 | < 100ms | 5,000件 |
| 期間重複チェック | < 200ms | 全フェーズ |
| 在庫状況表示 | < 300ms | 全機材 |
| フェーズ機材一覧 | < 150ms | フェーズあたり |

---

## 🔐 セキュリティ・制約

### データ整合性制約

#### **日付整合性**
```sql
-- フェーズの開始日 ≤ 終了日
ALTER TABLE phases ADD CONSTRAINT chk_phase_dates
CHECK (start_date <= end_date);

-- 公演の開始日 ≤ 終了日
ALTER TABLE performances ADD CONSTRAINT chk_performance_dates
CHECK (start_date <= end_date);
```

#### **数量制約**
```sql
-- 正の数量のみ許可
ALTER TABLE phase_equipment ADD CONSTRAINT chk_positive_quantity
CHECK (quantity > 0);

ALTER TABLE equipment_set_items ADD CONSTRAINT chk_positive_quantity
CHECK (quantity > 0);
```

### データ暗号化
```sql
-- 機密情報の暗号化（Laravel Eloquent Castingで実装）
users.lineworks_token (暗号化)
users.lineworks_refresh_token (暗号化)
```

---

## 📊 サンプルデータ・テストケース

### マスタデータ例

#### **equipment_categories & equipment_subcategories**
```sql
-- 大分類
INSERT INTO equipment_categories (name, description, sort) VALUES
('ミキシングコンソール', '音響ミキサー関連機材', 1),
('スピーカー', 'スピーカーシステム関連機材', 2),
('マイク', 'マイクロフォン関連機材', 3);

-- 中分類
INSERT INTO equipment_subcategories (category_id, name, description, sort) VALUES
(1, 'デジタルミキサー', 'デジタル音響ミキサー', 1),
(1, 'アナログミキサー', 'アナログ音響ミキサー', 2),
(2, 'メインスピーカー', 'メインスピーカーシステム', 1),
(2, 'モニタースピーカー', 'モニタースピーカーシステム', 2);
```

#### **equipment**
```sql
INSERT INTO equipment (subcategory_id, management_type, shin_on_number, name, model) VALUES
(1, 'individual', '32ch-1', 'デジタルミキサー32ch', 'CL5'),
(1, 'individual', '32ch-2', 'デジタルミキサー32ch', 'CL5'),
(3, 'quantity', NULL, 'XLRケーブル5m', 'XLR-5M');
```

### よく使用されるクエリ例

#### **利用可能機材検索**
```sql
-- 特定期間で利用可能な機材を検索
SELECT e.* FROM equipments e
WHERE e.id NOT IN (
    SELECT DISTINCT pe.equipment_id
    FROM phase_equipment pe
    JOIN phases p ON pe.phase_id = p.id
    WHERE p.start_date < '2025-02-01'
    AND p.end_date > '2025-01-15'
    AND pe.status IN ('reserved', 'checked_out')
);
```

#### **基準日時点在庫**
```sql
-- 2025-01-15時点での倉庫別在庫
SELECT
    l.name AS warehouse_name,
    e.name AS equipment_name,
    COALESCE(ins.quantity, 0) AS quantity
FROM equipments e
LEFT JOIN inventory_snapshots ins ON e.id = ins.equipment_id
    AND ins.snapshot_date = '2025-01-15'
LEFT JOIN locations l ON ins.location_id = l.id
WHERE l.type = '倉庫' OR l.id IS NULL
ORDER BY l.name, e.name;
```

---

**作成日**: 2025年1月
**バージョン**: 1.0
**対象システム**: shin-on 機材管理システム
