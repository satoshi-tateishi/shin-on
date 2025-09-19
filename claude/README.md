# 🚀 shin-on 機材管理システム ドキュメント

## 📋 プロジェクト概要
演劇・ミュージカル公演における機材の総合管理システム。Laravel 12 + MySQL 8.0 + Tailwind CSS v4 + Laravel-Boost MCPを使用し、機材の入出庫管理、スケジュール管理、修理・メンテナンス管理、在庫管理を統合した業務システムです。

## 🔧 技術スタック
- **バックエンド**: Laravel 12
- **データベース**: MySQL 8.0
- **フロントエンド**: Tailwind CSS v4.1.13 ✨
- **AI開発支援**: Laravel-Boost MCP
- **開発環境**: Docker (Laravel Sail)

### ✨ Tailwind CSS v4 の特徴
- **設定ファイル不要**: CSS-first 設定方式
- **パフォーマンス向上**: 最大5倍高速化
- **OKLCH色空間**: より正確な色表現

## 🌐 アクセス情報
- **アプリケーション**: http://localhost:8081
- **Vite開発サーバー**: http://localhost:5174
- **MySQL**: localhost:3307

## 📚 ドキュメント ナビゲーション

### 🛠️ 環境設定
- **[setup/environment.md](setup/environment.md)** - 開発環境、基本設定
- **[setup/tailwind-css-v4.md](setup/tailwind-css-v4.md)** - Tailwind CSS v4 移行ガイド ✨

### 💻 開発ツール・標準
- **[development/commands.md](development/commands.md)** - よく使うコマンド集
- **[development/php-standards.md](development/php-standards.md)** - PHP・Laravel コーディング標準
- **[development/testing.md](development/testing.md)** - テストの書き方・実行方法

### 🏗️ アーキテクチャ
- **[architecture/application-overview.md](architecture/application-overview.md)** - アプリケーション設計概要
- **[architecture/laravel-best-practices.md](architecture/laravel-best-practices.md)** - Laravel開発ベストプラクティス
- **[architecture/laravel-12.md](architecture/laravel-12.md)** - Laravel 12の新機能・特徴

### ⚙️ 運用・ドキュメント
- **[operations/documentation.md](operations/documentation.md)** - API仕様書・ドキュメント管理

### 📋 システム仕様書・設計書
- **[../docs/Equipment_Management_System_Requirements_v2.md](../docs/Equipment_Management_System_Requirements_v2.md)** - 機材管理システム要件定義書 v2.0 ✨
- **[../docs/Equipment_Management_Database_Design_v2.md](../docs/Equipment_Management_Database_Design_v2.md)** - データベース設計書 v2.0 ✨
- **[../docs/Dropbox_API_Specification.md](../docs/Dropbox_API_Specification.md)** - Dropbox OAuth 2.0 & バックアップAPI仕様
- **[../docs/LINE_WORKS_SSO_Specification.md](../docs/LINE_WORKS_SSO_Specification.md)** - LINE WORKS SSO認証仕様

### 🗂️ 旧バージョン・アーカイブ
- **[zOLD/Equipment_Management_System_Requirements_v1.md](zOLD/Equipment_Management_System_Requirements_v1.md)** - 旧要件定義書 v1.0
- **[zOLD/Equipment_Management_Database_Design_v1.md](zOLD/Equipment_Management_Database_Design_v1.md)** - 旧データベース設計書 v1.0

### 📊 進捗管理・作業ログ
- **[progress/implementation-status.md](progress/implementation-status.md)** - 実装進捗状況
- **[progress/work-log.md](progress/work-log.md)** - 作業ログ・履歴
- **[progress/resume-rules.md](progress/resume-rules.md)** - CLAUDE CODE作業再開ルール

## 🚀 クイックスタート

### プロジェクト起動
```bash
# Docker環境起動
./vendor/bin/sail up -d

# フロントエンド開発サーバー起動
./vendor/bin/sail npm run dev

# Laravel-Boost MCP起動
./vendor/bin/sail artisan boost:mcp
```

### 🔍 起動状態チェック
```bash
# コンテナ状態確認
./vendor/bin/sail ps

# アプリケーションアクセステスト
curl -I http://localhost:8081

# Viteサーバー動作確認
curl -I http://localhost:5174

# ポート使用状況確認
lsof -i :8081 -i :5174 -i :3307
```

### ⚡ 一括起動（推奨）
```bash
# Laravel + Vite を同時起動
./vendor/bin/sail up -d && ./vendor/bin/sail npm run start
```

### 基本開発コマンド
```bash
# マイグレーション実行
./vendor/bin/sail artisan migrate

# コードフォーマット
./vendor/bin/sail php ./vendor/bin/pint

# テスト実行
./vendor/bin/sail artisan test
```

## 🎭 システム機能概要

### ✅ 実装完了済み機能
1. **マスタ管理システム** ✅
   - ユーザーマスタ（LINE WORKS SSO連携）
   - 機材マスタ（個体管理・数量管理）
   - 機材セットマスタ
   - カテゴリ・サブカテゴリマスタ
   - 使用場所・プロダクション・ポジションマスタ

2. **公演管理システム** ✅
   - 公演CRUD・ステータス管理
   - 主要スタッフ管理・予算管理

3. **フェーズ管理システム** ✅
   - フェーズCRUD・順序管理
   - 期間重複チェック・場所連携

4. **機材使用・貸出返却管理** ✅
   - 機材使用記録・期間重複防止
   - 貸出・返却ワークフロー
   - イベントソーシング対応履歴管理

### 🚧 実装中機能
5. **修理・メンテナンス管理** 🚧 60%完了
   - 修理記録CRUD・ワークフロー（バックエンド完了）
   - 修理コスト・業者管理（バックエンド完了）
   - 修理統計・レポート機能（バックエンド完了）
   - ✅ **完了**: データベース・モデル・コントローラー・ルーティング
   - 🚧 **残り**: ビューテンプレート・ワークフローUI・統計ダッシュボード

### ⏳ 実装予定機能
6. **機材スケジュール表** - Phase 5実装予定
7. **在庫状況表示（基準日指定）** - Phase 6実装予定
8. **統合・最適化** - Phase 7実装予定

## 📖 開発ガイドライン
1. **コード品質**: Laravel Pintによる自動フォーマット適用
2. **テスト**: 機能追加時は必ずテストを作成
3. **コミット**: 意味のある単位でコミット
4. **ドキュメント**: 新機能追加時はドキュメント更新
5. **進捗記録**: 作業完了時は必ず progress/work-log.md に記録

## 🔍 トラブルシューティング
- ポート競合時は`.env`ファイルのポート設定を変更
- Docker関連問題は`./vendor/bin/sail down && ./vendor/bin/sail up -d`で再起動
- Laravel-Boost MCPが動作しない場合は`composer require laravel/boost --dev`で再インストール

---

**💡 詳細情報は各カテゴリのドキュメントを参照してください。**