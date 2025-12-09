<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>ユーザーマスタ一覧</title>
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

        .affiliation-section { margin-bottom: 15px; }
        .affiliation-header { font-size: 11pt; font-weight: bold; margin-bottom: 6px; padding: 5px 8px; background: #e5e7eb; border-left: 4px solid #374151; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th { background: #f3f4f6; border: 1px solid #333; padding: 3px 8px; font-size: 8pt; font-weight: bold; text-align: center; }
        td { border: 1px solid #ccc; padding: 3px 8px; font-size: 9pt; }
        tr { page-break-inside: avoid; }

        .col-name { width: 20%; }
        .col-birthday { width: 20%; }
        .birthday-wrapper { display: table; width: 100%; }
        .birthday-date { display: table-cell; text-align: left; }
        .birthday-age { display: table-cell; text-align: right; }
        .col-hired { width: 20%; text-align: center; }
        .col-blood { width: 8%; text-align: center; }
        .col-contact { width: 32%; font-size: 9pt; padding: 2px 4px 2px 8px; }
        .contact-email { margin-bottom: 1px; }
        .contact-phone { }

        .user-furigana { font-size: 7pt; color: #999; margin-bottom: 1px; }
        .user-name { font-weight: bold; font-size: 10pt; }
        .user-resigned { text-decoration: line-through; color: #999; }

        .badge { display: inline-block; padding: 2px 6px; border-radius: 10px; font-size: 7pt; font-weight: bold; }
        .badge-admin { background: #fee2e2; color: #991b1b; }
        .badge-editor { background: #dbeafe; color: #1e40af; }
        .badge-general { background: #dcfce7; color: #166534; }
        .badge-viewer { background: #f3f4f6; color: #374151; }
        .badge-active { background: #dcfce7; color: #166534; }
        .badge-on-leave { background: #fef3c7; color: #92400e; }
        .badge-resigned { background: #fee2e2; color: #991b1b; }

        .no-data { text-align: center; padding: 30px; color: #999; }

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
                <div class="page-title">ユーザーマスタ一覧</div>
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


    @if($groupedUsers->count() > 0)
        @foreach($groupedUsers as $affiliationName => $users)
            <div class="affiliation-section">
                <div class="affiliation-header">{{ $affiliationName }} ({{ $users->count() }}名)</div>
                <table>
                    <thead>
                        <tr>
                            <th class="col-name">氏名</th>
                            <th class="col-birthday">生年月日</th>
                            <th class="col-blood">血液型</th>
                            <th class="col-hired">入社日</th>
                            <th class="col-contact">連絡先</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $user)
                            <tr>
                                <td class="col-name">
                                    @if($user->furigana)
                                        <div class="user-furigana">{{ $user->furigana }}</div>
                                    @endif
                                    <div class="user-name {{ $user->is_resigned ? 'user-resigned' : '' }}">{{ $user->name }}</div>
                                </td>
                                <td class="col-birthday">
                                    @if($user->birthday)
                                        <div class="birthday-wrapper">
                                            <span class="birthday-date">{{ \Carbon\Carbon::parse($user->birthday)->format('Y年n月j日') }}</span>
                                            <span class="birthday-age">({{ \Carbon\Carbon::parse($user->birthday)->age }}歳)</span>
                                        </div>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="col-blood">
                                    @if($user->blood_type)
                                        {{ $user->blood_type }}型
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="col-hired">
                                    @if($user->hired_at)
                                        {{ \Carbon\Carbon::parse($user->hired_at)->format('Y年n月j日') }}
                                        @if($user->resigned_at)
                                            ({{ \Carbon\Carbon::parse($user->hired_at)->diff(\Carbon\Carbon::parse($user->resigned_at))->format('%y年%mヶ月') }})
                                        @else
                                            ({{ \Carbon\Carbon::parse($user->hired_at)->diff(\Carbon\Carbon::now())->format('%y年%mヶ月') }})
                                        @endif
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="col-contact">
                                    <div class="contact-email">{{ $user->email ?: '-' }}</div>
                                    <div class="contact-phone">{{ $user->mobile_phone ?: '-' }}</div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach
    @else
        <div class="no-data">ユーザーデータがありません</div>
    @endif
</body>
</html>
