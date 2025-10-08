# Dompdf 日本語フォント対応ガイド

## 📋 概要

Laravel + Dompdf (`barryvdh/laravel-dompdf`) で日本語を含むPDFを生成する際、デフォルトでは日本語が「?」や空白で表示されます。このドキュメントでは、IPAフォントを使用した日本語表示の実装方法を解説します。

## 🔧 技術スタック

- **PDF生成ライブラリ**: `barryvdh/laravel-dompdf` v3.1+
- **日本語フォント**: IPA Gothic (ipag.ttf, ipam.ttf)
- **フォント配置場所**: `storage/fonts/`

## 📂 フォントファイル構成

```
storage/fonts/
├── ipag.ttf           # IPA Gothic 通常フォント
├── ipam.ttf           # IPA Mincho 太字フォント
└── installed-fonts.json
```

### フォント入手方法

IPA フォントは以下から入手可能です:
- **公式サイト**: https://moji.or.jp/ipafont/
- **ダウンロード**: IPAfont00303.zip をダウンロードし、TTFファイルを `storage/fonts/` に配置

## 🎨 実装方法

### 1. Bladeテンプレート（PDFビュー）の設定

PDFビューのCSSセクションに以下を追加します:

```html
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>PDFタイトル</title>
    <style>
        /* 日本語フォント定義 */
        @font-face {
            font-family: "IPAGothic";
            font-style: normal;
            font-weight: normal;
            src: url('{{ storage_path('fonts/ipag.ttf') }}') format('truetype');
            font-feature-settings: 'palt' 0; /* プロポーショナル機能を無効化 */
        }
        @font-face {
            font-family: "IPAGothic";
            font-style: normal;
            font-weight: bold;
            src: url('{{ storage_path('fonts/ipam.ttf') }}') format('truetype');
            font-feature-settings: 'palt' 0;
        }

        /* ページ設定 */
        @page {
            margin: 15mm 10mm;
        }

        /* ボディに日本語フォントを適用 */
        body {
            font-family: "IPAGothic", monospace;
            font-size: 12px;
            line-height: 1.4;
        }

        /* ページ分割設定 */
        thead {
            display: table-header-group; /* 各ページにヘッダー繰り返し */
        }

        tr {
            page-break-inside: avoid; /* 行の途中で改ページしない */
        }
    </style>
</head>
<body>
    <!-- PDFコンテンツ -->
</body>
</html>
```

### 2. コントローラーでのPDF生成設定

```php
use Barryvdh\DomPDF\Facade\Pdf;

public function exportPdf(Request $request)
{
    // データ準備
    $data = [
        'title' => '在庫一覧',
        'date' => now()->format('Y年m月d日'),
        // その他のデータ...
    ];

    // PDF生成
    $pdf = Pdf::loadView('your-view.pdf', $data);

    // DOMPDFオプション設定（重要）
    $pdf->getDomPDF()->set_option('isHtml5ParserEnabled', true);
    $pdf->getDomPDF()->set_option('isFontSubsettingEnabled', true);

    // 用紙サイズ設定
    $pdf->setPaper('A4', 'portrait'); // または 'landscape'

    // ファイル名生成
    $filename = '在庫一覧_' . now()->format('Ymd') . '.pdf';

    // ダウンロード
    return $pdf->download($filename);

    // またはブラウザ表示（ストリーム）
    // return $pdf->stream($filename);
}
```

## 🔑 重要ポイント

### 1. `@font-face` の必須項目

- **`src: url()`**: `storage_path('fonts/ipag.ttf')` で絶対パスを指定
- **`format('truetype')`**: TTFフォントであることを明示
- **`font-feature-settings: 'palt' 0`**: プロポーショナル機能を無効化（等幅表示）

### 2. DOMPDFオプション

```php
// HTML5パーサー有効化（日本語の正しい解析）
$pdf->getDomPDF()->set_option('isHtml5ParserEnabled', true);

// フォントサブセッティング有効化（ファイルサイズ最適化）
$pdf->getDomPDF()->set_option('isFontSubsettingEnabled', true);
```

### 3. 文字エンコーディング

```html
<meta charset="UTF-8">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
```

この2つのメタタグを必ず含めます。

## 📄 実装例

### 在庫一覧PDFの例

**ビュー**: `resources/views/inventory/pdf.blade.php`

```html
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>在庫一覧 - {{ $locationName }}</title>
    <style>
        @font-face {
            font-family: "IPAGothic";
            font-style: normal;
            font-weight: normal;
            src: url('{{ storage_path('fonts/ipag.ttf') }}') format('truetype');
            font-feature-settings: 'palt' 0;
        }
        @font-face {
            font-family: "IPAGothic";
            font-style: normal;
            font-weight: bold;
            src: url('{{ storage_path('fonts/ipam.ttf') }}') format('truetype');
            font-feature-settings: 'palt' 0;
        }

        @page {
            margin: 15mm 10mm;
        }

        body {
            font-family: "IPAGothic", monospace;
            font-size: 10pt;
            line-height: 1.4;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            display: table-header-group;
        }

        th {
            background-color: #f3f4f6;
            border: 1px solid #333;
            padding: 6px 4px;
            font-weight: bold;
        }

        td {
            border: 1px solid #ccc;
            padding: 5px 4px;
        }

        tr {
            page-break-inside: avoid;
        }
    </style>
</head>
<body>
    <h1>{{ $locationName }} 在庫一覧</h1>
    <p>出力日: {{ $asOfDate }}</p>

    <table>
        <thead>
            <tr>
                <th>カテゴリ</th>
                <th>機材名</th>
                <th>在庫数量</th>
            </tr>
        </thead>
        <tbody>
            @foreach($inventoryData as $item)
                <tr>
                    <td>{{ $item['category'] }}</td>
                    <td>{{ $item['name'] }}</td>
                    <td>{{ $item['quantity'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
```

**コントローラー**: `app/Http/Controllers/InventoryController.php`

```php
public function exportPdf(Request $request)
{
    $validated = $request->validate([
        'as_of_date' => 'required|date',
        'location_id' => 'required|exists:locations,id',
    ]);

    // データ取得
    $location = Location::findOrFail($validated['location_id']);
    $inventoryData = $this->getInventoryData($validated);

    $data = [
        'locationName' => $location->name,
        'asOfDate' => Carbon::parse($validated['as_of_date'])->format('Y年m月d日'),
        'inventoryData' => $inventoryData,
    ];

    // PDF生成
    $pdf = Pdf::loadView('inventory.pdf', $data);
    $pdf->getDomPDF()->set_option('isHtml5ParserEnabled', true);
    $pdf->getDomPDF()->set_option('isFontSubsettingEnabled', true);
    $pdf->getDomPDF()->set_option('isPhpEnabled', true); // PHPスクリプト有効化
    $pdf->setPaper('A4', 'portrait');

    $filename = sprintf(
        '在庫一覧_%s_%s.pdf',
        $location->name,
        Carbon::parse($validated['as_of_date'])->format('Ymd')
    );

    return $pdf->download($filename);
}
```

## 🔍 トラブルシューティング

### 問題: 日本語が「?」で表示される

**原因**:
- フォントファイルが見つからない
- `@font-face` の設定が不足
- DOMPDFオプションが未設定

**解決方法**:
1. `storage/fonts/ipag.ttf` が存在することを確認
2. `@font-face` でフォントパスが正しいか確認
3. `isHtml5ParserEnabled` を `true` に設定

### 問題: PDFファイルサイズが大きい

**原因**:
- フォントサブセッティングが無効

**解決方法**:
```php
$pdf->getDomPDF()->set_option('isFontSubsettingEnabled', true);
```

### 問題: ページ分割が正しく動作しない

**原因**:
- CSS設定が不足

**解決方法**:
```css
thead {
    display: table-header-group; /* ヘッダー繰り返し */
}

tr {
    page-break-inside: avoid; /* 行の途中で改ページしない */
}
```

### 問題: ページマージンが適用されない

**原因**:
- `@page`のマージン設定がDompdfで正しく反映されない

**解決方法**:
`@page`と`body`の両方にマージンを設定:
```css
@page {
    margin: 10mm;
}

body {
    margin: 10mm; /* bodyにも同じマージンを設定 */
}
```

### 問題: テーブルとページ番号が重なる

**原因**:
- フッター用の余白が確保されていない

**解決方法**:
テーブルに下部マージンを追加:
```css
table {
    margin-bottom: 10mm; /* フッター（ページ番号）との余白確保 */
}
```

## 📊 ページ番号の表示

### 推奨: `page_text()`を使用したフッター表示

**重要**: `<thead>`内で`<script type="text/php">`は動作しないため、ページ番号はフッター部分に表示します。

#### 1. コントローラーでPHPスクリプトを有効化

```php
$pdf->getDomPDF()->set_option('isPhpEnabled', true); // PHPスクリプト有効化
```

#### 2. ビューにフッター用スクリプトを追加

```html
<body>
    <!-- 固定フッター（ページ番号） -->
    <div class="page-footer">
        <script type="text/php">
            if (isset($pdf)) {
                $font = $fontMetrics->getFont("IPAGothic");
                $pdf->page_text(297, 820, "ページ {PAGE_NUM}", $font, 8, array(0.4, 0.4, 0.4));
            }
        </script>
    </div>

    <!-- コンテンツ -->
    <table>...</table>
</body>
```

#### 3. CSSで固定フッタースタイルを定義

```css
.page-footer {
    position: fixed;
    bottom: 10mm;
    left: 0;
    right: 0;
    text-align: center;
    font-size: 8pt;
    color: #666;
}
```

#### 4. テーブルに下部マージンを追加（重要）

ページ番号とテーブルの重なりを防ぐため:

```css
table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 10mm; /* フッター（ページ番号）との余白確保 */
}
```

### `page_text()`のパラメータ

```php
$pdf->page_text(x, y, text, font, size, color);
```

- **x**: X座標（ポイント単位。A4幅210mm ≈ 595pt。中央は約297pt）
- **y**: Y座標（ポイント単位。A4高さ297mm ≈ 842pt。下部は約820pt）
- **text**: 表示テキスト（`{PAGE_NUM}`は現在のページ番号に自動置換）
- **font**: フォントオブジェクト
- **size**: フォントサイズ（ポイント）
- **color**: RGB配列 `array(R, G, B)` 各値は0.0〜1.0

### 総ページ数を含む表示（高度）

総ページ数の取得は Dompdf では困難です。代替案:
1. ページ番号のみ表示（推奨）
2. 2パス処理（1回目でページ数計算、2回目で描画）

## 🎨 レイアウト調整のコツ

### ページマージンの設定

**重要**: Dompdfでは`@page`のマージン設定が正しく反映されない場合があります。確実にマージンを適用するには、**`@page`と`body`の両方にマージンを設定**します。

```css
@page {
    margin: 10mm; /* ページ全体のマージン */
}

body {
    font-family: "IPAGothic", monospace;
    font-size: 10pt;
    margin: 10mm; /* bodyにもマージンを設定（重要） */
}
```

**推奨設定**:
- 上下左右すべて同じ余白: `margin: 10mm;`
- 個別指定: `margin: 15mm 10mm;` (上下15mm、左右10mm)
- 4辺個別: `margin: 10mm 15mm 20mm 15mm;` (上、右、下、左)

### A4サイズに最適化

```css
body {
    font-size: 10pt; /* A4に適したサイズ */
}
```

### テーブルの列幅制御

```css
.col-category {
    width: 20%;
}

.col-equipment {
    width: 25%;
}

.col-quantity {
    width: 10%;
    text-align: center;
}
```

### 長い文字列の折り返し

```css
.company-number {
    word-wrap: break-word;
    word-break: break-all;
}
```

## 🔄 各ページにヘッダーを繰り返し表示する方法

Dompdfで各ページにヘッダー（タイトル、日付など）を繰り返し表示するには、**`<thead>`を活用**します。

### 実装方法

```html
<table>
    <thead>
        <!-- ヘッダー行（タイトル） -->
        <tr class="header-row">
            <th colspan="4">{{ $locationName }} 在庫一覧</th>
        </tr>
        <!-- 情報行（出力日） -->
        <tr class="info-row">
            <th colspan="4">出力日: {{ $asOfDate }}</th>
        </tr>
        <!-- カラムヘッダー行 -->
        <tr class="column-header">
            <th>カテゴリ</th>
            <th>機材名</th>
            <th>在庫数量</th>
            <th>新音番号</th>
        </tr>
    </thead>
    <tbody>
        <!-- データ行 -->
    </tbody>
</table>
```

### CSS設定

```css
thead {
    display: table-header-group; /* 各ページにヘッダー繰り返し */
}

/* ヘッダー行のスタイル */
thead tr.header-row th {
    background-color: #fff;
    border: none;
    border-bottom: 2px solid #333;
    padding: 5px 4px;
    font-size: 14pt;
    font-weight: bold;
}

thead tr.info-row th {
    background-color: #fff;
    border: none;
    padding: 3px 4px 8px 4px;
    font-size: 9pt;
    color: #555;
}

thead tr.column-header th {
    background-color: #f3f4f6;
    border: 1px solid #333;
    padding: 6px 4px;
    font-size: 9pt;
    font-weight: bold;
}
```

### 重要ポイント

- ✅ `thead { display: table-header-group; }` により、`<thead>`内の全ての行が各ページに自動的に繰り返し表示される
- ✅ タイトル、出力日、カラムヘッダーをすべて`<thead>`内に配置
- ✅ `colspan`を使用して複数列にまたがるヘッダーを作成
- ⚠️ `<thead>`内では`<script type="text/php">`は動作しないため、ページ番号はフッター部分に配置

## ✅ チェックリスト

新しいPDFビューを作成する際のチェックリスト:

### フォント設定
- [ ] `@font-face` で IPAGothic フォントを定義
- [ ] `body` に `font-family: "IPAGothic"` を設定
- [ ] `<meta charset="UTF-8">` を含める

### コントローラー設定
- [ ] `isHtml5ParserEnabled` を有効化
- [ ] `isFontSubsettingEnabled` を有効化
- [ ] `isPhpEnabled` を有効化（ページ番号表示時）

### レイアウト設定
- [ ] `@page` でマージン設定
- [ ] `thead { display: table-header-group; }` を設定（ヘッダー繰り返し）
- [ ] `tr { page-break-inside: avoid; }` を設定（行の途中改ページ防止）
- [ ] `table { margin-bottom: 10mm; }` を設定（フッターとの余白確保）

### ページ番号設定（オプション）
- [ ] `page_text()` を使用してフッターにページ番号を表示
- [ ] `.page-footer` のCSSスタイルを定義

## 📚 参考リソース

- **Dompdf公式ドキュメント**: https://github.com/dompdf/dompdf
- **barryvdh/laravel-dompdf**: https://github.com/barryvdh/laravel-dompdf
- **IPAフォント**: https://moji.or.jp/ipafont/

## 🔗 関連実装ファイル

このプロジェクトでの実装例:

1. **在庫一覧PDF**
   - ビュー: `resources/views/inventory/pdf.blade.php`
   - コントローラー: `app/Http/Controllers/InventoryController.php::exportPdf()`

2. **修理伝票PDF**
   - ビュー: `resources/views/repair-records/pdf/slip.blade.php`
   - コントローラー: `app/Http/Controllers/RepairRecordController.php::exportPdf()`

---

**💡 このガイドに従うことで、日本語を含むPDFが正しく生成できます。**
