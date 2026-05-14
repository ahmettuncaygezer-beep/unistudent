<?php
$PAGE_TITLE = 'Abonelik Yönetimi';
require_once 'header.php';
require_once 'api/database.php';
ensureTables(); $db = getDB();

$totalSubs   = (int)$db->query("SELECT COUNT(*) FROM subscriptions WHERE is_active=1")->fetchColumn();
$monthlyTotal= (float)$db->query("SELECT COALESCE(SUM(CASE WHEN billing_cycle='monthly' THEN amount WHEN billing_cycle='yearly' THEN amount/12 ELSE 0 END),0) FROM subscriptions WHERE is_active=1")->fetchColumn();
$topService  = $db->query("SELECT name, COUNT(*) as cnt FROM subscriptions WHERE is_active=1 GROUP BY name ORDER BY cnt DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
?>

<!-- KPIs -->
<div class="adm-kpi-grid" style="grid-template-columns:repeat(3,1fr); margin-bottom:24px;">
    <div class="adm-kpi" style="--kpi-color:var(--cyan);--kpi-bg:var(--cyan-dim);">
        <div class="adm-kpi-icon"><i class="fa-solid fa-rotate"></i></div>
        <div class="adm-kpi-content"><div class="adm-kpi-value"><?php echo number_format($totalSubs); ?></div><div class="adm-kpi-label">Aktif Abonelik</div></div>
    </div>
    <div class="adm-kpi" style="--kpi-color:var(--orange);--kpi-bg:var(--orange-dim);">
        <div class="adm-kpi-icon"><i class="fa-solid fa-turkish-lira-sign"></i></div>
        <div class="adm-kpi-content"><div class="adm-kpi-value" style="font-size:1.2rem;">₺<?php echo number_format($monthlyTotal,0,',','.'); ?></div><div class="adm-kpi-label">Toplam Aylık (tüm üyeler)</div></div>
    </div>
    <div class="adm-kpi" style="--kpi-color:var(--purple);--kpi-bg:var(--purple-dim);">
        <div class="adm-kpi-icon"><i class="fa-solid fa-star"></i></div>
        <div class="adm-kpi-content">
            <div class="adm-kpi-value" style="font-size:1rem;"><?php echo htmlspecialchars($topService['name']??'—'); ?></div>
            <div class="adm-kpi-label">En Popüler Servis <?php if($topService): ?>(<?php echo $topService['cnt']; ?> üye)<?php endif; ?></div>
        </div>
    </div>
</div>

<div class="adm-table-wrap">
    <div class="adm-table-toolbar">
        <div class="adm-search-wrap" style="max-width:280px;">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" class="adm-input adm-btn-sm" id="subSearch" placeholder="Servis adı, üye adı...">
        </div>
        <select class="adm-select adm-btn-sm" id="subStatus" style="width:auto;min-width:140px;">
            <option value="all">Tüm Durumlar</option>
            <option value="1">Aktif</option>
            <option value="0">Pasif</option>
        </select>
    </div>
    <div style="overflow-x:auto;">
        <table class="adm-table">
            <thead><tr>
                <th>Üye</th><th>Servis</th><th>Kategori</th><th style="text-align:center;">Döngü</th>
                <th style="text-align:right;">Tutar / Ay</th><th style="text-align:center;">Sonraki Ödeme</th><th style="text-align:center;">Durum</th>
            </tr></thead>
            <tbody id="subBody"><tr><td colspan="7" style="text-align:center;padding:40px;color:var(--text-muted);"><i class="fa-solid fa-spinner fa-spin"></i></td></tr></tbody>
        </table>
    </div>
    <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 20px;border-top:1px solid var(--border);flex-wrap:wrap;gap:10px;">
        <span id="subPageInfo" style="font-size:0.8rem;color:var(--text-muted);"></span>
        <div id="subPaginBtns" style="display:flex;gap:6px;"></div>
    </div>
</div>

<script>
let subOffset = 0; const SUB_PAGE = 30;
let subStatus = 'all', subSearch = '';

async function loadSubs(){
    const params = new URLSearchParams({action:'list',status:subStatus,search:subSearch,limit:SUB_PAGE,offset:subOffset});
    const data = await fetch(`api/admin_subscriptions.php?${params}`).then(r=>r.json());
    renderSubs(data.subscriptions||[], data.total||0);
}

function renderSubs(rows, total){
    const tbody = document.getElementById('subBody');
    if(!rows.length){tbody.innerHTML='<tr><td colspan="7" style="text-align:center;padding:50px;color:var(--text-muted);">Abonelik bulunamadı</td></tr>';return;}
    tbody.innerHTML = rows.map(s=>`
        <tr>
            <td>
                <div class="adm-user-cell">
                    <div class="adm-avatar">${(s.full_name||'?')[0].toUpperCase()}</div>
                    <div><div class="adm-user-name">${esc(s.full_name||'—')}</div><div class="adm-user-email">${esc(s.email||'')}</div></div>
                </div>
            </td>
            <td style="font-weight:600;">${esc(s.name)}</td>
            <td><span class="adm-badge gray">${esc(s.category||'—')}</span></td>
            <td style="text-align:center;"><span class="adm-badge ${s.billing_cycle==='yearly'?'purple':'cyan'}">${s.billing_cycle==='yearly'?'Yıllık':'Aylık'}</span></td>
            <td style="text-align:right;font-family:'JetBrains Mono',monospace;font-weight:700;color:var(--orange);">
                ₺${Number(s.billing_cycle==='yearly'?s.amount/12:s.amount).toLocaleString('tr-TR',{minimumFractionDigits:2})}
            </td>
            <td style="text-align:center;font-size:0.8rem;color:var(--text-muted);">${s.next_billing||'—'}</td>
            <td style="text-align:center;"><span class="adm-badge ${s.is_active?'green':'gray'}">${s.is_active?'Aktif':'Pasif'}</span></td>
        </tr>`).join('');

    const pages = Math.ceil(total/SUB_PAGE);
    const cur   = Math.floor(subOffset/SUB_PAGE);
    document.getElementById('subPageInfo').textContent = `${subOffset+1}–${Math.min(subOffset+SUB_PAGE,total)} / ${total} kayıt`;
    const btns = document.getElementById('subPaginBtns');
    btns.innerHTML='';
    if(cur>0){const b=document.createElement('button');b.className='adm-btn adm-btn-ghost adm-btn-sm';b.innerHTML='<i class="fa-solid fa-chevron-left"></i>';b.onclick=()=>{subOffset-=SUB_PAGE;loadSubs();};btns.appendChild(b);}
    for(let p=Math.max(0,cur-2);p<=Math.min(pages-1,cur+2);p++){const b=document.createElement('button');b.className=`adm-btn adm-btn-sm ${p===cur?'adm-btn-primary':'adm-btn-ghost'}`;b.textContent=p+1;b.onclick=()=>{subOffset=p*SUB_PAGE;loadSubs();};btns.appendChild(b);}
    if(cur<pages-1){const b=document.createElement('button');b.className='adm-btn adm-btn-ghost adm-btn-sm';b.innerHTML='<i class="fa-solid fa-chevron-right"></i>';b.onclick=()=>{subOffset+=SUB_PAGE;loadSubs();};btns.appendChild(b);}
}

let subTimer;
document.getElementById('subSearch').addEventListener('input',e=>{clearTimeout(subTimer);subTimer=setTimeout(()=>{subSearch=e.target.value;subOffset=0;loadSubs();},350);});
document.getElementById('subStatus').addEventListener('change',e=>{subStatus=e.target.value;subOffset=0;loadSubs();});

function esc(s){const d=document.createElement('div');d.textContent=s||'';return d.innerHTML;}
loadSubs();
</script>
<?php require_once 'footer.php'; ?>
