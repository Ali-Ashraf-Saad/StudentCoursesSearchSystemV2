<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");
?>
<!doctype html>
<html lang="ar" dir="rtl">
  <head>
    <link rel="icon" href="/images/favicon.ico?v=2" />
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>الاستعلام عن المقررات الدراسية</title>
    
    <meta
    http-equiv="Cache-Control"
    content="no-cache, no-store, must-revalidate"
    />
    <meta http-equiv="Pragma" content="no-cache" />
    <meta http-equiv="Expires" content="0" />
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    
    <link rel="stylesheet" href="/assets/css/variables.css?v=1">
    <link rel="stylesheet" href="/assets/css/base.css?v=1">
    <link rel="stylesheet" href="/assets/css/index.css?v=1">
    <link rel="stylesheet" href="/assets/css/nav.css?v=1">
</head>

  <body>
    <nav class="top-nav" aria-label="التنقل الرئيسي">
      <button class="nav-menu-toggle" type="button" aria-label="فتح القائمة" aria-expanded="false" aria-controls="primaryNavLinks">
        <span></span><span></span><span></span>
      </button>
      <a class="site-title" href="/" aria-label="العودة إلى الصفحة الرئيسية"><img class="site-title-logo" src="/images/icons8-student-center-96.png" alt="" /><span>StudentCourses</span></a>
      <div class="nav-links" id="primaryNavLinks">
        <button class="nav-btn active" data-nav-href="/"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3.5 9.5 12 3l8.5 6.5V20a1 1 0 0 1-1 1h-5v-6h-5v6h-5a1 1 0 0 1-1-1V9.5z"/></svg><span>استعلام الطالب</span></button>
        <button class="nav-btn" data-nav-href="/courses"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6H2v14c0 1.1.9 2 2 2h14v-2H4V6zm16-4H8c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H8V4h12v12zM10 9h8v2h-8V9zm0 4h6v2h-6v-2z"/></svg><span>المقررات</span></button>
        <button class="nav-btn" data-nav-href="/qa"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M11 18h2v-2h-2v2zm1-16C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm0-14c-2.21 0-4 1.79-4 4h2c0-1.1.9-2 2-2s2 .9 2 2c0 2-3 1.75-3 5h2c0-2.25 3-2.5 3-5 0-2.21-1.79-4-4-4z"/></svg><span>أسئلة وروابط</span></button>
        <button class="nav-btn" data-nav-href="/gpa"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 2h10a2 2 0 0 1 2 2v16a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2zm0 4h10V4H7v2zm2 4H7v2h2v-2zm4 0h-2v2h2v-2zm4 0h-2v2h2v-2zm-8 4H7v2h2v-2zm4 0h-2v2h2v-2zm4 0h-2v6h2v-6zm-8 4H7v2h2v-2zm4 0h-2v2h2v-2z"/></svg><span>حساب GPA</span></button>
        <button class="nav-btn" data-nav-action="refresh"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 6v3l4-4-4-4v3c-4.42 0-8 3.58-8 8 0 1.57.46 3.03 1.24 4.26L6.7 14.8c-.45-.83-.7-1.79-.7-2.8 0-3.31 2.69-6 6-6zm6.76 1.74L17.3 9.2c.45.83.7 1.79.7 2.8 0 3.31-2.69 6-6 6v-3l-4 4 4 4v-3c4.42 0 8-3.58 8-8 0-1.57-.46-3.03-1.24-4.26z"/></svg><span>تحديث</span></button>
      </div>
    </nav>

    <div class="container">
      <div class="logo-wrapper">
        <img src="images/LogoFCI.jpeg" alt="شعار الكلية" class="logo-img" />
      </div>

      <h1>الاستعلام عن المقررات الدراسية</h1>
      <div class="subtitle">استعلام سريع ومباشر عن الامتحانات، اللجان، والمقررات الدراسية</div>
      <div class="counter">عدد الزوار: <span id="visitCount">...</span></div>

      <div class="year-picker">
        <label for="academicYear">السنة الدراسية</label>
        <select id="academicYear" aria-label="اختيار السنة الدراسية">
          <option value="">جاري تحميل السنوات...</option>
        </select>
      </div>

      <div class="search-box">
        <input type="text" id="search" placeholder="اكتب الاسم أو الرقم الأكاديمي..." autocomplete="off" />
        <button class="clear-btn" id="clearSearchBtn" title="مسح البحث" aria-label="مسح البحث">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
      </div>

      <div class="history" id="history">
        <div class="history-label">سجل البحث:</div>
        <div class="history-list" id="history-list"></div>
        <div class="history-clear" id="clear-history">مسح السجل</div>
      </div>

      <div class="pinned-course" id="pinned-course"></div>

      <div class="export-btn-container" id="export-container">
        <button class="export-btn" onclick="exportAsImage()">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
          <span>تحميل صورة المواد</span>
        </button>
      </div>

      <div class="results" id="results"></div>
    </div>

    <div id="export-card"></div>
    <div class="toast" id="toast"></div>

    <footer>
      StudentsCourses 2026 &middot; Developed by <span id="secretStatsTrigger">Ali Ashraf</span> &middot;
      <a href="http://wa.me/+201148727448" target="_blank" rel="noopener noreferrer">ContactMe</a>
    </footer>

    <script src="/assets/js/nav.js?v=1"></script>
    <script src="/assets/js/index.js?v=1"></script>
    <script src="tour-guide.js?v=0"></script>
  </body>
</html>
