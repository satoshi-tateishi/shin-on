# 📚 ドキュメント・API仕様書

## 📋 ドキュメント管理方針

### ドキュメント階層
1. **プロジェクトドキュメント** (claude/ディレクトリ)
2. **API仕様書** (自動生成 + 手動保守)
3. **ユーザーマニュアル** (必要に応じて作成)
4. **運用ドキュメント** (デプロイ・保守手順)

### 更新方針
- **機能追加時**: 必ずドキュメント更新
- **API変更時**: OpenAPI仕様の更新
- **定期レビュー**: 月次でドキュメント整合性確認

## 🔧 API仕様書生成

### Laravel API Documentation
Laravel 12では、APIドキュメント生成ツールが強化されています。

#### Scramble使用（推奨）
```bash
# Scrambleインストール
composer require dedoc/scramble

# 設定ファイル発行
php artisan vendor:publish --tag=scramble-config

# ドキュメント生成
php artisan scramble:generate
```

#### 設定例
```php
// config/scramble.php
return [
    'type' => 'openapi',
    'openapi_version' => '3.0.0',
    'info' => [
        'title' => 'shin-on API',
        'description' => 'shin-on プロジェクトのAPI仕様書',
        'version' => '1.0.0',
    ],
    'servers' => [
        [
            'url' => env('APP_URL').'/api/v1',
            'description' => 'Development server',
        ],
    ],
];
```

### API Resource Documentation
```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="UserResource",
 *     type="object",
 *     title="User Resource",
 *     description="User resource representation",
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         format="int64",
 *         description="User ID"
 *     ),
 *     @OA\Property(
 *         property="name",
 *         type="string",
 *         description="User name"
 *     ),
 *     @OA\Property(
 *         property="email",
 *         type="string",
 *         format="email",
 *         description="User email address"
 *     )
 * )
 */
class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
```

### API Controller Documentation
```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;

/**
 * @OA\Tag(
 *     name="Users",
 *     description="ユーザー管理API"
 * )
 */
class UserController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/users",
     *     operationId="getUsersList",
     *     tags={"Users"},
     *     summary="ユーザー一覧取得",
     *     description="登録されているユーザーの一覧を取得します",
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="ページ番号",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, default=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="1ページあたりの件数",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, maximum=100, default=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="ユーザー一覧",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/UserResource")
     *             ),
     *             @OA\Property(
     *                 property="links",
     *                 type="object",
     *                 description="ページネーション情報"
     *             ),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 description="メタ情報"
     *             )
     *         )
     *     ),
     *     security={{"bearerAuth": {}}}
     * )
     */
    public function index()
    {
        $users = User::paginate(15);
        return UserResource::collection($users);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/users",
     *     operationId="createUser",
     *     tags={"Users"},
     *     summary="ユーザー作成",
     *     description="新しいユーザーを作成します",
     *     @OA\RequestBody(
     *         required=true,
     *         description="ユーザー情報",
     *         @OA\JsonContent(
     *             required={"name","email","password"},
     *             @OA\Property(property="name", type="string", example="山田太郎"),
     *             @OA\Property(property="email", type="string", format="email", example="yamada@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="ユーザー作成成功",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="ユーザーを作成しました"),
     *             @OA\Property(property="data", ref="#/components/schemas/UserResource")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="バリデーションエラー",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function store(StoreUserRequest $request)
    {
        // 実装
    }
}
```

## 📖 README.md テンプレート

### プロジェクトREADME
````markdown
# 🚀 shin-on

Laravel 12 + Tailwind CSS v4 による新規Webアプリケーション

## 🔧 技術スタック

- **Backend**: Laravel 12 (PHP 8.4)
- **Database**: MySQL 8.0
- **Frontend**: Tailwind CSS v4, Vite
- **Development**: Docker (Laravel Sail)
- **AI Support**: Laravel-Boost MCP

## 🚀 Quick Start

### 必要要件
- Docker Desktop
- Git

### セットアップ
```bash
# リポジトリクローン
git clone [repository-url] shin-on
cd shin-on

# Docker環境起動
./vendor/bin/sail up -d

# 依存関係インストール
./vendor/bin/sail composer install
./vendor/bin/sail npm install

# アプリケーションキー生成
./vendor/bin/sail artisan key:generate

# データベースマイグレーション
./vendor/bin/sail artisan migrate

# フロントエンド開発サーバー起動
./vendor/bin/sail npm run dev
```

### アクセス
- **アプリケーション**: http://localhost:8081
- **Vite開発サーバー**: http://localhost:5174

## 📚 ドキュメント

詳細なドキュメントは [claude/README.md](claude/README.md) をご覧ください。

## 🧪 テスト実行

```bash
# 全テスト実行
./vendor/bin/sail artisan test

# カバレッジレポート生成
./vendor/bin/sail artisan test --coverage
```

## 📝 開発規約

- **コーディング規約**: PSR-12 (Laravel Pintで自動適用)
- **コミット規約**: Conventional Commits
- **ブランチ戦略**: Git Flow

## 🤝 コントリビュート

1. Fork the Project
2. Create your Feature Branch (`git checkout -b feature/AmazingFeature`)
3. Commit your Changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the Branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 📄 License

This project is licensed under the MIT License.
````

## 🔄 変更履歴管理

### CHANGELOG.md テンプレート
```markdown
# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- 新機能の説明

### Changed
- 既存機能の変更

### Deprecated
- 非推奨になった機能

### Removed
- 削除された機能

### Fixed
- バグ修正

### Security
- セキュリティ関連の修正

## [1.0.0] - 2025-01-XX

### Added
- 初期リリース
- Laravel 12 + MySQL 8.0環境
- Tailwind CSS v4統合
- Laravel-Boost MCP導入
- 基本的なユーザー管理機能
```

## 📊 コードドキュメント

### PHPDoc標準
```php
<?php

namespace App\Services;

/**
 * ユーザー関連のビジネスロジックを処理するサービスクラス
 *
 * @package App\Services
 * @author Your Name <your.email@example.com>
 * @version 1.0.0
 * @since 1.0.0
 */
class UserService
{
    /**
     * 新しいユーザーを作成する
     *
     * @param array $data ユーザー作成用データ
     * @param array $data.name ユーザー名
     * @param array $data.email メールアドレス
     * @param array $data.password パスワード
     *
     * @return \App\Models\User 作成されたユーザーインスタンス
     *
     * @throws \InvalidArgumentException 不正なデータが渡された場合
     * @throws \Illuminate\Database\QueryException データベースエラーが発生した場合
     *
     * @example
     * ```php
     * $user = $userService->createUser([
     *     'name' => '山田太郎',
     *     'email' => 'yamada@example.com',
     *     'password' => 'password123'
     * ]);
     * ```
     */
    public function createUser(array $data): User
    {
        // 実装
    }
}
```

## 🔧 ドキュメント生成自動化

### GitHub Actions設定例
```yaml
# .github/workflows/docs.yml
name: Generate Documentation

on:
  push:
    branches: [ main, develop ]
  pull_request:
    branches: [ main ]

jobs:
  generate-docs:
    runs-on: ubuntu-latest

    steps:
    - uses: actions/checkout@v3

    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.4'
        extensions: mbstring, xml, ctype, iconv, intl, pdo, pdo_mysql, dom, filter, gd, iconv, json, mbstring

    - name: Install dependencies
      run: composer install --prefer-dist --no-progress --no-suggest

    - name: Generate API Documentation
      run: php artisan scramble:generate

    - name: Deploy to GitHub Pages
      uses: peaceiris/actions-gh-pages@v3
      if: github.ref == 'refs/heads/main'
      with:
        github_token: ${{ secrets.GITHUB_TOKEN }}
        publish_dir: ./public/docs
```

## 📝 ドキュメント更新チェックリスト

### 機能追加時
- [ ] API仕様書更新（OpenAPI/Swagger）
- [ ] README.mdの機能説明更新
- [ ] コードコメント・PHPDoc記述
- [ ] テストケースドキュメント作成

### API変更時
- [ ] バージョン番号更新
- [ ] 破壊的変更の明記
- [ ] マイグレーションガイド作成
- [ ] 非推奨機能の警告追加

### リリース時
- [ ] CHANGELOG.md更新
- [ ] バージョンタグ作成
- [ ] リリースノート作成
- [ ] ドキュメントデプロイ確認

## 📋 API仕様書リンク

### 実装済みAPI仕様
- **[../../docs/Dropbox_API_Specification.md](../../docs/Dropbox_API_Specification.md)** - Dropbox OAuth 2.0 & バックアップAPI仕様
- **[../../docs/LINE_WORKS_SSO_Specification.md](../../docs/LINE_WORKS_SSO_Specification.md)** - LINE WORKS SSO認証仕様

これらの仕様書には、認証フロー、エンドポイント、セキュリティ実装、エラーハンドリングの詳細が記載されています。

---
**[← README.md に戻る](../README.md)**