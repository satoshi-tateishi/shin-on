# 内部サービスAPI ガイド

## 概要

shin-on_db が提供する内部API。同じサーバー上のDockerコンテナ（Django・Laravel等）から、マスターデータを参照するためのRead-only APIです。

### 提供データ
- **使用場所マスター**（Location）- 劇場・稽古場・倉庫の情報
- **プロダクションマスター**（Production）- 制作会社の情報

---

## 認証

**shin-on Portal の JWT をBearerトークンとして使用します。**

Portal SSOで認証済みのユーザーセッションから `portal_jwt` Cookie の値を取り出し、`Authorization: Bearer` ヘッダーに設定してリクエストします。

```
Authorization: Bearer <portal_jwt の値>
```

### 認証フロー

```
ユーザー → Portal SSO 認証 → portal_jwt Cookie 発行
              ↓
呼び出し元アプリ → Authorization: Bearer <portal_jwt> → shin-on_db API
              ↓
         PortalJwtService が JWT の署名・有効期限・is_active を検証
              ↓
         検証OK → データ返却 / 検証NG → 401 Unauthorized
```

### エラーレスポンス

| ステータス | 原因 |
|-----------|------|
| `401 Unauthorized` | Bearerトークンなし、または無効なJWT（署名不正・期限切れ・無効ユーザー） |
| `404 Not Found` | 指定IDが存在しない、または非アクティブ |

---

## エンドポイント

ベースURL（Docker内部からのアクセス）: `http://shin-on_db_app/api/v1`

> **注意:** 開発環境では `http://localhost:8081/api/v1`

### 使用場所マスター

| メソッド | パス | 説明 |
|---------|------|------|
| `GET` | `/locations` | アクティブな使用場所の一覧 |
| `GET` | `/locations/{id}` | 使用場所の詳細 |

**クエリパラメータ（`/locations`）**

| パラメータ | 値 | 説明 |
|-----------|-----|------|
| `type` | `劇場` / `稽古場` / `倉庫` | タイプでフィルタ（省略時は全件） |

**レスポンスフィールド（`/locations`）**

| フィールド | 型 | 説明 |
|-----------|-----|------|
| `id` | integer | ID |
| `sort` | integer | 表示順 |
| `type` | string | `劇場` / `稽古場` / `倉庫` |
| `name` | string | 名称 |
| `furigana` | string | フリガナ |
| `tel1_name` | string\|null | 電話1 ラベル |
| `tel1` | string\|null | 電話1 |
| `tel2_name` | string\|null | 電話2 ラベル |
| `tel2` | string\|null | 電話2 |
| `fax` | string\|null | FAX |
| `email1_name` | string\|null | メール1 ラベル |
| `email1` | string\|null | メール1 |
| `email2_name` | string\|null | メール2 ラベル |
| `email2` | string\|null | メール2 |
| `postal_code` | string\|null | 郵便番号 |
| `address` | string\|null | 住所 |
| `note` | string\|null | 備考 |
| `is_active` | boolean | 有効フラグ |
| `is_inventory_visible` | boolean | 在庫フィルター表示フラグ |
| `is_transfer_visible` | boolean | 移動フィルター表示フラグ |
| `is_main_warehouse` | boolean | メイン倉庫フラグ |
| `created_at` | string (ISO 8601) | 作成日時 |
| `updated_at` | string (ISO 8601) | 更新日時 |

---

### プロダクションマスター

| メソッド | パス | 説明 |
|---------|------|------|
| `GET` | `/productions` | アクティブなプロダクションの一覧 |
| `GET` | `/productions/{id}` | プロダクションの詳細 |

**クエリパラメータ（`/productions`）**

| パラメータ | 値 | 説明 |
|-----------|-----|------|
| `type` | `株式会社` / `有限会社` / `合同会社` / `財団法人` / `公益財団法人` / `公益社団法人` / `その他` | タイプでフィルタ（省略時は全件） |

**レスポンスフィールド（`/productions`）**

| フィールド | 型 | 説明 |
|-----------|-----|------|
| `id` | integer | ID |
| `sort` | integer | 表示順 |
| `type` | string | 法人種別 |
| `name` | string | 名称 |
| `postal_code` | string\|null | 郵便番号 |
| `address` | string\|null | 住所 |
| `note` | string\|null | 備考 |
| `is_active` | boolean | 有効フラグ |
| `created_at` | string (ISO 8601) | 作成日時 |
| `updated_at` | string (ISO 8601) | 更新日時 |

---

## レスポンス形式

Laravel API Resource の標準形式です。

**一覧（`/locations`）**

```json
{
  "data": [
    {
      "id": 1,
      "sort": 1,
      "type": "倉庫",
      "name": "本拠地倉庫",
      "furigana": "ホンキョチソウコ",
      "tel1_name": "代表",
      "tel1": "03-0000-0000",
      "tel2_name": null,
      "tel2": null,
      "fax": null,
      "email1_name": null,
      "email1": null,
      "email2_name": null,
      "email2": null,
      "postal_code": "000-0000",
      "address": "東京都...",
      "note": null,
      "is_active": true,
      "is_inventory_visible": true,
      "is_transfer_visible": true,
      "is_main_warehouse": true,
      "created_at": "2025-09-26T15:20:04+09:00",
      "updated_at": "2025-09-26T15:20:04+09:00"
    }
  ]
}
```

**詳細（`/locations/{id}`）**

```json
{
  "data": {
    "id": 1,
    ...
  }
}
```

---

## 実装例

### Django（Python）

```python
import requests


def get_locations(request, location_type: str | None = None):
    """Portal JWT を使ってshin-on_db から使用場所一覧を取得する。"""
    portal_jwt = request.COOKIES.get('portal_jwt')
    if not portal_jwt:
        raise PermissionError('Portal JWT が見つかりません')

    params = {}
    if location_type:
        params['type'] = location_type

    response = requests.get(
        'http://shin-on_db_app/api/v1/locations',
        headers={'Authorization': f'Bearer {portal_jwt}'},
        params=params,
        timeout=10,
    )
    response.raise_for_status()
    return response.json()['data']


def get_location(request, location_id: int):
    """指定IDの使用場所を取得する。"""
    portal_jwt = request.COOKIES.get('portal_jwt')

    response = requests.get(
        f'http://shin-on_db_app/api/v1/locations/{location_id}',
        headers={'Authorization': f'Bearer {portal_jwt}'},
        timeout=10,
    )
    if response.status_code == 404:
        return None
    response.raise_for_status()
    return response.json()['data']
```

---

### Laravel（PHP）

```php
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;

class ShinOnDbService
{
    private string $baseUrl = 'http://shin-on_db_app/api/v1';

    public function __construct(private Request $request) {}

    /** @return array<int, array<string, mixed>> */
    public function getLocations(?string $type = null): array
    {
        $response = Http::withToken($this->portalJwt())
            ->get("{$this->baseUrl}/locations", array_filter(['type' => $type]));

        $response->throw();

        return $response->json('data');
    }

    /** @return array<string, mixed>|null */
    public function getLocation(int $id): ?array
    {
        $response = Http::withToken($this->portalJwt())
            ->get("{$this->baseUrl}/locations/{$id}");

        if ($response->notFound()) {
            return null;
        }

        $response->throw();

        return $response->json('data');
    }

    /** @return array<int, array<string, mixed>> */
    public function getProductions(?string $type = null): array
    {
        $response = Http::withToken($this->portalJwt())
            ->get("{$this->baseUrl}/productions", array_filter(['type' => $type]));

        $response->throw();

        return $response->json('data');
    }

    private function portalJwt(): string
    {
        $token = $this->request->cookie('portal_jwt');
        if (! $token) {
            throw new \RuntimeException('Portal JWT が見つかりません');
        }

        return $token;
    }
}
```

---

## Docker ネットワーク設定

同じサーバー上のDockerコンテナから接続するには、`shin-on-internal` ネットワークへの参加が必要です。

**`docker-compose.yml` への追加例:**

```yaml
services:
  your_app:
    # ...
    networks:
      - shin-on-internal

networks:
  shin-on-internal:
    external: true
```

接続先ホスト名: `shin-on_db_app`

> `shin-on-internal` ネットワークは shin-on_portal（gateway-apache）が管理しています。参加方法は shin-on_portal の管理者に確認してください。
>
> **注意:** `shin-on_db_app` 自体も `shin-on-internal` に参加している必要があります。shin-on_db の `docker-compose.yml` には設定済みですが、未参加の場合は以下で即時接続できます：
> ```
> docker network connect shin-on-internal shin-on_db_app
> ```

---

## 関連ドキュメント

- [LINE_WORKS_SSO_Specification.md](LINE_WORKS_SSO_Specification.md) - Portal SSO認証の詳細
- [Deployment_Guide.md](Deployment_Guide.md) - Dockerネットワーク構成の詳細
