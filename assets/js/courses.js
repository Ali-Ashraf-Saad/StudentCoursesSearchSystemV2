const STORAGE_FILTER = 'courses_filter';
    const STORAGE_COMPLETED = 'completed_courses';
    const STORAGE_SEARCH_HISTORY = 'courses_search_history';
    const MAX_SEARCH_HISTORY = 10;

    const yearSelect = document.getElementById('yearSelect');
    const deptSelect = document.getElementById('deptSelect');
    const termSelect = document.getElementById('termSelect');
    const container = document.getElementById('coursesContainer');
    const overallProgressFill = document.getElementById('overallProgressFill');
    const overallProgressText = document.getElementById('overallProgressText');
    const passedHoursText = document.getElementById('passedHoursText');
    const academicLevelText = document.getElementById('academicLevelText');
    const graduationRemainingText = document.getElementById('graduationRemainingText');
    const levelRemainingText = document.getElementById('levelRemainingText');
    const reminderContainer = document.getElementById('reminderContainer');
    const toast = document.getElementById('toast');
    const clearDataBtn = document.getElementById('clearDataBtn');
    const prevYearBtn = document.getElementById('prevYearBtn');
    const nextYearBtn = document.getElementById('nextYearBtn');
    const fillPreviousBtn = document.getElementById('fillPreviousBtn');

    const openCoursesModal = document.getElementById('openCoursesModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalBody = document.getElementById('modalBody');
    const closeModalBtn = document.getElementById('closeModalBtn');
    const confirmModal = document.getElementById('confirmModal');
    const closeConfirmModalBtn = document.getElementById('closeConfirmModalBtn');
    const confirmClearBtn = document.getElementById('confirmClearBtn');
    const cancelClearBtn = document.getElementById('cancelClearBtn');

    const searchInput = document.getElementById('searchInput');
    const clearSearchBtn = document.getElementById('clearSearchBtn');
    const courseSearchSuggestions = document.getElementById('courseSearchSuggestions');
    const searchBtn = document.getElementById('searchBtn');
    const searchModal = document.getElementById('searchModal');
    const closeSearchModalBtn = document.getElementById('closeSearchModalBtn');
    const searchModalTitle = document.getElementById('searchModalTitle');
    const searchModalBody = document.getElementById('searchModalBody');
    const historyDiv = document.getElementById('history');
    const historyList = document.getElementById('history-list');
    const clearHistoryBtn = document.getElementById('clear-history');

    let allData = [];
    let courseMap = {};
    let prereqToDependents = {};
    let courseDetailsMap = {};
    let courseLocationsMap = {};
    let completedSet = new Set(JSON.parse(localStorage.getItem(STORAGE_COMPLETED) || '[]'));
    let realDept = '';
    let courseSearchOptions = [];
    let currentAutocompleteItems = [];
    let activeAutocompleteIndex = -1;
    let pendingSearchHistoryText = '';
    const SPECIALIZED_DEPTS = ['CS', 'IT', 'IS'];

    // ---------- دوال البحث المرن ----------
    function normalizeForSearch(str) {
      if (!str) return '';
      let s = str.toLowerCase();
      // إزالة التشكيل
      s = s.replace(/[\u064B-\u065F\u0670]/g, '');
      // توحيد الألفات كما في search.php
      s = s.replace(/[أإآٱ]/g, 'ا');
      // توحيد الألف المقصورة كما في search.php
      s = s.replace(/ى/g, 'ي');
      // توحيد التاء المربوطة (ة -> ه)
      s = s.replace(/ة/g, 'ه');
      // إزالة كل شيء غير الحروف العربية والإنجليزية والأرقام
      s = s.replace(/[^\u0621-\u064Aa-z0-9]/g, '');
      return s;
    }

    function isFlexibleMatch(queryNorm, targetNorm) {
      if (!queryNorm || !targetNorm) return false;
      if (targetNorm.includes(queryNorm)) return true;
      // إزالة حرف 'ه' (الناتج عن ة أو ه) من نهاية الاستعلام إن وُجد
      if (queryNorm.endsWith('ه') && queryNorm.length > 2) {
        if (targetNorm.includes(queryNorm.slice(0, -1))) return true;
      }
      // إزالة 'ه' من نهاية الهدف أيضاً
      if (targetNorm.endsWith('ه') && targetNorm.length > 2) {
        if (targetNorm.slice(0, -1).includes(queryNorm)) return true;
      }
      // الإزالة من الطرفين معاً
      if (queryNorm.endsWith('ه') && targetNorm.endsWith('ه') && queryNorm.length > 2 && targetNorm.length > 2) {
        if (targetNorm.slice(0, -1).includes(queryNorm.slice(0, -1))) return true;
      }
      return false;
    }

    function escapeHTML(value) {
      const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
      return String(value ?? '').replace(/[&<>"']/g, char => map[char]);
    }

    function loadSearchHistory() {
      try {
        const raw = localStorage.getItem(STORAGE_SEARCH_HISTORY);
        return raw ? JSON.parse(raw) : [];
      } catch (_) {
        return [];
      }
    }

    function saveSearchHistory(history) {
      localStorage.setItem(STORAGE_SEARCH_HISTORY, JSON.stringify(history));
    }

    function addToSearchHistory(query) {
      if (!query) return;
      let history = loadSearchHistory();
      history = history.filter(item => !query.startsWith(item));
      const isPrefixOfExisting = history.some(item => item.startsWith(query));
      if (isPrefixOfExisting) {
        saveSearchHistory(history);
        renderSearchHistory();
        return;
      }
      history = history.filter(item => item !== query);
      history.unshift(query);
      if (history.length > MAX_SEARCH_HISTORY) history.pop();
      saveSearchHistory(history);
      renderSearchHistory();
    }

    function deleteSearchHistoryItem(query) {
      saveSearchHistory(loadSearchHistory().filter(item => item !== query));
      renderSearchHistory();
    }

    function clearSearchHistory() {
      localStorage.removeItem(STORAGE_SEARCH_HISTORY);
      renderSearchHistory();
    }

    function toggleClearSearchButton() {
      clearSearchBtn.style.display = searchInput.value.trim() ? 'block' : 'none';
    }

    function renderSearchHistory() {
      const history = loadSearchHistory();
      historyList.innerHTML = '';

      if (!history.length) {
        historyDiv.style.display = 'none';
        return;
      }

      historyDiv.style.display = 'block';
      history.forEach(item => {
        const row = document.createElement('div');
        row.className = 'history-item';

        const text = document.createElement('span');
        text.className = 'history-text';
        text.textContent = item;
        text.addEventListener('click', () => {
          searchInput.value = item;
          toggleClearSearchButton();
          performSearch();
        });

        const del = document.createElement('button');
        del.className = 'history-delete';
        del.type = 'button';
        del.innerHTML = '&times;';
        del.addEventListener('click', (e) => {
          e.stopPropagation();
          deleteSearchHistoryItem(item);
        });

        row.appendChild(text);
        row.appendChild(del);
        historyList.appendChild(row);
      });
    }

    function populateCourseSearchSuggestions() {
      const byCode = new Map();

      allData.forEach(sem => {
        (sem.courses || []).forEach(course => {
          const code = String(course.code || '').trim();
          if (!code) return;
          const key = code.toLowerCase();
          if (byCode.has(key)) return;

          const arabic = course.arabic_name || course.name || '';
          const english = course.name || '';
          byCode.set(key, {
            code,
            arabic,
            english,
            codeNorm: code.toLowerCase().replace(/[^a-z0-9]/g, ''),
            searchNorm: normalizeForSearch(`${code} ${arabic} ${english}`)
          });
        });
      });

      courseSearchOptions = [...byCode.values()].sort((a, b) => a.code.localeCompare(b.code));
      renderAutocomplete();
    }

    function hideAutocomplete() {
      currentAutocompleteItems = [];
      activeAutocompleteIndex = -1;
      courseSearchSuggestions.classList.remove('show');
      courseSearchSuggestions.innerHTML = '';
      searchInput.setAttribute('aria-expanded', 'false');
    }

    function setActiveAutocomplete(index) {
      activeAutocompleteIndex = index;
      courseSearchSuggestions.querySelectorAll('.autocomplete-item').forEach((item, i) => {
        item.classList.toggle('active', i === activeAutocompleteIndex);
      });
    }

    function chooseAutocompleteItem(item) {
      if (!item) return;
      searchInput.value = item.code;
      pendingSearchHistoryText = item.arabic || item.english || item.code;
      toggleClearSearchButton();
      hideAutocomplete();
      performSearch();
    }

    function renderAutocomplete() {
      const query = searchInput.value.trim();
      const queryNorm = normalizeForSearch(query);
      courseSearchSuggestions.innerHTML = '';
      currentAutocompleteItems = [];
      activeAutocompleteIndex = -1;

      if (!queryNorm) {
        hideAutocomplete();
        return;
      }

      currentAutocompleteItems = courseSearchOptions
        .filter(item => item.codeNorm.includes(queryNorm) || isFlexibleMatch(queryNorm, item.searchNorm))
        .slice(0, 8);

      if (!currentAutocompleteItems.length) {
        hideAutocomplete();
        return;
      }

      const fragment = document.createDocumentFragment();
      currentAutocompleteItems.forEach((item, index) => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'autocomplete-item';
        btn.innerHTML = `
          <span class="autocomplete-names">
            <span class="autocomplete-ar">${escapeHTML(item.arabic)}</span>
            <span class="autocomplete-en">${escapeHTML(item.english)}</span>
          </span>
          <span class="autocomplete-code">${escapeHTML(item.code)}</span>
        `;
        btn.addEventListener('mousedown', (e) => e.preventDefault());
        btn.addEventListener('click', () => chooseAutocompleteItem(currentAutocompleteItems[index]));
        fragment.appendChild(btn);
      });

      courseSearchSuggestions.appendChild(fragment);
      courseSearchSuggestions.classList.add('show');
      searchInput.setAttribute('aria-expanded', 'true');
    }

    // ---------- دوال التحكم بالمودالات ----------
    function lockScroll() { document.body.classList.add('no-scroll'); }
    function unlockScroll() { 
      if (openCoursesModal.style.display === 'flex' || searchModal.style.display === 'flex' || confirmModal.style.display === 'flex') {
        document.body.classList.add('no-scroll');
      } else {
        document.body.classList.remove('no-scroll');
      }
    }

    function openConfirmModal() { confirmModal.style.display = 'flex'; lockScroll(); }
    function closeConfirmModal() { confirmModal.style.display = 'none'; unlockScroll(); }

    function showOpenCoursesModal(code) {
      const course = courseMap[code];
      if (!course) return;
      const arabicName = course.arabic_name || course.name;
      const englishName = course.name;
      const dependents = prereqToDependents[code] || [];

      modalTitle.innerHTML = `
        <span class="modal-title-main">المواد التي تفتحها</span>
        <span class="modal-title-ar">${arabicName}</span>
        <span class="modal-title-en">${englishName} (${code})</span>
      `;

      const pathGroups = {};
      const addedCodes = new Set();
      dependents.forEach(depCode => {
        if (addedCodes.has(depCode)) return;
        addedCodes.add(depCode);
        const depCourse = courseMap[depCode], pathInfo = getCoursePathInfo(depCode);
        const details = pathInfo.details || {};
        const pathLabel = pathInfo.pathLabel || (details.dept || 'عام');
        if (!pathGroups[pathLabel]) pathGroups[pathLabel] = [];
        pathGroups[pathLabel].push({
          code: depCode,
          name: depCourse ? (depCourse.arabic_name || depCourse.name) : depCode,
          english: depCourse ? depCourse.name : '',
          year: details.year || '?',
          term: details.term || '?'
        });
      });

      const currentDetails = getPreferredCourseLocation(code);
      let filterDept = null;
      if (currentDetails && (currentDetails.year === 3 || currentDetails.year === 4) && realDept && realDept !== 'عام') {
        filterDept = realDept;
      }

      Object.keys(pathGroups).forEach(d => {
        pathGroups[d].sort((a, b) => a.year - b.year || a.term - b.term);
      });

      let html = '', total = 0;
      let pathOrder = Object.keys(pathGroups).sort(comparePathLabels);
      if (filterDept) {
        pathOrder = pathOrder.filter(path => path.split('، ').includes(filterDept) || path.split('، ').includes('عام'));
      }
      pathOrder.forEach(pathLabel => {
        const courses = pathGroups[pathLabel];
        if (!courses || !courses.length) return;
        total += courses.length;
        html += `<div class="modal-dept-group"><div class="modal-dept-title">من طريق ${pathLabel} (${courses.length} مواد)</div>`;
        courses.forEach((c, i) => {
          html += `
            <div class="modal-course-item">
              <div class="modal-course-main">
                <span class="modal-course-num">${i + 1}.</span>
                <div class="modal-course-names">
                  <span class="modal-course-name-ar">${c.name}</span>
                  <span class="modal-course-name-en">${c.english}</span>
                </div>
              </div>
              <div class="modal-course-detail">
                <span>${c.code}</span><span class="year-chip year-${c.year}">سنة ${c.year}</span><span class="term-chip term-${c.term}">ترم ${c.term}</span>
              </div>
            </div>`;
        });
        html += `</div>`;
      });
      html += `<div class="modal-total">إجمالي عدد المواد التي تفتحها: <strong>${total}</strong> مادة</div>`;
      modalBody.innerHTML = html;
      openCoursesModal.style.display = 'flex';
      lockScroll();
    }

    function closeOpenCoursesModal() {
      openCoursesModal.style.display = 'none';
      unlockScroll();
    }

    function getPreferredCourseLocation(code, preferredDept = realDept) {
      const locations = courseLocationsMap[code] || [];
      if (!locations.length) return courseDetailsMap[code] || null;

      const thirdYearGeneral = locations.find(loc => loc.year === 3 && loc.dept === 'عام');
      if (thirdYearGeneral) return thirdYearGeneral;

      if (preferredDept && preferredDept !== 'عام') {
        const deptLocation = locations.find(loc => loc.dept === preferredDept);
        if (deptLocation) return deptLocation;
      }

      return locations[0];
    }

    function getCoursePathInfo(code) {
      const locations = courseLocationsMap[code] || [];
      if (!locations.length) return { details: courseDetailsMap[code] || null, pathLabel: '' };

      const preferred = getPreferredCourseLocation(code);
      const sameTermLocations = locations.filter(loc =>
        loc.year === preferred.year && loc.term === preferred.term
      );
      const deptOrder = ['عام', 'CS', 'IT', 'IS'];
      const depts = [...new Set(sameTermLocations.map(loc => loc.dept || 'عام'))]
        .sort((a, b) => deptOrder.indexOf(a) - deptOrder.indexOf(b));
      const pathLabel = depts.includes('عام') ? 'عام' : depts.join('، ');

      return {
        details: preferred,
        pathLabel
      };
    }

    function comparePathLabels(a, b) {
      const order = ['عام', 'CS', 'IT', 'IS'];
      const partsA = a.split('، ').filter(Boolean);
      const partsB = b.split('، ').filter(Boolean);
      const isGeneralA = partsA.includes('عام');
      const isGeneralB = partsB.includes('عام');
      if (isGeneralA !== isGeneralB) return isGeneralA ? -1 : 1;
      if (partsA.length !== partsB.length) return partsB.length - partsA.length;
      const rankA = partsA.reduce((sum, part, index) => sum + ((order.indexOf(part) + 1) * (index + 1)), 0);
      const rankB = partsB.reduce((sum, part, index) => sum + ((order.indexOf(part) + 1) * (index + 1)), 0);
      return rankA - rankB || a.localeCompare(b);
    }

    function clearCourseSearchInput() {
      searchInput.value = '';
      pendingSearchHistoryText = '';
      toggleClearSearchButton();
      hideAutocomplete();
    }

    function groupCourseLocations(locations) {
      const groups = new Map();
      const deptOrder = ['عام', 'CS', 'IT', 'IS'];

      (locations || []).forEach(loc => {
        const key = `${loc.year}|${loc.term}`;
        if (!groups.has(key)) groups.set(key, []);
        groups.get(key).push(loc);
      });

      return [...groups.values()].map(group => {
        const general = group.find(loc => (loc.dept || 'عام') === 'عام');
        if (general) return { ...general, dept: 'عام' };

        const depts = [...new Set(group.map(loc => loc.dept || 'عام'))]
          .sort((a, b) => deptOrder.indexOf(a) - deptOrder.indexOf(b));
        return { ...group[0], dept: depts.join('، ') };
      }).sort((a, b) =>
        (a.year || 0) - (b.year || 0) ||
        (a.term || 0) - (b.term || 0) ||
        String(a.dept || '').localeCompare(String(b.dept || ''))
      );
    }

    function performSearch() {
      const query = searchInput.value.trim();
      if (!query) return;
      hideAutocomplete();
      const normalizedQuery = normalizeForSearch(query);
      const safeQuery = escapeHTML(query);
      const results = [];
      const resultsByCode = new Map();
      const historyText = pendingSearchHistoryText || query;
      pendingSearchHistoryText = '';
      addToSearchHistory(historyText);

      for (const sem of allData) {
        for (const course of (sem.courses || [])) {
          const normalizedArabic = normalizeForSearch(course.arabic_name || course.name);
          const normalizedEnglish = normalizeForSearch(course.name || '');
          const normalizedCode = (course.code || '').toLowerCase().replace(/[^a-z0-9]/g, '');

          if (isFlexibleMatch(normalizedQuery, normalizedArabic) ||
              isFlexibleMatch(normalizedQuery, normalizedEnglish) ||
              normalizedCode.includes(normalizedQuery)) {
            const codeKey = (course.code || '').toLowerCase();
            if (resultsByCode.has(codeKey)) continue;
            const details = courseDetailsMap[course.code] || {};
            const locations = (courseLocationsMap[course.code] || [details]).filter(Boolean);
            const sortedLocations = [...locations].sort((a, b) =>
              (a.year || 0) - (b.year || 0) ||
              (a.term || 0) - (b.term || 0) ||
              String(a.dept || '').localeCompare(String(b.dept || ''))
            );
            const result = { ...course, details: details, locations: sortedLocations };
            resultsByCode.set(codeKey, result);
            results.push(result);
          }
        }
      }

      if (results.length === 0) {
        searchModalTitle.innerHTML = `لا توجد نتائج لـ "${safeQuery}"`;
        searchModalBody.innerHTML = '<div class="empty-msg">لم يتم العثور على مواد تطابق بحثك</div>';
      } else {
        searchModalTitle.innerHTML = `نتائج البحث عن "${safeQuery}" (${results.length} مادة)`;
        let html = '<div class="search-results-list">';
        results.forEach(course => {
          const rawLocations = course.locations && course.locations.length
            ? course.locations
            : [{ ...(course.details || {}), prerequisites: course.prerequisites || [] }];
          const locations = groupCourseLocations(rawLocations);
          const locationsHTML = locations.map(loc => {
            const yearText = loc.year === 1 ? 'الأولى' : loc.year === 2 ? 'الثانية' : loc.year === 3 ? 'الثالثة' : loc.year === 4 ? 'الرابعة' : '?';
            const prereqCodes = loc.prerequisites || [];
            const prereqHTML = prereqCodes.length
              ? `<div class="location-prereq"><span class="prereq-label">متطلبات:</span> ${formatPrerequisites(prereqCodes)}</div>` : ``;
              // : `<div class="location-prereq empty"><span class="prereq-label">متطلبات:</span> لا توجد</div>`;
            return `
              <div class="search-result-location-row">
                <span class="search-result-location">الفرقة ${yearText} - ${loc.termLabel || '?'} - ${loc.dept || 'عام'}</span>
                ${prereqHTML}
              </div>`;
          }).join('');

          const hasVisibleDependents = getVisibleDependentsCount(course.code) > 0;
          const openBtnHTML = hasVisibleDependents ? 
            `<button class="open-courses-btn" data-code="${course.code}">يفتح</button>` : '';

          html += `
            <div class="search-result-card">
              <div class="search-result-header">
                <div class="search-result-names">
                  <div class="search-result-ar">${course.arabic_name || course.name}</div>
                  <div class="search-result-en">${course.name}</div>
                </div>
                <div class="search-result-code">${course.code}</div>
              </div>
              <div class="search-result-locations">
                <span class="search-result-locations-label">موجودة في:</span>
                <div class="search-result-location-rows">${locationsHTML}</div>
              </div>
              ${openBtnHTML ? `<div class="search-result-footer">${openBtnHTML}</div>` : ''}
            </div>`;
        });
        html += '</div>';
        searchModalBody.innerHTML = html;

        searchModalBody.querySelectorAll('.open-courses-btn').forEach(btn => {
          btn.addEventListener('click', (e) => {
            e.stopPropagation();
            showOpenCoursesModal(btn.dataset.code);
          });
        });
      }
      searchModal.style.display = 'flex';
      lockScroll();
    }

    function closeSearchModal() {
      searchModal.style.display = 'none';
      clearCourseSearchInput();
      unlockScroll();
    }

    // ---------- ربط الأحداث ----------
    closeModalBtn.addEventListener('click', (e) => { e.stopPropagation(); closeOpenCoursesModal(); });
    openCoursesModal.addEventListener('click', (e) => { if (e.target === openCoursesModal) closeOpenCoursesModal(); });

    closeSearchModalBtn.addEventListener('click', (e) => { e.stopPropagation(); closeSearchModal(); });
    searchModal.addEventListener('click', (e) => { if (e.target === searchModal) closeSearchModal(); });

    closeConfirmModalBtn.addEventListener('click', closeConfirmModal);
    cancelClearBtn.addEventListener('click', closeConfirmModal);
    confirmModal.addEventListener('click', (e) => { if (e.target === confirmModal) closeConfirmModal(); });
    confirmClearBtn.addEventListener('click', () => {
      localStorage.removeItem(STORAGE_COMPLETED);
      localStorage.removeItem(STORAGE_FILTER);
      completedSet.clear();
      realDept = '';
      yearSelect.value = '1';
      termSelect.value = 'all';
      updateDepartmentOptions();
      saveFilterState();
      render();
      updateOverallProgress();
      buildReminders();
      closeConfirmModal();
      showToast('تم مسح جميع البيانات بنجاح');
    });

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        if (openCoursesModal.style.display === 'flex') closeOpenCoursesModal();
        else if (searchModal.style.display === 'flex') closeSearchModal();
        else if (confirmModal.style.display === 'flex') closeConfirmModal();
      }
    });

    clearSearchBtn.addEventListener('mousedown', (e) => e.preventDefault());
    clearSearchBtn.addEventListener('click', () => {
      searchInput.value = '';
      toggleClearSearchButton();
      hideAutocomplete();
      searchInput.focus();
    });
    searchInput.addEventListener('input', () => {
      toggleClearSearchButton();
      renderAutocomplete();
    });
    searchInput.addEventListener('focus', renderAutocomplete);
    searchInput.addEventListener('keydown', (e) => {
      if (!currentAutocompleteItems.length) {
        if (e.key === 'Escape') hideAutocomplete();
        return;
      }

      if (e.key === 'ArrowDown') {
        e.preventDefault();
        setActiveAutocomplete((activeAutocompleteIndex + 1) % currentAutocompleteItems.length);
      } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        setActiveAutocomplete((activeAutocompleteIndex - 1 + currentAutocompleteItems.length) % currentAutocompleteItems.length);
      } else if (e.key === 'Enter' && activeAutocompleteIndex >= 0) {
        e.preventDefault();
        chooseAutocompleteItem(currentAutocompleteItems[activeAutocompleteIndex]);
      } else if (e.key === 'Escape') {
        hideAutocomplete();
      }
    });
    document.addEventListener('click', (e) => {
      if (!e.target.closest('.search-input-wrapper')) hideAutocomplete();
    });
    clearHistoryBtn.addEventListener('click', clearSearchHistory);
    searchBtn.addEventListener('click', () => {
      hideAutocomplete();
      performSearch();
    });
    searchInput.addEventListener('keypress', (e) => {
      if (e.key === 'Enter') {
        hideAutocomplete();
        performSearch();
      }
    });
    renderSearchHistory();
    toggleClearSearchButton();

    function loadCoursesCounter() {
      fetch("/counterFiles/counter?counter=course", { cache: "no-store" })
        .then(r => r.json())
        .then(d => { document.getElementById("visitCount").innerText = d.count ?? 0; })
        .catch(() => { document.getElementById("visitCount").innerText = "--"; });
    }

    const COURSE_VISIT_KEY = "course_visit";
    const COURSE_VISIT_TTL = 1 * 60 * 1000;
    const lastCourseVisit = Number(sessionStorage.getItem(COURSE_VISIT_KEY));
    const enteredFromStats = (() => {
      if (!document.referrer) return false;
      try {
        const referrer = new URL(document.referrer);
        return referrer.origin === location.origin && /^\/stats(?:\.php)?\/?$/.test(referrer.pathname);
      } catch (_) {
        return false;
      }
    })();
    const courseVisitPromise = (!enteredFromStats && (!Number.isFinite(lastCourseVisit) || Date.now() - lastCourseVisit > COURSE_VISIT_TTL))
      ? (() => {
      sessionStorage.setItem(COURSE_VISIT_KEY, String(Date.now()));
      return fetch("/counterFiles/counter?action=increment&counter=course", {
        method: "POST",
        keepalive: true,
        cache: "no-store"
      }).catch(() => {});
    })()
      : Promise.resolve();

    courseVisitPromise.finally(loadCoursesCounter);
    setInterval(loadCoursesCounter, 4000);

    function getSemesterMeta(title) {
      let year = null, term = null, dept = null;
      if (title.includes('أولى')) year = 1;
      else if (title.includes('ثانية')) year = 2;
      else if (title.includes('ثالثة')) year = 3;
      else if (title.includes('رابعة')) year = 4;
      if (title.includes('الأول')) term = 1;
      else if (title.includes('الثاني')) term = 2;
      if (title.includes('CS')) dept = 'CS';
      else if (title.includes('IT')) dept = 'IT';
      else if (title.includes('IS')) dept = 'IS';
      else if (title.includes('عام')) dept = 'عام';
      return { year, term, dept: dept || 'عام', termLabel: term===1?'ترم أول':(term===2?'ترم ثاني':''), semesterTitle: title };
    }

    function isThirdYearGeneralCourse(code) {
      return (courseLocationsMap[code] || []).some(loc => loc.year === 3 && loc.dept === 'عام');
    }

    function getCompletionKey(code, details) {
      if (details && details.year === 3 && isThirdYearGeneralCourse(code)) return `${code}__عام`;
      if (details && details.year >= 3) return `${code}__${details.dept || 'عام'}`;
      return code;
    }

    function getCoursePathKeys(code, pathDept = realDept) {
      const locations = courseLocationsMap[code] || [];
      const keys = [];
      locations.forEach(loc => {
        if (loc.year <= 2) keys.push(getCompletionKey(code, loc));
        else if (loc.year === 3 && loc.dept === 'عام') keys.push(getCompletionKey(code, loc));
        else if ((pathDept || 'عام') === (loc.dept || 'عام')) keys.push(getCompletionKey(code, loc));
      });
      return [...new Set(keys.length ? keys : [code])];
    }

    function getAllCourseKeys(code) {
      const locations = courseLocationsMap[code] || [];
      const keys = locations.map(loc => getCompletionKey(code, loc));
      return [...new Set(keys.length ? keys : [code])];
    }

    function isCourseCompleted(code, details) {
      return completedSet.has(getCompletionKey(code, details));
    }

    function isPrerequisiteCompleted(code) {
      return getCoursePathKeys(code).some(key => completedSet.has(key));
    }

    fetch('data/subjects.json')
      .then(res => res.json())
      .then(data => {
        allData = data;
        allData.forEach(sem => {
          const detailsBase = getSemesterMeta(sem.semester);

          (sem.courses || []).forEach(c => {
            courseMap[c.code] = c;
            const details = { ...detailsBase };
            courseDetailsMap[c.code] = details;
            if (!courseLocationsMap[c.code]) courseLocationsMap[c.code] = [];
            const locationKey = `${details.year}|${details.term}|${details.dept}|${details.semesterTitle}`;
            if (!courseLocationsMap[c.code].some(loc => loc.key === locationKey)) {
              courseLocationsMap[c.code].push({
                ...details,
                key: locationKey,
                prerequisites: [...(c.prerequisites || [])]
              });
            }
            (c.prerequisites || []).forEach(pre => {
              if (!prereqToDependents[pre]) prereqToDependents[pre] = [];
              prereqToDependents[pre].push(c.code);
            });
          });
        });
        populateCourseSearchSuggestions();
        loadFilterState();
        updateDepartmentOptions();
        render();
        updateOverallProgress();
        buildReminders();
      }).catch(() => container.innerHTML = '<div class="empty-msg">تعذّر تحميل الملف</div>');

    function saveFilterState() { localStorage.setItem(STORAGE_FILTER, JSON.stringify({ year: yearSelect.value, dept: realDept, term: termSelect.value })); }
    function loadFilterState() {
      const s = JSON.parse(localStorage.getItem(STORAGE_FILTER) || '{}');
      if (s.year) yearSelect.value = s.year;
      realDept = s.dept || '';
      termSelect.value = s.term || 'all';
      if ((yearSelect.value === '3' || yearSelect.value === '4') && !realDept) realDept = 'عام';
      updateDepartmentOptions();
    }

    function updateDepartmentOptions() {
      const y = yearSelect.value;
      if (y === '1' || y === '2') {
        deptSelect.value = 'عام'; deptSelect.disabled = true;
        Array.from(deptSelect.options).forEach(o => o.hidden = (o.value !== 'عام'));
      } else if (y === '3') {
        deptSelect.disabled = false;
        Array.from(deptSelect.options).forEach(o => o.hidden = false);
        if (!realDept || realDept === 'عام') { realDept = 'عام'; deptSelect.value = 'عام'; }
        else deptSelect.value = realDept;
      } else if (y === '4') {
        deptSelect.disabled = false;
        Array.from(deptSelect.options).forEach(o => o.hidden = (o.value === 'عام'));
        if (!SPECIALIZED_DEPTS.includes(realDept)) { realDept = ''; deptSelect.value = ''; }
        else deptSelect.value = realDept;
      }
      updateNavButtons();
    }

    function updateNavButtons() { const y=+yearSelect.value; prevYearBtn.disabled=y===1; nextYearBtn.disabled=y===4; }

    yearSelect.addEventListener('change', ()=>{ updateDepartmentOptions(); saveFilterState(); render(); buildReminders(); });
    prevYearBtn.addEventListener('click', ()=>{ const c=+yearSelect.value; if(c>1){ yearSelect.value=c-1; yearSelect.dispatchEvent(new Event('change')); } });
    nextYearBtn.addEventListener('click', ()=>{ const c=+yearSelect.value; if(c<4){ yearSelect.value=c+1; yearSelect.dispatchEvent(new Event('change')); } });
    deptSelect.addEventListener('change', ()=>{ if (!deptSelect.disabled) realDept = deptSelect.value; saveFilterState(); render(); updateOverallProgress(); buildReminders(); });
    termSelect.addEventListener('change', ()=>{ saveFilterState(); render(); });

    clearDataBtn.addEventListener('click', openConfirmModal);
    fillPreviousBtn.addEventListener('click', fillPreviousCourses);

    function fillPreviousCourses() {
      const currentYear = parseInt(yearSelect.value);
      const currentTerm = termSelect.value;
      const currentDept = realDept;
      const codesToAdd = new Set();
      allData.forEach(sem => {
        const ti = sem.semester;
        const meta = getSemesterMeta(ti);
        const year = meta.year, term = meta.term, courseDept = meta.dept;
        if (!year) return;
        if (!term) return;

        const canAdd = () => currentDept && currentDept !== 'عام' ? (courseDept === currentDept || courseDept === 'عام') : courseDept === 'عام';
        if (year < currentYear) {
          if (canAdd()) (sem.courses || []).forEach(c => codesToAdd.add(getCompletionKey(c.code, meta)));
        } else if (year === currentYear && currentTerm === '2' && term === 1) {
          if (canAdd()) (sem.courses || []).forEach(c => codesToAdd.add(getCompletionKey(c.code, meta)));
        }
      });
      if (codesToAdd.size === 0) { showToast('لا توجد مواد سابقة لإضافتها'); return; }
      codesToAdd.forEach(code => completedSet.add(code));
      persistAndUpdate();
      render();
      showToast(`تم تحديد ${codesToAdd.size} مادة من السنوات السابقة`);
    }

    function formatPrerequisites(codes) {
      if(!codes||!codes.length) return '';
      return codes.map(c=>{ const co=courseMap[c]; return `<span class="prereq-item">${co?co.arabic_name||co.name:c} (${c})</span>`; }).join(' ');
    }

    function filterSemesters() {
      const y=yearSelect.value, d=deptSelect.value, t=termSelect.value;
      let res=[];
      allData.forEach(sem=>{
        const ti=sem.semester;
        if(y==='1'&&!ti.includes('أولى')) return; if(y==='2'&&!ti.includes('ثانية')) return;
        if(y==='3'&&!ti.includes('ثالثة')) return; if(y==='4'&&!ti.includes('رابعة')) return;
        if(t==='1'&&!ti.includes('الأول')) return; if(t==='2'&&!ti.includes('الثاني')) return;
        let courses=sem.courses||[];
        if(y==='3'){ if(d==='عام'&&!ti.includes('عام')) return; else if(['CS','IT','IS'].includes(d)&&!ti.includes(d)) return; }
        else if(y==='4'){ if(!SPECIALIZED_DEPTS.includes(d)) return; else if(!ti.includes(d)) return; }
        if(courses.length) res.push({title:ti, courses});
      });
      return res;
    }

    function getGlobalScopeCourses() {
      const d=realDept; const scope=[];
      allData.forEach(sem=>{
        const ti=sem.semester; const meta=getSemesterMeta(ti); const y=meta.year;
        if(!y) return;
        (sem.courses||[]).forEach(c=>{
          const item = { ...c, completionDetails: meta, completionKey: getCompletionKey(c.code, meta) };
          if(y===1||y===2) scope.push(item);
          else if(y===3){ if(d==='عام'&&ti.includes('عام')) scope.push(item); else if(SPECIALIZED_DEPTS.includes(d)&&ti.includes(d)) scope.push(item); }
          else if(y===4){ if(SPECIALIZED_DEPTS.includes(d)&&ti.includes(d)) scope.push(item); }
        });
      });
      return [...new Map(scope.map(c=>[c.completionKey,c])).values()];
    }

    function updateOverallProgress() {
      const scope=getGlobalScopeCourses(); const total=scope.length;
      let comp=0; scope.forEach(c=>{ if(completedSet.has(c.completionKey)) comp++; });
      const passedHours = scope.reduce((sum, c) => {
        if (!completedSet.has(c.completionKey)) return sum;
        return sum + (['HM110', 'GN160'].includes(c.code) ? 0 : 3);
      }, 0);
      let level = 'المستوى الأول';
      if (passedHours >= 102) level = 'المستوى الرابع';
      else if (passedHours >= 66) level = 'المستوى الثالث';
      else if (passedHours >= 30) level = 'المستوى الثاني';
      overallProgressFill.style.width=(total? (comp/total)*100 :0)+'%';
      overallProgressText.textContent=`${comp} / ${total} مادة مكتملة`;
      passedHoursText.textContent=`${passedHours} ساعة`;
      academicLevelText.textContent=level;
      const graduationRemaining = Math.max(0, 144 - passedHours);
      graduationRemainingText.textContent = passedHours >= 144
        ? ''
        : `متبقي ${graduationRemaining} ساعة للتخرج`;

      let nextLevel = 'المستوى الثاني';
      let nextLevelHours = 30;
      if (passedHours >= 102) {
        levelRemainingText.textContent = '';
      } else if (passedHours >= 66) {
        nextLevel = 'المستوى الرابع';
        nextLevelHours = 102;
      } else if (passedHours >= 30) {
        nextLevel = 'المستوى الثالث';
        nextLevelHours = 66;
      } else {
        nextLevel = 'المستوى الثاني';
        nextLevelHours = 30;
      }
      if (passedHours < 102) {
        levelRemainingText.textContent = `متبقي ${Math.max(0, nextLevelHours - passedHours)} ساعة على ${nextLevel}`;
      }
    }

    function getVisibleDependentsCount(code) {
      const dependents = prereqToDependents[code] || [];
      if (dependents.length === 0) return 0;
      const details = getPreferredCourseLocation(code);
      if (!details) return dependents.length;
      if ((details.year === 3 || details.year === 4) && realDept && realDept !== 'عام') {
        return dependents.filter(dep => {
          const depLocations = courseLocationsMap[dep] || [];
          return depLocations.some(loc => (loc.year === 3 && loc.dept === 'عام') || loc.dept === realDept);
        }).length;
      }
      return dependents.length;
    }

    function render() {
      const sems=filterSemesters(); container.innerHTML='';
      if(!sems.length) {
        let msg='لا توجد مواد تطابق الفلتر';
        if((yearSelect.value==='3'||yearSelect.value==='4')&&deptSelect.value==='') msg='الرجاء اختيار القسم';
        else if(yearSelect.value==='4'&&!deptSelect.value) msg='الرجاء اختيار القسم';
        container.innerHTML=`<div class="empty-msg">${msg}</div>`; return;
      }
      sems.forEach(sem => {
        const semDetails = getSemesterMeta(sem.title);
        const block=document.createElement('div'); block.className='semester-block';
        const allCompleted = sem.courses.every(c => isCourseCompleted(c.code, semDetails));
        const btnText = allCompleted ? 'مسح الكل' : 'تحديد الكل';
        block.innerHTML=`<div class="semester-title"><span>${sem.title}</span><button class="select-all-btn">${btnText}</button></div><div class="courses-grid"></div>`;
        const grid=block.querySelector('.courses-grid');
        sem.courses.forEach(course=>{
          const code=course.code, isCompleted=isCourseCompleted(code, semDetails);
          const completionKey = getCompletionKey(code, semDetails);
          const hasVisibleDependents = getVisibleDependentsCount(code) > 0;
          const card=document.createElement('div'); card.className=`course-card ${isCompleted?'completed':''}`; card.dataset.code=code;
          card.dataset.completionKey=completionKey;
          card.innerHTML=`
            <div class="checkbox-custom">&#10003;</div>
            <div class="course-info">
              <div class="course-arabic">${course.arabic_name||course.name}</div>
              <div class="course-code">${code}</div>
              <div class="course-english">${course.name}</div>
              ${course.prerequisites?.length?`<div class="prereq"><span class="prereq-label">متطلبات:</span> ${formatPrerequisites(course.prerequisites)}</div>`:''}
            </div>
            <span class="completed-badge">&#10003; تم</span>
            ${hasVisibleDependents?`<button class="open-courses-btn" data-code="${code}">يفتح</button>`:''}
          `;
          card.addEventListener('click', e=>{ if(!e.target.closest('.open-courses-btn')) toggleCompleted(code,card,course,semDetails); });
          grid.appendChild(card);
        });
        grid.querySelectorAll('.open-courses-btn').forEach(btn=> btn.addEventListener('click', e=>{ e.stopPropagation(); showOpenCoursesModal(btn.dataset.code); }));
        const selectAllBtn = block.querySelector('.select-all-btn');
        selectAllBtn.addEventListener('click', e=>{ e.stopPropagation(); toggleAllInSemester(sem.courses, grid, selectAllBtn, semDetails); });
        container.appendChild(block);
      });
    }

    function toggleAllInSemester(courses, grid, btnElement, semDetails) {
      const allCompleted = courses.every(c => isCourseCompleted(c.code, semDetails));
      if (allCompleted) {
        courses.forEach(c => {
          const key = getCompletionKey(c.code, semDetails);
          if(completedSet.has(key)) { completedSet.delete(key); cascadeUncheck(c.code, semDetails.dept, semDetails.year <= 2); }
        });
        grid.querySelectorAll('.course-card').forEach(c => c.classList.remove('completed'));
        persistAndUpdate(); showToast('تم إلغاء إكمال جميع المواد');
      } else {
        let changed = false; const added = new Set(); let keep = true;
        while(keep) { keep=false; courses.forEach(c=>{
          const key = getCompletionKey(c.code, semDetails);
          if(completedSet.has(key)) return;
          if((c.prerequisites||[]).every(p=>isPrerequisiteCompleted(p)||added.has(p))) { completedSet.add(key); added.add(c.code); changed=true; keep=true; }
        }); }
        if(changed) { grid.querySelectorAll('.course-card').forEach(c=>{ if(added.has(c.dataset.code)) c.classList.add('completed'); }); persistAndUpdate(); showToast('تم تحديد جميع المواد الممكنة'); }
        else showToast('لا توجد مواد إضافية يمكن إكمالها حالياً');
      }
      const nowAllCompleted = courses.every(c => isCourseCompleted(c.code, semDetails));
      if (btnElement) btnElement.textContent = nowAllCompleted ? 'مسح الكل' : 'تحديد الكل';
    }

    function updateCardVisual(code){
      document.querySelectorAll(`.course-card[data-code="${code}"]`).forEach(c => {
        const semTitle = c.closest('.semester-block')?.querySelector('.semester-title span')?.textContent || '';
        c.classList.toggle('completed', isCourseCompleted(code, getSemesterMeta(semTitle)));
      });
    }
    function cascadeUncheck(code, pathDept = realDept, includeAllPaths = false){
      (prereqToDependents[code]||[]).forEach(d=>{
        const dependentKeys = includeAllPaths ? getAllCourseKeys(d) : getCoursePathKeys(d, pathDept);
        dependentKeys.forEach(key => {
          if(completedSet.has(key)){ completedSet.delete(key); updateCardVisual(d); cascadeUncheck(d, pathDept, includeAllPaths); }
        });
      });
    }
    function toggleCompleted(code, card, course, semDetails){
      const key = getCompletionKey(code, semDetails);
      if(completedSet.has(key)){
        completedSet.delete(key); cascadeUncheck(code, semDetails.dept, semDetails.year <= 2); updateCardVisual(code); persistAndUpdate(); showToast('تم إلغاء الإكمال');
      } else {
        const pre=course.prerequisites||[]; if(pre.length){ const miss=pre.filter(p=>!isPrerequisiteCompleted(p)); if(miss.length){ showToast(`يجب إنهاء: ${formatPrerequisites(miss)}`); return; } }
        completedSet.add(key); updateCardVisual(code); persistAndUpdate(); showToast('تم الإكمال');
      }
      updateCurrentSemesterSelectAllBtn(code);
    }

    function updateCurrentSemesterSelectAllBtn(code) {
      const card = document.querySelector(`.course-card[data-code="${code}"]`);
      if (!card) return;
      const grid = card.closest('.courses-grid');
      const block = grid.closest('.semester-block');
      const btn = block.querySelector('.select-all-btn');
      const courses = Array.from(grid.querySelectorAll('.course-card')).map(c => courseMap[c.dataset.code]);
      const semTitle = block.querySelector('.semester-title span')?.textContent || '';
      const allComp = courses.every(c => isCourseCompleted(c.code, getSemesterMeta(semTitle)));
      btn.textContent = allComp ? 'مسح الكل' : 'تحديد الكل';
    }

    function persistAndUpdate(){ localStorage.setItem(STORAGE_COMPLETED, JSON.stringify([...completedSet])); updateOverallProgress(); buildReminders(); }
    function showToast(m){ toast.innerHTML=m; toast.classList.add('show'); clearTimeout(toast._t); toast._t=setTimeout(()=>toast.classList.remove('show'),3500); }

    function highlightCourseCard(completionKey, code) {
      document.querySelectorAll('.course-card.reminder-highlight').forEach(card => {
        card.classList.remove('reminder-highlight');
      });
      const cards = Array.from(document.querySelectorAll('.course-card'));
      const target = cards.find(card => card.dataset.completionKey === completionKey) ||
        cards.find(card => card.dataset.code === code);
      if (!target) return false;
      target.scrollIntoView({ behavior: 'smooth', block: 'center' });
      window.setTimeout(() => {
        target.classList.add('reminder-highlight');
        clearTimeout(target._reminderHighlightTimer);
        target._reminderHighlightTimer = setTimeout(() => {
          target.classList.remove('reminder-highlight');
        }, 1200);
      }, 350);
      return true;
    }

    function goToReminderCourse(item) {
      const year = item.dataset.year;
      const term = item.dataset.term;
      const dept = item.dataset.dept || 'عام';
      const code = item.dataset.code;
      const completionKey = item.dataset.completionKey;

      yearSelect.value = year;
      if (year === '3' || year === '4') realDept = dept;
      else realDept = 'عام';
      updateDepartmentOptions();
      termSelect.value = term || 'all';
      saveFilterState();
      render();
      buildReminders();

      requestAnimationFrame(() => {
        const found = highlightCourseCard(completionKey, code);
        showToast(found ? 'تم الانتقال للمادة' : 'تعذر العثور على بطاقة المادة في الفلتر الحالي');
      });
    }

    function renderRemainingCourse(c) {
      const statusClass = c.canRegister ? 'can-register' : 'blocked-register';
      const statusText = c.canRegister ? 'يمكنك تسجيلها' : 'لا يمكنك تسجيلها';
      const missingText = c.missingPrerequisites?.length ? ` - المتطلبات الناقصة: ${c.missingPrerequisites.join('، ')}` : '';
      const tooltip = `${statusText}${missingText}`;
      return `<li class="remaining-course-item" role="button" tabindex="0" title="${escapeHTML(tooltip)}" onclick="goToReminderCourse(this)" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();goToReminderCourse(this);}" data-year="${c.year}" data-term="${c.term}" data-dept="${escapeHTML(c.dept)}" data-code="${escapeHTML(c.code)}" data-completion-key="${escapeHTML(c.completionKey)}">
        <span class="remaining-main">
          <span class="remaining-status ${statusClass}" title="${escapeHTML(tooltip)}" aria-label="${escapeHTML(statusText)}"></span>
          <span class="remaining-name">${escapeHTML(c.name)} <span style="font-size:12px;color:#64748b;">(${escapeHTML(c.english)})</span></span>
        </span>
        <span class="remaining-code">${escapeHTML(c.code)}</span>
      </li>`;
    }

    function buildReminders(){
      const years={1:{first:[],second:[]},2:{first:[],second:[]},3:{first:[],second:[]},4:{first:[],second:[]}};
      const added={1:{first:new Set(),second:new Set()},2:{first:new Set(),second:new Set()},3:{first:new Set(),second:new Set()},4:{first:new Set(),second:new Set()}};
      allData.forEach(sem=>{
        const ti=sem.semester; const meta=getSemesterMeta(ti); const y=meta.year;
        if(!y) return;
        let includeSem = false;
        if (y === 1 || y === 2) includeSem = true;
        else if (y === 3) { if (realDept === 'عام') includeSem = ti.includes('عام'); else if (['CS','IT','IS'].includes(realDept)) includeSem = ti.includes(realDept); }
        else if (y === 4) { if (['CS','IT','IS'].includes(realDept)) includeSem = ti.includes(realDept); }
        if (!includeSem) return;
        const isF = ti.includes('الأول'), isS = ti.includes('الثاني');
        const tk = isF ? 'first' : (isS ? 'second' : null);
        if(!tk) return;
        (sem.courses||[]).forEach(c=>{
          const key = getCompletionKey(c.code, meta);
          if(!completedSet.has(key) && !added[y][tk].has(key)){
            const missingCodes = (c.prerequisites || []).filter(p => !isPrerequisiteCompleted(p));
            const missingPrerequisites = missingCodes.map(code => {
              const course = courseMap[code];
              return `${course ? (course.arabic_name || course.name) : code} (${code})`;
            });
            added[y][tk].add(key);
            years[y][tk].push({
              code:c.code,
              name:c.arabic_name||c.name,
              english:c.name,
              year:y,
              term:meta.term,
              dept:meta.dept,
              completionKey:key,
              canRegister: missingCodes.length === 0,
              missingPrerequisites
            });
          }
        });
      });

      let html='';
      const yn=['','الأولى','الثانية','الثالثة','الرابعة'];
      for(let y=1;y<=4;y++){
        const f=years[y].first, s=years[y].second, t=f.length+s.length;
        html+=`<div class="year-reminder"><div class="year-header" onclick="toggleReminderYear(this)"><span>الفرقة ${yn[y]} (${t} مادة متبقية)</span><span class="arrow">▼</span></div><div class="year-body-wrapper"><div class="year-body">${t===0?'<p style="color:#10b981;">مكتملة</p>':`${f.length?`<div class="term-separator">ترم أول</div><ul class="remaining-list">${f.map(renderRemainingCourse).join('')}</ul>`:''}${s.length?`<div class="term-separator">ترم ثاني</div><ul class="remaining-list">${s.map(renderRemainingCourse).join('')}</ul>`:''}`}</div></div></div>`;
      }
      reminderContainer.innerHTML=html;
    }
    function toggleReminderYear(h){
      const r=h.parentElement, w=r.querySelector('.year-body-wrapper'), b=w.querySelector('.year-body'), o=r.classList.contains('open');
      if(o){ w.style.height=w.scrollHeight+'px'; requestAnimationFrame(()=>{ w.style.height='0px'; }); r.classList.remove('open'); setTimeout(()=>{ if(!r.classList.contains('open')) w.style.height=''; },400); }
      else { r.classList.add('open'); w.style.height='0px'; requestAnimationFrame(()=>{ w.style.height=b.scrollHeight+'px'; }); setTimeout(()=>{ if(r.classList.contains('open')) w.style.height='auto'; },400); }
    }
    updateDepartmentOptions();
