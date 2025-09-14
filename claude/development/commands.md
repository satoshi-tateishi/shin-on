# 💻 開発コマンド集

## 🐋 Docker / Laravel Sail

### 基本操作
```bash
# Docker環境起動（デタッチモード）
./vendor/bin/sail up -d

# Docker環境停止
./vendor/bin/sail down

# コンテナ状況確認
./vendor/bin/sail ps

# コンテナログ確認
./vendor/bin/sail logs

# Laravelアプリケーションに接続
./vendor/bin/sail shell
```

## 🎯 Laravel Artisan

### アプリケーション管理
```bash
# アプリケーション情報
./vendor/bin/sail artisan --version

# 利用可能なコマンド一覧
./vendor/bin/sail artisan list

# キャッシュクリア
./vendor/bin/sail artisan cache:clear
./vendor/bin/sail artisan config:clear

# ストレージリンク作成
./vendor/bin/sail artisan storage:link
```

### データベース
```bash
# マイグレーション実行
./vendor/bin/sail artisan migrate

# マイグレーション状況確認
./vendor/bin/sail artisan migrate:status

# マイグレーションロールバック
./vendor/bin/sail artisan migrate:rollback

# 新しいマイグレーション作成
./vendor/bin/sail artisan make:migration create_[table_name]_table
```

### 開発支援
```bash
# Tinker（対話型PHP環境）
./vendor/bin/sail artisan tinker

# ルート一覧表示
./vendor/bin/sail artisan route:list

# ログ監視
./vendor/bin/sail artisan log:tail
```

## 🤖 Laravel-Boost MCP

### Boost関連コマンド
```bash
# Boost MCP サーバー起動
./vendor/bin/sail artisan boost:mcp

# Boost インストーラー（要インタラクティブ環境）
./vendor/bin/sail artisan boost:install

# Boost関連コマンド確認
./vendor/bin/sail artisan list | grep boost
```

## 📦 Composer

### パッケージ管理
```bash
# 依存関係インストール
./vendor/bin/sail composer install

# パッケージ追加
./vendor/bin/sail composer require [package-name]

# 開発専用パッケージ追加
./vendor/bin/sail composer require --dev [package-name]

# パッケージ削除
./vendor/bin/sail composer remove [package-name]

# Composer更新
./vendor/bin/sail composer update
```

### 開発ツール
```bash
# Laravel Pint（コードフォーマッター）
./vendor/bin/sail php ./vendor/bin/pint

# PSR-12準拠チェック（dry-run）
./vendor/bin/sail php ./vendor/bin/pint --test
```

## 🎨 Node.js / NPM

### フロントエンド開発
```bash
# 依存関係インストール
./vendor/bin/sail npm install

# 開発サーバー起動
./vendor/bin/sail npm run dev

# プロダクションビルド
./vendor/bin/sail npm run build

# パッケージ追加
./vendor/bin/sail npm install [package-name]

# 開発専用パッケージ追加
./vendor/bin/sail npm install --save-dev [package-name]
```

### Tailwind CSS
```bash
# Tailwind CSS関連パッケージ確認
./vendor/bin/sail npm list | grep tailwind

# PostCSS設定確認
./vendor/bin/sail npm run build -- --verbose
```

## 🧪 テスト

### PHPUnit
```bash
# 全テスト実行
./vendor/bin/sail artisan test

# 特定テストファイル実行
./vendor/bin/sail artisan test tests/Feature/ExampleTest.php

# カバレッジレポート生成
./vendor/bin/sail artisan test --coverage

# 詳細出力
./vendor/bin/sail artisan test --verbose
```

## 🗄️ データベース

### MySQL接続
```bash
# MySQL CLI接続
./vendor/bin/sail mysql

# MySQLクエリ直接実行
./vendor/bin/sail mysql -e "SHOW TABLES;"

# データベースダンプ作成
./vendor/bin/sail exec mysql mysqldump -u sail -p laravel > backup.sql
```

## 🔍 デバッグ・監視

### ログ監視
```bash
# Laravelログ監視
./vendor/bin/sail artisan log:tail

# 特定ログファイル監視
tail -f storage/logs/laravel.log

# Docker コンテナログ
./vendor/bin/sail logs laravel.test
./vendor/bin/sail logs mysql
```

### システム情報
```bash
# PHP情報
./vendor/bin/sail php -v
./vendor/bin/sail php -m

# Composer情報
./vendor/bin/sail composer show

# Node.js情報
./vendor/bin/sail node --version
./vendor/bin/sail npm --version
```

## 🚀 よく使用するワークフロー

### 新機能開発
```bash
# 1. マイグレーション作成・実行
./vendor/bin/sail artisan make:migration create_new_feature_table
./vendor/bin/sail artisan migrate

# 2. モデル作成
./vendor/bin/sail artisan make:model NewFeature

# 3. コントローラー作成
./vendor/bin/sail artisan make:controller NewFeatureController

# 4. テスト作成
./vendor/bin/sail artisan make:test NewFeatureTest

# 5. コードフォーマット
./vendor/bin/sail php ./vendor/bin/pint
```

### デプロイ前チェック
```bash
# 1. 全テスト実行
./vendor/bin/sail artisan test

# 2. コードフォーマット確認
./vendor/bin/sail php ./vendor/bin/pint --test

# 3. プロダクションビルド
./vendor/bin/sail npm run build

# 4. キャッシュ最適化
./vendor/bin/sail artisan config:cache
./vendor/bin/sail artisan route:cache
```

## 🔧 カスタムエイリアス設定

### ~/.bashrc または ~/.zshrc に追加
```bash
# shin-on プロジェクト用エイリアス
alias sail='./vendor/bin/sail'
alias artisan='./vendor/bin/sail artisan'
alias pint='./vendor/bin/sail php ./vendor/bin/pint'
alias npm='./vendor/bin/sail npm'
alias composer='./vendor/bin/sail composer'
```

使用例:
```bash
# エイリアス使用後
sail up -d
artisan migrate
pint
npm run dev
```

---
**[← README.md に戻る](../README.md)**