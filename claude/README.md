# 🚀 shin-on プロジェクト ドキュメント

## 📋 プロジェクト概要
Laravel 12 + MySQL 8.0 + Tailwind CSS v4 + Laravel-Boost MCPを使用した新規Webアプリケーションプロジェクトです。

## 🔧 技術スタック
- **バックエンド**: Laravel 12
- **データベース**: MySQL 8.0
- **フロントエンド**: Tailwind CSS v4
- **AI開発支援**: Laravel-Boost MCP
- **開発環境**: Docker (Laravel Sail)

## 🌐 アクセス情報
- **アプリケーション**: http://localhost:8081
- **Vite開発サーバー**: http://localhost:5174
- **MySQL**: localhost:3307

## 📚 ドキュメント ナビゲーション

### 🛠️ 環境設定
- **[setup/environment.md](setup/environment.md)** - 開発環境、Tailwind CSS、Laravel-Boost設定

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

### 📋 API仕様書
- **[../docs/Dropbox_API_Specification.md](../docs/Dropbox_API_Specification.md)** - Dropbox OAuth 2.0 & バックアップAPI仕様
- **[../docs/LINE_WORKS_SSO_Specification.md](../docs/LINE_WORKS_SSO_Specification.md)** - LINE WORKS SSO認証仕様

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

### 基本開発コマンド
```bash
# マイグレーション実行
./vendor/bin/sail artisan migrate

# コードフォーマット
./vendor/bin/sail php ./vendor/bin/pint

# テスト実行
./vendor/bin/sail artisan test
```

## 📖 開発ガイドライン
1. **コード品質**: Laravel Pintによる自動フォーマット適用
2. **テスト**: 機能追加時は必ずテストを作成
3. **コミット**: 意味のある単位でコミット
4. **ドキュメント**: 新機能追加時はドキュメント更新

## 🔍 トラブルシューティング
- ポート競合時は`.env`ファイルのポート設定を変更
- Docker関連問題は`./vendor/bin/sail down && ./vendor/bin/sail up -d`で再起動
- Laravel-Boost MCPが動作しない場合は`composer require laravel/boost --dev`で再インストール

---

**💡 詳細情報は各カテゴリのドキュメントを参照してください。**