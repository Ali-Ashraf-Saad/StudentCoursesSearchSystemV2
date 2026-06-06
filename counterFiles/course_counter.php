<?php
header("Content-Type: application/json; charset=UTF-8");

$file = "counterFiles/course_counter.txt";


if (!file_exists($file)) {
    file_put_contents($file, "0");
}

$count = (int) file_get_contents($file);

$action = $_GET['action'] ?? '';

if ($action === "increment") {
    $count++;
    file_put_contents($file, $count);
}

echo json_encode(["count" => $count]);