# CLAUDE.md - shin-on_db プロジェクト

## 📢 プロジェクト概要
Laravel 12 + MySQL 8.0 プロジェクト「shin-on_db」のドキュメント構造です。
日本語で応対してください。

## 🔗 ドキュメント構造

### メイン エントリーポイント
**👉 [claude/README.md](claude/README.md) - ナビゲーション付きメインドキュメント**

### 📂 カテゴリ別ドキュメント

#### 🛠️ 環境設定
- [claude/setup/environment.md](claude/setup/environment.md) - Laravel Boost、Tailwind CSS、基本設定

#### 💻 開発ツール・標準
- [claude/development/commands.md](claude/development/commands.md) - Artisan、Boost、開発コマンド
- [claude/development/php-standards.md](claude/development/php-standards.md) - PHP標準、Laravel Pint
- [claude/development/javascript-standards.md](claude/development/javascript-standards.md) - JavaScript標準、Alpine.js、タイムゾーン対応
- [claude/development/dompdf-japanese-fonts.md](claude/development/dompdf-japanese-fonts.md) - Dompdf 日本語フォント対応 📄
- [claude/development/playwright-mcp.md](claude/development/playwright-mcp.md) - Playwright MCP ブラウザ自動化設定 🌐
- [claude/development/testing.md](claude/development/testing.md) - PHPUnit、テスト実行

#### 🏗️ アーキテクチャ
- [claude/architecture/application-overview.md](claude/architecture/application-overview.md) - プロジェクト概要、実装済み機能
- [claude/architecture/laravel-best-practices.md](claude/architecture/laravel-best-practices.md) - Laravel開発標準
- [claude/architecture/laravel-12.md](claude/architecture/laravel-12.md) - Laravel 12固有機能

#### 🚀 デプロイ・運用
- [claude/docs/Deployment_Guide.md](claude/docs/Deployment_Guide.md) - デプロイメントガイド（アーキテクチャ・初回デプロイ・トラブルシューティング）
- [claude/docs/GitHub_Actions_Deploy_Specification.md](claude/docs/GitHub_Actions_Deploy_Specification.md) - GitHub Actions 自動デプロイ仕様 🚀

#### 📋 API仕様書・外部連携
- [claude/docs/LINE_WORKS_Bot_API_Guide.md](claude/docs/LINE_WORKS_Bot_API_Guide.md) - LINE WORKS Bot API実装ガイド 🤖
- [claude/docs/LINE_WORKS_SSO_Specification.md](claude/docs/LINE_WORKS_SSO_Specification.md) - LINE WORKS SSO認証仕様 🔐
- [claude/docs/Dropbox_API_Specification.md](claude/docs/Dropbox_API_Specification.md) - Dropbox OAuth 2.0 & バックアップAPI仕様 ☁️
- [claude/docs/Activity_Log_Specification.md](claude/docs/Activity_Log_Specification.md) - アクティビティログ機能仕様 📊

#### 🔐 権限・セキュリティ
- [claude/docs/Role_Permission_Specification.md](claude/docs/Role_Permission_Specification.md) - Role権限設計仕様書（機能追加時の権限設定ガイド）

## 🚀 利用方法
1. **[claude/README.md](claude/README.md)** から開始
2. 目的に応じて各カテゴリのファイルを参照
3. クイックナビゲーションで効率的にアクセス

## ✅ プロジェクト完成状況（2026年3月21日更新）
- ✅ Laravel 12 + MySQL 8.0（Docker） - 基盤完成
- ✅ Tailwind CSS v4.1.13（CSS-first設定、パフォーマンス改善済み）
- ✅ Laravel-Boost MCP - AI開発支援環境構築済み
- ✅ LINE WORKS SSO - 完全統合・認証システム運用中
- ✅ LINE WORKS Bot API - フェーズPDF送信機能実装完了（2025年11月11日追加）
- ✅ 機材管理システム - 全7機能実装完了（Phase 1-6完成）
- ✅ フェーズ間機材継承機能 - 完全実装・運用開始（2025年9月28日追加）
- ✅ Dropboxバックアップシステム - OAuth 2.0対応、自動アップロード・ログクリーンアップ機能（2025年9月29日追加）
- ✅ アクティビティログ - 操作履歴記録、ユーザー別フィルタリング、自動クリーンアップ（2025年12月1日追加）
- ✅ Role権限管理 - 4段階ロール（viewer/general/editor/admin）、権限設定ページ（2025年12月5日追加）
- ✅ shin-on_portal 統合 - gateway-apache による中央リバースプロキシ・SSL管理（2026年3月21日移行）
- ✅ ポート設定（HTTP:8081, Vite:5174, MySQL:3307）

## 🌐 サーバー情報
| 環境 | URL |
|------|-----|
| **本番サーバー** | https://db.shin-on1981.com |
| 開発アプリ | http://localhost:8081 |
| Vite開発サーバー | http://localhost:5174 |
| MySQL | localhost:3307 |

## 🚀 開発環境起動コマンド
```bash
# Laravel + Vite を同時起動
./vendor/bin/sail up -d && ./vendor/bin/sail npm run start
```

## 🔍 Viteサーバー起動チェック
```bash
# Viteサーバーの動作確認
curl -I http://localhost:5174

# ポート使用状況確認
lsof -i :5174

# Viteプロセス確認
./vendor/bin/sail ps | grep vite
```

---
**💡 詳細なドキュメントは claude/ ディレクトリをご利用ください。**
