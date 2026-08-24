// ══════════════════════════════════════════════════
      //  الإعدادات
      // ══════════════════════════════════════════════════
      const DEFAULT_EXAM_DURATION_HOURS = 2;

      // ════════ محاكاة الوقت للتجارب (احذف هذا القسم بالكامل بعد الاختبار) ════════
      const SIMULATION_ENABLED = false; //  سطر محاكاة - احذف بعد الاختبار
      // const SIMULATION_START = new Date("2026-06-29T9:29:00"); //  سطر محاكاة - احذف بعد الاختبار (حدد وقت البداية هنا)
      const SIMULATION_START = new Date("2026-06-01T13:59:50"); //  سطر محاكاة - احذف بعد الاختبار (حدد وقت البداية هنا)
      const SIMULATION_PAGE_LOAD_REAL_TIME = Date.now(); //  سطر محاكاة - احذف بعد الاختبار
      function getSimulatedNow() { //  دالة محاكاة - احذف بعد الاختبار
          if (!SIMULATION_ENABLED) return new Date();
          const elapsed = Date.now() - SIMULATION_PAGE_LOAD_REAL_TIME; //  سطر محاكاة - احذف بعد الاختبار
          return new Date(SIMULATION_START.getTime() + elapsed); //  سطر محاكاة - احذف بعد الاختبار
      }
      // ════════ نهاية قسم المحاكاة ════════

      function refreshPage() {
        location.reload(true);
      }

      (function initSecretStatsShortcut() {
        const trigger = document.getElementById("secretStatsTrigger");
        if (!trigger) return;

        let clickCount = 0;
        let resetTimer = null;

        trigger.addEventListener("click", () => {
          clickCount++;
          clearTimeout(resetTimer);

          if (clickCount >= 5) {
            window.location.href = "/stats";
            return;
          }

          resetTimer = setTimeout(() => {
            clickCount = 0;
          }, 2000);
        });
      })();

      let suppressBlurCommit = false;
      const MIN_QUERY_LENGTH = 3;
      const LIVE_DEBOUNCE_MS = 450;
      const AUTO_COMMIT_MS = 1500;
      const MAX_RESULTS = 15;

      const HISTORY_KEY = "search_history";
      const MAX_HISTORY = 10;
      const PINNED_COURSE_KEY = "pinned_course_card_v1";

      function goCourses() {
        window.location.href = "/courses";
      }

      function goQA() {
        window.location.href = "/qa";
      }

      function goGPA() {
        window.location.href = "/gpa";
      }

      function loadHistory() {
        const raw = localStorage.getItem(HISTORY_KEY);
        return raw ? JSON.parse(raw) : [];
      }
      function saveHistory(h) {
        localStorage.setItem(HISTORY_KEY, JSON.stringify(h));
      }
      function addToHistory(query) {
        if (!query) return;
        let h = loadHistory();
        h = h.filter((item) => !query.startsWith(item));
        const isPrefixOfExisting = h.some((item) => item.startsWith(query));
        if (isPrefixOfExisting) {
          saveHistory(h);
          renderHistory();
          return;
        }
        h = h.filter((item) => item !== query);
        h.unshift(query);
        if (h.length > MAX_HISTORY) h.pop();
        saveHistory(h);
        renderHistory();
      }
      function deleteHistoryItem(query) {
        saveHistory(loadHistory().filter((item) => item !== query));
        renderHistory();
      }
      function clearHistory() {
        localStorage.removeItem(HISTORY_KEY);
        renderHistory();
      }

      function renderHistory() {
        const listEl = document.getElementById("history-list");
        const historyDiv = document.getElementById("history");
        const h = loadHistory();
        if (h.length === 0) {
          historyDiv.style.display = "none";
          return;
        }
        historyDiv.style.display = "block";
        listEl.innerHTML = h
          .map(
            (item) => `
          <div class="history-item">
            <span class="history-text" data-query="${escapeHTML(item)}">${escapeHTML(item)}</span>
            <button class="history-delete" data-query="${escapeHTML(item)}" title="حذف من السجل" aria-label="حذف">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
          </div>`
          )
          .join("");
        listEl.querySelectorAll(".history-text").forEach((el) => {
          el.addEventListener("click", function () {
            const q = this.dataset.query;
            searchInput.value = q;
            lastPolledValue = q;
            clearBtn.style.display = "flex";
            doCommitSearch(q);
          });
        });
        listEl.querySelectorAll(".history-delete").forEach((btn) => {
          btn.addEventListener("click", function (e) {
            e.stopPropagation();
            deleteHistoryItem(this.dataset.query);
          });
        });
      }
      document.addEventListener("click", (e) => {
        if (e.target && e.target.id === "clear-history") clearHistory();
      });

      function getClientId() {
        let id = localStorage.getItem("client_id");
        if (!id) {
          id = "client_" + Math.random().toString(36).substr(2, 9) + Date.now();
          localStorage.setItem("client_id", id);
        }
        return id;
      }
      const CLIENT_ID = getClientId();

      const searchInput = document.getElementById("search");
      const clearBtn = document.getElementById("clearSearchBtn");
      const yearSelect = document.getElementById("academicYear");
      const summerTermNotice = document.getElementById("summer-term-notice");
      const resultsDiv = document.getElementById("results");
      const exportContainer = document.getElementById("export-container");
      const pinnedCourseDiv = document.getElementById("pinned-course");
      const toast = document.getElementById("toast");

      let debounceTimer = null;
      let autoCommitTimer = null;
      let committedQuery = "";
      let lastPolledValue = "";
      let currentStudentData = null;
      let commitBlocked = false;
      let activeFetchCtrl = null;
      let remainingTimer = null;
      const YEAR_STORAGE_KEY = "selected_academic_year";
      let selectedYear = localStorage.getItem(YEAR_STORAGE_KEY) || "";

      function updateSummerTermNotice() {
        if (!summerTermNotice) return;
        const isSummerTerm = (yearSelect?.value || selectedYear) === "2025_2026_3";
        summerTermNotice.hidden = !isSummerTerm;
      }

      function loadCounter() {
        fetch("/counterFiles/counter?counter=users", { cache: "no-store" })
          .then((r) => r.json())
          .then((d) => {
            const el = document.getElementById("visitCount");
            if (el) el.innerText = d.count ?? 0;
          })
          .catch(() => {
            const el = document.getElementById("visitCount");
            if (el) el.innerText = "--";
          });
      }
      loadCounter();
      setInterval(loadCounter, 4000);

      function toggleClearButton() {
        clearBtn.style.display = searchInput.value.trim() ? "flex" : "none";
      }

      function escapeHTML(value) {
        const map = { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#039;" };
        return String(value ?? "").replace(/[&<>"']/g, (char) => map[char]);
      }

      function formatExamRoomHtml(room) {
        if (String(room || "").trim() === "اضغط هنا") {
          return `<a class="location-link" href="images/locationV2.jpg" target="_blank" rel="noopener noreferrer">اضغط هنا</a>`;
        }
        return escapeHTML(room);
      }

      function formatExamRoomText(room) {
        if (String(room || "").trim() === "اضغط هنا") {
          return "اضغط هنا: images/locationV2.jpg";
        }
        return room || "-";
      }

      function hasExamValue(value) {
        const normalized = String(value ?? "").trim();
        if (!normalized || normalized === "-") return false;
        if (/غير معروف|غير محدد|المكان غير محدد|^غير$|^معروف$|الاسم|الرقم/.test(normalized)) return false;
        return normalized.replace(/[()\s]/g, "") !== "";
      }

      function formatCourseDisplayName(name) {
        return String(name || "")
          .replace(/المشروع\s*([0-9٠-٩]+)/g, "المشروع $1")
          .trim();
      }

      function formatCourseCodeDisplay(code) {
        return String(code || "").replace(/[.\s]+$/g, "").trim();
      }

      function showToast(message) {
        if (!toast) return;
        toast.textContent = message;
        toast.classList.add("show");
        clearTimeout(toast._timer);
        toast._timer = setTimeout(() => toast.classList.remove("show"), 2600);
      }

      function buildCourseExamHtml(course) {
        if (!course.exam) return `<div class="no-exam">لم تحدد اللجنة بعد</div>`;

        const exam = course.exam;
        const detailLines = [];
        let range = null;

        if (hasExamValue(exam.committee)) {
          detailLines.push(`<span>اللجنة: ${escapeHTML(exam.committee)}</span>`);
        }
        if (hasExamValue(exam.room)) {
          detailLines.push(`<span>المكان: ${formatExamRoomHtml(exam.room)}</span>`);
        }

        const dateText = hasExamValue(exam.date)
          ? [exam.day, exam.date].filter(hasExamValue).join(" ")
          : "";
        if (dateText) {
          detailLines.push(`<span>التاريخ: ${escapeHTML(dateText)}</span>`);
        }

        const timeText = hasExamValue(exam.time) && hasExamValue(exam.period)
          ? `${exam.period} ${exam.time}`
          : hasExamValue(exam.time) ? String(exam.time).trim() : "";
        if (timeText) {
          detailLines.push(`<span>الوقت: ${escapeHTML(timeText)}</span>`);
        }

        range = parseExamTimeRange(exam.date, exam.time, exam.period);
        if (!detailLines.length && !range) {
          return `<div class="no-exam">لم تحدد اللجنة بعد</div>`;
        }

        const statusHtml = range
          ? `<div class="remaining-time" data-exam-start="${range.start.toISOString()}" data-exam-end="${range.end.toISOString()}">
              ${getExamStatusText(range.start, range.end)}
            </div>`
          : "";

        return `
          ${detailLines.length ? `<div class="exam-details">${detailLines.join("")}</div>` : ""}
          ${statusHtml}`;
      }

      function getExamPlainStatus(course) {
        if (!course.exam) return "لم تحدد اللجنة بعد";
        const range = parseExamTimeRange(course.exam.date, course.exam.time, course.exam.period);
        if (!range) return "وقت الامتحان غير معروف";
        const now = getSimulatedNow();
        if (now >= range.end) return "منتهي";
        if (now >= range.start && now < range.end) {
          return `الامتحان الآن - ${formatTimeRemaining(range.end - now).text}`;
        }
        return formatTimeRemaining(range.start - now).text;
      }

      function buildCourseSharePayload(student, course) {
        const title = `${course.name || "مادة"}`;
        const lines = [
          student?.name ? `الطالب: ${student.name}` : "",
          `المادة: ${title}`,
        ];

        if (course.exam) {
          const exam = course.exam;
          const dateText = hasExamValue(exam.date)
            ? [exam.day, exam.date].filter(hasExamValue).join(" ")
            : "";
          const timeText = hasExamValue(exam.time)
            ? [exam.period, exam.time].filter(hasExamValue).join(" ")
            : "";

          if (hasExamValue(exam.committee)) lines.push(`اللجنة: ${exam.committee}`);
          if (dateText) lines.push(`التاريخ: ${dateText}`);
          if (timeText) lines.push(`الوقت: ${timeText}`);
          if (hasExamValue(exam.room)) {
            lines.push(`المكان: ${formatExamRoomText(exam.room)}`);
          }
        } else {
          lines.push("لم تحدد اللجنة بعد");
        }

        return { title, text: lines.filter(Boolean).join("\n") };
      }

      async function shareCourse(student, course) {
        if (!course) return;
        const payload = buildCourseSharePayload(student, course);

        try {
          if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(payload.text);
          } else {
            const textarea = document.createElement("textarea");
            textarea.value = payload.text;
            textarea.setAttribute("readonly", "");
            textarea.style.position = "fixed";
            textarea.style.top = "-9999px";
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand("copy");
            textarea.remove();
          }
          showToast("تم نسخ نص المادة");
        } catch (err) {
          console.error("Copy failed:", err);
          showToast("تعذر نسخ النص");
        }
      }

      function getCoursePinId(student, course) {
        return `${student?.number || "student"}__${course?.code || "course"}`;
      }

      function normalizePinnedCourse(course) {
        return {
          name: course.name || "",
          code: course.code || "",
          driveLink: course.driveLink || "",
          exam: course.exam ? {
            committee: course.exam.committee || "",
            room: course.exam.room || "",
            day: course.exam.day || "",
            date: course.exam.date || "",
            period: course.exam.period || "",
            time: course.exam.time || "",
          } : null,
        };
      }

      function normalizePinnedCourses(courses) {
        return (courses || []).map((course) => normalizePinnedCourse(course));
      }

      function getUpcomingCourseFromCourses(courses) {
        const now = getSimulatedNow();
        const entries = (courses || [])
          .filter((course) => course.exam)
          .map((course) => {
            const range = parseExamTimeRange(course.exam.date, course.exam.time, course.exam.period);
            if (!range) return null;
            return { course, start: range.start, end: range.end };
          })
          .filter(Boolean);

        const activeExam = entries.find((entry) => now >= entry.start && now < entry.end);
        const futureExam = entries.filter((entry) => entry.start > now).sort((a, b) => a.start - b.start)[0];
        return (activeExam || futureExam || null)?.course || null;
      }

      function isPinnedCourseUpcoming(pinned) {
        const course = pinned?.course;
        const courses = pinned?.courses || [];
        if (!course || !courses.length) return false;
        const upcomingCourse = getUpcomingCourseFromCourses(courses);
        return !!upcomingCourse && upcomingCourse.code === course.code;
      }

      function updatePinnedUpcomingVisual() {
        if (!pinnedCourseDiv) return;
        const card = pinnedCourseDiv.querySelector(".pinned-course-card");
        if (!card) return;

        const isUpcomingPinned = isPinnedCourseUpcoming(loadPinnedCourse());
        card.classList.toggle("pinned-upcoming", isUpcomingPinned);
      }

      function loadPinnedCourse() {
        try {
          const raw = localStorage.getItem(PINNED_COURSE_KEY);
          return raw ? JSON.parse(raw) : null;
        } catch (_) {
          return null;
        }
      }

      function isPinnedCourse(student, course) {
        const pinned = loadPinnedCourse();
        return !!pinned && pinned.id === getCoursePinId(student, course);
      }

      function updatePinButtons() {
        document.querySelectorAll(".pin-course-btn").forEach((btn) => {
          const card = btn.closest(".card");
          const courseItem = btn.closest(".course-item");
          const courseIndex = Number(courseItem?.dataset.courseIndex);
          const student = card?.__studentData;
          const course = student?.courses?.[courseIndex];
          const pinned = student && course && isPinnedCourse(student, course);
          btn.classList.toggle("is-pinned", !!pinned);
          btn.textContent = pinned ? "مثبت" : "تثبيت";
          btn.title = pinned ? "إلغاء تثبيت المادة" : "تثبيت المادة";
        });
      }

      function renderPinnedCourse() {
        const pinned = loadPinnedCourse();
        if (!pinned || !pinnedCourseDiv) {
          if (pinnedCourseDiv) {
            pinnedCourseDiv.style.display = "none";
            pinnedCourseDiv.innerHTML = "";
          }
          return;
        }

        const student = pinned.student || {};
        const course = pinned.course || {};
        const isUpcomingPinned = isPinnedCourseUpcoming(pinned);
        const courseTitle = course.driveLink
          ? `<a href="${escapeHTML(course.driveLink)}" target="_blank" style="color:#60a5fa;text-decoration:none;">${escapeHTML(course.name)} (${escapeHTML(course.code)})</a>`
          : `${escapeHTML(course.name)} (${escapeHTML(course.code)})`;

        pinnedCourseDiv.style.display = "block";
        pinnedCourseDiv.innerHTML = `
          <div class="pinned-course-label">المادة المثبتة</div>
          <div class="pinned-course-card${isUpcomingPinned ? " pinned-upcoming" : ""}">
            <div class="pinned-header">
              <div>
                <div class="pinned-title">${courseTitle}</div>
                <div class="pinned-meta">${escapeHTML(student.name || "")}${student.number ? ` - الرقم: ${escapeHTML(student.number)}` : ""}</div>
              </div>
              <div class="pinned-actions">
                <button type="button" class="course-action-btn pinned-share-btn">نسخ</button>
                <button type="button" class="course-action-btn pinned-remove-btn">إلغاء التثبيت</button>
              </div>
            </div>
            ${buildCourseExamHtml(course)}
          </div>`;

        pinnedCourseDiv.querySelector(".pinned-share-btn")?.addEventListener("click", () => shareCourse(student, course));
        pinnedCourseDiv.querySelector(".pinned-remove-btn")?.addEventListener("click", () => {
          localStorage.removeItem(PINNED_COURSE_KEY);
          renderPinnedCourse();
          updatePinButtons();
          showToast("تم إلغاء تثبيت المادة");
        });
        startRemainingUpdates();
      }

      function toggleCoursePin(student, course) {
        if (isPinnedCourse(student, course)) {
          localStorage.removeItem(PINNED_COURSE_KEY);
          renderPinnedCourse();
          updatePinButtons();
          showToast("تم إلغاء تثبيت المادة");
          return;
        }

        const payload = {
          id: getCoursePinId(student, course),
          savedAt: Date.now(),
          student: { name: student?.name || "", number: student?.number || "" },
          course: normalizePinnedCourse(course),
          courses: normalizePinnedCourses(student?.courses || []),
        };
        localStorage.setItem(PINNED_COURSE_KEY, JSON.stringify(payload));
        renderPinnedCourse();
        updatePinButtons();
        showToast("تم تثبيت المادة");
      }

      function restartPinnedTimerIfNeeded() {
        if (loadPinnedCourse()) startRemainingUpdates();
      }

      clearBtn.addEventListener("mousedown", () => {
        suppressBlurCommit = true;
      });
      clearBtn.addEventListener("click", () => {
        searchInput.value = "";
        toggleClearButton();
        lastPolledValue = "";
        committedQuery = "";
        resultsDiv.innerHTML = "";
        exportContainer.style.display = "none";
        currentStudentData = null;
        clearTimeout(remainingTimer);
        clearTimeout(debounceTimer);
        clearTimeout(autoCommitTimer);
        if (activeFetchCtrl) {
          activeFetchCtrl.abort();
          activeFetchCtrl = null;
        }
        restartPinnedTimerIfNeeded();
        searchInput.focus();
        setTimeout(() => {
          suppressBlurCommit = false;
        }, 0);
      });

      searchInput.addEventListener("input", toggleClearButton);
      toggleClearButton();

      function parseDateOnly(dateStr) {
        if (!dateStr) return null;
        const d = dateStr.trim();
        const patterns = [
          /(\d{4})[-\/](\d{1,2})[-\/](\d{1,2})/,
          /(\d{1,2})[-\/](\d{1,2})[-\/](\d{4})/,
        ];
        for (const pat of patterns) {
          const m = d.match(pat);
          if (m) {
            let y, mo, day;
            if (pat === patterns[0]) {
              y = +m[1]; mo = +m[2]; day = +m[3];
            } else {
              day = +m[1]; mo = +m[2]; y = +m[3];
            }
            return new Date(y, mo - 1, day, 0, 0, 0);
          }
        }
        return null;
      }

      function parseExamTimeRange(dateStr, timeStr, periodStr) {
        const date = parseDateOnly(dateStr);
        if (!date) return null;

        const timeRangeRegex = /(\d{1,2}):(\d{2})\s*-\s*(\d{1,2}):(\d{2})/;
        const match = timeStr ? timeStr.match(timeRangeRegex) : null;

        let startH, startM, endH, endM;
        if (match) {
          startH = +match[1]; startM = +match[2]; endH = +match[3]; endM = +match[4];
        } else {
          const singleTime = timeStr ? timeStr.match(/(\d{1,2}):(\d{2})/) : null;
          if (singleTime) {
            startH = +singleTime[1]; startM = +singleTime[2];
            const endDate = new Date(date);
            endDate.setHours(startH, startM, 0, 0);
            endDate.setTime(endDate.getTime() + DEFAULT_EXAM_DURATION_HOURS * 3600000);
            const startDate = new Date(date);
            startDate.setHours(startH, startM, 0, 0);
            return { start: startDate, end: endDate };
          }
          return null;
        }

        const periodLower = (periodStr || "").toLowerCase();
        const isAM = /صباح|ص|am|الأولى/i.test(periodLower);
        const isPM = /مساء|م|pm|الثانية/i.test(periodLower);

        if (isAM) {
          if (startH === 12) startH = 0;
          if (endH === 12) endH = 0;
        } else if (isPM) {
          if (startH !== 12) startH += 12;
          if (endH !== 12) endH += 12;
        } else {
          if (endH < startH) endH += 12;
        }

        const startDate = new Date(date);
        startDate.setHours(startH, startM, 0, 0);
        const endDate = new Date(date);
        endDate.setHours(endH, endM, 0, 0);

        return { start: startDate, end: endDate };
      }

      function formatTimeRemaining(diffMs) {
        if (diffMs <= 0) return { text: "انتهى", interval: null };
        const seconds = Math.floor(diffMs / 1000);
        const minutes = Math.floor(seconds / 60);
        const hours = Math.floor(minutes / 60);
        const days = Math.floor(hours / 24);
        let text = "متبقي ";
        let interval;
        if (days > 0) {
          const remainingHours = hours % 24;
          text += `${days} ${days === 1 ? "يوم" : "أيام"}`;
          if (remainingHours > 0) {
            text += ` و ${remainingHours} ${remainingHours === 1 ? "ساعة" : "ساعات"}`;
          }
          interval = 3600000;
        } else if (hours > 0) {
          const remainingMinutes = minutes % 60;
          text += `${hours} ${hours === 1 ? "ساعة" : "ساعات"}`;
          if (remainingMinutes > 0) {
            text += ` و ${remainingMinutes} ${remainingMinutes === 1 ? "دقيقة" : "دقائق"}`;
          }
          interval = 60000;
        } else if (minutes > 0) {
          const remainingSeconds = seconds % 60;
          text += `${minutes} ${minutes === 1 ? "دقيقة" : "دقائق"}`;
          if (remainingSeconds > 0) {
            text += ` و ${remainingSeconds} ${remainingSeconds === 1 ? "ثانية" : "ثواني"}`;
          }
          interval = 1000;
        } else {
          text += `${seconds} ${seconds === 1 ? "ثانية" : "ثواني"}`;
          interval = 1000;
        }
        return { text, interval };
      }

      function getExamStatusText(examStart, examEnd) {
        const now = getSimulatedNow();
        if (now >= examEnd) {
          return '<span class="status ended">منتهي</span>';
        }
        if (now >= examStart && now < examEnd) {
          const diffMs = examEnd - now;
          const { text } = formatTimeRemaining(diffMs);
          return `<span class="status live">الامتحان الآن - ${text}</span>`;
        }
        const diffMs = examStart - now;
        const { text } = formatTimeRemaining(diffMs);
        return `<span class="status upcoming">${text}</span>`;
      }

      function refreshCardUpcoming(card) {
        const studentData = card.__studentData;
        const examEntries = card.__examEntries;
        if (!studentData || !examEntries) return;

        const now = getSimulatedNow();
        const activeExam = examEntries.find(e => now >= e.start && now < e.end);
        const futureExams = examEntries.filter(e => e.start > now).sort((a, b) => a.start - b.start);
        const newUpcoming = activeExam || futureExams[0] || null;

        card.querySelectorAll('.course-item').forEach(el => el.classList.remove('upcoming'));

        if (newUpcoming) {
          const courseItems = card.querySelectorAll('.course-item');
          courseItems.forEach(item => {
            const codeEl = item.querySelector('.course-name');
            if (codeEl) {
              const codeText = codeEl.textContent || codeEl.innerText;
              if (codeText.includes(newUpcoming.course.code)) {
                item.classList.add('upcoming');
              }
            }
          });
        }
      }

      function updateAllRemainingTimes() {
        clearTimeout(remainingTimer);
        const elements = document.querySelectorAll(".remaining-time[data-exam-start]");
        const now = getSimulatedNow().getTime();
        let nextUpdateInterval = Infinity;
        let anyChange = false;

        elements.forEach(el => {
          const startStr = el.dataset.examStart;
          const endStr = el.dataset.examEnd;
          if (!startStr || !endStr) return;
          const start = new Date(startStr);
          const end = new Date(endStr);
          if (isNaN(start) || isNaN(end)) return;

          let diffMs, statusHtml, interval;
          if (now >= end) {
            statusHtml = '<span class="status ended">منتهي</span>';
            interval = null;
          } else if (now >= start && now < end) {
            diffMs = end - now;
            const { text, interval: intv } = formatTimeRemaining(diffMs);
            statusHtml = `<span class="status live">الامتحان الآن - ${text}</span>`;
            interval = intv;
          } else {
            diffMs = start - now;
            const { text, interval: intv } = formatTimeRemaining(diffMs);
            statusHtml = `<span class="status upcoming">${text}</span>`;
            interval = intv;
          }
          el.innerHTML = statusHtml;
          if (interval && interval < nextUpdateInterval) {
            nextUpdateInterval = interval;
          }
        });

        const cards = document.querySelectorAll('.card');
        cards.forEach(card => {
          const upcomingItem = card.querySelector('.course-item.upcoming');
          if (upcomingItem) {
            const remainingEl = upcomingItem.querySelector('.remaining-time');
            if (remainingEl && remainingEl.innerText.includes('منتهي')) {
              refreshCardUpcoming(card);
              anyChange = true;
            }
          }
        });

        if (anyChange) {
          updateAllRemainingTimes();
          return;
        }

        updatePinnedUpcomingVisual();

        if (nextUpdateInterval !== Infinity && nextUpdateInterval > 0) {
          remainingTimer = setTimeout(updateAllRemainingTimes, nextUpdateInterval);
        }
      }

      function startRemainingUpdates() {
        clearTimeout(remainingTimer);
        updateAllRemainingTimes();
      }

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
        if (activeFetchCtrl) {
          activeFetchCtrl.abort();
          activeFetchCtrl = null;
        }
        if (!query || query.length < MIN_QUERY_LENGTH) {
          if (query === "") {
            resultsDiv.innerHTML = "";
            exportContainer.style.display = "none";
            currentStudentData = null;
            clearTimeout(remainingTimer);
            restartPinnedTimerIfNeeded();
          }
          return;
        }
        activeFetchCtrl = new AbortController();
        const yearParam = selectedYear ? `&year=${encodeURIComponent(selectedYear)}` : "";
        fetch(`search?q=${encodeURIComponent(query)}&limit=${MAX_RESULTS}${yearParam}`, {
          signal: activeFetchCtrl.signal,
          cache: "no-store"
        })
          .then((r) => r.json())
          .then((data) => {
            activeFetchCtrl = null;
            renderResults(data);
            scheduleAutoCommit(query);
          })
          .catch((err) => {
            if (err.name !== "AbortError") console.error("Live search error:", err);
            activeFetchCtrl = null;
          });
      }

      function doCommitSearch(query) {
        if (!query || commitBlocked || query === committedQuery) return;
        commitBlocked = true;
        clearTimeout(autoCommitTimer);
        if (activeFetchCtrl) {
          activeFetchCtrl.abort();
          activeFetchCtrl = null;
        }
        clearTimeout(debounceTimer);
        const yearParam = selectedYear ? `&year=${encodeURIComponent(selectedYear)}` : "";
        fetch(
          `search?q=${encodeURIComponent(query)}&limit=${MAX_RESULTS}&commit=1&client_id=${encodeURIComponent(CLIENT_ID)}${yearParam}`,
          { cache: "no-store" }
        )
          .then((r) => r.json())
          .then((data) => {
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
        resultsDiv.innerHTML = "";
        const limited = (data.results || []).slice(0, MAX_RESULTS);
        if (!limited.length) {
          resultsDiv.innerHTML = `<div class="no-result">لا يوجد نتائج</div>`;
          exportContainer.style.display = "none";
          currentStudentData = null;
          clearTimeout(remainingTimer);
          return;
        }
        currentStudentData = limited[0];
        const now = getSimulatedNow();

        const fragment = document.createDocumentFragment();

        limited.forEach((item) => {
          const studentExamEntries = (item.courses || [])
            .filter((c) => c.exam)
            .map((c) => {
              const range = parseExamTimeRange(c.exam.date, c.exam.time, c.exam.period);
              if (!range) return null;
              return { course: c, start: range.start, end: range.end };
            })
            .filter(Boolean);

          const activeExam = studentExamEntries.find((e) => now >= e.start && now < e.end);
          const futureExams = studentExamEntries.filter((e) => e.start > now).sort((a, b) => a.start - b.start);
          const upcomingExam = activeExam || futureExams[0] || null;

          const card = document.createElement("div");
          card.className = "card";
          card.__studentData = item;
          card.__examEntries = studentExamEntries;

          const coursesHtml = item.courses
            .map((course, courseIndex) => {
              const isUpcoming = upcomingExam && course === upcomingExam.course;
              const courseClass = "course-item" + (isUpcoming ? " upcoming" : "");
              const examHtml = buildCourseExamHtml(course);

              const titleHtml = course.driveLink
                ? `<a href="${escapeHTML(course.driveLink)}" target="_blank" style="color:#60a5fa;text-decoration:none;">${escapeHTML(course.name)} (${escapeHTML(course.code)})</a>`
                : `${escapeHTML(course.name)} (${escapeHTML(course.code)})`;
              const pinned = isPinnedCourse(item, course);

              return `
                <div class="${courseClass}" data-course-index="${courseIndex}">
                  <div class="course-topline">
                    <div class="course-name">${titleHtml}</div>
                    <div class="course-actions">
                      <button type="button" class="course-action-btn share-course-btn" title="نسخ نص المادة">نسخ</button>
                      <button type="button" class="course-action-btn pin-course-btn${pinned ? " is-pinned" : ""}" title="${pinned ? "إلغاء تثبيت المادة" : "تثبيت المادة"}">${pinned ? "مثبت" : "تثبيت"}</button>
                    </div>
                  </div>
                  ${examHtml}
                </div>`;
            })
            .join("") || `<div>لا توجد مواد مسجلة</div>`;

          card.innerHTML = `
            <div class="student-header">
              <div class="student-info">
                <div class="name">${escapeHTML(item.name)}</div>
                <div class="number">الرقم الأكاديمي: ${escapeHTML(item.number)}</div>
              </div>
              <div class="student-meta-badge">
                <span class="courses-count">${item.courses.length} مواد</span>
              </div>
            </div>
            <div class="student-courses-list">
              ${coursesHtml}
            </div>`;

          card.addEventListener("click", (e) => {
            const shareBtn = e.target.closest(".share-course-btn");
            const pinBtn = e.target.closest(".pin-course-btn");
            if (!shareBtn && !pinBtn) return;
            e.preventDefault();
            e.stopPropagation();
            const courseItem = e.target.closest(".course-item");
            const courseIndex = Number(courseItem?.dataset.courseIndex);
            const course = item.courses?.[courseIndex];
            if (!course) return;
            if (shareBtn) shareCourse(item, course);
            if (pinBtn) toggleCoursePin(item, course);
          });
          fragment.appendChild(card);
        });

        resultsDiv.appendChild(fragment);
        updatePinButtons();
        exportContainer.style.display = "block";
        startRemainingUpdates();
      }

      searchInput.addEventListener("input", function () {
        const val = this.value.trim();
        lastPolledValue = val;
        clearBtn.style.display = val ? "flex" : "none";
        clearTimeout(debounceTimer);
        clearTimeout(autoCommitTimer);
        if (val === "") {
          committedQuery = "";
          resultsDiv.innerHTML = "";
          exportContainer.style.display = "none";
          currentStudentData = null;
          clearTimeout(remainingTimer);
          if (activeFetchCtrl) {
            activeFetchCtrl.abort();
            activeFetchCtrl = null;
          }
          restartPinnedTimerIfNeeded();
          return;
        }
        debounceTimer = setTimeout(() => doLiveSearch(val), LIVE_DEBOUNCE_MS);
      });

      searchInput.addEventListener("keydown", function (e) {
        if (e.key === "Enter") {
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

      searchInput.addEventListener("blur", function () {
        if (suppressBlurCommit) return;
        const val = this.value.trim();
        if (val && val !== committedQuery && !commitBlocked) {
          clearTimeout(debounceTimer);
          clearTimeout(autoCommitTimer);
          doCommitSearch(val);
        }
      });

      function clearSearchResults() {
        clearTimeout(debounceTimer);
        clearTimeout(autoCommitTimer);
        if (activeFetchCtrl) {
          activeFetchCtrl.abort();
          activeFetchCtrl = null;
        }
        committedQuery = "";
        resultsDiv.innerHTML = "";
        exportContainer.style.display = "none";
        currentStudentData = null;
        clearTimeout(remainingTimer);
        restartPinnedTimerIfNeeded();
      }

      function loadAcademicYears() {
        fetch("years", { cache: "no-store" })
          .then((response) => response.json())
          .then((data) => {
            const years = Array.isArray(data.years) ? data.years : [];
            if (!years.length) {
              yearSelect.innerHTML = '<option value="">لا توجد سنوات متاحة</option>';
              yearSelect.disabled = true;
              selectedYear = "";
              return;
            }

            const selectedOption = years.find((year) => year.key === selectedYear);
            if (!selectedOption) selectedYear = years[0].key;
            localStorage.setItem(YEAR_STORAGE_KEY, selectedYear);
            yearSelect.innerHTML = years
              .map((year) => `<option value="${escapeHTML(year.key)}">${escapeHTML(year.label)}</option>`)
              .join("");
            yearSelect.value = selectedYear;
            updateSummerTermNotice();
          })
          .catch(() => {
            yearSelect.innerHTML = '<option value="">تعذر تحميل السنوات</option>';
            yearSelect.disabled = true;
          });
      }

      yearSelect?.addEventListener("change", () => {
        selectedYear = yearSelect.value;
        localStorage.setItem(YEAR_STORAGE_KEY, selectedYear);
        updateSummerTermNotice();
        clearSearchResults();
        const query = searchInput.value.trim();
        if (query.length >= MIN_QUERY_LENGTH) {
          doLiveSearch(query);
          scheduleAutoCommit(query);
        }
      });

      function exportAsImage() {
        if (!currentStudentData) return;
        const exportCard = document.getElementById("export-card");
        const student = currentStudentData;

        let coursesExportHtml = "";
        student.courses.forEach((course) => {
          const courseName = escapeHTML(formatCourseDisplayName(course.name));
          const courseCode = escapeHTML(formatCourseCodeDisplay(course.code));
          let examHtml = "";

          if (course.exam) {
            const exam = course.exam;
            const exportRows = [];
            const dateText = hasExamValue(exam.date)
              ? [exam.day, exam.date].filter(hasExamValue).join(" ")
              : "";
            const timeText = hasExamValue(exam.time)
              ? [exam.period, exam.time].filter(hasExamValue).join(" ")
              : "";

            if (hasExamValue(exam.committee)) {
              exportRows.push(`<span class="export-badge">اللجنة: ${escapeHTML(exam.committee)}</span>`);
            }
            if (hasExamValue(exam.room)) {
              const roomText = formatExamRoomHtml(exam.room);
              exportRows.push(`<span class="export-badge">المكان: ${roomText}</span>`);
            }
            if (dateText) {
              exportRows.push(`<span class="export-badge">التاريخ: ${escapeHTML(dateText)}</span>`);
            }
            if (timeText) {
              exportRows.push(`<span class="export-badge">الوقت: ${escapeHTML(timeText)}</span>`);
            }

            examHtml = exportRows.length
              ? `<div class="export-exam-row">${exportRows.join("")}</div>`
              : `<div class="no-exam-export">لم تحدد لجنة الامتحان بعد</div>`;
          } else {
            examHtml = `<div class="no-exam-export">لم تحدد لجنة الامتحان بعد</div>`;
          }

          coursesExportHtml += `
            <div class="export-course">
              <div class="export-course-header">
                <span class="export-course-title">${courseName}</span>
                <span class="export-course-code">${courseCode}</span>
              </div>
              ${examHtml}
            </div>`;
        });

        exportCard.innerHTML = `
          <div class="export-header">
            <div class="export-name">${escapeHTML(student.name)}</div>
            <div class="export-meta">
              <span>الرقم الأكاديمي: <strong>${escapeHTML(student.number)}</strong></span>
              <span>&bull;</span>
              <span>عدد المواد: <strong>${student.courses.length}</strong></span>
            </div>
          </div>
          <div class="export-courses-list">
            ${coursesExportHtml || '<div class="no-exam-export">لا توجد مواد مسجلة</div>'}
          </div>
          <div class="export-watermark">
            StudentsCourses 2026 &middot; Developed by Ali Ashraf
          </div>`;

        html2canvas(exportCard, {
          backgroundColor: "#0b1120",
          scale: 2,
          useCORS: true,
          logging: false,
          onclone: (clonedDoc) => {
            const clonedCard = clonedDoc.getElementById("export-card");
            if (clonedCard) {
              clonedCard.style.position = "static";
              clonedCard.style.display = "block";
            }
          }
        })
          .then((canvas) => {
            const link = document.createElement("a");
            link.download = `student_${student.number}_courses.png`;
            link.href = canvas.toDataURL("image/png");
            link.click();
          })
          .catch((err) => {
            console.error("فشل في تصدير الصورة:", err);
            alert("حدث خطأ أثناء تصدير الصورة. حاول مرة أخرى.");
          });
      }

      renderHistory();
      renderPinnedCourse();
      loadAcademicYears();
