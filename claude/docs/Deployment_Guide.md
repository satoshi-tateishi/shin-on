# shin-on デプロイメントガイド

## アーキテクチャ

```
Internet
  ↓ HTTPS (443)
shin-on_portal / gateway-apache（Apacheコンテナ）
  └─ db.shin-on1981.com → http://shin-on_app:8080/（shin-on-internal ネットワーク経由）
           ↓
      Docker（shin-on_app コンテナ）
           ↓
      Docker（shin-on_mysql コンテナ）
```

| 項目 | 値 |
|------|-----|
| ドメイン | db.shin-on1981.com |
| Dockerポート | 8084:8080 |
| SSL / リバースプロキシ | shin-on_portal の gateway-apache が担当 |
| DB | MySQL 8.0 |
| PHP | serversideup/php:8.4-fpm-apache |

---

## 共通コマンド

以下、`DC` は `docker compose -f docker-compose.production.yml` の略。

```bash
# コンテナ操作
DC up -d / down / restart / ps / logs -f

# Artisan
DC exec -T app php artisan [command]

# キャッシュクリア
DC exec -T app php artisan config:clear && \
DC exec -T app php artisan cache:clear && \
DC exec -T app php artisan route:clear && \
DC exec -T app php artisan view:clear

# キャッシュ最適化
DC exec -T app php artisan config:cache && \
DC exec -T app php artisan route:cache && \
DC exec -T app php artisan view:cache
```

---

## 初回デプロイ手順

### 1. 前提条件

- Ubuntu + Docker
- `shin-on_portal` が起動済みで `shin-on-internal` ネットワークが存在すること
- DNS: `db.shin-on1981.com` → サーバーIP（shin-on_portal 側で管理）

```bash
# shin-on-internal ネットワークの存在確認
docker network ls | grep shin-on-internal
```

### 2. ファイル転送（Mac → サーバー）

```bash
rsync -avz -e 'ssh -p 56834' \
  --exclude='vendor' --exclude='node_modules' --exclude='.env' \
  --exclude='storage/app/lineworks/private_key.pem' \
  /Users/satoshi/Laravel/shin-on_db/ satoshi@SERVER:/var/www/shin-on/
```

### 3. 環境設定（サーバー）

```bash
cd /var/www/shin-on
cp .env.example .env
nano .env  # APP_KEY, DB_PASSWORD, LINE WORKS, Dropbox等を設定
```

主な設定値：

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://db.shin-on1981.com
APP_PORT=8084
TRUSTED_PROXIES=*
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
DC exec -T app composer install --optimize-autoloader --no-dev
DC exec -T app php artisan key:generate --force
DC exec -T app php artisan migrate --force
DC exec -T app php artisan storage:link
# キャッシュ最適化（共通コマンド参照）
```

### 6. shin-on-internal ネットワークへの接続

shin-on_portal の gateway-apache から `shin-on_app` コンテナに到達できるよう、
`docker-compose.production.yml` の `networks` に `shin-on-internal` 外部ネットワークを追加する。

```yaml
networks:
  shin-on:
    driver: bridge
    name: shin-on_network
  shin-on-internal:
    external: true
    name: shin-on-internal
```

---

## 自動デプロイ（GitHub Actions）

`release` ブランチへの push でトリガー。詳細は
[GitHub_Actions_Deploy_Specification.md](GitHub_Actions_Deploy_Specification.md) 参照。

### CI パイプライン（デプロイ前に全て成功が必要）

| ジョブ | 内容 |
|--------|------|
| `lint` | Laravel Pint によるコードスタイルチェック |
| `analyse` | PHPStan による静的解析 |
| `test` | PHPUnit（MySQL 8.0 サービスコンテナ使用） |

### デプロイ処理内容

1. `git fetch` + `git reset --hard origin/release`
2. `docker compose build app`（Dockerfile変更を反映）
3. `docker compose up -d app`
4. `composer install --no-dev --optimize-autoloader`（コンテナ内）
5. `rm -f public/hot` + `npm ci` + `npm run build`（ホスト側）
6. `php artisan migrate --force`
7. キャッシュクリア → キャッシュ最適化
8. `docker compose restart app`（OPcacheリセット）

### GitHub Secrets

| Secret | 値 |
|--------|-----|
| `DEPLOY_HOST` | shin-on.mydns.jp |
| `DEPLOY_USER` | satoshi |
| `DEPLOY_KEY` | SSH秘密鍵 |
| `DEPLOY_PATH` | /var/www/shin-on |

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
DC exec -T app php artisan view:clear
```

### Mixed Content エラー

原因: HTTPS未認識

1. `.env`: `TRUSTED_PROXIES=*`
2. `AppServiceProvider::boot()`: `URL::forceScheme('https')`
3. キャッシュクリア + restart

### 502 Bad Gateway

```bash
# shin-on_app コンテナ確認
DC ps
curl -I http://localhost:8084
DC logs app --tail=50

# shin-on_portal gateway 確認
cd /path/to/shin-on_portal
docker compose logs apache-gateway --tail=50

# ネットワーク接続確認
docker network inspect shin-on-internal
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
- 強固な `DB_PASSWORD`
- SSH公開鍵認証（ポート 56834）
- 定期バックアップ（Dropbox連携）

---

**最終更新: 2026年3月21日**
