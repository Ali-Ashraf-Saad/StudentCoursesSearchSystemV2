<?php
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$dataRoot = __DIR__ . '/data';
$years = [];

// كل مجلد باسم سنة أكاديمية وترم يمثل مجموعة بيانات مستقلة.
if (is_dir($dataRoot)) {
    foreach (scandir($dataRoot) ?: [] as $entry) {
        if (preg_match('/^\d{4}_\d{4}_[123]$/', $entry) && is_dir($dataRoot . '/' . $entry)) {
            $years[] = $entry;
        }
    }
}
$years = array_values(array_unique($years));
usort($years, function ($a, $b) {
    preg_match('/^(\d{4})_(\d{4})_([123])$/', $a, $ma);
    preg_match('/^(\d{4})_(\d{4})_([123])$/', $b, $mb);
    $aKey = [(int)$ma[1], (int)$ma[2], (int)$ma[3]];
    $bKey = [(int)$mb[1], (int)$mb[2], (int)$mb[3]];
    return $bKey <=> $aKey;
});

$yearOptions = array_map(function ($year) {
    if (preg_match('/^(\d{4})_(\d{4})_([123])$/', $year, $match)) {
        $termLabel = ['1' => 'الأول', '2' => 'الثاني', '3' => 'الصيفي'][$match[3]];
        return [
            'key' => $year,
            'label' => $match[1] . '/' . $match[2] . ' — الترم ' . $termLabel
        ];
    }
}, $years);

echo json_encode(['years' => $yearOptions], JSON_UNESCAPED_UNICODE);
