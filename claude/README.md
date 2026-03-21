# shin-on 機材管理システム ドキュメント

## 概要

演劇・ミュージカル公演における機材の総合管理システム。

| 項目 | 技術 |
|------|------|
| バックエンド | Laravel 12 |
| データベース | MySQL 8.0 |
| フロントエンド | Tailwind CSS v4 |
| 開発環境 | Docker (Laravel Sail) |

| 環境 | URL |
|------|-----|
| **本番** | https://db.shin-on1981.com |
| 開発 | http://localhost:8081 |
| Vite | http://localhost:5174 |

---

## ドキュメント

### 環境設定
- [setup/environment.md](setup/environment.md) - 開発環境
- [setup/tailwind-css-v4.md](setup/tailwind-css-v4.md) - Tailwind CSS v4

### 開発ツール
- [development/commands.md](development/commands.md) - コマンド集
- [development/php-standards.md](development/php-standards.md) - PHP標準
- [development/javascript-standards.md](development/javascript-standards.md) - JavaScript標準
- [development/dompdf-japanese-fonts.md](development/dompdf-japanese-fonts.md) - Dompdf日本語フォント
- [development/playwright-mcp.md](development/playwright-mcp.md) - Playwright MCP
- [development/testing.md](development/testing.md) - テスト

### アーキテクチャ
- [architecture/application-overview.md](architecture/application-overview.md) - 設計概要
- [architecture/laravel-best-practices.md](architecture/laravel-best-practices.md) - Laravel標準
- [architecture/laravel-12.md](architecture/laravel-12.md) - Laravel 12

### 運用
- [operations/backup-system.md](operations/backup-system.md) - バックアップ
- [operations/log-maintenance.md](operations/log-maintenance.md) - ログ管理
- [docs/Deployment_Guide.md](docs/Deployment_Guide.md) - デプロイメントガイド（アーキテクチャ・初回デプロイ・トラブルシューティング）
- [docs/GitHub_Actions_Deploy_Specification.md](docs/GitHub_Actions_Deploy_Specification.md) - GitHub Actions 自動デプロイ仕様

### 仕様書
- [docs/Equipment_Management_System_Requirements_v2.md](docs/Equipment_Management_System_Requirements_v2.md) - 要件定義
- [docs/Equipment_Management_Database_Design_v2.md](docs/Equipment_Management_Database_Design_v2.md) - DB設計
- [docs/Phase_Equipment_Inheritance_Specification.md](docs/Phase_Equipment_Inheritance_Specification.md) - 機材継承
- [docs/Role_Permission_Specification.md](docs/Role_Permission_Specification.md) - 権限設計

### 外部連携
- [docs/LINE_WORKS_SSO_Specification.md](docs/LINE_WORKS_SSO_Specification.md) - LINE WORKS SSO
- [docs/LINE_WORKS_Bot_API_Guide.md](docs/LINE_WORKS_Bot_API_Guide.md) - LINE WORKS Bot
- [docs/Dropbox_API_Specification.md](docs/Dropbox_API_Specification.md) - Dropbox API
- [docs/Activity_Log_Specification.md](docs/Activity_Log_Specification.md) - アクティビティログ

### 進捗管理
- [progress/work-log.md](progress/work-log.md) - 作業ログ

---

## クイックスタート

```bash
# 起動（推奨）
./vendor/bin/sail up -d && ./vendor/bin/sail npm run start

# 基本コマンド
./vendor/bin/sail artisan migrate      # マイグレーション
./vendor/bin/sail php ./vendor/bin/pint # フォーマット
./vendor/bin/sail artisan test          # テスト
```

---

## 実装済み機能

| 機能 | 内容 |
|------|------|
| マスタ管理 | ユーザー、機材、カテゴリ、場所等 |
| 公演管理 | CRUD、ステータス、スタッフ管理 |
| フェーズ管理 | 順序管理、期間重複チェック |
| 機材使用管理 | 貸出・返却ワークフロー |
| 修理管理 | ワークフロー、統計レポート |
| スケジュール | Excel風UI、8種類ステータス |
| 在庫管理 | 基準日指定、倉庫間移動 |

---

**詳細は各ドキュメントを参照**
