<?php
/**
 * router.php — PHP Built-in Server Router
 * يحاكي قواعد .htaccess بالكامل للخادم المدمج في PHP
 *
 * الاستخدام:
 *   php -S localhost:8000 router.php
 *
 * @see https://www.php.net/manual/en/features.commandline.webserver.php
 */

// ── البيانات الأساسية للطلب ──────────────────────────────────────────────────
$uri     = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/');
$qs      = $_SERVER['QUERY_STRING'] ?? '';
$docRoot = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\');


// ════════════════════════════════════════════════════════════════════════════
// 1. حذف أي Query String من الصفحة الرئيسية → إعادة توجيه 301
//    RewriteCond %{REQUEST_URI}  ^/$
//    RewriteCond %{QUERY_STRING} .+
//    RewriteRule ^$ /?  [R=301,L]
// ════════════════════════════════════════════════════════════════════════════
if ($uri === '/' && $qs !== '') {
    header('Location: /', true, 301);
    exit;
}


// ════════════════════════════════════════════════════════════════════════════
// 2. /index  /index.php  /index.html  →  /   [R=301]
//    RewriteRule ^index(?:\.(?:php|html))?$ / [R=301,L]
// ════════════════════════════════════════════════════════════════════════════
if (preg_match('#^/index(?:\.(php|html))?$#i', $uri)) {
    header('Location: /', true, 301);
    exit;
}


// ════════════════════════════════════════════════════════════════════════════
// 3. /foo.php  /foo.html  →  /foo   [R=301]
//    RewriteCond %{THE_REQUEST} \s/+(.+?)\.(php|html)[\s?] [NC]
//    RewriteRule ^(.+?)\.(php|html)$ /%1 [R=301,L]
// ════════════════════════════════════════════════════════════════════════════
if (preg_match('#^/(.+?)\.(php|html)$#i', $uri, $m)) {
    header('Location: /' . $m[1], true, 301);
    exit;
}


// ════════════════════════════════════════════════════════════════════════════
// 4. الملفات الثابتة → تسليم مباشر بدون معالجة
//    RewriteRule \.(json|css|js|png|…) - [L,NC]
// ════════════════════════════════════════════════════════════════════════════
if (preg_match(
    '#\.(json|css|js|png|jpe?g|gif|svg|ico|webp|woff2?|ttf)$#i',
    $uri
)) {
    return false; // يُعيد التحكم للخادم المدمج ليخدم الملف مباشرة
}


// ════════════════════════════════════════════════════════════════════════════
// 5. الملفات والمجلدات الحقيقية الموجودة → تسليم مباشر
//    RewriteCond %{REQUEST_FILENAME} -f [OR]
//    RewriteCond %{REQUEST_FILENAME} -d
//    RewriteRule ^ - [L]
// ════════════════════════════════════════════════════════════════════════════
$fsPath = $docRoot . $uri;
if (file_exists($fsPath) && (is_file($fsPath) || is_dir($fsPath))) {
    return false;
}


// ════════════════════════════════════════════════════════════════════════════
// استخراج الـ slug (المسار النظيف بدون / في البداية والنهاية)
// ════════════════════════════════════════════════════════════════════════════
$slug = trim($uri, '/');


// ────────────────────────────────────────────────────────────────────────────
// الصفحة الرئيسية: جرِّب index.php أولاً ثم index.html
// (يُعادل DirectoryIndex index.php index.html)
// ────────────────────────────────────────────────────────────────────────────
if ($slug === '') {
    foreach (['index.php', 'index.html'] as $idx) {
        $file = $docRoot . '/' . $idx;
        if (is_file($file)) {
            _serve($file, $docRoot);
            exit;
        }
    }
    return false; // لا يوجد index → الخادم المدمج يتولى الأمر
}


// ════════════════════════════════════════════════════════════════════════════
// 6. صفحات PHP النظيفة: /foo → foo.php
//    RewriteCond %{DOCUMENT_ROOT}/$1.php -f
//    RewriteRule ^(.+?)/?$ $1.php [L]
// ════════════════════════════════════════════════════════════════════════════
$phpFile = $docRoot . '/' . $slug . '.php';
if (is_file($phpFile)) {
    _serve($phpFile, $docRoot);
    exit;
}


// ════════════════════════════════════════════════════════════════════════════
// 7. صفحات HTML النظيفة: /foo → foo.html
//    RewriteCond %{DOCUMENT_ROOT}/$1.html -f
//    RewriteRule ^(.+?)/?$ $1.html [L]
// ════════════════════════════════════════════════════════════════════════════
$htmlFile = $docRoot . '/' . $slug . '.html';
if (is_file($htmlFile)) {
    _serve($htmlFile, $docRoot);
    exit;
}


// ════════════════════════════════════════════════════════════════════════════
// 404 — الصفحة غير موجودة
// ════════════════════════════════════════════════════════════════════════════
http_response_code(404);

foreach (['404.php', '404.html'] as $p) {
    $f = $docRoot . '/' . $p;
    if (is_file($f)) {
        _serve($f, $docRoot);
        exit;
    }
}

// صفحة 404 احتياطية مدمجة
header('Content-Type: text/html; charset=UTF-8');
echo <<<HTML
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>404 — الصفحة غير موجودة</title>
  <style>
    body { font-family: system-ui, sans-serif; text-align: center;
           padding: 4rem; color: #333; }
    h1   { font-size: 5rem; margin: 0; color: #c0392b; }
    p    { font-size: 1.25rem; }
    a    { color: #2980b9; text-decoration: none; }
  </style>
</head>
<body>
  <h1>404</h1>
  <p>الصفحة التي تبحث عنها غير موجودة.</p>
  <a href="/">→ العودة للرئيسية</a>
</body>
</html>
HTML;
exit;


// ════════════════════════════════════════════════════════════════════════════
// دالة مساعدة: تشغيل PHP أو تقديم HTML
// ════════════════════════════════════════════════════════════════════════════
/**
 * @param string $file     المسار الكامل للملف
 * @param string $docRoot  جذر المشروع
 */
function _serve(string $file, string $docRoot): void
{
    if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
        // تغيير المجلد الحالي ليطابق سلوك Apache
        // (تعمل include/require النسبية بشكل صحيح)
        chdir(dirname($file));

        // تعريف DOCUMENT_ROOT كثابت اختياري للملفات التي تحتاجه
        if (!defined('DOCUMENT_ROOT')) {
            define('DOCUMENT_ROOT', $docRoot);
        }

        require $file;
    } else {
        // HTML / أي ملف نصي آخر
        header('Content-Type: text/html; charset=UTF-8');
        readfile($file);
    }
}