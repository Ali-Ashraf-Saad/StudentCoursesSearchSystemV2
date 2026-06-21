<?php
// stats.php - لوحة الإحصائيات الذكية المتقدمة

date_default_timezone_set('Africa/Cairo'); 

// ============================================
// الإعدادات
// ============================================
define('LOGS_DIR', __DIR__ . '/counterFiles/logs');
define('CACHE_DIR', __DIR__ . '/counterFiles/stats_cache');
define('CACHE_TTL', 5); // 5 second

if (!is_dir(CACHE_DIR)) {
    @mkdir(CACHE_DIR, 0755, true);
}

$logFiles = [
    'courses' => [
        'file' => LOGS_DIR . '/course.jsonl',
        'title' => 'زيارات المقررات',
        'icon' => 'fa-book-open',
        'color' => '#3b82f6'
    ],
    'qa' => [
        'file' => LOGS_DIR . '/qa.jsonl',
        'title' => 'زيارات سؤال وجواب',
        'icon' => 'fa-comments',
        'color' => '#10b981'
    ],
    'users' => [
        'file' => LOGS_DIR . '/users.jsonl',
        'title' => 'عمليات بحث الطلاب',
        'icon' => 'fa-magnifying-glass',
        'color' => '#f59e0b'
    ]
];

// ============================================
// دالة تنسيق المدة الزمنية بالعربية
// ============================================
function formatDuration($minutes) {
    $minutes = (int)$minutes;
    if ($minutes < 1) return 'الآن';
    
    $days = floor($minutes / 1440);
    $hours = floor(($minutes % 1440) / 60);
    $mins = $minutes % 60;
    
    $parts = [];
    
    if ($days > 0) {
        if ($days == 1) $parts[] = 'يوم';
        elseif ($days == 2) $parts[] = 'يومين';
        elseif ($days >= 3 && $days <= 10) $parts[] = $days . ' أيام';
        else $parts[] = $days . ' يوم';
    }
    
    if ($hours > 0) {
        if ($hours == 1) $parts[] = 'ساعة';
        elseif ($hours == 2) $parts[] = 'ساعتين';
        elseif ($hours >= 3 && $hours <= 10) $parts[] = $hours . ' ساعات';
        else $parts[] = $hours . ' ساعة';
    }
    
    if ($mins > 0) {
        if ($mins == 1) $parts[] = 'دقيقة';
        elseif ($mins == 2) $parts[] = 'دقيقتين';
        elseif ($mins >= 3 && $mins <= 10) $parts[] = $mins . ' دقائق';
        else $parts[] = $mins . ' دقيقة';
    }
    
    if (empty($parts)) return 'الآن';
    
    $parts = array_slice($parts, 0, 2);
    
    return implode(' و', $parts);
}

// ============================================
// دالة معالجة ملف JSONL واستخراج الإحصائيات
// ============================================
function processJsonlFile($filePath, $id) {
    $cacheFile = CACHE_DIR . "/{$id}_stats.json";
    
    if (file_exists($cacheFile)) {
        $cached = json_decode(@file_get_contents($cacheFile), true);
        if ($cached && isset($cached['lastUpdate'])) {
            $lastUpdate = strtotime($cached['lastUpdate']);
            if ((time() - $lastUpdate) < CACHE_TTL) {
                return $cached;
            }
        }
    }
    
    if (!file_exists($filePath)) {
        return null;
    }
    
    $content = @file_get_contents($filePath);
    if ($content === false || empty(trim($content))) {
        return null;
    }
    
    $lines = explode("\n", trim($content));
    $events = [];
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;
        
        $data = json_decode($line, true);
        if ($data && isset($data['time']) && isset($data['count'])) {
            $events[] = $data;
        }
    }
    
    if (empty($events)) {
        return null;
    }
    
    $now = new DateTime('now');
    $today = $now->format('Y-m-d');
    $yesterday = (clone $now)->modify('-1 day')->format('Y-m-d');
    $currentHour = (int)$now->format('H');
    $currentMonth = $now->format('Y-m');
    
    $lastEvent = end($events);
    $total = (int)$lastEvent['count'];
    
    // حساب آخر زيارة
    $lastEventDateTime = DateTime::createFromFormat('Y-m-d H:i:s', $lastEvent['time']);
    $lastVisitMinutes = 0;
    if ($lastEventDateTime) {
        $diff = $now->getTimestamp() - $lastEventDateTime->getTimestamp();
        $lastVisitMinutes = max(0, floor($diff / 60));
    }
    $lastVisitFormatted = formatDuration($lastVisitMinutes);
    
    $arabicDays = ['الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'];
    $arabicMonths = ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];
    
    usort($events, function($a, $b) {
        return strtotime($a['time']) - strtotime($b['time']);
    });
    
    // ============================================
    // إعداد الفترات الزمنية
    // ============================================
    
    // 1. آخر ساعة - تجميع كل 5 دقائق (12 فترة)
    $minutelyData = []; $minutelyLabels = []; $minutelyIsCurrent = []; $minutelyKeys = [];
    for ($i = 11; $i >= 0; $i--) {
        $t = clone $now;
        $minutesBack = $i * 5;
        $t->modify("-{$minutesBack} minutes");
        $minute = (int)$t->format('i');
        $groupedMinute = floor($minute / 5) * 5;
        $t->setTime((int)$t->format('H'), $groupedMinute, 0);
        $minutelyData[] = 0;
        $minutelyLabels[] = $t->format('H:i');
        $minutelyIsCurrent[] = ($i === 0);
        $minutelyKeys[] = $t->format('Y-m-d H:i');
    }
    
    // 2. آخر 6 ساعات
    $hourly6Data = []; $hourly6Labels = []; $hourly6IsCurrent = []; $hourly6Keys = [];
    for ($i = 5; $i >= 0; $i--) {
        $t = clone $now; $t->modify("-{$i} hours");
        $hourly6Data[] = 0;
        $hourly6Labels[] = $t->format('H:00');
        $hourly6IsCurrent[] = ($i === 0);
        $hourly6Keys[] = $t->format('Y-m-d H');
    }
    
    // 3. آخر 24 ساعة
    $hourlyData = []; $hourlyLabels = []; $hourlyIsCurrent = []; $hourlyKeys = [];
    for ($i = 23; $i >= 0; $i--) {
        $t = clone $now; $t->modify("-{$i} hours");
        $hourlyData[] = 0;
        $hourlyLabels[] = $t->format('H:00');
        $hourlyIsCurrent[] = ($i === 0);
        $hourlyKeys[] = $t->format('Y-m-d H');
    }
    
    // 4. آخر 7 أيام
    $dailyData = []; $dailyLabels = []; $dailyIsCurrent = []; $dailyKeys = [];
    for ($i = 6; $i >= 0; $i--) {
        $t = clone $now; $t->modify("-{$i} days");
        $dailyData[] = 0;
        $dailyLabels[] = $arabicDays[(int)$t->format('w')] . ' ' . $t->format('d/m');
        $dailyIsCurrent[] = ($i === 0);
        $dailyKeys[] = $t->format('Y-m-d');
    }
    
    // 5. آخر 30 يوم
    $daily30Data = []; $daily30Labels = []; $daily30IsCurrent = []; $daily30Keys = [];
    for ($i = 29; $i >= 0; $i--) {
        $t = clone $now; $t->modify("-{$i} days");
        $daily30Data[] = 0;
        $daily30Labels[] = $t->format('d/m');
        $daily30IsCurrent[] = ($i === 0);
        $daily30Keys[] = $t->format('Y-m-d');
    }
    
    // 6. آخر 12 شهر
    $monthlyData = []; $monthlyLabels = []; $monthlyIsCurrent = []; $monthlyKeys = [];
    for ($i = 11; $i >= 0; $i--) {
        $t = clone $now; $t->modify("-{$i} months");
        $monthlyData[] = 0;
        $monthlyLabels[] = $arabicMonths[(int)$t->format('n') - 1] . ' ' . $t->format('M Y');
        $monthlyIsCurrent[] = ($i === 0);
        $monthlyKeys[] = $t->format('Y-m');
    }
    
    // دالة لحساب baseline
    $findBaseline = function(array $events, DateTime $windowStart): int {
        $baseline = 0;
        foreach ($events as $event) {
            $time = DateTime::createFromFormat('Y-m-d H:i:s', $event['time']);
            if (!$time) continue;
            $time->setTimezone($windowStart->getTimezone());
            if ($time < $windowStart) {
                $baseline = (int)$event['count'];
            } else {
                break;
            }
        }
        return $baseline;
    };
    
    // بداية كل نافذة
    $minutelyWindowStart = (clone $now)->modify('-59 minutes');
    $hourly6WindowStart = (clone $now)->modify('-5 hours');
    $hourly6WindowStart->setTime((int)$hourly6WindowStart->format('H'), 0, 0);
    $hourlyWindowStart = (clone $now)->modify('-23 hours');
    $hourlyWindowStart->setTime((int)$hourlyWindowStart->format('H'), 0, 0);
    $dailyWindowStart = (clone $now)->modify('-6 days');
    $dailyWindowStart->setTime(0, 0, 0);
    $daily30WindowStart = (clone $now)->modify('-29 days');
    $daily30WindowStart->setTime(0, 0, 0);
    $monthlyWindowStart = (clone $now)->modify('-11 months');
    $monthlyWindowStart->modify('first day of this month');
    $monthlyWindowStart->setTime(0, 0, 0);
    
    $minutelyBaseline = $findBaseline($events, $minutelyWindowStart);
    $hourly6Baseline = $findBaseline($events, $hourly6WindowStart);
    $hourlyBaseline = $findBaseline($events, $hourlyWindowStart);
    $dailyBaseline = $findBaseline($events, $dailyWindowStart);
    $daily30Baseline = $findBaseline($events, $daily30WindowStart);
    $monthlyBaseline = $findBaseline($events, $monthlyWindowStart);
    
    // بناء cumulative
    $minutelyCumulative = []; $hourly6Cumulative = []; $hourlyCumulative = [];
    $dailyCumulative = []; $daily30Cumulative = []; $monthlyCumulative = [];
    
    $running = $minutelyBaseline;
    for ($i = 0; $i < 12; $i++) { $running += $minutelyData[$i]; $minutelyCumulative[$i] = $running; }
    
    $running = $hourly6Baseline;
    for ($i = 0; $i < 6; $i++) { $running += $hourly6Data[$i]; $hourly6Cumulative[$i] = $running; }
    
    $running = $hourlyBaseline;
    for ($i = 0; $i < 24; $i++) { $running += $hourlyData[$i]; $hourlyCumulative[$i] = $running; }
    
    $running = $dailyBaseline;
    for ($i = 0; $i < 7; $i++) { $running += $dailyData[$i]; $dailyCumulative[$i] = $running; }
    
    $running = $daily30Baseline;
    for ($i = 0; $i < 30; $i++) { $running += $daily30Data[$i]; $daily30Cumulative[$i] = $running; }
    
    $running = $monthlyBaseline;
    for ($i = 0; $i < 12; $i++) { $running += $monthlyData[$i]; $monthlyCumulative[$i] = $running; }
    
    // ============================================
    // معالجة الأحداث
    // ============================================
    $todayCount = 0; $yesterdayCount = 0; $thisHourCount = 0; $thisMonthCount = 0;
    $thisWeekCount = 0; $lastWeekCount = 0;
    $prevCount = 0;
    
    // حساب بداية الأسبوع (السبت في مصر)
    $dayOfWeek = (int)$now->format('w');
    $daysSinceSaturday = ($dayOfWeek + 1) % 7;
    $weekStart = (clone $now)->modify("-{$daysSinceSaturday} days");
    $weekStart->setTime(0, 0, 0);
    $lastWeekStart = (clone $weekStart)->modify('-7 days');
    
    foreach ($events as $event) {
        $time = DateTime::createFromFormat('Y-m-d H:i:s', $event['time']);
        if (!$time) continue;
        $time->setTimezone($now->getTimezone());
        
        $eventCount = (int)$event['count'];
        $increment = $eventCount - $prevCount;
        $prevCount = $eventCount;
        
        if ($increment <= 0) continue;
        
        $eventDate = $time->format('Y-m-d');
        $eventHour = $time->format('Y-m-d H');
        $eventMonth = $time->format('Y-m');
        $eventHourOnly = (int)$time->format('H');
        
        $minute = (int)$time->format('i');
        $groupedMinute = floor($minute / 5) * 5;
        $eventMinuteGrouped = $time->format('Y-m-d H') . ':' . str_pad($groupedMinute, 2, '0', STR_PAD_LEFT);
        
        if ($eventDate === $today) {
            $todayCount += $increment;
            if ($eventHourOnly === $currentHour) $thisHourCount += $increment;
        }
        if ($eventDate === $yesterday) {
            $yesterdayCount += $increment;
        }
        if ($eventMonth === $currentMonth) {
            $thisMonthCount += $increment;
        }
        if ($time >= $weekStart) {
            $thisWeekCount += $increment;
        } elseif ($time >= $lastWeekStart && $time < $weekStart) {
            $lastWeekCount += $increment;
        }
        
        // ملء الفترات
        $mIdx = array_search($eventMinuteGrouped, $minutelyKeys);
        if ($mIdx !== false) { $minutelyData[$mIdx] += $increment; $minutelyCumulative[$mIdx] = $eventCount; }
        
        $h6Idx = array_search($eventHour, $hourly6Keys);
        if ($h6Idx !== false) { $hourly6Data[$h6Idx] += $increment; $hourly6Cumulative[$h6Idx] = $eventCount; }
        
        $hIdx = array_search($eventHour, $hourlyKeys);
        if ($hIdx !== false) { $hourlyData[$hIdx] += $increment; $hourlyCumulative[$hIdx] = $eventCount; }
        
        $dIdx = array_search($eventDate, $dailyKeys);
        if ($dIdx !== false) { $dailyData[$dIdx] += $increment; $dailyCumulative[$dIdx] = $eventCount; }
        
        $d30Idx = array_search($eventDate, $daily30Keys);
        if ($d30Idx !== false) { $daily30Data[$d30Idx] += $increment; $daily30Cumulative[$d30Idx] = $eventCount; }
        
        $mthIdx = array_search($eventMonth, $monthlyKeys);
        if ($mthIdx !== false) { $monthlyData[$mthIdx] += $increment; $monthlyCumulative[$mthIdx] = $eventCount; }
    }
    
    // ملء الفجوات
    for ($i = 1; $i < 12; $i++) { if ($minutelyData[$i] == 0) $minutelyCumulative[$i] = $minutelyCumulative[$i-1]; }
    for ($i = 1; $i < 6; $i++) { if ($hourly6Data[$i] == 0) $hourly6Cumulative[$i] = $hourly6Cumulative[$i-1]; }
    for ($i = 1; $i < 24; $i++) { if ($hourlyData[$i] == 0) $hourlyCumulative[$i] = $hourlyCumulative[$i-1]; }
    for ($i = 1; $i < 7; $i++) { if ($dailyData[$i] == 0) $dailyCumulative[$i] = $dailyCumulative[$i-1]; }
    for ($i = 1; $i < 30; $i++) { if ($daily30Data[$i] == 0) $daily30Cumulative[$i] = $daily30Cumulative[$i-1]; }
    for ($i = 1; $i < 12; $i++) { if ($monthlyData[$i] == 0) $monthlyCumulative[$i] = $monthlyCumulative[$i-1]; }
    
    // متوسط الزيارات اليومي (آخر 30 يوم)
    $totalLast30 = array_sum($daily30Data);
    $avgPerDay = round($totalLast30 / 30, 1);
    
    // ساعة الذروة
    $peakHour = ''; $maxHourVal = 0;
    for ($i = 0; $i < 24; $i++) {
        if ($hourlyData[$i] > $maxHourVal) {
            $maxHourVal = $hourlyData[$i];
            $peakHour = $hourlyLabels[$i];
        }
    }
    
    $stats = [
        'id' => $id,
        'total' => $total,
        'today' => $todayCount,
        'yesterday' => $yesterdayCount,
        'thisHour' => $thisHourCount,
        'thisMonth' => $thisMonthCount,
        'thisWeek' => $thisWeekCount,
        'lastWeek' => $lastWeekCount,
        'avgPerDay' => $avgPerDay,
        'peakHour' => $peakHour ?: '00:00',
        'lastVisitMinutes' => $lastVisitMinutes,
        'lastVisitFormatted' => $lastVisitFormatted,
        'minutelyData' => $minutelyData,
        'minutelyLabels' => $minutelyLabels,
        'minutelyIsCurrent' => $minutelyIsCurrent,
        'minutelyCumulative' => $minutelyCumulative,
        'hourly6Data' => $hourly6Data,
        'hourly6Labels' => $hourly6Labels,
        'hourly6IsCurrent' => $hourly6IsCurrent,
        'hourly6Cumulative' => $hourly6Cumulative,
        'hourlyData' => $hourlyData,
        'hourlyLabels' => $hourlyLabels,
        'hourlyIsCurrent' => $hourlyIsCurrent,
        'hourlyCumulative' => $hourlyCumulative,
        'dailyData' => $dailyData,
        'dailyLabels' => $dailyLabels,
        'dailyIsCurrent' => $dailyIsCurrent,
        'dailyCumulative' => $dailyCumulative,
        'daily30Data' => $daily30Data,
        'daily30Labels' => $daily30Labels,
        'daily30IsCurrent' => $daily30IsCurrent,
        'daily30Cumulative' => $daily30Cumulative,
        'monthlyData' => $monthlyData,
        'monthlyLabels' => $monthlyLabels,
        'monthlyIsCurrent' => $monthlyIsCurrent,
        'monthlyCumulative' => $monthlyCumulative,
        'lastUpdate' => $now->format('Y-m-d H:i:s')
    ];
    
    @file_put_contents($cacheFile, json_encode($stats, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    return $stats;
}

// ============================================
// نقاط API
// ============================================

function getAllLogFiles(): array {
    $files = glob(LOGS_DIR . '/*.jsonl') ?: [];
    sort($files, SORT_NATURAL | SORT_FLAG_CASE);
    return array_map('basename', $files);
}

if (isset($_GET['action']) && $_GET['action'] === 'list_logs') {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    echo json_encode(['files' => getAllLogFiles()], JSON_UNESCAPED_UNICODE);
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'download_log') {
    $file = basename($_GET['file'] ?? '');
    if ($file === '' || !preg_match('/^[a-zA-Z0-9._-]+\.jsonl$/', $file)) {
        http_response_code(400);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'اسم ملف غير صالح';
        exit;
    }
    $filePath = LOGS_DIR . '/' . $file;
    if (!file_exists($filePath) || !is_file($filePath)) {
        http_response_code(404);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'الملف غير موجود';
        exit;
    }
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $file . '"');
    header('Content-Length: ' . filesize($filePath));
    header('Cache-Control: no-store, no-cache, must-revalidate');
    readfile($filePath);
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'get_stats') {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    
    $results = [];
    foreach ($logFiles as $id => $config) {
        $stats = processJsonlFile($config['file'], $id);
        if ($stats) {
            $results[$id] = $stats;
        } else {
            $results[$id] = [
                'id' => $id, 'total' => 0, 'today' => 0, 'yesterday' => 0,
                'thisHour' => 0, 'thisMonth' => 0, 'thisWeek' => 0, 'lastWeek' => 0,
                'avgPerDay' => 0,
                'peakHour' => '00:00', 
                'lastVisitMinutes' => 0, 'lastVisitFormatted' => 'غير متوفر',
                'minutelyData' => array_fill(0, 12, 0), 'minutelyLabels' => [], 'minutelyIsCurrent' => array_fill(0, 12, false), 'minutelyCumulative' => array_fill(0, 12, 0),
                'hourly6Data' => array_fill(0, 6, 0), 'hourly6Labels' => [], 'hourly6IsCurrent' => array_fill(0, 6, false), 'hourly6Cumulative' => array_fill(0, 6, 0),
                'hourlyData' => array_fill(0, 24, 0), 'hourlyLabels' => [], 'hourlyIsCurrent' => array_fill(0, 24, false), 'hourlyCumulative' => array_fill(0, 24, 0),
                'dailyData' => array_fill(0, 7, 0), 'dailyLabels' => [], 'dailyIsCurrent' => array_fill(0, 7, false), 'dailyCumulative' => array_fill(0, 7, 0),
                'daily30Data' => array_fill(0, 30, 0), 'daily30Labels' => [], 'daily30IsCurrent' => array_fill(0, 30, false), 'daily30Cumulative' => array_fill(0, 30, 0),
                'monthlyData' => array_fill(0, 12, 0), 'monthlyLabels' => [], 'monthlyIsCurrent' => array_fill(0, 12, false), 'monthlyCumulative' => array_fill(0, 12, 0),
                'lastUpdate' => date('Y-m-d H:i:s')
            ];
        }
    }
    echo json_encode($results, JSON_UNESCAPED_UNICODE);
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'reset_cache') {
    header('Content-Type: application/json; charset=utf-8');
    $deleted = 0;
    foreach ($logFiles as $id => $config) {
        $cacheFile = CACHE_DIR . "/{$id}_stats.json";
        if (file_exists($cacheFile)) { @unlink($cacheFile); $deleted++; }
    }
    echo json_encode(['success' => true, 'deleted' => $deleted]);
    exit;
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة الإحصائيات الذكية المتقدمة</title>
    <link rel="icon" type="image/x-icon" href="images/stats-favicon.ico">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg-gradient: linear-gradient(135deg, #0b1120 0%, #0f172a 50%, #020617 100%);
            --card-bg: rgba(30, 41, 59, 0.5);
            --card-border: rgba(148, 163, 184, 0.1);
            --primary-blue: #3b82f6;
            --primary-glow: rgba(59, 130, 246, 0.3);
            --text-main: #f1f5f9;
            --text-secondary: #94a3b8;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --glass-blur: blur(20px);
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Cairo', sans-serif;
            background: var(--bg-gradient);
            color: var(--text-main);
            min-height: 100vh;
            padding: 2rem;
            overflow-x: hidden;
        }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #334155; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--primary-blue); }
        .container { max-width: 1400px; margin: 0 auto; }
        header { 
            text-align: center; 
            margin-bottom: 3.5rem; 
            position: relative; 
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
        }
        header h1 {
            font-size: 2.8rem; font-weight: 800;
            background: linear-gradient(135deg, #60a5fa 0%, #3b82f6 50%, #2563eb 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            margin-bottom: 0.75rem; letter-spacing: -0.5px;
        }
        header p { color: var(--text-secondary); font-size: 1.15rem; font-weight: 400; }
        
        /* زر التلميحات */
        .hints-toggle-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.3);
            color: var(--primary-blue);
            padding: 8px 18px;
            border-radius: 20px;
            font-family: 'Cairo';
            font-weight: 600;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 0.5rem;
        }
        .hints-toggle-btn:hover {
            background: rgba(59, 130, 246, 0.2);
            border-color: rgba(59, 130, 246, 0.5);
            transform: translateY(-2px);
        }
        .hints-toggle-btn.active {
            background: var(--primary-blue);
            color: white;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.4);
        }
        .hints-toggle-btn .toggle-icon {
            width: 36px;
            height: 20px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            position: relative;
            transition: all 0.3s ease;
        }
        .hints-toggle-btn.active .toggle-icon {
            background: rgba(255, 255, 255, 0.3);
        }
        .hints-toggle-btn .toggle-icon::after {
            content: '';
            position: absolute;
            width: 16px;
            height: 16px;
            background: white;
            border-radius: 50%;
            top: 2px;
            right: 18px;
            transition: all 0.3s ease;
        }
        .hints-toggle-btn.active .toggle-icon::after {
            right: 2px;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(380px, 1fr));
            gap: 2rem;
        }
        .card {
            background: var(--card-bg);
            backdrop-filter: var(--glass-blur);
            -webkit-backdrop-filter: var(--glass-blur);
            border: 1px solid var(--card-border);
            border-radius: 24px;
            padding: 1.75rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex; flex-direction: column; position: relative; overflow: hidden;
            animation: fadeInUp 0.6s ease-out forwards;
            opacity: 0;
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .card::before {
            content: ''; position: absolute; top: 0; left: 0; right: 0; height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.15), transparent);
        }
        .card:hover {
            transform: translateY(-6px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4), 0 0 30px var(--primary-glow);
            border-color: rgba(59, 130, 246, 0.25);
        }
        .card-header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 1.5rem;
        }
        .card-title {
            font-size: 1.3rem; font-weight: 700; color: var(--text-main);
            display: flex; align-items: center; gap: 0.75rem;
        }
        .card-title i { 
            color: var(--primary-blue); font-size: 1.5rem; 
            filter: drop-shadow(0 0 8px var(--primary-glow));
        }
        .main-counter {
            font-size: 3.8rem; font-weight: 800; text-align: center;
            margin: 0.5rem 0 1.5rem 0; letter-spacing: -1.5px;
            text-shadow: 0 0 40px rgba(59, 130, 246, 0.15);
        }
        .stats-grid {
            display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;
        }
        .stat-item {
            background: rgba(15, 23, 42, 0.5); padding: 1rem; border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.03); transition: all 0.3s ease;
            position: relative;
        }
        .stat-item:hover { 
            background: rgba(15, 23, 42, 0.8); 
            border-color: rgba(255, 255, 255, 0.08);
            transform: translateY(-2px);
        }
        .stat-label {
            font-size: 0.8rem; color: var(--text-secondary); margin-bottom: 0.5rem;
            display: flex; align-items: center; gap: 0.5rem; font-weight: 600;
        }
        .stat-value { font-size: 1.1rem; font-weight: 700; color: var(--text-main); }
        .stat-value.highlight { color: var(--warning); }
        .stat-value.success { color: var(--success); }
        
        /* Tooltip مخصص - يظهر فقط عند تفعيل الزر */
        .stat-item::after {
            content: attr(data-tooltip);
            position: absolute;
            bottom: calc(100% + 8px);
            left: 50%;
            transform: translateX(-50%);
            background: rgba(15, 23, 42, 0.98);
            color: var(--text-main);
            padding: 10px 14px;
            border-radius: 10px;
            font-size: 0.75rem;
            font-weight: 500;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            border: 1px solid rgba(59, 130, 246, 0.3);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4);
            z-index: 1000;
            pointer-events: none;
            max-width: 250px;
            white-space: normal;
            text-align: center;
            line-height: 1.5;
        }
        
        .stat-item::before {
            content: '';
            position: absolute;
            bottom: calc(100% + 2px);
            left: 50%;
            transform: translateX(-50%);
            border: 6px solid transparent;
            border-top-color: rgba(15, 23, 42, 0.98);
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 1000;
        }
        
        /* التلميحات تظهر فقط عند تفعيل الزر */
        body.show-hints .stat-item {
            cursor: help;
        }
        
        body.show-hints .stat-item:hover::after,
        body.show-hints .stat-item:hover::before {
            opacity: 1;
            visibility: visible;
        }
        
        /* مؤشر بصري للكروت عند تفعيل التلميحات */
        body.show-hints .stat-item::after {
            content: attr(data-tooltip);
        }
        
        body:not(.show-hints) .stat-item::after {
            content: '';
        }
        
        .chart-controls {
            display: flex; justify-content: center; margin-bottom: 1rem;
            background: rgba(15, 23, 42, 0.8); padding: 4px; border-radius: 14px;
            width: 100%; border: 1px solid var(--card-border);
            flex-wrap: wrap;
        }
        .toggle-btn {
            flex: 1; padding: 8px 8px; border-radius: 10px; border: none; background: transparent;
            color: var(--text-secondary); font-family: 'Cairo'; font-weight: 600; font-size: 0.75rem;
            cursor: pointer; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            min-width: 60px;
        }
        .toggle-btn:hover:not(.active) {
            color: var(--text-main);
            background: rgba(255, 255, 255, 0.05);
        }
        .toggle-btn.active {
            background: var(--primary-blue); color: white;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
        }
        .chart-container { 
            margin-top: auto; height: 220px; position: relative; width: 100%; 
        }
        .loader-container {
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            height: 60vh; width: 100%;
        }
        .loader {
            width: 50px; height: 50px; border: 4px solid rgba(255,255,255,0.1); 
            border-top-color: var(--primary-blue); border-radius: 50%;
            animation: rotation 1s linear infinite;
        }
        @keyframes rotation { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        .reset-btn {
            margin-top: 3rem;
            text-align: center;
            display: flex;
            justify-content: center;
            gap: 12px;
            flex-wrap: wrap;
        }
        .reset-btn button {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
            border: 1px solid rgba(239, 68, 68, 0.3);
            padding: 0.75rem 2rem;
            border-radius: 12px;
            cursor: pointer;
            font-family: 'Cairo';
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .reset-btn button:first-child {
            background: rgba(59, 130, 246, 0.1);
            color: var(--primary-blue);
            border-color: rgba(59, 130, 246, 0.3);
        }
        .reset-btn button:first-child:hover {
            background: var(--primary-blue);
            color: white;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
        }
        .reset-btn button:hover { 
            background: var(--danger); color: white; 
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
        }
        .last-update-info {
            text-align: center; margin-top: 1rem;
            font-size: 0.85rem; color: var(--text-secondary);
        }
        @media (max-width: 768px) {
            .dashboard-grid { grid-template-columns: 1fr; }
            header h1 { font-size: 2rem; }
            .main-counter { font-size: 3rem; }
            body { padding: 1rem; }
            .toggle-btn { font-size: 0.7rem; padding: 6px 4px; min-width: 50px; }
            body.show-hints .stat-item::after {
                max-width: 200px;
                font-size: 0.7rem;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <header>
        <h1>لوحة التحكم والإحصائيات</h1>
        <button class="hints-toggle-btn" id="hintsToggleBtn" onclick="toggleHints()">
            <i class="fas fa-circle-info"></i>
            <span id="hintsToggleText">تفعيل التلميحات</span>
            <span class="toggle-icon"></span>
        </button>
    </header>

    <div class="dashboard-grid" id="dashboard">
        <div class="loader-container">
            <span class="loader"></span>
            <p style="margin-top: 1.5rem; color: var(--text-secondary); font-weight: 600;">جاري جلب البيانات من ملفات السجلات...</p>
        </div>
    </div>

    <div class="last-update-info" id="lastUpdateInfo"></div>

    <div class="reset-btn">
        <button onclick="downloadAllLogs()">
            <i class="fas fa-download"></i> تحميل كل ملفات JSONL
        </button>
        <button onclick="resetServerCache()">
            <i class="fas fa-rotate-right"></i> إعادة ضبط كاش الخادم
        </button>
    </div>
</div>

<script>
    const countersConfig = [
        { id: 'courses', title: 'زيارات المقررات', icon: 'fa-book-open', color: '#3b82f6' },
        { id: 'qa', title: 'زيارات سؤال وجواب', icon: 'fa-comments', color: '#10b981' },
        { id: 'users', title: 'عمليات بحث الطلاب', icon: 'fa-magnifying-glass', color: '#f59e0b' }
    ];

    const chartDataStore = {};
    const chartInstances = {};

    // كل فترة زمنية مخزّنة في chartDataStore تحت بادئة موحّدة:
    // <prefix>Data, <prefix>Labels, <prefix>IsCurrent, <prefix>Cumulative
    const VIEW_PREFIXES = {
        last1hour: 'minutely',
        last6hours: 'hourly6',
        last24hours: 'hourly',
        last7days: 'daily',
        last30days: 'daily30',
        last12months: 'monthly'
    };

    // يبني كائن chartDataStore الموحّد من استجابة الإحصائيات القادمة من الخادم
    function buildChartDataStoreEntry(stats) {
        const entry = {};
        Object.values(VIEW_PREFIXES).forEach(function(prefix) {
            entry[prefix + 'Data'] = stats[prefix + 'Data'];
            entry[prefix + 'Labels'] = stats[prefix + 'Labels'];
            entry[prefix + 'IsCurrent'] = stats[prefix + 'IsCurrent'];
            entry[prefix + 'Cumulative'] = stats[prefix + 'Cumulative'];
        });
        return entry;
    }

    // يستخرج بيانات/تسميات/علامة "الفترة الحالية" لعرض معيّن (last24hours, last7days...) من أي مصدر بيانات بنفس الشكل
    function getViewSeries(dataStore, view) {
        const prefix = VIEW_PREFIXES[view] || VIEW_PREFIXES.last24hours;
        return {
            data: dataStore[prefix + 'Data'],
            labels: dataStore[prefix + 'Labels'],
            isCurrent: dataStore[prefix + 'IsCurrent'],
            cumulative: dataStore[prefix + 'Cumulative']
        };
    }

    // ============================================
    // إدارة زر التلميحات
    // ============================================
    function toggleHints() {
        const body = document.body;
        const btn = document.getElementById('hintsToggleBtn');
        const text = document.getElementById('hintsToggleText');
        
        body.classList.toggle('show-hints');
        const isActive = body.classList.contains('show-hints');
        
        if (isActive) {
            btn.classList.add('active');
            text.innerText = 'إيقاف التلميحات';
            localStorage.setItem('statsHintsEnabled', 'true');
        } else {
            btn.classList.remove('active');
            text.innerText = 'تفعيل التلميحات';
            localStorage.setItem('statsHintsEnabled', 'false');
        }
    }
    
    // استرجاع حالة الزر عند تحميل الصفحة
    function initHintsToggle() {
        const saved = localStorage.getItem('statsHintsEnabled');
        if (saved === 'true') {
            document.body.classList.add('show-hints');
            const btn = document.getElementById('hintsToggleBtn');
            const text = document.getElementById('hintsToggleText');
            btn.classList.add('active');
            text.innerText = 'إيقاف التلميحات';
        }
    }

    const tooltipPlugin = Chart.registry.getPlugin('tooltip');
    if (tooltipPlugin && tooltipPlugin.positioners) {
        tooltipPlugin.positioners.topFixed = function(elements) {
            if (!elements.length) return false;
            const activeElement = elements[0];
            const chart = this.chart;
            const x = activeElement.element.x;
            const left = chart.chartArea.left;
            const right = chart.chartArea.right;
            let offset = 0;
            if (right > left) {
                const ratio = (x - left) / (right - left);
                offset = ratio * 4;
            }
            return {
                x: x - offset,
                y: chart.chartArea.top,
                xAlign: 'center',
                yAlign: 'bottom'
            };
        };
    }

    async function fetchAllStats() {
        try {
            const response = await fetch('stats?action=get_stats&t=' + Date.now());
            if (!response.ok) throw new Error('HTTP ' + response.status);
            return await response.json();
        } catch (error) {
            console.error('فشل جلب الإحصائيات:', error);
            return null;
        }
    }

// يحسب أنماط النقاط (اللون/الحجم) بناءً على أيّها تمثّل "الفترة الحالية"
function computePointStyles(isCurrentArray) {
    const pointBackgroundColor = [], pointRadius = [], pointBorderWidth = [], pointHoverRadius = [];
    isCurrentArray.forEach(function(isCurrent) {
        if (isCurrent) {
            pointBackgroundColor.push('#fff');
            pointRadius.push(8);
            pointHoverRadius.push(10);
            pointBorderWidth.push(3);
        } else {
            pointBackgroundColor.push('#0f172a');
            pointRadius.push(4);
            pointHoverRadius.push(7);
            pointBorderWidth.push(2);
        }
    });
    return { pointBackgroundColor, pointRadius, pointHoverRadius, pointBorderWidth };
}

// يبرز نقطة "الفترة الحالية" على رسم بياني موجود بالفعل (تحديثات لاحقة بدون أنيميشن)
function updateCurrentHighlight(chart, isCurrentArray, color) {
    const style = computePointStyles(isCurrentArray);
    chart.data.datasets[0].pointBackgroundColor = style.pointBackgroundColor;
    chart.data.datasets[0].pointRadius = style.pointRadius;
    chart.data.datasets[0].pointHoverRadius = style.pointHoverRadius;
    chart.data.datasets[0].pointBorderWidth = style.pointBorderWidth;
    chart.data.datasets[0].pointBorderColor = color;
}

    function getCardHTML(config, stats) {
        return '<div class="card-header">' +
                '<div class="card-title"><i class="fas ' + config.icon + '"></i> ' + config.title + '</div>' +
            '</div>' +
            '<div style="text-align: center;">' +
                '<div style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 0.5rem; font-weight: 600;">الإجمالي</div>' +
                '<div class="main-counter" style="color: ' + config.color + '">' + stats.total.toLocaleString('en-US') + '</div>' +
            '</div>' +
            '<div class="stats-grid">' +
                '<div class="stat-item" data-tooltip="إجمالي عدد الزيارات المسجلة منذ بداية اليوم (12:00 صباحاً) حتى الآن">' +
                    '<div class="stat-label"><i class="fas fa-calendar-day" style="color: var(--success)"></i> زيارات اليوم</div>' +
                    '<div class="stat-value success">' + stats.today.toLocaleString('en-US') + '</div>' +
                '</div>' +
                '<div class="stat-item" data-tooltip="عدد الزيارات المسجلة في الساعة الحالية فقط">' +
                    '<div class="stat-label"><i class="fas fa-clock" style="color: var(--primary-blue)"></i> هذه الساعة</div>' +
                    '<div class="stat-value">' + stats.thisHour.toLocaleString('en-US') + '</div>' +
                '</div>' +
                '<div class="stat-item" data-tooltip="إجمالي عدد الزيارات منذ بداية الأسبوع الحالي (يبدأ من السبت)">' +
                    '<div class="stat-label"><i class="fas fa-calendar-week" style="color: #8b5cf6"></i> هذا الأسبوع</div>' +
                    '<div class="stat-value">' + stats.thisWeek.toLocaleString('en-US') + '</div>' +
                '</div>' +
                '<div class="stat-item" data-tooltip="متوسط عدد الزيارات اليومية خلال آخر 30 يوماً">' +
                    '<div class="stat-label"><i class="fas fa-chart-line" style="color: #a855f7"></i> متوسط يومي</div>' +
                    '<div class="stat-value">' + stats.avgPerDay + '</div>' +
                '</div>' +
                '<div class="stat-item" data-tooltip="الساعة التي سجلت أعلى عدد من الزيارات خلال آخر 24 ساعة">' +
                    '<div class="stat-label"><i class="fas fa-crown" style="color: var(--warning)"></i> ساعة الذروة</div>' +
                    '<div class="stat-value highlight">' + stats.peakHour + '</div>' +
                '</div>' +
                '<div class="stat-item" data-tooltip="الوقت المنقضي منذ آخر زيارة مسجلة في النظام">' +
                    '<div class="stat-label"><i class="fas fa-clock-rotate-left" style="color: #06b6d4"></i> آخر زيارة</div>' +
                    '<div class="stat-value">' + stats.lastVisitFormatted + '</div>' +
                '</div>' +
            '</div>' +
            '<div class="chart-controls">' +
                '<button class="toggle-btn" onclick="switchChartView(\'' + config.id + '\', \'last1hour\', this)">ساعة</button>' +
                '<button class="toggle-btn" onclick="switchChartView(\'' + config.id + '\', \'last6hours\', this)">6 ساعات</button>' +
                '<button class="toggle-btn active" onclick="switchChartView(\'' + config.id + '\', \'last24hours\', this)">24 ساعة</button>' +
                '<button class="toggle-btn" onclick="switchChartView(\'' + config.id + '\', \'last7days\', this)">7 أيام</button>' +
                '<button class="toggle-btn" onclick="switchChartView(\'' + config.id + '\', \'last30days\', this)">30 يوم</button>' +
                '<button class="toggle-btn" onclick="switchChartView(\'' + config.id + '\', \'last12months\', this)">12 شهر</button>' +
            '</div>' +
            '<div class="chart-container"><canvas id="chart-' + config.id + '"></canvas></div>';
    }

// ينشئ كرت إحصائية جديد في أول تحميل، أو يحدّث كرتًا موجودًا في التحديثات اللاحقة
function createOrUpdateCard(config, stats) {
    const existingCard = document.getElementById('card-wrapper-' + config.id);
    if (existingCard) {
        updateCard(config, stats);
    } else {
        const card = document.createElement('div');
        card.className = 'card';
        card.id = 'card-wrapper-' + config.id;
        card.innerHTML = getCardHTML(config, stats);
        document.getElementById('dashboard').appendChild(card);

        chartDataStore[config.id] = buildChartDataStoreEntry(stats);

        setTimeout(function() {
            renderChart(config.id, config.color, stats.hourlyData, stats.hourlyLabels, stats.hourlyIsCurrent, 'last24hours');
        }, 100);
    }
}


// ينشئ رسمًا بيانيًا جديدًا (Chart.js) لكرت إحصائية، مع أنيميشن "رسم الخط" عند الإنشاء فقط
function renderChart(canvasId, color, initialData, initialLabels, initialIsCurrent, defaultView) {
    const ctx = document.getElementById('chart-' + canvasId).getContext('2d');
    const gradient = ctx.createLinearGradient(0, 0, 0, 250);
    gradient.addColorStop(0, color + '50'); 
    gradient.addColorStop(1, color + '00'); 

    // نحسب أنماط النقاط مسبقًا ونضعها ضمن إعداد الرسم الابتدائي بدل استدعاء update() بعد
    // الإنشاء مباشرة - فاستدعاء update('none') فور الإنشاء كان يُلغي أنيميشن الرسم قبل تشغيله
    const pointStyle = computePointStyles(initialIsCurrent);

    const chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: initialLabels,
            datasets: [{
                label: 'النشاط',
                data: initialData,
                borderColor: color,
                backgroundColor: gradient,
                borderWidth: 3,
                pointBackgroundColor: pointStyle.pointBackgroundColor,
                pointBorderColor: color,
                pointBorderWidth: pointStyle.pointBorderWidth,
                pointRadius: pointStyle.pointRadius,
                pointHoverRadius: pointStyle.pointHoverRadius,
                pointHoverBackgroundColor: color,
                pointHoverBorderColor: '#fff',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'nearest', axis: 'x', intersect: false },
            layout: {
                padding: { top: 80 }
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    position: 'topFixed',
                    caretSize: 7,
                    backgroundColor: 'rgba(15, 23, 42, 0.95)',
                    titleFont: { family: 'Cairo', size: 14, weight: 'bold' },
                    bodyFont: { family: 'Cairo', size: 13, weight: '600' },
                    padding: 10, cornerRadius: 10, displayColors: false,
                    borderColor: 'rgba(255,255,255,0.1)', borderWidth: 1,
                    callbacks: {
                        label: function(context) {
                            const view = context.chart.currentView || 'last24hours';
                            const store = chartDataStore[context.chart.canvas.id.replace('chart-', '')];
                            if (!store) return '';

                            const increment = context.parsed.y;
                            const cumulative = getViewSeries(store, view).cumulative[context.dataIndex];

                            return [
                                'الزيادة في هذا الوقت: ' + increment.toLocaleString('en-US'),
                                'الإجمالي حتى هذا الوقت: ' + cumulative.toLocaleString('en-US')
                            ];
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    border: { display: false },
                    ticks: { color: '#64748b', font: { family: 'Cairo', size: 11 }, maxTicksLimit: 8 }
                },
                y: {
                    grid: { color: 'rgba(255, 255, 255, 0.04)' },
                    border: { display: false },
                    ticks: { display: false }, beginAtZero: true
                }
            },
            // أنيميشن "رسم الخط" عند الإنشاء لأول مرة
            animation: { duration: 800, easing: 'easeOutQuart' },
            // تعطيل أنيميشن "resize" تحديدًا (وليس كل الأنيميشن): هذا هو السبب الحقيقي للخط
            // العمودي الذي كان يظهر يمين الرسم ويزيحه لليسار عند إعادة بناء الكروت بعد مسح
            // الكاش - حجم الكرت يتغيّر بفارق بسيط (تحميل خط Cairo / استقرار التخطيط) أثناء
            // تشغيل أنيميشن الإنشاء، فيقوم Chart.js تلقائيًا بعمل resize متحرك في منتصف
            // الحركة وهو ما يُنتج الخط/الإزاحة. تعطيل أنيميشن resize فقط يمنع هذا تحديدًا.
            transitions: {
                resize: {
                    animation: { duration: 0 }
                }
            },
            resizeDelay: 100
        }
    });

    chartInstances[canvasId] = chart;
    chartInstances[canvasId].currentView = defaultView;
}


// يحدّث الأرقام والرسم البياني لكرت موجود دون إعادة إنشائه (يحافظ على حالة التلميح الظاهر إن وجد)
function updateCard(config, stats) {
    const card = document.getElementById('card-wrapper-' + config.id);
    if (!card) return;
    
    card.querySelector('.main-counter').innerText = stats.total.toLocaleString('en-US');
    
    const statValues = card.querySelectorAll('.stat-value');
    statValues[0].innerText = stats.today.toLocaleString('en-US');
    statValues[1].innerText = stats.thisHour.toLocaleString('en-US');
    statValues[2].innerText = stats.thisWeek.toLocaleString('en-US');
    statValues[3].innerText = stats.avgPerDay;
    statValues[4].innerText = stats.peakHour;
    statValues[5].innerText = stats.lastVisitFormatted;

    chartDataStore[config.id] = buildChartDataStoreEntry(stats);
    
    const chart = chartInstances[config.id];
    if (chart) {
        const activeElements = chart.getActiveElements();
        const hasActiveTooltip = activeElements.length > 0;
        const activeIndex = hasActiveTooltip ? activeElements[0].index : -1;

        const currentView = chart.currentView || 'last24hours';
        const series = getViewSeries(chartDataStore[config.id], currentView);

        chart.data.labels = series.labels;
        chart.data.datasets[0].data = series.data;
        updateCurrentHighlight(chart, series.isCurrent, config.color);
        
        chart.update('none');
        
        if (hasActiveTooltip && activeIndex >= 0 && activeIndex < series.data.length) {
            chart.setActiveElements([{datasetIndex: 0, index: activeIndex}]);
            chart.tooltip.setActiveElements([{datasetIndex: 0, index: activeIndex}], {x: 0, y: 0});
            chart.update('none');
        }
    }
}

// يبدّل الفترة الزمنية المعروضة على رسم بياني معيّن عند الضغط على أحد أزرار التبويب
function switchChartView(id, view, btnElement) {
    const buttons = btnElement.parentElement.querySelectorAll('.toggle-btn');
    buttons.forEach(function(btn) { btn.classList.remove('active'); });
    btnElement.classList.add('active');

    const chart = chartInstances[id];
    const data = chartDataStore[id];
    if (!chart || !data) return;
    
    chart.currentView = view;
    const series = getViewSeries(data, view);

    chart.data.labels = series.labels;
    chart.data.datasets[0].data = series.data;
    updateCurrentHighlight(chart, series.isCurrent, chart.data.datasets[0].borderColor);
    chart.update('none');
}

    async function downloadAllLogs() {
        try {
            const response = await fetch('stats?action=list_logs&t=' + Date.now());
            if (!response.ok) throw new Error('HTTP ' + response.status);
            const data = await response.json();
            const files = data.files || [];
            if (files.length === 0) {
                alert('لا توجد ملفات JSONL داخل مجلد السجلات');
                return;
            }
            for (const file of files) {
                const a = document.createElement('a');
                a.href = 'stats?action=download_log&file=' + encodeURIComponent(file) + '&t=' + Date.now();
                a.download = file;
                document.body.appendChild(a);
                a.click();
                a.remove();
                await new Promise(resolve => setTimeout(resolve, 250));
            }
        } catch (error) {
            console.error(error);
            alert('حدث خطأ أثناء تحميل الملفات');
        }
    }

async function resetServerCache() {
    try {
        const response = await fetch('stats?action=reset_cache');
        const result = await response.json();
        if (result.success) {
            // تدمير جميع الرسوم البيانية الحالية
            Object.keys(chartInstances).forEach(function(id) {
                if (chartInstances[id]) {
                    chartInstances[id].destroy();
                }
            });
            
            // مسح المصفوفات
            for (let key in chartInstances) delete chartInstances[key];
            for (let key in chartDataStore) delete chartDataStore[key];
            
            document.getElementById('dashboard').innerHTML = '<div class="loader-container"><span class="loader"></span><p style="margin-top: 1.5rem; color: var(--text-secondary); font-weight: 600;">جاري إعادة حساب الإحصائيات...</p></div>';
            setTimeout(initDashboard, 1000);
        }
    } catch (error) {
        alert('حدث خطأ أثناء إعادة الضبط');
    }
}

    async function initDashboard() {
        const dashboard = document.getElementById('dashboard');
        const allStats = await fetchAllStats();
        
        if (!allStats) {
            dashboard.innerHTML = '<div class="card" style="grid-column: 1/-1; border-color: var(--danger); animation: fadeInUp 0.6s ease-out forwards;"><div class="card-header"><div class="card-title" style="color: var(--danger);"><i class="fas fa-exclamation-triangle"></i> فشل الاتصال بالخادم</div></div><div style="text-align: center; padding: 2rem; color: var(--text-secondary);"><i class="fas fa-server" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3; color: var(--danger);"></i><p style="font-weight: 700; margin-bottom: 0.5rem; color: var(--text-main);">تعذر جلب البيانات من الخادم</p></div></div>';
            return;
        }
        
        const loader = dashboard.querySelector('.loader-container');
        if (loader) loader.remove();
        
        countersConfig.forEach(function(config) {
            const stats = allStats[config.id];
            if (stats) createOrUpdateCard(config, stats);
        });
        
        const now = new Date();
        const timeStr = now.toLocaleTimeString('ar-EG', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        document.getElementById('lastUpdateInfo').innerHTML = '<i class="fas fa-clock"></i> آخر تحديث: ' + timeStr;
    }

    document.addEventListener('DOMContentLoaded', function() {
        initHintsToggle();
        initDashboard();
        setInterval(initDashboard, 5000);
    });
</script>

</body>
</html>