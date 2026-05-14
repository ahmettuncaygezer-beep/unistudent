<?php
$PAGE_TITLE = 'Şehir Yönetimi';
require_once 'header.php';
require_once 'api/data_manager.php';

$dm     = new DataManager();
$cities = $dm->getCityData();
$hasError = isset($cities['error']);
?>

<div class="adm-section-header">
    <div>
        <div class="adm-section-title">Şehir Fiyat Yönetimi</div>
        <div class="adm-section-desc">
            <?php echo $hasError ? '<span style="color:var(--red);">Veri hatası: '.htmlspecialchars($cities['error']).'</span>' : count($cities).' şehir listeleniyor'; ?>
        </div>
    </div>
    <div class="adm-section-actions">
        <div class="adm-search-wrap">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" class="adm-input adm-btn-sm" id="citySearch" placeholder="Şehir ara..." style="min-width:200px;">
        </div>
    </div>
</div>

<div class="adm-table-wrap">
    <div style="overflow-x:auto; max-height:72vh;">
        <table class="adm-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Şehir</th>
                    <th style="text-align:right;">Kira (Tek)</th>
                    <th style="text-align:right;">Kira (Ortak)</th>
                    <th style="text-align:right;">Özel Yurt</th>
                    <th style="text-align:right;">Yemek</th>
                    <th style="text-align:right;">Ulaşım</th>
                    <th style="text-align:right;">Eğlence</th>
                    <th style="text-align:center;">İşlem</th>
                </tr>
            </thead>
            <tbody id="cityBody">
                <?php if($hasError): ?>
                <tr><td colspan="9"><div class="adm-empty"><i class="fa-solid fa-triangle-exclamation"></i><p>Veri yüklenemedi</p></div></td></tr>
                <?php else: ?>
                <?php foreach($cities as $i => $city): ?>
                <tr class="city-row" data-name="<?php echo strtolower(htmlspecialchars($city[1])); ?>">
                    <td style="color:var(--text-muted);font-size:0.8rem;"><?php echo $i+1; ?></td>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <span style="font-size:1.3rem;"><?php echo $city[2]; ?></span>
                            <div>
                                <div style="font-weight:600;"><?php echo htmlspecialchars($city[1]); ?></div>
                                <?php if(!empty($city[4])): ?>
                                <div style="font-size:0.72rem;color:var(--text-muted);">Skor: <?php echo number_format((float)$city[4],1); ?>/10</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <?php
                    $priceFields = [5,6,7,8,9,10];
                    foreach($priceFields as $pf):
                        $val = (int)($city[$pf] ?? 0);
                    ?>
                    <td style="text-align:right;font-family:'JetBrains Mono',monospace;font-size:0.83rem;color:var(--cyan);">
                        <?php echo number_format($val,0,',','.'); ?>₺
                    </td>
                    <?php endforeach; ?>
                    <td style="text-align:center;">
                        <button class="adm-btn adm-btn-ghost adm-btn-sm" onclick="editCity(<?php echo $i; ?>)">
                            <i class="fa-solid fa-pen"></i> Düzenle
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Edit Modal -->
<div class="adm-modal-overlay" id="cityModal">
    <div class="adm-modal">
        <div class="adm-modal-header">
            <div class="adm-modal-title"><i class="fa-solid fa-city"></i> Şehir Düzenle: <span id="cityModalName" style="color:var(--cyan);"></span></div>
            <button class="adm-modal-close" onclick="admCloseModal('cityModal')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="adm-modal-body">
            <form id="cityForm">
                <input type="hidden" name="index" id="cityIndex">
                <div class="adm-form-grid-2">
                    <div class="adm-form-group"><label class="adm-form-label">Kira (Tek Kişilik) ₺</label><input type="number" name="rentSingle" id="cs_rentSingle" class="adm-input"></div>
                    <div class="adm-form-group"><label class="adm-form-label">Kira (Ortak) ₺</label><input type="number" name="rentShared" id="cs_rentShared" class="adm-input"></div>
                    <div class="adm-form-group"><label class="adm-form-label">Özel Yurt ₺</label><input type="number" name="dormPrivate" id="cs_dormPrivate" class="adm-input"></div>
                    <div class="adm-form-group"><label class="adm-form-label">Yemek ₺</label><input type="number" name="food" id="cs_food" class="adm-input"></div>
                    <div class="adm-form-group"><label class="adm-form-label">Ulaşım ₺</label><input type="number" name="transport" id="cs_transport" class="adm-input"></div>
                    <div class="adm-form-group"><label class="adm-form-label">Eğlence ₺</label><input type="number" name="entertainment" id="cs_entertainment" class="adm-input"></div>
                    <div class="adm-form-group"><label class="adm-form-label">Faturalar ₺</label><input type="number" name="utilities" id="cs_utilities" class="adm-input"></div>
                </div>
            </form>
        </div>
        <div class="adm-modal-footer">
            <button class="adm-btn adm-btn-ghost" onclick="admCloseModal('cityModal')">İptal</button>
            <button class="adm-btn adm-btn-primary" id="saveCityBtn"><i class="fa-solid fa-floppy-disk"></i> Kaydet</button>
        </div>
    </div>
</div>

<script>
const CITY_DATA = <?php echo json_encode($cities ?? []); ?>;
const FIELD_MAP = {cs_rentSingle:5,cs_rentShared:6,cs_dormPrivate:7,cs_food:8,cs_transport:9,cs_entertainment:10,cs_utilities:11};

document.getElementById('citySearch').addEventListener('input', e => {
    const q = e.target.value.toLowerCase();
    document.querySelectorAll('.city-row').forEach(r=>{ r.style.display=r.dataset.name.includes(q)?'':'none'; });
});

function editCity(idx){
    const c = CITY_DATA[idx]; if(!c) return;
    document.getElementById('cityIndex').value = idx;
    document.getElementById('cityModalName').textContent = c[2]+' '+c[1];
    Object.entries(FIELD_MAP).forEach(([id,fi])=>document.getElementById(id).value=c[fi]||0);
    admOpenModal('cityModal');
}

document.getElementById('saveCityBtn').onclick = async () => {
    const fd  = new FormData(document.getElementById('cityForm'));
    const res = await fetch('api/save_city.php',{method:'POST',body:fd});
    const d   = await res.json();
    if(d.success){admCloseModal('cityModal');admToast('success','Kaydedildi','Şehir fiyatları güncellendi.');setTimeout(()=>location.reload(),1200);}
    else admToast('error','Hata',d.message||'Kayıt başarısız.');
};
</script>

<?php require_once 'footer.php'; ?>
