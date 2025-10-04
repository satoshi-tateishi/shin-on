<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>修理伝票</title>
    <style>
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
            font-feature-settings: 'palt' 0; /* プロポーショナル機能を無効化 */
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: "IPAGothic", monospace;
            font-size: 12px;
            line-height: 1.4;
            padding: 25px 40px 80px 40px;
            color: #000;
            position: relative;
            min-height: 297mm;
            box-sizing: border-box;
            font-variant-numeric: tabular-nums; /* 数字を等幅に */
            letter-spacing: 0; /* 文字間隔を0に */
        }

        .document-title {
            font-family: "IPAGothic", sans-serif;
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 10px;
            padding: 8px;
            border: 2px solid #000;
            background-color: #f0f0f0;
        }
        .slip-info {
            text-align: right;
            font-size: 11px;
            margin-bottom: 10px;
        }
        .main-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .main-table th,
        .main-table td {
            border: 1px solid #333;
            padding: 3px 10px;
            vertical-align: top;
            line-height: 1.2;
        }
        .main-table th {
            background-color: #e8e8e8;
            font-weight: bold;
            text-align: right;
            width: 100px;
            font-size: 13px;
        }
        .column-left .main-table {
            width: 100%;
            table-layout: auto;
        }
        .column-left .main-table th {
            width: 30% !important;
            max-width: 80px !important;
            white-space: nowrap;
        }
        .column-left .main-table td:not(.section-header) {
            width: 70% !important;
        }
        .two-column-container {
            display: table;
            width: 100%;
            margin-bottom: 10px;
        }
        .column-left,
        .column-right {
            display: table-cell;
            width: 49%;
            vertical-align: top;
        }
        .column-spacer {
            display: table-cell;
            width: 2%;
        }
        .main-table td {
            font-size: 14px;
        }
        .section-header {
            background-color: #d0d0d0;
            font-weight: bold;
            text-align: center;
            font-size: 13px;
            padding: 3px 10px !important;
            line-height: 1.2;
        }
        .problem-section {
            margin: 15px 0;
        }
        .problem-title {
            font-family: "IPAGothic", sans-serif;
            background-color: #d0d0d0;
            padding: 3px 10px;
            font-weight: bold;
            text-align: center;
            border: 1px solid #333;
            margin-bottom: 0px;
            font-size: 13px;
            line-height: 1.2;
        }
        .problem-content {
            border: 1px solid #333;
            border-top: none;
            padding: 3px 10px;
            min-height: 80px;
            font-size: 14px;
            line-height: 1.2;
            white-space: pre-wrap;
        }
        .problem-content.note {
            height: 190px;
            overflow: hidden;
        }
        .company-footer {
            position: absolute;
            bottom: 20px;
            left: 40px;
            right: 40px;
            padding-top: 8px;
            border-top: 2px solid #333;
            text-align: center;
        }
        .company-name {
            font-family: "IPAGothic", sans-serif;
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 3px;
        }
        .company-details {
            font-family: "IPAGothic", sans-serif;
            font-size: 12px;
            color: #333;
            line-height: 1.4;
        }
        .photo-section {
            margin-top: 10px;
            margin-bottom: 15px;
        }
        .photo-container {
            text-align: center;
            padding: 0 10px;
            border: 1px solid #333;
            border-top: none;
            overflow: hidden;
            line-height: 0;
            height: 150px;
            display: flex;
            align-items: center;
            justify-content: center;
        }


        /* メインコンテンツコンテナ */
        .main-content {
            min-height: calc(297mm - 105px - 80px);
        }
    </style>
</head>
@php
    // 写真パスの処理
    $validPhotoPaths = [];
    if ($repairRecord->photos && is_array($repairRecord->photos)) {
        foreach ($repairRecord->photos as $item) {
            if (is_string($item) && !empty(trim($item))) {
                $validPhotoPaths[] = $item;
            }
        }
    }

    $hasPhotos = count($validPhotoPaths) > 0;
@endphp
<body>
    <div class="main-content">
        <!-- Title -->
        <div class="document-title">修理伝票</div>

    <!-- Slip Number -->
    <div class="slip-info">
        伝票番号: <strong>{{ str_pad($repairRecord->id, 6, '0', STR_PAD_LEFT) }}</strong> |
        発行日: <strong>{{ now()->format('Y年m月d日') }}</strong>
    </div>

    <!-- Equipment and Failure Info Tables (Side by Side) -->
    <div class="two-column-container">
        <!-- Left Column -->
        <div class="column-left">
            <!-- Equipment Info Table -->
            <table class="main-table">
                <tr>
                    <td colspan="2" class="section-header">機材情報</td>
                </tr>
                <tr>
                    <th style="width: 80px; max-width: 80px;">メーカー</th>
                    <td>{{ $repairRecord->equipment->manufacturer ?? '-' }}</td>
                </tr>
                <tr>
                    <th style="width: 80px; max-width: 80px;">型番</th>
                    <td>{{ $repairRecord->equipment->model_number ?? '-' }}</td>
                </tr>
                <tr>
                    <th style="width: 80px; max-width: 80px;">新音番号</th>
                    <td>{{ $repairRecord->equipment->company_number ?? '-' }}</td>
                </tr>
                <tr>
                    <th style="width: 80px; max-width: 80px;">シリアル番号</th>
                    <td>{{ $repairRecord->equipment->serial_number ?? '-' }}</td>
                </tr>
            </table>

            <!-- Failure Info Table (Below Equipment Info) -->
            <table class="main-table" style="margin-top: 10px;">
                <tr>
                    <td colspan="2" class="section-header">故障日時・場所</td>
                </tr>
                <tr>
                    <th style="width: 80px; max-width: 80px;">故障発生日</th>
                    <td>{{ $repairRecord->failure_occurred_at ? $repairRecord->failure_occurred_at->format('Y年m月d日') : '-' }}</td>
                </tr>
                <tr>
                    <th style="width: 80px; max-width: 80px;">公演名</th>
                    <td>{{ $repairRecord->performance_name ? mb_substr($repairRecord->performance_name, 0, 15) . (mb_strlen($repairRecord->performance_name) > 15 ? '...' : '') : '-' }}</td>
                </tr>
                <tr>
                    <th style="width: 80px; max-width: 80px;">使用場所</th>
                    <td>{{ $repairRecord->usage_location ? mb_substr($repairRecord->usage_location, 0, 15) . (mb_strlen($repairRecord->usage_location) > 15 ? '...' : '') : '-' }}</td>
                </tr>
                <tr>
                    <th style="width: 80px; max-width: 80px;">報告者</th>
                    <td>{{ $repairRecord->reportedBy->name ?? '-' }}</td>
                </tr>
            </table>

            <!-- Repair Information Table (Below Failure Info) -->
            <table class="main-table" style="margin-top: 10px; margin-bottom: 5px;">
                <tr>
                    <td colspan="2" class="section-header">修理情報</td>
                </tr>
                <tr>
                    <th style="width: 80px; max-width: 80px;">修理依頼先</th>
                    <td>{{ $repairRecord->repair_company ?? '-' }}</td>
                </tr>
                <tr>
                    <th style="width: 80px; max-width: 80px;">社内修理担当者</th>
                    <td>{{ $repairRecord->repairedBy->name ?? $repairRecord->repaired_by ?? '-' }}</td>
                </tr>
                <tr>
                    <th style="width: 80px; max-width: 80px;">修理開始日</th>
                    <td>{{ $repairRecord->started_at ? $repairRecord->started_at->format('Y年m月d日') : '-' }}</td>
                </tr>
                <tr>
                    <th style="width: 80px; max-width: 80px;">修理完了日</th>
                    <td>{{ $repairRecord->completed_at ? $repairRecord->completed_at->format('Y年m月d日') : '-' }}</td>
                </tr>
                <tr>
                    <th style="width: 80px; max-width: 80px;">保証期限</th>
                    <td>{{ $repairRecord->warranty_until ? $repairRecord->warranty_until->format('Y年m月d日') : '-' }}</td>
                </tr>
                <tr>
                    <th style="width: 80px; max-width: 80px;">修理費用(税別)</th>
                    <td>{{ $repairRecord->repair_cost ? '¥' . number_format($repairRecord->repair_cost) : '-' }}</td>
                </tr>
            </table>
        </div>

        <!-- Spacer -->
        <div class="column-spacer"></div>

        <!-- Right Column -->
        <div class="column-right">
            <!-- Problem Description -->
            <table class="main-table">
                <tr>
                    <td class="section-header">問題内容</td>
                </tr>
                <tr>
                    <td style="padding: 3px 10px; vertical-align: top; white-space: pre-wrap; word-wrap: break-word; word-break: break-all; font-size: 14px; line-height: 1.2; height: 190px; overflow: hidden;">@php
                            $text = $repairRecord->problem_description;
                            $result = [];
                            // 既存の改行で分割
                            $existingLines = explode("\n", $text);

                            foreach ($existingLines as $line) {
                                // 各行を23文字で分割
                                while (mb_strlen($line) > 23) {
                                    $result[] = mb_substr($line, 0, 23);
                                    $line = mb_substr($line, 23);
                                }
                                // 残りの文字（23文字以下）を追加
                                if (mb_strlen($line) > 0) {
                                    $result[] = $line;
                                }
                            }

                            // 10行以上の場合、9行目に"..."を付加
                            if (count($result) > 9) {
                                $result = array_slice($result, 0, 9);
                                $result[8] = $result[8] . '...';
                            }

                            echo implode("\n", $result);
                        @endphp</td>
                </tr>
            </table>

            <!-- Repair Description (Below Problem Description) -->
            <table class="main-table" style="margin-top: 10px; margin-bottom: 5px;">
                <tr>
                    <td class="section-header">修理内容</td>
                </tr>
                <tr>
                    <td style="padding: 3px 10px; vertical-align: top; white-space: pre-wrap; word-wrap: break-word; word-break: break-all; font-size: 14px; line-height: 1.2; height: 190px; overflow: hidden;">@php
                            $text = $repairRecord->repair_description ?? '-';
                            if ($text !== '-') {
                                $result = [];
                                // 既存の改行で分割
                                $existingLines = explode("\n", $text);

                                foreach ($existingLines as $line) {
                                    // 各行を23文字で分割
                                    while (mb_strlen($line) > 23) {
                                        $result[] = mb_substr($line, 0, 23);
                                        $line = mb_substr($line, 23);
                                    }
                                    // 残りの文字（23文字以下）を追加
                                    if (mb_strlen($line) > 0) {
                                        $result[] = $line;
                                    }
                                }

                                // 10行以上の場合、9行目に"..."を付加
                                if (count($result) > 9) {
                                    $result = array_slice($result, 0, 9);
                                    $result[8] = $result[8] . '...';
                                }

                                echo implode("\n", $result);
                            } else {
                                echo '-';
                            }
                        @endphp</td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Photos (if exists and limited) -->
    @if($hasPhotos && count($validPhotoPaths) <= 2)
        <div class="photo-section">
            <div class="problem-title">故障箇所写真</div>
            <div class="photo-container">
                @foreach($validPhotoPaths as $index => $photoPath)
                    @php
                        $fullPath = storage_path('app/public/' . $photoPath);
                        if (file_exists($fullPath)) {
                            try {
                                // Intervention Image v3の新しい構文を使用
                                $manager = new \Intervention\Image\ImageManager(
                                    new \Intervention\Image\Drivers\Gd\Driver()
                                );
                                $image = $manager->read($fullPath);

                                // PDF用に最大幅500px、高さ350pxに制限（適度なサイズ）
                                $image->scaleDown(500, 350);

                                // JPEG形式に変換して品質を75%に設定（バランスの良い圧縮）
                                $imageData = base64_encode($image->encodeByMediaType('image/jpeg', quality: 75)->toString());
                                $mimeType = 'image/jpeg';
                            } catch (\Exception $e) {
                                // エラーの場合は元の画像を小さく読み込む
                                try {
                                    $imageData = base64_encode(file_get_contents($fullPath));
                                    $mimeType = mime_content_type($fullPath);
                                } catch (\Exception $e2) {
                                    $imageData = null;
                                }
                            }
                        } else {
                            $imageData = null;
                        }
                    @endphp
                    @if($imageData)
                        <img src="data:{{ $mimeType }};base64,{{ $imageData }}"
                             style="height: 148px; max-width: 45%; margin: 2px 2%; object-fit: contain; vertical-align: middle;">
                    @endif
                @endforeach
            </div>
        </div>
    @endif


    <!-- Note (if exists) -->
    @if($repairRecord->note)
        <div class="problem-section">
            <div class="problem-title">備考</div>
            <div class="problem-content note">@php
                    $text = $repairRecord->note;
                    $result = [];
                    // 既存の改行で分割
                    $existingLines = explode("\n", $text);

                    foreach ($existingLines as $line) {
                        // 各行を49文字で分割
                        while (mb_strlen($line) > 49) {
                            $result[] = mb_substr($line, 0, 49);
                            $line = mb_substr($line, 49);
                        }
                        // 残りの文字（49文字以下）を追加
                        if (mb_strlen($line) > 0) {
                            $result[] = $line;
                        }
                    }

                    // 10行以上の場合、9行目に"..."を付加
                    if (count($result) > 9) {
                        $result = array_slice($result, 0, 9);
                        $result[8] = $result[8] . '...';
                    }

                    echo implode("\n", $result);
                @endphp</div>
        </div>
    @endif
    </div><!-- main-content -->

    <!-- Company Footer -->
    <div class="company-footer">
        @if($companyInfo)
            <div class="company-name">{{ $companyInfo->company_name }}</div>
            <div class="company-details">
                @if($companyInfo->address)
                    {{ $companyInfo->postal_code ? '〒'.$companyInfo->postal_code.' ' : '' }}{{ $companyInfo->address }}<br>
                @endif
                @if($companyInfo->phone)
                    TEL: {{ $companyInfo->phone }}
                @endif
                @if($companyInfo->repair_contact_person)
                     | 修理担当: {{ $companyInfo->repair_contact_person }}
                @endif
                @if($companyInfo->repair_contact_email)
                     | Email: {{ $companyInfo->repair_contact_email }}
                @endif
            </div>
        @else
            <div class="company-name">[会社情報未設定]</div>
        @endif
    </div>
</body>
</html>