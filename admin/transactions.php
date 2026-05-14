<?php
$PAGE_TITLE = 'İşlem Monitörü';
require_once 'header.php';
require_once 'api/database.php';
ensureTables(); $db = getDB();

// Server-side initial KPIs
$kpis = [];
try {
    $kpis['total']   = (int)$db->query("SELECT COUNT(*) FROM transactions")->fetchColumn();
    $kpis['income']  = (float)$db->query("SELECT COALESCE(SUM(amount),0) FROM transactions WHERE type='income'")->fetchColumn();
    $kpis['expense'] = (float)$db->query("SELECT COALESCE(SUM(amount),0) FROM transactions WHERE type='expense'")->fetchColumn();
    $kpis['today']   = (int)$db->query("SELECT COUNT(*) FROM transactions WHERE DATE(transaction_date)=CURDATE()")->fetchColumn();
    $kpis['this_month'] = (int)$db->query("SELECT COUNT(*) FROM transactions WHERE MONTH(transaction_date)=MONTH(NOW()) AND YEAR(transaction_date)=YEAR(NOW())")->fetchColumn();
    $kpis['avg']     = (float)$db->query("SELECT COALESCE(AVG(amount),0) FROM transactions")->fetchColumn();
} catch(Exception $e){ $kpis = array_fill_keys(['total','income','expense','today','this_month','avg'],0); }
?>

<!-- KPIs -->
<div style="display:grid;grid-template-columns:repeat(6,1fr);gap:12px;margin-bottom:20px;">
    <div class="adm-kpi" style="--kpi-color:var(--cyan);--kpi-bg:var(--cyan-dim);padding:14px;">
        <div class="adm-kpi-icon" style="width:36px;height:36px;font-size:0.9rem;"><i class="fa-solid fa-arrows-left-right-to-line"></i></div>
        <div class="adm-kpi-content"><div class="adm-kpi-value" style="font-size:1.2rem;"><?php echo number_format($kpis['total']); ?></div><div class="adm-kpi-label">Toplam İşlem</div></div>
    </div>
    <div class="adm-kpi" style="--kpi-color:var(--green);--kpi-bg:var(--green-dim);padding:14px;">
        <div class="adm-kpi-icon" style="width:36px;height:36px;font-size:0.9rem;"><i class="fa-solid fa-arrow-trend-up"></i></div>
        <div class="adm-kpi-content"><div class="adm-kpi-value" style="font-size:1rem;">₺<?php echo number_format($kpis['income'],0,',','.'); ?></div><div class="adm-kpi-label">Toplam Gelir</div></div>
    </div>
    <div class="adm-kpi" style="--kpi-color:var(--orange);--kpi-bg:var(--orange-dim);padding:14px;">
        <div class="adm-kpi-icon" style="width:36px;height:36px;font-size:0.9rem;"><i class="fa-solid fa-arrow-trend-down"></i></div>
        <div class="adm-kpi-content"><div class="adm-kpi-value" style="font-size:1rem;">₺<?php echo number_format($kpis['expense'],0,',','.'); ?></div><div class="adm-kpi-label">Toplam Gider</div></div>
    </div>
    <div class="adm-kpi" style="--kpi-color:var(--purple);--kpi-bg:var(--purple-dim);padding:14px;">
        <div class="adm-kpi-icon" style="width:36px;height:36px;font-size:0.9rem;"><i class="fa-solid fa-scale-balanced"></i></div>
        <div class="adm-kpi-content"><div class="adm-kpi-value" style="font-size:1rem;">₺<?php echo number_format($kpis['income']-$kpis['expense'],0,',','.'); ?></div><div class="adm-kpi-label">Net Bilanço</div></div>
    </div>
    <div class="adm-kpi" style="--kpi-color:#74b9ff;--kpi-bg:var(--blue-dim);padding:14px;">
        <div class="adm-kpi-icon" style="width:36px;height:36px;font-size:0.9rem;"><i class="fa-solid fa-calendar-day"></i></div>
        <div class="adm-kpi-content"><div class="adm-kpi-value" style="font-size:1.2rem;"><?php echo $kpis['today']; ?></div><div class="adm-kpi-label">Bugünkü</div></div>
    </div>
    <div class="adm-kpi" style="--kpi-color:var(--cyan);--kpi-bg:var(--cyan-dim);padding:14px;">
        <div class="adm-kpi-icon" style="width:36px;height:36px;font-size:0.9rem;"><i class="fa-solid fa-calculator"></i></div>
        <div class="adm-kpi-content"><div class="adm-kpi-value" style="font-size:1rem;">₺<?php echo number_format($kpis['avg'],2,',','.'); ?></div><div class="adm-kpi-label">Ortalama İşlem</div></div>
    </div>
</div>

<!-- Charts row -->
<div style="display:grid;grid-template-columns:2fr 1fr;gap:16px;margin-bottom:18px;">
    <div class="adm-card">
        <div class="adm-card-header">
            <div>
                <div class="adm-card-title"><i class="fa-solid fa-chart-area"></i> Aylık Gelir / Gider Trendi</div>
                <div class="adm-card-subtitle">Son 12 ay karşılaştırma</div>
            </div>
        </div>
        <div style="height:220px;"><canvas id="txMonthlyChart"></canvas></div>
    </div>
    <div class="adm-card">
        <div class="adm-card-header">
            <div class="adm-card-title"><i class="fa-solid fa-chart-bar"></i> Kategori Dağılımı</div>
        </div>
        <div style="height:220px;"><canvas id="txCatChart"></canvas></div>
    </div>
</div>

<!-- Filter bar -->
<div class="adm-table-wrap" id="txTableWrap">
    <div class="adm-table-toolbar" style="flex-wrap:wrap;gap:8px;">
        <div class="adm-search-wrap" style="max-width:260px;flex:1;">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" class="adm-input adm-btn-sm" id="txSearch" placeholder="Açıklama, kategori...">
        </div>
        <select class="adm-select adm-btn-sm" id="txTypeFilter" style="width:auto;">
            <option value="">Tüm Türler</option>
            <option value="income">Gelir</option>
            <option value="expense">Gider</option>
        </select>
        <select class="adm-select adm-btn-sm" id="txCatFilter" style="width:auto;min-width:130px;">
            <option value="">Tüm Kategoriler</option>
            <?php
            try{
                $cats2=$db->query("SELECT DISTINCT category FROM transactions ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);
                foreach($cats2 as $c) echo '<option value="'.htmlspecialchars($c).'">'.htmlspecialchars($c).'</option>';
            } catch(Exception $e){}
            ?>
        </select>
        <input type="date" class="adm-input adm-btn-sm" id="txDateFrom" style="width:140px;">
        <input type="date" class="adm-input adm-btn-sm" id="txDateTo"   style="width:140px;">
        <button class="adm-btn adm-btn-ghost adm-btn-sm" id="txResetBtn"><i class="fa-solid fa-rotate-left"></i></button>
        <div style="display:flex;gap:6px;margin-left:auto;">
            <span id="txSelInfo" style="font-size:0.8rem;color:var(--text-muted);display:flex;align-items:center;"></span>
            <button class="adm-btn adm-btn-danger adm-btn-sm" id="txBulkDeleteBtn" style="display:none;"><i class="fa-solid fa-trash"></i> Seçilenleri Sil</button>
        </div>
    </div>

    <div style="overflow-x:auto;">
        <table class="adm-table">
            <thead>
                <tr>
                    <th style="width:40px;"><input type="checkbox" id="txSelectAll" style="accent-color:var(--cyan);"></th>
                    <th>Kullanıcı</th>
                    <th>Açıklama</th>
                    <th>Tür</th>
                    <th>Kategori</th>
                    <th style="text-align:right;">Tutar</th>
                    <th>Tarih</th>
                    <th style="width:60px;"></th>
                </tr>
            </thead>
            <tbody id="txTableBody">
                <tr><td colspan="8" style="text-align:center;padding:40px;color:var(--text-muted);"><i class="fa-solid fa-spinner fa-spin"></i></td></tr>
            </tbody>
        </table>
    </div>

    <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 20px;border-top:1px solid var(--border);flex-wrap:wrap;gap:8px;">
        <span id="txPageInfo" style="font-size:0.8rem;color:var(--text-muted);"></span>
        <div style="display:flex;gap:6px;" id="txPaginBtns"></div>
    </div>
</div>

<script>
const TX_API = 'api/admin_transactions.php';
const AN_API = 'api/admin_analytics.php';
let txOffset=0; const TX_PAGE=25;
let txSearch='', txType='', txCat='', txFrom='', txTo='';
let txSelected = new Set();

// Load charts once
(async()=>{
    const [monthly, cat] = await Promise.all([
        fetch(`${AN_API}?action=chart_monthly&months=12`).then(r=>r.json()),
        fetch(`${AN_API}?action=chart_categories`).then(r=>r.json())
    ]);
    if(monthly.success){
        new Chart(document.getElementById('txMonthlyChart'),{
            type:'line',
            data:{labels:monthly.rows.map(r=>r.m),datasets:[
                {label:'Gelir',data:monthly.rows.map(r=>parseFloat(r.income)),borderColor:'#39ff14',backgroundColor:'rgba(57,255,20,0.06)',fill:true,tension:0.4,borderWidth:2,pointRadius:3},
                {label:'Gider',data:monthly.rows.map(r=>parseFloat(r.expense)),borderColor:'#ff9100',backgroundColor:'rgba(255,145,0,0.06)',fill:true,tension:0.4,borderWidth:2,pointRadius:3},
            ]},
            options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'top'}},scales:{y:{beginAtZero:true,grid:{color:'rgba(255,255,255,0.04)'},ticks:{callback:v=>'₺'+fmtK(v)}},x:{grid:{display:false}}}}
        });
    }
    if(cat.success && cat.rows.length){
        const cols=['#00f0ff','#39ff14','#a29bfe','#ff9100','#ff3b30','#0088ff','#fdcb6e','#fd79a8','#55efc4','#e17055'];
        new Chart(document.getElementById('txCatChart'),{
            type:'doughnut',
            data:{labels:cat.rows.map(r=>r.category),datasets:[{data:cat.rows.map(r=>parseFloat(r.total)),backgroundColor:cols,borderWidth:2,borderColor:'rgba(8,12,24,0.8)'}]},
            options:{responsive:true,maintainAspectRatio:false,cutout:'60%',plugins:{legend:{position:'bottom',labels:{font:{size:10},boxWidth:8}}}}
        });
    }
})();

async function loadTx(){
    const params=new URLSearchParams({action:'list',search:txSearch,type:txType,category:txCat,date_from:txFrom,date_to:txTo,limit:TX_PAGE,offset:txOffset});
    const d=await fetch(`${TX_API}?${params}`).then(r=>r.json()).catch(()=>({rows:[],total:0}));
    renderTx(d.rows||[],d.total||0);
}

function renderTx(rows,total){
    const tbody=document.getElementById('txTableBody');
    if(!rows.length){tbody.innerHTML='<tr><td colspan="8"><div class="adm-empty"><i class="fa-solid fa-inbox"></i><p>İşlem bulunamadı</p></div></td></tr>';return;}
    tbody.innerHTML=rows.map(r=>{
        const isIncome=r.type==='income';
        const chk=txSelected.has(String(r.id))?'checked':'';
        return `<tr>
            <td><input type="checkbox" class="tx-check" data-id="${r.id}" ${chk} style="accent-color:var(--cyan);"></td>
            <td>
                <div class="adm-user-cell">
                    <div class="adm-avatar" style="width:28px;height:28px;font-size:0.7rem;">${(r.full_name||'?')[0].toUpperCase()}</div>
                    <div>
                        <div style="font-size:0.82rem;font-weight:600;">${esc(r.full_name||'—')}</div>
                        <div style="font-size:0.72rem;color:var(--text-muted);">${esc(r.email||'')}</div>
                    </div>
                </div>
            </td>
            <td style="max-width:180px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;color:var(--text-secondary);font-size:0.83rem;">${esc(r.description||'—')}</td>
            <td><span class="adm-badge ${isIncome?'green':'orange'}">${isIncome?'Gelir':'Gider'}</span></td>
            <td><span class="adm-badge gray" style="font-size:0.73rem;">${esc(r.category||'—')}</span></td>
            <td style="text-align:right;font-family:'JetBrains Mono',monospace;font-weight:700;font-size:0.88rem;color:${isIncome?'var(--green)':'var(--orange)'};">${isIncome?'+':'-'}₺${Number(r.amount).toLocaleString('tr-TR',{minimumFractionDigits:2})}</td>
            <td style="font-size:0.78rem;color:var(--text-muted);">${fmtDate(r.transaction_date)}</td>
            <td><button class="adm-btn adm-btn-danger adm-btn-icon adm-btn-sm" onclick="deleteTx(${r.id})"><i class="fa-solid fa-trash"></i></button></td>
        </tr>`;
    }).join('');

    const pages=Math.ceil(total/TX_PAGE),cur=Math.floor(txOffset/TX_PAGE);
    document.getElementById('txPageInfo').textContent=`${txOffset+1}–${Math.min(txOffset+TX_PAGE,total)} / ${total} işlem`;
    const btns=document.getElementById('txPaginBtns'); btns.innerHTML='';
    if(cur>0){const b=mkBtn('adm-btn adm-btn-ghost adm-btn-sm','<i class="fa-solid fa-chevron-left"></i>',()=>{txOffset-=TX_PAGE;loadTx();});btns.appendChild(b);}
    for(let p=Math.max(0,cur-2);p<=Math.min(pages-1,cur+2);p++){const b=mkBtn(`adm-btn adm-btn-sm ${p===cur?'adm-btn-primary':'adm-btn-ghost'}`,p+1,()=>{txOffset=p*TX_PAGE;loadTx();});btns.appendChild(b);}
    if(cur<pages-1){const b=mkBtn('adm-btn adm-btn-ghost adm-btn-sm','<i class="fa-solid fa-chevron-right"></i>',()=>{txOffset+=TX_PAGE;loadTx();});btns.appendChild(b);}

    document.querySelectorAll('.tx-check').forEach(cb=>{
        cb.addEventListener('change',()=>{cb.checked?txSelected.add(cb.dataset.id):txSelected.delete(cb.dataset.id);updateTxBulk();});
    });
}

function mkBtn(cls,html,cb){const b=document.createElement('button');b.className=cls;b.innerHTML=html;b.onclick=cb;return b;}
function updateTxBulk(){
    const cnt=txSelected.size;
    document.getElementById('txSelInfo').textContent=cnt>0?`${cnt} seçili`:'';
    document.getElementById('txBulkDeleteBtn').style.display=cnt>0?'':'none';
}

document.getElementById('txSelectAll').addEventListener('change',e=>{
    document.querySelectorAll('.tx-check').forEach(cb=>{cb.checked=e.target.checked;e.target.checked?txSelected.add(cb.dataset.id):txSelected.delete(cb.dataset.id);});
    updateTxBulk();
});

async function deleteTx(id){
    admConfirm('İşlemi Sil','Bu işlemi silmek istediğinize emin misiniz?',async()=>{
        const d=await admFetch(TX_API,{action:'delete',id});
        if(d.success){admToast('success','Silindi');txSelected.delete(String(id));loadTx();}else admToast('error','Hata',d.message);
    },'Sil','fa-trash');
}

document.getElementById('txBulkDeleteBtn').addEventListener('click',()=>{
    admConfirm('Toplu Sil',`${txSelected.size} işlemi silmek istediğinize emin misiniz?`,async()=>{
        for(const id of [...txSelected]) await admFetch(TX_API,{action:'delete',id});
        admToast('success',`${txSelected.size} işlem silindi`);txSelected.clear();updateTxBulk();loadTx();
    },'Sil','fa-trash');
});

// Filters
let txTimer;
document.getElementById('txSearch').addEventListener('input',e=>{clearTimeout(txTimer);txTimer=setTimeout(()=>{txSearch=e.target.value;txOffset=0;loadTx();},300);});
document.getElementById('txTypeFilter').addEventListener('change',e=>{txType=e.target.value;txOffset=0;loadTx();});
document.getElementById('txCatFilter').addEventListener('change',e=>{txCat=e.target.value;txOffset=0;loadTx();});
document.getElementById('txDateFrom').addEventListener('change',e=>{txFrom=e.target.value;txOffset=0;loadTx();});
document.getElementById('txDateTo').addEventListener('change',e=>{txTo=e.target.value;txOffset=0;loadTx();});
document.getElementById('txResetBtn').addEventListener('click',()=>{
    ['txSearch','txTypeFilter','txCatFilter','txDateFrom','txDateTo'].forEach(id=>document.getElementById(id).value='');
    [txSearch,txType,txCat,txFrom,txTo]=Array(5).fill(''); txOffset=0; loadTx();
});

function esc(s){const d=document.createElement('div');d.textContent=s||'';return d.innerHTML;}
function fmtDate(s){return s?new Date(s).toLocaleDateString('tr-TR'):'—';}
function fmtK(n){n=parseFloat(n)||0;if(n>=1000000)return(n/1e6).toFixed(1)+'M';if(n>=1000)return(n/1e3).toFixed(0)+'K';return Math.round(n);}

loadTx();
</script>

<?php require_once 'footer.php'; ?>
