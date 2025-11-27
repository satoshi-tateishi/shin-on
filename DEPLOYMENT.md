# shin-on 本番環境デプロイメントガイド

## アーキテクチャ

```
Internet --> Apache (SSL, 443) --> localhost:8084 --> Docker (shin-on)
                               --> localhost:8083 --> Docker (shin-on_wiki)
```

| 項目 | 値 |
|------|-----|
| ドメイン | db.shin-on1981.com |
| Dockerポート | 8084:8080 |
| SSL | Apache + Let's Encrypt |
| DB | MySQL 8.0 |
| PHP | serversideup/php:8.4-fpm-apache |

---

## 共通コマンド

以下、`DC` は `docker compose -f docker-compose.production.yml` の略。

```bash
# コンテナ操作
DC up -d / down / restart / ps / logs -f

# Artisan
DC exec app php artisan [command]

# キャッシュクリア
DC exec app php artisan config:clear && \
DC exec app php artisan cache:clear && \
DC exec app php artisan route:clear && \
DC exec app php artisan view:clear

# キャッシュ最適化
DC exec app php artisan config:cache && \
DC exec app php artisan route:cache && \
DC exec app php artisan view:cache
```

---

## 初回デプロイ手順

### 1. 前提条件

- Ubuntu + Apache2 + Docker
- Apache モジュール: `sudo a2enmod proxy proxy_http headers ssl rewrite`
- DNS: `db.shin-on1981.com` → サーバーIP
- ポート 80, 443 開放

### 2. ファイル転送（Mac → サーバー）

```bash
rsync -avz -e 'ssh -p 56834' \
  --exclude='vendor' --exclude='node_modules' --exclude='.env' \
  --exclude='storage/app/lineworks/private_key.pem' \
  /Users/satoshi/Laravel/shin-on/ satoshi@SERVER:/var/www/shin-on/
```

### 3. 環境設定（サーバー）

```bash
cd /var/www/shin-on
cp .env.production.example .env
nano .env  # DB_PASSWORD, LINE WORKS, Dropbox設定
```

### 4. 秘密鍵配置（Mac）

```bash
rsync -avz -e 'ssh -p 56834' \
  storage/app/lineworks/private_key.pem satoshi@SERVER:/var/www/shin-on/storage/app/lineworks/
# サーバーで: chmod 600 storage/app/lineworks/private_key.pem
```

### 5. パーミッション・起動（サーバー）

```bash
sudo chown -R www-data:www-data /var/www/shin-on
sudo chmod -R 775 /var/www/shin-on
sudo chmod 644 docker-compose.production.yml

DC up -d
DC exec app composer install --optimize-autoloader --no-dev
DC exec app php artisan key:generate --force
DC exec app php artisan migrate --force
DC exec app php artisan storage:link
# キャッシュ最適化（共通コマンド参照）
```

### 6. SSL証明書（サーバー）

```bash
# 一時Apache設定作成 → certbot --apache -d db.shin-on1981.com
# 設定ファイルリネーム: shin-on-temp* → shin-on*
```

---

## 自動デプロイ（GitHub Actions）

`release` ブランチへのpushでトリガー。

### 処理内容

1. git fetch & reset --hard
2. composer install --no-dev
3. `rm -f public/hot` + npm run build
4. DC restart app
5. migrate --force
6. キャッシュクリア・最適化

### GitHub Secrets

| Secret | 値 |
|--------|-----|
| DEPLOY_HOST | shin-on.mydns.jp |
| DEPLOY_USER | satoshi |
| DEPLOY_KEY | SSH秘密鍵 |
| DEPLOY_PATH | /var/www/shin-on |

---

## OAuth設定

| サービス | Redirect URI |
|----------|--------------|
| LINE WORKS | https://db.shin-on1981.com/auth/lineworks/callback |
| Dropbox | https://db.shin-on1981.com/auth/dropbox/callback |

---

## トラブルシューティング

### CSSが適用されない（localhost:5174参照）

原因: `public/hot` ファイル存在 → Viteが開発モード判定

```bash
rm -f public/hot && npm run build
DC exec app php artisan view:clear
```

### Mixed Content エラー

原因: HTTPS未認識

1. `.env`: `TRUSTED_PROXIES=*`
2. `AppServiceProvider::boot()`: `URL::forceScheme('https')`
3. キャッシュクリア + restart

### 502 Bad Gateway

```bash
DC ps  # コンテナ確認
curl -I http://localhost:8084
DC logs app --tail=50
```

### パーミッションエラー（rsync時）

```bash
# 転送前: sudo chown -R $USER:$USER /var/www/shin-on
# 転送後: sudo chown -R www-data:www-data /var/www/shin-on
```

### MySQL再作成（.env変更後）

```bash
DC down -v  # データ削除注意
DC up -d
```

---

## セキュリティ

- `.env` パーミッション 600
- `APP_DEBUG=false`
- 強固なDB_PASSWORD
- SSH公開鍵認証
- 定期バックアップ

---

**最終更新: 2025年11月28日**
