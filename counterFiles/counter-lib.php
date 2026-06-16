<?php
declare(strict_types=1);

function ensureCounterFile(string $file): void
{
    $dir = dirname($file);

    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }

    if (!file_exists($file)) {
        file_put_contents($file, '');
    }
}

function getLastRecordFromHandle($fp): array
{
    $stat = fstat($fp);
    $size = $stat['size'] ?? 0;

    if ($size <= 0) {
        return ['count' => 0, 'time' => null];
    }

    $pos = $size - 1;
    $buffer = '';

    while ($pos >= 0) {
        fseek($fp, $pos);
        $char = fgetc($fp);

        if ($char === "\n") {
            if ($buffer !== '') {
                break;
            }
        } elseif ($char !== "\r") {
            $buffer = $char . $buffer;
        }

        $pos--;
    }

    $line = trim($buffer);

    if ($line === '') {
        return ['count' => 0, 'time' => null];
    }

    $decoded = json_decode($line, true);

    if (is_array($decoded) && isset($decoded['count'])) {
        return [
            'count' => (int) $decoded['count'],
            'time'  => $decoded['time'] ?? null
        ];
    }

    if (is_numeric($line)) {
        return ['count' => (int) $line, 'time' => null];
    }

    return ['count' => 0, 'time' => null];
}

function incrementCounter(string $file): int
{
    ensureCounterFile($file);

    $fp = fopen($file, 'c+');
    if (!$fp) {
        throw new RuntimeException('Cannot open counter file');
    }

    flock($fp, LOCK_EX);

    $last  = getLastRecordFromHandle($fp);
    $count = $last['count'] + 1;

    $record = [
        'count' => $count,
        'time'  => (new DateTimeImmutable('now', new DateTimeZone('Africa/Cairo')))
            ->format('Y-m-d H:i:s')
    ];

    fseek($fp, 0, SEEK_END);
    fwrite($fp, json_encode($record, JSON_UNESCAPED_UNICODE) . PHP_EOL);

    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);

    return $count;
}

function getCounter(string $file): array
{
    ensureCounterFile($file);

    $fp = fopen($file, 'c+');
    if (!$fp) {
        throw new RuntimeException('Cannot open counter file');
    }

    flock($fp, LOCK_SH);

    $last = getLastRecordFromHandle($fp);

    flock($fp, LOCK_UN);
    fclose($fp);

    return $last;
}