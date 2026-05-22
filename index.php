<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");
?>
<!doctype html>
<html lang="ar">
  <head>
    <link rel="icon" href="/favicon.ico?v=2">
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>الاستعلام عن المقررات الدراسية</title>

    <!-- منع التخزين المؤقت للمتصفح -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
    <meta http-equiv="Pragma" content="no-cache" />
    <meta http-equiv="Expires" content="0" />

    <!-- مكتبة html2canvas لتصدير الصورة -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

    <style>
      @import url("https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap");

      * { box-sizing: border-box; }

      body {
        font-family: "Cairo", sans-serif;
        background: linear-gradient(135deg, #0f172a, #020617);
        color: #fff;
        margin: 0; padding: 0;
        display: flex; flex-direction: column; min-height: 100vh;
      }

      .container {
        max-width: 700px; margin: auto; padding: 40px 20px; flex: 1;
      }

      .logo-wrapper {
        text-align: center;
        margin-bottom: 20px;
      }

      .logo-img {
        width: 80px;
        height: auto;
        border-radius: 24px;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3), 0 0 0 2px rgba(59, 130, 246, 0.2);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        background: #1e293b;
        padding: 4px;
        cursor: pointer;
      }

      .logo-img:hover {
        transform: scale(1.05);
        box-shadow: 0 12px 25px rgba(59, 130, 246, 0.4), 0 0 0 2px #3b82f6;
      }

      h1 { text-align: center; margin-bottom: 30px; font-weight: 600; }

      .counter { text-align: center; margin-bottom: 20px; color: #94a3b8; font-size: 14px; }

      .search-box {
        position: relative;
      }

      .search-box input {
        width: 100%;
        padding: 15px 50px 15px 20px;
        border-radius: 15px;
        border: none;
        outline: none;
        font-size: 16px;
        background: #1e293b;
        color: white;
        transition: 0.3s;
      }

      .search-box input:focus {
        box-shadow: 0 0 15px #3b82f6;
      }

      .search-box .clear-btn {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: #94a3b8;
        font-size: 22px;
        cursor: pointer;
        padding: 4px 8px;
        border-radius: 50%;
        line-height: 1;
        display: none;
        transition: background 0.2s, color 0.2s;
      }

      .search-box .clear-btn:hover {
        color: #f87171;
        background: rgba(255,255,255,0.1);
      }

      .history {
        margin-top: 12px;
        background: rgba(30, 41, 59, 0.6);
        border-radius: 12px;
        padding: 10px;
        display: none;
      }

      .history-label {
        font-size: 13px;
        color: #94a3b8;
        margin-bottom: 8px;
        padding: 0 8px;
      }

      .history-list {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 8px;
      }

      .history-item {
        background: #0f172a;
        border-radius: 8px;
        padding: 4px 10px;
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        transition: background 0.2s;
      }

      .history-item:hover { background: #1e3a5f; }

      .history-text {
        color: #e2e8f0;
        font-size: 14px;
        max-width: 120px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
      }

      .history-delete {
        background: none; border: none; color: #94a3b8;
        font-size: 16px; cursor: pointer; padding: 0; line-height: 1;
        transition: color 0.2s;
      }

      .history-delete:hover { color: #f87171; }

      .history-clear {
        display: inline-block;
        width: auto;
        color: #f59e0b;
        font-size: 13px;
        cursor: pointer;
        padding: 4px 12px;
        border-radius: 6px;
        transition: background 0.2s;
      }

      .history-clear:hover { background: rgba(245, 158, 11, 0.1); }

      .results { margin-top: 30px; }

      .card {
        background: rgba(30, 41, 59, 0.8); padding: 20px; border-radius: 20px;
        margin-bottom: 20px; animation: fadeIn 0.4s ease; transition: 0.3s;
      }

      .card:hover {
        transform: translateY(-5px) scale(1.01);
        box-shadow: 0 10px 25px rgba(59, 130, 246, 0.3);
      }

      .name { font-size: 18px; font-weight: 600; }
      .number { color: #94a3b8; margin: 5px 0; }

      .course-item {
        background: #0f172a; border-radius: 12px; padding: 12px;
        margin: 10px 0; border-right: 4px solid #3b82f6;
      }

      .course-name { font-weight: 600; margin-bottom: 6px; }

      .exam-details {
        font-size: 13px; color: #cbd5e1; display: flex; flex-wrap: wrap; gap: 15px;
      }
      .exam-details span { white-space: nowrap; }
      .no-exam { color: #f59e0b; font-style: italic; }
      .no-result { text-align: center; color: #94a3b8; margin-top: 20px; }
      .remaining-time { font-weight: 600; margin-top: 4px; display: block; }

      .export-btn-container {
        display: none;
        text-align: center;
        margin: 20px 0;
      }

      .export-btn {
        background: linear-gradient(135deg, #3b82f6, #1d4ed8);
        color: white;
        border: none;
        padding: 12px 30px;
        border-radius: 50px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        box-shadow: 0 4px 15px rgba(59,130,246,0.4);
        display: inline-flex;
        align-items: center;
        gap: 8px;
      }

      .export-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(59,130,246,0.6);
      }

      #export-card {
        position: absolute;
        left: -9999px;
        top: -9999px;
        width: 600px;
        padding: 30px;
        background: linear-gradient(145deg, #0b1120 0%, #1a1f2f 100%);
        border-radius: 24px;
        color: #e2e8f0;
        font-family: "Cairo", sans-serif;
        direction: rtl;
        border: 2px solid rgba(59,130,246,0.4);
        box-shadow: 0 20px 40px rgba(0,0,0,0.6);
      }

      #export-card .export-header {
        text-align: center;
        margin-bottom: 25px;
        padding-bottom: 20px;
        border-bottom: 2px solid rgba(59,130,246,0.3);
      }

      #export-card .export-name {
        font-size: 26px;
        font-weight: 700;
        background: linear-gradient(to left, #60a5fa, #a78bfa);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        margin-bottom: 5px;
      }

      #export-card .export-number {
        font-size: 16px;
        color: #94a3b8;
      }

      #export-card .export-course {
        background: rgba(15,23,42,0.8);
        border-radius: 14px;
        padding: 15px;
        margin: 12px 0;
        border-right: 5px solid #3b82f6;
        backdrop-filter: blur(10px);
      }

      #export-card .export-course-name {
        font-size: 18px;
        font-weight: 600;
        margin-bottom: 8px;
        color: #f1f5f9;
      }

      #export-card .export-exam-row {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        font-size: 14px;
        color: #cbd5e1;
      }

      #export-card .export-exam-row span {
        background: rgba(59,130,246,0.15);
        padding: 4px 12px;
        border-radius: 20px;
        white-space: nowrap;
      }

      #export-card .no-exam-export {
        color: #f59e0b;
        font-style: italic;
        font-size: 13px;
        margin-top: 5px;
      }

      #export-card .watermark {
        text-align: center;
        margin-top: 20px;
        font-size: 12px;
        color: #475569;
      }

      footer {
        text-align: center; padding: 15px; background: rgba(15, 23, 42, 0.9);
        color: #94a3b8; font-size: 14px;
        border-top: 1px solid rgba(148, 163, 184, 0.2); backdrop-filter: blur(10px);
      }

      @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
      }

      .dashboard-btn {
        position: fixed; top: 20px; left: 20px; display: flex; align-items: center; gap: 8px;
        background: linear-gradient(135deg, #16537e, #cfe2f3); color: #000; border: none;
        padding: 10px 14px; border-radius: 12px; font-size: 14px; cursor: pointer;
        box-shadow: 0 0 15px rgba(0,200,255,0.4); transition: all 0.3s ease; overflow: hidden;
        z-index: 999;
      }
      .dashboard-btn .text { opacity: 0; max-width: 0; transition: all 0.3s ease; white-space: nowrap; }
      .dashboard-btn:hover { padding: 10px 18px; }
      .dashboard-btn:hover .text { opacity: 1; max-width: 120px; }
      .dashboard-btn:hover { transform: translateY(-2px) scale(1.05); box-shadow: 0 0 25px rgba(0,200,255,0.7); }
      .dashboard-btn .icon {
        width: 25px;
        height: 25px;
        margin-right: 8px;
      }
      /* معالجة تغير لون الخلفية عند الإكمال التلقائي */
input:-webkit-autofill,
input:-webkit-autofill:hover,
input:-webkit-autofill:focus,
input:-webkit-autofill:active {
    -webkit-box-shadow: 0 0 0 30px #1e293b inset !important;  /* يفرض لون الخلفية الداكن */
    -webkit-text-fill-color: white !important;                 /* يفرض لون النص أبيض */
    caret-color: white !important;                             /* لون المؤشر */
    border: none !important;                                   /* يمنع أي حدود زرقاء */
    transition: background-color 5000s ease-in-out 0s;         /* يمنع وميض الانتقال */
}
    </style>
  </head>

  <body>
    <button class="dashboard-btn" onclick="goDashboard()">
      <img src="dashboard-icon.png" class="icon">
      <span class="text">Dashboard</span>
    </button>

    <div class="container">
      <div class="logo-wrapper">
        <a href="https://www.facebook.com/profile.php?id=61569852016021" target="_blank" rel="noopener noreferrer">
          <img src="images/LogoFCI.jpeg" alt="شعار الكلية" class="logo-img">
        </a>
      </div>

      <h1>الاستعلام عن المقررات الدراسية</h1>

      <div class="counter">
        عدد الزوار: <span id="visitCount">...</span>
      </div>

      <div class="search-box">
        <input type="text" id="search" placeholder="اكتب الاسم أو الرقم الأكاديمي..." />
        <button class="clear-btn" id="clearSearchBtn" title="مسح البحث">✕</button>
      </div>

      <div class="history" id="history">
        <div class="history-label">سجل البحث:</div>
        <div class="history-list" id="history-list"></div>
        <div class="history-clear" id="clear-history">مسح السجل</div>
      </div>

      <div class="export-btn-container" id="export-container">
        <button class="export-btn" onclick="exportAsImage()">
          📸 تحميل صورة المواد
        </button>
      </div>

      <div class="results" id="results"></div>
    </div>

    <div id="export-card"></div>

    <footer>
      © 2026 StudentsCourses V2 · Developed by Ali Ashraf
    </footer>

    <script>
      let suppressBlurCommit = false;
      // ═══════════════════════════════════════════
      //  وضع المحاكاة الزمنية للاختبار
      // ═══════════════════════════════════════════
      const SIMULATION_MODE = false;                  // اجعلها false للتشغيل الحقيقي
      const SIMULATION_START = '2026-05-20T10:00:00'; // وقت بداية المحاكاة

      let pageLoadRealTime = null;
      let simulationStartMs = null;

      function initSimulation() {
        if (SIMULATION_MODE) {
          simulationStartMs = new Date(SIMULATION_START).getTime();
          pageLoadRealTime = Date.now();
        }
      }

      function simulatedNow() {
        if (!SIMULATION_MODE || !pageLoadRealTime || !simulationStartMs) {
          return new Date();
        }
        const realNow = Date.now();
        const elapsed = realNow - pageLoadRealTime;
        return new Date(simulationStartMs + elapsed);
      }

      // استدعاء التهيئة
      initSimulation();

      // ═══════════════════════════════════════════
      //  إدارة سجل البحث
      // ═══════════════════════════════════════════
      const HISTORY_KEY = 'search_history';
      const MAX_HISTORY = 10;

      function loadHistory() {
        const raw = localStorage.getItem(HISTORY_KEY);
        return raw ? JSON.parse(raw) : [];
      }

      function saveHistory(history) {
        localStorage.setItem(HISTORY_KEY, JSON.stringify(history));
      }

      function addToHistory(query) {
        if (!query) return;
        let history = loadHistory();
        history = history.filter(item => item !== query);
        history.unshift(query);
        if (history.length > MAX_HISTORY) history.pop();
        saveHistory(history);
        renderHistory();
      }

      function deleteHistoryItem(query) {
        let history = loadHistory().filter(item => item !== query);
        saveHistory(history);
        renderHistory();
      }

      function clearHistory() {
        localStorage.removeItem(HISTORY_KEY);
        renderHistory();
      }

      function renderHistory() {
        const historyContainer = document.getElementById('history-list');
        const historyDiv = document.getElementById('history');
        const history = loadHistory();

        if (history.length === 0) {
          historyDiv.style.display = 'none';
          return;
        }

        historyDiv.style.display = 'block';
        let html = '';
        history.forEach(item => {
          html += `<div class="history-item">
                     <span class="history-text" data-query="${item}">${item}</span>
                     <button class="history-delete" data-query="${item}">×</button>
                   </div>`;
        });
        historyContainer.innerHTML = html;

        document.querySelectorAll('.history-text').forEach(el => {
          el.addEventListener('click', function() {
            const query = this.getAttribute('data-query');
            document.getElementById('search').value = query;
            lastPolledValue = query;
            clearBtn.style.display = 'block';
            doCommitSearch(query);
          });
        });

        document.querySelectorAll('.history-delete').forEach(btn => {
          btn.addEventListener('click', function(e) {
            e.stopPropagation();
            const query = this.getAttribute('data-query');
            deleteHistoryItem(query);
          });
        });
      }

      document.addEventListener('click', function(e) {
        if (e.target && e.target.id === 'clear-history') {
          clearHistory();
        }
      });

      // ═══════════════════════════════════════════
      //  توليد Client ID
      // ═══════════════════════════════════════════
      function getClientId() {
        let id = localStorage.getItem('client_id');
        if (!id) {
          id = 'client_' + Math.random().toString(36).substr(2, 9) + Date.now();
          localStorage.setItem('client_id', id);
        }
        return id;
      }
      const CLIENT_ID = getClientId();

      // ═══════════════════════════════════════════
      //  عناصر البحث
      // ═══════════════════════════════════════════
      const searchInput = document.getElementById("search");
      const clearBtn = document.getElementById("clearSearchBtn");
      const resultsDiv = document.getElementById("results");
      const exportContainer = document.getElementById("export-container");

      let debounceTimer = null;
      let autoCommitTimer = null;
      let committedQuery = '';
      let lastPolledValue = '';
      let currentStudentData = null;
      let commitBlocked = false;

      // مؤقت التحديث التلقائي للوقت
      let remainingTimer = null;

      // ═══════════════════════════════════════════
      //  عداد الزوار (قراءة فقط)
      // ═══════════════════════════════════════════
      function loadCounter() {
        fetch("counter.php?action=get")
          .then(res => res.json())
          .then(data => {
            const el = document.getElementById("visitCount");
            if (el) el.innerText = data.count ?? 0;
          })
          .catch(() => {
            const el = document.getElementById("visitCount");
            if (el) el.innerText = "—";
          });
      }
      loadCounter();
      setInterval(loadCounter, 5000);

      // ═══════════════════════════════════════════
      //  زر مسح البحث
      // ═══════════════════════════════════════════
      function toggleClearButton() {
        clearBtn.style.display = searchInput.value.trim() ? 'block' : 'none';
      }

clearBtn.addEventListener('mousedown', function() {
  suppressBlurCommit = true;   // ← تفعيل المنع قبل فقدان التركيز
});

clearBtn.addEventListener('click', function() {
  searchInput.value = '';
  toggleClearButton();
  lastPolledValue = '';
  committedQuery = '';
  resultsDiv.innerHTML = '';
  exportContainer.style.display = 'none';
  currentStudentData = null;
  clearTimeout(remainingTimer);
  searchInput.focus();
  clearAutoCommit();

  // إعادة تفعيل commit العادي بعد انتهاء دورة الأحداث
  setTimeout(() => { suppressBlurCommit = false; }, 0);
});      searchInput.addEventListener('input', toggleClearButton);
      toggleClearButton();

      // ═══════════════════════════════════════════
      //  تحليل تاريخ الامتحان
      // ═══════════════════════════════════════════
      function parseExamDateTime(dateStr, timeStr) {
        try {
          let full = (dateStr || '').trim();
          if (timeStr) full += ' ' + timeStr.trim();

          const patterns = [
            /(\d{4})[-\/](\d{1,2})[-\/](\d{1,2})\s+(\d{1,2})[.:](\d{2})/,
            /(\d{1,2})[-\/](\d{1,2})[-\/](\d{4})\s+(\d{1,2})[.:](\d{2})/,
          ];

          for (const pat of patterns) {
            const m = full.match(pat);
            if (m) {
              let y, mo, d, h, mi;
              if (pat === patterns[0]) {
                y = +m[1]; mo = +m[2]; d = +m[3];
              } else {
                d = +m[1]; mo = +m[2]; y = +m[3];
              }
              h = +m[4]; mi = +m[5];

              const timePart = (timeStr || '').trim();
              if (timePart) {
                if (/مساء|م|pm/i.test(timePart) && h < 12) h += 12;
                else if (/صباح|ص|am/i.test(timePart) && h === 12) h = 0;
              }
              return new Date(y, mo - 1, d, h, mi, 0);
            }
          }

          // محاولة استخراج التاريخ فقط (بدون وقت)
          const dateOnly = full.match(/(\d{4})[-\/](\d{1,2})[-\/](\d{1,2})/);
          if (dateOnly) {
            const y = +dateOnly[1], mo = +dateOnly[2], d = +dateOnly[3];
            let h = 0, mi = 0;
            const timePart = (timeStr || '').trim();
            if (timePart) {
              const tm = timePart.match(/(\d{1,2})[.:](\d{2})/);
              if (tm) {
                h = +tm[1];
                mi = +tm[2];
                if (/مساء|م|pm/i.test(timePart) && h < 12) h += 12;
                else if (/صباح|ص|am/i.test(timePart) && h === 12) h = 0;
              }
            }
            return new Date(y, mo - 1, d, h, mi, 0);
          }
        } catch (e) {}
        return null;
      }

      // ═══════════════════════════════════════════
      //  حساب الوقت المتبقي (باستخدام المحاكاة)
      // ═══════════════════════════════════════════
      function getRemainingTimeFromDate(examDate) {
        const now = simulatedNow();  // هنا نستخدم الوقت المحاكى
        const diffMs = examDate - now;
        if (diffMs <= 0) return '✅ منتهي';

        const diffDays = Math.floor(diffMs / 86400000);
        const diffHours = Math.floor((diffMs % 86400000) / 3600000);
        const diffMinutes = Math.floor((diffMs % 3600000) / 60000);
        const diffSeconds = Math.floor((diffMs % 60000) / 1000);

        if (diffDays === 0 && diffHours === 0 && diffMinutes === 0) {
          return `⏳ متبقي ${diffSeconds} ثانية`;
        }

        let res = '⏳ متبقي ';
        if (diffDays > 0) res += `${diffDays} يوم `;
        if (diffHours > 0 || diffDays === 0) res += `${diffHours} ساعة `;
        if (diffDays === 0 && diffHours < 10 && diffMinutes > 0) res += `و ${diffMinutes} دقيقة`;
        return res.trim();
      }

      // ═══════════════════════════════════════════
      //  التحديث التلقائي الذكي للوقت المتبقي
      // ═══════════════════════════════════════════
      function updateAllRemainingTimes() {
        const elements = document.querySelectorAll('.remaining-time[data-exam-datetime]');
        let minRemainingMs = Infinity;

        elements.forEach(el => {
          const iso = el.getAttribute('data-exam-datetime');
          if (!iso) return;
          const examDate = new Date(iso);
          if (isNaN(examDate.getTime())) return;
          const remainingMs = examDate - simulatedNow();  // استخدام المحاكاة
          el.innerHTML = getRemainingTimeFromDate(examDate);
          if (remainingMs > 0 && remainingMs < minRemainingMs) {
            minRemainingMs = remainingMs;
          }
        });

        if (minRemainingMs === Infinity || minRemainingMs <= 0) {
          clearTimeout(remainingTimer);
          remainingTimer = null;
          return;
        }

        let delay;
        if (minRemainingMs <= 60000) {
          delay = 1000;
        } else if (minRemainingMs <= 3600000) {
          delay = 60000;
        } else {
          delay = 3600000;
        }

        clearTimeout(remainingTimer);
        remainingTimer = setTimeout(updateAllRemainingTimes, delay);
      }

      function startRemainingUpdates() {
        clearTimeout(remainingTimer);
        updateAllRemainingTimes();
      }

      function clearAutoCommit() {
  if (autoCommitTimer) {
    clearTimeout(autoCommitTimer);
    autoCommitTimer = null;
  }
}

function scheduleAutoCommit(query) {
  clearAutoCommit();
  // إذا لم يكن هناك نتائج، أو تم commit مسبقاً، لا داعي
  if (!query || query === committedQuery) return;
  autoCommitTimer = setTimeout(() => {
    // تأكد أن النص لا يزال نفسه ولم يتغير
    if (searchInput.value.trim() === query && query !== committedQuery) {
      doCommitSearch(query);
    }
  }, 1500); // ثانية ونصف بعد آخر توقف
}

      // ═══════════════════════════════════════════
      //  البحث المباشر (live) بدون زيادة العداد
      // ═══════════════════════════════════════════
function doLiveSearch(query) {
  clearAutoCommit(); // ⬅️ أضف هذا السطر
  if (!query) {
    resultsDiv.innerHTML = '';
    exportContainer.style.display = 'none';
    currentStudentData = null;
    clearTimeout(remainingTimer);
    return;
  }
  fetch(`search.php?q=${encodeURIComponent(query)}`)
    .then(r => r.json())
    .then(data => {
      renderResults(data);
      scheduleAutoCommit(query); // ⬅️ أضف هذا السطر
    });
}
      // ═══════════════════════════════════════════
      //  البحث المثبت (commit) - يزيد العداد
      // ═══════════════════════════════════════════
function doCommitSearch(query) {
  if (!query || commitBlocked || query === committedQuery) return;
  commitBlocked = true;
  fetch(`search.php?q=${encodeURIComponent(query)}&commit=1&client_id=${encodeURIComponent(CLIENT_ID)}`)
    .then(r => r.json())
    .then(data => {
      // 1. نضيف السجل أولًا (بغض النظر عن تطابق النتائج)
      addToHistory(query);

      // 2. إن كانت النتيجة المعروضة بالفعل هي نفسها القادمة من الخادم
      if (currentStudentData && data.results.length > 0) {
        const newFirst = data.results[0];
        if (newFirst.number === currentStudentData.number &&
            newFirst.name   === currentStudentData.name) {
          committedQuery = query;        // تحديث committedQuery
          loadCounter();                 // تحديث العداد
          setTimeout(() => { commitBlocked = false; }, 500);
          return;                        // خروج بدون إعادة رسم
        }
      }

      // 3. وإلا نعرض النتائج الجديدة
      renderResults(data);
      committedQuery = query;
      loadCounter();
      setTimeout(() => { commitBlocked = false; }, 500);
    })
    .catch(() => { commitBlocked = false; });
}      // ═══════════════════════════════════════════
      //  عرض النتائج (مع الوقت المتبقي)
      // ═══════════════════════════════════════════
      function renderResults(data) {
        resultsDiv.innerHTML = '';
        if (!data.results.length) {
          resultsDiv.innerHTML = `<div class="no-result">لا يوجد نتائج</div>`;
          exportContainer.style.display = 'none';
          currentStudentData = null;
          clearTimeout(remainingTimer);
          return;
        }
        currentStudentData = data.results[0];
        const fragment = document.createDocumentFragment();
        data.results.forEach(item => {
          const card = document.createElement('div');
          card.className = 'card';
          const coursesHtml = item.courses.map(course => {
            let examHtml = '';
            if (course.exam) {
              const examDateObj = parseExamDateTime(course.exam.date, course.exam.time);
              const remaining = examDateObj ? getRemainingTimeFromDate(examDateObj) : 'وقت الامتحان غير معروف';
              const examDateTimeISO = examDateObj ? examDateObj.toISOString() : '';
              examHtml = `
                <div class="exam-details">
                  <span>🔢 لجنة ${course.exam.committee}</span>
                  <span>📍 ${course.exam.room}</span>
                  <span>📅 ${course.exam.day} ${course.exam.date}</span>
                  <span>🕒 ${course.exam.period} (${course.exam.time})</span>
                </div>
                <div class="remaining-time" data-exam-datetime="${examDateTimeISO}">${remaining}</div>`;
            } else {
              examHtml = `<div class="no-exam">لم تحدد اللجنة بعد</div>`;
            }
            const titleHtml = course.driveLink
              ? `<a href="${course.driveLink}" target="_blank" style="color:#60a5fa;text-decoration:none;">📘 ${course.name} (${course.code})</a>`
              : `📘 ${course.name} (${course.code})`;
            return `<div class="course-item"><div class="course-name">${titleHtml}</div>${examHtml}</div>`;
          }).join('') || `<div>لا توجد مواد مسجلة</div>`;
          card.innerHTML = `
            <div class="name">${item.name}</div>
            <div class="number">الرقم: ${item.number}</div>
            <div>عدد المواد: ${item.courses.length}</div>
            ${coursesHtml}`;
          fragment.appendChild(card);
        });
        resultsDiv.appendChild(fragment);
        exportContainer.style.display = 'block';
        startRemainingUpdates();
      }

      // ═══════════════════════════════════════════
      //  أحداث حقل البحث (live + commit)
      // ═══════════════════════════════════════════
      // Polling كل 200ms لالتقاط الإكمال التلقائي
      setInterval(() => {
        const val = searchInput.value.trim();
        if (val === lastPolledValue) return;
        lastPolledValue = val;
        clearBtn.style.display = val ? 'block' : 'none';
        clearTimeout(debounceTimer);
        if (val === '') {
          committedQuery = '';
          resultsDiv.innerHTML = '';
          exportContainer.style.display = 'none';
          currentStudentData = null;
          clearTimeout(remainingTimer);
          clearAutoCommit();
          return;
        }
        debounceTimer = setTimeout(() => doLiveSearch(val), 300);
      }, 200);

      // Enter -> commit
      searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
          e.preventDefault();
          const val = this.value.trim();
          if (val && val !== committedQuery) {
            doCommitSearch(val);
            this.blur();
          }
        }
      });

      // Blur -> commit إذا كان النص قد تغير
searchInput.addEventListener('blur', function() {
  if (suppressBlurCommit) return;   // ← امنع commit لو الزر هو السبب

  const val = this.value.trim();
  if (val && val !== committedQuery && !commitBlocked) {
    doCommitSearch(val);
  }
});
      // ═══════════════════════════════════════════
      //  تصدير صورة (بدون الوقت المتبقي)
      // ═══════════════════════════════════════════
      function exportAsImage() {
        if (!currentStudentData) return;

        const exportCard = document.getElementById('export-card');
        const student = currentStudentData;

        let coursesExportHtml = '';
        student.courses.forEach(course => {
          let examHtml = '';
          if (course.exam) {
            examHtml = `
              <div class="export-exam-row">
                <span>🔢 لجنة ${course.exam.committee}</span>
                <span>📍 ${course.exam.room}</span>
                <span>📅 ${course.exam.day} ${course.exam.date}</span>
                <span>🕒 ${course.exam.period} (${course.exam.time})</span>
              </div>`;
          } else {
            examHtml = `<div class="no-exam-export">لم تحدد اللجنة بعد</div>`;
          }

          coursesExportHtml += `
            <div class="export-course">
              <div class="export-course-name">📘 ${course.name} (${course.code})</div>
              ${examHtml}
            </div>`;
        });

        exportCard.innerHTML = `
          <div class="export-header">
            <div class="export-name">${student.name}</div>
            <div class="export-number">الرقم الأكاديمي: ${student.number}</div>
            <div style="color:#94a3b8; margin-top:5px;">عدد المواد: ${student.courses.length}</div>
          </div>
          ${coursesExportHtml || '<div style="text-align:center; color:#94a3b8;">لا توجد مواد مسجلة</div>'}
          <div class="watermark">StudentsCourses V2 · Developed by Ali Ashraf</div>
        `;

        html2canvas(exportCard, {
          backgroundColor: null,
          scale: 2,
          useCORS: true,
          allowTaint: true
        }).then(canvas => {
          const link = document.createElement('a');
          link.download = `student_${student.number}_courses.png`;
          link.href = canvas.toDataURL('image/png');
          link.click();
        }).catch(err => {
          console.error('فشل في تصدير الصورة:', err);
          alert('حدث خطأ أثناء تصدير الصورة. حاول مرة أخرى.');
        });
      }

      function goDashboard() {
        window.location.href = "dashboard.html";
      }

      // تهيئة سجل البحث
      renderHistory();
    </script>
  </body>
</html>