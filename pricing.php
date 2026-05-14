<?php
$__title = 'ÜniBütçe Pro';
$__desc  = 'Öğrenci bütçesi için ultra-premium özellikler.';
require __DIR__ . '/includes/public_head.php';
?>
<h1>💎 ÜniBütçe Pro</h1>
<p class="ub-lead">Tüm temel özellikler her zaman ücretsiz. Pro tier, ileri düzey araçlar isteyenler için.</p>

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

    <div class="ub-card" data-tilt style="border-color:rgba(125,92,255,.6); box-shadow: var(--ub-glow);">
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
        <button class="btn btn-primary" data-magnetic style="width:100%; margin-top:16px;" onclick="alert('Pro yakında! Erken erişim listesine kaydolmak için hello@unibutce.com')">Pro'ya Yükselt</button>
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

<?php require __DIR__ . '/includes/public_foot.php'; ?>
