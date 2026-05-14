<?php
$PAGE_TITLE = 'Bildirim Yönetimi';
require_once 'header.php';
require_once 'api/database.php';
ensureTables(); $db = getDB();

$totalNotifs = (int)$db->query("SELECT COUNT(*) FROM notifications")->fetchColumn();
$unread      = (int)$db->query("SELECT COUNT(*) FROM notifications WHERE is_read=0")->fetchColumn();
$users = $db->query("SELECT id, full_name, email FROM users ORDER BY full_name")->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- KPIs -->
<div class="adm-kpi-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:20px;">
    <div class="adm-kpi" style="--kpi-color:var(--cyan);--kpi-bg:var(--cyan-dim);">
        <div class="adm-kpi-icon"><i class="fa-solid fa-bell"></i></div>
        <div class="adm-kpi-content"><div class="adm-kpi-value"><?php echo number_format($totalNotifs); ?></div><div class="adm-kpi-label">Toplam Bildirim</div></div>
    </div>
    <div class="adm-kpi" style="--kpi-color:var(--orange);--kpi-bg:var(--orange-dim);">
        <div class="adm-kpi-icon"><i class="fa-solid fa-envelope"></i></div>
        <div class="adm-kpi-content"><div class="adm-kpi-value"><?php echo $unread; ?></div><div class="adm-kpi-label">Okunmamış</div></div>
    </div>
    <div class="adm-kpi" style="--kpi-color:var(--green);--kpi-bg:var(--green-dim);">
        <div class="adm-kpi-icon"><i class="fa-solid fa-envelope-open"></i></div>
        <div class="adm-kpi-content"><div class="adm-kpi-value"><?php echo $totalNotifs-$unread; ?></div><div class="adm-kpi-label">Okunmuş</div></div>
    </div>
    <div class="adm-kpi" style="--kpi-color:var(--purple);--kpi-bg:var(--purple-dim);">
        <div class="adm-kpi-icon"><i class="fa-solid fa-users"></i></div>
        <div class="adm-kpi-content"><div class="adm-kpi-value"><?php echo count($users); ?></div><div class="adm-kpi-label">Toplam Üye</div></div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 340px;gap:16px;align-items:start;">

    <!-- Notifications list -->
    <div class="adm-table-wrap">
        <div class="adm-table-toolbar">
            <div class="adm-search-wrap" style="max-width:280px;">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" class="adm-input adm-btn-sm" id="notifSearch" placeholder="Başlık, mesaj ara...">
            </div>
            <div style="display:flex;gap:6px;">
                <button class="adm-btn adm-btn-ghost adm-btn-sm" id="markAllReadBtn"><i class="fa-solid fa-check-double"></i> Tümünü Okundu İşaretle</button>
                <button class="adm-btn adm-btn-danger adm-btn-sm" id="clearAllNotifBtn"><i class="fa-solid fa-trash"></i> Tümünü Sil</button>
            </div>
        </div>
        <div style="overflow-x:auto;">
            <table class="adm-table">
                <thead><tr><th>Tür</th><th>Alıcı</th><th>Başlık</th><th>Mesaj</th><th style="text-align:center;">Durum</th><th>Tarih</th><th></th></tr></thead>
                <tbody id="notifBody">
                    <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--text-muted);"><i class="fa-solid fa-spinner fa-spin"></i></td></tr>
                </tbody>
            </table>
        </div>
        <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 20px;border-top:1px solid var(--border);">
            <span id="notifPageInfo" style="font-size:0.8rem;color:var(--text-muted);"></span>
            <div id="notifPaginBtns" style="display:flex;gap:6px;"></div>
        </div>
    </div>

    <!-- Send Form -->
    <div class="adm-card" style="position:sticky;top:80px;">
        <div class="adm-card-header">
            <div class="adm-card-title"><i class="fa-solid fa-paper-plane"></i> Bildirim Gönder</div>
        </div>
        <div class="adm-form-group">
            <label class="adm-form-label">Alıcı</label>
            <select class="adm-select" id="nfUserId">
                <option value="0">🔔 Tüm Kullanıcılara Gönder</option>
                <?php foreach($users as $u): ?>
                <option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['full_name']); ?> &lt;<?php echo htmlspecialchars($u['email']); ?>&gt;</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="adm-form-group">
            <label class="adm-form-label">Tür</label>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px;">
                <?php foreach(['info'=>['Bilgi','#74b9ff'],'success'=>['Başarı','#39ff14'],'warning'=>['Uyarı','#ff9100'],'error'=>['Hata','#ff3b30']] as $v=>[$l,$c]): ?>
                <label style="display:flex;align-items:center;gap:6px;padding:8px 10px;border:1px solid var(--border);border-radius:var(--radius-sm);cursor:pointer;font-size:0.82rem;transition:var(--transition);" class="notif-type-label">
                    <input type="radio" name="nfType" value="<?php echo $v; ?>" style="accent-color:<?php echo $c; ?>;" <?php echo $v==='info'?'checked':''; ?>>
                    <span style="color:<?php echo $c; ?>;font-weight:600;"><?php echo $l; ?></span>
                </label>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="adm-form-group">
            <label class="adm-form-label">Başlık <span>*</span></label>
            <input type="text" class="adm-input" id="nfTitle" placeholder="Bildirim başlığı...">
        </div>
        <div class="adm-form-group">
            <label class="adm-form-label">Mesaj</label>
            <textarea class="adm-textarea" id="nfMsg" rows="3" placeholder="Bildirim içeriği (opsiyonel)..."></textarea>
        </div>
        <div style="display:flex;gap:8px;">
            <button class="adm-btn adm-btn-ghost" style="flex:1;" onclick="document.getElementById('nfTitle').value='';document.getElementById('nfMsg').value='';">Temizle</button>
            <button class="adm-btn adm-btn-primary" style="flex:2;" id="sendNotifBtn"><i class="fa-solid fa-paper-plane"></i> Gönder</button>
        </div>
        <!-- Quick Templates -->
        <div style="margin-top:14px;border-top:1px solid var(--border);padding-top:14px;">
            <div style="font-size:0.75rem;color:var(--text-muted);margin-bottom:8px;font-weight:600;text-transform:uppercase;letter-spacing:1px;">Hızlı Şablonlar</div>
            <?php $templates=[
                ['✅ Hoş Geldiniz!','Ünistudent platformuna hoş geldiniz! Bütçenizi takip etmeye ve tasarruf etmeye başlayın.','success'],
                ['🔔 Sistem Bakımı','Platform bakım çalışmaları nedeniyle kısa süreliğine hizmet kesintisi yaşanabilir.','warning'],
                ['🎯 Yeni Challenge!','Yeni bir tasarruf challenge\'ı başladı. Hemen katılın ve XP kazanın!','info'],
            ]; foreach($templates as [$t,$m,$type]): ?>
            <button class="adm-btn adm-btn-ghost adm-btn-sm" style="width:100%;justify-content:flex-start;margin-bottom:4px;font-size:0.78rem;"
                onclick="document.getElementById('nfTitle').value=<?php echo json_encode($t); ?>;document.getElementById('nfMsg').value=<?php echo json_encode($m); ?>;document.querySelector('[name=nfType][value=<?php echo $type;?>]').checked=true;">
                <?php echo htmlspecialchars($t); ?>
            </button>
            <?php endforeach; ?>
        </div>
    </div>

</div>

<script>
const NF_API = 'api/admin_analytics.php';
let nfOffset=0; const NF_PAGE=30;
let nfSearch='';

async function loadNotifs(){
    const d = await fetch(`api/admin_notifications.php?action=list&search=${nfSearch}&limit=${NF_PAGE}&offset=${nfOffset}`).then(r=>r.json()).catch(()=>({rows:[],total:0}));
    renderNotifs(d.rows||[], d.total||0);
}

function renderNotifs(rows,total){
    const tbody=document.getElementById('notifBody');
    if(!rows.length){tbody.innerHTML='<tr><td colspan="7" style="text-align:center;padding:50px;color:var(--text-muted);">Bildirim bulunamadı</td></tr>';return;}
    const typeColors={info:'blue',success:'green',warning:'orange',error:'red'};
    const typeLabels={info:'Bilgi',success:'Başarı',warning:'Uyarı',error:'Hata'};
    tbody.innerHTML=rows.map(r=>`<tr style="${!r.is_read?'background:rgba(0,240,255,0.02);':''}">
        <td><span class="adm-badge ${typeColors[r.type]||'gray'}">${typeLabels[r.type]||r.type}</span></td>
        <td style="font-size:0.8rem;">${esc(r.full_name||'Tüm Kullanıcılar')}</td>
        <td style="font-weight:600;font-size:0.85rem;max-width:150px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${esc(r.title)}</td>
        <td style="font-size:0.78rem;color:var(--text-secondary);max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${esc(r.message||'—')}</td>
        <td style="text-align:center;"><span class="adm-badge ${r.is_read?'gray':'cyan'}">${r.is_read?'Okundu':'Yeni'}</span></td>
        <td style="font-size:0.77rem;color:var(--text-muted);">${new Date(r.created_at).toLocaleDateString('tr-TR')}</td>
        <td><button class="adm-btn adm-btn-danger adm-btn-icon adm-btn-sm" onclick="deleteNotif(${r.id})"><i class="fa-solid fa-trash"></i></button></td>
    </tr>`).join('');

    const pages=Math.ceil(total/NF_PAGE),cur=Math.floor(nfOffset/NF_PAGE);
    document.getElementById('notifPageInfo').textContent=`${nfOffset+1}–${Math.min(nfOffset+NF_PAGE,total)} / ${total}`;
    const btns=document.getElementById('notifPaginBtns'); btns.innerHTML='';
    if(cur>0){const b=document.createElement('button');b.className='adm-btn adm-btn-ghost adm-btn-sm';b.innerHTML='<i class="fa-solid fa-chevron-left"></i>';b.onclick=()=>{nfOffset-=NF_PAGE;loadNotifs();};btns.appendChild(b);}
    for(let p=Math.max(0,cur-2);p<=Math.min(pages-1,cur+2);p++){const b=document.createElement('button');b.className=`adm-btn adm-btn-sm ${p===cur?'adm-btn-primary':'adm-btn-ghost'}`;b.textContent=p+1;b.onclick=()=>{nfOffset=p*NF_PAGE;loadNotifs();};btns.appendChild(b);}
    if(cur<pages-1){const b=document.createElement('button');b.className='adm-btn adm-btn-ghost adm-btn-sm';b.innerHTML='<i class="fa-solid fa-chevron-right"></i>';b.onclick=()=>{nfOffset+=NF_PAGE;loadNotifs();};btns.appendChild(b);}
}

document.getElementById('sendNotifBtn').addEventListener('click', async()=>{
    const title=document.getElementById('nfTitle').value.trim();
    if(!title){admToast('error','Hata','Başlık boş olamaz.');return;}
    const d=await admFetch(NF_API,{
        action:'send_notification',
        user_id:document.getElementById('nfUserId').value,
        type: document.querySelector('[name=nfType]:checked')?.value||'info',
        title,
        message:document.getElementById('nfMsg').value,
    });
    if(d.success){admToast('success','Gönderildi',d.message);document.getElementById('nfTitle').value='';document.getElementById('nfMsg').value='';loadNotifs();}
    else admToast('error','Hata',d.message);
});

async function deleteNotif(id){
    const d=await admFetch('api/admin_notifications.php',{action:'delete',id});
    if(d.success){admToast('success','Silindi');loadNotifs();}else admToast('error','Hata',d.message);
}

document.getElementById('markAllReadBtn').addEventListener('click',async()=>{
    const d=await admFetch('api/admin_notifications.php',{action:'mark_all_read'});
    if(d.success){admToast('success','Tümü okundu işaretlendi');loadNotifs();}else admToast('error','Hata',d.message);
});
document.getElementById('clearAllNotifBtn').addEventListener('click',()=>admConfirm('Tümünü Sil','Tüm bildirimleri silmek istediğinize emin misiniz?',async()=>{
    const d=await admFetch('api/admin_notifications.php',{action:'clear_all'});
    if(d.success){admToast('success','Tümü silindi');loadNotifs();}else admToast('error','Hata',d.message);
},'Sil','fa-trash'));

let nfTimer;
document.getElementById('notifSearch').addEventListener('input',e=>{clearTimeout(nfTimer);nfTimer=setTimeout(()=>{nfSearch=e.target.value;nfOffset=0;loadNotifs();},300);});
function esc(s){const d=document.createElement('div');d.textContent=s||'';return d.innerHTML;}
loadNotifs();
</script>

<?php require_once 'footer.php'; ?>
