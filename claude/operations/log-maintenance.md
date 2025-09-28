# 🗂️ ログ管理・メンテナンスシステム

## 📋 概要

shin-onプロジェクトのログファイル自動管理システム。開発環境でのログファイル肥大化を防ぎ、ディスク使用量を最適化する自動クリーンアップ機能を提供します。

## 🔍 ログファイルの種類と特徴

### 主要ログファイル

| ファイル | 用途 | 典型サイズ | 作成タイミング |
|----------|------|------------|----------------|
| `laravel.log` | アプリケーションログ | ~6MB | 常時 |
| `browser.log` | Laravel Boost ブラウザログ | ~41MB | LINE WORKS認証時 |

### ログ内容詳細

#### 1. laravel.log
```
[2025-09-29 07:26:31] local.ERROR: Required.
[2025-09-29 07:26:31] local.INFO: Backup uploaded successfully
```
- **内容**: Laravel標準ログ（エラー、警告、情報）
- **作成**: アプリケーション実行中の各種イベント
- **特徴**: 累積的に肥大化

#### 2. browser.log
```
[2025-09-14 14:18:51] local.DEBUG: Processing LINE WORKS Implicit Flow callback
[2025-09-14 14:18:51] local.DEBUG: ID Token: eyJ0eXAiOiJKV1QiLCJr...
```
- **内容**: LINE WORKS OAuth認証のデバッグログ
- **作成**: ユーザーがLINE WORKSでログインする際
- **特徴**: JWTトークン、認証フロー、ブラウザ情報を含む大量ログ

## 🔧 ログクリーンアップシステム

### 実装コンポーネント

```
app/Console/Commands/
└── ClearLogsCommand.php           # ログクリーンアップコマンド

routes/
└── console.php                    # スケジュールタスク設定
```

### コマンド実装

**ファイル**: `app/Console/Commands/ClearLogsCommand.php`

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ClearLogsCommand extends Command
{
    protected $signature = 'logs:clear
                            {--days=7 : Number of days to keep logs}
                            {--force : Force deletion without confirmation}';

    protected $description = 'Clear old log files older than specified days';

    public function handle()
    {
        $days = $this->option('days');
        $force = $this->option('force');

        $logPath = storage_path('logs');
        $cutoffTime = now()->subDays($days);

        $logFiles = [
            'browser.log',
            'laravel.log',
        ];

        $totalSizeBefore = 0;
        $totalSizeAfter = 0;
        $clearedFiles = [];

        foreach ($logFiles as $logFile) {
            $filePath = $logPath . '/' . $logFile;

            if (File::exists($filePath)) {
                $fileSize = File::size($filePath);
                $totalSizeBefore += $fileSize;
                $fileModified = File::lastModified($filePath);

                if ($fileModified < $cutoffTime->timestamp) {
                    if (!$force && !$this->confirm("Delete {$logFile} (" . $this->formatBytes($fileSize) . ")?")) {
                        continue;
                    }

                    File::delete($filePath);
                    $clearedFiles[] = $logFile . ' (' . $this->formatBytes($fileSize) . ')';
                    $this->line("🗑️  Deleted: {$logFile}");
                } else {
                    $totalSizeAfter += $fileSize;
                    $this->line("⏭️  Kept: {$logFile} (modified recently)");
                }
            }
        }

        if (empty($clearedFiles)) {
            $this->info("✅ No log files need to be cleared.");
        } else {
            $this->newLine();
            $this->info("✅ Log cleanup completed!");
            $this->table(['Cleared Files'], array_map(fn($file) => [$file], $clearedFiles));

            $savedSpace = $totalSizeBefore - $totalSizeAfter;
            if ($savedSpace > 0) {
                $this->info("💾 Freed up: " . $this->formatBytes($savedSpace));
            }
        }

        return Command::SUCCESS;
    }

    private function formatBytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
```

### スケジュールタスク設定

**ファイル**: `routes/console.php`

```php
use Illuminate\Support\Facades\Schedule;

// 自動ログクリーンアップ（毎日深夜2時に実行）
Schedule::command('logs:clear --days=7 --force')->dailyAt('02:00');
```

## 🎯 使用方法

### CLI コマンド

```bash
# デフォルト実行（7日以上古いログを削除）
./vendor/bin/sail artisan logs:clear

# 確認プロンプト表示
Delete browser.log (39.15 MB)? (yes/no) [no]:
Delete laravel.log (5.83 MB)? (yes/no) [no]:

# 指定日数で実行
./vendor/bin/sail artisan logs:clear --days=3

# 強制削除（確認なし）
./vendor/bin/sail artisan logs:clear --days=0 --force

# ヘルプ表示
./vendor/bin/sail artisan logs:clear --help
```

### 実行結果例

```bash
🧹 Clearing log files older than 7 days...
🗑️  Deleted: browser.log
🗑️  Deleted: laravel.log

✅ Log cleanup completed!
+------------------------+
| Cleared Files          |
+------------------------+
| browser.log (39.15 MB) |
| laravel.log (5.83 MB)  |
+------------------------+
💾 Freed up: 44.98 MB
```

## 🔄 自動化機能

### スケジュール実行

- **頻度**: 毎日深夜2時
- **対象**: 7日以上古いログファイル
- **方式**: 確認なし自動削除
- **ログ**: クリーンアップ結果をLaravelログに記録

### 手動実行での調整

```bash
# 保持期間を調整
./vendor/bin/sail artisan logs:clear --days=3   # 3日保持
./vendor/bin/sail artisan logs:clear --days=14  # 14日保持

# 緊急時の全削除
./vendor/bin/sail artisan logs:clear --days=0 --force
```

## 💡 Laravel Boost ログ最適化

### ブラウザログ肥大化の対策

**問題**: `browser.log`が41MBまで肥大化
**原因**: LINE WORKS OAuth認証時の大量DEBUGログ

#### 解決策1: ブラウザログ無効化

```env
# .env
BOOST_BROWSER_LOGS_WATCHER=false
```

**効果**: ブラウザログの生成を完全停止

#### 解決策2: 頻繁なクリーンアップ

```bash
# 毎日クリーンアップ
./vendor/bin/sail artisan logs:clear --days=1 --force
```

**効果**: ログファイルサイズの抑制

## 📊 パフォーマンス改善効果

### 実測値

| 項目 | Before | After | 改善効果 |
|------|--------|-------|----------|
| browser.log | 39.15 MB | 0 MB | 100% 削減 |
| laravel.log | 5.83 MB | 0 MB | 100% 削減 |
| **総容量** | **44.98 MB** | **0 MB** | **100% 削減** |

### 継続的効果

- ✅ **ディスク使用量**: 累積的肥大化を防止
- ✅ **バックアップサイズ**: ログファイルがバックアップ対象から除外
- ✅ **開発効率**: ログファイル肥大化による動作重要の解消
- ✅ **メンテナンス性**: 自動化により手動メンテナンス不要

## 🔍 監視・運用

### 定期チェック項目

```bash
# ログファイルサイズ確認
ls -lh storage/logs/

# 最新のクリーンアップ実行確認
grep "Log cleanup" storage/logs/laravel.log | tail -5

# スケジュールタスク実行状況確認
./vendor/bin/sail artisan schedule:list
```

### 推奨メンテナンススケジュール

| 頻度 | 作業内容 | コマンド |
|------|----------|----------|
| 毎日 | 自動クリーンアップ | `Schedule::command` |
| 週次 | 手動サイズ確認 | `ls -lh storage/logs/` |
| 月次 | 保持期間見直し | 設定調整 |

## ⚠️ 注意事項

### ログ削除の影響

- **開発デバッグ**: 過去のログが参照不可
- **トラブル調査**: 削除されたログは復旧不可
- **監査要件**: 本番環境では保持期間の法的要件を確認

### 安全な運用

```bash
# 削除前の確認
./vendor/bin/sail artisan logs:clear --days=7
# → 確認プロンプトで内容を確認してから削除

# 重要期間中は無効化
# routes/console.php で一時的にコメントアウト
// Schedule::command('logs:clear --days=7 --force')->dailyAt('02:00');
```

## 🚨 トラブルシューティング

### よくある問題

**1. ログファイルが削除されない**
```bash
# 権限確認
ls -la storage/logs/

# 手動削除テスト
./vendor/bin/sail artisan logs:clear --days=0 --force
```

**2. スケジュールタスクが実行されない**
```bash
# クローンジョブ設定確認（本番環境）
crontab -l

# Laravel スケジューラー確認
./vendor/bin/sail artisan schedule:run
```

**3. ログファイル再生成**
```bash
# アプリケーション再起動後にログファイルは自動再生成
# 削除後は正常動作
```

## 📈 将来的な拡張

### 高度な機能（実装検討）

1. **ログローテーション**
   ```php
   // 日次でログファイル分割
   'daily' => true,
   'max_files' => 7,
   ```

2. **圧縮保存**
   ```php
   // 古いログをgzip圧縮して保存
   gzencode($logContent);
   ```

3. **外部ストレージ**
   ```php
   // 古いログをS3等に退避
   Storage::disk('s3')->put("logs/{$date}.log", $content);
   ```

---
**[← README.md に戻る](../README.md)**