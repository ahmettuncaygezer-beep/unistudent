<?php
$PAGE_TITLE = 'Detaylı Analitik';
require_once 'header.php';
?>

<div class="adm-section-header">
    <div>
        <div class="adm-section-title">Platform Analitik Merkezi</div>
        <div class="adm-section-desc">Gerçek zamanlı platform metrikleri ve kullanıcı davranış analizi</div>
    </div>
    <div class="adm-section-actions">
        <select class="adm-select adm-btn-sm" id="anRange" style="width:auto;min-width:140px;">
            <option value="7">Son 7 Gün</option>
            <option value="30" selected>Son 30 Gün</option>
            <option value="60">Son 60 Gün</option>
            <option value="90">Son 90 Gün</option>
        </select>
        <button class="adm-btn adm-btn-ghost adm-btn-sm" id="anRefresh"><i class="fa-solid fa-rotate"></i> Yenile</button>
    </div>
</div>

<!-- ══ KPI ROW ══ -->
<div id="anKpiGrid" style="display:grid;grid-template-columns:repeat(6,1fr);gap:12px;margin-bottom:20px;">
    <?php
    $kpis=[
        ['id'=>'an_users','icon'=>'fa-users','label'=>'Toplam Üye','color'=>'cyan'],
        ['id'=>'an_active','icon'=>'fa-user-check','label'=>'Aktif (30g)','color'=>'green'],
        ['id'=>'an_income','icon'=>'fa-arrow-trend-up','label'=>'Gelir','color'=>'green'],
        ['id'=>'an_expense','icon'=>'fa-arrow-trend-down','label'=>'Gider','color'=>'orange'],
        ['id'=>'an_net','icon'=>'fa-scale-balanced','label'=>'Net Bilanço','color'=>'cyan'],
        ['id'=>'an_retained','icon'=>'fa-repeat','label'=>'Geri Dönen','color'=>'purple'],
    ];
    foreach($kpis as $k): ?>
    <div class="adm-kpi" style="--kpi-color:var(--<?php echo $k['color']; ?>);--kpi-bg:var(--<?php echo $k['color']; ?>-dim);padding:14px;">
        <div class="adm-kpi-icon" style="width:38px;height:38px;font-size:1rem;"><i class="fa-solid <?php echo $k['icon']; ?>"></i></div>
        <div class="adm-kpi-content">
            <div class="adm-kpi-value" id="<?php echo $k['id']; ?>" style="font-size:1.2rem;">—</div>
            <div class="adm-kpi-label" style="font-size:0.7rem;"><?php echo $k['label']; ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- ══ CHARTS ROW 1 ══ -->
<div style="display:grid;grid-template-columns:3fr 2fr;gap:16px;margin-bottom:18px;">

    <!-- Signup Trend -->
    <div class="adm-card">
        <div class="adm-card-header">
            <div>
                <div class="adm-card-title"><i class="fa-solid fa-chart-line"></i> Üye Kazanım Trendi</div>
                <div class="adm-card-subtitle" id="signupSubtitle">Seçili dönemdeki günlük kayıtlar</div>
            </div>
            <div style="display:flex;gap:6px;align-items:center;">
                <div id="signupTotal" class="adm-badge cyan" style="font-size:0.8rem;padding:4px 12px;"></div>
            </div>
        </div>
        <div style="height:240px;"><canvas id="anSignupChart"></canvas></div>
    </div>

    <!-- User Geography -->
    <div class="adm-card">
        <div class="adm-card-header">
            <div class="adm-card-title"><i class="fa-solid fa-university"></i> Üniversite Dağılımı</div>
            <div class="adm-card-subtitle">En çok üye olan 8 üniversite</div>
        </div>
        <div style="height:240px;"><canvas id="anUniChart"></canvas></div>
    </div>

</div>

<!-- ══ CHARTS ROW 2 ══ -->
<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:18px;">

    <div class="adm-card">
        <div class="adm-card-header">
            <div class="adm-card-title"><i class="fa-solid fa-chart-area"></i> Gelir vs Gider</div>
            <div class="adm-card-subtitle">Aylık karşılaştırma (12 ay)</div>
        </div>
        <div style="height:220px;"><canvas id="anMonthlyChart"></canvas></div>
    </div>

    <div class="adm-card">
        <div class="adm-card-header">
            <div class="adm-card-title"><i class="fa-solid fa-chart-bar"></i> İşlem Kategorileri</div>
            <div class="adm-card-subtitle">Gider dağılımı (₺)</div>
        </div>
        <div style="height:220px;"><canvas id="anCatChart"></canvas></div>
    </div>

    <div class="adm-card">
        <div class="adm-card-header">
            <div class="adm-card-title"><i class="fa-solid fa-rotate"></i> Abonelik Kategorileri</div>
            <div class="adm-card-subtitle">Aktif abonelik hacmi</div>
        </div>
        <div style="height:220px;"><canvas id="anSubsChart"></canvas></div>
    </div>

</div>

<!-- ══ ROW 3: Cohort + Hourly ══ -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:18px;">

    <div class="adm-card">
        <div class="adm-card-header">
            <div class="adm-card-title"><i class="fa-solid fa-chart-gantt"></i> Kohort Analizi (12 Ay)</div>
            <div class="adm-card-subtitle">Aktivasyon oranları</div>
        </div>
        <div id="anCohortTable" style="overflow-y:auto;max-height:280px;">
            <div style="text-align:center;padding:30px;color:var(--text-muted);"><i class="fa-solid fa-spinner fa-spin"></i></div>
        </div>
    </div>

    <div class="adm-card">
        <div class="adm-card-header">
            <div class="adm-card-title"><i class="fa-solid fa-clock"></i> Bugünkü Saatlik Aktivite</div>
            <div class="adm-card-subtitle">Bugün yapılan işlemlerin saatlik dağılımı</div>
        </div>
        <div style="height:240px;"><canvas id="anHourlyChart"></canvas></div>
    </div>

</div>

<!-- ══ TABLE: Top Users ══ -->
<div class="adm-card" style="margin-bottom:18px;">
    <div class="adm-card-header">
        <div class="adm-card-title"><i class="fa-solid fa-ranking-star"></i> En Aktif Üyeler (Harcamaya Göre)</div>
        <a href="users.php" class="adm-btn adm-btn-ghost adm-btn-sm">Tümünü Yönet</a>
    </div>
    <div class="adm-table-wrap" style="border:none;">
        <div style="overflow-x:auto;">
            <table class="adm-table" id="anTopUsersTable">
                <thead><tr><th>#</th><th>Üye</th><th>Üniversite</th><th style="text-align:right;">İşlem</th><th style="text-align:right;">Gelir</th><th style="text-align:right;">Gider</th><th style="text-align:right;">Oran</th></tr></thead>
                <tbody><tr><td colspan="7" style="text-align:center;padding:30px;color:var(--text-muted);"><i class="fa-solid fa-spinner fa-spin"></i></td></tr></tbody>
            </table>
        </div>
    </div>
</div>

<script>
const AN_API = 'api/admin_analytics.php';
let anRange = 30;
const anCharts = {};

document.addEventListener('DOMContentLoaded', loadAll);
document.getElementById('anRange').addEventListener('change', e=>{ anRange=parseInt(e.target.value); loadAll(); });
document.getElementById('anRefresh').addEventListener('click', loadAll);

async function loadAll() {
    document.getElementById('anRefresh').querySelector('i').classList.add('fa-spin');
    await Promise.all([loadAnKPIs(), loadSignupChart(), loadUniChart(), loadMonthlyChart2(), loadCatChart(), loadSubsChart(), loadCohortTable(), loadHourlyChart(), loadTopUsersTable()]);
    document.getElementById('anRefresh').querySelector('i').classList.remove('fa-spin');
}

async function loadAnKPIs() {
    const d = await fetch(`${AN_API}?action=dashboard_stats`).then(r=>r.json());
    if(!d.success) return;
    const s = d.stats;
    const setText=(id,v)=>{ const el=document.getElementById(id); if(el) el.textContent=v; };
    setText('an_users',  fmt(s.total_users));
    setText('an_active', fmt(s.active_users_30d));
    setText('an_income', '₺'+fmtK(s.total_income));
    setText('an_expense','₺'+fmtK(s.total_expense));
    setText('an_net',    '₺'+fmtK(s.total_income-s.total_expense));
    setText('an_retained',fmt(s.retained_users));
}

async function loadSignupChart() {
    const d = await fetch(`${AN_API}?action=chart_signups&days=${anRange}`).then(r=>r.json());
    if(!d.success) return;
    const total = d.data.reduce((a,b)=>a+b,0);
    document.getElementById('signupTotal').textContent = `+${total} kayıt`;
    document.getElementById('signupSubtitle').textContent = `Son ${anRange} gün — ${total} yeni üye`;
    mkChart('anSignupChart','line',{labels:d.labels,datasets:[{label:'Günlük Kayıt',data:d.data,borderColor:'#00f0ff',backgroundColor:'rgba(0,240,255,0.08)',borderWidth:2,fill:true,tension:0.4,pointRadius:anRange<=30?3:1,pointBackgroundColor:'#00f0ff'}]},{plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,grid:{color:'rgba(255,255,255,0.04)'},ticks:{precision:0}},x:{grid:{display:false},ticks:{maxTicksLimit:10}}}});
}

async function loadUniChart() {
    const res = await fetch(`${AN_API}?action=list`).catch(()=>null);
    // Uni data from users API
    const d = await fetch(`api/admin_users.php?action=list&limit=999&offset=0`).then(r=>r.json()).catch(()=>({users:[]}));
    const map={};
    (d.users||[]).forEach(u=>{ const uni=u.university_name||'Belirtilmemiş'; map[uni]=(map[uni]||0)+1; });
    const sorted=Object.entries(map).sort((a,b)=>b[1]-a[1]).slice(0,8);
    const colors=['#00f0ff','#39ff14','#a29bfe','#ff9100','#0088ff','#ff3b30','#fdcb6e','#fd79a8'];
    mkChart('anUniChart','doughnut',{labels:sorted.map(([k])=>k),datasets:[{data:sorted.map(([,v])=>v),backgroundColor:colors,borderWidth:2,borderColor:'rgba(8,12,24,0.8)'}]},{cutout:'60%',plugins:{legend:{position:'right',labels:{font:{size:10},boxWidth:8}}}});
}

async function loadMonthlyChart2() {
    const d = await fetch(`${AN_API}?action=chart_monthly&months=12`).then(r=>r.json());
    if(!d.success) return;
    mkChart('anMonthlyChart','bar',{
        labels:d.rows.map(r=>r.m),
        datasets:[
            {label:'Gelir',data:d.rows.map(r=>parseFloat(r.income)),backgroundColor:'rgba(57,255,20,0.3)',borderColor:'#39ff14',borderWidth:1,borderRadius:3},
            {label:'Gider',data:d.rows.map(r=>parseFloat(r.expense)),backgroundColor:'rgba(255,145,0,0.3)',borderColor:'#ff9100',borderWidth:1,borderRadius:3},
        ]
    },{plugins:{legend:{position:'top'}},scales:{y:{beginAtZero:true,grid:{color:'rgba(255,255,255,0.04)'},ticks:{callback:v=>'₺'+fmtK(v)}},x:{grid:{display:false}}}});
}

async function loadCatChart() {
    const d = await fetch(`${AN_API}?action=chart_categories`).then(r=>r.json());
    if(!d.success||!d.rows.length) return;
    const cols=['#00f0ff','#39ff14','#a29bfe','#ff9100','#ff3b30','#0088ff','#fdcb6e','#fd79a8','#55efc4','#e17055'];
    mkChart('anCatChart','bar',{labels:d.rows.map(r=>r.category),datasets:[{label:'₺',data:d.rows.map(r=>parseFloat(r.total)),backgroundColor:cols,borderRadius:3,borderWidth:0}]},{indexAxis:'y',plugins:{legend:{display:false}},scales:{x:{beginAtZero:true,grid:{color:'rgba(255,255,255,0.04)'},ticks:{callback:v=>'₺'+fmtK(v)}},y:{grid:{display:false}}}});
}

async function loadSubsChart() {
    const d = await fetch(`${AN_API}?action=chart_subs`).then(r=>r.json());
    if(!d.success||!d.rows.length) return;
    const cols=['#00f0ff','#39ff14','#a29bfe','#ff9100','#0088ff','#ff3b30'];
    mkChart('anSubsChart','doughnut',{labels:d.rows.map(r=>r.category||'Diğer'),datasets:[{data:d.rows.map(r=>parseFloat(r.monthly)),backgroundColor:cols,borderWidth:2,borderColor:'rgba(8,12,24,0.8)'}]},{cutout:'55%',plugins:{legend:{position:'bottom',labels:{font:{size:10},boxWidth:8}}}});
}

async function loadCohortTable() {
    const d = await fetch(`${AN_API}?action=cohort`).then(r=>r.json());
    const el = document.getElementById('anCohortTable');
    if(!d.success||!d.rows.length){el.innerHTML='<p style="color:var(--text-muted);text-align:center;padding:20px;">Veri yok</p>';return;}
    el.innerHTML=`<table class="adm-table" style="font-size:0.8rem;">
        <thead><tr><th>Kohort</th><th style="text-align:center;">Kayıt</th><th style="text-align:center;">İşlem Yapan</th><th style="text-align:center;">Oran</th><th>Oran Çubuğu</th></tr></thead>
        <tbody>${d.rows.map(r=>{
            const pct=r.signed_up>0?Math.round(r.active/r.signed_up*100):0;
            const col=pct>=50?'var(--green)':pct>=25?'var(--orange)':'var(--red)';
            return `<tr>
                <td class="adm-code" style="color:var(--cyan);">${r.cohort}</td>
                <td style="text-align:center;">${r.signed_up}</td>
                <td style="text-align:center;">${r.active}</td>
                <td style="text-align:center;"><strong style="color:${col};">${pct}%</strong></td>
                <td style="width:100px;"><div class="adm-progress"><div class="adm-progress-fill" style="width:${pct}%;background:${col};"></div></div></td>
            </tr>`;
        }).join('')}</tbody></table>`;
}

async function loadHourlyChart() {
    const d = await fetch(`${AN_API}?action=chart_hourly`).then(r=>r.json());
    if(!d.success) return;
    mkChart('anHourlyChart','bar',{
        labels: d.labels.filter((_,i)=>i%2===0),
        datasets:[{
            label:'İşlem Sayısı',
            data: d.counts.filter((_,i)=>i%2===0),
            backgroundColor:'rgba(0,240,255,0.25)',
            borderColor:'#00f0ff', borderWidth:1, borderRadius:4
        }]
    },{plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,grid:{color:'rgba(255,255,255,0.04)'},ticks:{precision:0}},x:{grid:{display:false}}}});
}

async function loadTopUsersTable() {
    const d = await fetch(`${AN_API}?action=top_users&limit=15`).then(r=>r.json());
    const tbody = document.querySelector('#anTopUsersTable tbody');
    if(!d.success||!d.rows.length){tbody.innerHTML='<tr><td colspan="7" style="text-align:center;padding:30px;color:var(--text-muted);">Veri yok</td></tr>';return;}
    tbody.innerHTML=d.rows.map((u,i)=>{
        const ratio=u.income>0?Math.round(u.expense/u.income*100):100;
        const col=ratio>80?'var(--red)':ratio>50?'var(--orange)':'var(--green)';
        return `<tr>
            <td style="color:var(--text-muted);font-size:0.8rem;font-weight:700;">${i+1}</td>
            <td>
                <div class="adm-user-cell">
                    <div class="adm-avatar" style="width:30px;height:30px;font-size:0.72rem;">${(u.full_name||'?')[0].toUpperCase()}</div>
                    <div><div class="adm-user-name" style="font-size:0.85rem;">${esc(u.full_name||'—')}</div><div class="adm-user-email">${esc(u.email||'')}</div></div>
                </div>
            </td>
            <td style="font-size:0.78rem;color:var(--text-secondary);">${esc(u.university_name||'—')}</td>
            <td style="text-align:right;"><span class="adm-badge gray">${u.tx_count}</span></td>
            <td style="text-align:right;font-family:'JetBrains Mono',monospace;color:var(--green);font-size:0.83rem;">₺${fmtCur(u.income)}</td>
            <td style="text-align:right;font-family:'JetBrains Mono',monospace;color:var(--orange);font-size:0.83rem;">₺${fmtCur(u.expense)}</td>
            <td style="text-align:right;"><span class="adm-badge" style="background:${col}22;color:${col};border-color:${col}44;">${ratio}%</span></td>
        </tr>`;
    }).join('');
}

// ─── Chart factory ───
function mkChart(canvasId, type, data, options={}) {
    if(anCharts[canvasId]) anCharts[canvasId].destroy();
    const defaults = { responsive:true, maintainAspectRatio:false };
    anCharts[canvasId] = new Chart(document.getElementById(canvasId), { type, data, options:{...defaults,...options} });
}

function fmt(n){ return Number(n).toLocaleString('tr-TR'); }
function fmtCur(n){ return Number(n).toLocaleString('tr-TR',{minimumFractionDigits:0,maximumFractionDigits:0}); }
function fmtK(n){ n=parseFloat(n)||0; if(n>=1000000) return (n/1000000).toFixed(1)+'M'; if(n>=1000) return (n/1000).toFixed(0)+'K'; return Math.round(n).toString(); }
function esc(s){ const d=document.createElement('div'); d.textContent=s||''; return d.innerHTML; }
</script>

<?php require_once 'footer.php'; ?>
