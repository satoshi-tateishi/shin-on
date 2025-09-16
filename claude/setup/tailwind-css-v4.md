# 🎨 Tailwind CSS v4 移行ガイド

## 📋 概要

このドキュメントでは、shin-onプロジェクトでのTailwind CSS v4の実装と、v3からv4への移行について説明します。

## 🔄 v3からv4の主な変更点

### 設定方法の変更
| 項目 | v3 | v4 |
|------|----|----|
| 設定ファイル | `tailwind.config.js` | 不要（削除） |
| CSSインポート | `@tailwind base; @tailwind components; @tailwind utilities;` | `@import "tailwindcss";` |
| コンテンツ指定 | JavaScript設定内 | CSS内 `@source` ディレクティブ |
| カスタマイズ | JavaScript設定内 | CSS内 `@theme` ブロック |

### 新機能・改善点
- **パフォーマンス向上**: 最大5倍高速化
- **自動コンテンツ検出**: `@source`ディレクティブによる自動化
- **OKLCH色空間**: より正確な色表現
- **CSS-first設定**: すべてがCSS内で完結
- **ゼロ設定**: 設定ファイル不要

## 📁 現在の設定構成

### vite.config.js
```javascript
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
```

### resources/css/app.css
```css
@import "tailwindcss";

@source "../**/*.blade.php";
@source "../**/*.js";
@source "../**/*.vue";
@source "../../vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php";
@source "../../storage/framework/views/*.php";

@theme {
    --font-sans: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol', 'Noto Color Emoji';
}
```

### package.json（関連部分）
```json
{
  "devDependencies": {
    "tailwindcss": "4.0.0",
    "@tailwindcss/vite": "4.1.13",
    "@tailwindcss/postcss": "4.0.0"
  }
}
```

## 🚀 v3からv4への移行手順

### 1. 古い設定ファイル削除
```bash
# tailwind.config.js を削除
rm tailwind.config.js

# postcss.config.js も不要（Viteプラグイン使用時）
rm postcss.config.js  # 必要に応じて
```

### 2. CSSファイル更新
`resources/css/app.css` を以下に更新：

```css
@import "tailwindcss";

@source "../**/*.blade.php";
@source "../**/*.js";
@source "../**/*.vue";
@source "../../vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php";
@source "../../storage/framework/views/*.php";

@theme {
    --font-sans: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol', 'Noto Color Emoji';
    /* 追加のカスタマイズをここに記述 */
}
```

### 3. ビルド実行
```bash
# プロダクションビルド
./vendor/bin/sail npm run build

# 開発サーバー起動
./vendor/bin/sail npm run dev
```

## 🔍 動作確認方法

### 1. ビルド成功確認
```bash
./vendor/bin/sail npm run build
```
出力例：
```
✓ built in 9.13s
public/build/assets/app-BFY6tSOb.css  51.59 kB
```

### 2. 生成されたCSSの確認
```bash
head -1 public/build/assets/app-*.css
```
`tailwindcss v4.1.13` の記載があることを確認

### 3. ブラウザでの確認
- http://localhost:8081 にアクセス
- ブラウザの開発者ツールでスタイルが適用されていることを確認

## 🎯 @source ディレクティブの設定

### 基本的な指定
```css
@source "../**/*.blade.php";     /* Bladeテンプレート */
@source "../**/*.js";            /* JavaScriptファイル */
@source "../**/*.vue";           /* Vue.jsコンポーネント */
```

### Laravel固有の指定
```css
@source "../../vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php";
@source "../../storage/framework/views/*.php";
```

## 🛠️ @theme ブロックでのカスタマイズ

### フォント設定
```css
@theme {
    --font-sans: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
    --font-serif: 'Noto Serif JP', serif;
    --font-mono: 'JetBrains Mono', ui-monospace, monospace;
}
```

### カスタムカラー
```css
@theme {
    --color-brand-50: oklch(97.1% .013 17.38);
    --color-brand-500: oklch(63.7% .237 25.331);
    --color-brand-900: oklch(39.6% .141 25.723);
}
```

### カスタムブレークポイント
```css
@theme {
    --breakpoint-3xl: 1920px;
    --breakpoint-4xl: 2560px;
}
```

## 🚨 トラブルシューティング

### スタイルが適用されない場合

1. **ビルドキャッシュクリア**
```bash
./vendor/bin/sail npm run build
```

2. **@sourceパスの確認**
Bladeファイルのパスが正しく指定されているか確認

3. **ブラウザキャッシュクリア**
Ctrl+F5 または Cmd+Shift+R でハードリロード

### パフォーマンス問題

1. **@sourceの範囲を最適化**
```css
/* 悪い例: 範囲が広すぎる */
@source "../../**/*.php";

/* 良い例: 必要なファイルのみ */
@source "../**/*.blade.php";
```

## ✅ 検証項目チェックリスト

- [ ] `tailwind.config.js` が削除されている
- [ ] `resources/css/app.css` が v4 形式に更新されている
- [ ] ビルドが正常に完了する
- [ ] 生成されたCSSに `tailwindcss v4.x.x` の記載がある
- [ ] ブラウザでスタイルが正しく適用される
- [ ] 開発サーバーが正常に起動する

## 📚 参考リンク

- [Tailwind CSS v4.0 公式ブログ](https://tailwindcss.com/blog/tailwindcss-v4)
- [Tailwind CSS v4 ドキュメント](https://tailwindcss.com/docs)
- [Vite プラグイン設定](https://tailwindcss.com/docs/installation/vite)

---
**[← 環境設定に戻る](./environment.md)** | **[← README.md に戻る](../README.md)**