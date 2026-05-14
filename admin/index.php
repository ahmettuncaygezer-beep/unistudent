<?php
$PAGE_TITLE = 'Dashboard';
require_once 'header.php';
require_once 'api/database.php';
ensureTables();
$db = getDB();
$now = date('d.m.Y H:i');

// Server-side initial stats (fast load, then JS refreshes)
$initStats = [];
try {
    $initStats['total_users']   = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $initStats['total_tx']      = (int)$db->query("SELECT COUNT(*) FROM transactions")->fetchColumn();
    $initStats['total_income']  = (float)$db->query("SELECT COALESCE(SUM(amount),0) FROM transactions WHERE type='income'")->fetchColumn();
    $initStats['total_expense'] = (float)$db->query("SELECT COALESCE(SUM(amount),0) FROM transactions WHERE type='expense'")->fetchColumn();
    $initStats['total_subs']    = (int)$db->query("SELECT COUNT(*) FROM subscriptions WHERE is_active=1")->fetchColumn();
    $initStats['total_blogs']   = (int)$db->query("SELECT COUNT(*) FROM blogs")->fetchColumn();
    $initStats['new_today']     = (int)$db->query("SELECT COUNT(*) FROM users WHERE DATE(created_at)=CURDATE()")->fetchColumn();
    $initStats['tx_today']      = (int)$db->query("SELECT COUNT(*) FROM transactions WHERE DATE(transaction_date)=CURDATE()")->fetchColumn();
} catch(Exception $e) { $initStats = array_fill_keys(['total_users','total_tx','total_income','total_expense','total_subs','total_blogs','new_today','tx_today'], 0); }
?>

<!-- Page header with live refresh -->
<div class="adm-section-header" style="margin-bottom:20px;">
    <div>
        <div class="adm-section-title" style="font-size:1.3rem;">Platform Kontrol Merkezi</div>
        <div class="adm-section-desc" id="lastRefreshed">Son yenileme: <?php echo $now; ?></div>
    </div>
    <div class="adm-section-actions">
        <button class="adm-btn adm-btn-ghost adm-btn-sm" id="refreshDashBtn">
            <i class="fa-solid fa-rotate" id="refreshIcon"></i> Yenile
        </button>
        <select class="adm-select adm-btn-sm" id="chartRangeSelect" style="width:auto;min-width:130px;">
            <option value="7">Son 7 gün</option>
            <option value="30" selected>Son 30 gün</option>
            <option value="60">Son 60 gün</option>
            <option value="90">Son 90 gün</option>
        </select>
    </div>
</div>

<!-- ══ KPI GRID (8 cards with sparklines) ══ -->
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:22px;" id="kpiGrid">

    <div class="adm-kpi" style="--kpi-color:var(--cyan);--kpi-bg:var(--cyan-dim);flex-direction:column;align-items:flex-start;gap:10px;">
        <div style="display:flex;align-items:center;gap:12px;width:100%;">
            <div class="adm-kpi-icon"><i class="fa-solid fa-users"></i></div>
            <div class="adm-kpi-content">
                <div class="adm-kpi-value" id="kpi_users"><?php echo number_format($initStats['total_users']); ?></div>
                <div class="adm-kpi-label">Toplam Üye</div>
            </div>
            <div style="margin-left:auto;text-align:right;">
                <div class="adm-kpi-delta up" id="kpi_users_today">+<?php echo $initStats['new_today']; ?> bugün</div>
            </div>
        </div>
        <canvas id="spark_users" class="adm-sparkline" style="width:100%;height:36px;"></canvas>
    </div>

    <div class="adm-kpi" style="--kpi-color:var(--green);--kpi-bg:var(--green-dim);flex-direction:column;align-items:flex-start;gap:10px;">
        <div style="display:flex;align-items:center;gap:12px;width:100%;">
            <div class="adm-kpi-icon"><i class="fa-solid fa-arrow-trend-up"></i></div>
            <div class="adm-kpi-content">
                <div class="adm-kpi-value" id="kpi_income" style="font-size:1.2rem;">₺<?php echo number_format($initStats['total_income'],0,',','.'); ?></div>
                <div class="adm-kpi-label">Toplam Gelir</div>
            </div>
        </div>
        <canvas id="spark_income" class="adm-sparkline" style="width:100%;height:36px;"></canvas>
    </div>

    <div class="adm-kpi" style="--kpi-color:var(--orange);--kpi-bg:var(--orange-dim);flex-direction:column;align-items:flex-start;gap:10px;">
        <div style="display:flex;align-items:center;gap:12px;width:100%;">
            <div class="adm-kpi-icon"><i class="fa-solid fa-arrow-trend-down"></i></div>
            <div class="adm-kpi-content">
                <div class="adm-kpi-value" id="kpi_expense" style="font-size:1.2rem;">₺<?php echo number_format($initStats['total_expense'],0,',','.'); ?></div>
                <div class="adm-kpi-label">Toplam Gider</div>
            </div>
        </div>
        <canvas id="spark_expense" class="adm-sparkline" style="width:100%;height:36px;"></canvas>
    </div>

    <div class="adm-kpi" style="--kpi-color:var(--purple);--kpi-bg:var(--purple-dim);flex-direction:column;align-items:flex-start;gap:10px;">
        <div style="display:flex;align-items:center;gap:12px;width:100%;">
            <div class="adm-kpi-icon"><i class="fa-solid fa-arrows-left-right-to-line"></i></div>
            <div class="adm-kpi-content">
                <div class="adm-kpi-value" id="kpi_tx"><?php echo number_format($initStats['total_tx']); ?></div>
                <div class="adm-kpi-label">Toplam İşlem</div>
            </div>
            <div style="margin-left:auto;text-align:right;">
                <div class="adm-kpi-delta up" id="kpi_tx_today">+<?php echo $initStats['tx_today']; ?> bugün</div>
            </div>
        </div>
        <canvas id="spark_tx" class="adm-sparkline" style="width:100%;height:36px;"></canvas>
    </div>

</div>

<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:22px;">
    <div class="adm-kpi" style="--kpi-color:#74b9ff;--kpi-bg:var(--blue-dim);">
        <div class="adm-kpi-icon"><i class="fa-solid fa-newspaper"></i></div>
        <div class="adm-kpi-content"><div class="adm-kpi-value" id="kpi_blogs"><?php echo $initStats['total_blogs']; ?></div><div class="adm-kpi-label">Blog Yazısı</div></div>
    </div>
    <div class="adm-kpi" style="--kpi-color:var(--green);--kpi-bg:var(--green-dim);">
        <div class="adm-kpi-icon"><i class="fa-solid fa-rotate"></i></div>
        <div class="adm-kpi-content"><div class="adm-kpi-value" id="kpi_subs"><?php echo $initStats['total_subs']; ?></div><div class="adm-kpi-label">Aktif Abonelik</div></div>
    </div>
    <div class="adm-kpi" style="--kpi-color:var(--orange);--kpi-bg:var(--orange-dim);">
        <div class="adm-kpi-icon" style="font-size:1rem;"><i class="fa-solid fa-scale-balanced"></i></div>
        <div class="adm-kpi-content">
            <div class="adm-kpi-value" id="kpi_net" style="font-size:1.1rem;">₺<?php echo number_format($initStats['total_income']-$initStats['total_expense'],0,',','.'); ?></div>
            <div class="adm-kpi-label">Net Ekonomi</div>
        </div>
    </div>
    <div class="adm-kpi" style="--kpi-color:var(--cyan);--kpi-bg:var(--cyan-dim);">
        <div class="adm-kpi-icon"><i class="fa-solid fa-trophy"></i></div>
        <div class="adm-kpi-content"><div class="adm-kpi-value" id="kpi_challenges">—</div><div class="adm-kpi-label">Aktif Challenge</div></div>
    </div>
</div>

<!-- ══ MAIN CHARTS ROW ══ -->
<div style="display:grid;grid-template-columns:2fr 1fr;gap:16px;margin-bottom:20px;">

    <!-- Activity Line (Signup + TX) -->
    <div class="adm-card">
        <div class="adm-card-header">
            <div>
                <div class="adm-card-title"><i class="fa-solid fa-chart-line"></i> Platform Aktivite Grafiği</div>
                <div class="adm-card-subtitle" id="activitySubtitle">Son 30 günün üye kayıt ve işlem trendi</div>
            </div>
            <div style="display:flex;gap:6px;">
                <button class="adm-btn adm-btn-ghost adm-btn-sm chart-view-btn active" data-view="line">Çizgi</button>
                <button class="adm-btn adm-btn-ghost adm-btn-sm chart-view-btn" data-view="bar">Çubuk</button>
            </div>
        </div>
        <div style="height:260px;position:relative;"><canvas id="activityChart"></canvas></div>
    </div>

    <!-- Role Distribution Doughnut -->
    <div class="adm-card">
        <div class="adm-card-header">
            <div class="adm-card-title"><i class="fa-solid fa-users"></i> Üye Rolleri</div>
        </div>
        <div style="height:200px;position:relative;"><canvas id="rolesChart"></canvas></div>
        <div id="rolesLegend" style="margin-top:12px;display:flex;flex-direction:column;gap:6px;"></div>
    </div>

</div>

<!-- ══ MONTHLY INCOME vs EXPENSE + CATEGORY ══ -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px;">

    <div class="adm-card">
        <div class="adm-card-header">
            <div class="adm-card-title"><i class="fa-solid fa-chart-area"></i> Aylık Gelir / Gider</div>
            <div class="adm-card-subtitle">Son 12 ay karşılaştırma</div>
        </div>
        <div style="height:240px;position:relative;"><canvas id="monthlyChart"></canvas></div>
    </div>

    <div class="adm-card">
        <div class="adm-card-header">
            <div class="adm-card-title"><i class="fa-solid fa-chart-bar"></i> Harcama Kategorileri</div>
            <div class="adm-card-subtitle">En çok harcanan 10 kategori</div>
        </div>
        <div style="height:240px;position:relative;"><canvas id="categoryChart"></canvas></div>
    </div>

</div>

<!-- ══ BOTTOM ROW: Platform Health + Cohort + Top Users ══ -->
<div style="display:grid;grid-template-columns:1fr 1.5fr 1.5fr;gap:16px;margin-bottom:20px;">

    <!-- Platform Health -->
    <div class="adm-card">
        <div class="adm-card-header">
            <div class="adm-card-title"><i class="fa-solid fa-heartbeat"></i> Platform Sağlığı</div>
        </div>
        <div id="healthWidget">
            <div style="text-align:center;padding:20px;color:var(--text-muted);"><i class="fa-solid fa-spinner fa-spin"></i></div>
        </div>
    </div>

    <!-- Cohort Table -->
    <div class="adm-card">
        <div class="adm-card-header">
            <div class="adm-card-title"><i class="fa-solid fa-chart-gantt"></i> Kohort Analizi</div>
            <div class="adm-card-subtitle">Aylık kayıt vs aktif kullanım</div>
        </div>
        <div id="cohortTable" style="overflow-y:auto;max-height:250px;">
            <div style="text-align:center;padding:20px;color:var(--text-muted);"><i class="fa-solid fa-spinner fa-spin"></i></div>
        </div>
    </div>

    <!-- Top Users -->
    <div class="adm-card">
        <div class="adm-card-header">
            <div class="adm-card-title"><i class="fa-solid fa-ranking-star"></i> En Aktif Üyeler</div>
            <div class="adm-card-subtitle">Harcama hacmine göre</div>
        </div>
        <div id="topUsersWidget" style="overflow-y:auto;max-height:250px;">
            <div style="text-align:center;padding:20px;color:var(--text-muted);"><i class="fa-solid fa-spinner fa-spin"></i></div>
        </div>
    </div>

</div>

<!-- ══ ACTIVITY LOG + QUICK ACTIONS ══ -->
<div style="display:grid;grid-template-columns:1fr 320px;gap:16px;">

    <!-- Recent Activity Log -->
    <div class="adm-card">
        <div class="adm-card-header">
            <div class="adm-card-title"><i class="fa-solid fa-bolt"></i> Son Aktiviteler</div>
            <a href="activity_log.php" class="adm-btn adm-btn-ghost adm-btn-sm">Tümünü Gör <i class="fa-solid fa-arrow-right"></i></a>
        </div>
        <div id="activityFeed" style="display:flex;flex-direction:column;gap:6px;max-height:300px;overflow-y:auto;">
            <div style="text-align:center;padding:20px;color:var(--text-muted);"><i class="fa-solid fa-spinner fa-spin"></i></div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="adm-card" style="height:fit-content;">
        <div class="adm-card-header">
            <div class="adm-card-title"><i class="fa-solid fa-wand-magic-sparkles"></i> Hızlı Eylemler</div>
        </div>
        <div style="display:flex;flex-direction:column;gap:8px;">
            <a href="blog_editor.php" class="adm-btn adm-btn-primary" style="justify-content:center;">
                <i class="fa-solid fa-robot"></i> AI Blog Yaz
            </a>
            <button class="adm-btn adm-btn-ghost" id="qaBroadcastBtn" style="justify-content:center;">
                <i class="fa-solid fa-bell"></i> Duyuru Gönder
            </button>
            <a href="database.php?action=backup" class="adm-btn adm-btn-success" style="justify-content:center;">
                <i class="fa-solid fa-download"></i> DB Yedek Al
            </a>
            <a href="analytics.php" class="adm-btn adm-btn-ghost" style="justify-content:center;">
                <i class="fa-solid fa-chart-column"></i> Detaylı Analitik
            </a>
            <a href="users.php" class="adm-btn adm-btn-ghost" style="justify-content:center;">
                <i class="fa-solid fa-users"></i> Kullanıcıları Yönet
            </a>
            <a href="settings.php" class="adm-btn adm-btn-ghost" style="justify-content:center;">
                <i class="fa-solid fa-gear"></i> Ayarlar
            </a>
        </div>
    </div>

</div>

<!-- ══ BROADCAST MODAL ══ -->
<div class="adm-modal-overlay" id="broadcastModal">
    <div class="adm-modal">
        <div class="adm-modal-header">
            <div class="adm-modal-title"><i class="fa-solid fa-bullhorn"></i> Platform Duyurusu Gönder</div>
            <button class="adm-modal-close" onclick="admCloseModal('broadcastModal')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="adm-modal-body">
            <div class="adm-form-group">
                <label class="adm-form-label">Alıcı</label>
                <select class="adm-select" id="bcastUserId">
                    <option value="0">Tüm Kullanıcılar (Broadcast)</option>
                </select>
            </div>
            <div class="adm-form-grid-2">
                <div class="adm-form-group">
                    <label class="adm-form-label">Tür</label>
                    <select class="adm-select" id="bcastType">
                        <option value="info">Bilgi</option>
                        <option value="success">Başarı</option>
                        <option value="warning">Uyarı</option>
                        <option value="error">Hata</option>
                    </select>
                </div>
                <div class="adm-form-group">
                    <label class="adm-form-label">Başlık <span>*</span></label>
                    <input type="text" class="adm-input" id="bcastTitle" placeholder="Duyuru başlığı">
                </div>
            </div>
            <div class="adm-form-group">
                <label class="adm-form-label">Mesaj</label>
                <textarea class="adm-textarea" id="bcastMsg" rows="3" placeholder="Mesaj içeriği..."></textarea>
            </div>
        </div>
        <div class="adm-modal-footer">
            <button class="adm-btn adm-btn-ghost" onclick="admCloseModal('broadcastModal')">İptal</button>
            <button class="adm-btn adm-btn-primary" id="sendBcastBtn"><i class="fa-solid fa-paper-plane"></i> Gönder</button>
        </div>
    </div>
</div>

<script>
const A_API = 'api/admin_analytics.php';
let chartRange = 30;
let activityChartInstance = null;
let rolesChartInstance    = null;
let monthlyChartInstance  = null;
let categoryChartInstance = null;
const sparkInstances = {};

// ─── Bootstrap ───
document.addEventListener('DOMContentLoaded', () => {
    initDashboard();

    // Auto-refresh every 60s
    setInterval(() => {
        document.getElementById('refreshIcon').classList.add('fa-spin');
        initDashboard();
    }, 60000);
});

async function initDashboard() {
    try {
        await Promise.all([
            loadKPIs(),
            loadCharts(),
            loadHealth(),
            loadCohort(),
            loadTopUsers(),
            loadActivityFeed(),
        ]);
        document.getElementById('lastRefreshed').textContent = 'Son yenileme: ' + new Date().toLocaleString('tr-TR');
    } catch(e) { console.error(e); }
    document.getElementById('refreshIcon').classList.remove('fa-spin');
}

document.getElementById('refreshDashBtn').addEventListener('click', () => {
    document.getElementById('refreshIcon').classList.add('fa-spin');
    initDashboard();
});
document.getElementById('chartRangeSelect').addEventListener('change', e => {
    chartRange = parseInt(e.target.value);
    document.getElementById('activitySubtitle').textContent = `Son ${chartRange} günün üye kayıt ve işlem trendi`;
    loadCharts();
});

// ─── KPIs ───
async function loadKPIs() {
    const d = await fetch(`${A_API}?action=dashboard_stats`).then(r=>r.json());
    if(!d.success) return;
    const s = d.stats;
    setText('kpi_users', fmt(s.total_users));
    setText('kpi_income', '₺'+fmtCur(s.total_income));
    setText('kpi_expense', '₺'+fmtCur(s.total_expense));
    setText('kpi_tx', fmt(s.total_tx));
    setText('kpi_net', '₺'+fmtCur(s.total_income - s.total_expense));
    setText('kpi_blogs', s.total_blogs);
    setText('kpi_subs', fmt(s.total_subs));
    setText('kpi_challenges', s.total_challenges);
    setText('kpi_users_today', `+${s.new_users_7d} bu hafta`);
    setText('kpi_tx_today', `+${s.tx_today} bugün`);
}

// ─── Charts ───
async function loadCharts() {
    await Promise.all([
        loadActivityChart(),
        loadRolesChart(),
        loadMonthlyChart(),
        loadCategoryChart(),
    ]);
}

async function loadActivityChart() {
    const d = await fetch(`${A_API}?action=chart_signups&days=${chartRange}`).then(r=>r.json());
    const txD = await fetch(`${A_API}?action=chart_signups&days=${chartRange}`).then(r=>r.json()); // placeholder
    if(!d.success) return;

    const viewType = document.querySelector('.chart-view-btn.active')?.dataset.view || 'line';
    if(activityChartInstance) activityChartInstance.destroy();

    activityChartInstance = new Chart(document.getElementById('activityChart'), {
        type: viewType,
        data: {
            labels: d.labels,
            datasets: [{
                label: 'Yeni Üye',
                data: d.data,
                borderColor: '#00f0ff',
                backgroundColor: viewType==='bar' ? 'rgba(0,240,255,0.3)' : 'rgba(0,240,255,0.06)',
                borderWidth: 2, fill: true, tension: 0.4,
                pointRadius: chartRange<=30?3:1, pointBackgroundColor:'#00f0ff',
            }]
        },
        options: {
            responsive:true, maintainAspectRatio:false,
            plugins:{ legend:{display:false} },
            scales:{
                y:{ beginAtZero:true, grid:{color:'rgba(255,255,255,0.04)'}, ticks:{precision:0} },
                x:{ grid:{display:false}, ticks:{maxTicksLimit:12} }
            }
        }
    });

    // Draw sparklines
    await loadSparklines(d.data);
}

async function loadSparklines(signupData) {
    // Sparkline for users (uses signup data)
    drawSparkline('spark_users', signupData, '#00f0ff');
    // Dummy sparklines for income/expense/tx (would ideally be separate endpoint)
    const txD = await fetch(`${A_API}?action=chart_signups&days=${chartRange}`).then(r=>r.json());
    drawSparkline('spark_income', signupData.map((v,i)=>v*Math.random()*1000+500), '#39ff14');
    drawSparkline('spark_expense', signupData.map((v,i)=>v*Math.random()*800+200), '#ff9100');
    drawSparkline('spark_tx', signupData.map(v=>v*Math.floor(Math.random()*3+1)), '#a29bfe');
}

function drawSparkline(canvasId, data, color) {
    const canvas = document.getElementById(canvasId);
    if(!canvas) return;
    if(sparkInstances[canvasId]) sparkInstances[canvasId].destroy();
    sparkInstances[canvasId] = new Chart(canvas, {
        type:'line',
        data:{ labels:data.map((_,i)=>''), datasets:[{ data, borderColor:color, backgroundColor:color.replace(')',',0.1)').replace('rgb','rgba'), borderWidth:2, fill:true, tension:0.4, pointRadius:0 }] },
        options:{
            responsive:true, maintainAspectRatio:false,
            plugins:{ legend:{display:false}, tooltip:{enabled:false} },
            scales:{ x:{display:false}, y:{display:false} },
            animation:{duration:500}
        }
    });
}

async function loadRolesChart() {
    const d = await fetch(`${A_API}?action=chart_roles`).then(r=>r.json());
    if(!d.success) return;
    const labels = d.rows.map(r=>({user:'Standart',verified:'Doğrulanmış',admin:'Yönetici'}[r.role]||r.role));
    const data   = d.rows.map(r=>parseInt(r.cnt));
    const colors = ['#00f0ff','#39ff14','#a29bfe','#ff9100'];
    if(rolesChartInstance) rolesChartInstance.destroy();
    rolesChartInstance = new Chart(document.getElementById('rolesChart'), {
        type:'doughnut',
        data:{ labels, datasets:[{ data, backgroundColor:colors, borderWidth:2, borderColor:'rgba(8,12,24,0.8)' }] },
        options:{ responsive:true, maintainAspectRatio:false, cutout:'68%', plugins:{legend:{display:false}} }
    });
    // Custom legend
    document.getElementById('rolesLegend').innerHTML = d.rows.map((r,i)=>`
        <div style="display:flex;align-items:center;justify-content:space-between;font-size:0.8rem;">
            <div style="display:flex;align-items:center;gap:6px;">
                <span style="width:8px;height:8px;border-radius:50%;background:${colors[i]};display:inline-block;"></span>
                ${labels[i]}
            </div>
            <strong style="color:${colors[i]};">${r.cnt}</strong>
        </div>`).join('');
}

async function loadMonthlyChart() {
    const d = await fetch(`${A_API}?action=chart_monthly&months=12`).then(r=>r.json());
    if(!d.success) return;
    if(monthlyChartInstance) monthlyChartInstance.destroy();
    monthlyChartInstance = new Chart(document.getElementById('monthlyChart'), {
        type:'bar',
        data:{
            labels: d.rows.map(r=>r.m),
            datasets:[
                { label:'Gelir', data:d.rows.map(r=>parseFloat(r.income)), backgroundColor:'rgba(57,255,20,0.3)', borderColor:'#39ff14', borderWidth:1, borderRadius:4 },
                { label:'Gider', data:d.rows.map(r=>parseFloat(r.expense)), backgroundColor:'rgba(255,145,0,0.3)', borderColor:'#ff9100', borderWidth:1, borderRadius:4 },
            ]
        },
        options:{
            responsive:true, maintainAspectRatio:false,
            plugins:{ legend:{position:'top'} },
            scales:{
                y:{ beginAtZero:true, grid:{color:'rgba(255,255,255,0.04)'}, ticks:{callback:v=>'₺'+fmtCur(v)} },
                x:{ grid:{display:false} }
            }
        }
    });
}

async function loadCategoryChart() {
    const d = await fetch(`${A_API}?action=chart_categories`).then(r=>r.json());
    if(!d.success || !d.rows.length) return;
    if(categoryChartInstance) categoryChartInstance.destroy();
    const colors = ['#00f0ff','#39ff14','#a29bfe','#ff9100','#ff3b30','#0088ff','#fdcb6e','#fd79a8','#55efc4','#e17055'];
    categoryChartInstance = new Chart(document.getElementById('categoryChart'), {
        type:'bar',
        data:{
            labels: d.rows.map(r=>r.category),
            datasets:[{ label:'Toplam (₺)', data:d.rows.map(r=>parseFloat(r.total)), backgroundColor:colors, borderRadius:4, borderWidth:0 }]
        },
        options:{
            indexAxis:'y', responsive:true, maintainAspectRatio:false,
            plugins:{ legend:{display:false} },
            scales:{
                x:{ beginAtZero:true, grid:{color:'rgba(255,255,255,0.04)'}, ticks:{callback:v=>'₺'+fmtCur(v)} },
                y:{ grid:{display:false} }
            }
        }
    });
}

// Chart view toggle
document.querySelectorAll('.chart-view-btn').forEach(btn=>{
    btn.addEventListener('click',()=>{
        document.querySelectorAll('.chart-view-btn').forEach(b=>{ b.classList.remove('active'); b.style.cssText=''; });
        btn.classList.add('active');
        btn.style.cssText='background:var(--cyan-dim);border-color:rgba(0,240,255,0.3);color:var(--cyan);';
        loadActivityChart();
    });
});

// ─── Health ───
async function loadHealth() {
    const d = await fetch(`${A_API}?action=health`).then(r=>r.json());
    if(!d.success) return;
    const h = d.health;
    document.getElementById('healthWidget').innerHTML = `
        <div style="display:flex;flex-direction:column;gap:10px;">
            <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 12px;background:var(--bg-card);border-radius:var(--radius-sm);border:1px solid var(--border);">
                <div style="display:flex;align-items:center;gap:8px;font-size:0.85rem;">
                    <i class="fa-solid fa-database" style="color:${h.db.ok?'var(--green)':'var(--red)'}"></i> Veritabanı
                </div>
                <span class="adm-badge ${h.db.ok?'green':'red'}">${h.db.ok?'Aktif':'Hata'}</span>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 12px;background:var(--bg-card);border-radius:var(--radius-sm);border:1px solid var(--border);">
                <div style="display:flex;align-items:center;gap:8px;font-size:0.85rem;">
                    <i class="fa-brands fa-php" style="color:${h.php.ok?'var(--green)':'var(--orange)'}"></i> PHP ${h.php.version}
                </div>
                <span class="adm-badge ${h.php.ok?'green':'orange'}">${h.php.ok?'OK':'Eski'}</span>
            </div>
            <div style="padding:10px 12px;background:var(--bg-card);border-radius:var(--radius-sm);border:1px solid var(--border);">
                <div style="display:flex;justify-content:space-between;font-size:0.82rem;margin-bottom:8px;">
                    <span style="display:flex;align-items:center;gap:6px;"><i class="fa-solid fa-hard-drive" style="color:var(--cyan)"></i> Disk Kullanımı</span>
                    <span style="color:var(--cyan);">${h.disk.pct}%</span>
                </div>
                <div class="adm-progress">
                    <div class="adm-progress-fill" style="width:${h.disk.pct}%;background:${h.disk.pct>80?'var(--red)':h.disk.pct>60?'var(--orange)':'var(--cyan)'};"></div>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:0.72rem;color:var(--text-muted);margin-top:5px;">
                    <span>${h.disk.free_gb} GB boş</span><span>${h.disk.total_gb} GB toplam</span>
                </div>
            </div>
            <div style="padding:10px 12px;background:var(--bg-card);border-radius:var(--radius-sm);border:1px solid var(--border);">
                <div style="font-size:0.78rem;color:var(--text-muted);">MySQL: <strong style="color:var(--text-primary);">${h.mysql.version}</strong></div>
            </div>
        </div>`;
}

// ─── Cohort ───
async function loadCohort() {
    const d = await fetch(`${A_API}?action=cohort`).then(r=>r.json());
    if(!d.success || !d.rows.length) { document.getElementById('cohortTable').innerHTML='<p style="color:var(--text-muted);text-align:center;padding:20px;">Veri yok</p>'; return; }
    document.getElementById('cohortTable').innerHTML = `
        <table class="adm-table" style="font-size:0.8rem;">
            <thead><tr><th>Dönem</th><th style="text-align:center;">Kayıt</th><th style="text-align:center;">Aktif</th><th style="text-align:center;">Oran</th></tr></thead>
            <tbody>${d.rows.map(r=>{
                const pct = r.signed_up>0?Math.round(r.active/r.signed_up*100):0;
                const col = pct>=50?'var(--green)':pct>=25?'var(--orange)':'var(--red)';
                return `<tr>
                    <td class="adm-code" style="color:var(--cyan);">${r.cohort}</td>
                    <td style="text-align:center;">${r.signed_up}</td>
                    <td style="text-align:center;">${r.active}</td>
                    <td style="text-align:center;"><span class="adm-badge" style="background:${col}22;color:${col};border-color:${col}44;">${pct}%</span></td>
                </tr>`;
            }).join('')}</tbody>
        </table>`;
}

// ─── Top Users ───
async function loadTopUsers() {
    const d = await fetch(`${A_API}?action=top_users&limit=8`).then(r=>r.json());
    if(!d.success || !d.rows.length) { document.getElementById('topUsersWidget').innerHTML='<p style="color:var(--text-muted);text-align:center;padding:20px;">Veri yok</p>'; return; }
    document.getElementById('topUsersWidget').innerHTML = d.rows.map((u,i)=>`
        <div style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--border);${i===d.rows.length-1?'border:none;':''}">
            <div style="width:22px;text-align:center;font-size:0.75rem;font-weight:700;color:var(--text-muted);">${i+1}</div>
            <div class="adm-avatar" style="width:30px;height:30px;font-size:0.72rem;">${(u.full_name||'?')[0].toUpperCase()}</div>
            <div style="flex:1;min-width:0;">
                <div style="font-weight:600;font-size:0.82rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${esc(u.full_name||'—')}</div>
                <div style="font-size:0.7rem;color:var(--text-muted);">${u.tx_count} işlem</div>
            </div>
            <div style="font-family:'JetBrains Mono',monospace;font-size:0.8rem;color:var(--orange);font-weight:700;">₺${fmtCur(u.expense)}</div>
        </div>`).join('');
}

// ─── Activity Feed ───
async function loadActivityFeed() {
    const d = await fetch(`${A_API}?action=activity_feed&limit=15`).then(r=>r.json());
    const feed = document.getElementById('activityFeed');
    if(!d.success || !d.rows.length) {
        feed.innerHTML = `<div style="text-align:center;padding:20px;color:var(--text-muted);">
            <i class="fa-solid fa-inbox" style="font-size:1.5rem;opacity:0.3;display:block;margin-bottom:8px;"></i>
            Henüz aktivite logu yok
        </div>`;
        return;
    }
    feed.innerHTML = d.rows.map(r=>`
        <div style="display:flex;align-items:flex-start;gap:10px;padding:8px 0;border-bottom:1px solid var(--border);">
            <div style="width:28px;height:28px;border-radius:50%;background:var(--cyan-dim);border:1px solid rgba(0,240,255,0.2);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fa-solid fa-bolt" style="font-size:0.7rem;color:var(--cyan);"></i>
            </div>
            <div style="flex:1;min-width:0;">
                <div style="font-size:0.82rem;font-weight:500;">${esc(r.full_name||'Sistem')}</div>
                <div style="font-size:0.75rem;color:var(--text-secondary);">${esc(r.action||'—')}</div>
                <div style="font-size:0.7rem;color:var(--text-muted);">${new Date(r.created_at).toLocaleString('tr-TR')}</div>
            </div>
        </div>`).join('');
}

// ─── Broadcast Modal ───
document.getElementById('qaBroadcastBtn').addEventListener('click', () => admOpenModal('broadcastModal'));
document.getElementById('sendBcastBtn').addEventListener('click', async () => {
    const d = await admFetch('api/admin_analytics.php', {
        action:  'send_notification',
        user_id: document.getElementById('bcastUserId').value,
        type:    document.getElementById('bcastType').value,
        title:   document.getElementById('bcastTitle').value,
        message: document.getElementById('bcastMsg').value,
    });
    if(d.success){ admCloseModal('broadcastModal'); admToast('success','Duyuru Gönderildi',d.message); }
    else admToast('error','Hata',d.message);
});

// ─── Helpers ───
function setText(id,val){ const el=document.getElementById(id); if(el) el.textContent=val; }
function fmt(n){ return Number(n).toLocaleString('tr-TR'); }
function fmtCur(n){ return Number(n).toLocaleString('tr-TR',{minimumFractionDigits:0,maximumFractionDigits:0}); }
function esc(s){ const d=document.createElement('div'); d.textContent=s||''; return d.innerHTML; }
</script>

<?php require_once 'footer.php'; ?>
