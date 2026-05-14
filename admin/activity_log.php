<?php
$PAGE_TITLE = 'Aktivite Logu';
require_once 'header.php';
require_once 'api/database.php';
ensureTables(); $db = getDB();

// Ensure admin_audit_log exists
try {
    $db->query("CREATE TABLE IF NOT EXISTS admin_audit_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        action VARCHAR(100), target_type VARCHAR(50),
        target_id INT, detail TEXT,
        ip_address VARCHAR(45),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
} catch(Exception $e){}
?>

<div class="adm-section-header">
    <div>
        <div class="adm-section-title">Aktivite Logu</div>
        <div class="adm-section-desc">Platform genelinde yapılan işlemlerin kayıtları</div>
    </div>
    <div class="adm-section-actions">
        <button class="adm-btn adm-btn-ghost adm-btn-sm" id="refreshLogBtn"><i class="fa-solid fa-rotate"></i> Yenile</button>
        <button class="adm-btn adm-btn-danger adm-btn-sm" id="clearLogBtn"><i class="fa-solid fa-trash"></i> Logu Temizle</button>
    </div>
</div>

<!-- Filter -->
<div style="display:flex;gap:10px;margin-bottom:16px;flex-wrap:wrap;">
    <div class="adm-search-wrap" style="max-width:280px;">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" class="adm-input adm-btn-sm" id="logSearch" placeholder="Aksiyon, kullanıcı, detay...">
    </div>
    <input type="date" class="adm-input adm-btn-sm" id="logDateFrom" style="width:140px;" placeholder="Başlangıç">
    <input type="date" class="adm-input adm-btn-sm" id="logDateTo"   style="width:140px;" placeholder="Bitiş">
    <button class="adm-btn adm-btn-ghost adm-btn-sm" id="logResetBtn"><i class="fa-solid fa-rotate-left"></i> Sıfırla</button>
</div>

<div class="adm-table-wrap" id="logTableWrap">
    <div style="overflow-x:auto;">
        <table class="adm-table">
            <thead><tr><th>#</th><th>Aksiyon</th><th>Hedef</th><th>Detay</th><th>IP Adresi</th><th>Zaman</th></tr></thead>
            <tbody id="logBody">
                <tr><td colspan="6" style="text-align:center;padding:40px;color:var(--text-muted);"><i class="fa-solid fa-spinner fa-spin"></i> Yükleniyor...</td></tr>
            </tbody>
        </table>
    </div>
    <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 20px;border-top:1px solid var(--border);">
        <span id="logPageInfo" style="font-size:0.8rem;color:var(--text-muted);"></span>
        <div id="logPaginBtns" style="display:flex;gap:6px;"></div>
    </div>
</div>

<!-- Also show user audit log -->
<div style="margin-top:24px;">
    <div class="adm-section-header">
        <div><div class="adm-section-title">Kullanıcı Aksiyonları</div><div class="adm-section-desc">Kullanıcıların platform üzerindeki hareketleri</div></div>
    </div>
    <div class="adm-table-wrap">
        <div style="overflow-x:auto;">
            <table class="adm-table" style="font-size:0.83rem;">
                <thead><tr><th>#</th><th>Kullanıcı</th><th>Aksiyon</th><th>Detay</th><th>Zaman</th></tr></thead>
                <tbody id="userLogBody">
                    <tr><td colspan="5" style="text-align:center;padding:30px;color:var(--text-muted);"><i class="fa-solid fa-spinner fa-spin"></i></td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
let logOffset=0; const LOG_PAGE=30;
let logSearch='', logDateFrom='', logDateTo='';

async function loadLog(){
    const params=new URLSearchParams({action:'list_admin_log',limit:LOG_PAGE,offset:logOffset,search:logSearch,date_from:logDateFrom,date_to:logDateTo});
    const data = await fetch(`api/admin_log.php?${params}`).then(r=>r.json()).catch(()=>({rows:[],total:0}));
    renderLog(data.rows||[],data.total||0);
}

function renderLog(rows,total){
    const tbody=document.getElementById('logBody');
    if(!rows.length){tbody.innerHTML='<tr><td colspan="6" style="text-align:center;padding:50px;color:var(--text-muted);">Log kaydı bulunamadı</td></tr>';return;}
    const actionColors={delete:'red',create:'green',update:'cyan',login:'purple',logout:'orange'};
    tbody.innerHTML=rows.map(r=>{
        const baseAction=(r.action||'').split('_')[0];
        const col=actionColors[baseAction]||'gray';
        return `<tr>
            <td class="adm-code" style="color:var(--text-muted);font-size:0.75rem;">#${r.id}</td>
            <td><span class="adm-badge ${col}" style="text-transform:uppercase;font-size:0.68rem;">${esc(r.action||'—')}</span></td>
            <td style="font-size:0.8rem;">${r.target_type?`<span class="adm-badge gray">${esc(r.target_type)}</span> <span class="adm-code" style="font-size:0.75rem;">#${r.target_id}</span>`:'—'}</td>
            <td style="font-size:0.8rem;color:var(--text-secondary);max-width:250px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${esc(r.detail||'—')}</td>
            <td class="adm-code" style="font-size:0.75rem;color:var(--text-muted);">${esc(r.ip_address||'—')}</td>
            <td style="font-size:0.77rem;color:var(--text-muted);">${new Date(r.created_at).toLocaleString('tr-TR')}</td>
        </tr>`;
    }).join('');

    const pages=Math.ceil(total/LOG_PAGE);
    const cur=Math.floor(logOffset/LOG_PAGE);
    document.getElementById('logPageInfo').textContent=`${logOffset+1}–${Math.min(logOffset+LOG_PAGE,total)} / ${total} kayıt`;
    const btns=document.getElementById('logPaginBtns');
    btns.innerHTML='';
    if(cur>0){const b=mk('adm-btn adm-btn-ghost adm-btn-sm','<i class="fa-solid fa-chevron-left"></i>',()=>{logOffset-=LOG_PAGE;loadLog();});btns.appendChild(b);}
    for(let p=Math.max(0,cur-2);p<=Math.min(pages-1,cur+2);p++){const b=mk(`adm-btn adm-btn-sm ${p===cur?'adm-btn-primary':'adm-btn-ghost'}`,p+1,()=>{logOffset=p*LOG_PAGE;loadLog();});btns.appendChild(b);}
    if(cur<pages-1){const b=mk('adm-btn adm-btn-ghost adm-btn-sm','<i class="fa-solid fa-chevron-right"></i>',()=>{logOffset+=LOG_PAGE;loadLog();});btns.appendChild(b);}
}

function mk(cls,html,cb){const b=document.createElement('button');b.className=cls;b.innerHTML=html;b.onclick=cb;return b;}

async function loadUserLog(){
    const data=await fetch('api/admin_analytics.php?action=activity_feed&limit=30').then(r=>r.json()).catch(()=>({rows:[]}));
    const tbody=document.getElementById('userLogBody');
    const rows=data.rows||[];
    if(!rows.length){tbody.innerHTML='<tr><td colspan="5" style="text-align:center;padding:30px;color:var(--text-muted);">Aktivite yok</td></tr>';return;}
    tbody.innerHTML=rows.map(r=>`<tr>
        <td class="adm-code" style="color:var(--text-muted);font-size:0.72rem;">#${r.id}</td>
        <td><div class="adm-user-cell"><div class="adm-avatar" style="width:26px;height:26px;font-size:0.68rem;">${(r.full_name||'?')[0].toUpperCase()}</div><div class="adm-user-name" style="font-size:0.82rem;">${esc(r.full_name||'—')}</div></div></td>
        <td><span class="adm-badge gray" style="font-size:0.7rem;">${esc(r.action||'—')}</span></td>
        <td style="font-size:0.78rem;color:var(--text-secondary);">${esc(r.detail||'—')}</td>
        <td style="font-size:0.75rem;color:var(--text-muted);">${new Date(r.created_at).toLocaleString('tr-TR')}</td>
    </tr>`).join('');
}

// Filters
let logTimer;
document.getElementById('logSearch').addEventListener('input',e=>{clearTimeout(logTimer);logTimer=setTimeout(()=>{logSearch=e.target.value;logOffset=0;loadLog();},300);});
['logDateFrom','logDateTo'].forEach(id=>document.getElementById(id).addEventListener('change',e=>{[logDateFrom,logDateTo]=[document.getElementById('logDateFrom').value,document.getElementById('logDateTo').value];logOffset=0;loadLog();}));
document.getElementById('logResetBtn').addEventListener('click',()=>{document.getElementById('logSearch').value='';document.getElementById('logDateFrom').value='';document.getElementById('logDateTo').value='';logSearch='';logDateFrom='';logDateTo='';logOffset=0;loadLog();});
document.getElementById('refreshLogBtn').addEventListener('click',()=>{loadLog();loadUserLog();});
document.getElementById('clearLogBtn').addEventListener('click',()=>admConfirm('Logu Temizle','Tüm admin log kayıtlarını silmek istediğinize emin misiniz?',async()=>{
    const d=await admFetch('api/admin_log.php',{action:'clear'});
    if(d.success){admToast('success','Log Temizlendi');logOffset=0;loadLog();}
    else admToast('error','Hata',d.message);
},'Temizle','fa-trash'));

function esc(s){const d=document.createElement('div');d.textContent=s||'';return d.innerHTML;}
loadLog(); loadUserLog();
</script>

<?php require_once 'footer.php'; ?>
