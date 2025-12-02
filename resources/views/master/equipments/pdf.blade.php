<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>機材マスタ一覧</title>
    <style>
        @font-face {
            font-family: "IPAGothic";
            font-style: normal;
            font-weight: normal;
            src: url('{{ storage_path('fonts/ipag.ttf') }}') format('truetype');
        }

        @page { margin: 10mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: "IPAGothic", sans-serif; font-size: 10pt; line-height: 1.4; margin: 5mm 10mm; }

        .page-header { margin-bottom: 10px; border-bottom: 2px solid #333; padding-bottom: 8px; }
        .page-header-row { display: table; width: 100%; }
        .page-header-left { display: table-cell; width: 70%; vertical-align: middle; }
        .page-header-right { display: table-cell; width: 30%; text-align: right; vertical-align: middle; }
        .page-header-right img { max-height: 40px; vertical-align: middle; }
        .page-title { font-size: 14pt; font-weight: bold; }
        .page-subtitle { font-size: 9pt; color: #555; }

        .filter-info { margin-bottom: 10px; padding: 5px; background: #f5f5f5; font-size: 9pt; }
        .stats-info { margin-bottom: 15px; font-size: 10pt; }
        .total-count { font-weight: bold; }

        .category-section { margin-bottom: 15px; }
        .category-header { font-size: 11pt; font-weight: bold; margin-bottom: 6px; padding: 5px 8px; background: #e5e7eb; border-left: 4px solid #374151; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th { background: #f3f4f6; border: 1px solid #333; padding: 1px 8px; font-size: 8pt; font-weight: bold; text-align: center; }
        td { border: 1px solid #ccc; padding: 1px 8px; font-size: 8pt; }
        tr { page-break-inside: avoid; }

        .col-subcategory { width: 15%; font-size: 6pt; color: #666; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 20mm; }
        .col-manufacturer { width: 20mm; font-size: 8pt; }
        .col-equipment { width: 55mm; }
        .col-company-number { width: 55mm; }
        .col-quantity { width: 12mm; text-align: center; }

        .equipment-name { font-weight: bold; font-size: 9pt; }
        .quantity { font-size: 11pt; font-weight: bold; text-align: center; }

        .no-data { text-align: center; padding: 30px; color: #999; }

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
    </style>
</head>
<body>
    <!-- 固定フッター（ページ番号） -->
    <div class="page-footer">
        <script type="text/php">
            if (isset($pdf)) {
                $font = $fontMetrics->getFont("IPAGothic");
                $text = "ページ {PAGE_NUM}";
                $pageWidth = $pdf->get_width();
                $textWidth = $fontMetrics->getTextWidth($text, $font, 8);
                $x = ($pageWidth - $textWidth) / 2;
                $pdf->page_text($x, 820, $text, $font, 8, array(0.4, 0.4, 0.4));
            }
        </script>
    </div>

    <div class="page-header">
        <div class="page-header-row">
            <div class="page-header-left">
                <div class="page-title">機材マスタ一覧</div>
                <div class="page-subtitle">出力日時: {{ $exportDate }}</div>
            </div>
            <div class="page-header-right">
                @if($logoPath && file_exists($logoPath))
                    <img src="{{ $logoPath }}" alt="Company Logo">
                @endif
            </div>
        </div>
    </div>

    @if(count($filterInfo) > 0)
        <div class="filter-info">
            @foreach($filterInfo as $label => $value)
                <span>{{ $label }}: <strong>{{ $value }}</strong></span>
            @endforeach
        </div>
    @endif

    <div class="stats-info">
        合計: <span class="total-count">{{ $totalCount }}</span> 件
    </div>

    @if($groupedEquipments->count() > 0)
        @foreach($groupedEquipments as $categoryName => $equipments)
            <div class="category-section">
                <div class="category-header">{{ $categoryName }} ({{ $equipments->count() }}件)</div>
                <table>
                    <thead>
                        <tr>
                            <th class="col-subcategory">サブカテゴリ</th>
                            <th class="col-manufacturer">メーカー</th>
                            <th class="col-equipment">機材名</th>
                            <th class="col-company-number">新音番号</th>
                            <th class="col-quantity">数量</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($equipments as $equipment)
                            <tr>
                                <td class="col-subcategory">{{ $equipment->subcategory->name ?? '-' }}</td>
                                <td class="col-manufacturer">{{ $equipment->manufacturer ?: '-' }}</td>
                                <td class="col-equipment"><div class="equipment-name">{{ $equipment->name }}</div></td>
                                <td class="col-company-number">{{ $equipment->company_numbers ?: '-' }}</td>
                                <td class="col-quantity"><div class="quantity">{{ $equipment->total_quantity }}</div></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach
    @else
        <div class="no-data">機材データがありません</div>
    @endif
</body>
</html>
