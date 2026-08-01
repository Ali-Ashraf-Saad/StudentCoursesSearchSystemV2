let termCounter = 0;
    let termIdToDelete = null;
    let reorderModeEnabled = false;
    let semesterGpaChart = null;
    let cumulativeGpaChart = null;
    const MAX_SUBJECTS_PER_TERM = 50;
    const MAX_HOURS_PER_TERM = 100;
    const liveValidationTimers = new WeakMap();

    // --- وضع إدخال الساعات: "variable" (ساعات مختلفة لكل مادة) أو "fixed" (3 ساعات ثابتة لكل مادة) ---
    let fixedHoursMode = (localStorage.getItem('gpaHoursMode') !== 'variable');
    let pendingHoursMode = null;
    document.body.classList.toggle('fixed-hours-mode', fixedHoursMode);

    function getRegUnit() { return fixedHoursMode ? 3 : 1; }
    function getRegMax() { return fixedHoursMode ? MAX_SUBJECTS_PER_TERM : MAX_HOURS_PER_TERM; }
    function getStorageKey() { return fixedHoursMode ? 'gpaDataFixed' : 'gpaData'; }
    function getFailedErrorMessage() {
        return fixedHoursMode
            ? "لا يمكن أن يزيد عدد مواد الرسوب عن المواد المسجلة"
            : "لا يمكن أن تزيد ساعات الرسوب عن الساعات المسجلة";
    }

    function syncHoursModeRadios() {
        document.getElementById('hours-mode-variable').checked = !fixedHoursMode;
        document.getElementById('hours-mode-fixed').checked = fixedHoursMode;
    }

    function requestHoursModeChange(mode) {
        const newFixed = (mode === 'fixed');
        if (newFixed === fixedHoursMode) return;
        pendingHoursMode = newFixed;

        document.getElementById('msg-title').innerText = "تغيير طريقة إدخال الساعات؟";
        document.getElementById('msg-desc').innerText = newFixed
            ? "سيتم التحويل إلى وضع الساعات الثابتة (3 ساعات لكل مادة). سيتم حفظ بيانات الوضع الحالي والانتقال إلى بيانات هذا الوضع بشكل منفصل."
            : "سيتم التحويل إلى وضع إدخال عدد ساعات مختلف لكل مادة. سيتم حفظ بيانات الوضع الحالي والانتقال إلى بيانات هذا الوضع بشكل منفصل.";
        document.getElementById('msg-actions').innerHTML = `
            <button class="btn-cancel" onclick="cancelHoursModeChange()">إلغاء</button>
            <button class="btn-danger-confirm" onclick="applyHoursModeChange()">تأكيد</button>
        `;
        document.getElementById('general-modal').classList.add('active');
    }

    function cancelHoursModeChange() {
        pendingHoursMode = null;
        syncHoursModeRadios();
        closeModal('general-modal');
    }

    function applyHoursModeChange() {
        fixedHoursMode = pendingHoursMode;
        pendingHoursMode = null;
        localStorage.setItem('gpaHoursMode', fixedHoursMode ? 'fixed' : 'variable');
        document.body.classList.toggle('fixed-hours-mode', fixedHoursMode);
        syncHoursModeRadios();
        closeModal('general-modal');
        loadData();
    }

    const gradePoints = {
        'A+': 4.0, 'A': 3.7, 'B+': 3.3, 'B': 3.0,
        'C+': 2.7, 'C': 2.4, 'D+': 2.2, 'D': 2.0, 'F': 0.0
    };

    function saveData() {
        const termsData = [];
        const terms = document.querySelectorAll('.term-card');
        
        terms.forEach(term => {
            const termId = term.id;
            const checkedRadio = term.querySelector(`input[name="mode-${termId}"]:checked`);
            const modeValue = checkedRadio ? checkedRadio.value : term.dataset.currentMode;

            const termObj = {
                name: term.querySelector('.term-header input').value,
                mode: modeValue,
                directGpa: term.querySelector('.term-gpa-input').value,
                registeredSubjects: term.querySelector('.registered-subjects-input').value,
                failedSubjects: term.querySelector('.failed-subjects-input').value,
                repeatedHours: term.querySelector('.repeated-subjects-input').value,
                isExpanded: term.classList.contains('expanded'),
                subjects: []
            };

            const subjects = term.querySelectorAll('.subject-row');
            subjects.forEach(sub => {
                const subObj = {
                    name: sub.querySelector('.subject-name').value,
                    hours: sub.querySelector('.hours-input') ? sub.querySelector('.hours-input').value : 3,
                    letterGrade: sub.querySelector('.grade-input') ? sub.querySelector('.grade-input').value : '',
                    percentGrade: sub.querySelector('.percent-input') ? sub.querySelector('.percent-input').value : ''
                };
                termObj.subjects.push(subObj);
            });
            termsData.push(termObj);
        });

        localStorage.setItem(getStorageKey(), JSON.stringify(termsData));
    }

    function loadData() {
        const saved = localStorage.getItem(getStorageKey());
        const container = document.getElementById('terms-container');
        container.innerHTML = '';
        termCounter = 0;

        if (saved) {
            const termsData = JSON.parse(saved);

            if (termsData.length === 0) { addTerm(); return; }

            termsData.forEach(tData => {
                addTerm(false, tData.isExpanded !== false); 
                
                const currentId = `term-${termCounter}`;
                const termDiv = document.getElementById(currentId);

                termDiv.querySelector('.term-header input').value = tData.name;
                
                const radios = document.getElementsByName(`mode-${currentId}`);
                radios.forEach(r => { if (r.value === tData.mode) r.checked = true; });
                termDiv.dataset.currentMode = tData.mode;
                updateTermView(currentId, false);
                termDiv.querySelector('.repeated-subjects-input').value = tData.repeatedHours ?? tData.repeatedSubjects ?? 0;

                if (tData.mode === 'term-gpa') {
                    termDiv.querySelector('.term-gpa-input').value = tData.directGpa;
                    termDiv.querySelector('.registered-subjects-input').value = tData.registeredSubjects ?? (fixedHoursMode ? 6 : 18);
                    termDiv.querySelector('.failed-subjects-input').value = tData.failedSubjects ?? 0;
                } else {
                    const list = document.getElementById(`${currentId}-list`);
                    list.innerHTML = ''; 
                    tData.subjects.forEach(sub => {
                        addSubject(currentId, false);
                        const rows = list.querySelectorAll('.subject-row');
                        const lastRow = rows[rows.length - 1];
                        
                        lastRow.querySelector('.subject-name').value = sub.name;
                        if (lastRow.querySelector('.hours-input')) lastRow.querySelector('.hours-input').value = sub.hours ?? 3;
                        if (tData.mode === 'subjects-letter') {
                             if(sub.letterGrade) lastRow.querySelector('.grade-input').value = sub.letterGrade;
                        } else {
                             if(sub.percentGrade) lastRow.querySelector('.percent-input').value = sub.percentGrade;
                        }
                    });
                }

                updateTermBadgePreview(termDiv);
            });
            updateRepeatedSubjectsFields();
        } else {
            addTerm();
        }
    }

    document.addEventListener('input', event => {
        updateRepeatedSubjectsFields();
        saveData();
        const term = event.target.closest('.term-card');
        if (term) updateTermBadgePreview(term);
        scheduleLiveValidation(event.target);
    });
    document.addEventListener('change', event => {
        updateRepeatedSubjectsFields();
        saveData();
        const term = event.target.closest('.term-card');
        if (term) updateTermBadgePreview(term);
        scheduleLiveValidation(event.target);
    });
    document.addEventListener('blur', event => {
        validateLiveInput(event.target);
    }, true);

    function toggleTerm(termId) {
        if (reorderModeEnabled) return;
        const termCard = document.getElementById(termId);
        termCard.classList.toggle('expanded');
        saveData();
    }

    function clearError(input) {
        input.classList.remove('input-error');
        const parent = input.parentElement;
        const errorMsg = parent.querySelector('.error-feedback');
        if (errorMsg) errorMsg.classList.remove('visible');
    }

    function validateInput(input, min, max) {
        const valStr = input.value;
        const parent = input.parentElement;
        let errorMsg = parent.querySelector('.error-feedback');
        if (!errorMsg) {
            errorMsg = document.createElement('div');
            errorMsg.className = 'error-feedback';
            parent.appendChild(errorMsg);
        }

        if (valStr.trim() === "") {
            input.classList.add('input-error');
            errorMsg.innerText = "هذا الحقل مطلوب";
            errorMsg.classList.add('visible');
            return false;
        }

        if (min !== undefined && max !== undefined) {
            const val = parseFloat(valStr);
            if (val < min || val > max) {
                input.classList.add('input-error');
                errorMsg.innerText = `بين ${min} و ${max}`;
                errorMsg.classList.add('visible');
                return false;
            }
        }

        clearError(input);
        return true;
    }

    function formatDirectGpa(input) {
        let value = input.value.replace(/[^\d.]/g, '');
        value = value.replace(/^\.+/, '');
        const firstDot = value.indexOf('.');

        if (firstDot !== -1) {
            value = value.slice(0, firstDot + 1) + value.slice(firstDot + 1).replace(/\./g, '');
            const parts = value.split('.');
            value = parts[0].slice(0, 1) + '.' + parts[1].slice(0, 2);
        } else if (value.length > 1) {
            value = value[0] + '.' + value.slice(1, 3);
        }

        input.value = value;
    }

    function scheduleLiveValidation(input) {
        if (!isLiveValidatedInput(input)) return;

        const existingTimer = liveValidationTimers.get(input);
        if (existingTimer) clearTimeout(existingTimer);

        const timer = setTimeout(() => validateLiveInput(input), 650);
        liveValidationTimers.set(input, timer);
    }

    function isLiveValidatedInput(input) {
        return input.matches?.('.term-gpa-input, .registered-subjects-input, .failed-subjects-input, .percent-input, .hours-input');
    }

    function validateLiveInput(input) {
        if (!isLiveValidatedInput(input)) return true;

        const existingTimer = liveValidationTimers.get(input);
        if (existingTimer) clearTimeout(existingTimer);
        liveValidationTimers.delete(input);

        if (input.value.trim() === '') {
            clearError(input);
            return true;
        }

        if (input.classList.contains('term-gpa-input')) {
            return validateInput(input, 0, 4);
        }

        if (input.classList.contains('percent-input')) {
            return validateInput(input, 0, 100);
        }

        if (input.classList.contains('hours-input')) {
            return validateWholeNumber(input, 1, 6);
        }

        const term = input.closest('.term-card');
        const registeredInput = term?.querySelector('.registered-subjects-input');
        const failedInput = term?.querySelector('.failed-subjects-input');

        if (input.classList.contains('registered-subjects-input')) {
            const isRegisteredValid = validateWholeNumber(input, 1, getRegMax());
            if (failedInput && failedInput.value.trim() !== '') validateLiveInput(failedInput);
            return isRegisteredValid;
        }

        if (input.classList.contains('failed-subjects-input')) {
            if (!validateWholeNumber(input, 0, getRegMax())) return false;
            if (!registeredInput || registeredInput.value.trim() === '') return true;

            const failedSubjects = Number(input.value);
            const registeredSubjects = Number(registeredInput.value);
            if (
                Number.isInteger(failedSubjects) &&
                Number.isInteger(registeredSubjects) &&
                failedSubjects > registeredSubjects
            ) {
                showInputError(input, getFailedErrorMessage());
                return false;
            }

            clearError(input);
            return true;
        }

        return true;
    }

    function addTerm(shouldSave = true, isExpanded = true) {
        // حماية إضافية: منع تنفيذ الدالة إذا كان وضع الترتيب مفعلاً
        if (reorderModeEnabled) return; 

        termCounter++;
        const container = document.getElementById('terms-container');
        const termNumber = container.querySelectorAll('.term-card').length + 1;
        const termId = `term-${termCounter}`;
        const termDiv = document.createElement('div');
        
        termDiv.className = `term-card ${isExpanded ? 'expanded' : ''}`;
        termDiv.id = termId;
        termDiv.dataset.currentMode = 'term-gpa';

        termDiv.innerHTML = `
            <div class="term-header" onclick="toggleTerm('${termId}')">
                <span class="arrow-icon">▼</span>
                <input type="text" value="ترم ${termNumber}" onclick="event.stopPropagation(); this.select()">
                <div class="term-result-badge">GPA: 0.00</div>
                <button class="btn-delete-term" onclick="event.stopPropagation(); confirmDeleteTerm('${termId}')">✕</button>
            </div>
            
            <div class="term-body">
                <div class="radio-group">
                    <div class="radio-title">طريقة حساب الترم:</div>
                    <div class="radio-options-row">
                        <label class="radio-option">
                            <input type="radio" name="mode-${termId}" value="term-gpa" onchange="updateTermView('${termId}')" checked> المعدل الفصلي
                        </label>
                        <label class="radio-option">
                            <input type="radio" name="mode-${termId}" value="subjects-letter" onchange="updateTermView('${termId}')"> المواد (تقدير)
                        </label>
                        <label class="radio-option">
                            <input type="radio" name="mode-${termId}" value="subjects-percent" onchange="updateTermView('${termId}')"> المواد (درجة)
                        </label>
                    </div>
                </div>
                <div class="repeated-subjects-section hidden">
                    <label>${fixedHoursMode ? 'عدد المواد المُعادة (المسجلة أكثر من مرة):' : 'عدد الساعات المُعادة (لمواد مسجلة أكثر من مرة):'}</label>
                    <input type="number" step="1" min="0" max="0" class="repeated-subjects-input" value="0" oninput="clearError(this)">
                    <div class="repeated-subjects-hint">متاح للإعادة من الترمات السابقة: <span class="available-repeats">0</span>${fixedHoursMode ? '' : ' ساعة'}</div>
                </div>
                <div id="${termId}-direct" class="input-section">
                    <div class="direct-inputs-grid">
                        <div class="direct-input-field">
                            <label>المعدل الفصلي (GPA):</label>
                            <input type="text" inputmode="decimal" maxlength="4" class="term-gpa-input" placeholder="مثلاً: 3.5" oninput="formatDirectGpa(this); clearError(this)">
                        </div>
                        <div class="direct-input-field">
                            <label>${fixedHoursMode ? 'عدد المواد المسجلة:' : 'عدد الساعات المسجلة:'}</label>
                            <input type="number" step="1" min="1" max="${getRegMax()}" class="registered-subjects-input" value="${fixedHoursMode ? 6 : 18}" oninput="clearError(this)">
                        </div>
                        <div class="direct-input-field">
                            <label>${fixedHoursMode ? 'عدد مواد الرسوب:' : 'عدد ساعات الرسوب:'}</label>
                            <input type="number" step="1" min="0" max="${getRegMax()}" class="failed-subjects-input" value="0" oninput="clearError(this)">
                        </div>
                    </div>
                </div>
                <div id="${termId}-subjects" class="input-section hidden">
                    <div class="subjects-list" id="${termId}-list"></div>
                    <button class="btn-add-subject" onclick="addSubject('${termId}')">+ إضافة مادة</button>
                </div>
            </div>
        `;
        container.appendChild(termDiv);
        
        if (shouldSave) {
            addSubject(termId);
            updateRepeatedSubjectsFields();
            saveData();
        }
    }
    
    // --- متغيرات ونظام السحب والإفلات الموحد ---
    let pointerGhost = null;
    let dragTarget = null;
    let placeholder = null;
    let pointerOffset = { x: 0, y: 0 };
    let autoScrollInterval = null;
    let currentPointerY = 0;

function toggleReorderMode() {
        reorderModeEnabled = !reorderModeEnabled;
        const container = document.getElementById('terms-container');
        const button = document.getElementById('reorder-btn');
        const addTermBtn = document.querySelector('.btn-add-term'); // جلب زر إضافة ترم

        container.classList.toggle('reorder-mode', reorderModeEnabled);
        button.classList.toggle('active', reorderModeEnabled);
        button.innerText = reorderModeEnabled ? '✓ إنهاء الترتيب' : '↕ ترتيب الترمات';

        // تعطيل زر "إضافة ترم" وتغيير مظهره أثناء وضع الترتيب
        if (addTermBtn) {
            addTermBtn.disabled = reorderModeEnabled;
            addTermBtn.style.opacity = reorderModeEnabled ? '0.4' : '1';
            addTermBtn.style.cursor = reorderModeEnabled ? 'not-allowed' : 'pointer';
        }

        container.querySelectorAll('.term-card').forEach(term => {
            if (reorderModeEnabled) {
                // حفظ حالة الترم (مفتوح أم مغلق) قبل طيه
                term.dataset.wasExpanded = term.classList.contains('expanded');
                term.classList.remove('expanded'); // طي الترم
            } else {
                // استرجاع حالة الترم السابقة عند إنهاء الترتيب
                if (term.dataset.wasExpanded === 'true') {
                    term.classList.add('expanded');
                }
                // تنظيف المتغير بعد الاسترجاع
                delete term.dataset.wasExpanded;
            }
        });
        
        if (reorderModeEnabled) {
            if (navigator.vibrate) navigator.vibrate(50);
        }
        
        // حفظ البيانات في الحالتين (لضمان حفظ حالة الفتح والإغلاق الجديدة في التخزين المحلي)
        saveData();
    }
    
    document.getElementById('terms-container').addEventListener('pointerdown', startDrag);
    document.addEventListener('pointermove', moveDrag, { passive: false });
    document.addEventListener('pointerup', endDrag);
    document.addEventListener('pointercancel', endDrag);

    function startDrag(e) {
        if (!reorderModeEnabled || dragTarget) return; // يمنع السحب بأكثر من إصبع في نفس الوقت
        
        const term = e.target.closest('.term-card');
        if (!term || e.target.closest('.btn-delete-term')) return;

        e.preventDefault();
        if (navigator.vibrate) navigator.vibrate(40);

        dragTarget = term;
        const rect = term.getBoundingClientRect();
        
        pointerOffset.x = e.clientX - rect.left;
        pointerOffset.y = e.clientY - rect.top;
        currentPointerY = e.clientY;

        pointerGhost = term.cloneNode(true);
        pointerGhost.className = 'term-ghost'; 
        pointerGhost.removeAttribute('id'); // يمنع اختلاط الـ IDs بالمتصفح
        
        // يعزل أزرار الراديو في الكرت الوهمي حتى لا تلغي تحديد الكرت الأصلي
        pointerGhost.querySelectorAll('input[type="radio"]').forEach(r => {
            r.name += '-ghost';
        });

        pointerGhost.style.width = rect.width + 'px';
        pointerGhost.style.height = rect.height + 'px';
        pointerGhost.style.left = rect.left + 'px';
        pointerGhost.style.top = rect.top + 'px';
        document.body.appendChild(pointerGhost);

        placeholder = document.createElement('div');
        placeholder.className = 'term-drop-placeholder';
        placeholder.style.height = rect.height + 'px';
        term.after(placeholder);

        term.style.display = 'none';
        startAutoScroll();
    }

    function moveDrag(e) {
        if (!reorderModeEnabled || !pointerGhost || !dragTarget) return;
        e.preventDefault(); 
        
        currentPointerY = e.clientY;

        pointerGhost.style.top = (e.clientY - pointerOffset.y) + 'px';
        pointerGhost.style.left = (e.clientX - pointerOffset.x) + 'px';

        pointerGhost.style.visibility = 'hidden';
        const elementBelow = document.elementFromPoint(e.clientX, e.clientY);
        pointerGhost.style.visibility = 'visible';

        if (!elementBelow) return;

        const overTerm = elementBelow.closest('.term-card:not([style*="display: none"])');
        
        if (overTerm && overTerm !== dragTarget) {
            const overRect = overTerm.getBoundingClientRect();
            const isAfter = e.clientY > overRect.top + (overRect.height / 2);
            overTerm.parentElement.insertBefore(
                placeholder, 
                isAfter ? overTerm.nextSibling : overTerm
            );
        }
    }

    function endDrag(e) {
        if (!reorderModeEnabled || !pointerGhost || !dragTarget) return;
        
        stopAutoScroll();
        
        const placeholderRect = placeholder.getBoundingClientRect();
        
        pointerGhost.classList.add('dropping');
        pointerGhost.style.top = placeholderRect.top + 'px';
        pointerGhost.style.left = placeholderRect.left + 'px';
        
        if (navigator.vibrate) navigator.vibrate(30);

        // عزل المراجع لتفادي أي مشكلة وميض مزدوج أو تداخل
        const localGhost = pointerGhost;
        const localTarget = dragTarget;
        const localPlaceholder = placeholder;
        
        pointerGhost = null;
        dragTarget = null;
        placeholder = null;

        setTimeout(() => {
            if (localPlaceholder && localTarget) {
                localPlaceholder.replaceWith(localTarget);
                localTarget.style.display = '';
            }
            if (localGhost) localGhost.remove();
            
            renumberDefaultTermNames();
            updateRepeatedSubjectsFields();
            saveData();
        }, 250); 
    }

    function startAutoScroll() {
        if (autoScrollInterval) return;
        const scrollZone = 80; 
        const scrollSpeed = 12;

        autoScrollInterval = setInterval(() => {
            if (!pointerGhost) return stopAutoScroll();
            
            let scrolled = false;
            if (currentPointerY < scrollZone) {
                window.scrollBy(0, -scrollSpeed);
                scrolled = true;
            } else if (currentPointerY > window.innerHeight - scrollZone) {
                window.scrollBy(0, scrollSpeed);
                scrolled = true;
            }

            if (scrolled) {
                moveDrag({ clientX: pointerGhost.getBoundingClientRect().left + pointerOffset.x, clientY: currentPointerY, preventDefault: ()=>{} });
            }
        }, 16);
    }

    function stopAutoScroll() {
        if (autoScrollInterval) {
            clearInterval(autoScrollInterval);
            autoScrollInterval = null;
        }
    }

    function updateTermView(termId, shouldSync = true) {
        const term = document.getElementById(termId);
        const checkedRadio = term.querySelector(`input[name="mode-${termId}"]:checked`);
        const mode = checkedRadio ? checkedRadio.value : term.dataset.currentMode;
        
        const previousMode = term.dataset.currentMode || 'term-gpa';
        const directDiv = document.getElementById(`${termId}-direct`);
        const subjectsDiv = document.getElementById(`${termId}-subjects`);
        
        term.querySelector('.term-result-badge').classList.remove('visible');

        const allInputs = term.querySelectorAll('input, select');
        allInputs.forEach(inp => clearError(inp));

        if (shouldSync && previousMode !== mode) {
            syncTermMode(term, previousMode, mode);
        }

        if (mode === 'term-gpa') {
            directDiv.classList.remove('hidden'); subjectsDiv.classList.add('hidden');
        } else {
            directDiv.classList.add('hidden'); subjectsDiv.classList.remove('hidden');
        }

        term.dataset.currentMode = mode;
        updateRepeatedSubjectsFields();
        updateTermBadgePreview(term);
    }

    function updateTermBadgePreview() {
        let totalCoursePoints = 0;
        let totalRegisteredHours = 0;
        let previousTermsComplete = true;

        document.querySelectorAll('.term-card').forEach(term => {
            const badge = term.querySelector('.term-result-badge'); 
            const termData = getTermPreviewData(term);

            if (!termData || !previousTermsComplete) {
                badge.classList.remove('visible');
                previousTermsComplete = false;
                return;
            }

            totalCoursePoints += termData.totalPoints;
            totalRegisteredHours += termData.registeredHours;

            if (totalRegisteredHours <= 0) {
                badge.classList.remove('visible');
                return;
            }

            const cumulativeGpa = totalCoursePoints / totalRegisteredHours;
            badge.innerText = `فصلي: ${termData.gpa.toFixed(2)} | تراكمي: ${cumulativeGpa.toFixed(2)}`;
            badge.classList.add('visible');
        });
    }

    function getTermPreviewData(term) {
        const checkedRadio = term.querySelector(`input[name="mode-${term.id}"]:checked`);
        const mode = checkedRadio ? checkedRadio.value : term.dataset.currentMode;

        const repeatedInput = term.querySelector('.repeated-subjects-input');
        if (!repeatedInput) return null;
        
        const repeatedRaw = Number(repeatedInput.value);
        const availableRepeats = Number(repeatedInput.dataset.available || 0);

        if (!Number.isInteger(repeatedRaw) || repeatedRaw < 0 || repeatedRaw > availableRepeats) return null;
        const repeatedHours = repeatedRaw * getRegUnit();

        if (mode === 'term-gpa') {
            const gpaInput = term.querySelector('.term-gpa-input');
            const registeredInput = term.querySelector('.registered-subjects-input');
            const failedInput = term.querySelector('.failed-subjects-input');
            
            if (!gpaInput || !registeredInput || !failedInput) return null;

            const gpaValue = gpaInput.value.trim();
            const gpa = Number(gpaValue);
            const registeredRaw = Number(registeredInput.value);
            const failedRaw = Number(failedInput.value);

            if (
                gpaValue === '' || isNaN(gpa) || gpa < 0 || gpa > 4 ||
                !Number.isInteger(registeredRaw) || registeredRaw < 1 || registeredRaw > getRegMax() ||
                !Number.isInteger(failedRaw) || failedRaw < 0 || failedRaw > registeredRaw
            ) return null;

            const fullRegisteredHours = registeredRaw * getRegUnit();
            return {
                gpa,
                totalPoints: gpa * fullRegisteredHours,
                registeredHours: fullRegisteredHours - repeatedHours
            };
        }

        const rows = Array.from(term.querySelectorAll('.subject-row'));
        if (rows.length === 0) return null;
        let totalPoints = 0;
        let totalHours = 0;
        let allComplete = true;

        for (const row of rows) {
            const hoursInput = row.querySelector('.hours-input');
            const hours = Number(hoursInput?.value);
            if (!hoursInput || !Number.isInteger(hours) || hours < 1) { allComplete = false; break; }

            if (mode === 'subjects-letter') {
                const value = row.querySelector('.grade-input')?.value;
                if (!value) { allComplete = false; break; }
                totalPoints += gradePoints[value] * hours;
            } else {
                const value = row.querySelector('.percent-input')?.value;
                const percent = Number(value);
                if (!value || value === '' || isNaN(percent) || percent < 0 || percent > 100) { allComplete = false; break; }
                totalPoints += convertPercentToPoints(percent) * hours;
            }
            totalHours += hours;
        }

        if (!allComplete || totalHours === 0) return null;

        return {
            gpa: totalPoints / totalHours,
            totalPoints,
            registeredHours: totalHours - repeatedHours
        };
    }

    function getTermFailedSubjects(term) {
        const checkedRadio = term.querySelector(`input[name="mode-${term.id}"]:checked`);
        const mode = checkedRadio ? checkedRadio.value : term.dataset.currentMode;

        if (mode === 'term-gpa') {
            const value = Number(term.querySelector('.failed-subjects-input').value);
            return Number.isInteger(value) && value >= 0 ? value : 0;
        }

        let failedSubjects = 0;
        term.querySelectorAll('.subject-row').forEach(row => {
            if (mode === 'subjects-letter') {
                if (row.querySelector('.grade-input')?.value === 'F') failedSubjects++;
            } else {
                const value = row.querySelector('.percent-input')?.value;
                if (value !== '' && !isNaN(Number(value)) && Number(value) < 50) failedSubjects++;
            }
        });
        return failedSubjects;
    }

    function getTermRegisteredSubjects(term) {
        const checkedRadio = term.querySelector(`input[name="mode-${term.id}"]:checked`);
        const mode = checkedRadio ? checkedRadio.value : term.dataset.currentMode;
        
        if (mode === 'term-gpa') {
            const value = Number(term.querySelector('.registered-subjects-input').value);
            return Number.isInteger(value) && value > 0 ? value : 0;
        }
        return term.querySelectorAll('.subject-row').length;
    }

    function getTermFailedHours(term) {
        const checkedRadio = term.querySelector(`input[name="mode-${term.id}"]:checked`);
        const mode = checkedRadio ? checkedRadio.value : term.dataset.currentMode;

        if (mode === 'term-gpa') {
            const value = Number(term.querySelector('.failed-subjects-input').value);
            const raw = Number.isInteger(value) && value >= 0 ? value : 0;
            return raw * getRegUnit();
        }

        let failedHours = 0;
        term.querySelectorAll('.subject-row').forEach(row => {
            const hours = Number(row.querySelector('.hours-input')?.value) || 0;
            if (mode === 'subjects-letter') {
                if (row.querySelector('.grade-input')?.value === 'F') failedHours += hours;
            } else {
                const value = row.querySelector('.percent-input')?.value;
                if (value !== '' && !isNaN(Number(value)) && Number(value) < 50) failedHours += hours;
            }
        });
        return failedHours;
    }

    function getTermRegisteredHours(term) {
        const checkedRadio = term.querySelector(`input[name="mode-${term.id}"]:checked`);
        const mode = checkedRadio ? checkedRadio.value : term.dataset.currentMode;

        if (mode === 'term-gpa') {
            const value = Number(term.querySelector('.registered-subjects-input').value);
            const raw = Number.isInteger(value) && value > 0 ? value : 0;
            return raw * getRegUnit();
        }

        let totalHours = 0;
        term.querySelectorAll('.subject-row').forEach(row => {
            totalHours += Number(row.querySelector('.hours-input')?.value) || 3;
        });
        return totalHours;
    }

    function updateRepeatedSubjectsFields() {
        let pendingFailedHours = 0;
        const unit = getRegUnit();

        document.querySelectorAll('.term-card').forEach(term => {
            const section = term.querySelector('.repeated-subjects-section');
            const input = term.querySelector('.repeated-subjects-input');
            const hint = term.querySelector('.available-repeats');
            const maxRepeatedHours = Math.max(0, Math.min(pendingFailedHours, getTermRegisteredHours(term)));
            const maxRepeatedDisplay = Math.floor(maxRepeatedHours / unit);

            input.max = maxRepeatedDisplay;
            input.dataset.available = maxRepeatedDisplay;
            hint.innerText = maxRepeatedDisplay;
            section.classList.toggle('hidden', pendingFailedHours === 0);

            let repeatedRaw = Number(input.value);
            if (!Number.isInteger(repeatedRaw) || repeatedRaw < 0) repeatedRaw = 0;
            if (repeatedRaw > maxRepeatedDisplay) repeatedRaw = maxRepeatedDisplay;
            input.value = repeatedRaw;

            pendingFailedHours -= (repeatedRaw * unit);
            pendingFailedHours += getTermFailedHours(term);
        });

        updateTermBadgePreview();
    }

    function syncTermMode(term, previousMode, newMode) {
        const list = term.querySelector('.subjects-list');
        const rows = Array.from(list.querySelectorAll('.subject-row'));

        if (previousMode === 'subjects-letter' || previousMode === 'subjects-percent') {
            storeSubjectValues(rows, previousMode);
        }

        if (newMode === 'term-gpa') {
            const summary = summarizeSubjectRows(rows, previousMode);
            const totalHours = summary.totalHours || rows.reduce((sum, row) => sum + (Number(row.querySelector('.hours-input')?.value) || 3), 0);
            const unit = getRegUnit();
            const registeredDisplay = Math.round(totalHours / unit);
            const failedDisplay = Math.round((summary.failedHours || 0) / unit);
            term.querySelector('.registered-subjects-input').value = Math.min(Math.max(registeredDisplay, 1), getRegMax()) || 1;
            term.querySelector('.failed-subjects-input').value = Math.min(failedDisplay, getRegMax());
            term.querySelector('.term-gpa-input').value = summary.gpa === null ? '' : summary.gpa.toFixed(2);
            return;
        }

        if (previousMode === 'term-gpa') {
            const requestedRaw = Number(term.querySelector('.registered-subjects-input').value);
            const requestedHours = requestedRaw * getRegUnit();
            const subjectCount = Number.isInteger(requestedRaw) ? Math.min(Math.max(Math.round(requestedHours / 3), 1), MAX_SUBJECTS_PER_TERM) : 1;

            while (list.querySelectorAll('.subject-row').length < subjectCount) { addSubject(term.id, false); }
            while (list.querySelectorAll('.subject-row').length > subjectCount) { list.lastElementChild.remove(); }

            list.querySelectorAll('.subject-row').forEach(row => { renderSubjectInput(row, newMode); });
            renumberDefaultSubjectNames(list);
            return;
        }

        rows.forEach(row => {
            if (previousMode === 'subjects-percent' && newMode === 'subjects-letter') {
                const percentValue = row.dataset.percentGrade || '';
                if (percentValue !== '') {
                    const percent = Number(percentValue);
                    if (!isNaN(percent) && percent >= 0 && percent <= 100) row.dataset.letterGrade = convertPercentToLetter(percent);
                }
            }
            renderSubjectInput(row, newMode);
        });
    }

    function storeSubjectValues(rows, mode) {
        rows.forEach(row => {
            if (mode === 'subjects-letter') {
                const input = row.querySelector('.grade-input');
                if (input) row.dataset.letterGrade = input.value;
            } else {
                const input = row.querySelector('.percent-input');
                if (input) row.dataset.percentGrade = input.value;
            }
        });
    }

    function renderSubjectInput(row, mode) {
        const inputGroup = row.querySelector('.subject-input-group');
        inputGroup.innerHTML = getSubjectInputHTML(mode);

        if (mode === 'subjects-letter') {
            const savedValue = row.dataset.letterGrade || '';
            if (savedValue) row.querySelector('.grade-input').value = savedValue;
        } else {
            row.querySelector('.percent-input').value = row.dataset.percentGrade || '';
        }
    }

    function summarizeSubjectRows(rows, mode) {
        let totalPoints = 0;
        let totalHours = 0;
        let failedSubjects = 0;
        let failedHours = 0;
        let allComplete = rows.length > 0;

        rows.forEach(row => {
            const hours = Number(row.querySelector('.hours-input')?.value) || 3;
            if (mode === 'subjects-letter') {
                const value = row.querySelector('.grade-input')?.value;
                if (!value) { allComplete = false; return; }
                totalPoints += gradePoints[value] * hours;
                totalHours += hours;
                if (value === 'F') { failedSubjects++; failedHours += hours; }
            } else {
                const value = row.querySelector('.percent-input')?.value;
                const percent = Number(value);
                if (!value || value === '' || isNaN(percent) || percent < 0 || percent > 100) { allComplete = false; return; }
                totalPoints += convertPercentToPoints(percent) * hours;
                totalHours += hours;
                if (percent < 50) { failedSubjects++; failedHours += hours; }
            }
        });

        return { gpa: allComplete && totalHours > 0 ? totalPoints / totalHours : null, failedSubjects, failedHours, totalHours };
    }

    function getSubjectInputHTML(mode) {
        if (mode === 'subjects-letter') {
            return `<select class="grade-input" onchange="clearError(this)">
                    <option value="" disabled selected>التقدير</option>
                    <option value="A+">A+ / أ+</option><option value="A">A / أ</option>
                    <option value="B+">B+ / ب+</option><option value="B">B / ب</option>
                    <option value="C+">C+ / ج+</option><option value="C">C / ج</option>
                    <option value="D+">D+ / د+</option><option value="D">D / د</option>
                    <option value="F">F / ر / رن / رل</option>
                    </select>`;
        } else {
            return `<input type="number" class="percent-input" placeholder="الدرجة" min="0" max="100" oninput="clearError(this)">`;
        }
    }

    function handleGradeKeyboard(event, select) {
        if (event.ctrlKey || event.altKey || event.metaKey) return;
        const key = event.key.toLowerCase();
        const gradeMap = { 'a': 'A', 'b': 'B', 'c': 'C', 'd': 'D', 'f': 'F' };
        const grade = gradeMap[key];
        if (!grade) return;
        event.preventDefault();
        select.value = grade;
        select.dispatchEvent(new Event('change', { bubbles: true }));
    }

    document.addEventListener('keydown', event => {
        if (event.target.matches('.grade-input')) handleGradeKeyboard(event, event.target);
    }, true);

    function addSubject(termId, shouldSave = true) {
        const listContainer = document.getElementById(`${termId}-list`);
        if (listContainer.querySelectorAll('.subject-row').length >= MAX_SUBJECTS_PER_TERM) {
            if (shouldSave) showAlert("تنبيه", `الحد الأقصى لعدد المواد هو ${MAX_SUBJECTS_PER_TERM}.`, "⚠️");
            return;
        }
        
        const term = document.getElementById(termId);
        const checkedRadio = term.querySelector(`input[name="mode-${termId}"]:checked`);
        let mode = checkedRadio ? checkedRadio.value : term.dataset.currentMode;
        if (mode === 'term-gpa') mode = 'subjects-letter';
        
        const currentCount = listContainer.querySelectorAll('.subject-row').length + 1;
        const row = document.createElement('div');
        row.className = 'subject-row';
        row.innerHTML = `<input type="text" class="subject-name" placeholder="اسم المادة" value="مادة ${currentCount}">
            <div class="hours-group"><input type="number" class="hours-input" placeholder="الساعات" title="عدد الساعات المعتمدة" min="1" max="6" step="1" value="3" oninput="clearError(this)"></div>
            <div class="subject-input-group">${getSubjectInputHTML(mode)}</div>
            <button class="btn-delete" onclick="removeSubject(this)"></button>`;
        listContainer.appendChild(row);
        
        if (shouldSave) {
            updateTermBadgePreview(document.getElementById(termId));
            saveData();
        }
    }
    
    function removeSubject(btn) {
        const term = btn.closest('.term-card');
        const listContainer = btn.closest('.subjects-list');
        btn.parentElement.remove();
        renumberDefaultSubjectNames(listContainer);
        updateRepeatedSubjectsFields();
        updateTermBadgePreview(term);
        saveData();
    }

    function renumberDefaultSubjectNames(listContainer) {
        const rows = listContainer.querySelectorAll('.subject-row');
        rows.forEach((row, index) => {
            const nameInput = row.querySelector('.subject-name');
            if (/^مادة\s+\d+$/.test(nameInput.value.trim())) nameInput.value = `مادة ${index + 1}`;
        });
    }

    function convertPercentToPoints(percent) {
        if (percent >= 90) return 4.0; if (percent >= 85) return 3.7;
        if (percent >= 80) return 3.3; if (percent >= 75) return 3.0;
        if (percent >= 70) return 2.7; if (percent >= 65) return 2.4;
        if (percent >= 60) return 2.2; if (percent >= 50) return 2.0;
        return 0.0;
    }

    function convertPercentToLetter(percent) {
        if (percent >= 90) return 'A+'; if (percent >= 85) return 'A';
        if (percent >= 80) return 'B+'; if (percent >= 75) return 'B';
        if (percent >= 70) return 'C+'; if (percent >= 65) return 'C';
        if (percent >= 60) return 'D+'; if (percent >= 50) return 'D';
        return 'F';
    }

    function showInputError(input, message) {
        const parent = input.parentElement;
        let errorMsg = parent.querySelector('.error-feedback');
        if (!errorMsg) {
            errorMsg = document.createElement('div');
            errorMsg.className = 'error-feedback';
            parent.appendChild(errorMsg);
        }
        input.classList.add('input-error');
        errorMsg.innerText = message;
        errorMsg.classList.add('visible');
    }

    function validateWholeNumber(input, min, max) {
        if (!validateInput(input, min, max)) return false;
        if (!Number.isInteger(Number(input.value))) {
            showInputError(input, "يجب إدخال عدد صحيح");
            return false;
        }
        return true;
    }

    function getTermCalculationData(term) {
        const checkedRadio = term.querySelector(`input[name="mode-${term.id}"]:checked`);
        const mode = checkedRadio ? checkedRadio.value : term.dataset.currentMode;
        
        const repeatedInput = term.querySelector('.repeated-subjects-input');
        const maxRepeatedSubjects = Number(repeatedInput.dataset.available || 0);
        const repeatedIsValid = validateWholeNumber(repeatedInput, 0, maxRepeatedSubjects);

        if (!repeatedIsValid) return null;
        const repeatedRaw = Number(repeatedInput.value);
        const repeatedHours = repeatedRaw * getRegUnit();

        if (mode === 'term-gpa') {
            const gpaInput = term.querySelector('.term-gpa-input');
            const registeredInput = term.querySelector('.registered-subjects-input');
            const failedInput = term.querySelector('.failed-subjects-input');
            
            if (!validateInput(gpaInput, 0, 4) || !validateWholeNumber(registeredInput, 1, getRegMax()) || !validateWholeNumber(failedInput, 0, getRegMax())) return null;

            const gpa = Number(gpaInput.value);
            if(isNaN(gpa)) return null;

            const registeredRaw = Number(registeredInput.value);
            const failedRaw = Number(failedInput.value);
            
            if (failedRaw > registeredRaw) {
                showInputError(failedInput, getFailedErrorMessage());
                return null;
            }

            const registeredHours = registeredRaw * getRegUnit();
            return {
                gpa: gpa,
                totalPoints: gpa * registeredHours,
                registeredHours: registeredHours - repeatedHours
            };
        }

        const rows = term.querySelectorAll('.subject-row');
        let totalCoursePoints = 0, failedSubjects = 0, totalHours = 0, isValid = rows.length > 0;

        rows.forEach(row => {
            const hoursInput = row.querySelector('.hours-input');
            if (!validateWholeNumber(hoursInput, 1, 6)) { isValid = false; return; }
            const hours = Number(hoursInput.value);

            if (mode === 'subjects-letter') {
                const gradeSelect = row.querySelector('.grade-input');
                if (!validateInput(gradeSelect)) { isValid = false; return; }
                totalCoursePoints += gradePoints[gradeSelect.value] * hours;
                if (gradeSelect.value === 'F') failedSubjects++;
            } else {
                const percentInput = row.querySelector('.percent-input');
                if (!validateInput(percentInput, 0, 100)) { isValid = false; return; }
                const percent = Number(percentInput.value);
                if (isNaN(percent)) { isValid = false; return; }
                totalCoursePoints += convertPercentToPoints(percent) * hours;
                if (percent < 50) failedSubjects++;
            }
            totalHours += hours;
        });

        if (!isValid || totalHours === 0) return null;

        return {
            gpa: totalCoursePoints / totalHours,
            totalPoints: totalCoursePoints,
            registeredHours: totalHours - repeatedHours
        };
    }

    function calculateOverallGPA() {
        updateRepeatedSubjectsFields();
        const terms = document.querySelectorAll('.term-card');
        let totalCoursePoints = 0, totalRegisteredHours = 0, validTermsCount = 0, pendingFailedHours = 0;
        let hasError = false, hasAnyFailedSubject = false;
        const chartLabels = [], semesterGpas = [], cumulativeGpas = [];

        terms.forEach(term => {
            const badge = term.querySelector('.term-result-badge');
            const termData = getTermCalculationData(term);
            const failedSubjects = getTermFailedSubjects(term);
            const failedHours = getTermFailedHours(term);
            const repeatedHours = (Number(term.querySelector('.repeated-subjects-input').value) || 0) * getRegUnit();
            
            if (failedSubjects > 0) hasAnyFailedSubject = true;
            pendingFailedHours = Math.max(0, pendingFailedHours - repeatedHours) + failedHours;

            if (termData) {
                totalCoursePoints += termData.totalPoints;
                totalRegisteredHours += termData.registeredHours;
                validTermsCount++;
                chartLabels.push(term.querySelector('.term-header input[type="text"]').value);
                semesterGpas.push(Number(termData.gpa.toFixed(2)));
                cumulativeGpas.push(totalRegisteredHours > 0 ? Number((totalCoursePoints / totalRegisteredHours).toFixed(2)) : null);
                
                badge.innerText = `فصلي: ${termData.gpa.toFixed(2)} | تراكمي: ${(totalCoursePoints / totalRegisteredHours).toFixed(2)}`;
                badge.classList.add('visible');
            } else {
                hasError = true;
                badge.classList.remove('visible');
            }
        });

        if (hasError) {
             showAlert("تنبيه", "توجد حقول ناقصة أو خاطئة (باللون الأحمر).", "⚠️");
        } else if (validTermsCount > 0) {
            const finalGPA = totalCoursePoints / totalRegisteredHours;
            document.getElementById('final-gpa').innerText = finalGPA.toFixed(2);
            const finalGrade = getGradeText(finalGPA);
            document.getElementById('final-grade').innerText = hasAnyFailedSubject ? finalGrade : `${finalGrade} مع مرتبة الشرف`;
            
            const passedHours = Math.max(0, totalRegisteredHours - pendingFailedHours);
            renderAcademicStatus(finalGPA, passedHours);
            
            let dynamicColor = getComputedStyle(document.documentElement).getPropertyValue('--danger-color').trim();
            if (finalGPA >= 3.0) dynamicColor = getComputedStyle(document.documentElement).getPropertyValue('--success-color').trim();
            else if (finalGPA >= 2.0) dynamicColor = getComputedStyle(document.documentElement).getPropertyValue('--warning-color').trim();
            
            document.documentElement.style.setProperty('--dynamic-color', dynamicColor);

            const circle = document.getElementById('progress-circle');
            const radius = circle.r.baseVal.value;
            const circumference = 2 * Math.PI * radius;
            circle.style.strokeDashoffset = circumference - (Math.min(finalGPA / 4.0, 1) * circumference);

            renderTermsSummary(chartLabels, semesterGpas, cumulativeGpas);
            renderGpaCharts(chartLabels, semesterGpas, cumulativeGpas);
            document.getElementById('result-modal').classList.add('active');
            if (finalGPA >= 3.0) triggerConfetti();
        } else {
            showAlert("تنبيه", "الرجاء إدخال بيانات صحيحة في ترم واحد على الأقل للمتابعة.", "⚠️");
        }
    }

    function renderAcademicStatus(gpa, passedHours) {
        const status = document.getElementById('academic-status');
        const list = document.getElementById('academic-status-list');
        const allowedSubjects = gpa >= 2 ? 6 : gpa >= 1.5 ? 5 : 4;
        const allowedHours = allowedSubjects * 3;

        let level = 'المستوى الأول';
        if (passedHours >= 102) level = 'المستوى الرابع';
        else if (passedHours >= 66) level = 'المستوى الثالث';
        else if (passedHours >= 30) level = 'المستوى الثاني';

        const items = [
            ['الحد الأقصى للتسجيل', `${allowedSubjects} مواد (${allowedHours} ساعة)`],
            ['المستوى الحالي', level],
            ['الساعات المجتازة', `${passedHours} ساعة`]
        ];

        if (passedHours >= 102 && passedHours <= 126) {
            items.push(['مشروع التخرج', 'يمكنك تسجيل مشروع التخرج (6 ساعات على فصلين دراسيين)']);
        } else if (passedHours >= 90) {
            items.push(['مشروع التخرج', `متبقي ${102 - passedHours} ساعة لتسجيل مادة المشروع`]);
        }

        if (passedHours < 60) items.push(['التخصص', `لا يمكنك التخصص في الترم القادم — متبقي ${Math.ceil((60 - passedHours) / 3)} مادة`]);
        else if (passedHours <= 72) items.push(['التخصص', 'يمكنك التخصص في أي قسم الترم القادم']);

        if (passedHours >= 144) items.push(['التخرج', 'استوفيت شرط الساعات المطلوبة للتخرج (144 ساعة)']);
        else if (passedHours >= 126) items.push(['التخرج', `متبقي ${144 - passedHours} ساعة للتخرج`]);

        list.innerHTML = items.map(([label, value]) => `
            <div class="academic-status-item"><span class="academic-status-label">${label}</span><span class="academic-status-value">${value}</span></div>
        `).join('');
        status.classList.add('visible');
    }

    function renderTermsSummary(labels, semesterValues, cumulativeValues) {
        document.getElementById('terms-summary-list').innerHTML = labels.map((label, index) => `
            <div class="summary-term-row">
                <span class="summary-term-name">${escapeHtml(label)}</span>
                <span class="summary-semester">فصلي ${semesterValues[index].toFixed(2)}</span>
                <span class="summary-cumulative">تراكمي ${cumulativeValues[index].toFixed(2)}</span>
            </div>
        `).join('');
        document.getElementById('terms-result-summary').classList.toggle('visible', labels.length > 0);
    }

    function escapeHtml(value) {
        const div = document.createElement('div');
        div.textContent = value;
        return div.innerHTML;
    }

    function renderGpaCharts(labels, semesterValues, cumulativeValues) {
    const section = document.getElementById('charts-section');

    if (semesterGpaChart) { semesterGpaChart.destroy(); semesterGpaChart = null; }
    if (cumulativeGpaChart) { cumulativeGpaChart.destroy(); cumulativeGpaChart = null; }

    if (labels.length < 2 || typeof Chart === 'undefined') { section.classList.remove('visible'); return; }

    section.classList.add('visible');

    // ألوان عالية التباين تضمن الوضوح العالي للمحاور والنصوص على خلفية الموقع الداكنة
    const textColor = '#e2e8f0'; // لون نصوص التسميات (X و Y) - فاتح وواضح
    const gridColor = 'rgba(148, 163, 184, 0.15)'; // لون خطوط الشبكة الهادئ
    const tooltipBg = 'rgba(15, 23, 42, 0.96)'; // خلفية التولتيب المعتمة

    const validValues = [...semesterValues, ...cumulativeValues.filter(v => v !== null)];
    const maxValue = validValues.length > 0 ? Math.max(...validValues) : 4;
    const suggestedMax = Math.min(4.0, Math.ceil((maxValue + 0.2) * 10) / 10);

    const createOptions = () => ({
        responsive: true,
        maintainAspectRatio: false,
        locale: 'ar',
        animation: { duration: 600 },
        plugins: {
            legend: { display: false },
            tooltip: {
                rtl: true,
                enabled: true,
                displayColors: false,
                backgroundColor: tooltipBg,
                titleColor: '#f8fafc',
                bodyColor: '#60a5fa',
                borderColor: 'rgba(96, 165, 250, 0.35)',
                borderWidth: 1,
                padding: { top: 8, bottom: 8, left: 12, right: 12 },
                cornerRadius: 10,
                yAlign: 'bottom', // يرفع التولتيب أعلى النقطة حتى لا يحجبها
                caretPadding: 10, // مسافة أمان رأسية بين النقطة والتولتيب
                caretSize: 6,
                titleFont: { family: 'Cairo', size: 12, weight: '700' },
                bodyFont: { family: 'Cairo', size: 13, weight: '700' },
                callbacks: {
                    title: (items) => items[0] ? items[0].label : '',
                    label: (c) => `المعدل: ${Number(c.parsed.y).toFixed(2)}`
                }
            }
        },
        scales: {
            x: {
                ticks: {
                    color: textColor,
                    font: { family: 'Cairo', size: 12, weight: '600' },
                    padding: 6
                },
                grid: { display: false },
                border: { color: gridColor }
            },
            y: {
                beginAtZero: true,
                suggestedMax: suggestedMax > 0 ? suggestedMax : 4.0,
                max: 4.0,
                ticks: {
                    color: textColor,
                    font: { family: 'Cairo', size: 12, weight: '600' },
                    stepSize: 0.5,
                    padding: 6,
                    callback: (v) => Number(v).toFixed(1)
                },
                grid: { color: gridColor },
                border: { display: false }
            }
        },
        interaction: {
            mode: 'nearest',
            axis: 'x',
            intersect: false
        }
    });

    semesterGpaChart = new Chart(document.getElementById('semester-gpa-chart'), {
        type: 'line',
        data: {
            labels,
            datasets: [{
                data: semesterValues,
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.15)',
                pointBackgroundColor: '#3b82f6',
                pointBorderColor: '#0f172a',
                pointBorderWidth: 2,
                pointRadius: 5,
                pointHoverRadius: 8,
                pointHoverBackgroundColor: '#60a5fa',
                pointHoverBorderColor: '#ffffff',
                pointHoverBorderWidth: 2,
                borderWidth: 3,
                fill: true,
                tension: 0.3
            }]
        },
        options: createOptions()
    });

    cumulativeGpaChart = new Chart(document.getElementById('cumulative-gpa-chart'), {
        type: 'line',
        data: {
            labels,
            datasets: [{
                data: cumulativeValues,
                borderColor: '#f59e0b',
                backgroundColor: 'rgba(245, 158, 11, 0.15)',
                pointBackgroundColor: '#f59e0b',
                pointBorderColor: '#0f172a',
                pointBorderWidth: 2,
                pointRadius: 5,
                pointHoverRadius: 8,
                pointHoverBackgroundColor: '#fbbf24',
                pointHoverBorderColor: '#ffffff',
                pointHoverBorderWidth: 2,
                borderWidth: 3,
                fill: true,
                tension: 0.3,
                spanGaps: true
            }]
        },
        options: createOptions()
    });
    }

    async function shareResult() {
        const card = document.getElementById('capture-area');
        try {
            const currentColor = getComputedStyle(document.documentElement).getPropertyValue('--dynamic-color').trim();
            const canvas = await html2canvas(card, {
                backgroundColor: '#1e1e1e', scale: 3, logging: false, useCORS: true,
                onclone: (doc) => {
                    doc.body.classList.add('dark-mode');
                    const clonedCard = doc.getElementById('capture-area');
                    clonedCard.querySelectorAll('button').forEach(b => b.style.display = 'none');
                    const clonedCharts = doc.getElementById('charts-section');
                    if (clonedCharts) clonedCharts.style.display = 'none';
                    if (clonedCard) { clonedCard.style.backgroundColor = '#1e1e1e'; clonedCard.style.color = '#e0e0e0'; clonedCard.style.boxShadow = 'none'; }
                    const progressCircle = doc.getElementById('progress-circle');
                    if (progressCircle) progressCircle.style.stroke = currentColor;
                    const gpaNum = doc.querySelector('.gpa-number');
                    if (gpaNum) gpaNum.style.color = currentColor;
                    const svgBg = doc.querySelector('.progress-ring__circle-bg');
                    if(svgBg) svgBg.style.stroke = '#333333'; 
                    const gpaLbl = doc.querySelector('.gpa-label');
                    if(gpaLbl) gpaLbl.style.color = '#e0e0e0';
                }
            });
            const link = document.createElement('a'); link.download = 'GPA-Result.png'; link.href = canvas.toDataURL(); link.click();
        } catch (err) { console.error(err); alert('حدث خطأ أثناء حفظ الصورة'); }
    }

    function showAlert(title, message, icon) {
        document.getElementById('msg-title').innerText = title;
        document.getElementById('msg-desc').innerText = message;
        document.getElementById('msg-actions').innerHTML = `<button class="btn-ok" onclick="closeModal('general-modal')">حسناً</button>`;
        document.getElementById('general-modal').classList.add('active');
    }

    function confirmDeleteTerm(termId) {
        termIdToDelete = termId;
        document.getElementById('msg-title').innerText = "حذف الترم؟";
        document.getElementById('msg-desc').innerText = "سيتم حذف هذا الترم بجميع بياناته.";
        document.getElementById('msg-actions').innerHTML = `
            <button class="btn-cancel" onclick="closeModal('general-modal')">إلغاء</button>
            <button class="btn-danger-confirm" onclick="executeDeleteTerm()">حذف</button>
        `;
        document.getElementById('general-modal').classList.add('active');
    }

    function renumberDefaultTermNames() {
        document.querySelectorAll('.term-card').forEach((term, index) => {
            const nameInput = term.querySelector('.term-header input[type="text"]');
            if (/^ترم\s+\d+$/.test(nameInput.value.trim())) nameInput.value = `ترم ${index + 1}`;
        });
    }

    function executeDeleteTerm() {
        if (termIdToDelete) {
            const termElement = document.getElementById(termIdToDelete);
            if (termElement) termElement.remove();
            termIdToDelete = null;
            renumberDefaultTermNames();
            updateRepeatedSubjectsFields();
            saveData();
        }
        closeModal('general-modal');
    }

    function confirmResetAction() {
        document.getElementById('msg-title').innerText = "مسح الكل؟";
        document.getElementById('msg-desc').innerText = "سيتم حذف جميع الترمات نهائياً.";
        document.getElementById('msg-actions').innerHTML = `
            <button class="btn-cancel" onclick="closeModal('general-modal')">إلغاء</button>
            <button class="btn-danger-confirm" onclick="executeReset()">مسح</button>
        `;
        document.getElementById('general-modal').classList.add('active');
    }

    function executeReset() {
        const container = document.getElementById('terms-container');
        container.innerHTML = ''; termCounter = 0; addTerm();
        localStorage.removeItem('gpaData'); 
        window.scrollTo({ top: 0, behavior: 'smooth' });
        closeModal('general-modal');
    }

    function closeModal(modalId) { document.getElementById(modalId).classList.remove('active'); }
    function closeModalOutside(event, modalId) { if (event.target.id === modalId) closeModal(modalId); }

    function getGradeText(gpa) {
        if (gpa >= 3.5) return "ممتاز"; if (gpa >= 3.0) return "جيد جداً";
        if (gpa >= 2.5) return "جيد"; if (gpa >= 2.0) return "مقبول";
        if (gpa >= 1.5) return "ضعيف"; return "ضعيف جداً";
    }

    function triggerConfetti() {
        var duration = 3000; var animationEnd = Date.now() + duration;
        var defaults = { startVelocity: 30, spread: 360, ticks: 60, zIndex: 3000 };
        var interval = setInterval(function() {
            var timeLeft = animationEnd - Date.now();
            if (timeLeft <= 0) return clearInterval(interval);
            var particleCount = 50 * (timeLeft / duration);
            confetti(Object.assign({}, defaults, { particleCount, origin: { x: Math.random()*0.2 + 0.1, y: Math.random()-0.2 } }));
            confetti(Object.assign({}, defaults, { particleCount, origin: { x: Math.random()*0.2 + 0.7, y: Math.random()-0.2 } }));
        }, 250);
    }

    document.addEventListener('wheel', event => {
        if (event.target.matches('input[type="number"]')) event.target.blur();
    }, { passive: true });

    function loadGPACounter() {
        fetch("/counterFiles/counter?counter=gpa", { cache: "no-store" })
            .then(res => res.json()).then(data => { document.getElementById("visitCount").innerText = data.count ?? 0; })
            .catch(() => { document.getElementById("visitCount").innerText = "--"; });
    }

    const GPA_VISIT_KEY = "gpa_visit";
    const GPA_VISIT_TTL = 1 * 60 * 1000;
    const lastGPAVVisit = Number(sessionStorage.getItem(GPA_VISIT_KEY));
    const enteredFromStats = (() => {
        if (!document.referrer) return false;
        try {
            const referrer = new URL(document.referrer);
            return referrer.origin === location.origin && /^\/stats(?:\.php)?\/?$/.test(referrer.pathname);
        } catch (_) {
            return false;
        }
    })();
    const gpaVisitPromise = (!enteredFromStats && (!Number.isFinite(lastGPAVVisit) || Date.now() - lastGPAVVisit > GPA_VISIT_TTL))
        ? (() => {
        sessionStorage.setItem(GPA_VISIT_KEY, String(Date.now()));
        return fetch("/counterFiles/counter?action=increment&counter=gpa", {
            method: "POST",
            keepalive: true,
            cache: "no-store"
        }).catch(() => {});
    })()
        : Promise.resolve();

    gpaVisitPromise.finally(loadGPACounter);
    setInterval(loadGPACounter, 4000);
    window.onload = function() { syncHoursModeRadios(); loadData(); };
