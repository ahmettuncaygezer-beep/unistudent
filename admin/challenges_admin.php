<?php
$PAGE_TITLE = 'Challenge Yönetimi';
require_once 'header.php';
?>

<div class="adm-section-header">
    <div><div class="adm-section-title">Challenge Yönetimi</div><div class="adm-section-desc">Öğrenci tasarruf meydan okumalarını düzenle</div></div>
    <button class="adm-btn adm-btn-primary" id="newChallengeBtn"><i class="fa-solid fa-plus"></i> Yeni Challenge</button>
</div>

<div class="adm-table-wrap">
    <table class="adm-table">
        <thead><tr>
            <th>İkon</th><th>Başlık</th><th style="text-align:center;">Süre</th><th style="text-align:center;">Hedef</th>
            <th style="text-align:center;">XP</th><th style="text-align:center;">Katılım</th><th style="text-align:center;">Durum</th>
            <th style="text-align:right;">İşlemler</th>
        </tr></thead>
        <tbody id="challengeBody"><tr><td colspan="8" style="text-align:center;padding:40px;color:var(--text-muted);"><i class="fa-solid fa-spinner fa-spin"></i></td></tr></tbody>
    </table>
</div>

<!-- Challenge Modal -->
<div class="adm-modal-overlay" id="challengeModal">
    <div class="adm-modal">
        <div class="adm-modal-header">
            <div class="adm-modal-title"><i class="fa-solid fa-trophy"></i> <span id="chModalTitle">Yeni Challenge</span></div>
            <button class="adm-modal-close" onclick="admCloseModal('challengeModal')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="adm-modal-body">
            <input type="hidden" id="chId">
            <div class="adm-form-grid-2">
                <div class="adm-form-group">
                    <label class="adm-form-label">Başlık <span>*</span></label>
                    <input class="adm-input" id="chTitle" placeholder="7 Gün Dışarıda Yeme">
                </div>
                <div class="adm-form-group">
                    <label class="adm-form-label">Slug <span>*</span></label>
                    <input class="adm-input adm-code" id="chSlug" placeholder="no_eat_out_7">
                </div>
                <div class="adm-form-group">
                    <label class="adm-form-label">İkon (Emoji)</label>
                    <input class="adm-input" id="chIcon" placeholder="🍱" maxlength="5">
                </div>
                <div class="adm-form-group">
                    <label class="adm-form-label">Kategori Bloğu</label>
                    <input class="adm-input" id="chCatBlock" placeholder="Yemek">
                </div>
                <div class="adm-form-group">
                    <label class="adm-form-label">Süre (Gün)</label>
                    <input class="adm-input" type="number" id="chDays" value="7" min="1">
                </div>
                <div class="adm-form-group">
                    <label class="adm-form-label">Tasarruf Hedefi (₺)</label>
                    <input class="adm-input" type="number" id="chTarget" value="0" min="0">
                </div>
                <div class="adm-form-group">
                    <label class="adm-form-label">XP Ödülü</label>
                    <input class="adm-input" type="number" id="chXp" value="100" min="0">
                </div>
                <div class="adm-form-group" style="display:flex;align-items:center;gap:12px;padding-top:24px;">
                    <label class="adm-toggle"><input type="checkbox" id="chActive" checked><span class="adm-toggle-slider"></span></label>
                    <label class="adm-form-label" style="margin:0;">Aktif</label>
                </div>
            </div>
            <div class="adm-form-group">
                <label class="adm-form-label">Açıklama</label>
                <textarea class="adm-textarea" id="chDesc" rows="3" placeholder="Challenge açıklaması..."></textarea>
            </div>
        </div>
        <div class="adm-modal-footer">
            <button class="adm-btn adm-btn-ghost" onclick="admCloseModal('challengeModal')">İptal</button>
            <button class="adm-btn adm-btn-primary" id="saveChallengeBtn"><i class="fa-solid fa-floppy-disk"></i> Kaydet</button>
        </div>
    </div>
</div>

<script>
const CH_API = 'api/admin_challenges.php';
let editingCh = null;

async function loadChallenges() {
    const data = await fetch(`${CH_API}?action=list`).then(r=>r.json());
    const tbody = document.getElementById('challengeBody');
    const rows  = data.challenges || [];
    if(!rows.length){tbody.innerHTML='<tr><td colspan="8" style="text-align:center;padding:50px;color:var(--text-muted);">Challenge bulunamadı</td></tr>';return;}
    tbody.innerHTML = rows.map(c=>`
        <tr>
            <td style="font-size:1.4rem;text-align:center;">${c.icon||'🎯'}</td>
            <td><div style="font-weight:600;">${esc(c.title)}</div><div class="adm-code" style="font-size:0.72rem;color:var(--text-muted);">${esc(c.slug)}</div></td>
            <td style="text-align:center;"><span class="adm-badge cyan">${c.duration_days}g</span></td>
            <td style="text-align:center;font-family:'JetBrains Mono',monospace;font-size:0.83rem;">₺${Number(c.target_savings).toLocaleString('tr-TR')}</td>
            <td style="text-align:center;"><span class="adm-badge purple">${Number(c.xp_reward).toLocaleString('tr-TR')} XP</span></td>
            <td style="text-align:center;">
                <div style="font-size:0.8rem;">${c.participant_count} katılım</div>
                <div style="font-size:0.72rem;color:var(--green);">${c.completed_count} tamamladı</div>
            </td>
            <td style="text-align:center;">
                <label class="adm-toggle"><input type="checkbox" ${c.is_active?'checked':''} onchange="toggleCh(${c.id})"><span class="adm-toggle-slider"></span></label>
            </td>
            <td>
                <div style="display:flex;justify-content:flex-end;gap:6px;">
                    <button class="adm-btn adm-btn-ghost adm-btn-icon adm-btn-sm" onclick="editCh(${c.id})"><i class="fa-solid fa-pen"></i></button>
                    <button class="adm-btn adm-btn-danger adm-btn-icon adm-btn-sm" onclick="deleteCh(${c.id},'${esc(c.title)}')"><i class="fa-solid fa-trash"></i></button>
                </div>
            </td>
        </tr>`).join('');
}

document.getElementById('newChallengeBtn').onclick = () => {
    editingCh = null;
    ['chId','chTitle','chSlug','chIcon','chCatBlock','chDesc'].forEach(id=>document.getElementById(id).value='');
    document.getElementById('chDays').value = 7;
    document.getElementById('chTarget').value = 0;
    document.getElementById('chXp').value = 100;
    document.getElementById('chActive').checked = true;
    document.getElementById('chModalTitle').textContent = 'Yeni Challenge';
    admOpenModal('challengeModal');
};

function editCh(id) {
    editingCh = id;
    fetch(`${CH_API}?action=list`).then(r=>r.json()).then(data=>{
        const c = (data.challenges||[]).find(x=>x.id==id);
        if(!c) return;
        document.getElementById('chId').value = c.id;
        document.getElementById('chTitle').value = c.title;
        document.getElementById('chSlug').value  = c.slug;
        document.getElementById('chIcon').value  = c.icon;
        document.getElementById('chCatBlock').value = c.category_block;
        document.getElementById('chDays').value   = c.duration_days;
        document.getElementById('chTarget').value = c.target_savings;
        document.getElementById('chXp').value     = c.xp_reward;
        document.getElementById('chDesc').value   = c.description || '';
        document.getElementById('chActive').checked = !!c.is_active;
        document.getElementById('chModalTitle').textContent = 'Challenge Düzenle';
        admOpenModal('challengeModal');
    });
}

document.getElementById('saveChallengeBtn').onclick = async () => {
    const payload = {
        action:        editingCh ? 'update' : 'create',
        id:            editingCh || '',
        slug:          document.getElementById('chSlug').value,
        title:         document.getElementById('chTitle').value,
        description:   document.getElementById('chDesc').value,
        icon:          document.getElementById('chIcon').value,
        duration_days: document.getElementById('chDays').value,
        target_savings:document.getElementById('chTarget').value,
        xp_reward:     document.getElementById('chXp').value,
        category_block:document.getElementById('chCatBlock').value,
    };
    if(document.getElementById('chActive').checked) payload.is_active = '1';
    const d = await admFetch(CH_API, payload);
    if(d.success){admCloseModal('challengeModal');admToast('success','Kaydedildi',d.message);loadChallenges();}
    else admToast('error','Hata',d.message);
};

async function toggleCh(id) {
    const d = await admFetch(CH_API,{action:'toggle',id});
    if(d.success) admToast('info','Güncellendi','Durum değiştirildi.');
    else admToast('error','Hata',d.message);
}
function deleteCh(id,title){
    admConfirm('Challenge Sil',`"${title}" challenge'ını silmek istediğinize emin misiniz?`,async()=>{
        const d=await admFetch(CH_API,{action:'delete',id});
        if(d.success){admToast('success','Silindi',d.message);loadChallenges();}
        else admToast('error','Hata',d.message);
    });
}
function esc(s){const d=document.createElement('div');d.textContent=s||'';return d.innerHTML;}
loadChallenges();
</script>
<?php require_once 'footer.php'; ?>
