# LINE WORKS SSO 仕様書

## 概要

LINE WORKS OAuth 2.0 OpenID Connect Implicit Flowを使用したシングルサインオン（SSO）認証 + アプリ内2段階認証（OTP）。

### 認証フロー

```
ユーザー → LINE WORKS認証 → ID Token検証 → OTP生成・Bot送信 → OTP入力画面 → OTP検証 → ログイン完了
```

---

## エンドポイント

### LINE WORKS OAuth 2.0

| 項目 | URL |
|------|-----|
| 認証 | `https://auth.worksmobile.com/oauth2/v2.0/authorize` |
| トークン | `https://auth.worksmobile.com/oauth2/v2.0/token` |

### アプリケーション

| URL | 説明 |
|-----|------|
| `/auth/lineworks` | LINE WORKS認証開始 |
| `/auth/lineworks/callback` | コールバック（id_token受信） |
| `/auth/lineworks/process-token` | ID Token処理 → OTP送信 |
| `/two-factor/challenge` | OTP入力画面 |
| `/two-factor/verify` | OTP検証 |
| `/two-factor/resend` | OTP再送信 |

---

## 環境変数

```env
# LINE WORKS OAuth設定
LINEWORKS_CLIENT_ID=your_client_id
LINEWORKS_CLIENT_SECRET=your_client_secret
LINEWORKS_REDIRECT_URI="${APP_URL}/auth/lineworks/callback"
LINEWORKS_DOMAIN=shin-on1981
```

---

## OTP（2段階認証）

### 概要

LINE WORKS SSO認証成功後、アプリ内でOTPを生成しLINE WORKS Bot経由でユーザーに送信。ユーザーはOTPを入力してログイン完了。

### 認証フロー詳細

```
1. ユーザーがLINE WORKSで認証
2. アプリがID Tokenを検証
3. アプリが6桁OTPを生成
4. OTPをハッシュ化してDBに保存
5. LINE WORKS Bot経由でOTPを送信
6. ユーザーをOTP入力画面にリダイレクト
7. ユーザーがOTPを入力
8. アプリがOTPを検証
9. ログイン完了
```

### OTP仕様

| 項目 | 値 |
|------|-----|
| 桁数 | 6桁 |
| 形式 | 数字のみ（4種類の数字から生成） |
| 有効期限 | 10分 |
| 保存方法 | bcryptハッシュ化 |
| 送信方法 | LINE WORKS Bot API |

### セキュリティ対策

| 対策 | 内容 |
|------|------|
| 試行回数制限 | 5回失敗でアカウントロック |
| ロック時間 | 3分間 |
| 有効期限 | 10分 |
| ハッシュ化 | bcrypt |
| ログ記録 | 全操作をTwoFactorLogに記録 |

### データベースカラム（users）

| カラム | 型 | 説明 |
|--------|-----|------|
| two_factor_code | VARCHAR | OTPハッシュ |
| two_factor_expires_at | TIMESTAMP | 有効期限 |
| two_factor_attempts | INT | 失敗回数 |
| two_factor_locked_until | TIMESTAMP | ロック解除日時 |

---

## ID Token

### JWT構造

```json
{
    "sub": "unique_user_id",      // ← lineworks_idに格納
    "name": "立石 智史",
    "email": "user@shin-on1981",
    "iss": "https://auth.worksmobile.com",
    "aud": "your_client_id",
    "exp": 1640998800,
    "nonce": "nonce_abc123"
}
```

### lineworks_id

| 項目 | 内容 |
|------|------|
| データ型 | VARCHAR(255), UNIQUE |
| 格納値 | ID Tokenの`sub`フィールド |
| 用途 | ユーザー検索、アカウント連携 |

---

## セキュリティ

| 対策 | 説明 |
|------|------|
| State検証 | CSRF攻撃防止 |
| Nonce検証 | リプレイ攻撃防止 |
| ドメイン制限 | `shin-on1981`のみ許可 |
| HTTPS強制 | 通信暗号化 |
| 2段階認証 | OTP必須 |
| セッション再生成 | OTP検証成功時 |

---

## エラーハンドリング

| エラー | 原因 | 対処 |
|--------|------|------|
| Domain validation failed | 許可外ドメイン | shin-on1981ドメインでログイン |
| State validation failed | CSRF/セッション切れ | 再度ログイン |
| 認証コードが正しくありません | OTP不一致 | 正しいOTPを入力 |
| 有効期限が切れています | OTP期限切れ | 再送信ボタンで再発行 |
| アカウントがロックされています | 5回失敗 | 3分後に再試行 |

---

## 関連ファイル

| 種別 | ファイル |
|------|----------|
| SSO認証 | `app/Http/Controllers/Auth/LineWorksController.php` |
| OTP検証 | `app/Http/Controllers/Auth/TwoFactorController.php` |
| Bot送信 | `app/Services/LineWorksBotService.php` |
| OTP入力画面 | `resources/views/auth/two-factor-challenge.blade.php` |
| ログモデル | `app/Models/TwoFactorLog.php` |
| Provider | `app/Socialite/LineWorksProvider.php` |

---

## 参考リンク

- [LINE WORKS OAuth ドキュメント](https://developers.worksmobile.com/jp/docs/auth)
- [LINE WORKS Bot API ガイド](./LINE_WORKS_Bot_API_Guide.md)

---

**最終更新**: 2025年12月5日
