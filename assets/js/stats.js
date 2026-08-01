const countersConfig = [
    { id: 'courses', title: 'زيارات المقررات', icon: 'fa-book-open', color: '#3b82f6', url: '/courses' },
    { id: 'qa', title: 'زيارات سؤال وجواب', icon: 'fa-comments', color: '#10b981', url: '/qa' },
    { id: 'users', title: 'عمليات بحث الطلاب', icon: 'fa-magnifying-glass', color: '#f59e0b', url: '/' },
    { id: 'gpa', title: 'استخدام حاسبة المعدل', icon: 'fa-calculator', color: '#8b5cf6', url: '/gpa' }
]; // add here New counter

    const chartDataStore = {};
    const chartInstances = {};

    // كل فترة زمنية مخزّنة في chartDataStore تحت بادئة موحّدة:
    // <prefix>Data, <prefix>Labels, <prefix>IsCurrent, <prefix>Cumulative
    const VIEW_PREFIXES = {
        last1hour: 'minutely',
        last6hours: 'hourly6',
        last24hours: 'hourly',
        last7days: 'daily',
        last30days: 'daily30',
        last12months: 'monthly'
    };

    // يبني كائن chartDataStore الموحّد من استجابة الإحصائيات القادمة من الخادم
    function buildChartDataStoreEntry(stats) {
        const entry = {};
        Object.values(VIEW_PREFIXES).forEach(function(prefix) {
            entry[prefix + 'Data'] = stats[prefix + 'Data'];
            entry[prefix + 'Labels'] = stats[prefix + 'Labels'];
            entry[prefix + 'IsCurrent'] = stats[prefix + 'IsCurrent'];
            entry[prefix + 'Cumulative'] = stats[prefix + 'Cumulative'];
        });
        return entry;
    }

    // يستخرج بيانات/تسميات/علامة "الفترة الحالية" لعرض معيّن (last24hours, last7days...) من أي مصدر بيانات بنفس الشكل
    function getViewSeries(dataStore, view) {
        const prefix = VIEW_PREFIXES[view] || VIEW_PREFIXES.last24hours;
        return {
            data: dataStore[prefix + 'Data'],
            labels: dataStore[prefix + 'Labels'],
            isCurrent: dataStore[prefix + 'IsCurrent'],
            cumulative: dataStore[prefix + 'Cumulative']
        };
    }

    // ============================================
    // إدارة زر التلميحات
    // ============================================
    function toggleHints() {
        const body = document.body;
        const btn = document.getElementById('hintsToggleBtn');
        const text = document.getElementById('hintsToggleText');
        
        body.classList.toggle('show-hints');
        const isActive = body.classList.contains('show-hints');
        
        if (isActive) {
            btn.classList.add('active');
            text.innerText = 'إيقاف التلميحات';
            localStorage.setItem('statsHintsEnabled', 'true');
        } else {
            btn.classList.remove('active');
            text.innerText = 'تفعيل التلميحات';
            localStorage.setItem('statsHintsEnabled', 'false');
        }
    }
    
    // استرجاع حالة الزر عند تحميل الصفحة
    function initHintsToggle() {
        const saved = localStorage.getItem('statsHintsEnabled');
        if (saved === 'true') {
            document.body.classList.add('show-hints');
            const btn = document.getElementById('hintsToggleBtn');
            const text = document.getElementById('hintsToggleText');
            btn.classList.add('active');
            text.innerText = 'إيقاف التلميحات';
        }
    }

    const tooltipPlugin = Chart.registry.getPlugin('tooltip');
    if (tooltipPlugin && tooltipPlugin.positioners) {
        tooltipPlugin.positioners.topFixed = function(elements) {
            if (!elements.length) return false;
            const activeElement = elements[0];
            const chart = this.chart;
            const x = activeElement.element.x;
            const left = chart.chartArea.left;
            const right = chart.chartArea.right;
            let offset = 0;
            if (right > left) {
                const ratio = (x - left) / (right - left);
                offset = ratio * 4;
            }
            return {
                x: x - offset,
                y: chart.chartArea.top,
                xAlign: 'center',
                yAlign: 'bottom'
            };
        };
    }

    function buildStatsActionUrl(action) {
        const url = new URL(window.location.href);
        url.search = '';
        url.searchParams.set('action', action);
        url.searchParams.set('t', Date.now());
        return url.toString();
    }

    async function fetchJsonAction(action) {
        const response = await fetch(buildStatsActionUrl(action), {
            cache: 'no-store',
            headers: { 'Accept': 'application/json' }
        });

        if (!response.ok) throw new Error('HTTP ' + response.status);

        const contentType = response.headers.get('content-type') || '';
        const responseText = await response.text();
        if (!contentType.includes('application/json')) {
            throw new Error('Expected JSON, got ' + (contentType || 'unknown content type') + ': ' + responseText.slice(0, 80));
        }

        return JSON.parse(responseText);
    }

    async function fetchAllStats() {
        try {
            return await fetchJsonAction('get_stats');
        } catch (error) {
            console.error('فشل جلب الإحصائيات:', error);
            return null;
        }
    }

// يحسب أنماط النقاط (اللون/الحجم) بناءً على أيّها تمثّل "الفترة الحالية"
function computePointStyles(isCurrentArray) {
    const pointBackgroundColor = [], pointRadius = [], pointBorderWidth = [], pointHoverRadius = [];
    isCurrentArray.forEach(function(isCurrent) {
        if (isCurrent) {
            pointBackgroundColor.push('#fff');
            pointRadius.push(8);
            pointHoverRadius.push(10);
            pointBorderWidth.push(3);
        } else {
            pointBackgroundColor.push('#0f172a');
            pointRadius.push(4);
            pointHoverRadius.push(7);
            pointBorderWidth.push(2);
        }
    });
    return { pointBackgroundColor, pointRadius, pointHoverRadius, pointBorderWidth };
}

// يبرز نقطة "الفترة الحالية" على رسم بياني موجود بالفعل (تحديثات لاحقة بدون أنيميشن)
function updateCurrentHighlight(chart, isCurrentArray, color) {
    const style = computePointStyles(isCurrentArray);
    chart.data.datasets[0].pointBackgroundColor = style.pointBackgroundColor;
    chart.data.datasets[0].pointRadius = style.pointRadius;
    chart.data.datasets[0].pointHoverRadius = style.pointHoverRadius;
    chart.data.datasets[0].pointBorderWidth = style.pointBorderWidth;
    chart.data.datasets[0].pointBorderColor = color;
}

    function getCardHTML(config, stats) {
        return '<div class="card-header">' +
                '<div class="card-title"><a href="' + config.url + '"><i class="fas ' + config.icon + '"></i> ' + config.title + '</a></div>' +
            '</div>' +
            '<div style="text-align: center;">' +
                '<div style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 0.5rem; font-weight: 600;">الإجمالي</div>' +
                '<div class="main-counter" style="color: ' + config.color + '">' + stats.total.toLocaleString('en-US') + '</div>' +
            '</div>' +
            '<div class="stats-grid">' +
                '<div class="stat-item" data-tooltip="إجمالي عدد الزيارات المسجلة منذ بداية اليوم (12:00 صباحاً) حتى الآن">' +
                    '<div class="stat-label"><i class="fas fa-calendar-day" style="color: var(--success)"></i> زيارات اليوم</div>' +
                    '<div class="stat-value success">' + stats.today.toLocaleString('en-US') + '</div>' +
                '</div>' +
                '<div class="stat-item" data-tooltip="عدد الزيارات المسجلة في الساعة الحالية فقط">' +
                    '<div class="stat-label"><i class="fas fa-clock" style="color: var(--primary-blue)"></i> هذه الساعة</div>' +
                    '<div class="stat-value">' + stats.thisHour.toLocaleString('en-US') + '</div>' +
                '</div>' +
                '<div class="stat-item" data-tooltip="إجمالي عدد الزيارات منذ بداية الأسبوع الحالي (يبدأ من السبت)">' +
                    '<div class="stat-label"><i class="fas fa-calendar-week" style="color: #8b5cf6"></i> هذا الأسبوع</div>' +
                    '<div class="stat-value">' + stats.thisWeek.toLocaleString('en-US') + '</div>' +
                '</div>' +
                '<div class="stat-item" data-tooltip="متوسط عدد الزيارات اليومية خلال آخر 30 يوماً">' +
                    '<div class="stat-label"><i class="fas fa-chart-line" style="color: #a855f7"></i> متوسط يومي</div>' +
                    '<div class="stat-value">' + stats.avgPerDay + '</div>' +
                '</div>' +
                '<div class="stat-item" data-tooltip="الساعة التي سجلت أعلى عدد من الزيارات خلال آخر 24 ساعة">' +
                    '<div class="stat-label"><i class="fas fa-crown" style="color: var(--warning)"></i> ساعة الذروة</div>' +
                    '<div class="stat-value highlight">' + stats.peakHour + '</div>' +
                '</div>' +
                '<div class="stat-item" data-tooltip="الوقت المنقضي منذ آخر زيارة مسجلة في النظام">' +
                    '<div class="stat-label"><i class="fas fa-clock-rotate-left" style="color: #06b6d4"></i> آخر زيارة</div>' +
                    '<div class="stat-value">' + stats.lastVisitFormatted + '</div>' +
                '</div>' +
            '</div>' +
            '<div class="chart-controls">' +
                '<button class="toggle-btn" onclick="switchChartView(\'' + config.id + '\', \'last1hour\', this)">ساعة</button>' +
                '<button class="toggle-btn" onclick="switchChartView(\'' + config.id + '\', \'last6hours\', this)">6 ساعات</button>' +
                '<button class="toggle-btn active" onclick="switchChartView(\'' + config.id + '\', \'last24hours\', this)">24 ساعة</button>' +
                '<button class="toggle-btn" onclick="switchChartView(\'' + config.id + '\', \'last7days\', this)">7 أيام</button>' +
                '<button class="toggle-btn" onclick="switchChartView(\'' + config.id + '\', \'last30days\', this)">30 يوم</button>' +
                '<button class="toggle-btn" onclick="switchChartView(\'' + config.id + '\', \'last12months\', this)">12 شهر</button>' +
            '</div>' +
            '<div class="chart-container"><canvas id="chart-' + config.id + '"></canvas></div>';
    }

// ينشئ كرت إحصائية جديد في أول تحميل، أو يحدّث كرتًا موجودًا في التحديثات اللاحقة
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

        chartDataStore[config.id] = buildChartDataStoreEntry(stats);

        setTimeout(function() {
            renderChart(config.id, config.color, stats.hourlyData, stats.hourlyLabels, stats.hourlyIsCurrent, 'last24hours');
        }, 100);
    }
}


// ينشئ رسمًا بيانيًا جديدًا (Chart.js) لكرت إحصائية، مع أنيميشن "رسم الخط" عند الإنشاء فقط
function renderChart(canvasId, color, initialData, initialLabels, initialIsCurrent, defaultView) {
    const ctx = document.getElementById('chart-' + canvasId).getContext('2d');
    const gradient = ctx.createLinearGradient(0, 0, 0, 250);
    gradient.addColorStop(0, color + '50'); 
    gradient.addColorStop(1, color + '00'); 

    // نحسب أنماط النقاط مسبقًا ونضعها ضمن إعداد الرسم الابتدائي بدل استدعاء update() بعد
    // الإنشاء مباشرة - فاستدعاء update('none') فور الإنشاء كان يُلغي أنيميشن الرسم قبل تشغيله
    const pointStyle = computePointStyles(initialIsCurrent);

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
                pointBackgroundColor: pointStyle.pointBackgroundColor,
                pointBorderColor: color,
                pointBorderWidth: pointStyle.pointBorderWidth,
                pointRadius: pointStyle.pointRadius,
                pointHoverRadius: pointStyle.pointHoverRadius,
                pointHoverBackgroundColor: color,
                pointHoverBorderColor: '#fff',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'nearest', axis: 'x', intersect: false },
            layout: {
                padding: { top: 80 }
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    position: 'topFixed',
                    caretSize: 7,
                    backgroundColor: 'rgba(15, 23, 42, 0.95)',
                    titleFont: { family: 'Cairo', size: 14, weight: 'bold' },
                    bodyFont: { family: 'Cairo', size: 13, weight: '600' },
                    padding: 10, cornerRadius: 10, displayColors: false,
                    borderColor: 'rgba(255,255,255,0.1)', borderWidth: 1,
                    callbacks: {
                        label: function(context) {
                            const view = context.chart.currentView || 'last24hours';
                            const store = chartDataStore[context.chart.canvas.id.replace('chart-', '')];
                            if (!store) return '';

                            const increment = context.parsed.y;
                            const cumulative = getViewSeries(store, view).cumulative[context.dataIndex];

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
                    grid: { display: false },
                    border: { display: false },
                    ticks: { color: '#64748b', font: { family: 'Cairo', size: 11 }, maxTicksLimit: 8 }
                },
                y: {
                    grid: { color: 'rgba(255, 255, 255, 0.04)' },
                    border: { display: false },
                    ticks: { display: false }, beginAtZero: true
                }
            },
            // أنيميشن "رسم الخط" عند الإنشاء لأول مرة
            animation: { duration: 800, easing: 'easeOutQuart' },
            // تعطيل أنيميشن "resize" تحديدًا (وليس كل الأنيميشن): هذا هو السبب الحقيقي للخط
            // العمودي الذي كان يظهر يمين الرسم ويزيحه لليسار عند إعادة بناء الكروت بعد مسح
            // الكاش - حجم الكرت يتغيّر بفارق بسيط (تحميل خط Cairo / استقرار التخطيط) أثناء
            // تشغيل أنيميشن الإنشاء، فيقوم Chart.js تلقائيًا بعمل resize متحرك في منتصف
            // الحركة وهو ما يُنتج الخط/الإزاحة. تعطيل أنيميشن resize فقط يمنع هذا تحديدًا.
            transitions: {
                resize: {
                    animation: { duration: 0 }
                }
            },
            resizeDelay: 100
        }
    });

    chartInstances[canvasId] = chart;
    chartInstances[canvasId].currentView = defaultView;
}


// يحدّث الأرقام والرسم البياني لكرت موجود دون إعادة إنشائه (يحافظ على حالة التلميح الظاهر إن وجد)
function updateCard(config, stats) {
    const card = document.getElementById('card-wrapper-' + config.id);
    if (!card) return;
    
    card.querySelector('.main-counter').innerText = stats.total.toLocaleString('en-US');
    
    const statValues = card.querySelectorAll('.stat-value');
    statValues[0].innerText = stats.today.toLocaleString('en-US');
    statValues[1].innerText = stats.thisHour.toLocaleString('en-US');
    statValues[2].innerText = stats.thisWeek.toLocaleString('en-US');
    statValues[3].innerText = stats.avgPerDay;
    statValues[4].innerText = stats.peakHour;
    statValues[5].innerText = stats.lastVisitFormatted;

    chartDataStore[config.id] = buildChartDataStoreEntry(stats);
    
    const chart = chartInstances[config.id];
    if (chart) {
        const activeElements = chart.getActiveElements();
        const hasActiveTooltip = activeElements.length > 0;
        const activeIndex = hasActiveTooltip ? activeElements[0].index : -1;

        const currentView = chart.currentView || 'last24hours';
        const series = getViewSeries(chartDataStore[config.id], currentView);

        chart.data.labels = series.labels;
        chart.data.datasets[0].data = series.data;
        updateCurrentHighlight(chart, series.isCurrent, config.color);
        
        chart.update('none');
        
        if (hasActiveTooltip && activeIndex >= 0 && activeIndex < series.data.length) {
            chart.setActiveElements([{datasetIndex: 0, index: activeIndex}]);
            chart.tooltip.setActiveElements([{datasetIndex: 0, index: activeIndex}], {x: 0, y: 0});
            chart.update('none');
        }
    }
}

// يبدّل الفترة الزمنية المعروضة على رسم بياني معيّن عند الضغط على أحد أزرار التبويب
function switchChartView(id, view, btnElement) {
    const buttons = btnElement.parentElement.querySelectorAll('.toggle-btn');
    buttons.forEach(function(btn) { btn.classList.remove('active'); });
    btnElement.classList.add('active');

    const chart = chartInstances[id];
    const data = chartDataStore[id];
    if (!chart || !data) return;
    
    chart.currentView = view;
    const series = getViewSeries(data, view);

    chart.data.labels = series.labels;
    chart.data.datasets[0].data = series.data;
    updateCurrentHighlight(chart, series.isCurrent, chart.data.datasets[0].borderColor);
    chart.update('none');
}

    async function downloadAllLogs() {
        try {
            const data = await fetchJsonAction('list_logs');
            const files = data.files || [];
            if (files.length === 0) {
                alert('لا توجد ملفات JSONL داخل مجلد السجلات');
                return;
            }
            for (const file of files) {
                const a = document.createElement('a');
                const downloadUrl = new URL(buildStatsActionUrl('download_log'));
                downloadUrl.searchParams.set('file', file);
                a.href = downloadUrl.toString();
                a.download = file;
                document.body.appendChild(a);
                a.click();
                a.remove();
                await new Promise(resolve => setTimeout(resolve, 250));
            }
        } catch (error) {
            console.error(error);
            alert('حدث خطأ أثناء تحميل الملفات');
        }
    }

async function resetServerCache() {
    try {
        const result = await fetchJsonAction('reset_cache');
        if (result.success) {
            // تدمير جميع الرسوم البيانية الحالية
            Object.keys(chartInstances).forEach(function(id) {
                if (chartInstances[id]) {
                    chartInstances[id].destroy();
                }
            });
            
            // مسح المصفوفات
            for (let key in chartInstances) delete chartInstances[key];
            for (let key in chartDataStore) delete chartDataStore[key];
            
            document.getElementById('dashboard').innerHTML = '<div class="loader-container"><span class="loader"></span><p style="margin-top: 1.5rem; color: var(--text-secondary); font-weight: 600;">جاري إعادة حساب الإحصائيات...</p></div>';
            setTimeout(initDashboard, 1000);
        }
    } catch (error) {
        alert('حدث خطأ أثناء إعادة الضبط');
    }
}

    let statsRefreshInFlight = false;

    async function initDashboard() {
        if (statsRefreshInFlight) return;
        statsRefreshInFlight = true;

        const dashboard = document.getElementById('dashboard');
        try {
            const allStats = await fetchAllStats();
            
            if (!allStats) {
                if (!dashboard.querySelector('.card[id^="card-wrapper-"]')) {
                    dashboard.innerHTML = '<div class="card error-card" style="grid-column: 1/-1; border-color: var(--danger); animation: fadeInUp 0.6s ease-out forwards;"><div class="card-header"><div class="card-title" style="color: var(--danger);"><i class="fas fa-exclamation-triangle"></i> فشل الاتصال بالخادم</div></div><div style="text-align: center; padding: 2rem; color: var(--text-secondary);"><i class="fas fa-server" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3; color: var(--danger);"></i><p style="font-weight: 700; margin-bottom: 0.5rem; color: var(--text-main);">تعذر جلب البيانات من الخادم</p></div></div>';
                }
                return;
            }
            
            const loader = dashboard.querySelector('.loader-container');
            if (loader) loader.remove();

            const errorCard = dashboard.querySelector('.error-card');
            if (errorCard) errorCard.remove();
            
            countersConfig.forEach(function(config) {
                const stats = allStats[config.id];
                if (stats) createOrUpdateCard(config, stats);
            });
            
            const now = new Date();
            const timeStr = now.toLocaleTimeString('ar-EG', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            document.getElementById('lastUpdateInfo').innerHTML = '<i class="fas fa-clock"></i> آخر تحديث: ' + timeStr;
        } finally {
            statsRefreshInFlight = false;
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        initHintsToggle();
        initDashboard();
        setInterval(initDashboard, 5000);
    });
