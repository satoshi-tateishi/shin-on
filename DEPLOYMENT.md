# shin-on 本番環境デプロイメントガイド

## 概要

shin-on（機材管理システム）を本番サーバーにデプロイする手順です。

### アーキテクチャ

```
Internet --> Apache (SSL終端, port 443)
                |
                +-- db.shin-on1981.com --> localhost:8084 --> Docker (shin-on)
                |
                +-- wiki.shin-on1981.com --> localhost:8083 --> Docker (shin-on_wiki)
```

| 項目 | 値 |
|------|-----|
| ドメイン | db.shin-on1981.com |
| Dockerポート | 8084 (ホスト) → 8080 (コンテナ) |
| SSL | Apache + Let's Encrypt |
| データベース | MySQL 8.0（個別コンテナ） |
| PHPイメージ | serversideup/php:8.4-fpm-apache |

---

## 前提条件

### サーバー環境
- [ ] Ubuntu Server（Apache2インストール済み）
- [ ] Docker と Docker Compose がインストール済み
- [ ] 十分なディスク容量（最低10GB以上推奨）

### Apache モジュール
```bash
sudo a2enmod proxy proxy_http headers ssl rewrite
sudo systemctl restart apache2
```

### DNS設定（MyDNS.jp）
- [ ] `db` サブドメインのAレコードをサーバーIPに設定
- [ ] DNS反映確認: `nslookup db.shin-on1981.com`

### ネットワーク設定
- [ ] ポート80, 443がサーバーに転送されている
- [ ] ファイアウォールでポート80, 443を開放

---

## デプロイ手順

### Step 1: サーバー側でディレクトリ作成

**サーバーで実行：**
```bash
sudo mkdir -p /var/www/shin-on
sudo chown $USER:$USER /var/www/shin-on
```

### Step 2: 開発マシンからファイル転送

**開発マシン（Mac）で実行：**

```bash
# SSHポートがデフォルト(22)の場合
rsync -avz --exclude='vendor' --exclude='node_modules' --exclude='.env' --exclude='storage/app/lineworks/private_key.pem' /Users/satoshi/Laravel/shin-on/ satoshi@サーバーIP:/var/www/shin-on/

# SSHポートを変更している場合（例: 56834）
rsync -avz -e 'ssh -p 56834' --exclude='vendor' --exclude='node_modules' --exclude='.env' --exclude='storage/app/lineworks/private_key.pem' /Users/satoshi/Laravel/shin-on/ satoshi@サーバーIP:/var/www/shin-on/
```

### Step 3: 環境変数の設定

**サーバーで実行：**
```bash
cd /var/www/shin-on
cp .env.production.example .env
nano .env
```

**必須項目を編集：**
```bash
# データベース認証情報（必ず変更すること）
DB_DATABASE=shin_on
DB_USERNAME=shin_on_user
DB_PASSWORD=安全なパスワード

# リバースプロキシ設定（HTTPS強制に必要）
TRUSTED_PROXIES=*

# LINE WORKS設定（開発環境の.envからコピー）
LINEWORKS_CLIENT_ID=...
LINEWORKS_CLIENT_SECRET=...
LINEWORKS_REDIRECT_URI=https://db.shin-on1981.com/auth/lineworks/callback
LINEWORKS_BOT_ID=...
LINEWORKS_BOT_SECRET=...
LINEWORKS_DB_CLIENT_ID=...
LINEWORKS_DB_CLIENT_SECRET=...
LINEWORKS_SERVICE_ACCOUNT=...

# Dropbox設定（開発環境の.envからコピー）
DROPBOX_CLIENT_ID=...
DROPBOX_CLIENT_SECRET=...
DROPBOX_REDIRECT_URI=https://db.shin-on1981.com/auth/dropbox/callback
```

> **重要**: `.env` の設定はDockerコンテナ起動前に完了させてください。MySQLコンテナは初回起動時に `.env` の値でデータベースとユーザーを作成します。

### Step 4: LINE WORKS秘密鍵の配置

**開発マシン（Mac）で実行：**
```bash
rsync -avz -e 'ssh -p 56834' /Users/satoshi/Laravel/shin-on/storage/app/lineworks/private_key.pem satoshi@サーバーIP:/var/www/shin-on/storage/app/lineworks/
```

**サーバーで実行：**
```bash
chmod 600 /var/www/shin-on/storage/app/lineworks/private_key.pem
```

### Step 5: パーミッション設定

**サーバーで実行：**
```bash
# vendorディレクトリを作成（Composer用）
mkdir -p /var/www/shin-on/vendor

# 所有者をwww-dataに変更
sudo chown -R www-data:www-data /var/www/shin-on
sudo chmod -R 775 /var/www/shin-on

# docker-compose.ymlは読み取り可能に（docker composeコマンド実行に必要）
sudo chmod 644 /var/www/shin-on/docker-compose.production.yml
```

### Step 6: Dockerコンテナの起動

**サーバーで実行：**
```bash
cd /var/www/shin-on

# コンテナを起動
docker compose -f docker-compose.production.yml up -d

# 起動確認（MySQLがHealthyになるまで待つ）
docker compose -f docker-compose.production.yml ps

# 接続テスト
curl -I http://localhost:8084
```

### Step 7: Laravel初期設定

**サーバーで実行：**
```bash
# Composerインストール
docker compose -f docker-compose.production.yml exec app composer install --optimize-autoloader --no-dev

# アプリケーションキー生成（--forceで確認プロンプトをスキップ）
docker compose -f docker-compose.production.yml exec app php artisan key:generate --force

# データベースマイグレーション
docker compose -f docker-compose.production.yml exec app php artisan migrate --force

# ストレージリンク作成（既に存在する場合はエラーになるが無視してOK）
docker compose -f docker-compose.production.yml exec app php artisan storage:link

# キャッシュ最適化
docker compose -f docker-compose.production.yml exec app php artisan config:cache
docker compose -f docker-compose.production.yml exec app php artisan route:cache
docker compose -f docker-compose.production.yml exec app php artisan view:cache
```

### Step 8: SSL証明書の取得とApache設定

**サーバーで実行：**
```bash
# 一時的なHTTP用Apache設定を作成
sudo nano /etc/apache2/sites-available/shin-on-temp.conf
```

以下の内容を貼り付け：
```apache
<VirtualHost *:80>
    ServerName db.shin-on1981.com
    ProxyPreserveHost On
    ProxyPass / http://localhost:8084/
    ProxyPassReverse / http://localhost:8084/
</VirtualHost>
```

```bash
# 一時設定を有効化
sudo a2ensite shin-on-temp
sudo systemctl reload apache2

# SSL証明書を取得（CertbotがSSL設定を自動作成）
sudo certbot --apache -d db.shin-on1981.com
```

### Step 9: Apache設定の整理

Certbotが作成した設定ファイルを整理します。

```bash
# 一時設定を無効化
sudo a2dissite shin-on-temp
sudo a2dissite shin-on-temp-le-ssl

# ファイル名を本番用にリネーム
sudo mv /etc/apache2/sites-available/shin-on-temp-le-ssl.conf /etc/apache2/sites-available/shin-on-le-ssl.conf
sudo mv /etc/apache2/sites-available/shin-on-temp.conf /etc/apache2/sites-available/shin-on.conf

# 本番設定を有効化
sudo a2ensite shin-on
sudo a2ensite shin-on-le-ssl

# 設定テスト
sudo apache2ctl configtest

# Apache再読み込み
sudo systemctl reload apache2
```

### Step 10: 動作確認

```bash
# HTTPSアクセス確認
curl -I https://db.shin-on1981.com
```

ブラウザで https://db.shin-on1981.com にアクセスしてログインページが表示されることを確認。

---

## OAuth設定の更新

### LINE WORKS Developer Console
リダイレクトURIを追加：
```
https://db.shin-on1981.com/auth/lineworks/callback
```

### Dropbox App Console
リダイレクトURIを追加：
```
https://db.shin-on1981.com/auth/dropbox/callback
```

---

## 自動デプロイ（GitHub Actions）

`release`ブランチにpushすると、GitHub Actionsが自動的に本番サーバーへデプロイします。

### ワークフローの動作

1. SSHでサーバーに接続
2. 最新コードを`git fetch`＆`git reset --hard`
3. Composerの依存関係を更新
4. NPMビルド（Vite）
5. Dockerコンテナ再起動
6. マイグレーション実行
7. キャッシュ最適化

### GitHub Secretsの設定

| Secret名 | 説明 |
|----------|------|
| `DEPLOY_HOST` | DDNSホスト名（shin-on.mydns.jp） |
| `DEPLOY_USER` | SSHユーザー名（satoshi） |
| `DEPLOY_KEY` | SSH秘密鍵（shin-on_wikiと共通） |
| `DEPLOY_PATH` | デプロイ先パス（/var/www/shin-on） |

### デプロイキーの設定

サーバーには2つのキーが必要：

1. **SSH接続用**: `~/.ssh/id_ed25519_deploy`（authorized_keysに登録済み）
2. **GitHub接続用**: `~/.ssh/id_ed25519_deploy_shinon`（GitHubリポジトリのDeploy keysに登録）

### 手動でワークフローを再実行

GitHub → Actions → 該当ワークフロー → Re-run all jobs

---

## 運用コマンド

### コンテナ操作

```bash
cd /var/www/shin-on

# 起動
docker compose -f docker-compose.production.yml up -d

# 停止
docker compose -f docker-compose.production.yml down

# 再起動
docker compose -f docker-compose.production.yml restart

# 状態確認
docker compose -f docker-compose.production.yml ps

# ログ確認
docker compose -f docker-compose.production.yml logs -f
```

### アップデート

**1. サーバーで実行（転送前にパーミッション変更）：**
```bash
sudo chown -R $USER:$USER /var/www/shin-on
```

**2. 開発マシン（Mac）で実行：**
```bash
rsync -avz -e 'ssh -p 56834' --exclude='vendor' --exclude='node_modules' --exclude='.env' --exclude='storage/app/lineworks/private_key.pem' /Users/satoshi/Laravel/shin-on/ satoshi@サーバーIP:/var/www/shin-on/
```

**3. サーバーで実行（転送後の設定）：**
```bash
cd /var/www/shin-on

# パーミッションをwww-dataに戻す
sudo chown -R www-data:www-data /var/www/shin-on
sudo chmod -R 775 /var/www/shin-on
sudo chmod 644 /var/www/shin-on/docker-compose.production.yml

# 依存関係を更新
docker compose -f docker-compose.production.yml exec app composer install --optimize-autoloader --no-dev

# マイグレーション
docker compose -f docker-compose.production.yml exec app php artisan migrate --force

# キャッシュ再生成
docker compose -f docker-compose.production.yml exec app php artisan config:clear
docker compose -f docker-compose.production.yml exec app php artisan config:cache
docker compose -f docker-compose.production.yml exec app php artisan route:cache
docker compose -f docker-compose.production.yml exec app php artisan view:cache

# 再起動
docker compose -f docker-compose.production.yml restart
```

### データベースバックアップ

```bash
# 手動バックアップ
docker compose -f docker-compose.production.yml exec mysql mysqldump -u shin_on_user -p shin_on > backup_$(date +%Y%m%d_%H%M%S).sql

# Dropboxへのバックアップ（アプリ内機能）
docker compose -f docker-compose.production.yml exec app php artisan backup:dropbox
```

---

## SSL証明書の更新

Let's Encryptの証明書はCertbotが自動更新します。

```bash
# 自動更新の確認
sudo systemctl status certbot.timer

# 手動更新（テスト）
sudo certbot renew --dry-run

# 証明書の有効期限確認
sudo certbot certificates
```

---

## トラブルシューティング

### MySQLコンテナの再作成が必要な場合

`.env` の設定を変更した後にMySQLを再作成する場合：

```bash
docker compose -f docker-compose.production.yml down -v
docker compose -f docker-compose.production.yml up -d
```

> **注意**: `-v` オプションはデータベースのデータも削除します。

### 502 Bad Gateway

```bash
# Dockerコンテナが起動しているか確認
docker compose -f docker-compose.production.yml ps

# ポート8084で応答があるか確認
curl -I http://localhost:8084

# コンテナログを確認
docker compose -f docker-compose.production.yml logs app --tail=50
```

### データベース接続エラー

```bash
# MySQLコンテナのログを確認
docker compose -f docker-compose.production.yml logs mysql

# .envの設定を確認
grep -E '^DB_' /var/www/shin-on/.env

# データベース接続テスト
docker compose -f docker-compose.production.yml exec app php artisan db:show
```

### パーミッションエラー（rsync転送時）

www-data所有のファイルはrsyncで上書きできないため、転送前にパーミッションを変更：

```bash
# 転送前
sudo chown -R $USER:$USER /var/www/shin-on

# 転送後
sudo chown -R www-data:www-data /var/www/shin-on
sudo chmod -R 775 /var/www/shin-on
sudo chmod 644 /var/www/shin-on/docker-compose.production.yml
```

### docker compose コマンドが permission denied

```bash
sudo chmod 644 /var/www/shin-on/docker-compose.production.yml
```

### キャッシュ関連の問題

```bash
docker compose -f docker-compose.production.yml exec app php artisan cache:clear
docker compose -f docker-compose.production.yml exec app php artisan config:clear
docker compose -f docker-compose.production.yml exec app php artisan route:clear
docker compose -f docker-compose.production.yml exec app php artisan view:cache
```

### Mixed Content エラー（HTTPS/HTTP混在）

ログイン時に「認証処理中...」で止まり、ブラウザコンソールに以下のエラーが表示される場合：

```
Mixed Content: The page at 'https://...' was loaded over HTTPS,
but requested an insecure resource 'http://...'
```

**原因**: Laravelがリバースプロキシ経由であることを認識していない

**解決方法**:

1. `.env`に`TRUSTED_PROXIES=*`を設定（Step 3参照）

2. `app/Providers/AppServiceProvider.php`の`boot()`メソッドに以下を追加：

```php
public function boot(): void
{
    // 本番環境でHTTPSを強制
    if (config('app.env') === 'production') {
        \Illuminate\Support\Facades\URL::forceScheme('https');
    }

    // 既存のコード...
}
```

3. キャッシュクリアとコンテナ再起動：

```bash
docker compose -f docker-compose.production.yml exec app php artisan config:clear
docker compose -f docker-compose.production.yml restart app
```

---

## セキュリティチェックリスト

- [ ] `.env` ファイルのパーミッションを600に設定
- [ ] データベースパスワードを強固なものに変更
- [ ] `APP_DEBUG=false` を確認
- [ ] SSH接続に公開鍵認証を使用
- [ ] 不要なポートをファイアウォールで閉じる
- [ ] 定期的なバックアップを設定

---

**最終更新: 2025年11月28日**
