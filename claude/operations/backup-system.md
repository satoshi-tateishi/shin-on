# ☁️ Dropboxバックアップシステム

## 📋 概要

shin-onプロジェクトでは、OAuth 2.0を使用したDropboxバックアップシステムを実装しています。データベースとアプリケーションファイルを自動的にDropboxにバックアップし、ローカルの重複ファイルを自動削除します。

## 🔧 システム構成

### 主要コンポーネント
- **OAuth 2.0認証**: リフレッシュトークン対応の長期運用
- **自動バックアップ**: データベース + ファイル圧縮アップロード
- **ログクリーンアップ**: 定期的なログファイル削除
- **管理画面**: Web UIでのバックアップ操作

### ファイル構成
```
app/
├── Services/
│   ├── DropboxService.php      # Dropbox API操作
│   └── BackupService.php       # バックアップ統括処理
├── Http/Controllers/
│   ├── DropboxAuthController.php       # OAuth認証
│   └── Admin/BackupController.php      # 管理画面
├── Models/
│   └── DropboxToken.php        # OAuthトークン管理
└── Console/Commands/
    ├── DropboxBackupCommand.php   # CLIバックアップ
    ├── RestoreDropboxCommand.php  # CLI復元（準備中）
    └── ClearLogsCommand.php       # ログクリーンアップ
```

## 🚀 セットアップ

### 1. Dropboxアプリ設定
```
OAuth redirect URI: http://localhost:8081/auth/dropbox/callback
```

### 2. 環境変数設定
```bash
# .env
DROPBOX_CLIENT_ID=your_client_id
DROPBOX_CLIENT_SECRET=your_client_secret
DROPBOX_REDIRECT_URI="${APP_URL}/auth/dropbox/callback"
```

### 3. OAuth認証
```
http://localhost:8081/admin/backup
↓
「Dropbox認証」ボタンクリック
↓
Dropbox認証完了
```

## 📁 バックアップ構成

### ディレクトリ構造
```
Dropbox/
└── 2025/
    └── 09/
        └── 29/
            └── 2025-09-29_07-26-12/
                ├── database_backup_2025-09-29_07-26-12.sql
                └── files_backup_2025-09-29_07-26-12.zip
```

### バックアップ対象
```php
// config/backup.php
'paths' => [
    'storage/app',
    'public/uploads',
],
'exclude_paths' => [
    'storage/app/backups/*',    // 過去バックアップ除外
    'storage/logs/*',           // ログファイル除外
    'node_modules/*',           // 開発依存関係除外
    'vendor/*',                 // Composer依存関係除外
    '.git/*',                   // Gitファイル除外
],
```

## 🎯 使用方法

### CLI コマンド
```bash
# フルバックアップ実行
./vendor/bin/sail artisan backup:dropbox

# 接続テストのみ
./vendor/bin/sail artisan backup:dropbox --test

# バックアップ一覧確認
./vendor/bin/sail artisan restore:dropbox --list

# ログクリーンアップ
./vendor/bin/sail artisan logs:clear --days=7
```

### Web管理画面
```
URL: http://localhost:8081/admin/backup
機能:
- Dropbox認証状況確認
- ワンクリックバックアップ実行
- バックアップ履歴表示
```

## 🔄 自動化機能

### 自動ログクリーンアップ
```php
// routes/console.php
Schedule::command('logs:clear --days=7 --force')->dailyAt('02:00');
```

### 自動ローカルファイル削除
- Dropboxアップロード成功後、ローカルバックアップファイルを自動削除
- ディスク使用量を最適化

## 📊 パフォーマンス最適化

### ファイルサイズ制限
- **Dropbox制限**: 150MB以下
- **最適化後**: ~4.6MB (DB: 1.48MB + ファイル: 3.1MB)

### 除外による削減効果
| 項目 | Before | After |
|------|--------|--------|
| 総サイズ | 208MB | 4.6MB |
| バックアップ時間 | 長時間 | 高速 |
| ストレージ使用量 | 435MB累積 | 0MB (自動削除) |

## 🔐 セキュリティ

### OAuth 2.0仕様
- **CSRF保護**: 状態パラメータによる検証
- **リフレッシュトークン**: 長期運用対応
- **トークン有効期限**: 自動更新機能

### ログセキュリティ
- 認証情報の自動除外
- 開発ログの本番除外

## 🚨 トラブルシューティング

### よくある問題

**1. API域名エラー**
```
Error: Could not resolve host: content.dropbox.com
↓
解決: content.dropboxapi.com を使用
```

**2. ファイルサイズ超過**
```
Error: File too large: 208MB
↓
解決: exclude_paths設定で不要ファイル除外
```

**3. 認証トークン期限切れ**
```
Error: invalid_access_token
↓
解決: 自動リフレッシュ機能で解決
```

### ログ確認
```bash
# バックアップログ
./vendor/bin/sail artisan log:tail

# Dropbox API通信ログ
tail -f storage/logs/laravel.log | grep Dropbox
```

## 📈 監視・メンテナンス

### 定期確認項目
- [ ] OAuth認証状況
- [ ] バックアップ成功率
- [ ] ストレージ使用量
- [ ] ログファイルサイズ

### 推奨メンテナンススケジュール
```bash
# 毎日: ログクリーンアップ（自動）
02:00 - logs:clear --days=7 --force

# 週次: バックアップテスト
backup:dropbox --test

# 月次: 古いバックアップ整理（手動）
restore:dropbox --list
```

---
**[← README.md に戻る](../README.md)**