<?php

// テスト用のCSVテンプレート生成
$headers = [
    'sort', 'name', 'furigana', 'email', 'role', 'affiliation',
    'hired_at', 'birthday', 'mobile_phone', 'postal_code', 'address',
    'emergency_contact_name', 'emergency_contact_phone', 'notes', 'is_staff', 'is_designer',
    'is_driver', 'is_on_leave', 'is_resigned', 'is_active',
];

$timestamp = date('Ymd_His');
$filename = "users_template_{$timestamp}.csv";

$csvData = "\xEF\xBB\xBF".implode(',', $headers)."\n";

header('Content-Type: text/csv; charset=UTF-8');
header("Content-Disposition: attachment; filename=\"{$filename}\"");
header('Content-Transfer-Encoding: binary');

echo $csvData;
