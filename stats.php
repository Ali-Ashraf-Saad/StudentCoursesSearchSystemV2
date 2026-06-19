<?php
// stats.php - لوحة الإحصائيات الذكية المتقدمة


date_default_timezone_set('Africa/Cairo'); 

// ============================================
// الإعدادات
// ============================================
define('LOGS_DIR', __DIR__ . '/counterFiles/logs');
define('CACHE_DIR', __DIR__ . '/counterFiles/stats_cache');
define('CACHE_TTL', 30); // مدة صلاحية الكاش بالثواني

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
    $currentHour = (int)$now->format('H');
    $currentMonth = $now->format('Y-m');
    
    $lastEvent = end($events);
    $total = (int)$lastEvent['count'];
    
    // أسماء الأيام والأشهر بالعربي
    $arabicDays = ['الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'];
    $arabicMonths = ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];
    
    // ============================================
    // إعداد الفترات الزمنية الديناميكية
    // ============================================
    $hourlyData = []; $hourlyLabels = []; $hourlyIsCurrent = []; $hourlyKeys = [];
    for ($i = 23; $i >= 0; $i--) {
        $t = clone $now; $t->modify("-{$i} hours");
        $hourlyData[] = 0;
        $hourlyLabels[] = $t->format('H:00');
        $hourlyIsCurrent[] = ($i === 0);
        $hourlyKeys[] = $t->format('Y-m-d H');
    }
    
    $dailyData = []; $dailyLabels = []; $dailyIsCurrent = []; $dailyKeys = [];
    for ($i = 6; $i >= 0; $i--) {
        $t = clone $now; $t->modify("-{$i} days");
        $dailyData[] = 0;
        // إضافة اسم اليوم بالعربي مع التاريخ
        $dailyLabels[] = $arabicDays[(int)$t->format('w')] . ' ' . $t->format('d/m');
        $dailyIsCurrent[] = ($i === 0);
        $dailyKeys[] = $t->format('Y-m-d');
    }
    
    $monthlyData = []; $monthlyLabels = []; $monthlyIsCurrent = []; $monthlyKeys = [];
    for ($i = 11; $i >= 0; $i--) {
        $t = clone $now; $t->modify("-{$i} months");
        $monthlyData[] = 0;
        // إضافة اسم الشهر بالعربي بجانب الاخت الإنجليزي والسنة
        $monthlyLabels[] = $arabicMonths[(int)$t->format('n') - 1] . ' ' . $t->format('M Y');
        $monthlyIsCurrent[] = ($i === 0);
        $monthlyKeys[] = $t->format('Y-m');
    }
    
// ============================================
// حساب الإجمالي التراكمي لكل فترة (Cumulative)
// ============================================

// ترتيب الأحداث زمنيًا
usort($events, function($a, $b) {
    return strtotime($a['time']) - strtotime($b['time']);
});

// دالة تجيب آخر إجمالي قبل بداية النافذة
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
$hourlyWindowStart = (clone $now)->modify('-23 hours');
$hourlyWindowStart->setTime((int)$hourlyWindowStart->format('H'), 0, 0);

$dailyWindowStart = (clone $now)->modify('-6 days');
$dailyWindowStart->setTime(0, 0, 0);

$monthlyWindowStart = (clone $now)->modify('-11 months');
$monthlyWindowStart->modify('first day of this month');
$monthlyWindowStart->setTime(0, 0, 0);

// baseline لكل نافذة
$hourlyBaseline = $findBaseline($events, $hourlyWindowStart);
$dailyBaseline = $findBaseline($events, $dailyWindowStart);
$monthlyBaseline = $findBaseline($events, $monthlyWindowStart);

// بناء cumulative بشكل صحيح من الزيادات
$hourlyCumulative = [];
$dailyCumulative = [];
$monthlyCumulative = [];

$running = $hourlyBaseline;
for ($i = 0; $i < 24; $i++) {
    $running += $hourlyData[$i];
    $hourlyCumulative[$i] = $running;
}

$running = $dailyBaseline;
for ($i = 0; $i < 7; $i++) {
    $running += $dailyData[$i];
    $dailyCumulative[$i] = $running;
}

$running = $monthlyBaseline;
for ($i = 0; $i < 12; $i++) {
    $running += $monthlyData[$i];
    $monthlyCumulative[$i] = $running;
}    
    // ============================================
    // معالجة الأحداث وحساب الفروق والإجماليات
    // ============================================
    usort($events, function($a, $b) {
        return strtotime($a['time']) - strtotime($b['time']);
    });
    
    $todayCount = 0; $thisHourCount = 0; $thisMonthCount = 0;
    $prevCount = 0;
    
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
        
        if ($eventDate === $today) {
            $todayCount += $increment;
            if ($eventHourOnly === $currentHour) $thisHourCount += $increment;
        }
        if ($eventMonth === $currentMonth) {
            $thisMonthCount += $increment;
        }
        
        $hIdx = array_search($eventHour, $hourlyKeys);
        if ($hIdx !== false) {
            $hourlyData[$hIdx] += $increment;
            $hourlyCumulative[$hIdx] = $eventCount; // حفظ الإجمالي في هذه الساعة
        }
        
        $dIdx = array_search($eventDate, $dailyKeys);
        if ($dIdx !== false) {
            $dailyData[$dIdx] += $increment;
            $dailyCumulative[$dIdx] = $eventCount; // حفظ الإجمالي في هذا اليوم
        }
        
        $mIdx = array_search($eventMonth, $monthlyKeys);
        if ($mIdx !== false) {
            $monthlyData[$mIdx] += $increment;
            $monthlyCumulative[$mIdx] = $eventCount; // حفظ الإجمالي في هذا الشهر
        }
    }
    
    // ملء الفجوات في الإجمالي (إذا لم تكن هناك زيارات في فترة معينة، يبقى الإجمالي كما كان)
    for ($i = 1; $i < 24; $i++) {
        if ($hourlyData[$i] == 0) $hourlyCumulative[$i] = $hourlyCumulative[$i-1];
    }
    for ($i = 1; $i < 7; $i++) {
        if ($dailyData[$i] == 0) $dailyCumulative[$i] = $dailyCumulative[$i-1];
    }
    for ($i = 1; $i < 12; $i++) {
        if ($monthlyData[$i] == 0) $monthlyCumulative[$i] = $monthlyCumulative[$i-1];
    }
    
    // حساب ساعة الذروة والمتوسط
    $peakHour = ''; $maxHourVal = 0;
    for ($i = 0; $i < 24; $i++) {
        if ($hourlyData[$i] > $maxHourVal) {
            $maxHourVal = $hourlyData[$i];
            $peakHour = $hourlyLabels[$i];
        }
    }
    $totalLast24 = array_sum($hourlyData);
    $avgPerHour = $totalLast24 > 0 ? round($totalLast24 / 24, 1) : 0;
    
    $stats = [
        'id' => $id,
        'total' => $total,
        'today' => $todayCount,
        'thisHour' => $thisHourCount,
        'thisMonth' => $thisMonthCount,
        'peakHour' => $peakHour ?: '00:00',
        'avgPerHour' => $avgPerHour,
        'hourlyData' => $hourlyData,
        'hourlyLabels' => $hourlyLabels,
        'hourlyIsCurrent' => $hourlyIsCurrent,
        'hourlyCumulative' => $hourlyCumulative,
        'dailyData' => $dailyData,
        'dailyLabels' => $dailyLabels,
        'dailyIsCurrent' => $dailyIsCurrent,
        'dailyCumulative' => $dailyCumulative,
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

    echo json_encode([
        'files' => getAllLogFiles()
    ], JSON_UNESCAPED_UNICODE);
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
                'id' => $id, 'total' => 0, 'today' => 0, 'thisHour' => 0, 'thisMonth' => 0,
                'peakHour' => '00:00', 'avgPerHour' => 0,
                'hourlyData' => array_fill(0, 24, 0), 'hourlyLabels' => [], 'hourlyIsCurrent' => array_fill(0, 24, false), 'hourlyCumulative' => array_fill(0, 24, 0),
                'dailyData' => array_fill(0, 7, 0), 'dailyLabels' => [], 'dailyIsCurrent' => array_fill(0, 7, false), 'dailyCumulative' => array_fill(0, 7, 0),
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
        header { text-align: center; margin-bottom: 3.5rem; position: relative; }
        header h1 {
            font-size: 2.8rem; font-weight: 800;
            background: linear-gradient(135deg, #60a5fa 0%, #3b82f6 50%, #2563eb 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            margin-bottom: 0.75rem; letter-spacing: -0.5px;
        }
        header p { color: var(--text-secondary); font-size: 1.15rem; font-weight: 400; }
        .server-badge {
            display: inline-flex; align-items: center; gap: 8px;
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.2);
            padding: 6px 16px; border-radius: 20px;
            font-size: 0.85rem; color: var(--primary-blue);
            margin-top: 1rem; font-weight: 600;
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
        .live-badge {
            font-size: 0.75rem; color: var(--success); 
            background: rgba(16, 185, 129, 0.1); 
            padding: 6px 12px; border-radius: 20px;
            display: flex; align-items: center; gap: 6px;
            border: 1px solid rgba(16, 185, 129, 0.2);
        }
        .pulse-dot {
            width: 8px; height: 8px; background: var(--success);
            border-radius: 50%; position: relative;
        }
        .pulse-dot::after {
            content: ''; position: absolute; top: -4px; left: -4px;
            width: 16px; height: 16px; background: var(--success);
            border-radius: 50%; opacity: 0.6;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); opacity: 0.6; }
            100% { transform: scale(2.5); opacity: 0; }
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
        .stat-value { font-size: 1.3rem; font-weight: 700; color: var(--text-main); }
        .stat-value.highlight { color: var(--warning); }
        .stat-value.success { color: var(--success); }
        .chart-controls {
            display: flex; justify-content: center; margin-bottom: 1rem;
            background: rgba(15, 23, 42, 0.8); padding: 4px; border-radius: 14px;
            width: 100%; border: 1px solid var(--card-border);
        }
        .toggle-btn {
            flex: 1; padding: 8px 12px; border-radius: 10px; border: none; background: transparent;
            color: var(--text-secondary); font-family: 'Cairo'; font-weight: 600; font-size: 0.85rem;
            cursor: pointer; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
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
            box-shadow: 0 4px 15px rgba(68, 73, 239, 0.3);
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
            .chart-controls { flex-direction: row; }
            .toggle-btn { font-size: 0.8rem; padding: 8px 4px; }
        }
    </style>
</head>
<body>

<div class="container">
    <header>
        <h1>لوحة التحكم والإحصائيات</h1>

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
            
            chartDataStore[config.id] = {
                hourlyData: stats.hourlyData,
                hourlyLabels: stats.hourlyLabels,
                hourlyIsCurrent: stats.hourlyIsCurrent,
                hourlyCumulative: stats.hourlyCumulative,
                dailyData: stats.dailyData,
                dailyLabels: stats.dailyLabels,
                dailyIsCurrent: stats.dailyIsCurrent,
                dailyCumulative: stats.dailyCumulative,
                monthlyData: stats.monthlyData,
                monthlyLabels: stats.monthlyLabels,
                monthlyIsCurrent: stats.monthlyIsCurrent,
                monthlyCumulative: stats.monthlyCumulative
            };
            
            setTimeout(function() { 
                renderChart(config.id, config.color, stats.hourlyData, stats.hourlyLabels, stats.hourlyIsCurrent, 'day'); 
            }, 100);
        }
    }

    function updateCard(config, stats) {
        const card = document.getElementById('card-wrapper-' + config.id);
        if (!card) return;
        
        card.querySelector('.main-counter').innerText = stats.total.toLocaleString('en-US');
        card.querySelector('.stat-value.success').innerText = stats.today.toLocaleString('en-US');
        card.querySelectorAll('.stat-value')[1].innerText = stats.thisHour.toLocaleString('en-US');
        card.querySelector('.stat-value.highlight').innerText = stats.peakHour;
        card.querySelectorAll('.stat-value')[3].innerText = stats.avgPerHour;
        
        chartDataStore[config.id] = {
            hourlyData: stats.hourlyData,
            hourlyLabels: stats.hourlyLabels,
            hourlyIsCurrent: stats.hourlyIsCurrent,
            hourlyCumulative: stats.hourlyCumulative,
            dailyData: stats.dailyData,
            dailyLabels: stats.dailyLabels,
            dailyIsCurrent: stats.dailyIsCurrent,
            dailyCumulative: stats.dailyCumulative,
            monthlyData: stats.monthlyData,
            monthlyLabels: stats.monthlyLabels,
            monthlyIsCurrent: stats.monthlyIsCurrent,
            monthlyCumulative: stats.monthlyCumulative
        };
        
        const chart = chartInstances[config.id];
        if (chart) {
            const currentView = chart.currentView || 'day';
            let newData, newLabels, newIsCurrent;
            
            if (currentView === 'day') {
                newData = stats.hourlyData;
                newLabels = stats.hourlyLabels;
                newIsCurrent = stats.hourlyIsCurrent;
            } else if (currentView === 'week') {
                newData = stats.dailyData;
                newLabels = stats.dailyLabels;
                newIsCurrent = stats.dailyIsCurrent;
            } else {
                newData = stats.monthlyData;
                newLabels = stats.monthlyLabels;
                newIsCurrent = stats.monthlyIsCurrent;
            }
            
            chart.data.labels = newLabels;
            chart.data.datasets[0].data = newData;
            updateCurrentHighlight(chart, newIsCurrent, config.color);
            chart.update('none');
        }
    }

    function updateCurrentHighlight(chart, isCurrentArray, color) {
        const pointColors = [], pointRadii = [], pointBorderWidths = [];
        isCurrentArray.forEach(function(isCurrent) {
            if (isCurrent) {
                pointColors.push('#fff');
                pointRadii.push(8);
                pointBorderWidths.push(3);
            } else {
                pointColors.push('#0f172a');
                pointRadii.push(4);
                pointBorderWidths.push(2);
            }
        });
        chart.data.datasets[0].pointBackgroundColor = pointColors;
        chart.data.datasets[0].pointRadius = pointRadii;
        chart.data.datasets[0].pointBorderWidth = pointBorderWidths;
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
                '<div class="stat-item"><div class="stat-label"><i class="fas fa-calendar-day" style="color: var(--success)"></i> زيارات اليوم</div><div class="stat-value success">' + stats.today.toLocaleString('en-US') + '</div></div>' +
                '<div class="stat-item"><div class="stat-label"><i class="fas fa-clock" style="color: var(--primary-blue)"></i> هذه الساعة</div><div class="stat-value">' + stats.thisHour.toLocaleString('en-US') + '</div></div>' +
                '<div class="stat-item"><div class="stat-label"><i class="fas fa-crown" style="color: var(--warning)"></i> ساعة الذروة</div><div class="stat-value highlight">' + stats.peakHour + '</div></div>' +
                '<div class="stat-item"><div class="stat-label"><i class="fas fa-chart-line" style="color: #a855f7"></i> المتوسط/ساعة</div><div class="stat-value">' + stats.avgPerHour + '</div></div>' +
            '</div>' +
            '<div class="chart-controls">' +
                '<button class="toggle-btn active" onclick="switchChartView(\'' + config.id + '\', \'day\', this)">آخر 24 ساعة</button>' +
                '<button class="toggle-btn" onclick="switchChartView(\'' + config.id + '\', \'week\', this)">آخر 7 أيام</button>' +
                '<button class="toggle-btn" onclick="switchChartView(\'' + config.id + '\', \'month\', this)">آخر 12 شهر</button>' +
            '</div>' +
            '<div class="chart-container"><canvas id="chart-' + config.id + '"></canvas></div>';
    }

    function switchChartView(id, view, btnElement) {
        const buttons = btnElement.parentElement.querySelectorAll('.toggle-btn');
        buttons.forEach(function(btn) { btn.classList.remove('active'); });
        btnElement.classList.add('active');

        const chart = chartInstances[id];
        const data = chartDataStore[id];
        if (!chart || !data) return;
        
        chart.currentView = view;
        let newData, newLabels, newIsCurrent;
        
        if (view === 'day') {
            newData = data.hourlyData; newLabels = data.hourlyLabels; newIsCurrent = data.hourlyIsCurrent;
        } else if (view === 'week') {
            newData = data.dailyData; newLabels = data.dailyLabels; newIsCurrent = data.dailyIsCurrent;
        } else {
            newData = data.monthlyData; newLabels = data.monthlyLabels; newIsCurrent = data.monthlyIsCurrent;
        }
        
        chart.data.labels = newLabels;
        chart.data.datasets[0].data = newData;
        updateCurrentHighlight(chart, newIsCurrent, chart.data.datasets[0].borderColor);
        chart.update();
    }

    function renderChart(canvasId, color, initialData, initialLabels, initialIsCurrent, defaultView) {
        const ctx = document.getElementById('chart-' + canvasId).getContext('2d');
        const gradient = ctx.createLinearGradient(0, 0, 0, 250);
        gradient.addColorStop(0, color + '50'); 
        gradient.addColorStop(1, color + '00'); 

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
                    pointBackgroundColor: '#0f172a',
                    pointBorderColor: color,
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 7,
                    pointHoverBackgroundColor: color,
                    pointHoverBorderColor: '#fff',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.95)',
                        titleFont: { family: 'Cairo', size: 14, weight: 'bold' },
                        bodyFont: { family: 'Cairo', size: 13, weight: '600' },
                        padding: 14, cornerRadius: 10, displayColors: false,
                        borderColor: 'rgba(255,255,255,0.1)', borderWidth: 1,
                        callbacks: {
                            label: function(context) {
                                const view = context.chart.currentView || 'day';
                                const store = chartDataStore[context.chart.canvas.id.replace('chart-', '')];
                                if (!store) return '';
                                
                                let increment = context.parsed.y;
                                let cumulative = 0;
                                
                                if (view === 'day') {
                                    cumulative = store.hourlyCumulative[context.dataIndex];
                                } else if (view === 'week') {
                                    cumulative = store.dailyCumulative[context.dataIndex];
                                } else {
                                    cumulative = store.monthlyCumulative[context.dataIndex];
                                }
                                
                                // إرجاع مصفوفة لعرض سطرين في التلميح
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
                        grid: { display: false, drawBorder: false },
                        ticks: { color: '#64748b', font: { family: 'Cairo', size: 11 }, maxTicksLimit: 8 }
                    },
                    y: {
                        grid: { color: 'rgba(255, 255, 255, 0.04)', drawBorder: false },
                        ticks: { display: false }, beginAtZero: true
                    }
                },
                animation: { duration: 800, easing: 'easeOutQuart' }
            }
        });

        chartInstances[canvasId] = chart;
        chartInstances[canvasId].currentView = defaultView;
        updateCurrentHighlight(chart, initialIsCurrent, color);
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

        // تحميل كل ملف منفصل
        for (const file of files) {
            const a = document.createElement('a');
            a.href = 'stats?action=download_log&file=' + encodeURIComponent(file) + '&t=' + Date.now();
            a.download = file;
            document.body.appendChild(a);
            a.click();
            a.remove();

            // تأخير بسيط حتى لا يمنع المتصفح التحميلات المتعددة
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
        initDashboard();
        setInterval(initDashboard, 5000);
    });
</script>

</body>
</html>