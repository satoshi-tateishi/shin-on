# Role権限設計仕様書

## 概要
本システムでは4種類のユーザーロールを定義し、各機能へのアクセス権限を制御しています。

## ロール定義

| ロール | ラベル | 説明 |
|--------|--------|------|
| `viewer` | 閲覧者 | 閲覧のみ。編集・作成・削除は一切不可 |
| `general` | 一般 | 基本操作可。担当公演の編集 + 修理管理の作成・編集が可能 |
| `editor` | 編集者 | 編集・削除可。ユーザー管理・バックアップ・CSV操作を除く全機能 |
| `admin` | 管理者 | 全機能へのフルアクセス |

### ロールの階層関係
```
viewer < general < editor < admin
```

## 権限マトリックス

### ダッシュボード・メニューアクセス

| 機能 | viewer | general | editor | admin |
|------|--------|---------|--------|-------|
| 機材管理メニュー | ○ | ○ | ○ | ○ |
| - 公演使用機材 | ○ | ○ | ○ | ○ |
| - 機材スケジュール表 | ○ | ○ | ○ | ○ |
| - 倉庫別 在庫表示 | ○ | ○ | ○ | ○ |
| - 修理管理 | ○ | ○ | ○ | ○ |
| - 倉庫間移動 | × | ○ | ○ | ○ |
| マスタ管理メニュー | ○ | ○ | ○ | ○ |
| システム管理メニュー | × | × | ○ | ○ |
| - 会社設定 | × | × | ○ | ○ |
| - バックアップ管理 | × | × | × | ○ |
| - Role権限設定 | ○ | ○ | ○ | ○ |
| アクティビティメニュー | × | × | × | ○ |
| - 操作履歴 | × | × | × | ○ |

### 機材管理セクション

| 機能 | viewer | general | editor | admin |
|------|--------|---------|--------|-------|
| 公演一覧 - 閲覧 | ○ | ○ | ○ | ○ |
| 公演一覧 - 新規作成 | × | × | ○ | ○ |
| 公演詳細 - 閲覧 | ○ | ○ | ○ | ○ |
| 公演詳細 - 編集 | × | 👤 | ○ | ○ |
| 公演詳細 - フェーズ追加 | × | 👤 | ○ | ○ |
| 公演詳細 - フェーズ編集 | × | 👤 | ○ | ○ |
| フェーズ詳細 - 閲覧 | ○ | ○ | ○ | ○ |
| フェーズ詳細 - 編集 | × | 👤 | ○ | ○ |
| フェーズ詳細 - 削除 | × | × | ○ | ○ |
| フェーズ詳細 - PDF出力 | ○ | ○ | ○ | ○ |
| フェーズ機材一覧 - 閲覧 | ○ | ○ | ○ | ○ |
| フェーズ機材一覧 - 機材追加 | × | 👤 | ○ | ○ |
| フェーズ機材一覧 - 出庫/返却 | × | 👤 | ○ | ○ |
| フェーズ機材一覧 - 削除 | × | 👤 | ○ | ○ |
| 修理管理一覧 - 閲覧 | ○ | ○ | ○ | ○ |
| 修理管理一覧 - 新規作成 | × | ○ | ○ | ○ |
| 修理管理一覧 - 編集 | × | ○ | ○ | ○ |
| 修理詳細 - 閲覧 | ○ | ○ | ○ | ○ |
| 修理詳細 - 編集/ステータス変更 | × | ○ | ○ | ○ |
| 修理詳細 - PDF出力 | ○ | ○ | ○ | ○ |
| 倉庫別在庫 - PDF出力 | ○ | ○ | ○ | ○ |

> 👤 = 公演スタッフとして登録されているユーザーのみ

### マスタ管理セクション

| 機能 | viewer | general | editor | admin |
|------|--------|---------|--------|-------|
| 機材マスタ一覧 - 閲覧 | ○ | ○ | ○ | ○ |
| 機材マスタ一覧 - 新規作成 | × | × | ○ | ○ |
| 機材マスタ一覧 - 並び替え | × | × | ○ | ○ |
| 機材マスタ一覧 - PDF出力 | ○ | ○ | ○ | ○ |
| 機材マスタ詳細 - 閲覧 | ○ | ○ | ○ | ○ |
| 機材マスタ詳細 - 編集 | × | × | ○ | ○ |
| カテゴリ/サブカテ一覧 - 閲覧 | ○ | ○ | ○ | ○ |
| カテゴリ/サブカテ一覧 - 新規作成 | × | × | ○ | ○ |
| カテゴリ/サブカテ一覧 - 並び替え | × | × | ○ | ○ |
| 機材セット詳細 - 編集/削除 | × | × | ○ | ○ |
| ユーザーマスタ - 新規作成 | × | × | × | ○ |
| ユーザー詳細 - 編集 | × | × | × | ○ |
| その他マスタ - 新規作成/編集 | × | × | ○ | ○ |
| CSVインポート/エクスポート | × | × | × | ○ |

## 新機能実装時の権限設計ガイドライン

### 1. 機能タイプ別の推奨権限設定

#### 閲覧機能
```php
// 全ロールに許可
// 権限チェック不要（認証済みであれば可）
```

#### PDF/帳票出力機能
```php
// 全ロールに許可（推奨）
// 閲覧できるデータはPDF出力も可能とする方針
```

#### 新規作成・編集機能（一般データ）
```php
// editor, admin に許可
@if(in_array(auth()->user()->role, ['editor', 'admin']))
    // 新規作成・編集ボタン表示
@endif
```

#### 新規作成・編集機能（修理管理）
```php
// general, editor, admin に許可
@if(in_array(auth()->user()->role, ['general', 'editor', 'admin']))
    // 修理関連の作成・編集ボタン表示
@endif
```

#### 担当者限定機能（公演関連）
```php
// general + 公演スタッフ、または editor, admin
@if(auth()->user()->role === 'editor' ||
    auth()->user()->role === 'admin' ||
    $performance->staff->contains('user_id', auth()->id()))
    // 編集ボタン表示
@endif
```

#### 削除機能
```php
// editor, admin に許可
@if(in_array(auth()->user()->role, ['editor', 'admin']))
    // 削除ボタン表示
@endif
```

#### システム管理機能
```php
// editor, admin に許可（バックアップ除く）
@if(in_array(auth()->user()->role, ['editor', 'admin']))
    // システム管理メニュー表示
@endif
```

#### バックアップ・ユーザー管理
```php
// admin のみ
@if(auth()->user()->role === 'admin')
    // バックアップ・ユーザー管理表示
@endif
```

#### CSVインポート/エクスポート
```php
// admin のみ
@if(auth()->user()->role === 'admin')
    // CSVメニュー表示
@endif
```

### 2. Bladeテンプレートでの実装パターン

#### パターン1: editor/admin チェック
```blade
@if(auth()->user()->role === 'editor' || auth()->user()->role === 'admin')
    {{-- 編集・作成ボタン --}}
@endif
```

#### パターン2: in_array を使用
```blade
@if(in_array(auth()->user()->role, ['editor', 'admin']))
    {{-- 編集・作成ボタン --}}
@endif
```

#### パターン3: admin のみ
```blade
@if(auth()->user()->role === 'admin')
    {{-- 管理者専用機能 --}}
@endif
```

#### パターン4: 担当者 + editor/admin
```blade
@if(auth()->user()->role === 'editor' ||
    auth()->user()->role === 'admin' ||
    $performance->staff->contains('user_id', auth()->id()))
    {{-- 担当者または編集権限者 --}}
@endif
```

#### パターン5: viewer以外（general以上）
```blade
@if(auth()->user()->role !== 'viewer')
    {{-- viewer以外に表示 --}}
@endif
```

### 3. コントローラーでの権限チェック

```php
// ミドルウェアでのチェック（routes/web.php）
Route::middleware(['auth', 'role:admin'])->group(function () {
    // admin専用ルート
});

// コントローラー内でのチェック
public function store(Request $request)
{
    if (!in_array(auth()->user()->role, ['editor', 'admin'])) {
        abort(403, 'この操作を行う権限がありません。');
    }
    // 処理続行
}
```

### 4. 権限設定ページへの反映

新機能追加時は、`RolePermissionController.php` の `getPermissionMatrix()` メソッドに項目を追加してください。

```php
// app/Http/Controllers/Admin/RolePermissionController.php

['name' => '新機能 - 閲覧', 'general' => true, 'viewer' => true, 'editor' => true, 'admin' => true],
['name' => '新機能 - 編集', 'general' => false, 'viewer' => false, 'editor' => true, 'admin' => true],
```

## データベース定義

### users テーブル role カラム
```sql
`role` ENUM('viewer', 'general', 'editor', 'admin') NOT NULL DEFAULT 'general'
```

### User モデル
```php
// app/Models/User.php

public function getRoleLabelAttribute(): string
{
    return match($this->role) {
        'admin' => '管理者',
        'editor' => '編集者',
        'general' => '一般',
        'viewer' => '閲覧者',
        default => '不明',
    };
}
```

## 権限変更履歴

| 日付 | 変更内容 |
|------|----------|
| 2025-12-05 | CSV機能をadmin専用に変更（editorから権限削除） |
| 2025-12-05 | PDF出力を全ロールに開放 |
| 2025-12-05 | Role権限設計仕様書 初版作成 |

---
**[← README.md に戻る](../README.md)**
