<?php
require_once __DIR__ . '/counterFiles/counter-lib.php';


date_default_timezone_set('Africa/Cairo');
header("Content-Type: application/json; charset=UTF-8");

$MAX_RESULTS = 20;

function normalizeArabic($text) {
    $text = mb_strtolower($text, 'UTF-8');
    $search  = ['أ','إ','آ','ٱ','ة','ى'];
    $replace = ['ا','ا','ا','ا','ه','ي'];
    $text = str_replace($search, $replace, $text);
    $text = preg_replace('/[\x{064B}-\x{065F}]/u', '', $text);
    $text = preg_replace('/\s+/', '', $text);
    return $text;
}

function parseDate($dateStr) {
    if (empty($dateStr)) return null;
    $date = DateTime::createFromFormat('d/m/Y', $dateStr);
    return $date ?: null;
}

$query   = $_GET['q'] ?? '';
$query   = trim($query);
$commit  = ($_GET['commit'] ?? '') === '1';
$clientId = $_GET['client_id'] ?? '';

if (!$query) {
    echo json_encode(["results" => []]);
    exit;
}

$queryNorm = normalizeArabic($query);
$isNumberSearch = preg_match('/^\d+$/', $query);

$students = json_decode(file_get_contents("data/students.json"), true);
$courses  = json_decode(file_get_contents("data/courses.json"), true);
$exams    = json_decode(file_get_contents("data/exams.json"), true);

$driveLinks = [
    'CS438' => 'https://drive.google.com/drive/folders/12DyDQkgaPOgR7UiBQN_-vGJsaI2hI77F',
    'IS381' => 'https://drive.google.com/drive/folders/12DyDQkgaPOgR7UiBQN_-vGJsaI2hI77F',
    'CS424' => 'https://drive.google.com/drive/folders/1sScPEPmuKz-PSBm_6eK6QboHX0PKxfnV',
    'CS323' => 'https://drive.google.com/drive/folders/1pcEuSkbQVN2qgUyrBGVYvvJYoQ8E4yod',
    'CS352' => 'https://drive.google.com/drive/folders/12OwMNWROG6WqP3QyeHNGy3OLqfifVNDv',
    'IS322' => 'https://drive.google.com/drive/folders/1HEaIpNPCB_NMwBH3VfXkltLt-kSbp0z5',
    'IS352' => 'https://drive.google.com/drive/folders/1OMmP5jZ5kxfGpplPKAfW8vyMys6r5LCU',
    'IS342' => 'https://drive.google.com/drive/folders/1NJsazUSiQuBnBHjwanSW2rjeiPT9EfYl',
    'IT341' => 'https://drive.google.com/drive/folders/1mk0F1ME4gBOnP1IioTI5abOtjHsFhoJ2',
    'IT321' => 'https://drive.google.com/drive/folders/13LXjyVZt5Ib_6E5JZOB6BO8di9s_N2kk',
    'IT313' => 'https://drive.google.com/drive/folders/1u25XQbcZZZHdE2l_WTl5Tcy5FR3ETxEf',
    'IT472' => 'https://drive.google.com/drive/folders/1P9dtq423P583o_JLf0gM8GSU8wbMtShJ',
    'IT418' => 'https://drive.google.com/drive/folders/1GdKl4ze_4LP6Bvh-VlaQeHEmDyPbdlxT',
    'CS415' => 'https://drive.google.com/drive/folders/1GdKl4ze_4LP6Bvh-VlaQeHEmDyPbdlxT',
    'IS463' => 'https://drive.google.com/drive/folders/1GdKl4ze_4LP6Bvh-VlaQeHEmDyPbdlxT',
    'CS313' => 'https://drive.google.com/drive/folders/1m3wQzwFxZj4mB-IKgiJprqpPh75qzfZR'
];

$examIndex = [];
foreach ($exams as $exam) {
    $code = $exam['course'];
    foreach ($exam['students'] as $sid) {
        $examIndex[$code][$sid] = [
            'committee' => $exam['committee'],
            'room'      => $exam['room'],
            'day'       => $exam['day'],
            'date'      => $exam['date'],
            'period'    => $exam['period'],
            'time'      => $exam['time']
        ];
    }
}

$results = [];

foreach ($students as $student) {
    $sid   = $student['id'];
    $name  = $student['name'];
    $dept  = $student['department'] ?? '';
    $registeredCourses = $student['courses'] ?? [];

    $match = false;
    if ($isNumberSearch) {
        if (strpos($sid, $query) !== false) $match = true;
    } else {
        $nameNorm = normalizeArabic($name);
        if (strpos($nameNorm, $queryNorm) !== false) $match = true;
    }
    if (!$match) continue;

    $coursesList = [];
    foreach ($registeredCourses as $code) {
        $courseName = $courses[$code]['name'] ?? $code;
        $examInfo = $examIndex[$code][$sid] ?? null;
        $coursesList[] = [
            'code'      => $code,
            'name'      => $courseName,
            'exam'      => $examInfo,
            'driveLink' => $driveLinks[$code] ?? null
        ];
    }

    usort($coursesList, function($a, $b) {
        $dateA = isset($a['exam']['date']) ? parseDate($a['exam']['date']) : null;
        $dateB = isset($b['exam']['date']) ? parseDate($b['exam']['date']) : null;
        if ($dateA !== null && $dateB !== null) return $dateA <=> $dateB;
        if ($dateA !== null && $dateB === null) return -1;
        if ($dateA === null && $dateB !== null) return 1;
        return 0;
    });

    $results[] = [
        "number"     => $sid,
        "name"       => $name,
        "department" => $dept,
        "courses"    => $coursesList
    ];
}

if ($commit && count($results) > 0 && count($results) < 5) {
    if (!file_exists('data')) mkdir('data', 0755, true);

    incrementCounter(__DIR__ . '/counterFiles/logs/users.jsonl');
}

echo json_encode(["results" => $results], JSON_UNESCAPED_UNICODE);