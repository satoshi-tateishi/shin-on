<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>在庫一覧</title>
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
            margin: 10mm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "IPAGothic", monospace;
            font-size: 10pt;
            line-height: 1.4;
            margin: 10mm;
        }

        /* テーブル */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10mm; /* フッター（ページ番号）との余白確保 */
        }

        thead {
            display: table-header-group; /* 各ページにヘッダー繰り返し */
        }

        /* ヘッダー行（倉庫名・出力日） */
        thead tr.header-row th {
            background-color: #fff;
            border: none;
            border-bottom: 2px solid #333;
            padding: 5px 4px;
            font-size: 14pt;
            font-weight: bold;
            text-align: left;
        }

        thead tr.info-row th {
            background-color: #fff;
            border: none;
            padding: 3px 4px 8px 4px;
            font-size: 9pt;
            font-weight: normal;
            text-align: left;
            color: #555;
        }

        /* カラムヘッダー行 */
        thead tr.column-header th {
            background-color: #f3f4f6;
            border: 1px solid #333;
            padding: 6px 4px;
            font-size: 9pt;
            font-weight: bold;
            text-align: left;
        }

        tbody td {
            border: 1px solid #ccc;
            padding: 5px 4px;
            font-size: 9pt;
            vertical-align: top;
        }

        tr {
            page-break-inside: avoid;
        }

        /* 列幅調整 */
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

        .col-company-number {
            width: 45%;
            word-wrap: break-word;
        }

        /* カテゴリ表示（縦並び） */
        .category-main {
            font-size: 8pt;
            color: #666;
            margin-bottom: 2px;
        }

        .category-sub {
            font-size: 8pt;
            color: #666;
        }

        /* 機材名 */
        .equipment-name {
            font-weight: bold;
            font-size: 9pt;
        }

        /* 在庫数量 */
        .quantity {
            font-size: 12pt;
            font-weight: bold;
            text-align: center;
        }

        /* 新音番号 */
        .company-number {
            font-size: 8pt;
            line-height: 1.5;
            word-wrap: break-word;
        }

        /* データなし */
        .no-data {
            text-align: center;
            padding: 30px;
            color: #999;
            font-size: 10pt;
        }

        /* 固定フッター（ページ番号表示用） */
        .page-footer {
            position: fixed;
            bottom: 10mm;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8pt;
            color: #666;
        }

        /* 倉庫ごとの改ページ */
        .location-section {
            page-break-after: always; /* 各倉庫セクション後に改ページ */
        }

        .location-section:last-child {
            page-break-after: auto; /* 最後の倉庫は改ページしない */
        }
    </style>
</head>
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

    <!-- 倉庫ごとの在庫テーブル -->
    @foreach($locationInventories as $index => $locationInventory)
        <div class="location-section">
            @if(count($locationInventory['inventoryData']) > 0)
                <table>
                    <thead>
                        <!-- ヘッダー行（倉庫名） -->
                        <tr class="header-row">
                            <th colspan="4">{{ $locationInventory['locationName'] }} 在庫一覧</th>
                        </tr>
                        <!-- 情報行（出力日時） -->
                        <tr class="info-row">
                            <th colspan="4">出力日時: {{ $asOfDate }}</th>
                        </tr>
                        <!-- カラムヘッダー行 -->
                        <tr class="column-header">
                            <th class="col-category">カテゴリ</th>
                            <th class="col-equipment">機材名</th>
                            <th class="col-quantity">在庫数量</th>
                            <th class="col-company-number">新音番号</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($locationInventory['inventoryData'] as $item)
                            <tr>
                                <!-- カテゴリ（縦並び） -->
                                <td class="col-category">
                                    <div class="category-main">{{ $item['equipment']['subcategory']['category']['name'] ?? '未設定' }}</div>
                                    <div class="category-sub">{{ $item['equipment']['subcategory']['name'] ?? '未設定' }}</div>
                                </td>

                                <!-- 機材名 -->
                                <td class="col-equipment">
                                    <div class="equipment-name">{{ $item['equipment']['name'] ?? '機材名不明' }}</div>
                                </td>

                                <!-- 在庫数量 -->
                                <td class="col-quantity">
                                    <div class="quantity">{{ $item['quantity'] ?? 0 }}</div>
                                </td>

                                <!-- 新音番号 -->
                                <td class="col-company-number">
                                    <div class="company-number">
                                        {{ ($item['quantity'] > 0) ? ($item['equipment']['company_number'] ?? '-') : '-' }}
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="no-data">
                    {{ $locationInventory['locationName'] }} に在庫データがありません
                </div>
            @endif
        </div>
    @endforeach
</body>
</html>
