# LINE WORKS Bot API 実装ガイド

## 概要

LINE WORKS Bot APIを使用したメッセージ・ファイル送信機能の実装ガイド。

### 実装機能
- JWT認証によるAccess Token取得
- テキストメッセージ送信
- ファイル（PDF）送信

---

## 認証方式

### JWT認証フロー

```
Private Key → JWT生成(RS256) → Access Token取得 → API呼び出し
```

### 環境変数

```env
# LINE WORKS BOT API
LINEWORKS_API_BASE_URL=https://www.worksapis.com/v1.0
LINEWORKS_AUTH_URL=https://auth.worksmobile.com/oauth2/v2.0/token
LINEWORKS_BOT_ID=your_bot_id
LINEWORKS_BOT_SECRET=your_bot_secret
LINEWORKS_DB_CLIENT_ID=your_client_id
LINEWORKS_DB_CLIENT_SECRET=your_client_secret
LINEWORKS_SERVICE_ACCOUNT=serviceaccount@yourdomain
LINEWORKS_PRIVATE_KEY_PATH=lineworks/private_key.pem
```

---

## API エンドポイント

| 用途 | メソッド | URL |
|------|--------|-----|
| Access Token取得 | POST | `https://auth.worksmobile.com/oauth2/v2.0/token` |
| メッセージ送信 | POST | `/bots/{botId}/users/{userId}/messages` |
| アップロードURL取得 | POST | `/bots/{botId}/attachments` |

---

## 主要メソッド

### LineWorksBotService

| メソッド | 説明 |
|----------|------|
| `getAccessToken()` | Access Token取得（キャッシュ59分） |
| `sendMessage($userId, $message)` | テキストメッセージ送信 |
| `sendPdfToUser($userId, $filePath, $fileName)` | PDF送信（統合メソッド） |

### ファイル送信フロー

```
1. getUploadUrl() → fileId, uploadUrl 取得
2. uploadFile() → ファイルアップロード
3. sendFileMessage() → ファイルメッセージ送信
```

---

## 使用例

### テキストメッセージ送信

```php
$botService = app(LineWorksBotService::class);
$botService->sendMessage('user@domain', 'こんにちは！');
```

### PDF送信

```php
$botService = app(LineWorksBotService::class);
$botService->sendPdfToUser(
    $user->lineworks_id,
    $tempFilePath,
    '機材リスト.pdf'
);
```

---

## ルート

```php
Route::post('phases/{phase}/send-lineworks', [PhaseController::class, 'sendPdfToLineWorks'])
    ->name('phases.send-lineworks')
    ->middleware(['auth', 'performance.access']);
```

---

## 制限事項

| 項目 | 制限 |
|------|------|
| アップロードURL有効期限 | 24時間 |
| fileId有効期限 | 24時間 |
| uploadURL再利用 | 不可（1回限り） |
| ファイルサイズ上限 | 約10MB |

---

## セキュリティ

- Private Keyは `storage/app/lineworks/` に保存
- `.gitignore` に追加してコミット禁止
- Access Tokenはキャッシュ使用（API負荷軽減）

---

## トラブルシューティング

| エラー | 原因 | 解決策 |
|--------|------|--------|
| Private key not found | ファイル未配置 | `storage/app/lineworks/private_key.pem` を確認 |
| Failed to get access token | 認証情報不正 | `.env` の設定確認、`config:clear` |
| Failed to upload file | URL期限切れ/サイズ超過 | 即座にアップロード、サイズ確認 |
| メッセージ届かない | ユーザーID不正/Bot未追加 | `lineworks_id` 確認、Bot友達追加 |

---

## 関連ファイル

| 種別 | ファイル |
|------|----------|
| サービス | `app/Services/LineWorksBotService.php` |
| コントローラー | `app/Http/Controllers/PhaseController.php` |
| 設定 | `config/services.php` |
| Private Key | `storage/app/lineworks/private_key.pem` |

---

## 参考リンク

- [LINE WORKS API ドキュメント](https://developers.worksmobile.com/jp/docs/api)
- [Bot API リファレンス](https://developers.worksmobile.com/jp/docs/bot-api)
- [LINE WORKS SSO仕様書](./LINE_WORKS_SSO_Specification.md)

---

**最終更新**: 2025年12月5日
