<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/counter-lib.php';

$allowedCounters = [
    'qa'     => 'qa.jsonl',
    'course' => 'course.jsonl',
    'users'  => 'users.jsonl',
];

$counter = $_REQUEST['counter'] ?? '';
$action  = $_REQUEST['action'] ?? '';

if (!isset($allowedCounters[$counter])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid counter'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$file = __DIR__ . '/logs/' . $allowedCounters[$counter];

try {
    if ($action === 'increment') {
        incrementCounter($file);

        http_response_code(204);
        exit;
    }

    $last = getCounter($file);

    echo json_encode([
        'success'   => true,
        'count'     => $last['count'],
        'last_time' => $last['time'],
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}