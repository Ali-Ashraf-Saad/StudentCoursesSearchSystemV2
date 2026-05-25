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

    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
    <meta http-equiv="Pragma" content="no-cache" />
    <meta http-equiv="Expires" content="0" />

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

      .search-box { position: relative; }
      .search-box input {
        width: 100%; padding: 15px 50px 15px 20px; border-radius: 15px; border: none;
        outline: none; font-size: 16px; background: #1e293b; color: white;
        transition: 0.3s;
      }
      .search-box input:focus { box-shadow: 0 0 15px #3b82f6; }

      .search-box .clear-btn {
        position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
        background: none; border: none; color: #94a3b8; font-size: 22px;
        cursor: pointer; padding: 4px 8px; border-radius: 50%; line-height: 1;
        display: none; transition: background 0.2s, color 0.2s;
      }
      .search-box .clear-btn:hover {
        color: #f87171; background: rgba(255,255,255,0.1);
      }

      /* ═══ سجل البحث ═══ */
      .history {
        margin-top: 12px; background: rgba(30, 41, 59, 0.6);
        border-radius: 12px; padding: 10px; display: none;
      }
      .history-label { font-size: 13px; color: #94a3b8; margin-bottom: 8px; padding: 0 8px; }
      .history-list { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 8px; }
      .history-item {
        background: #0f172a; border-radius: 8px; padding: 4px 10px;
        display: flex; align-items: center; gap: 8px; cursor: pointer;
        transition: background 0.2s;
      }
      .history-item:hover { background: #1e3a5f; }
      .history-text { color: #e2e8f0; font-size: 14px; white-space: normal; word-break: break-word; }
      .history-delete {
        background: none; border: none; color: #94a3b8; font-size: 16px;
        cursor: pointer; padding: 0; line-height: 1; transition: color 0.2s;
        flex-shrink: 0;
      }
      .history-delete:hover { color: #f87171; }
      .history-clear {
        display: inline-block; width: auto; color: #f59e0b; font-size: 13px;
        cursor: pointer; padding: 4px 12px; border-radius: 6px;
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
      .course-item.upcoming {
        border-right-color: #ef4444;
        box-shadow: 0 0 12px rgba(239, 68, 68, 0.4);
        animation: pulse-red 2s infinite;
      }

      @keyframes pulse-red {
        0%   { box-shadow: 0 0 12px rgba(239, 68, 68, 0.4); }
        50%  { box-shadow: 0 0 24px rgba(239, 68, 68, 0.8); }
        100% { box-shadow: 0 0 12px rgba(239, 68, 68, 0.4); }
      }

      .course-name { font-weight: 600; margin-bottom: 6px; }
      .exam-details { font-size: 13px; color: #cbd5e1; display: flex; flex-wrap: wrap; gap: 15px; }
      .exam-details span { white-space: nowrap; }
      .no-exam { color: #f59e0b; font-style: italic; }
      .no-result { text-align: center; color: #94a3b8; margin-top: 20px; }
      .remaining-time { font-weight: 600; margin-top: 4px; display: block; }

      .export-btn-container { display: none; text-align: center; margin: 20px 0; }
      .export-btn {
        background: linear-gradient(135deg, #3b82f6, #1d4ed8);
        color: white; border: none; padding: 12px 30px; border-radius: 50px;
        font-size: 16px; font-weight: 600; cursor: pointer;
        transition: all 0.3s; box-shadow: 0 4px 15px rgba(59,130,246,0.4);
        display: inline-flex; align-items: center; gap: 8px;
      }
      .export-btn:hover {
        transform: translateY(-2px); box-shadow: 0 8px 25px rgba(59,130,246,0.6);
      }

      #export-card {
        position: absolute; left: -9999px; top: -9999px; width: 600px; padding: 30px;
        background: linear-gradient(145deg, #0b1120 0%, #1a1f2f 100%);
        border-radius: 24px; color: #e2e8f0; font-family: "Cairo", sans-serif;
        direction: rtl; border: 2px solid rgba(59,130,246,0.4);
        box-shadow: 0 20px 40px rgba(0,0,0,0.6);
      }
      #export-card .export-header {
        text-align: center; margin-bottom: 25px; padding-bottom: 20px;
        border-bottom: 2px solid rgba(59,130,246,0.3);
      }
      #export-card .export-name {
        font-size: 26px; font-weight: 700;
        background: linear-gradient(to left, #60a5fa, #a78bfa);
        -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        margin-bottom: 5px;
      }
      #export-card .export-number { font-size: 16px; color: #94a3b8; }
      #export-card .export-course {
        background: rgba(15,23,42,0.8); border-radius: 14px; padding: 15px;
        margin: 12px 0; border-right: 5px solid #3b82f6; backdrop-filter: blur(10px);
      }
      #export-card .export-course-name {
        font-size: 18px; font-weight: 600; margin-bottom: 8px; color: #f1f5f9;
      }
      #export-card .export-exam-row {
        display: flex; flex-wrap: wrap; gap: 15px; font-size: 14px; color: #cbd5e1;
      }
      #export-card .export-exam-row span {
        background: rgba(59,130,246,0.15); padding: 4px 12px;
        border-radius: 20px; white-space: nowrap;
      }
      #export-card .no-exam-export { color: #f59e0b; font-style: italic; font-size: 13px; margin-top: 5px; }
      #export-card .watermark { text-align: center; margin-top: 20px; font-size: 12px; color: #475569; }

      footer {
        text-align: center; padding: 15px; background: rgba(15, 23, 42, 0.9);
        color: #94a3b8; font-size: 14px;
        border-top: 1px solid rgba(148, 163, 184, 0.2); backdrop-filter: blur(10px);
      }

      /* ═══ تنسيق رابط التواصل الاحترافي ═══ */
      footer a {
        color: #60a5fa;
        text-decoration: none;
        margin: 0 5px;
        transition: color 0.2s;
      }
      footer a:hover {
        color: #93c5fd;
        text-decoration: underline;
      }

      @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
      }

      /* أزرار التنقل */
    /* ===== Buttons Base ===== */
.dashboard-btn,
.refresh-btn {
  position: fixed;
  top: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 52px;
  height: 52px;
  padding: 0 14px;
  border: none;
  border-radius: 26px; /* دائري في الحالة العادية */
  background: linear-gradient(135deg, #16537e, #cfe2f3);
  color: #000;
  cursor: pointer;
  overflow: hidden;
  box-shadow: 0 0 15px rgba(0, 200, 255, 0.35);
  transition:
    width 0.35s cubic-bezier(0.4, 0, 0.2, 1),
    border-radius 0.35s ease,
    transform 0.25s ease,
    box-shadow 0.25s ease;
  z-index: 999;
}

/* ===== Position ===== */
.dashboard-btn {
  left: 20px;
  flex-direction: row;           /* أيقونة يسار، نص يمين */
  justify-content: flex-start;
}
.refresh-btn {
  right: 20px;
  flex-direction: row-reverse;   /* أيقونة يمين، نص يسار */
  justify-content: flex-start;
}

/* ===== Icon ===== */
.dashboard-btn .icon,
.refresh-btn .icon {
  width: 24px;
  height: 24px;
  flex-shrink: 0;
  object-fit: contain;
  transition: transform 0.25s ease;
}

/* ===== Text ===== */
.dashboard-btn .text,
.refresh-btn .text {
  max-width: 0;
  opacity: 0;
  overflow: hidden;
  white-space: nowrap;
  transition:
    max-width 0.35s cubic-bezier(0.4, 0, 0.2, 1),
    opacity 0.25s ease;
}

/* مسافة النص بجانب الأيقونة */
.dashboard-btn .text {
  margin-left: 8px;
}
.refresh-btn .text {
  margin-right: 8px;
}

/* ===== Hover ===== */
.dashboard-btn:hover {
  width: 127px;
  border-radius: 14px;
  transform: translateY(-2px);
  box-shadow: 0 0 24px rgba(0, 200, 255, 0.6);
}

.refresh-btn:hover {
  width: 110px;
  border-radius: 14px;
  transform: translateY(-2px);
  box-shadow: 0 0 24px rgba(0, 200, 255, 0.6);
}

/* النص يظهر بسلاسة */
.dashboard-btn:hover .text,
.refresh-btn:hover .text {
  max-width: 110px;
  opacity: 1;
}

/* أنيميشن بسيط على الأيقونة */
.dashboard-btn:hover .icon,
.refresh-btn:hover .icon {
  transform: scale(1.08);
}

/* ===== Active ===== */
.dashboard-btn:active,
.refresh-btn:active {
  transform: scale(0.96);
}




      input:-webkit-autofill,
      input:-webkit-autofill:hover,
      input:-webkit-autofill:focus,
      input:-webkit-autofill:active {
          -webkit-box-shadow: 0 0 0 30px #1e293b inset !important;
          -webkit-text-fill-color: white !important;
          caret-color: white !important;
          border: none !important;
          transition: background-color 5000s ease-in-out 0s;
      }
    </style>
  </head>

  <body>
    <button class="dashboard-btn" onclick="goDashboard()">
      <img src="images/statisticsIcon.png" class="icon">
      <span class="text">Dashboard</span>
    </button>

    <button class="refresh-btn" onclick="refreshPage()">
      <img src="images/refresh.png" class="icon">
      <span class="text">Refresh</span>
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
        <input type="text" id="search" placeholder="اكتب الاسم أو الرقم الأكاديمي..." autocomplete="on" />
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
      © 2026 StudentsCourses · Developed by Ali Ashraf · 
      <a href="http://wa.me/+201148727448" target="_blank">Contact Me</a>
    </footer>

    <script>
      function refreshPage() { location.reload(true); }

      let suppressBlurCommit = false;
      const MIN_QUERY_LENGTH = 3;
      const LIVE_DEBOUNCE_MS = 450;
      const AUTO_COMMIT_MS   = 1500;
      const MAX_RESULTS      = 15;

      const HISTORY_KEY = 'search_history';
      const MAX_HISTORY = 10;

      function loadHistory() {
        const raw = localStorage.getItem(HISTORY_KEY);
        return raw ? JSON.parse(raw) : [];
      }
      function saveHistory(h) { localStorage.setItem(HISTORY_KEY, JSON.stringify(h)); }
      function addToHistory(query) {
        if (!query) return;
        let h = loadHistory();
        h = h.filter(item => !query.startsWith(item));
        const isPrefixOfExisting = h.some(item => item.startsWith(query));
        if (isPrefixOfExisting) { saveHistory(h); renderHistory(); return; }
        h = h.filter(item => item !== query);
        h.unshift(query);
        if (h.length > MAX_HISTORY) h.pop();
        saveHistory(h);
        renderHistory();
      }
      function deleteHistoryItem(query) {
        saveHistory(loadHistory().filter(item => item !== query));
        renderHistory();
      }
      function clearHistory() { localStorage.removeItem(HISTORY_KEY); renderHistory(); }

      function renderHistory() {
        const listEl     = document.getElementById('history-list');
        const historyDiv = document.getElementById('history');
        const h          = loadHistory();
        if (h.length === 0) { historyDiv.style.display = 'none'; return; }
        historyDiv.style.display = 'block';
        listEl.innerHTML = h.map(item => `
          <div class="history-item">
            <span class="history-text" data-query="${item}">${item}</span>
            <button class="history-delete" data-query="${item}">×</button>
          </div>`).join('');
        listEl.querySelectorAll('.history-text').forEach(el => {
          el.addEventListener('click', function () {
            const q = this.dataset.query;
            searchInput.value = q;
            lastPolledValue   = q;
            clearBtn.style.display = 'block';
            doCommitSearch(q);
          });
        });
        listEl.querySelectorAll('.history-delete').forEach(btn => {
          btn.addEventListener('click', function (e) {
            e.stopPropagation();
            deleteHistoryItem(this.dataset.query);
          });
        });
      }
      document.addEventListener('click', e => {
        if (e.target && e.target.id === 'clear-history') clearHistory();
      });

      function getClientId() {
        let id = localStorage.getItem('client_id');
        if (!id) {
          id = 'client_' + Math.random().toString(36).substr(2, 9) + Date.now();
          localStorage.setItem('client_id', id);
        }
        return id;
      }
      const CLIENT_ID = getClientId();

      const searchInput     = document.getElementById("search");
      const clearBtn        = document.getElementById("clearSearchBtn");
      const resultsDiv      = document.getElementById("results");
      const exportContainer = document.getElementById("export-container");

      let debounceTimer      = null;
      let autoCommitTimer    = null;
      let committedQuery     = '';
      let lastPolledValue    = '';
      let currentStudentData = null;
      let commitBlocked      = false;
      let activeFetchCtrl    = null;
      let remainingTimer     = null;

      function loadCounter() {
        fetch("counter.php?action=get")
          .then(r => r.json())
          .then(d => {
            const el = document.getElementById("visitCount");
            if (el) el.innerText = d.count ?? 0;
          })
          .catch(() => {
            const el = document.getElementById("visitCount");
            if (el) el.innerText = "—";
          });
      }
      loadCounter();
      setInterval(loadCounter, 5000);

      function toggleClearButton() {
        clearBtn.style.display = searchInput.value.trim() ? 'block' : 'none';
      }

      clearBtn.addEventListener('mousedown', () => { suppressBlurCommit = true; });
      clearBtn.addEventListener('click', () => {
        searchInput.value = '';
        toggleClearButton();
        lastPolledValue = '';
        committedQuery  = '';
        resultsDiv.innerHTML = '';
        exportContainer.style.display = 'none';
        currentStudentData = null;
        clearTimeout(remainingTimer);
        clearTimeout(debounceTimer);
        clearTimeout(autoCommitTimer);
        if (activeFetchCtrl) { activeFetchCtrl.abort(); activeFetchCtrl = null; }
        searchInput.focus();
        setTimeout(() => { suppressBlurCommit = false; }, 0);
      });

      searchInput.addEventListener('input', toggleClearButton);
      toggleClearButton();

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
              if (pat === patterns[0]) { y=+m[1]; mo=+m[2]; d=+m[3]; }
              else                     { d=+m[1]; mo=+m[2]; y=+m[3]; }
              h=+m[4]; mi=+m[5];
              const tp = (timeStr||'').trim();
              if (/مساء|م|pm/i.test(tp) && h<12)  h+=12;
              if (/صباح|ص|am/i.test(tp) && h===12) h=0;
              return new Date(y, mo-1, d, h, mi, 0);
            }
          }
          const dateOnly = full.match(/(\d{4})[-\/](\d{1,2})[-\/](\d{1,2})/);
          if (dateOnly) {
            const y=+dateOnly[1], mo=+dateOnly[2], d=+dateOnly[3];
            let h=0, mi=0;
            const tp=(timeStr||'').trim();
            if (tp) {
              const tm = tp.match(/(\d{1,2})[.:](\d{2})/);
              if (tm) {
                h=+tm[1]; mi=+tm[2];
                if (/مساء|م|pm/i.test(tp) && h<12)  h+=12;
                if (/صباح|ص|am/i.test(tp) && h===12) h=0;
              }
            }
            return new Date(y, mo-1, d, h, mi, 0);
          }
        } catch(e) {}
        return null;
      }

      function getRemainingTimeFromDate(examDate) {
        const now = new Date();
        const diffMs = examDate - now;
        if (diffMs <= 0) return '✅ منتهي';
        const diffDays    = Math.floor(diffMs / 86400000);
        const diffHours   = Math.floor((diffMs % 86400000) / 3600000);
        const diffMinutes = Math.floor((diffMs % 3600000)  / 60000);
        const diffSeconds = Math.floor((diffMs % 60000)    / 1000);
        let res = '⏳ متبقي ';
        if (diffDays > 0) {
          res += `${diffDays} يوم`;
          if (diffHours > 0) res += ` و ${diffHours} ساعة`;
        } else if (diffHours > 0) {
          res += `${diffHours} ساعة`;
          if (diffMinutes > 0) res += ` و ${diffMinutes} دقيقة`;
          else if (diffSeconds > 0) res += ` و ${diffSeconds} ثانية`;
        } else if (diffMinutes > 0) {
          res += `${diffMinutes} دقيقة`;
          if (diffSeconds > 0) res += ` و ${diffSeconds} ثانية`;
        } else {
          res += `${diffSeconds} ثانية`;
        }
        return res.trim();
      }

      function updateAllRemainingTimes() {
        const elements = document.querySelectorAll('.remaining-time[data-exam-datetime]');
        let nextTickMs = Infinity;
        elements.forEach(el => {
          const examDate = new Date(el.dataset.examDatetime);
          if (isNaN(examDate)) return;
          const ms = examDate - new Date();
          el.innerHTML = getRemainingTimeFromDate(examDate);
          if (ms <= 0) return;
          const diffDays    = Math.floor(ms / 86400000);
          const diffHours   = Math.floor((ms % 86400000) / 3600000);
          const diffMinutes = Math.floor((ms % 3600000) / 60000);
          let interval;
          if (diffDays > 0) {
            interval = ms % 3600000 || 3600000;
          } else if (diffHours > 0) {
            if (diffMinutes > 0) interval = ms % 60000 || 60000;
            else interval = ms % 1000 || 1000;
          } else {
            interval = ms % 1000 || 1000;
          }
          nextTickMs = Math.min(nextTickMs, interval);
        });
        clearTimeout(remainingTimer);
        if (nextTickMs !== Infinity) {
          remainingTimer = setTimeout(updateAllRemainingTimes, nextTickMs);
        }
      }

      function startRemainingUpdates() { clearTimeout(remainingTimer); updateAllRemainingTimes(); }

      function scheduleAutoCommit(query) {
        clearTimeout(autoCommitTimer);
        if (!query || query === committedQuery) return;
        autoCommitTimer = setTimeout(() => {
          if (searchInput.value.trim() === query && query !== committedQuery) {
            doCommitSearch(query);
          }
        }, AUTO_COMMIT_MS);
      }

      function doLiveSearch(query) {
        if (activeFetchCtrl) { activeFetchCtrl.abort(); activeFetchCtrl = null; }
        if (!query || query.length < MIN_QUERY_LENGTH) {
          if (query === '') {
            resultsDiv.innerHTML = '';
            exportContainer.style.display = 'none';
            currentStudentData = null;
            clearTimeout(remainingTimer);
          }
          return;
        }
        activeFetchCtrl = new AbortController();
        fetch(`search.php?q=${encodeURIComponent(query)}&limit=${MAX_RESULTS}`,
              { signal: activeFetchCtrl.signal })
          .then(r => r.json())
          .then(data => {
            activeFetchCtrl = null;
            renderResults(data);
            scheduleAutoCommit(query);
          })
          .catch(err => {
            if (err.name !== 'AbortError') console.error('Live search error:', err);
            activeFetchCtrl = null;
          });
      }

      function doCommitSearch(query) {
        if (!query || commitBlocked || query === committedQuery) return;
        commitBlocked = true;
        clearTimeout(autoCommitTimer);
        if (activeFetchCtrl) { activeFetchCtrl.abort(); activeFetchCtrl = null; }
        clearTimeout(debounceTimer);
        fetch(`search.php?q=${encodeURIComponent(query)}&limit=${MAX_RESULTS}&commit=1&client_id=${encodeURIComponent(CLIENT_ID)}`)
          .then(r => r.json())
          .then(data => {
            addToHistory(query);
            if (currentStudentData && data.results.length > 0) {
              const nf = data.results[0];
              if (nf.number === currentStudentData.number && nf.name === currentStudentData.name) {
                committedQuery = query;
                loadCounter();
                setTimeout(() => { commitBlocked = false; }, 500);
                return;
              }
            }
            renderResults(data);
            committedQuery = query;
            loadCounter();
            setTimeout(() => { commitBlocked = false; }, 500);
          })
          .catch(() => { commitBlocked = false; });
      }

      function renderResults(data) {
        resultsDiv.innerHTML = '';
        const limited = (data.results || []).slice(0, MAX_RESULTS);
        if (!limited.length) {
          resultsDiv.innerHTML = `<div class="no-result">لا يوجد نتائج</div>`;
          exportContainer.style.display = 'none';
          currentStudentData = null;
          clearTimeout(remainingTimer);
          return;
        }
        currentStudentData = limited[0];
        const fragment = document.createDocumentFragment();
        limited.forEach(item => {
          const now = new Date();
          let upcomingCourse = null;
          if (item.courses && item.courses.length) {
            const future = item.courses
              .filter(c => c.exam)
              .map(c => ({
                course: c,
                examDate: parseExamDateTime(c.exam.date, c.exam.time)
              }))
              .filter(x => x.examDate && x.examDate > now)
              .sort((a, b) => a.examDate - b.examDate);
            if (future.length) upcomingCourse = future[0].course;
          }
          const card = document.createElement('div');
          card.className = 'card';
          const coursesHtml = item.courses.map(course => {
            const isUpcoming = (upcomingCourse && course === upcomingCourse);
            const courseClass = 'course-item' + (isUpcoming ? ' upcoming' : '');
            let examHtml = '';
            if (course.exam) {
              const examDateObj     = parseExamDateTime(course.exam.date, course.exam.time);
              const remaining       = examDateObj ? getRemainingTimeFromDate(examDateObj) : 'وقت الامتحان غير معروف';
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
            return `<div class="${courseClass}"><div class="course-name">${titleHtml}</div>${examHtml}</div>`;
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

      searchInput.addEventListener('input', function () {
        const val = this.value.trim();
        lastPolledValue = val;
        clearBtn.style.display = val ? 'block' : 'none';
        clearTimeout(debounceTimer);
        clearTimeout(autoCommitTimer);
        if (val === '') {
          committedQuery = '';
          resultsDiv.innerHTML = '';
          exportContainer.style.display = 'none';
          currentStudentData = null;
          clearTimeout(remainingTimer);
          if (activeFetchCtrl) { activeFetchCtrl.abort(); activeFetchCtrl = null; }
          return;
        }
        debounceTimer = setTimeout(() => doLiveSearch(val), LIVE_DEBOUNCE_MS);
      });

      searchInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
          e.preventDefault();
          const val = this.value.trim();
          if (val && val !== committedQuery) {
            clearTimeout(debounceTimer);
            clearTimeout(autoCommitTimer);
            doCommitSearch(val);
            this.blur();
          }
        }
      });

      searchInput.addEventListener('blur', function () {
        if (suppressBlurCommit) return;
        const val = this.value.trim();
        if (val && val !== committedQuery && !commitBlocked) {
          clearTimeout(debounceTimer);
          clearTimeout(autoCommitTimer);
          doCommitSearch(val);
        }
      });

      function exportAsImage() {
        if (!currentStudentData) return;
        const exportCard = document.getElementById('export-card');
        const student    = currentStudentData;
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
          <div class="watermark">StudentsCourses V2 · Developed by Ali Ashraf</div>`;
        html2canvas(exportCard, { backgroundColor: null, scale: 2, useCORS: true, allowTaint: true })
          .then(canvas => {
            const link = document.createElement('a');
            link.download = `student_${student.number}_courses.png`;
            link.href = canvas.toDataURL('image/png');
            link.click();
          })
          .catch(err => {
            console.error('فشل في تصدير الصورة:', err);
            alert('حدث خطأ أثناء تصدير الصورة. حاول مرة أخرى.');
          });
      }

      function goDashboard() { window.location.href = "dashboard.html"; }

      renderHistory();
    </script>
  </body>
</html>