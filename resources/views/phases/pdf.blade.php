<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>使用機材リスト - {{ $performance->title }} {{ $phase->name }}</title>
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

        /* ヘッダー部分 */
        .page-header {
            margin-bottom: 10px;
            border-bottom: 2px solid #333;
            padding-bottom: 8px;
        }

        .page-header-row {
            display: table;
            width: 100%;
        }

        .page-header-left {
            display: table-cell;
            width: 70%;
            vertical-align: middle;
        }

        .page-header-right {
            display: table-cell;
            width: 30%;
            text-align: right;
            vertical-align: middle;
        }

        .page-header-right img {
            max-height: 40px;
            vertical-align: middle;
        }

        .page-title {
            font-size: 14pt;
            font-weight: bold;
            margin-bottom: 3px;
        }

        .page-subtitle {
            font-size: 9pt;
            color: #555;
        }

        /* フェーズ基本情報セクション・担当者セクション横並び */
        .info-staff-container {
            display: table;
            width: 100%;
            margin-bottom: 10px;
        }

        .info-section {
            display: table-cell;
            width: 60%;
            vertical-align: top;
            padding-right: 5mm;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5mm;
        }

        .info-table thead th {
            background-color: #f3f4f6;
            border: 1px solid #333;
            padding: 4px;
            font-size: 9pt;
            font-weight: bold;
            text-align: center;
            vertical-align: middle;
            height: 16px; /* ヘッダー高さを統一 */
        }

        .info-table tbody td {
            border: 1px solid #ccc;
            padding: 4px;
            font-size: 9pt;
            vertical-align: middle;
        }

        .info-table tbody td.info-label {
            width: 30%;
            background-color: #f9f9f9;
            font-weight: bold;
            color: #555;
            text-align: right;
        }

        .info-table tbody td.info-value {
            width: 70%;
        }

        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 8pt;
            font-weight: bold;
        }

        .status-upcoming {
            background-color: #e5e7eb;
            color: #374151;
        }

        .status-in-progress {
            background-color: #dbeafe;
            color: #1e40af;
        }

        .status-completed {
            background-color: #d1fae5;
            color: #065f46;
        }

        .active-badge {
            background-color: #d1fae5;
            color: #065f46;
        }

        .inactive-badge {
            background-color: #fee2e2;
            color: #991b1b;
        }

        /* 使用機材テーブル・担当者テーブル */
        .equipment-section {
            margin-top: 15px;
        }

        .equipment-section h2 {
            font-size: 11pt;
            font-weight: bold;
            margin-bottom: 6px;
            border-bottom: 1px solid #999;
            padding-bottom: 3px;
        }

        .staff-section {
            display: table-cell;
            width: 40%;
            vertical-align: top;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10mm;
        }

        .staff-table {
            margin-bottom: 5mm;
        }

        .staff-table tbody td {
            padding: 4px;
            font-size: 9pt;
            vertical-align: middle;
            text-align: left;
        }

        .staff-table thead th {
            text-align: center;
        }

        thead {
            display: table-header-group; /* 各ページでテーブルヘッダーを繰り返し表示 */
        }

        tfoot {
            display: table-footer-group; /* フッターも繰り返し表示（必要に応じて） */
        }

        thead th {
            background-color: #f3f4f6;
            border: 1px solid #333;
            padding: 4px;
            font-size: 9pt;
            font-weight: bold;
            text-align: center;
            vertical-align: middle;
            height: 16px; /* ヘッダー高さを統一 */
        }

        tbody td {
            border: 1px solid #ccc;
            padding: 5px 4px;
            font-size: 9pt;
            vertical-align: middle;
        }

        tr {
            page-break-inside: avoid; /* 行の途中で改ページしない */
        }

        /* 列幅調整 */
        .col-category {
            width: 20%;
            vertical-align: middle;
        }

        .col-equipment {
            width: 30%;
            vertical-align: middle;
        }

        .col-quantity {
            width: 10%;
            text-align: center;
            vertical-align: middle;
        }

        .col-company-number {
            width: 40%;
            word-wrap: break-word;
            vertical-align: middle;
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
        .equipment-manufacturer {
            font-size: 7pt;
            color: #999;
            margin-bottom: 2px;
        }

        .equipment-name {
            font-weight: bold;
            font-size: 9pt;
        }

        /* 数量 */
        .quantity {
            font-size: 11pt;
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

    <!-- ページヘッダー -->
    <div class="page-header">
        <div class="page-header-row">
            <div class="page-header-left">
                <div class="page-title">使用機材リスト: {{ $performance->title }} 【{{ $phase->name }}】</div>
                <div class="page-subtitle">出力日時: {{ $exportDate }}</div>
            </div>
            <div class="page-header-right">
                @if($logoPath && file_exists($logoPath))
                    <img src="{{ $logoPath }}" alt="Company Logo">
                @else
                    office shin•on
                @endif
            </div>
        </div>
    </div>

    <!-- 基本情報と担当者を横並びで表示 -->
    <div class="info-staff-container">
        <!-- フェーズ基本情報テーブル -->
        <div class="info-section">
            <table class="info-table">
                <thead>
                    <tr>
                        <th colspan="2">基本情報</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- 期間 -->
                    <tr>
                        <td class="info-label">期間</td>
                        <td class="info-value">
                            @if($phase->start_date && $phase->end_date)
                                {{ $phase->start_date->format('Y年m月d日') }} 〜 {{ $phase->end_date->format('Y年m月d日') }}
                                （{{ $phase->duration_days }}日間）
                            @elseif($phase->start_date)
                                開始: {{ $phase->start_date->format('Y年m月d日') }} （終了日未設定）
                            @elseif($phase->end_date)
                                終了: {{ $phase->end_date->format('Y年m月d日') }} （開始日未設定）
                            @else
                                期間未設定
                            @endif
                        </td>
                    </tr>

                    <!-- 使用場所 -->
                    <tr>
                        <td class="info-label">使用場所</td>
                        <td class="info-value">
                            @if($phase->location)
                                {{ $phase->location->name }}
                            @else
                                未設定
                            @endif
                        </td>
                    </tr>

                    <!-- 備考 -->
                    @if($phase->note)
                    <tr>
                        <td class="info-label">備考</td>
                        <td class="info-value">{{ $phase->note }}</td>
                    </tr>
                    @endif

                    <!-- 演出 -->
                    <tr>
                        <td class="info-label">演出</td>
                        <td class="info-value">
                            @if($performance->director)
                                {{ $performance->director }}
                            @else
                                未設定
                            @endif
                        </td>
                    </tr>

                    <!-- プロダクション名 -->
                    <tr>
                        <td class="info-label">プロダクション</td>
                        <td class="info-value">
                            @if($performance->productions->count() > 0)
                                {{ $performance->productions->pluck('name')->implode(', ') }}
                            @else
                                未設定
                            @endif
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- 担当者テーブル -->
        <div class="staff-section">
            <table class="staff-table">
                <thead>
                    <tr>
                        <th style="width: 50%;">ポジション</th>
                        <th style="width: 50%;">氏名</th>
                    </tr>
                </thead>
                <tbody>
                    @if($performance->staff->count() > 0)
                        @foreach($performance->staff as $staff)
                        <tr>
                            <td>{{ $staff->position->name }}</td>
                            <td>{{ $staff->user->name }}</td>
                        </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="2" style="text-align: center; color: #999;">担当者未設定</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    <!-- 使用機材一覧 -->
    <div class="equipment-section">
        <h2>使用機材一覧</h2>

        @if($groupedEquipments->count() > 0)
            <table>
                <thead>
                    <tr>
                        <th class="col-category">カテゴリ</th>
                        <th class="col-equipment">機材名</th>
                        <th class="col-quantity">数量</th>
                        <th class="col-company-number">新音番号</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($groupedEquipments as $groupedEquipment)
                        <tr>
                            <!-- カテゴリ（縦並び） -->
                            <td class="col-category">
                                <div class="category-main">{{ $groupedEquipment->equipment->subcategory->category->name }}</div>
                                <div class="category-sub">{{ $groupedEquipment->equipment->subcategory->name }}</div>
                            </td>

                            <!-- 機材名 -->
                            <td class="col-equipment">
                                @if($groupedEquipment->equipment->manufacturer)
                                    <div class="equipment-manufacturer">{{ $groupedEquipment->equipment->manufacturer }}</div>
                                @endif
                                <div class="equipment-name">{{ $groupedEquipment->equipment->name }}</div>
                            </td>

                            <!-- 数量 -->
                            <td class="col-quantity">
                                <div class="quantity">{{ $groupedEquipment->total_quantity }}</div>
                            </td>

                            <!-- 新音番号 -->
                            <td class="col-company-number">
                                <div class="company-number">
                                    {{ $groupedEquipment->company_numbers ?: '-' }}
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="no-data">
                使用機材が登録されていません
            </div>
        @endif
    </div>
</body>
</html>
