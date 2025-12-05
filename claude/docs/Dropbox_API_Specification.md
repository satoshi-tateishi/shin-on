# Dropbox OAuth 2.0 & バックアップAPI 仕様書

## 概要

Laravel向けDropbox OAuth 2.0バックアップシステム。リフレッシュトークン対応の長期運用可能な自動バックアップ。

### 実装機能
- OAuth 2.0 Authorization Code Flow（リフレッシュトークン対応）
- 自動バックアップ（データベース + ファイル）
- 自動ローカルファイル削除
- バックアップ復元機能
- ログクリーンアップ

---

## 認証フロー

```
管理者 → 認証URL → Dropbox → Authorization Code → Access Token + Refresh Token → 長期運用
         ↑                                                     ↓
         └────────────────── Token期限切れ時 ←───── Refresh Token で更新
```

---

## エンドポイント

### Dropbox API

| 用途 | URL |
|------|-----|
| トークン取得 | `https://api.dropbox.com/oauth2/token` |
| アカウント情報 | `https://api.dropboxapi.com/2/users/get_current_account` |
| ファイルアップロード | `https://content.dropboxapi.com/2/files/upload` |
| ファイルダウンロード | `https://content.dropboxapi.com/2/files/download` |
| フォルダ作成 | `https://api.dropboxapi.com/2/files/create_folder_v2` |

### アプリケーション

| URL | 説明 | 権限 |
|-----|------|------|
| `/auth/dropbox/redirect` | Dropbox認証開始 | admin |
| `/auth/dropbox/callback` | 認証コールバック | - |
| `/admin/backup` | 管理画面 | admin |
| `/admin/backup/run` | バックアップ実行 | admin |
| `/admin/backup/list` | バックアップ一覧 | admin |

---

## CLI コマンド

| コマンド | 説明 |
|----------|------|
| `artisan backup:dropbox` | バックアップ実行 |
| `artisan backup:dropbox --test` | 接続テスト |
| `artisan restore:dropbox --list` | バックアップ一覧 |
| `artisan restore:dropbox {name}` | バックアップ復元 |
| `artisan logs:clear --days=7` | ログクリーンアップ |

---

## データベース

### dropbox_tokens テーブル

| カラム | 型 | 説明 |
|--------|-----|------|
| id | BIGINT | PK |
| service_name | VARCHAR | サービス名（default: backup） |
| access_token | TEXT | アクセストークン |
| access_token_expires_at | TIMESTAMP | 期限 |
| refresh_token | TEXT | リフレッシュトークン |
| account_id | VARCHAR | Dropboxアカウントid |
| account_name | VARCHAR | アカウント名 |
| is_active | BOOLEAN | 有効フラグ |
| last_refreshed_at | TIMESTAMP | 最終更新日時 |

---

## バックアップ構造

```
Dropbox/
└── YYYY/
    └── MM/
        └── DD/
            └── YYYY-MM-DD_HH-mm-ss/
                ├── database_backup_YYYY-MM-DD_HH-mm-ss.sql
                └── files_backup_YYYY-MM-DD_HH-mm-ss.zip
```

---

## 環境設定

### .env

```env
# Dropbox OAuth 2.0
DROPBOX_CLIENT_ID=your_client_id
DROPBOX_CLIENT_SECRET=your_client_secret
DROPBOX_REDIRECT_URI="${APP_URL}/auth/dropbox/callback"

# バックアップ設定
DROPBOX_ACCESS_TOKEN_LIFETIME=14400
BACKUP_TIMEZONE=Asia/Tokyo
BACKUP_RETENTION_DAYS=30
```

### config/backup.php（抜粋）

```php
'dropbox' => [
    'client_id' => env('DROPBOX_CLIENT_ID'),
    'client_secret' => env('DROPBOX_CLIENT_SECRET'),
    'redirect_uri' => env('DROPBOX_REDIRECT_URI'),
    'folder_path' => env('DROPBOX_BACKUP_FOLDER', ''),
    'max_file_size' => 150 * 1024 * 1024,  // 150MB
],

'files' => [
    'paths' => ['storage/app', 'public/uploads'],
    'exclude_paths' => [
        'storage/app/backups/*',
        'storage/logs/*',
        'node_modules/*',
        'vendor/*',
    ],
],
```

---

## スコープ（権限）

| スコープ | 説明 |
|----------|------|
| `files.content.write` | ファイル書き込み |
| `files.content.read` | ファイル読み込み |
| `account_info.read` | アカウント情報取得 |

---

## 復元機能

### コマンド

```bash
# 一覧表示
php artisan restore:dropbox --list

# 復元実行
php artisan restore:dropbox 2025-10-08_23-05-13
```

### 復元フロー

1. Dropboxからバックアップをダウンロード
2. 復元前に現在のDBを自動バックアップ
3. データベース復元（PDOベース、mysql不要）
4. ファイル復元（ZIP展開）
5. 一時ファイルのクリーンアップ

---

## 管理画面機能

| 機能 | 説明 |
|------|------|
| 接続状態表示 | アカウント名・認証状態 |
| トークン期限表示 | 期限日時・残り時間 |
| 手動トークン更新 | 強制リフレッシュ |
| バックアップ実行 | 手動バックアップ |
| バックアップ一覧 | Dropbox上のバックアップ一覧 |

---

## エラーハンドリング

| エラー | 対応 |
|--------|------|
| 401 Unauthorized | 自動リフレッシュ実行 |
| 429 Rate Limited | 指数バックオフで再試行 |
| invalid_access_token | リフレッシュトークンで更新 |
| File too large | exclude_pathsで不要ファイル除外 |

---

## 関連ファイル

| 種別 | ファイル |
|------|----------|
| モデル | `app/Models/DropboxToken.php` |
| サービス | `app/Services/DropboxService.php` |
| バックアップ | `app/Services/BackupService.php` |
| 認証コントローラー | `app/Http/Controllers/DropboxAuthController.php` |
| 管理コントローラー | `app/Http/Controllers/Admin/BackupController.php` |
| バックアップコマンド | `app/Console/Commands/BackupToDropbox.php` |
| 復元コマンド | `app/Console/Commands/RestoreFromDropbox.php` |
| ビュー | `resources/views/admin/backup/index.blade.php` |
| 設定 | `config/backup.php` |

---

## パフォーマンス実績

| 項目 | Before | After |
|------|--------|-------|
| バックアップサイズ | 208 MB | 4.6 MB |
| ローカルストレージ | 435 MB累積 | 0 MB（自動削除） |

---

**最終更新**: 2025年12月5日
**バージョン**: 3.0
