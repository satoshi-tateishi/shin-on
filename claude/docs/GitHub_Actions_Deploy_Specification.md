# GitHub Actions デプロイ仕様書

## 概要

`release`ブランチへのpushで本番サーバーへ自動デプロイ。

### デプロイフロー

```
git push origin release → GitHub Actions → SSH接続 → 本番サーバー更新
```

---

## ワークフロー

### トリガー

```yaml
on:
  push:
    branches:
      - release
```

### 実行条件

```yaml
if: github.repository == 'satoshi-tateishi/shin-on_db'
```

---

## CI パイプライン（デプロイ前に全て成功が必要）

| ジョブ | 内容 |
|--------|------|
| `lint` | Laravel Pint によるコードスタイルチェック |
| `analyse` | PHPStan による静的解析 |
| `test` | PHPUnit（MySQL 8.0 サービスコンテナ使用） |

---

## デプロイ手順

| # | 処理 | コマンド |
|---|------|----------|
| 1 | コード取得 | `git fetch && git reset --hard origin/release` |
| 2 | Dockerビルド | `docker compose build app` |
| 3 | コンテナ再起動 | `docker compose up -d app` |
| 4 | Composer | `composer install --no-dev --optimize-autoloader`（コンテナ内） |
| 5 | NPM | `rm -f public/hot && npm ci && npm run build`（ホスト側） |
| 6 | マイグレーション | `php artisan migrate --force` |
| 7 | キャッシュクリア | `config:clear`, `cache:clear`, `route:clear`, `view:clear` |
| 8 | キャッシュ最適化 | `config:cache`, `route:cache`, `view:cache` |
| 9 | OPcacheリセット | `docker compose restart app` |

---

## 必要なSecrets

| Secret | 説明 | 例 |
|--------|------|-----|
| `DEPLOY_HOST` | サーバーホスト名/IP | `example.com` |
| `DEPLOY_USER` | SSHユーザー名 | `deploy` |
| `DEPLOY_KEY` | SSH秘密鍵 | `-----BEGIN OPENSSH...` |
| `DEPLOY_PATH` | デプロイ先パス | `/var/www/shin-on` |

### Secrets設定場所

```
GitHub → Repository → Settings → Secrets and variables → Actions
```

---

## 使用方法

### 通常デプロイ

```bash
# mainブランチで開発
git checkout main
git add .
git commit -m "feat: 新機能追加"
git push origin main

# releaseブランチにマージしてデプロイ
git checkout release
git merge main
git push origin release  # ← 自動デプロイ開始
```

### 緊急デプロイ（手動）

```bash
# 本番サーバーで直接実行
cd /var/www/shin-on
GIT_SSH_COMMAND='ssh -i ~/.ssh/id_ed25519_deploy_shinon -o StrictHostKeyChecking=no' git fetch origin
git reset --hard origin/release
docker compose -f docker-compose.production.yml restart app
```

---

## デプロイ確認

### GitHub Actions

```
GitHub → Repository → Actions → Deploy to Home Server
```

### 本番サーバー

```bash
# ログ確認
docker compose -f docker-compose.production.yml logs -f app

# バージョン確認
git log -1 --oneline
```

---

## トラブルシューティング

| エラー | 原因 | 対処 |
|--------|------|------|
| SSH接続失敗 | Secrets設定不正 | DEPLOY_KEY, DEPLOY_HOST確認 |
| Permission denied | 権限問題 | `sudo chown`でパーミッション修正 |
| migrate失敗 | DB接続/スキーマ問題 | ログ確認、手動migrate |
| npm build失敗 | Node.js/依存関係問題 | `npm ci`で再インストール |

---

## 関連ファイル

| 種別 | ファイル |
|------|----------|
| ワークフロー | `.github/workflows/deploy.yml` |
| 本番Docker | `docker-compose.production.yml` |
| 本番Dockerfile | `Dockerfile` |

---

## セキュリティ

| 対策 | 説明 |
|------|------|
| リポジトリ制限 | `satoshi-tateishi/shin-on_db`のみ実行 |
| SSH鍵認証 | パスワード認証なし |
| Secrets暗号化 | GitHub側で暗号化保存 |
| 非公開ポート | SSH: 56834 |

---

**最終更新**: 2026年3月21日
