<?php
$PAGE_TITLE = 'İndirim Yönetimi';
require_once 'header.php';
?>

<div class="adm-section-header">
    <div><div class="adm-section-title">Öğrenci İndirimleri</div><div class="adm-section-desc">Platform genelinde gösterilen indirim fırsatlarını yönet</div></div>
    <button class="adm-btn adm-btn-primary" id="newDiscountBtn"><i class="fa-solid fa-plus"></i> Yeni İndirim</button>
</div>

<div class="adm-table-wrap">
    <div class="adm-table-toolbar">
        <div class="adm-search-wrap" style="max-width:280px;">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" class="adm-input adm-btn-sm" id="discSearch" placeholder="Marka, kategori ara...">
        </div>
    </div>
    <div style="overflow-x:auto;">
        <table class="adm-table">
            <thead><tr>
                <th>Marka</th><th>Kategori</th><th>İndirim</th><th style="text-align:center;">Aylık Tasarruf</th>
                <th style="text-align:center;">Talep Sayısı</th><th style="text-align:center;">Durum</th><th style="text-align:right;">İşlemler</th>
            </tr></thead>
            <tbody id="discBody"><tr><td colspan="7" style="text-align:center;padding:40px;color:var(--text-muted);"><i class="fa-solid fa-spinner fa-spin"></i></td></tr></tbody>
        </table>
    </div>
</div>

<!-- Discount Modal -->
<div class="adm-modal-overlay" id="discountModal">
    <div class="adm-modal">
        <div class="adm-modal-header">
            <div class="adm-modal-title"><i class="fa-solid fa-ticket"></i> <span id="discModalTitle">Yeni İndirim</span></div>
            <button class="adm-modal-close" onclick="admCloseModal('discountModal')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="adm-modal-body">
            <input type="hidden" id="discId">
            <div class="adm-form-grid-2">
                <div class="adm-form-group"><label class="adm-form-label">Marka <span>*</span></label><input class="adm-input" id="discBrand" placeholder="Spotify"></div>
                <div class="adm-form-group"><label class="adm-form-label">Kategori</label>
                    <select class="adm-select" id="discCategory">
                        <option>Müzik</option><option>Video</option><option>Yazılım</option><option>Tasarım</option>
                        <option>Verimlilik</option><option>Eğitim</option><option>Ulaşım</option><option>Yemek</option>
                        <option>Alışveriş</option><option>Teknoloji</option><option>Seyahat</option><option>Kitap</option><option>Diğer</option>
                    </select>
                </div>
                <div class="adm-form-group"><label class="adm-form-label">İndirim Metni</label><input class="adm-input" id="discText" placeholder="%50 öğrenci"></div>
                <div class="adm-form-group"><label class="adm-form-label">Aylık Tasarruf (₺)</label><input class="adm-input" type="number" id="discSaving" value="0" min="0" step="0.01"></div>
                <div class="adm-form-group"><label class="adm-form-label">İkon (Emoji)</label><input class="adm-input" id="discIcon" placeholder="🎵" maxlength="5"></div>
                <div class="adm-form-group"><label class="adm-form-label">Bölge</label><select class="adm-select" id="discRegion"><option value="TR">Türkiye (TR)</option><option value="GLOBAL">Global</option></select></div>
            </div>
            <div class="adm-form-group"><label class="adm-form-label">URL</label><input class="adm-input" id="discUrl" placeholder="https://..."></div>
            <div class="adm-form-group"><label class="adm-form-label">Nasıl Alınır?</label><textarea class="adm-textarea" id="discHowTo" rows="2" placeholder="Adım adım açıklama..."></textarea></div>
            <div style="display:flex;align-items:center;gap:12px;">
                <label class="adm-toggle"><input type="checkbox" id="discActive" checked><span class="adm-toggle-slider"></span></label>
                <label class="adm-form-label" style="margin:0;">Aktif olarak göster</label>
            </div>
        </div>
        <div class="adm-modal-footer">
            <button class="adm-btn adm-btn-ghost" onclick="admCloseModal('discountModal')">İptal</button>
            <button class="adm-btn adm-btn-primary" id="saveDiscountBtn"><i class="fa-solid fa-floppy-disk"></i> Kaydet</button>
        </div>
    </div>
</div>

<script>
const DISC_API = 'api/admin_discounts.php';
let allDiscounts = [];
let editingDisc = null;

async function loadDiscounts(){
    const data = await fetch(`${DISC_API}?action=list`).then(r=>r.json());
    allDiscounts = data.discounts||[];
    renderDiscounts(allDiscounts);
}

function renderDiscounts(rows){
    const tbody = document.getElementById('discBody');
    if(!rows.length){tbody.innerHTML='<tr><td colspan="7" style="text-align:center;padding:50px;color:var(--text-muted);">İndirim bulunamadı</td></tr>';return;}
    tbody.innerHTML = rows.map(d=>`
        <tr>
            <td>
                <div style="display:flex;align-items:center;gap:8px;">
                    <span style="font-size:1.3rem;">${d.icon||'🎁'}</span>
                    <div>
                        <div style="font-weight:600;">${esc(d.brand)}</div>
                        <a href="${esc(d.url)}" target="_blank" style="font-size:0.72rem;color:var(--cyan);text-decoration:none;">bağlantıya git ↗</a>
                    </div>
                </div>
            </td>
            <td><span class="adm-badge gray">${esc(d.category)}</span></td>
            <td style="font-size:0.82rem;">${esc(d.discount_text)}</td>
            <td style="text-align:center;font-family:'JetBrains Mono',monospace;color:var(--green);font-weight:700;">₺${Number(d.monthly_saving).toLocaleString('tr-TR')}</td>
            <td style="text-align:center;"><span class="adm-badge purple">${d.claim_count} kişi</span></td>
            <td style="text-align:center;"><label class="adm-toggle"><input type="checkbox" ${d.is_active?'checked':''} onchange="toggleDisc(${d.id})"><span class="adm-toggle-slider"></span></label></td>
            <td>
                <div style="display:flex;justify-content:flex-end;gap:6px;">
                    <button class="adm-btn adm-btn-ghost adm-btn-icon adm-btn-sm" onclick="editDisc(${d.id})"><i class="fa-solid fa-pen"></i></button>
                    <button class="adm-btn adm-btn-danger adm-btn-icon adm-btn-sm" onclick="deleteDisc(${d.id},'${esc(d.brand)}')"><i class="fa-solid fa-trash"></i></button>
                </div>
            </td>
        </tr>`).join('');
}

document.getElementById('discSearch').addEventListener('input', e=>{
    const q = e.target.value.toLowerCase();
    renderDiscounts(allDiscounts.filter(d=>(d.brand+d.category+d.discount_text).toLowerCase().includes(q)));
});

document.getElementById('newDiscountBtn').onclick = ()=>{
    editingDisc=null;
    ['discId','discBrand','discText','discSaving','discIcon','discUrl','discHowTo'].forEach(id=>document.getElementById(id).value='');
    document.getElementById('discSaving').value=0;
    document.getElementById('discActive').checked=true;
    document.getElementById('discModalTitle').textContent='Yeni İndirim';
    admOpenModal('discountModal');
};

function editDisc(id){
    editingDisc=id;
    const d=allDiscounts.find(x=>x.id==id); if(!d) return;
    document.getElementById('discId').value=d.id;
    document.getElementById('discBrand').value=d.brand;
    document.getElementById('discCategory').value=d.category;
    document.getElementById('discText').value=d.discount_text;
    document.getElementById('discSaving').value=d.monthly_saving;
    document.getElementById('discIcon').value=d.icon;
    document.getElementById('discRegion').value=d.region||'TR';
    document.getElementById('discUrl').value=d.url;
    document.getElementById('discHowTo').value=d.how_to;
    document.getElementById('discActive').checked=!!d.is_active;
    document.getElementById('discModalTitle').textContent='İndirim Düzenle';
    admOpenModal('discountModal');
}

document.getElementById('saveDiscountBtn').onclick = async()=>{
    const payload={
        action:editingDisc?'update':'create', id:editingDisc||'',
        brand:document.getElementById('discBrand').value,
        category:document.getElementById('discCategory').value,
        discount_text:document.getElementById('discText').value,
        monthly_saving:document.getElementById('discSaving').value,
        icon:document.getElementById('discIcon').value,
        region:document.getElementById('discRegion').value,
        url:document.getElementById('discUrl').value,
        how_to:document.getElementById('discHowTo').value,
    };
    if(document.getElementById('discActive').checked) payload.is_active='1';
    const d=await admFetch(DISC_API,payload);
    if(d.success){admCloseModal('discountModal');admToast('success','Kaydedildi',d.message);loadDiscounts();}
    else admToast('error','Hata',d.message);
};

async function toggleDisc(id){const d=await admFetch(DISC_API,{action:'toggle',id});if(d.success) admToast('info','Güncellendi','Durum değiştirildi.');else admToast('error','Hata',d.message);}
function deleteDisc(id,brand){admConfirm('İndirimi Sil',`"${brand}" indirimini silmek istediğinize emin misiniz?`,async()=>{const d=await admFetch(DISC_API,{action:'delete',id});if(d.success){admToast('success','Silindi',d.message);loadDiscounts();}else admToast('error','Hata',d.message);});}
function esc(s){const d=document.createElement('div');d.textContent=s||'';return d.innerHTML;}
loadDiscounts();
</script>
<?php require_once 'footer.php'; ?>
