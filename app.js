// =============================================
// APP.JS — Üniversite Bütçe Hesaplayıcı Motor
// =============================================

// ── Globals ──
let budgetChart, comparisonChart, kykChart, dashboardDonut, dashboardBar, dashboardLine;
let currentExpenses = {};
let selectedSubcategories = {};

// ── Chart.js Global Config (safe) ──
if (typeof Chart !== 'undefined') {
    Chart.defaults.color = '#a0a0cc';
    Chart.defaults.font.family = "'Inter', system-ui, sans-serif";
    Chart.defaults.plugins.legend.labels.padding = 12;
    Chart.defaults.plugins.legend.labels.usePointStyle = true;
}

// ══════════════════════════════
// INITIALIZATION
// ══════════════════════════════
document.addEventListener('DOMContentLoaded', () => {
    // Init each module independently so one failure doesn't break others
    const safeInit = (fn, name) => {
        try { fn(); } catch(e) { console.warn(`[${name}] init error:`, e.message); }
    };

    safeInit(initNavigation, 'Navigation');
    safeInit(initBudgetCalculator, 'BudgetCalculator');
    safeInit(initCityComparison, 'CityComparison');
    safeInit(initKYKSimulator, 'KYKSimulator');
    safeInit(initFAQ, 'FAQ');
    safeInit(initScrollReveal, 'ScrollReveal');
    safeInit(initHeroCounters, 'HeroCounters');
    safeInit(initMotivation, 'Motivation');
    safeInit(updateAITips, 'AITips');
    safeInit(initDashboard, 'Dashboard');
    safeInit(initTrustSystem, 'TrustSystem');

    // Robustly attach save button listener
    const saveBtn = document.getElementById('saveBudgetBtn');
    if (saveBtn) {
        saveBtn.addEventListener('click', (e) => {
            e.preventDefault();
            saveBudgetPHP();
        });
    }
    console.log('ÜniBütçe App initialized ✓');
});

// ══════════════════════════════
// NAVIGATION
// ══════════════════════════════
function initNavigation() {
    const navbar = document.getElementById('navbar');
    const hamburger = document.getElementById('hamburger');
    const navLinks = document.getElementById('navLinks');

    // Scroll effect
    window.addEventListener('scroll', () => {
        if (navbar) navbar.classList.toggle('scrolled', window.scrollY > 50);
    });

    // Hamburger toggle
    if (hamburger && navLinks) {
        hamburger.addEventListener('click', () => {
            hamburger.classList.toggle('active');
            navLinks.classList.toggle('open');
        });

        // Close mobile menu on link click
        navLinks.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => {
                hamburger.classList.remove('active');
                navLinks.classList.remove('open');
            });
        });
    }

    // Active nav highlighting
    const sections = document.querySelectorAll('section[id]');
    window.addEventListener('scroll', () => {
        const scrollPos = window.scrollY + 150;
        sections.forEach(sec => {
            const top = sec.offsetTop;
            const height = sec.offsetHeight;
            const id = sec.getAttribute('id');
            const link = navLinks?.querySelector(`a[href="#${id}"]`);
            if (link) {
                link.classList.toggle('active', scrollPos >= top && scrollPos < top + height);
            }
        });
    });
}

// ══════════════════════════════
// HERO COUNTERS
// ══════════════════════════════
function initHeroCounters() {
    const counters = document.querySelectorAll('.hero-stat-number[data-target]');
    if (!counters.length) return;

    const startAnimation = (el) => {
        if (el.dataset.animated) return;
        el.dataset.animated = '1';
        animateCounter(el);
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                startAnimation(entry.target);
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1, rootMargin: '0px 0px 100px 0px' });

    counters.forEach(c => {
        // If already in viewport (e.g. at page load), animate immediately
        const rect = c.getBoundingClientRect();
        if (rect.top < window.innerHeight) {
            setTimeout(() => startAnimation(c), 300);
        } else {
            observer.observe(c);
        }
    });
}

function animateCounter(el) {
    const target = parseInt(el.dataset.target);
    let current = 0;
    const increment = Math.ceil(target / 40);
    const timer = setInterval(() => {
        current += increment;
        if (current >= target) {
            current = target;
            clearInterval(timer);
        }
        el.textContent = current + '+';
    }, 40);
}

// ══════════════════════════════
// SCROLL REVEAL
// ══════════════════════════════
function initScrollReveal() {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
            }
        });
    }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });

    document.querySelectorAll('.reveal').forEach(el => observer.observe(el));
}

// ══════════════════════════════
// BUDGET CALCULATOR
// ══════════════════════════════
function initBudgetCalculator() {
    const container = document.getElementById('budgetInputs');
    if (!container) return;
    container.innerHTML = '';

    EXPENSE_CATEGORIES.forEach(cat => {
        currentExpenses[cat.id] = cat.default;
        selectedSubcategories[cat.id] = [];

        const card = document.createElement('div');
        card.className = 'glass-card expense-item';
        card.innerHTML = `
            <div class="expense-header">
                <div class="expense-label">
                    <span class="expense-icon">${cat.icon}</span>
                    ${cat.name}
                </div>
                <div class="expense-amount" id="amount-${cat.id}" style="color: ${cat.color};">
                    ${formatCurrency(cat.default)}
                </div>
            </div>
            <div class="expense-slider-wrapper">
                <input type="range" 
                    id="slider-${cat.id}" 
                    min="${cat.min}" 
                    max="${cat.max}" 
                    step="${cat.step}" 
                    value="${cat.default}"
                    oninput="onExpenseChange('${cat.id}', this.value)"
                    style="accent-color: ${cat.color};"
                >
            </div>
            <div class="expense-subcats" id="subcats-${cat.id}">
                ${cat.subcategories.map(s =>
            `<span class="expense-subcat-tag" 
                        data-cat="${cat.id}" 
                        data-sub="${s}" 
                        data-color="${cat.color}"
                        onclick="toggleSubcategory(this, '${cat.id}', '${s}')">${s}</span>`
        ).join('')}
            </div>
        `;
        container.appendChild(card);
    });

    updateBudgetSummary();
    createBudgetChart();
}

function onExpenseChange(id, value) {
    value = parseInt(value);
    currentExpenses[id] = value;
    const cat = EXPENSE_CATEGORIES.find(c => c.id === id);
    const amountEl = document.getElementById(`amount-${id}`);
    if (amountEl) amountEl.textContent = formatCurrency(value);
    updateBudgetSummary();
    updateBudgetChart();
    updateAITips();
    updateDashboard();
}

function toggleSubcategory(el, catId, subName) {
    const idx = selectedSubcategories[catId].indexOf(subName);
    if (idx > -1) {
        // Deselect
        selectedSubcategories[catId].splice(idx, 1);
        el.classList.remove('active');
        el.style.background = '';
        el.style.borderColor = '';
    } else {
        // Select
        selectedSubcategories[catId].push(subName);
        el.classList.add('active');
        const color = el.dataset.color;
        el.style.background = color;
        el.style.borderColor = color;
    }
    updateBudgetSummary();
}

function updateBudgetSummary() {
    const total = Object.values(currentExpenses).reduce((a, b) => a + b, 0);
    const totalMonthlyEl = document.getElementById('totalMonthly');
    const totalYearlyEl = document.getElementById('totalYearly');

    if (totalMonthlyEl) totalMonthlyEl.textContent = formatCurrency(total);
    if (totalYearlyEl) totalYearlyEl.textContent = formatCurrency(total * 12);

    const breakdown = document.getElementById('summaryBreakdown');
    if (!breakdown) return;
    breakdown.innerHTML = '';

    EXPENSE_CATEGORIES.forEach(cat => {
        const val = currentExpenses[cat.id] || 0;
        const pct = total > 0 ? ((val / total) * 100).toFixed(1) : 0;
        const subs = selectedSubcategories[cat.id] || [];
        const subsText = subs.length > 0 ? `<div class="breakdown-subs">${subs.join(', ')}</div>` : '';
        const item = document.createElement('div');
        item.className = 'breakdown-item';
        item.innerHTML = `
            <div class="breakdown-left">
                <div class="breakdown-color" style="background: ${cat.color};"></div>
                <div>
                    <span class="breakdown-name">${cat.icon} ${cat.name}</span>
                    ${subsText}
                </div>
            </div>
            <div>
                <span class="breakdown-value">${formatCurrency(val)}</span>
                <span class="breakdown-pct">(%${pct})</span>
            </div>
        `;
        breakdown.appendChild(item);
    });

    // Update KYK simulation too
    updateKYKSimulation();
}

function createBudgetChart() {
    const chartEl = document.getElementById('budgetChart');
    if (!chartEl) return;
    const ctx = chartEl.getContext('2d');
    budgetChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: EXPENSE_CATEGORIES.map(c => c.name),
            datasets: [{
                data: EXPENSE_CATEGORIES.map(c => currentExpenses[c.id]),
                backgroundColor: EXPENSE_CATEGORIES.map(c => c.color),
                borderWidth: 0,
                hoverOffset: 8
            }]
        },
        options: {
            responsive: true,
            cutout: '68%',
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(10, 10, 26, 0.9)',
                    borderColor: 'rgba(79, 140, 255, 0.3)',
                    borderWidth: 1,
                    padding: 12,
                    titleFont: { family: "'Inter'" },
                    callbacks: {
                        label: (ctx) => ` ${formatCurrency(ctx.raw)}`
                    }
                }
            }
        }
    });
}

function updateBudgetChart() {
    if (!budgetChart) return;
    budgetChart.data.datasets[0].data = EXPENSE_CATEGORIES.map(c => currentExpenses[c.id]);
    budgetChart.update('none');
}

// ══════════════════════════════
// CITY COMPARISON
// ══════════════════════════════
function initCityComparison() {
    const select1 = document.getElementById('city1');
    const select2 = document.getElementById('city2');
    if (!select1 || !select2) return;

    // Sort cities alphabetically by Turkish name
    const cityKeys = Object.keys(CITY_DATA).sort((a, b) =>
        CITY_DATA[a].name.localeCompare(CITY_DATA[b].name, 'tr')
    );

    cityKeys.forEach(key => {
        const city = CITY_DATA[key];
        select1.innerHTML += `<option value="${key}">${city.emoji} ${city.name}</option>`;
        select2.innerHTML += `<option value="${key}">${city.emoji} ${city.name}</option>`;
    });

    select1.value = 'istanbul';
    select2.value = 'eskisehir';

    updateCityComparison();
}

function updateCityComparison() {
    const select1 = document.getElementById('city1');
    const select2 = document.getElementById('city2');
    if (!select1 || !select2) return;

    const key1 = select1.value;
    const key2 = select2.value;
    const city1 = CITY_DATA[key1];
    const city2 = CITY_DATA[key2];

    renderCityCard('cityCard1', city1);
    renderCityCard('cityCard2', city2);
    updateComparisonChart(city1, city2);
    updateComparisonDiff(city1, city2);
}

function renderCityCard(containerId, city) {
    const container = document.getElementById(containerId);
    if (!container) return;
    const totalCost = (city.costs.rentShared || 0) + (city.costs.food || 0) + (city.costs.transport || 0) + (city.costs.entertainment || 0);

    const fullStars = Math.floor(city.livingScore || 0);
    const halfStar = (city.livingScore % 1) >= 0.5 ? 1 : 0;
    const emptyStars = 5 - fullStars - halfStar;
    const stars = '<i class="fa-solid fa-star"></i>'.repeat(fullStars) + 
                  (halfStar ? '<i class="fa-solid fa-star-half-stroke"></i>' : '') + 
                  '<i class="fa-regular fa-star"></i>'.repeat(emptyStars);

    container.innerHTML = `
        <div class="city-card-header-premium">
            <div class="city-emoji-bg">${city.emoji}</div>
            <div class="city-title-group">
                <h3 class="city-name-premium">${city.name}</h3>
                <div class="city-desc-premium">${city.description}</div>
            </div>
        </div>
        <div class="city-score-premium">
            <span class="score-label">Yaşam Kalitesi:</span>
            <div class="score-visual">
                <span class="stars-container">${stars}</span>
                <span class="score-badge">${city.livingScore}/5</span>
            </div>
        </div>
        <div class="city-costs-grid-premium">
            <div class="cost-item-premium">
                <div class="cost-icon-box" style="background: rgba(0, 240, 255, 0.1); color: var(--accent-cyan);"><i class="fa-solid fa-house"></i></div>
                <div class="cost-data">
                    <span class="cost-name">Kira (1+1)</span>
                    <span class="cost-value">${formatCurrency(city.costs.rentSingle || 0)}</span>
                </div>
            </div>
            <div class="cost-item-premium">
                <div class="cost-icon-box" style="background: rgba(112, 0, 255, 0.1); color: var(--accent-purple);"><i class="fa-solid fa-user-group"></i></div>
                <div class="cost-data">
                    <span class="cost-name">Kira (Ortak)</span>
                    <span class="cost-value">${formatCurrency(city.costs.rentShared || 0)}</span>
                </div>
            </div>
            <div class="cost-item-premium">
                <div class="cost-icon-box" style="background: rgba(57, 255, 20, 0.1); color: var(--accent-green);"><i class="fa-solid fa-building-user"></i></div>
                <div class="cost-data">
                    <span class="cost-name">KYK Yurdu</span>
                    <span class="cost-value">${formatCurrency(city.costs.dormKYK || 0)}</span>
                </div>
            </div>
            <div class="cost-item-premium">
                <div class="cost-icon-box" style="background: rgba(255, 145, 0, 0.1); color: var(--accent-orange);"><i class="fa-solid fa-utensils"></i></div>
                <div class="cost-data">
                    <span class="cost-name">Aylık Yemek</span>
                    <span class="cost-value">${formatCurrency(city.costs.food || 0)}</span>
                </div>
            </div>
            <div class="cost-item-premium">
                <div class="cost-icon-box" style="background: rgba(0, 136, 255, 0.1); color: var(--accent-blue);"><i class="fa-solid fa-bus"></i></div>
                <div class="cost-data">
                    <span class="cost-name">Aylık Ulaşım</span>
                    <span class="cost-value">${formatCurrency(city.costs.transport || 0)}</span>
                </div>
            </div>
            <div class="cost-item-premium">
                <div class="cost-icon-box" style="background: rgba(244, 44, 146, 0.1); color: var(--accent-pink);"><i class="fa-solid fa-ticket"></i></div>
                <div class="cost-data">
                    <span class="cost-name">Sosyal Yaşam</span>
                    <span class="cost-value">${formatCurrency(city.costs.entertainment || 0)}</span>
                </div>
            </div>
        </div>
        <div class="city-total-premium">
            <div class="total-text">
                <span class="total-label">Aylık Maliyet (Tahmini)</span>
                <span class="total-subtext">Paylaşımlı ev baz alınmıştır</span>
            </div>
            <div class="total-amount">${formatCurrency(totalCost)}</div>
        </div>
        <div class="city-tips-premium">
            ${city.tips.map(t => `<div class="tip-item"><i class="fa-solid fa-circle-check tip-icon"></i> <span>${t}</span></div>`).join('')}
        </div>
        ${renderCityUserAvg(city)}
    `;
}

function updateComparisonChart(city1, city2) {
    const chartEl = document.getElementById('comparisonChart');
    if (!chartEl) return;

    const labels = ['Kira (Paylaşımlı)', 'Yemek', 'Ulaşım', 'Eğlence', 'Faturalar'];
    const data1 = [city1.costs.rentShared, city1.costs.food, city1.costs.transport, city1.costs.entertainment, city1.costs.utilities];
    const data2 = [city2.costs.rentShared, city2.costs.food, city2.costs.transport, city2.costs.entertainment, city2.costs.utilities];

    if (comparisonChart) comparisonChart.destroy();

    const ctx = chartEl.getContext('2d');
    comparisonChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: city1.name,
                    data: data1,
                    backgroundColor: 'rgba(79, 140, 255, 0.7)',
                    borderRadius: 6,
                    borderSkipped: false
                },
                {
                    label: city2.name,
                    data: data2,
                    backgroundColor: 'rgba(168, 85, 247, 0.7)',
                    borderRadius: 6,
                    borderSkipped: false
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'top' },
                tooltip: {
                    backgroundColor: 'rgba(10, 10, 26, 0.9)',
                    borderColor: 'rgba(79, 140, 255, 0.3)',
                    borderWidth: 1,
                    callbacks: {
                        label: (ctx) => ` ${ctx.dataset.label}: ${formatCurrency(ctx.raw)}`
                    }
                }
            },
            scales: {
                x: {
                    grid: { color: 'rgba(255,255,255,0.03)' },
                    ticks: { font: { size: 11 } }
                },
                y: {
                    grid: { color: 'rgba(255,255,255,0.03)' },
                    ticks: {
                        callback: v => formatCurrency(v),
                        font: { size: 11 }
                    }
                }
            }
        }
    });
}

function updateComparisonDiff(city1, city2) {
    const diffContainer = document.getElementById('comparisonDiff');
    if (!diffContainer) return;

    const metrics = [
        { name: 'Kira (Paylaşımlı)', v1: city1.costs.rentShared, v2: city2.costs.rentShared },
        { name: 'Yemek', v1: city1.costs.food, v2: city2.costs.food },
        { name: 'Ulaşım', v1: city1.costs.transport, v2: city2.costs.transport },
        { name: 'Eğlence', v1: city1.costs.entertainment, v2: city2.costs.entertainment }
    ];

    diffContainer.innerHTML = metrics.map(m => {
        const diff = m.v1 - m.v2;
        const pct = m.v1 > 0 ? Math.abs((diff / m.v1) * 100).toFixed(0) : 0;
        const cheaper = diff > 0 ? city2.name : city1.name;
        const isCheaper = diff !== 0;
        return `
            <div class="diff-item">
                <span class="diff-label">${m.name}</span>
                <span class="diff-value ${isCheaper ? (diff > 0 ? 'cheaper' : 'expensive') : ''}">
                    ${isCheaper ? `${cheaper} %${pct} daha ucuz` : 'Eşit'}
                </span>
            </div>
        `;
    }).join('');
}

// ══════════════════════════════
// KYK / BURS SIMULATOR
// ══════════════════════════════
function initKYKSimulator() {
    const select = document.getElementById('kykSelect');
    if (!select) return;

    KYK_CREDIT_OPTIONS.forEach(opt => {
        select.innerHTML += `<option value="${opt.amount}">${opt.label} — ${formatCurrency(opt.amount)}/ay</option>`;
    });
    updateKYKSimulation();
}

function updateKYKSimulation() {
    const kykSelect = document.getElementById('kykSelect');
    if (!kykSelect) return;

    const kykAmount = parseInt(kykSelect.value) || 0;
    const scholarship = parseInt(document.getElementById('scholarshipInput')?.value || 0);
    const partTime = parseInt(document.getElementById('partTimeInput')?.value || 0);
    const family = parseInt(document.getElementById('familyInput')?.value || 0);

    const totalIncome = kykAmount + scholarship + partTime + family;
    const totalExpense = Object.values(currentExpenses).reduce((a, b) => a + b, 0);
    const balance = totalIncome - totalExpense;

    // Update displays
    if (document.getElementById('kykAmount')) document.getElementById('kykAmount').textContent = formatCurrency(kykAmount);
    if (document.getElementById('scholarshipDisplay')) document.getElementById('scholarshipDisplay').textContent = formatCurrency(scholarship);
    if (document.getElementById('partTimeDisplay')) document.getElementById('partTimeDisplay').textContent = formatCurrency(partTime);
    if (document.getElementById('familyDisplay')) document.getElementById('familyDisplay').textContent = formatCurrency(family);

    // Balance
    const balanceEl = document.getElementById('kykBalance');
    const balanceAmount = document.getElementById('kykBalanceAmount');
    const balanceStatus = document.getElementById('kykBalanceStatus');

    if (balanceEl && balanceAmount && balanceStatus) {
        balanceEl.className = `kyk-balance ${balance >= 0 ? 'positive' : 'negative'}`;
        balanceAmount.textContent = `${balance >= 0 ? '+' : ''}${formatCurrency(balance)}`;

        if (totalIncome === 0 && totalExpense === 0) {
            balanceStatus.textContent = 'Gelir ve gider bilgilerinizi girin';
            balanceStatus.style.color = 'var(--text-secondary)';
        } else if (balance > 0) {
            balanceStatus.textContent = '✅ Bütçeniz dengede! Fazlayı biriktirin.';
            balanceStatus.style.color = '#10b981';
        } else if (balance === 0) {
            balanceStatus.textContent = '⚠️ Bütçeniz tam denk. Tasarruf için giderleri azaltın.';
            balanceStatus.style.color = '#f59e0b';
        } else {
            balanceStatus.textContent = `❌ Aylık ${formatCurrency(Math.abs(balance))} açığınız var!`;
            balanceStatus.style.color = '#ef4444';
        }
    }

    // Progress
    const progressPct = totalExpense > 0 ? Math.min((totalIncome / totalExpense) * 100, 100) : 0;
    const progressPctEl = document.getElementById('progressPct');
    const progressBar = document.getElementById('kykProgressBar');
    if (progressPctEl) progressPctEl.textContent = `%${progressPct.toFixed(0)}`;
    if (progressBar) {
        progressBar.style.width = `${progressPct}%`;
        progressBar.className = `kyk-progress-bar ${progressPct >= 70 ? '' : progressPct >= 40 ? 'warning' : 'danger'}`;
    }

    // Details
    const details = document.getElementById('kykDetails');
    if (details) {
        details.innerHTML = `
            <div class="kyk-detail-row">
                <span class="kyk-detail-label">🏦 KYK Kredi/Burs</span>
                <span class="kyk-detail-value income">+${formatCurrency(kykAmount)}</span>
            </div>
            <div class="kyk-detail-row">
                <span class="kyk-detail-label">🎓 Burs Geliri</span>
                <span class="kyk-detail-value income">+${formatCurrency(scholarship)}</span>
            </div>
            <div class="kyk-detail-row">
                <span class="kyk-detail-label">💼 Part-time İş</span>
                <span class="kyk-detail-value income">+${formatCurrency(partTime)}</span>
            </div>
            <div class="kyk-detail-row">
                <span class="kyk-detail-label">👨‍👩‍👧 Aile Desteği</span>
                <span class="kyk-detail-value income">+${formatCurrency(family)}</span>
            </div>
            <div class="kyk-detail-row" style="border-top: 1px solid rgba(255,255,255,0.08); padding-top: 12px;">
                <span class="kyk-detail-label"><strong>💰 Toplam Gelir</strong></span>
                <span class="kyk-detail-value income"><strong>${formatCurrency(totalIncome)}</strong></span>
            </div>
            <div class="kyk-detail-row">
                <span class="kyk-detail-label"><strong>📉 Toplam Gider</strong></span>
                <span class="kyk-detail-value expense"><strong>-${formatCurrency(totalExpense)}</strong></span>
            </div>
        `;
    }

    // KYK Chart
    updateKYKChart(totalIncome, totalExpense);

    // Savings target
    const targetEl = document.getElementById('savingsTarget');
    if (targetEl) targetEl.textContent = formatCurrency(Math.max(0, Math.round(totalIncome * 0.1)));
}

function updateKYKChart(income, expense) {
    const chartEl = document.getElementById('kykChart');
    if (!chartEl) return;

    if (kykChart) kykChart.destroy();
    const ctx = chartEl.getContext('2d');
    kykChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Gelir', 'Gider'],
            datasets: [{
                data: [income, expense],
                backgroundColor: ['rgba(0, 230, 118, 0.7)', 'rgba(255, 82, 82, 0.7)'],
                borderWidth: 0,
                hoverOffset: 6
            }]
        },
        options: {
            responsive: true,
            cutout: '65%',
            plugins: {
                legend: { position: 'bottom', labels: { padding: 16 } },
                tooltip: {
                    backgroundColor: 'rgba(10, 10, 26, 0.9)',
                    callbacks: { label: (ctx) => ` ${formatCurrency(ctx.raw)}` }
                }
            }
        }
    });
}

// ══════════════════════════════
// AI TIPS
// ══════════════════════════════
function updateAITips() {
    const grid = document.getElementById('aiTipsGrid');
    if (!grid) return;
    const tips = [];

    // Category-based tips
    Object.keys(AI_TIPS).forEach(catId => {
        const val = currentExpenses[catId];
        if (val === undefined) return;
        const catTips = AI_TIPS[catId];
        for (const tip of catTips) {
            if (val >= tip.threshold) {
                tips.push(tip.tip);
                break;
            }
        }
    });

    // Add general tips
    const shuffled = [...GENERAL_TIPS].sort(() => 0.5 - Math.random());
    tips.push(...shuffled.slice(0, Math.max(2, 8 - tips.length)));

    grid.innerHTML = tips.slice(0, 8).map(tip => `
        <div class="glass-card ai-tip-card">
            <div class="tip-text">${tip}</div>
        </div>
    `).join('');
}

function initMotivation() {
    const el = document.getElementById('motivationText');
    if (!el) return;
    const idx = Math.floor(Math.random() * MOTIVATIONAL_QUOTES.length);
    el.textContent = MOTIVATIONAL_QUOTES[idx];
}

// ══════════════════════════════
// DASHBOARD
// ══════════════════════════════
function initDashboard() {
    createDashboardDonut();
    createDashboardBar();
    createDashboardLine();
}

function createDashboardDonut() {
    const chartEl = document.getElementById('dashboardDonut');
    if (!chartEl) return;

    const ctx = chartEl.getContext('2d');
    dashboardDonut = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: EXPENSE_CATEGORIES.map(c => c.name),
            datasets: [{
                data: EXPENSE_CATEGORIES.map(c => currentExpenses[c.id]),
                backgroundColor: EXPENSE_CATEGORIES.map(c => c.color),
                borderWidth: 0,
                hoverOffset: 8
            }]
        },
        options: {
            responsive: true,
            cutout: '55%',
            plugins: {
                legend: { position: 'bottom', labels: { padding: 14, font: { size: 11 } } },
                tooltip: {
                    backgroundColor: 'rgba(10, 10, 26, 0.9)',
                    callbacks: { label: (ctx) => ` ${ctx.label}: ${formatCurrency(ctx.raw)}` }
                }
            }
        }
    });
}

function createDashboardBar() {
    const chartEl = document.getElementById('dashboardBar');
    if (!chartEl) return;

    const totalIncome = getTotalIncome();
    const totalExpense = Object.values(currentExpenses).reduce((a, b) => a + b, 0);

    const ctx = chartEl.getContext('2d');
    dashboardBar = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Gelir', 'Gider', 'Fark'],
            datasets: [{
                data: [totalIncome, totalExpense, totalIncome - totalExpense],
                backgroundColor: [
                    'rgba(0, 230, 118, 0.7)',
                    'rgba(255, 82, 82, 0.7)',
                    totalIncome >= totalExpense ? 'rgba(79, 140, 255, 0.7)' : 'rgba(255, 145, 0, 0.7)'
                ],
                borderRadius: 8,
                borderSkipped: false
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(10, 10, 26, 0.9)',
                    callbacks: { label: (ctx) => ` ${formatCurrency(ctx.raw)}` }
                }
            },
            scales: {
                x: { grid: { display: false } },
                y: {
                    grid: { color: 'rgba(255,255,255,0.03)' },
                    ticks: { callback: v => formatCurrency(v) }
                }
            }
        }
    });
}

function createDashboardLine() {
    const chartEl = document.getElementById('dashboardLine');
    if (!chartEl) return;

    const months = ['Eyl', 'Eki', 'Kas', 'Ara', 'Oca', 'Şub', 'Mar', 'Nis', 'May', 'Haz'];
    const totalExpense = Object.values(currentExpenses).reduce((a, b) => a + b, 0);

    const expenseData = months.map(() => Math.round(totalExpense + (Math.random() - 0.5) * totalExpense * 0.15));
    const incomeData = months.map(() => getTotalIncome());

    const ctx = chartEl.getContext('2d');
    dashboardLine = new Chart(ctx, {
        type: 'line',
        data: {
            labels: months,
            datasets: [
                {
                    label: 'Gider',
                    data: expenseData,
                    borderColor: '#ff5252',
                    backgroundColor: 'rgba(255, 82, 82, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointHoverRadius: 6
                },
                {
                    label: 'Gelir',
                    data: incomeData,
                    borderColor: '#00e676',
                    backgroundColor: 'rgba(0, 230, 118, 0.05)',
                    fill: true,
                    tension: 0.1,
                    borderDash: [5, 5],
                    pointRadius: 3,
                    pointHoverRadius: 5
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'top' },
                tooltip: {
                    backgroundColor: 'rgba(10, 10, 26, 0.9)',
                    callbacks: { label: (ctx) => ` ${ctx.dataset.label}: ${formatCurrency(ctx.raw)}` }
                }
            },
            scales: {
                x: { grid: { color: 'rgba(255,255,255,0.03)' } },
                y: {
                    grid: { color: 'rgba(255,255,255,0.03)' },
                    ticks: { callback: v => formatCurrency(v) }
                }
            }
        }
    });
}

function updateDashboard() {
    if (dashboardDonut) {
        dashboardDonut.data.datasets[0].data = EXPENSE_CATEGORIES.map(c => currentExpenses[c.id]);
        dashboardDonut.update('none');
    }

    if (dashboardBar) {
        const totalIncome = getTotalIncome();
        const totalExpense = Object.values(currentExpenses).reduce((a, b) => a + b, 0);
        dashboardBar.data.datasets[0].data = [totalIncome, totalExpense, totalIncome - totalExpense];
        dashboardBar.update('none');
    }

    if (dashboardLine) {
        const totalExpense = Object.values(currentExpenses).reduce((a, b) => a + b, 0);
        const totalIncome = getTotalIncome();
        dashboardLine.data.datasets[0].data = dashboardLine.data.labels.map(() => Math.round(totalExpense + (Math.random() - 0.5) * totalExpense * 0.1));
        dashboardLine.data.datasets[1].data = dashboardLine.data.labels.map(() => totalIncome);
        dashboardLine.update('update');
    }
}

// ══════════════════════════════
// MINI TOOLS
// ══════════════════════════════
function calcDailyLimit() {
    const budget = parseInt(document.getElementById('dailyBudgetInput')?.value || 0);
    const resultEl = document.getElementById('dailyLimitResult');
    if (!resultEl) return;
    if (budget <= 0) {
        resultEl.textContent = '⚠️ Geçerli bir tutar girin';
        return;
    }
    const daily = (budget / 30).toFixed(0);
    const weekly = (budget / 4.3).toFixed(0);
    resultEl.innerHTML = `Günlük: <strong>${formatCurrency(parseInt(daily))}</strong> | Haftalık: <strong>${formatCurrency(parseInt(weekly))}</strong>`;
}

function calcMealPlan() {
    const budget = parseInt(document.getElementById('mealBudgetInput')?.value || 0);
    const resultEl = document.getElementById('mealPlanResult');
    if (!resultEl) return;
    if (budget <= 0) {
        resultEl.textContent = '⚠️ Geçerli bir tutar girin';
        return;
    }
    const daily = (budget / 30).toFixed(0);
    const perMeal = (budget / 90).toFixed(0);
    resultEl.innerHTML = `Günlük: <strong>${formatCurrency(parseInt(daily))}</strong> | Öğün başı (3): <strong>${formatCurrency(parseInt(perMeal))}</strong>`;
}

function calcSavingsGoal() {
    const goal = parseInt(document.getElementById('savingsGoalInput')?.value || 0);
    const months = parseInt(document.getElementById('savingsMonthsInput')?.value || 0);
    const resultEl = document.getElementById('savingsGoalResult');
    if (!resultEl) return;
    if (goal <= 0 || months <= 0) {
        resultEl.textContent = '⚠️ Geçerli değerler girin';
        return;
    }
    const monthly = Math.ceil(goal / months);
    const daily = Math.ceil(goal / (months * 30));
    resultEl.innerHTML = `Aylık: <strong>${formatCurrency(monthly)}</strong> | Günlük: <strong>${formatCurrency(daily)}</strong> biriktirmelisin`;
}

// ══════════════════════════════
// FAQ
// ══════════════════════════════
function initFAQ() {
    const list = document.getElementById('faqList');
    if (!list) return;
    list.innerHTML = '';

    FAQ_DATA.forEach((item, i) => {
        const faqItem = document.createElement('div');
        faqItem.className = 'faq-item';
        faqItem.innerHTML = `
            <button class="faq-question" id="faqBtn-${i}" onclick="toggleFAQ(${i})">
                <span>${item.question}</span>
                <span class="faq-arrow">▼</span>
            </button>
            <div class="faq-answer" id="faqAnswer-${i}">
                <div class="faq-answer-text">${item.answer}</div>
            </div>
        `;
        list.appendChild(faqItem);
    });
}

function toggleFAQ(index) {
    const btn = document.getElementById(`faqBtn-${index}`);
    const answer = document.getElementById(`faqAnswer-${index}`);
    if (!btn || !answer) return;

    const isOpen = btn.classList.contains('active');
    document.querySelectorAll('.faq-question').forEach(q => q.classList.remove('active'));
    document.querySelectorAll('.faq-answer').forEach(a => a.classList.remove('open'));

    if (!isOpen) {
        btn.classList.add('active');
        answer.classList.add('open');
    }
}

// ══════════════════════════════
// HELPERS
// ══════════════════════════════
function formatCurrency(amount) {
    return (amount || 0).toLocaleString('tr-TR') + ' ₺';
}

function getTotalIncome() {
    const kyk = parseInt(document.getElementById('kykSelect')?.value || 0);
    const scholarship = parseInt(document.getElementById('scholarshipInput')?.value || 0);
    const partTime = parseInt(document.getElementById('partTimeInput')?.value || 0);
    const family = parseInt(document.getElementById('familyInput')?.value || 0);
    return kyk + scholarship + partTime + family;
}

// ══════════════════════════════
// TRUST & USER DATA SYSTEM
// ══════════════════════════════
const TRUST_STORAGE_KEY = 'uniBudget_userContributions';
let cityContributionCounts = {};
let totalStudentCount = 0;

function initTrustSystem() {
    const stored = localStorage.getItem(TRUST_STORAGE_KEY);
    if (stored) {
        cityContributionCounts = JSON.parse(stored);
    } else {
        cityContributionCounts = {};
        const popularCities = ['istanbul', 'ankara', 'izmir'];
        popularCities.forEach(c => cityContributionCounts[c] = Math.floor(Math.random() * 50) + 10);
    }

    totalStudentCount = Object.values(cityContributionCounts).reduce((a, b) => a + b, 0);
    updateTrustBadge();
}

function updateTrustBadge() {
    const el = document.getElementById('trustStudentCount');
    if (el) el.textContent = totalStudentCount.toLocaleString('tr-TR');
}

function renderCityUserAvg(city) {
    const cityKey = Object.keys(CITY_DATA).find(k => CITY_DATA[k].name === city.name);
    const count = cityContributionCounts[cityKey] || 0;
    if (count < 5) return '';

    return `
        <div class="city-user-avg">
            <div class="city-user-avg-header">
                <div class="city-user-avg-title">👥 Öğrenci Ortalaması</div>
                <div class="city-user-avg-count">${count} öğrenci</div>
            </div>
            <div class="city-user-avg-row">
                <span class="city-user-avg-label">Genel Yaşam</span>
                <span class="city-user-avg-value">Maliyet Analizi Mevcut</span>
            </div>
        </div>
    `;
}

// ══════════════════════════════
// BUDGET PERSISTENCE (BACKEND INTEGRATION)
// ══════════════════════════════
function saveBudgetPHP() {
    console.log("Budget Save sequence started...");

    // Improved login check
    const userMenuBtn = document.getElementById('userMenuBtn');
    console.log("Login check - window.isUserLoggedIn:", window.isUserLoggedIn);
    console.log("Login check - userMenuBtn exists:", userMenuBtn !== null);

    const isLoggedIn = window.isUserLoggedIn === true || userMenuBtn !== null;

    if (!isLoggedIn) {
        if (typeof window.openAuthModal === 'function') {
            window.openAuthModal();
        } else {
            alert('Lütfen bütçenizi kaydetmek için giriş yapın.');
        }
        return;
    }

    const btn = document.getElementById('saveBudgetBtn');
    if (!btn) return;

    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '🕒 Kaydediliyor...';
    btn.style.opacity = '0.7';

    const budgetData = {
        expenses: currentExpenses,
        total: Object.values(currentExpenses).reduce((a, b) => a + b, 0),
        lastSaved: new Date().toISOString()
    };

    console.log("Sending data:", budgetData);

    fetch('api/save_budget.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': window.CSRF_TOKEN || ''
        },
        body: JSON.stringify(budgetData)
    })
        .then(async response => {
            const text = await response.text();
            console.log("Raw response:", text);
            try {
                return JSON.parse(text);
            } catch (e) {
                throw new Error("Sunucudan geçersiz yanıt geldi: " + text.substring(0, 100));
            }
        })
        .then(data => {
            if (data.success) {
                btn.innerHTML = '✅ Bütçe Kaydedildi';
                btn.classList.add('success');
                localStorage.setItem('unistudent-budget', JSON.stringify({ state: budgetData, version: 0 }));

                if (typeof window.showToast === 'function') {
                    window.showToast('Bütçeniz başarıyla kaydedildi!', 'success');
                } else {
                    alert('Bütçe Profilinize Kaydedildi!');
                }
            } else {
                if (typeof window.showToast === 'function') {
                    window.showToast('Hata: ' + (data.message || 'Bütçe kaydedilemedi.'), 'error');
                } else {
                    alert('Hata: ' + (data.message || 'Bütçe kaydedilemedi.'));
                }
            }
        })
        .catch(error => {
            console.error('Save Error:', error);
            if (typeof window.showToast === 'function') {
                window.showToast('Hata: ' + error.message, 'error');
            } else {
                alert('Hata: ' + error.message);
            }
        })
        .finally(() => {
            setTimeout(() => {
                btn.innerHTML = originalText;
                btn.disabled = false;
                btn.style.opacity = '1';
                btn.classList.remove('success');
            }, 3000);
        });
}

// ══════════════════════════════
// CURRENCY CONVERTER (Mock Rates)
// ══════════════════════════════
function calcCurrency() {
    const amount = parseFloat(document.getElementById('currencyAmountInput')?.value || 0);
    const currency = document.getElementById('currencyFrom')?.value || 'USD';
    const resultEl = document.getElementById('currencyResult');
    if (!resultEl) return;

    if (amount <= 0) {
        resultEl.textContent = 'Geçerli bir miktar girin';
        return;
    }

    // Mock exchange rates (April 2026 approximations)
    const rates = { USD: 38.50, EUR: 42.10, GBP: 48.80 };
    const rate = rates[currency] || 38.50;
    const result = amount * rate;

    resultEl.innerHTML = `
        <strong>${amount} ${currency}</strong> = <strong style="color:var(--accent-cyan);">₺${result.toLocaleString('tr-TR', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</strong>
        <br><span style="font-size:0.8em;color:var(--text-muted);">Kur: 1 ${currency} = ₺${rate.toFixed(2)}</span>
    `;
}

// ══════════════════════════════
// ROOMMATE SPLITTER
// ══════════════════════════════
function calcRoommateSplit() {
    const total = parseFloat(document.getElementById('splitTotalInput')?.value || 0);
    const people = parseInt(document.getElementById('splitPeopleInput')?.value || 2);
    const resultEl = document.getElementById('splitResult');
    if (!resultEl) return;

    if (total <= 0 || people < 2) {
        resultEl.textContent = 'Tutar ve en az 2 kişi girin';
        return;
    }

    const perPerson = total / people;
    resultEl.innerHTML = `
        <strong style="color:var(--accent-green);">Kişi başı: ₺${perPerson.toLocaleString('tr-TR', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</strong>
        <br><span style="font-size:0.8em;color:var(--text-muted);">₺${total.toLocaleString('tr-TR')} ÷ ${people} kişi</span>
    `;
}

// ══════════════════════════════
// THEME INIT (Index Page)
// ══════════════════════════════
(function initThemeFromStorage() {
    const savedTheme = localStorage.getItem('ub-theme');
    if (savedTheme) {
        document.documentElement.setAttribute('data-theme', savedTheme);
    }
})();

// initScrollReveal already defined above at line ~128

// ══════════════════════════════
// TOAST NOTIFICATIONS
// ══════════════════════════════
window.showToast = function(message, type = 'info') {
    let toastContainer = document.getElementById('ubToastContainer');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'ubToastContainer';
        document.body.appendChild(toastContainer);
    }

    const toast = document.createElement('div');
    toast.className = `ub-toast ${type}`;
    
    let icon = 'ℹ️';
    if(type === 'success') icon = '✅';
    if(type === 'error') icon = '❌';

    // XSS güvenli: kullanıcı/sunucu kaynaklı message textContent ile ekleniyor
    const iconEl = document.createElement('span');
    iconEl.className = 'toast-icon';
    iconEl.textContent = icon;
    const textEl = document.createElement('span');
    textEl.className = 'toast-text';
    textEl.textContent = message;
    toast.appendChild(iconEl);
    toast.appendChild(document.createTextNode(' '));
    toast.appendChild(textEl);
    toastContainer.appendChild(toast);

    // Trigger animation
    setTimeout(() => {
        toast.classList.add('show');
    }, 10);

    // Auto remove
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 400);
    }, 4000);
};

// ══════════════════════════════
// PDF EXPORT (IMAGE)
// ══════════════════════════════
window.downloadBudgetPDF = async function() {
    const card = document.querySelector('.budget-summary-card');
    if (!card) return;

    if (typeof window.showToast === 'function') {
        window.showToast('Görsel hazırlanıyor, lütfen bekleyin...', 'info');
    }
    
    const btnPdf = document.getElementById('downloadPdf');
    const originalText = btnPdf.innerHTML;
    btnPdf.disabled = true;
    btnPdf.innerHTML = '🕒 Hazırlanıyor...';

    // Gizlememiz gereken buton bloğunu seçelim
    const trustActions = card.querySelector('.trust-actions');
    
    // Geçici olarak butonları gizle ki fotoğrafta çıkmasın
    if(trustActions) trustActions.style.display = 'none';

    try {
        if(typeof html2canvas === 'undefined') {
            throw new Error('html2canvas kütüphanesi yüklenemedi!');
        }

        const canvas = await html2canvas(card, {
            scale: 2,
            backgroundColor: '#0A0A12', // Veya temanıza uygun arka planı verin
            useCORS: true
        });

        const imgData = canvas.toDataURL('image/png');
        
        // Direk resim indir
        const link = document.createElement('a');
        link.download = 'UniButce-Harcama-Ozeti.png';
        link.href = imgData;
        link.click();
        
        if (typeof window.showToast === 'function') {
            window.showToast('Gider özetiniz başarıyla indirildi!', 'success');
        }

    } catch (err) {
        console.error('PDF oluşturma hatası:', err);
        if (typeof window.showToast === 'function') {
            window.showToast('Oluşturulurken bir hata oluştu: ' + err.message, 'error');
        }
    } finally {
        if(trustActions) trustActions.style.display = 'flex';
        btnPdf.disabled = false;
        btnPdf.innerHTML = originalText;
    }
};

// ══════════════════════════════
// SHARE EXPERIENCES
// ══════════════════════════════
window.shareCurrentExpenses = function() {
    const btn = document.getElementById('shareExpensesBtn');
    if (!btn) return;

    const isLoggedIn = window.isUserLoggedIn === true || document.getElementById('userMenuBtn') !== null;
    if (!isLoggedIn) {
        if (typeof window.openAuthModal === 'function') {
            window.openAuthModal();
        } else {
            alert('Lütfen giderlerinizi paylaşmak için önce giriş yapın.');
        }
        return;
    }

    const originalText = btn.innerHTML;
    btn.innerHTML = '🕒 Gönderiliyor...';
    btn.disabled = true;

    const budgetData = {
        expenses: currentExpenses,
        total: Object.values(currentExpenses).reduce((a, b) => a + b, 0),
        sharedAt: new Date().toISOString()
    };

    fetch('api/save_budget.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': window.CSRF_TOKEN || ''
        },
        body: JSON.stringify(budgetData)
    })
    .then(async response => await response.json())
    .then(data => {
        if (data.success) {
            btn.innerHTML = '✅ Paylaşıldı!';
            if (typeof window.showToast === 'function') {
                window.showToast('Giderleriniz sisteme başarıyla kaydedildi! Aileye katkınız için teşekkürler.', 'success');
            }
        } else {
            if (typeof window.showToast === 'function') {
                window.showToast('Hata: ' + (data.message || 'Gönderilemedi.'), 'error');
            }
        }
    })
    .catch(error => {
        console.error('Share error:', error);
        if (typeof window.showToast === 'function') {
            window.showToast('Beklenmeyen bir hata oluştu.', 'error');
        }
    })
    .finally(() => {
        setTimeout(() => {
            btn.innerHTML = originalText;
            btn.disabled = false;
        }, 4000);
    });
};

// ══════════════════════════════
// OAUTH COMING SOON MODAL
// ══════════════════════════════
function showOAuthComingSoon(provider) {
    // Remove existing modal if any
    const existing = document.getElementById('oauthComingSoonModal');
    if (existing) existing.remove();

    const modal = document.createElement('div');
    modal.id = 'oauthComingSoonModal';
    modal.style.cssText = `
        position: fixed; inset: 0; z-index: 99999;
        display: flex; align-items: center; justify-content: center;
        background: rgba(0,0,0,0.75); backdrop-filter: blur(8px);
        animation: fadeIn 0.2s ease;
    `;
    modal.innerHTML = `
        <div style="
            background: linear-gradient(135deg, rgba(12,26,51,0.98), rgba(6,17,33,0.98));
            border: 1px solid rgba(0,136,255,0.3);
            border-radius: 20px;
            padding: 40px 48px;
            max-width: 440px;
            width: 90%;
            text-align: center;
            box-shadow: 0 24px 80px rgba(0,0,0,0.6), 0 0 60px rgba(0,136,255,0.08);
            animation: slideUp 0.3s ease;
        ">
            <div style="font-size: 3.5rem; margin-bottom: 16px;">🚀</div>
            <h2 style="margin: 0 0 12px; font-size: 1.4rem; color: #fff;">${provider} ile Giriş</h2>
            <div style="
                display: inline-flex; align-items: center; gap: 6px;
                padding: 5px 14px; border-radius: 999px;
                background: linear-gradient(135deg, rgba(125,92,255,0.2), rgba(0,136,255,0.2));
                border: 1px solid rgba(125,92,255,0.4);
                font-size: 0.8rem; font-weight: 700; color: #a78bfa;
                text-transform: uppercase; letter-spacing: 1px;
                margin-bottom: 20px;
            ">⚡ Yakında Geliyor</div>
            <p style="color: rgba(255,255,255,0.65); line-height: 1.7; margin: 0 0 28px; font-size: 0.95rem;">
                ${provider} OAuth entegrasyonu geliştirme aşamasında.
                Şu an e-posta ve şifrenizle kayıt olup giriş yapabilirsiniz.
            </p>
            <div style="display: flex; gap: 12px; justify-content: center;">
                <button onclick="document.getElementById('oauthComingSoonModal').remove()"
                    style="
                        padding: 12px 28px; border-radius: 10px;
                        background: linear-gradient(135deg, #0088ff, #00c8ff);
                        color: #fff; border: none; cursor: pointer;
                        font-weight: 600; font-size: 0.95rem;
                        transition: all 0.2s;
                    "
                    onmouseover="this.style.transform='translateY(-2px)'"
                    onmouseout="this.style.transform=''"
                >E-posta ile Devam Et</button>
                <button onclick="document.getElementById('oauthComingSoonModal').remove()"
                    style="
                        padding: 12px 20px; border-radius: 10px;
                        background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1);
                        color: rgba(255,255,255,0.6); cursor: pointer;
                        font-size: 0.9rem; transition: all 0.2s;
                    "
                    onmouseover="this.style.background='rgba(255,255,255,0.1)'"
                    onmouseout="this.style.background='rgba(255,255,255,0.05)'"
                >Kapat</button>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
    modal.addEventListener('click', (e) => {
        if (e.target === modal) modal.remove();
    });
}