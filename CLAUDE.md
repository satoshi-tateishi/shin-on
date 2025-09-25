# CLAUDE.md - shin-on プロジェクト

## 📢 プロジェクト概要
新規Laravel 12 + MySQL 8.0 プロジェクト「shin-on」のドキュメント構造です。
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
- [claude/development/testing.md](claude/development/testing.md) - PHPUnit、テスト実行

#### 🏗️ アーキテクチャ
- [claude/architecture/application-overview.md](claude/architecture/application-overview.md) - プロジェクト概要、実装済み機能
- [claude/architecture/laravel-best-practices.md](claude/architecture/laravel-best-practices.md) - Laravel開発標準
- [claude/architecture/laravel-12.md](claude/architecture/laravel-12.md) - Laravel 12固有機能

## 🚀 利用方法
1. **[claude/README.md](claude/README.md)** から開始
2. 目的に応じて各カテゴリのファイルを参照
3. クイックナビゲーションで効率的にアクセス

## ✅ 環境構築完了項目
- ✅ Laravel 12 + MySQL 8.0（Docker）
- ✅ Tailwind CSS v4.1.13（CSS-first設定、パフォーマンス改善済み）
- ✅ Laravel-Boost MCP
- ✅ ポート設定（HTTP:8081, Vite:5174, MySQL:3307）

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
