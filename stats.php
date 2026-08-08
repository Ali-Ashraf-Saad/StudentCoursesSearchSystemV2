<?php
// stats.php - لوحة الإحصائيات الذكية المتقدمة

date_default_timezone_set('Africa/Cairo'); 

// ============================================
// الإعدادات
// ============================================
define('LOGS_DIR', __DIR__ . '/counterFiles/logs');
define('CACHE_DIR', __DIR__ . '/counterFiles/stats_cache');
define('CACHE_TTL', 5); // 5 second
define('NO_PEAK_HOUR_LABEL', 'لا يوجد'); // يظهر بدل "00:00" عندما لا توجد أي زيارات لحساب ساعة ذروة

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
    ],
    'gpa' => [
        'file' => LOGS_DIR . '/gpa.jsonl',
        'title' => 'استخدام حاسبة المعدل',
        'icon' => 'fa-calculator',
        'color' => '#8b5cf6'
    ]
]; // add here New counter

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
        $daily30Labels[] = $arabicDays[(int)$t->format('w')] . ' ' . $t->format('d/m');
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
    
    // ساعة الذروة (قد تكون أكثر من ساعة واحدة إذا تساوت أعلى قيمة بين عدة ساعات)
    $peakHours = []; $maxHourVal = 0;
    for ($i = 0; $i < 24; $i++) {
        if ($hourlyData[$i] > $maxHourVal) {
            $maxHourVal = $hourlyData[$i];
            $peakHours = [$hourlyLabels[$i]];
        } elseif ($maxHourVal > 0 && $hourlyData[$i] === $maxHourVal) {
            $peakHours[] = $hourlyLabels[$i];
        }
    }
    $peakHourLabel = $peakHours ? implode('، ', $peakHours) : NO_PEAK_HOUR_LABEL;
    
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
        'peakHour' => $peakHourLabel,
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
                'peakHour' => NO_PEAK_HOUR_LABEL, 
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

    
    <link rel="stylesheet" href="/assets/css/variables.css?v=1">
    <link rel="stylesheet" href="/assets/css/base.css?v=1">
    <link rel="stylesheet" href="/assets/css/stats.css?v=1">
    <link rel="stylesheet" href="/assets/css/nav.css?v=1">
</head>
<body>
  <nav class="top-nav" aria-label="التنقل الرئيسي">
      <button class="nav-menu-toggle" type="button" aria-label="فتح القائمة" aria-expanded="false" aria-controls="primaryNavLinks">
        <span></span><span></span><span></span>
      </button>
      <a class="site-title" href="/" aria-label="العودة إلى الصفحة الرئيسية"><img class="site-title-logo" src="/images/icons8-student-center-96.png" alt="" /><span>StudentCourses</span></a>
      <div class="nav-links" id="primaryNavLinks">
        <button class="nav-btn" data-nav-href="/"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10.5 3a7.5 7.5 0 1 0 4.69 13.36l4.72 4.72a1 1 0 0 0 1.41-1.41l-4.72-4.72A7.5 7.5 0 0 0 10.5 3zm0 2a5.5 5.5 0 1 1 0 11 5.5 5.5 0 0 1 0-11z"/></svg><span>استعلام الطالب</span></button>
        <button class="nav-btn" data-nav-href="/courses"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6H2v14c0 1.1.9 2 2 2h14v-2H4V6zm16-4H8c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H8V4h12v12zM10 9h8v2h-8V9zm0 4h6v2h-6v-2z"/></svg><span>المقررات</span></button>
        <button class="nav-btn" data-nav-href="/qa"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M11 18h2v-2h-2v2zm1-16C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm0-14c-2.21 0-4 1.79-4 4h2c0-1.1.9-2 2-2s2 .9 2 2c0 2-3 1.75-3 5h2c0-2.25 3-2.5 3-5 0-2.21-1.79-4-4-4z"/></svg><span>أسئلة وروابط</span></button>
        <button class="nav-btn" data-nav-href="/gpa"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 2h10a2 2 0 0 1 2 2v16a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2zm0 4h10V4H7v2zm2 4H7v2h2v-2zm4 0h-2v2h2v-2zm4 0h-2v2h2v-2zm-8 4H7v2h2v-2zm4 0h-2v2h2v-2zm4 0h-2v6h2v-6zm-8 4H7v2h2v-2zm4 0h-2v2h2v-2z"/></svg><span>حساب GPA</span></button>
        <button class="nav-btn" data-nav-action="refresh"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 6v3l4-4-4-4v3c-4.42 0-8 3.58-8 8 0 1.57.46 3.03 1.24 4.26L6.7 14.8c-.45-.83-.7-1.79-.7-2.8 0-3.31 2.69-6 6-6zm6.76 1.74L17.3 9.2c.45.83.7 1.79.7 2.8 0 3.31-2.69 6-6 6v-3l-4 4 4 4v-3c4.42 0 8-3.58 8-8 0-1.57-.46-3.03-1.24-4.26z"/></svg><span>تحديث</span></button>
      </div>
    </nav>

<div class="container">
    <header>
        <h1>لوحة التحكم والإحصائيات</h1>
        <div class="subtitle">مراقبة حركات البحث، زيارات المواد والصفحات، وساعات الذروة بنشاط لحظي</div>

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
    <footer>
      StudentsCourses 2026 &middot; Developed by Ali Ashraf &middot;
      <a href="http://wa.me/+201148727448" target="_blank">ContactMe</a>
    </footer>

  <script src="/assets/js/nav.js?v=1"></script>
  <script src="/assets/js/stats.js?v=1"></script>
</body>
</html>
