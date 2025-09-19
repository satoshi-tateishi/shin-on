# CLAUDE CODE 作業再開ルール

## 🎯 セッション開始時の必須確認手順

### 1. 📊 進捗状況の把握（必須）
```markdown
1. `/claude/progress/implementation-status.md` を読み取り
   - 現在のフェーズと完了状況を確認
   - 次のタスクの内容を把握
   - 技術的課題・注意事項を確認

2. `/claude/progress/work-log.md` で最新の作業内容確認
   - 前回の作業内容・成果物を確認
   - 引き継ぎ事項・注意点を把握
   - 未完了タスクがあれば特定
```

### 2. 🔍 技術的文脈の回復
```markdown
1. 最新の要件定義書確認
   - `/docs/Equipment_Management_System_Requirements_v2.md`

2. データベース設計の現在状況確認
   - `/docs/Equipment_Management_Database_Design_v2.md`

3. 実装済み機能の動作確認（必要に応じて）
   - 開発環境起動: `./vendor/bin/sail up -d`
   - アプリケーション動作確認: `http://localhost:8081`
```

### 3. 🎯 次タスクの特定・計画
```markdown
1. implementation-status.md の「次のタスク」セクション確認
2. 優先度の高い未完了タスクを特定
3. 依存関係を考慮した作業順序の決定
4. TodoWriteツールで作業計画をタスク化
```

---

## 📝 作業完了時の必須記録ルール

### 1. 🔄 TodoWriteツール更新
- 完了したタスクを即座に「completed」にマーク
- 新たに発見したタスクがあれば追加
- 次回の作業開始タスクを明確化

### 2. 📊 進捗管理ファイル更新
#### `progress/implementation-status.md` 更新内容
```markdown
- フェーズ進捗率の更新
- 完了機能の詳細記録
- 次のタスクの明記
- 技術的課題の追加・解決状況更新
```

#### `progress/work-log.md` 記録内容
```markdown
### YYYY年MM月DD日 - [作業概要]

#### ✅ 完了作業
1. **作業項目1**
   - 具体的な作業内容
   - 成果物・ファイルパス
   - 所要時間

#### 📊 成果物
- ファイル1: パス・概要
- ファイル2: パス・概要

#### 🎯 次回作業への引き継ぎ事項
1. 優先事項1
2. 技術的注意点
3. 未完了事項
```

---

## 🚨 重要な文脈維持ルール

### 🔧 技術的制約の常時認識
```markdown
必須記憶事項:
1. MySQL 8.0制約: EXCLUDE制約未対応
2. 大容量データ: 5,000機材×100公演対応必須
3. 同時利用: 30ユーザー対応
4. 期間重複チェック: Laravelでの複雑実装必要
```

### 📋 実装済み機能の把握
```markdown
Phase 1完了済み:
- 全マスタテーブル（8種類）実装完了
- 認証・権限システム完成
- CSV連携・検索・ソート機能完成
- 機材セット管理・使用可能性チェック完成
```

### 🎯 現在フェーズの明確化
```markdown
現在: Phase 1完了 → Phase 2準備段階
次の実装: 公演・フェーズ管理システム

Phase 2の主要タスク:
1. performances/phases/performance_staff テーブル作成
2. Eloquentモデル・リレーション実装
3. コントローラー・ビュー実装
4. 期間重複チェックロジック実装
```

---

## 🔄 文脈回復のクイックリファレンス

### ⚡ 30秒で把握すべき情報
```markdown
1. プロジェクト: 機材管理システム（Laravel 12 + MySQL 8.0）
2. 現在状況: Phase 1（マスタ管理）完了済み
3. 次の実装: Phase 2（公演・フェーズ管理）
4. 重要制約: MySQL 8.0のEXCLUDE制約制限
5. 最新ドキュメント: Requirements_v2.md / Database_Design_v2.md
```

### 📱 即座にアクセスすべきファイル
```markdown
必須確認:
- claude/progress/implementation-status.md （進捗状況）
- claude/progress/work-log.md （作業履歴）

設計確認:
- docs/Equipment_Management_System_Requirements_v2.md
- docs/Equipment_Management_Database_Design_v2.md

実装確認:
- app/Models/ （実装済みモデル確認）
- routes/web.php （実装済みルート確認）
```

---

## 🎭 プロジェクト全体像の把握

### 🏗️ システム構成（7つの主要機能）
```markdown
✅ 1. マスタ管理システム（完了）
⏳ 2. 公演管理システム（次回実装）
⏳ 3. フェーズ管理システム（次回実装）
⏳ 4. 機材使用・貸出返却管理
⏳ 5. 修理・メンテナンス管理
⏳ 6. 機材スケジュール表
⏳ 7. 在庫状況表示（基準日指定）
```

### 📊 データベース構成（17テーブル）
```markdown
✅ マスタテーブル（8つ）: 全て実装完了
⏳ 業務テーブル（4つ）: 未実装（Phase 2で実装）
✅ セット管理（2つ）: 実装完了
⏳ 履歴・ログ（3つ）: 未実装（後続Phaseで実装）
```

---

## ⚠️ エラー回避・品質保持ルール

### 🔍 作業開始前チェック
```markdown
1. 開発環境の動作確認
   - Docker起動: ./vendor/bin/sail up -d
   - アプリアクセス: http://localhost:8081
   - データベース接続確認

2. 既存機能の動作確認（必要時）
   - 認証システムの動作
   - マスタ管理機能の動作
   - CSV機能の動作
```

### 📝 コーディング時の必須ルール
```markdown
1. Laravel Pintによるコードフォーマット適用
2. 既存のコーディングスタイル・パターンの踏襲
3. 外部キー制約・インデックスの適切な設定
4. バリデーションルールの徹底実装
5. エラーハンドリングの適切な実装
```

### 🧪 実装完了時の確認事項
```markdown
1. 新機能の動作テスト
2. 既存機能への影響確認
3. データベースマイグレーションの動作確認
4. Laravel Pintでのコードフォーマット適用
5. 進捗管理ファイルの更新
```

---

## 🚀 効率的作業パターン

### 📋 Phase毎の推奨作業フロー
```markdown
1. Phase開始時
   - 詳細設計の再確認
   - マイグレーション・モデル作成
   - 基本CRUD実装

2. Phase進行中
   - 段階的な機能実装
   - 継続的なテスト・確認
   - 進捗記録の更新

3. Phase完了時
   - 全体テスト実施
   - ドキュメント更新
   - 次Phase準備
```

### 🎯 継続的品質向上
```markdown
1. 定期的なコードレビュー（自己レビュー）
2. パフォーマンス考慮（N+1問題等）
3. セキュリティ考慮（SQLインジェクション等）
4. ユーザビリティ考慮（UI/UX改善）
```

---

## 📞 緊急時・問題発生時の対処

### 🆘 開発環境問題
```bash
# Docker環境リセット
./vendor/bin/sail down
./vendor/bin/sail up -d

# キャッシュクリア
./vendor/bin/sail artisan cache:clear
./vendor/bin/sail artisan config:clear
./vendor/bin/sail artisan route:clear

# 依存関係再インストール
./vendor/bin/sail composer install
./vendor/bin/sail npm install
```

### 🔧 データベース問題
```bash
# マイグレーションリセット（開発環境のみ）
./vendor/bin/sail artisan migrate:fresh --seed

# マイグレーション状況確認
./vendor/bin/sail artisan migrate:status
```

### 📋 コードベース問題
```markdown
1. 最新のcommitに戻る
2. Laravel Pintでコード整形
3. 実装済み機能の動作確認
4. 段階的な問題の特定・修正
```

---

## 🏁 作業セッション終了時チェックリスト

### ✅ 必須完了事項
- [ ] TodoWriteツールでの進捗更新
- [ ] implementation-status.md の更新
- [ ] work-log.md への作業記録
- [ ] 次回作業タスクの明記
- [ ] 未完了事項の整理

### 💾 任意実行事項
- [ ] コードのコミット（意味のある単位で）
- [ ] 開発環境の停止（必要に応じて）
- [ ] ドキュメントの追加更新（必要に応じて）

---

**作成日**: 2025年1月18日
**対象**: CLAUDE CODE セッション管理
**目的**: 効率的な作業継続・品質保持