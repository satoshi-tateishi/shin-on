# 🚀 shin-on 本番環境デプロイメントガイド

## 📋 前提条件

### 1. ドメインとDNS設定
- [ ] ドメイン名を取得済み（例: shin-on.example.com）
- [ ] DNSのAレコードを自宅サーバーのグローバルIPに設定済み
- [ ] DNS設定が反映されていることを確認（`nslookup YOUR_DOMAIN`）

### 2. ネットワーク設定
- [ ] ルーターでポート80と443を自宅サーバーにポートフォワーディング設定済み
- [ ] ファイアウォールでポート80と443を開放済み

### 3. サーバー環境
- [ ] Docker と Docker Compose がインストール済み
- [ ] Git がインストール済み
- [ ] 十分なディスク容量（最低10GB以上推奨）

---

## 🔧 初回デプロイ手順

### Step 1: リポジトリのクローン

```bash
cd /path/to/your/projects
git clone https://github.com/YOUR_GITHUB_USERNAME/shin-on.git
cd shin-on
```

### Step 2: 本番環境変数の設定

```bash
# .env.production.example をコピー
cp .env.production.example .env

# .envファイルを編集
nano .env
```

**必須項目を編集：**
```bash
APP_KEY=                                    # php artisan key:generate で生成
APP_URL=https://YOUR_DOMAIN_HERE           # 実際のドメイン名に変更
SESSION_DOMAIN=YOUR_DOMAIN_HERE            # 実際のドメイン名に変更

# データベース認証情報
DB_USERNAME=your_secure_username
DB_PASSWORD=your_secure_password

# LINE WORKS設定（開発環境の値をコピー）
LINEWORKS_CLIENT_ID=...
LINEWORKS_CLIENT_SECRET=...
LINEWORKS_BOT_ID=...
LINEWORKS_BOT_SECRET=...
LINEWORKS_DB_CLIENT_ID=...
LINEWORKS_DB_CLIENT_SECRET=...
LINEWORKS_SERVICE_ACCOUNT=...

# Dropbox設定（開発環境の値をコピー）
DROPBOX_CLIENT_ID=...
DROPBOX_CLIENT_SECRET=...

# メール設定（必要に応じて）
MAIL_HOST=...
MAIL_USERNAME=...
MAIL_PASSWORD=...
```

### Step 3: Nginx設定ファイルの編集

```bash
# YOUR_DOMAIN_HERE を実際のドメイン名に置換
nano nginx/default.conf
```

以下の3箇所を置換：
```nginx
server_name YOUR_DOMAIN_HERE;  # 2箇所
ssl_certificate /etc/letsencrypt/live/YOUR_DOMAIN_HERE/fullchain.pem;
ssl_certificate_key /etc/letsencrypt/live/YOUR_DOMAIN_HERE/privkey.pem;
ssl_trusted_certificate /etc/letsencrypt/live/YOUR_DOMAIN_HERE/chain.pem;
```

### Step 4: Let's Encrypt 証明書の初回取得

**4-1. 一時的にHTTP用Nginx設定を作成**

```bash
# Nginx設定をHTTPのみに一時変更
cat > nginx/default.conf << 'EOF'
server {
    listen 80;
    listen [::]:80;
    server_name YOUR_DOMAIN_HERE;

    location /.well-known/acme-challenge/ {
        root /var/www/certbot;
    }

    location / {
        proxy_pass http://laravel.test:80;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
EOF
```

**4-2. Dockerコンテナを起動**

```bash
docker compose -f docker-compose.prod.yml up -d
```

**4-3. Let's Encrypt証明書を取得**

```bash
docker compose -f docker-compose.prod.yml run --rm certbot certonly \
  --webroot \
  --webroot-path=/var/www/certbot \
  -d YOUR_DOMAIN_HERE \
  --email YOUR_EMAIL@example.com \
  --agree-tos \
  --no-eff-email
```

成功すると以下のメッセージが表示されます：
```
Successfully received certificate.
Certificate is saved at: /etc/letsencrypt/live/YOUR_DOMAIN_HERE/fullchain.pem
Key is saved at: /etc/letsencrypt/live/YOUR_DOMAIN_HERE/privkey.pem
```

**4-4. 正式なNginx設定（HTTPS対応）に戻す**

```bash
# Step 3で編集した元の設定に戻す
git restore nginx/default.conf
nano nginx/default.conf  # YOUR_DOMAIN_HERE を置換

# Nginxを再起動
docker compose -f docker-compose.prod.yml restart nginx
```

### Step 5: Laravel初期設定

```bash
# Composerの依存関係をインストール
docker compose -f docker-compose.prod.yml exec laravel.test composer install --optimize-autoloader --no-dev

# アプリケーションキーを生成（.envに未設定の場合）
docker compose -f docker-compose.prod.yml exec laravel.test php artisan key:generate

# データベースマイグレーション
docker compose -f docker-compose.prod.yml exec laravel.test php artisan migrate --force

# キャッシュ最適化
docker compose -f docker-compose.prod.yml exec laravel.test php artisan config:cache
docker compose -f docker-compose.prod.yml exec laravel.test php artisan route:cache
docker compose -f docker-compose.prod.yml exec laravel.test php artisan view:cache

# ストレージリンク作成
docker compose -f docker-compose.prod.yml exec laravel.test php artisan storage:link
```

### Step 6: LINE WORKS秘密鍵のアップロード

```bash
# 開発環境からコピー、または直接配置
mkdir -p storage/app/lineworks
# private_key.pem をこのディレクトリにコピー
chmod 600 storage/app/lineworks/private_key.pem
```

### Step 7: 動作確認

```bash
# HTTPSでアクセス
curl -I https://YOUR_DOMAIN_HERE

# ログ確認
docker compose -f docker-compose.prod.yml logs -f
```

ブラウザで `https://YOUR_DOMAIN_HERE` にアクセスして動作を確認。

---

## 🔄 アップデート手順

```bash
# 最新コードを取得
git pull origin main

# Composerの依存関係を更新
docker compose -f docker-compose.prod.yml exec laravel.test composer install --optimize-autoloader --no-dev

# データベースマイグレーション
docker compose -f docker-compose.prod.yml exec laravel.test php artisan migrate --force

# キャッシュクリア＆再生成
docker compose -f docker-compose.prod.yml exec laravel.test php artisan config:clear
docker compose -f docker-compose.prod.yml exec laravel.test php artisan config:cache
docker compose -f docker-compose.prod.yml exec laravel.test php artisan route:cache
docker compose -f docker-compose.prod.yml exec laravel.test php artisan view:cache

# コンテナ再起動
docker compose -f docker-compose.prod.yml restart
```

---

## 🔐 SSL証明書の自動更新

Certbotコンテナが12時間ごとに証明書の更新をチェックします（証明書の有効期限が30日以内の場合に自動更新）。

**手動で更新する場合：**
```bash
docker compose -f docker-compose.prod.yml exec certbot certbot renew
docker compose -f docker-compose.prod.yml restart nginx
```

**証明書の有効期限確認：**
```bash
docker compose -f docker-compose.prod.yml exec certbot certbot certificates
```

---

## 📊 運用コマンド

### コンテナの起動・停止

```bash
# 起動
docker compose -f docker-compose.prod.yml up -d

# 停止
docker compose -f docker-compose.prod.yml down

# 再起動
docker compose -f docker-compose.prod.yml restart

# 状態確認
docker compose -f docker-compose.prod.yml ps
```

### ログ確認

```bash
# 全コンテナのログ
docker compose -f docker-compose.prod.yml logs -f

# 特定コンテナのログ
docker compose -f docker-compose.prod.yml logs -f laravel.test
docker compose -f docker-compose.prod.yml logs -f nginx
docker compose -f docker-compose.prod.yml logs -f certbot
```

### データベース操作

```bash
# MySQLに接続
docker compose -f docker-compose.prod.yml exec mysql mysql -u root -p

# データベースバックアップ
docker compose -f docker-compose.prod.yml exec mysql mysqldump -u root -p laravel > backup_$(date +%Y%m%d).sql
```

---

## 🔧 トラブルシューティング

### 証明書取得に失敗する場合

**原因チェックリスト：**
1. DNS設定が正しいか確認
   ```bash
   nslookup YOUR_DOMAIN_HERE
   # 自宅サーバーのグローバルIPが返ってくるか確認
   ```

2. ポート80が外部からアクセス可能か確認
   ```bash
   # 外部ネットワークから実行
   curl -I http://YOUR_DOMAIN_HERE
   ```

3. Nginxが正常に起動しているか確認
   ```bash
   docker compose -f docker-compose.prod.yml logs nginx
   ```

4. Let's Encryptのレート制限に達していないか確認
   - 同一ドメインで1週間に5回まで
   - テスト時は `--staging` オプションを使用

### Nginxが起動しない場合

```bash
# 設定ファイルの構文チェック
docker compose -f docker-compose.prod.yml exec nginx nginx -t

# 詳細ログを確認
docker compose -f docker-compose.prod.yml logs nginx
```

### Laravelアプリケーションが動作しない場合

```bash
# .envファイルが正しく読み込まれているか確認
docker compose -f docker-compose.prod.yml exec laravel.test php artisan config:show

# キャッシュをクリア
docker compose -f docker-compose.prod.yml exec laravel.test php artisan cache:clear
docker compose -f docker-compose.prod.yml exec laravel.test php artisan config:clear
docker compose -f docker-compose.prod.yml exec laravel.test php artisan route:clear
docker compose -f docker-compose.prod.yml exec laravel.test php artisan view:clear

# ストレージ権限を確認
docker compose -f docker-compose.prod.yml exec laravel.test ls -la storage/
```

### HTTPSでアクセスできない場合

```bash
# SSL証明書が正しく配置されているか確認
docker compose -f docker-compose.prod.yml exec nginx ls -la /etc/letsencrypt/live/YOUR_DOMAIN_HERE/

# Nginx設定でドメイン名が正しく設定されているか確認
docker compose -f docker-compose.prod.yml exec nginx cat /etc/nginx/conf.d/default.conf | grep server_name
```

---

## 🔒 セキュリティチェックリスト

- [ ] `.env` ファイルのパーミッションを600に設定
- [ ] データベースパスワードを強固なものに変更
- [ ] SSH接続に公開鍵認証を使用
- [ ] 不要なポートをファイアウォールで閉じる
- [ ] 定期的なバックアップを設定（Dropbox連携済み）
- [ ] アプリケーションとOSのセキュリティアップデートを定期実行
- [ ] ログの定期的な監視

---

## 📚 参考リンク

- [Let's Encrypt Documentation](https://letsencrypt.org/docs/)
- [Certbot User Guide](https://eff-certbot.readthedocs.io/)
- [Nginx Configuration](https://nginx.org/en/docs/)
- [Laravel Deployment](https://laravel.com/docs/12.x/deployment)
- [Docker Compose Documentation](https://docs.docker.com/compose/)

---

**デプロイに関する質問は、GitHubのIssuesでお問い合わせください。**
