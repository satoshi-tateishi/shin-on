# shin-on dB（機材管理システム）

音響機材の管理システムです。公演・フェーズごとの機材割当、在庫管理、修理記録などを一元管理します。

## システム概要

| 項目 | 値 |
|------|-----|
| フレームワーク | Laravel 12 |
| PHP | 8.4 |
| データベース | MySQL 8.0 |
| フロントエンド | Tailwind CSS v4 + Alpine.js |
| 認証 | LINE WORKS SSO + 2段階認証 |
| 本番URL | https://db.shin-on1981.com |

## 主要機能

- **公演管理**: 公演情報の登録・編集、フェーズ（仕込み・本番・バラシ等）管理
- **機材管理**: カテゴリ・サブカテゴリによる機材マスタ管理
- **機材割当**: フェーズごとの機材チェックアウト/チェックイン
- **在庫管理**: 倉庫別の機材在庫確認
- **修理記録**: 機材の修理履歴管理
- **PDF出力**: フェーズ機材リスト・在庫リストのPDF出力
- **LINE WORKS連携**: PDF送信、2段階認証
- **Dropboxバックアップ**: データベースの自動バックアップ

## 開発環境

### 必要条件

- Docker Desktop
- Node.js 20+
- Composer 2.x

### セットアップ

```bash
# リポジトリをクローン
git clone https://github.com/satoshi-tateishi/shin-on_db.git
cd shin-on_db

# 環境変数ファイルを作成
cp .env.example .env

# Composerパッケージをインストール（Sail起動前に必要）
composer install

# コンテナを起動
./vendor/bin/sail up -d

# アプリケーションキーを生成
./vendor/bin/sail artisan key:generate

# データベースをマイグレート
./vendor/bin/sail artisan migrate

# NPMパッケージをインストール＆ビルド
./vendor/bin/sail npm install
./vendor/bin/sail npm run build
```

### 開発サーバー起動

```bash
./vendor/bin/sail up -d && ./vendor/bin/sail npm run start
```

開発環境: http://localhost:8081

## 本番環境

### アーキテクチャ

```
Internet
  ↓ HTTPS (443)
shin-on_portal / gateway-apache（Apacheコンテナ）
  └─ db.shin-on1981.com → http://shin-on_app:8080/（shin-on-internal ネットワーク経由）
```

SSL証明書・リバースプロキシは `shin-on_portal` が一元管理。

### デプロイ方法

**自動デプロイ（推奨）:**

`release`ブランチにpushすると、GitHub Actionsが自動的にデプロイします。

```bash
git checkout release
git merge main
git push origin release
```

**手動デプロイ:**

詳細は [claude/docs/Deployment_Guide.md](claude/docs/Deployment_Guide.md) を参照してください。

## ディレクトリ構成

```
shin-on_db/
├── app/
│   ├── Console/Commands/     # Artisanコマンド（バックアップ等）
│   ├── Http/Controllers/     # コントローラー
│   ├── Models/               # Eloquentモデル
│   └── Services/             # ビジネスロジック
├── claude/                   # AI開発支援ドキュメント
├── config/                   # 設定ファイル
├── database/migrations/      # マイグレーション
├── public/                   # 公開ディレクトリ
├── resources/views/          # Bladeテンプレート
├── routes/web.php            # ルート定義
├── storage/fonts/            # PDF用日本語フォント
├── .github/workflows/        # GitHub Actions
├── docker-compose.yml        # 開発環境Docker設定
└── docker-compose.production.yml  # 本番環境Docker設定
```

## 環境変数

主要な環境変数（`.env`）:

| 変数 | 説明 |
|------|------|
| `APP_URL` | アプリケーションURL |
| `DB_*` | データベース接続情報 |
| `LINEWORKS_*` | LINE WORKS API設定 |
| `DROPBOX_*` | Dropbox API設定 |
| `TRUSTED_PROXIES` | リバースプロキシ設定（本番: `*`） |

詳細は `.env.example` を参照。

## 運用コマンド

### バックアップ

```bash
# Dropboxへバックアップ
./vendor/bin/sail artisan backup:dropbox

# Dropboxからリストア
./vendor/bin/sail artisan restore:dropbox
```

### キャッシュ管理

```bash
# キャッシュクリア
./vendor/bin/sail artisan cache:clear
./vendor/bin/sail artisan config:clear
./vendor/bin/sail artisan route:clear
./vendor/bin/sail artisan view:clear

# キャッシュ最適化（本番用）
./vendor/bin/sail artisan config:cache
./vendor/bin/sail artisan route:cache
./vendor/bin/sail artisan view:cache
```

### ログ管理

```bash
# 古いログを削除
./vendor/bin/sail artisan logs:clear
```

## ドキュメント

- [claude/docs/Deployment_Guide.md](claude/docs/Deployment_Guide.md) - 本番環境デプロイガイド
- [claude/README.md](claude/README.md) - 開発ドキュメント（AI支援用）
- [claude/docs/](claude/docs/) - API仕様書・設計書

## ライセンス

プライベートリポジトリ - 無断使用禁止

---

**最終更新: 2026年3月21日**
