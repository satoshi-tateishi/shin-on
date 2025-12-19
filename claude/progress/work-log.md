# 作業ログ

## 2025年12月

### 12/19 - CI/CD・テストカバレッジ向上

#### ハードコード倉庫ID解消
- `is_main_warehouse` フラグを locations テーブルに追加
- 8箇所のハードコード [92,93,94] を `Location::getMainWarehouseIds()` に置換
- 管理画面（場所マスタ）にチェックボックス追加
- LocationFactory に `mainWarehouse()` ステート追加

#### Feature テスト追加（38件）
| ファイル | テスト数 | 内容 |
|----------|---------|------|
| ActivityLogTest | 11 | 監査ログ一覧・フィルタ・認可 |
| LocationTest | 15 | 場所マスタ CRUD・is_main_warehouse |
| PhaseEquipmentCheckoutTest | 12 | 出庫・返却・一括処理・主要倉庫判定 |

#### 新規ファクトリ
- ActivityLogFactory
- PhaseEquipmentFactory

**テスト総数: 133 → 171（+38）**

---

## 次回作業候補

### テスト Phase 3（複雑なビジネスロジック）
1. **ScheduleTest** - スケジュール表示・日付計算・ステータス判定
2. **InventoryTest** - 在庫一覧・可用数計算・PDF エクスポート

### テスト Phase 4（マスタデータ）
3. EquipmentCategoryTest / SubcategoryTest
4. UserTest / PositionTest

### テスト Phase 5（管理・認証）
5. Admin/BackupController - バックアップ機能
6. Auth/LineWorksController - SSO連携

---

## 2025年9月

### 9/28 - 機材継承機能・ドキュメント更新
- フェーズ間機材継承機能実装完了
  - PhaseEquipmentInheritanceController（3 API）
  - 継承元限定操作・自動返却・期間重複除外
  - モーダルUI・JavaScript実装
- LINE WORKS SSO仕様書更新（lineworks_id詳細追加）
- プロジェクトドキュメント最新化

### 9/24 - Phase 6.5-6.6 基本倉庫・返却機能
- `equipments.now_location_id`カラム追加
- 倉庫間移動画面に返却ボタン追加
- returnEquipmentToBase API実装
- **全基本機能実装完了**

### 9/23 - Phase 6.4 計画策定
- 倉庫間移動・返却先選択機能の実装計画詳細化
- 実装進捗ドキュメント更新

### 9/22 - Phase 6.1-6.3 在庫管理システム
- `inventory_snapshots`テーブル・InventorySnapshotモデル
- InventoryController（7 APIエンドポイント）
- 在庫管理ダッシュボードUI（Alpine.js）
- スケジュール表エラー修正・パフォーマンス最適化
- 代替機自動割り振り機能実装
- Phase 6詳細仕様書作成

### 9/20 - リファクタリング
- Laravel Pintコード品質修正
- パフォーマンス向上インデックス追加
- N+1クエリ最適化

### 9/19 - Phase 3-4 機材使用・修理管理
- Phase 3: 機材使用管理（バックエンド・フロントエンド完了）
  - PhaseEquipmentController、ビューテンプレート、JS機能
- Phase 4: 修理管理（バックエンド完了）
  - RepairRecordController、修理ワークフロー
  - 日時フィールドをdate型に変更

### 9/18 - Phase 2 公演・フェーズ管理完了
- performances/phases/performance_staffテーブル
- Performance/Phase/PerformanceStaffモデル
- PerformanceController/PhaseController
- 期間重複チェック機能

---

## 2025年1月

### 1/18 - 要件定義・ドキュメント整理
- 旧ドキュメントを`claude/zOLD/`に移動
- 要件定義書v2.0・データベース設計書v2.0作成
- 進捗管理システム構築

### 1月以前 - Phase 1 基盤システム
- Laravel 12 + Docker環境構築
- Tailwind CSS v4 + LINE WORKS SSO統合
- マスタ管理システム完全実装（8種類）
- CSV機能・ドラッグ&ドロップソート・検索機能

---

**最終更新**: 2025年12月19日
