<?php
$__title = 'ÜniBütçe Pro — Fiyatlandırma';
$__desc  = 'Öğrenci bütçesi için ultra-premium özellikler. Temel özellikler her zaman ücretsiz.';
require __DIR__ . '/includes/public_head.php';
?>
<h1>💎 ÜniBütçe Pro</h1>
<p class="ub-lead">Tüm temel özellikler her zaman ücretsiz. Pro tier, ileri düzey araçlar isteyenler için.</p>

<!-- Early Access Banner -->
<div class="ub-card" style="background:linear-gradient(135deg, rgba(125,92,255,0.15), rgba(0,136,255,0.1)); border-color:rgba(125,92,255,0.4); margin-bottom:32px; text-align:center;">
    <div style="font-size:2rem; margin-bottom:8px;">🚀</div>
    <h3 style="margin:0 0 8px; color:#a78bfa;">Pro Yakında Geliyor!</h3>
    <p style="opacity:.7; margin:0 0 16px;">Erken erişim listesine katıl, ilk sen öğren ve %40 indirim kazan.</p>
    <button class="btn btn-primary" data-magnetic onclick="showEarlyAccessModal()" style="margin:0 auto;">
        ✉️ Erken Erişime Kaydol
    </button>
</div>

<div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap:20px; margin-top:32px;">
    <div class="ub-card" data-tilt>
        <h2 style="margin-top:0;">Ücretsiz</h2>
        <div style="font-size:2.5rem; font-weight:800; margin:8px 0;">₺0</div>
        <div style="opacity:.6; margin-bottom:20px;">Her zaman ücretsiz</div>
        <ul style="list-style:none; padding:0; line-height:2;">
            <li>✓ Sınırsız gelir/gider kaydı</li>
            <li>✓ 6 aylık işlem geçmişi</li>
            <li>✓ 10 özel kategori</li>
            <li>✓ Gamification (XP, level)</li>
            <li>✓ 5 Cyber Coach mesaj/hafta</li>
            <li>✓ 1 paylaşımlı bütçe</li>
            <li>✓ Temel anomali alarmları</li>
            <li>✓ Abonelik takibi</li>
        </ul>
    </div>

    <div class="ub-card" data-tilt style="border-color:rgba(125,92,255,.6); box-shadow: var(--ub-glow); position:relative; overflow:visible;">
        <div style="position:absolute; top:-12px; right:20px; background:linear-gradient(135deg,#7d5cff,#0088ff); color:#fff; padding:4px 12px; border-radius:999px; font-size:.75rem; font-weight:700;">ÖNERİLEN</div>
        <h2 style="margin-top:0;">Pro 💎</h2>
        <div style="font-size:2.5rem; font-weight:800; margin:8px 0;">₺29<span style="font-size:1rem; opacity:.6;">/ay</span></div>
        <div style="opacity:.6; margin-bottom:20px;">veya ₺200/yıl (42% indirim)</div>
        <ul style="list-style:none; padding:0; line-height:2;">
            <li>✨ Her şey ücretsiz tier'da +</li>
            <li>♾️ Sınırsız Cyber Coach mesaj</li>
            <li>📦 Sınırsız kategori</li>
            <li>📅 2+ yıllık işlem arşivi</li>
            <li>📊 Yatırım portföyü (BIST + crypto)</li>
            <li>🌐 Yurtdışı/Erasmus modu</li>
            <li>👥 5 paylaşımlı bütçe</li>
            <li>📧 Haftalık AI dijest (Claude)</li>
            <li>📄 PDF/CSV/Excel export</li>
            <li>🎯 Gelişmiş raporlar + custom filters</li>
            <li>⚡ Öncelikli destek (24h)</li>
            <li>🆕 Yeni özelliklere erken erişim</li>
        </ul>
        <button class="btn btn-primary" data-magnetic style="width:100%; margin-top:16px;" onclick="showEarlyAccessModal()">
            💌 Erken Erişime Kaydol
        </button>
    </div>

    <div class="ub-card" data-tilt>
        <h2 style="margin-top:0;">Üniversite</h2>
        <div style="font-size:2.5rem; font-weight:800; margin:8px 0;">Özel</div>
        <div style="opacity:.6; margin-bottom:20px;">Kampüs lisansı</div>
        <ul style="list-style:none; padding:0; line-height:2;">
            <li>🎓 Kampüs genelinde ücretsiz Pro</li>
            <li>📊 Kampüs analitik panosu</li>
            <li>🎨 Custom branding</li>
            <li>🔌 SSO entegrasyonu</li>
            <li>💬 Adanmış destek</li>
            <li>📚 Finansal eğitim içeriği</li>
        </ul>
        <a href="mailto:kampus@unibutce.com" class="btn btn-secondary" data-magnetic style="width:100%; margin-top:16px;">İletişime Geç</a>
    </div>
</div>

<h2>Sıkça Sorulanlar</h2>
<div class="ub-card"><b>Pro'yu iptal edebilir miyim?</b><br>Evet, istediğin zaman. Ödenen süre bitene kadar Pro özellikleri aktif kalır.</div>
<div class="ub-card"><b>Verim Pro iptal edilince kaybolur mu?</b><br>Hayır. Ücretsiz tier'a döner, son 6 ay verisi erişilebilir kalır. Eski arşive export üzerinden her zaman ulaşabilirsin.</div>
<div class="ub-card"><b>Öğrenci indirimi var mı?</b><br>Zaten öğrencilere özel tasarlandı — fiyat bu. Kampüsünüz toplu lisans için iletişime geçebilir.</div>
<div class="ub-card"><b>Ne zaman kullanıma açılacak?</b><br>2025 yazında beta başlıyor. Erken erişim listesindekiler ilk bildirilecek ve %40 indirim alacak.</div>

<!-- Early Access Modal -->
<div id="earlyAccessModal" style="display:none; position:fixed; inset:0; z-index:99999; align-items:center; justify-content:center; background:rgba(0,0,0,0.8); backdrop-filter:blur(8px);">
    <div style="background:linear-gradient(135deg,rgba(12,26,51,0.99),rgba(6,17,33,0.99)); border:1px solid rgba(125,92,255,0.4); border-radius:20px; padding:40px; max-width:460px; width:90%; text-align:center; box-shadow:0 24px 80px rgba(0,0,0,0.6);">
        <div style="font-size:3rem; margin-bottom:16px;">✉️</div>
        <h2 style="margin:0 0 8px; color:#fff;">Erken Erişim Listesi</h2>
        <p style="opacity:.7; margin:0 0 24px; line-height:1.7;">Pro çıktığında ilk sen öğren. <strong style="color:#a78bfa;">%40 indirim</strong> senin için hazır.</p>
        <form id="earlyAccessForm" onsubmit="submitEarlyAccess(event)">
            <input type="email" id="earlyEmail" placeholder="E-posta adresin" required
                style="width:100%; padding:14px 18px; border-radius:12px; border:1px solid rgba(255,255,255,0.15); background:rgba(255,255,255,0.06); color:#fff; font-size:1rem; margin-bottom:14px; box-sizing:border-box; outline:none;">
            <input type="text" id="earlyName" placeholder="Adın (opsiyonel)"
                style="width:100%; padding:14px 18px; border-radius:12px; border:1px solid rgba(255,255,255,0.15); background:rgba(255,255,255,0.06); color:#fff; font-size:1rem; margin-bottom:18px; box-sizing:border-box; outline:none;">
            <button type="submit" style="width:100%; padding:14px; border-radius:12px; background:linear-gradient(135deg,#7d5cff,#0088ff); color:#fff; border:none; font-size:1rem; font-weight:700; cursor:pointer; transition:all 0.2s;" id="earlySubmitBtn">
                🚀 Listeye Katıl
            </button>
            <div id="earlyMsg" style="margin-top:14px; font-size:.9rem;"></div>
        </form>
        <button onclick="closeEarlyModal()" style="margin-top:16px; background:none; border:none; color:rgba(255,255,255,0.4); cursor:pointer; font-size:.85rem;">Kapat</button>
    </div>
</div>

<script>
function showEarlyAccessModal() {
    const m = document.getElementById('earlyAccessModal');
    m.style.display = 'flex';
    setTimeout(() => document.getElementById('earlyEmail')?.focus(), 100);
}
function closeEarlyModal() {
    document.getElementById('earlyAccessModal').style.display = 'none';
}
document.getElementById('earlyAccessModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeEarlyModal();
});
async function submitEarlyAccess(e) {
    e.preventDefault();
    const email = document.getElementById('earlyEmail').value;
    const name = document.getElementById('earlyName').value;
    const btn = document.getElementById('earlySubmitBtn');
    const msg = document.getElementById('earlyMsg');

    btn.textContent = '⏳ Kaydediliyor...';
    btn.disabled = true;

    try {
        const existing = JSON.parse(localStorage.getItem('earlyAccessList') || '[]');
        existing.push({ email, name, date: new Date().toISOString() });
        localStorage.setItem('earlyAccessList', JSON.stringify(existing));
    } catch(err) {}

    await new Promise(r => setTimeout(r, 1000));

    msg.innerHTML = '✅ <strong style="color:#39ff14;">Harika!</strong> Listeye eklendin. Pro çıktığında ilk sen öğreneceksin!';
    btn.textContent = '✅ Kaydedildi!';
    setTimeout(() => closeEarlyModal(), 3000);
}
</script>

<?php require __DIR__ . '/includes/public_foot.php'; ?>
