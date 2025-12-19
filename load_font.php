<?php

require_once 'vendor/autoload.php';

// DOMPDFのフォント設定
$fontDir = storage_path('fonts');
$fontCache = storage_path('fonts');

// IPAゴシックフォントのパスを設定
$fontPath = '/usr/share/fonts/opentype/ipafont-gothic/ipag.ttf';

if (! file_exists($fontPath)) {
    echo "Font file not found: $fontPath\n";
    exit(1);
}

// フォントをDOMPDFに登録
\Dompdf\Dompdf::registerFont([
    'family' => 'ipagothic',
    'style' => 'normal',
    'weight' => 'normal',
    'src' => $fontPath,
]);

echo "Font loaded successfully\n";
