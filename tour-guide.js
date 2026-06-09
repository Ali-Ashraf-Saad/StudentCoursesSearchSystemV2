// tour-guide.js - جولة تعريفية تفاعلية (مُحسَّنة)
(function() {
  // ========== 1. حقن التنسيقات ==========
  const style = document.createElement('style');
  style.textContent = `
    .tour-overlay {
      position: fixed; inset: 0;
      background: rgba(0,0,0,0.15);
      z-index: 9998; pointer-events: none;
    }

    .tour-highlight {
      position: fixed; z-index: 10000; pointer-events: none;
      box-shadow: 0 0 0 9999px rgba(0,0,0,0.7);
      border: 3px solid #3b82f6;
      border-radius: 12px;
      box-sizing: content-box;
      will-change: transform, top, left;
    }

    .tour-tooltip {
      position: fixed; z-index: 10001;
      background: #1e293b; color: #e2e8f0;
      border: 2px solid rgba(59,130,246,0.6);
      border-radius: 20px; padding: 22px 24px;
      max-width: 380px; text-align: right;
      direction: rtl; font-family: 'Cairo', sans-serif;
      box-shadow: 0 25px 60px rgba(0,0,0,0.8);
      pointer-events: auto;
      opacity: 0; transform: translateY(10px);
      transition: opacity 0.3s, transform 0.3s;
      will-change: transform, top, left;
    }
    .tour-tooltip.active { opacity: 1; transform: translateY(0); }

    .tour-tooltip h4 {
      margin: 0 0 10px; color: #60a5fa; font-size: 19px;
      display: flex; align-items: center; gap: 8px;
    }
    .tour-tooltip p { margin: 0 0 22px; font-size: 14px; line-height: 1.7; color: #cbd5e1; }

    .tour-buttons {
      display: flex; justify-content: space-between; align-items: center;
    }
    .tour-step-count { font-size: 12px; color: #64748b; }

    .tour-btn {
      background: rgba(59,130,246,0.15); border: 1px solid rgba(59,130,246,0.5);
      color: #60a5fa; font-size: 14px; font-weight: 600;
      padding: 10px 22px; border-radius: 12px; cursor: pointer;
      transition: 0.2s; margin-left: 10px;
    }
    .tour-btn:hover { background: rgba(59,130,246,0.3); }
    .tour-btn.skip {
      background: rgba(239,68,68,0.1); border-color: rgba(239,68,68,0.5);
      color: #f87171; margin-left: 0; margin-right: auto;
    }
    .tour-btn.skip:hover { background: rgba(239,68,68,0.25); }

    /* زر الرجوع إلى الأعلى */
    .back-to-top {
      position: fixed;
      bottom: 30px;
      left: 30px;
      width: 50px;
      height: 50px;
      background: rgba(59,130,246,0.15);
      border: 2px solid rgba(59,130,246,0.7);
      color: #60a5fa;
      border-radius: 50%;
      font-size: 24px;
      font-weight: bold;
      cursor: pointer;
      z-index: 9999;
      display: flex;
      align-items: center;
      justify-content: center;
      opacity: 0;
      transform: translateY(30px);
      transition: opacity 0.35s, transform 0.35s, background 0.2s;
      pointer-events: none;
      backdrop-filter: blur(12px);
      box-shadow: 0 8px 25px rgba(0,0,0,0.4);
    }
    .back-to-top.show {
      opacity: 1;
      transform: translateY(0);
      pointer-events: auto;
    }
    .back-to-top:hover {
      background: rgba(59,130,246,0.35);
      transform: translateY(-4px);
      box-shadow: 0 12px 30px rgba(59,130,246,0.3);
    }
  `;
  document.head.appendChild(style);

  // ========== 2. إنشاء زر الرجوع للأعلى ==========
  if (!document.querySelector('.back-to-top')) {
    const backToTopBtn = document.createElement('div');
    backToTopBtn.className = 'back-to-top';
    backToTopBtn.innerHTML = '⬆';
    backToTopBtn.title = 'الرجوع إلى أعلى الصفحة';
    backToTopBtn.addEventListener('click', () => {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
    document.body.appendChild(backToTopBtn);

    window.addEventListener('scroll', () => {
      if (window.scrollY > 500) {
        backToTopBtn.classList.add('show');
      } else {
        backToTopBtn.classList.remove('show');
      }
    });
  }

  // ========== 3. منطق الجولة ==========
  const pageKey = location.pathname.includes('/courses') ? 'tour_courses_final6' : 'tour_index_final6';
  if (localStorage.getItem(pageKey)) return;

  const isIndex = !location.pathname.includes('/courses');
  const isCourses = location.pathname.includes('/courses');

  let steps = [];
  if (isIndex) {
    steps = [
      { selector: '#search', title: 'ابحث باسمك', desc: 'اكتب <b>الاسم</b> (أو جزء منه). يدعم أيضاً الرقم الأكاديمي.', position: 'bottom' },
      { selector: '.nav-btn[onclick*="goCourses"]', title: 'صفحة المقررات', desc: 'اضغط هنا للانتقال إلى متابعة مواد الفرق وتحديد ما أنجزته.', position: 'bottom' },
      { selector: 'footer a', title: 'تواصل معي', desc: 'لأي مشكلة أو اقتراح أو فكرة تحسين، اضغط هنا. <br><small style="color:#fbbf24;">تذكير: جرّب تحديث الصفحة إذا واجهت خطأ.</small>', position: 'top' }
    ];
  } else if (isCourses) {
    steps = [
      { selector: '.search-section', title: 'البحث السريع', desc: 'ابحث عن أي مادة بالاسم العربي، الإنجليزي، أو الكود. البحث مرن ويتجاهل الأخطاء الإملائية الشائعة.', position: 'bottom' },
      { selector: '.filters', title: 'اختر الفرقة والقسم', desc: 'حدد الفرقة والقسم والترم لتصفية المواد التي تهمك.', position: 'bottom' },
      { selector: '#fillPreviousBtn', title: 'المواد السابقة تلقائياً', desc: 'بنقرة واحدة تُكمل كل مواد السنوات الماضية لتوفير الوقت.', position: 'bottom' },
      { selector: '.course-card:first-child', title: 'أكمل المواد', desc: 'بعد اختيار الفلتر، ستظهر المواد هنا. <b>اضغط على أي مادة</b> لتحديدها كمكتملة (ستتحول للخضراء).', position: 'top', fallbackSelector: '#coursesContainer' },
      { selector: '.select-all-btn:first-child', title: 'تحديد الكل', desc: 'اضغط هذا الزر لتحديد جميع مواد هذا الترم كمكتملة دفعة واحدة (أو مسحها إن كانت مكتملة).', position: 'top', fallbackSelector: '.semester-block:first-child .select-all-btn' },
      { selector: '.open-courses-btn:first-child', title: 'زر "يفتح"', desc: 'اضغط هذا الزر لترى جميع المواد التي تعتمد على هذه المادة كمتطلب سابق.', position: 'left', fallbackSelector: '.open-courses-btn' },
      { selector: '#reminderSection', title: 'تذكير المواد المتبقية', desc: 'ملخص سريع للمواد غير المكتملة في كل فرقة، يساعدك على التخطيط.', position: 'top' },
      { selector: 'footer a', title: 'تواصل معي', desc: 'لأي مشكلة أو اقتراح أو فكرة تحسين، اضغط هنا. <br><small style="color:#fbbf24;">تذكير: جرّب تحديث الصفحة إذا واجهت خطأ.</small>', position: 'top' }
    ];
  }

  if (!steps.length) return;

  const overlay = document.createElement('div'); overlay.className = 'tour-overlay';
  const highlight = document.createElement('div'); highlight.className = 'tour-highlight';
  const tooltip = document.createElement('div'); tooltip.className = 'tour-tooltip';
  tooltip.innerHTML = `
    <div class="tour-content"></div>
    <div class="tour-buttons">
      <span class="tour-step-count"></span>
      <div>
        <button class="tour-btn next">التالي ◀</button>
        <button class="tour-btn skip">تخطي</button>
      </div>
    </div>
  `;

  document.body.appendChild(overlay);
  document.body.appendChild(highlight);
  document.body.appendChild(tooltip);

  let currentStep = 0;
  let currentTarget = null;
  let currentPosition = null;
  let cachedBorderRadius = '12px';
  let rafId = null;

  function scrollToElement(el) {
    // ضمان ظهور العنصر كاملاً وعدم تغطيته بالتولتيب
    const tooltipHeight = 250;
    const rect = el.getBoundingClientRect();
    if (rect.top < tooltipHeight || rect.bottom > window.innerHeight - tooltipHeight) {
      el.scrollIntoView({ behavior: 'smooth', block: 'center' });
    } else {
      // فقط للتأكد من أنه مرئي
      el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
  }

  function smartPosition(targetEl, preferred) {
    const rect = targetEl.getBoundingClientRect();
    const margin = 20;
    const tipWidth = tooltip.offsetWidth || 350;
    const tipHeight = tooltip.offsetHeight || 190;

    const placements = {
      top:    { top: rect.top - tipHeight - margin, left: rect.left + rect.width/2 - tipWidth/2 },
      bottom: { top: rect.bottom + margin, left: rect.left + rect.width/2 - tipWidth/2 },
      left:   { top: rect.top + rect.height/2 - tipHeight/2, left: rect.left - tipWidth - margin },
      right:  { top: rect.top + rect.height/2 - tipHeight/2, left: rect.right + margin }
    };

    // ترتيب المحاولات
    const order = [preferred, ...Object.keys(placements).filter(p => p !== preferred)];
    let best = null, bestScore = -Infinity;

    for (const pos of order) {
      const p = placements[pos];
      const constrainedTop = Math.max(margin, Math.min(p.top, innerHeight - tipHeight - margin));
      const constrainedLeft = Math.max(margin, Math.min(p.left, innerWidth - tipWidth - margin));
      const topDiff = Math.abs(constrainedTop - p.top);
      const leftDiff = Math.abs(constrainedLeft - p.left);
      // نفضل الموضع الذي لم يُضطر للتعديل
      const score = 2000 - (topDiff + leftDiff) * 2;
      if (constrainedTop === p.top && constrainedLeft === p.left) {
        best = { top: p.top, left: p.left, pos };
        break; // وجدنا موضعاً مثالياً
      }
      if (score > bestScore) {
        bestScore = score;
        best = { top: constrainedTop, left: constrainedLeft, pos };
      }
    }

    if (!best) best = { top: margin, left: margin, pos: 'bottom' };

    tooltip.style.top = best.top + 'px';
    tooltip.style.left = best.left + 'px';
    tooltip.classList.add('active');
  }

  function updateHighlight(targetEl) {
    const rect = targetEl.getBoundingClientRect();
    highlight.style.top = rect.top - 3 + 'px';
    highlight.style.left = rect.left - 3 + 'px';
    highlight.style.width = rect.width + 'px';
    highlight.style.height = rect.height + 'px';
    highlight.style.display = 'block';
    highlight.style.borderRadius = cachedBorderRadius;
  }

  function updatePositions() {
    if (!currentTarget || !currentTarget.isConnected) return;
    updateHighlight(currentTarget);
    smartPosition(currentTarget, currentPosition || 'bottom');
  }

  function blockClicksOutside(e) {
    if (tooltip.contains(e.target)) return;
    e.stopPropagation();
    e.preventDefault();
  }

  function startRafLoop() {
    function loop() {
      updatePositions();
      rafId = requestAnimationFrame(loop);
    }
    rafId = requestAnimationFrame(loop);
  }

  function stopRafLoop() {
    if (rafId) {
      cancelAnimationFrame(rafId);
      rafId = null;
    }
  }

  function showStep(index) {
    const step = steps[index];
    let target = document.querySelector(step.selector);
    if (!target && step.fallbackSelector) target = document.querySelector(step.fallbackSelector);
    if (!target) {
      // تخطي الخطوة إذا لم يوجد العنصر
      if (index < steps.length - 1) {
        showStep(index + 1);
      } else {
        endTour();
      }
      return;
    }

    currentTarget = target;
    currentPosition = step.position || 'bottom';
    const style = getComputedStyle(target);
    cachedBorderRadius = style.borderRadius || '12px';

    scrollToElement(target);

    // انتظار قليل حتى يستقر التمرير ثم تحديث الموضع
    setTimeout(() => {
      updatePositions();
      tooltip.querySelector('.tour-content').innerHTML = `<h4>${step.title}</h4><p>${step.desc}</p>`;
      tooltip.querySelector('.tour-step-count').textContent = `${index + 1} من ${steps.length}`;
      const nextBtn = tooltip.querySelector('.next');
      nextBtn.textContent = index === steps.length - 1 ? 'إنهاء' : 'التالي ◀';
      nextBtn.onclick = () => {
        if (index < steps.length - 1) showStep(index + 1);
        else endTour();
      };
      tooltip.querySelector('.skip').onclick = endTour;
    }, 150);
  }

  function endTour() {
    stopRafLoop();
    document.removeEventListener('click', blockClicksOutside, true);
    overlay.remove();
    highlight.remove();
    tooltip.remove();
    window.scrollTo({ top: 0, behavior: 'smooth' });
    localStorage.setItem(pageKey, '1');
  }

  document.addEventListener('click', blockClicksOutside, true);
  startRafLoop();

  let attempts = 0;
  function tryStartTour() {
    if (location.pathname.includes('/courses')) {
      // انتظار ظهور العناصر المهمة في صفحة المقررات
      const firstCard = document.querySelector('.course-card:first-child');
      if (!firstCard) {
        if (attempts < 20) { attempts++; setTimeout(tryStartTour, 200); return; }
      }
    }
    showStep(0);
  }

  // تأخير بداية الجولة لضمان تحميل DOM بالكامل
  window.addEventListener('load', () => {
    setTimeout(tryStartTour, 300);
  });

})();