<?php
$PAGE_TITLE = 'Kullanıcı Yönetimi';
require_once 'header.php';
require_once 'api/database.php';
ensureTables(); $db = getDB();

// Ensure is_banned column exists
try { $db->query("ALTER TABLE users ADD COLUMN is_banned TINYINT(1) DEFAULT 0"); } catch(Exception $e){}

$totalUsers = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$banned     = (int)$db->query("SELECT COUNT(*) FROM users WHERE is_banned=1")->fetchColumn();
$admins     = (int)$db->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn();
$verified   = (int)$db->query("SELECT COUNT(*) FROM users WHERE role='verified'")->fetchColumn();
$avgXp      = (float)$db->query("SELECT AVG(xp_points) FROM users")->fetchColumn();
?>

<!-- KPIs -->
<div class="adm-kpi-grid" style="grid-template-columns:repeat(5,1fr);margin-bottom:20px;">
    <div class="adm-kpi" style="--kpi-color:var(--cyan);--kpi-bg:var(--cyan-dim);">
        <div class="adm-kpi-icon"><i class="fa-solid fa-users"></i></div>
        <div class="adm-kpi-content"><div class="adm-kpi-value"><?php echo $totalUsers; ?></div><div class="adm-kpi-label">Toplam Üye</div></div>
    </div>
    <div class="adm-kpi" style="--kpi-color:var(--green);--kpi-bg:var(--green-dim);">
        <div class="adm-kpi-icon"><i class="fa-solid fa-user-check"></i></div>
        <div class="adm-kpi-content"><div class="adm-kpi-value"><?php echo $verified; ?></div><div class="adm-kpi-label">Doğrulanmış</div></div>
    </div>
    <div class="adm-kpi" style="--kpi-color:var(--purple);--kpi-bg:var(--purple-dim);">
        <div class="adm-kpi-icon"><i class="fa-solid fa-shield-halved"></i></div>
        <div class="adm-kpi-content"><div class="adm-kpi-value"><?php echo $admins; ?></div><div class="adm-kpi-label">Yönetici</div></div>
    </div>
    <div class="adm-kpi" style="--kpi-color:var(--red);--kpi-bg:var(--red-dim);">
        <div class="adm-kpi-icon"><i class="fa-solid fa-ban"></i></div>
        <div class="adm-kpi-content"><div class="adm-kpi-value"><?php echo $banned; ?></div><div class="adm-kpi-label">Banlı</div></div>
    </div>
    <div class="adm-kpi" style="--kpi-color:var(--orange);--kpi-bg:var(--orange-dim);">
        <div class="adm-kpi-icon"><i class="fa-solid fa-star"></i></div>
        <div class="adm-kpi-content"><div class="adm-kpi-value"><?php echo number_format($avgXp,0); ?></div><div class="adm-kpi-label">Ort. XP</div></div>
    </div>
</div>

<!-- Filter Tabs -->
<div style="display:flex;gap:6px;margin-bottom:14px;flex-wrap:wrap;align-items:center;">
    <button class="adm-btn adm-btn-ghost adm-btn-sm role-filter-btn" data-role="all" style="border-color:rgba(0,240,255,0.4);color:var(--cyan);background:rgba(0,240,255,0.08);">
        Tümü <span class="adm-badge gray" style="font-size:0.65rem;padding:1px 6px;margin-left:4px;"><?php echo $totalUsers; ?></span>
    </button>
    <?php foreach(['user'=>['Standart','gray'], 'verified'=>['Doğrulanmış','green'], 'admin'=>['Yönetici','purple']] as $r=>[$l,$c]):
        $cnt = (int)$db->prepare("SELECT COUNT(*) FROM users WHERE role=?")->execute([$r]) ? 0 : 0;
        $st2 = $db->prepare("SELECT COUNT(*) FROM users WHERE role=?"); $st2->execute([$r]); $cnt=(int)$st2->fetchColumn();
    ?>
    <button class="adm-btn adm-btn-ghost adm-btn-sm role-filter-btn" data-role="<?php echo $r; ?>">
        <?php echo $l; ?> <span class="adm-badge <?php echo $c; ?>" style="font-size:0.65rem;padding:1px 6px;margin-left:4px;"><?php echo $cnt; ?></span>
    </button>
    <?php endforeach; ?>
    <button class="adm-btn adm-btn-ghost adm-btn-sm role-filter-btn" data-role="banned" style="color:var(--red);">
        🚫 Banlılar <span class="adm-badge red" style="font-size:0.65rem;padding:1px 6px;margin-left:4px;"><?php echo $banned; ?></span>
    </button>
</div>

<div class="adm-table-wrap" id="usersTableWrap">
    <div class="adm-table-toolbar" style="flex-wrap:wrap;gap:8px;">
        <div class="adm-search-wrap" style="max-width:300px;flex:1;">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" class="adm-input adm-btn-sm" id="userSearch" placeholder="İsim, e-posta, üniversite...">
        </div>
        <select class="adm-select adm-btn-sm" id="sortSelect" style="width:auto;min-width:140px;">
            <option value="created_desc">↓ En Yeni</option>
            <option value="created_asc">↑ En Eski</option>
            <option value="xp_desc">↓ Yüksek XP</option>
            <option value="tx_desc">↓ Çok İşlem</option>
        </select>
        <div style="display:flex;gap:6px;margin-left:auto;">
            <span id="selectionInfo" style="font-size:0.8rem;color:var(--text-muted);display:flex;align-items:center;"></span>
            <button class="adm-btn adm-btn-ghost adm-btn-sm" id="bulkVerifyBtn" style="display:none;"><i class="fa-solid fa-check-circle"></i> Doğrula</button>
            <button class="adm-btn adm-btn-ghost adm-btn-sm" id="bulkBanBtn" style="display:none;color:var(--red);border-color:rgba(255,59,48,0.2);"><i class="fa-solid fa-ban"></i> Banla</button>
            <button class="adm-btn adm-btn-danger adm-btn-sm" id="bulkDeleteBtn" style="display:none;"><i class="fa-solid fa-trash"></i> Sil</button>
            <button class="adm-btn adm-btn-ghost adm-btn-sm" id="exportCsvBtn"><i class="fa-solid fa-file-csv"></i> CSV</button>
        </div>
    </div>

    <div style="overflow-x:auto;">
        <table class="adm-table">
            <thead>
                <tr>
                    <th style="width:40px;"><input type="checkbox" id="selectAll" style="accent-color:var(--cyan);"></th>
                    <th>Üye</th>
                    <th>Üniversite</th>
                    <th>Rol</th>
                    <th style="text-align:center;">XP / Seviye</th>
                    <th style="text-align:right;">İşlem / Hacim</th>
                    <th>Kayıt</th>
                    <th style="text-align:right;">İşlemler</th>
                </tr>
            </thead>
            <tbody id="usersTableBody">
                <tr><td colspan="8" style="text-align:center;padding:40px;color:var(--text-muted);"><i class="fa-solid fa-spinner fa-spin"></i> Yükleniyor...</td></tr>
            </tbody>
        </table>
    </div>

    <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 20px;border-top:1px solid var(--border);flex-wrap:wrap;gap:10px;">
        <span id="paginationInfo" style="font-size:0.8rem;color:var(--text-muted);"></span>
        <div style="display:flex;gap:6px;" id="paginationBtns"></div>
    </div>
</div>

<!-- ══ USER DETAIL MODAL ══ -->
<div class="adm-modal-overlay" id="userDetailModal">
    <div class="adm-modal adm-modal-lg">
        <div class="adm-modal-header">
            <div class="adm-modal-title"><i class="fa-solid fa-user-circle"></i> Kullanıcı Profili</div>
            <button class="adm-modal-close" onclick="admCloseModal('userDetailModal')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="adm-modal-body" id="userDetailBody">
            <div style="text-align:center;padding:40px;"><i class="fa-solid fa-spinner fa-spin" style="font-size:2rem;color:var(--cyan);"></i></div>
        </div>
    </div>
</div>

<!-- ══ EDIT USER MODAL ══ -->
<div class="adm-modal-overlay" id="editUserModal">
    <div class="adm-modal">
        <div class="adm-modal-header">
            <div class="adm-modal-title"><i class="fa-solid fa-user-pen"></i> Kullanıcı Düzenle</div>
            <button class="adm-modal-close" onclick="admCloseModal('editUserModal')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="adm-modal-body">
            <input type="hidden" id="editUserId">
            <div class="adm-form-group">
                <label class="adm-form-label">Rol</label>
                <select class="adm-select" id="editRoleSelect">
                    <option value="user">Standart</option>
                    <option value="verified">Doğrulanmış</option>
                    <option value="admin">Yönetici</option>
                </select>
            </div>
            <div class="adm-form-group">
                <label class="adm-form-label">XP Puanı</label>
                <input type="number" class="adm-input" id="editXpInput" min="0" placeholder="0">
            </div>
            <div class="adm-form-group">
                <label class="adm-form-label">Admin Notu (iç kullanım)</label>
                <textarea class="adm-textarea" id="editNoteInput" rows="2" placeholder="Bu kullanıcı hakkında not..."></textarea>
            </div>
        </div>
        <div class="adm-modal-footer">
            <button class="adm-btn adm-btn-ghost" onclick="admCloseModal('editUserModal')">İptal</button>
            <button class="adm-btn adm-btn-primary" id="saveUserEditBtn"><i class="fa-solid fa-check"></i> Kaydet</button>
        </div>
    </div>
</div>

<!-- ══ SEND NOTIFICATION MODAL ══ -->
<div class="adm-modal-overlay" id="notifUserModal">
    <div class="adm-modal adm-modal-sm">
        <div class="adm-modal-header">
            <div class="adm-modal-title"><i class="fa-solid fa-bell"></i> Bildirim Gönder</div>
            <button class="adm-modal-close" onclick="admCloseModal('notifUserModal')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="adm-modal-body">
            <input type="hidden" id="notifTargetUserId">
            <div class="adm-form-group">
                <label class="adm-form-label">Tür</label>
                <select class="adm-select" id="notifType">
                    <option value="info">Bilgi</option>
                    <option value="success">Başarı</option>
                    <option value="warning">Uyarı</option>
                </select>
            </div>
            <div class="adm-form-group">
                <label class="adm-form-label">Başlık <span>*</span></label>
                <input class="adm-input" id="notifTitle" placeholder="Bildirim başlığı">
            </div>
            <div class="adm-form-group">
                <label class="adm-form-label">Mesaj</label>
                <textarea class="adm-textarea" id="notifMsg" rows="2"></textarea>
            </div>
        </div>
        <div class="adm-modal-footer">
            <button class="adm-btn adm-btn-ghost" onclick="admCloseModal('notifUserModal')">İptal</button>
            <button class="adm-btn adm-btn-primary" id="sendNotifUserBtn"><i class="fa-solid fa-paper-plane"></i> Gönder</button>
        </div>
    </div>
</div>

<script>
const API = 'api/admin_users.php';
const AN_API = 'api/admin_analytics.php';
let currentRole = 'all';
let currentSearch = '';
let currentSort = 'created_desc';
let currentOffset = 0;
const PAGE_SIZE = 20;
let selectedIds = new Set();

// ── Load Users ──
async function loadUsers() {
    const tbody = document.getElementById('usersTableBody');
    tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:30px;color:var(--text-muted);"><i class="fa-solid fa-spinner fa-spin"></i></td></tr>';

    const params = new URLSearchParams({
        action: 'list', role: currentRole, search: currentSearch,
        sort: currentSort, limit: PAGE_SIZE, offset: currentOffset
    });
    const res  = await fetch(`${API}?${params}`);
    const data = await res.json();
    renderTable(data.users || [], data.total || 0);
}

function renderTable(users, total) {
    const tbody = document.getElementById('usersTableBody');
    const roleMap = {
        verified: ['green','Doğrulanmış'],
        admin:    ['purple','Yönetici'],
        user:     ['gray','Standart']
    };

    if (!users.length) {
        tbody.innerHTML = `<tr><td colspan="8"><div class="adm-empty">
            <i class="fa-solid fa-users-slash"></i><p>Üye bulunamadı</p>
        </div></td></tr>`;
        return;
    }

    tbody.innerHTML = users.map(u => {
        const [rc, rl] = roleMap[u.role] || ['gray','?'];
        const initial = (u.full_name||'?')[0].toUpperCase();
        const checked = selectedIds.has(String(u.id)) ? 'checked' : '';
        const isBanned = u.is_banned == 1;
        const xpPct = Math.min(100, (u.xp_points || 0) / 2000 * 100);
        return `
        <tr data-id="${u.id}" class="${isBanned?'user-row-banned':''}">
            <td><input type="checkbox" class="row-check" data-id="${u.id}" ${checked} style="accent-color:var(--cyan);"></td>
            <td>
                <div class="adm-user-cell">
                    <div class="adm-avatar" style="${isBanned?'opacity:0.4;':''}position:relative;">
                        ${initial}
                        ${isBanned?'<span style="position:absolute;bottom:-2px;right:-2px;font-size:0.6rem;">🚫</span>':''}
                    </div>
                    <div>
                        <div class="adm-user-name">${esc(u.full_name)}</div>
                        <div class="adm-user-email">${esc(u.email)}</div>
                        ${u.auth_provider && u.auth_provider!=='email' ? `<span class="adm-badge gray" style="font-size:0.6rem;padding:1px 5px;margin-top:2px;">${u.auth_provider}</span>` : ''}
                    </div>
                </div>
            </td>
            <td style="color:var(--text-secondary);font-size:0.79rem;max-width:150px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${esc(u.university_name||'—')}</td>
            <td><span class="adm-badge ${rc}">${rl}</span></td>
            <td style="min-width:110px;">
                <div style="font-size:0.82rem;font-weight:700;color:var(--orange);">${Number(u.xp_points||0).toLocaleString('tr-TR')} XP</div>
                <div style="font-size:0.7rem;color:var(--text-muted);">Seviye ${u.level||1}</div>
                <div class="adm-progress" style="margin-top:4px;height:3px;">
                    <div class="adm-progress-fill" style="width:${xpPct}%;background:var(--orange);"></div>
                </div>
            </td>
            <td style="text-align:right;">
                <span class="adm-code" style="color:var(--cyan);font-size:0.8rem;">${Number(u.tx_count).toLocaleString('tr-TR')} işlem</span>
                <div style="font-size:0.72rem;color:var(--text-muted);">₺${Number(u.tx_volume||0).toLocaleString('tr-TR',{minimumFractionDigits:0})}</div>
            </td>
            <td class="adm-code" style="color:var(--text-muted);font-size:0.75rem;">${fmtDate(u.created_at)}</td>
            <td>
                <div style="display:flex;justify-content:flex-end;gap:4px;flex-wrap:nowrap;">
                    <button class="adm-btn adm-btn-ghost adm-btn-icon adm-btn-sm" title="Detay" onclick="viewUser(${u.id})"><i class="fa-solid fa-eye"></i></button>
                    <button class="adm-btn adm-btn-ghost adm-btn-icon adm-btn-sm" title="Düzenle" onclick="openEditUser(${u.id},'${u.role}',${u.xp_points||0})"><i class="fa-solid fa-pen"></i></button>
                    <button class="adm-btn adm-btn-ghost adm-btn-icon adm-btn-sm" title="Bildirim Gönder" onclick="openNotifUser(${u.id},'${esc(u.full_name)}')"><i class="fa-solid fa-bell"></i></button>
                    <button class="adm-btn adm-btn-${isBanned?'success':'ghost'} adm-btn-icon adm-btn-sm" title="${isBanned?'Banı Kaldır':'Banla'}" onclick="banUser(${u.id},${isBanned?0:1},'${esc(u.full_name)}')">
                        <i class="fa-solid fa-${isBanned?'check':'ban'}"></i>
                    </button>
                    <button class="adm-btn adm-btn-danger adm-btn-icon adm-btn-sm" title="Sil" onclick="deleteUser(${u.id},'${esc(u.full_name)}')"><i class="fa-solid fa-trash"></i></button>
                </div>
            </td>
        </tr>`;
    }).join('');

    // Pagination
    const pages = Math.ceil(total / PAGE_SIZE);
    const curPage = Math.floor(currentOffset / PAGE_SIZE);
    document.getElementById('paginationInfo').textContent = `${currentOffset+1}–${Math.min(currentOffset+PAGE_SIZE,total)} / ${total} üye`;
    const btnsEl = document.getElementById('paginationBtns');
    btnsEl.innerHTML = '';
    if(curPage>0){ const b=mk('adm-btn adm-btn-ghost adm-btn-sm','<i class="fa-solid fa-chevron-left"></i>',()=>{currentOffset-=PAGE_SIZE;loadUsers();}); btnsEl.appendChild(b); }
    for(let p=Math.max(0,curPage-2);p<=Math.min(pages-1,curPage+2);p++){ const b=mk(`adm-btn adm-btn-sm ${p===curPage?'adm-btn-primary':'adm-btn-ghost'}`,p+1,()=>{currentOffset=p*PAGE_SIZE;loadUsers();}); btnsEl.appendChild(b); }
    if(curPage<pages-1){ const b=mk('adm-btn adm-btn-ghost adm-btn-sm','<i class="fa-solid fa-chevron-right"></i>',()=>{currentOffset+=PAGE_SIZE;loadUsers();}); btnsEl.appendChild(b); }

    // Attach row checkboxes
    document.querySelectorAll('.row-check').forEach(cb => {
        cb.addEventListener('change', () => { cb.checked?selectedIds.add(cb.dataset.id):selectedIds.delete(cb.dataset.id); updateBulkUI(); });
    });
}

function mk(cls,html,cb){ const b=document.createElement('button'); b.className=cls; b.innerHTML=html; b.onclick=cb; return b; }
function updateBulkUI() {
    const cnt=selectedIds.size;
    document.getElementById('selectionInfo').textContent=cnt>0?`${cnt} seçili`:'';
    ['bulkVerifyBtn','bulkBanBtn','bulkDeleteBtn'].forEach(id=>document.getElementById(id).style.display=cnt>0?'':'none');
}

document.getElementById('selectAll').addEventListener('change', e=>{
    document.querySelectorAll('.row-check').forEach(cb=>{cb.checked=e.target.checked;e.target.checked?selectedIds.add(cb.dataset.id):selectedIds.delete(cb.dataset.id);});
    updateBulkUI();
});

// ── Role filter tabs ──
document.querySelectorAll('.role-filter-btn').forEach(btn=>{
    btn.addEventListener('click',()=>{
        document.querySelectorAll('.role-filter-btn').forEach(b=>b.style.cssText='');
        btn.style.cssText='border-color:rgba(0,240,255,0.4);color:var(--cyan);background:rgba(0,240,255,0.08);';
        currentRole=btn.dataset.role;
        currentOffset=0;
        loadUsers();
    });
});

// ── Search + Sort ──
let searchTimeout;
document.getElementById('userSearch').addEventListener('input',e=>{ clearTimeout(searchTimeout); searchTimeout=setTimeout(()=>{currentSearch=e.target.value.trim();currentOffset=0;loadUsers();},350); });
document.getElementById('sortSelect').addEventListener('change',e=>{ currentSort=e.target.value; currentOffset=0; loadUsers(); });

// ── View User Detail ──
async function viewUser(id) {
    admOpenModal('userDetailModal');
    document.getElementById('userDetailBody').innerHTML='<div style="text-align:center;padding:40px;"><i class="fa-solid fa-spinner fa-spin" style="font-size:2rem;color:var(--cyan);"></i></div>';
    const data=await fetch(`${API}?action=get&user_id=${id}`).then(r=>r.json());
    if(!data.success){document.getElementById('userDetailBody').innerHTML='<p style="color:var(--red);padding:20px;">Hata: '+data.message+'</p>';return;}
    const u=data.user; const txs=data.transactions;
    const roleMap={verified:['green','Doğrulanmış'],admin:['purple','Yönetici'],user:['gray','Standart']};
    const [rc,rl]=roleMap[u.role]||['gray','?'];
    const xpPct=Math.min(100,(u.xp_points||0)/2000*100);
    document.getElementById('userDetailBody').innerHTML=`
        <div style="display:grid;grid-template-columns:auto 1fr;gap:20px;margin-bottom:24px;padding-bottom:20px;border-bottom:1px solid var(--border);">
            <div class="adm-avatar" style="width:64px;height:64px;font-size:1.5rem;">${(u.full_name||'?')[0].toUpperCase()}</div>
            <div>
                <div style="font-size:1.2rem;font-weight:800;">${esc(u.full_name)}</div>
                <div style="color:var(--text-muted);">${esc(u.email)}</div>
                <div style="margin-top:8px;display:flex;gap:6px;flex-wrap:wrap;">
                    <span class="adm-badge ${rc}">${rl}</span>
                    <span class="adm-badge gray">${esc(u.university_name||'Üniversite yok')}</span>
                    ${u.auth_provider?`<span class="adm-badge blue">${esc(u.auth_provider)}</span>`:''}
                    ${u.is_banned?'<span class="adm-badge red">🚫 Banlı</span>':''}
                </div>
            </div>
        </div>
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:20px;">
            <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-md);padding:14px;text-align:center;">
                <div class="adm-kpi-label">XP</div>
                <div style="font-size:1.2rem;font-weight:800;color:var(--orange);">${Number(u.xp_points||0).toLocaleString('tr-TR')}</div>
            </div>
            <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-md);padding:14px;text-align:center;">
                <div class="adm-kpi-label">Seviye</div>
                <div style="font-size:1.2rem;font-weight:800;color:var(--green);">${u.level||1}</div>
            </div>
            <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-md);padding:14px;text-align:center;">
                <div class="adm-kpi-label">Kimlik Doğrulama</div>
                <div style="font-size:0.9rem;font-weight:700;color:var(--cyan);">${u.auth_provider||'email'}</div>
            </div>
            <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-md);padding:14px;text-align:center;">
                <div class="adm-kpi-label">Kayıt Tarihi</div>
                <div style="font-size:0.85rem;font-weight:700;">${fmtDate(u.created_at)}</div>
            </div>
        </div>
        <div style="margin-bottom:16px;">
            <div style="margin-bottom:6px;font-size:0.8rem;color:var(--text-muted);">XP İlerleme (Seviye ${u.level||1})</div>
            <div class="adm-progress"><div class="adm-progress-fill" style="width:${xpPct}%;background:var(--orange);"></div></div>
        </div>
        <div style="font-size:0.85rem;font-weight:700;color:var(--text-secondary);margin-bottom:12px;text-transform:uppercase;letter-spacing:1px;">Son 5 İşlem</div>
        ${txs.length?`
        <div class="adm-table-wrap" style="border:none;">
            <table class="adm-table" style="font-size:0.82rem;">
                <thead><tr><th>Tür</th><th>Kategori</th><th>Açıklama</th><th style="text-align:right;">Miktar</th><th>Tarih</th></tr></thead>
                <tbody>${txs.map(t=>`<tr>
                    <td><span class="adm-badge ${t.type==='income'?'green':'orange'}">${t.type==='income'?'Gelir':'Gider'}</span></td>
                    <td>${esc(t.category)}</td>
                    <td style="color:var(--text-muted);max-width:120px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${esc(t.description||'—')}</td>
                    <td style="text-align:right;font-family:'JetBrains Mono',monospace;font-weight:700;color:${t.type==='income'?'var(--green)':'var(--orange)'};">${t.type==='income'?'+':'-'}₺${Number(t.amount).toLocaleString('tr-TR',{minimumFractionDigits:2})}</td>
                    <td style="color:var(--text-muted);">${fmtDate(t.transaction_date)}</td>
                </tr>`).join('')}</tbody>
            </table>
        </div>`:'<p style="color:var(--text-muted);font-size:0.85rem;">Henüz işlem yok.</p>'}
        <div style="display:flex;gap:8px;margin-top:20px;padding-top:16px;border-top:1px solid var(--border);">
            <button class="adm-btn adm-btn-ghost adm-btn-sm" onclick="admCloseModal('userDetailModal');openEditUser(${u.id},'${u.role}',${u.xp_points||0})"><i class="fa-solid fa-pen"></i> Düzenle</button>
            <button class="adm-btn adm-btn-ghost adm-btn-sm" onclick="admCloseModal('userDetailModal');openNotifUser(${u.id},'${esc(u.full_name)}')"><i class="fa-solid fa-bell"></i> Bildirim</button>
            <button class="adm-btn adm-btn-${u.is_banned?'success':'ghost'} adm-btn-sm" onclick="banUser(${u.id},${u.is_banned?0:1},'${esc(u.full_name)}')"><i class="fa-solid fa-${u.is_banned?'check':'ban'}"></i> ${u.is_banned?'Banı Kaldır':'Banla'}</button>
        </div>`;
}

// ── Edit User ──
function openEditUser(id, role, xp) {
    document.getElementById('editUserId').value=id;
    document.getElementById('editRoleSelect').value=role;
    document.getElementById('editXpInput').value=xp;
    admOpenModal('editUserModal');
}
document.getElementById('saveUserEditBtn').addEventListener('click', async()=>{
    const id  = document.getElementById('editUserId').value;
    const role= document.getElementById('editRoleSelect').value;
    const xp  = document.getElementById('editXpInput').value;
    const note= document.getElementById('editNoteInput').value;
    // Update role
    const d1=await admFetch(API,{action:'update_role',user_id:id,role});
    // Update XP
    const d2=await admFetch(AN_API,{action:'edit_xp',user_id:id,xp});
    if(d1.success && d2.success){
        admCloseModal('editUserModal');
        admToast('success','Kullanıcı Güncellendi','Rol ve XP başarıyla güncellendi.');
        loadUsers();
    } else {
        admToast('error','Hata',d1.message||d2.message);
    }
});

// ── Ban / Unban ──
async function banUser(id, ban, name) {
    if(ban) {
        admConfirm('Kullanıcıyı Banla', `"${name}" kullanıcısını banlamak istediğinize emin misiniz?`, async()=>{
            const d=await admFetch(AN_API,{action:'ban_user',user_id:id,ban:1});
            if(d.success){admToast('warning','Kullanıcı Banlandı',`${name} banlandı.`);loadUsers();}
            else admToast('error','Hata',d.message);
        },'Banla','fa-ban');
    } else {
        const d=await admFetch(AN_API,{action:'ban_user',user_id:id,ban:0});
        if(d.success){admToast('success','Ban Kaldırıldı',`${name} artık aktif.`);loadUsers();}
        else admToast('error','Hata',d.message);
    }
}

// ── Send Notification ──
function openNotifUser(id, name) {
    document.getElementById('notifTargetUserId').value=id;
    document.getElementById('notifTitle').value=`Merhaba ${name}`;
    document.getElementById('notifMsg').value='';
    admOpenModal('notifUserModal');
}
document.getElementById('sendNotifUserBtn').addEventListener('click', async()=>{
    const d=await admFetch(AN_API,{
        action:'send_notification',
        user_id:document.getElementById('notifTargetUserId').value,
        type:document.getElementById('notifType').value,
        title:document.getElementById('notifTitle').value,
        message:document.getElementById('notifMsg').value,
    });
    if(d.success){admCloseModal('notifUserModal');admToast('success','Bildirim Gönderildi');}
    else admToast('error','Hata',d.message);
});

// ── Delete User ──
function deleteUser(id, name) {
    admConfirm('Kullanıcıyı Sil',`"${name}" ve TÜM verilerini silmek istediğinize emin misiniz? Bu işlem GERİ ALINAMAZ.`,async()=>{
        const d=await admFetch(API,{action:'delete',user_id:id});
        if(d.success){admToast('success','Silindi',`${name} silindi.`);selectedIds.delete(String(id));loadUsers();}
        else admToast('error','Hata',d.message);
    },'Sil','fa-trash');
}

// ── Bulk Actions ──
document.getElementById('bulkVerifyBtn').addEventListener('click',async()=>{
    for(const id of [...selectedIds]) await admFetch(API,{action:'update_role',user_id:id,role:'verified'});
    admToast('success',`${selectedIds.size} Üye Doğrulandı`);selectedIds.clear();updateBulkUI();loadUsers();
});
document.getElementById('bulkBanBtn').addEventListener('click',()=>{
    admConfirm('Toplu Banla',`${selectedIds.size} kullanıcıyı banlamak istediğinize emin misiniz?`,async()=>{
        for(const id of [...selectedIds]) await admFetch(AN_API,{action:'ban_user',user_id:id,ban:1});
        admToast('warning',`${selectedIds.size} Kullanıcı Banlandı`);selectedIds.clear();updateBulkUI();loadUsers();
    },'Banla','fa-ban');
});
document.getElementById('bulkDeleteBtn').addEventListener('click',()=>{
    admConfirm('Toplu Sil',`${selectedIds.size} kullanıcıyı silmek istediğinize emin misiniz?`,async()=>{
        for(const id of [...selectedIds]) await admFetch(API,{action:'delete',user_id:id});
        admToast('success',`${selectedIds.size} Kullanıcı Silindi`);selectedIds.clear();updateBulkUI();loadUsers();
    },'Sil','fa-trash');
});

// ── CSV Export ──
document.getElementById('exportCsvBtn').addEventListener('click',async()=>{
    const res=await fetch(`${API}?action=list&role=${currentRole}&search=${currentSearch}&limit=9999&offset=0`);
    const data=await res.json();
    if(!data.users) return;
    const cols=['id','full_name','email','role','university_name','xp_points','level','created_at','tx_count','tx_volume'];
    const rows=[cols.join(','),...data.users.map(u=>cols.map(c=>`"${String(u[c]||'').replace(/"/g,'""')}"`).join(','))];
    const blob=new Blob([rows.join('\n')],{type:'text/csv;charset=utf-8;'});
    const a=document.createElement('a');a.href=URL.createObjectURL(blob);a.download=`users_${new Date().toISOString().slice(0,10)}.csv`;a.click();
    admToast('success','CSV Hazır','Dosya indirildi.');
});

function esc(s){const d=document.createElement('div');d.textContent=s||'';return d.innerHTML;}
function fmtDate(s){if(!s)return'—';return new Date(s).toLocaleDateString('tr-TR',{day:'2-digit',month:'2-digit',year:'numeric'});}

loadUsers();
</script>
<?php require_once 'footer.php'; ?>
