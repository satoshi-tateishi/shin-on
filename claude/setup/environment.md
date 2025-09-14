# 🛠️ 環境設定

## 📋 環境情報

### システム要件
- **PHP**: 8.4 (Laravel Sail Docker環境)
- **Node.js**: 22.x
- **MySQL**: 8.0
- **Docker**: Docker Desktop必須

### ポート設定
| サービス | ポート | 説明 |
|---------|-------|------|
| アプリケーション | 8081 | メインのWebサーバー |
| Vite開発サーバー | 5174 | フロントエンド開発サーバー |
| MySQL | 3307 | データベース接続（外部アクセス用） |

## 🚀 Laravel Sailセットアップ

### 基本コマンド
```bash
# Docker環境起動
./vendor/bin/sail up -d

# Docker環境停止
./vendor/bin/sail down

# コンテナ状況確認
./vendor/bin/sail ps

# Laravel アプリケーション接続
./vendor/bin/sail shell
```

### 環境変数設定 (.env)
```env
# アプリケーション基本設定
APP_NAME=shin-on
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8081
APP_PORT=8081

# データベース設定
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=sail
DB_PASSWORD=password

# Vite設定
VITE_PORT=5174
```

## 🎨 Tailwind CSS v4 設定

### インストール済みパッケージ
```json
{
  "devDependencies": {
    "tailwindcss": "next",
    "@tailwindcss/postcss": "next",
    "autoprefixer": "latest",
    "postcss": "latest"
  }
}
```

### 設定ファイル

#### tailwind.config.js
```javascript
/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
  ],
  theme: {
    extend: {},
  },
  plugins: [],
}
```

#### postcss.config.js
```javascript
export default {
  plugins: {
    '@tailwindcss/postcss': {},
    autoprefixer: {},
  },
}
```

#### resources/css/app.css
```css
@import 'tailwindcss';

@source '../../vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php';
@source '../../storage/framework/views/*.php';
@source '../**/*.blade.php';
@source '../**/*.js';

@theme {
    --font-sans: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji',
        'Segoe UI Symbol', 'Noto Color Emoji';
}
```

## 🤖 Laravel-Boost MCP設定

### インストール済みパッケージ
```bash
composer require laravel/boost --dev
```

### 利用可能なコマンド
```bash
# Boost MCP サーバー起動
./vendor/bin/sail artisan boost:mcp

# Boost インストーラー（インタラクティブモード）
./vendor/bin/sail artisan boost:install

# 利用可能なBoostコマンド確認
./vendor/bin/sail artisan list | grep boost
```

### Boost MCP機能
- **15+ AI開発支援ツール**
- **Laravel エコシステム ドキュメント検索**
- **データベースクエリ実行**
- **Tinkerコード実行**
- **テスト生成支援**
- **パッケージ情報取得**

## 📊 データベース設定

### 接続確認
```bash
# マイグレーション状況確認
./vendor/bin/sail artisan migrate:status

# 新しいマイグレーション実行
./vendor/bin/sail artisan migrate

# データベース接続（MySQL CLI）
./vendor/bin/sail mysql
```

### 初期マイグレーション
```
✓ 0001_01_01_000000_create_users_table
✓ 0001_01_01_000001_create_cache_table
✓ 0001_01_01_000002_create_jobs_table
```

## 🔧 開発ツール

### Node.js / NPM
```bash
# パッケージインストール
./vendor/bin/sail npm install

# 開発サーバー起動
./vendor/bin/sail npm run dev

# プロダクションビルド
./vendor/bin/sail npm run build
```

### Composer
```bash
# パッケージインストール
./vendor/bin/sail composer install

# パッケージ追加
./vendor/bin/sail composer require [package-name]

# 開発専用パッケージ追加
./vendor/bin/sail composer require --dev [package-name]
```

## 🚨 トラブルシューティング

### よくある問題と解決方法

#### ポート競合エラー
```bash
# エラー: Bind for 0.0.0.0:80 failed: port is already allocated
# 解決: .env ファイルのポート番号を変更
```

#### MySQL接続エラー
```bash
# エラー: Connection refused
# 解決: .env の DB_PORT を 3306 に設定
```

#### MYSQL_EXTRA_OPTIONS警告
```bash
# 警告: The "MYSQL_EXTRA_OPTIONS" variable is not set
# 解決: .env に MYSQL_EXTRA_OPTIONS= を追加（空の値でOK）
```

### デバッグコマンド
```bash
# コンテナログ確認
./vendor/bin/sail logs

# 特定サービスのログ
./vendor/bin/sail logs mysql
./vendor/bin/sail logs laravel.test

# コンテナ内でのデバッグ
./vendor/bin/sail shell
```

---
**[← README.md に戻る](../README.md)**