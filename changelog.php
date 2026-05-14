<?php
$__title = 'Değişiklik Günlüğü';
$__desc  = 'ÜniBütçe sürüm notları ve yayınlanan tüm güncellemeler.';
require __DIR__ . '/includes/public_head.php';
?>
<h1>📜 Changelog</h1>
<p class="ub-lead">Her sürümde neler değiştiğini, hangi hataların düzeltildiğini ve hangi özelliklerin eklendiğini buradan takip edebilirsin.</p>

<div class="ub-card">
    <h2 style="margin-top:0;">v3.0 — Ultra Premium Katman 🚀 <span style="font-size:.7em;opacity:.6">(<?= date('d M Y') ?>)</span></h2>
    <ul>
        <li>✨ Custom cursor + magnetic butonlar + 3D tilt kartlar</li>
        <li>⌘K Komut paleti (fuzzy arama ile 20+ komut)</li>
        <li>🔍 Anomali tespiti (z-score ile olağandışı harcama alarmı)</li>
        <li>📅 Abonelik otomatik tespiti (Netflix, Spotify, iCloud vb.)</li>
        <li>🔄 Multi-tab sync (BroadcastChannel)</li>
        <li>🌐 Yurtdışı/Erasmus modu (canlı kur çevirme)</li>
        <li>📊 Haftalık AI dijest (Claude Haiku 4.5)</li>
        <li>👥 Paylaşımlı ev bütçesi + Splitwise settlement</li>
        <li>🎓 Burs takipçisi + yatırım portföyü (Pro)</li>
        <li>🔒 GDPR veri indirme + hesap silme</li>
        <li>🎯 Kayıtlı görünümler (saved views)</li>
        <li>🏁 Feature flags + Pro tier gatekeeping</li>
    </ul>
</div>

<div class="ub-card">
    <h2 style="margin-top:0;">v2.0 — Güvenlik & Mimari Sağlamlaştırma</h2>
    <ul>
        <li>🛡️ CSRF double-submit token koruması</li>
        <li>🔐 Bcrypt + hash_equals timing-safe auth</li>
        <li>🚦 Atomic rate limiting (flock)</li>
        <li>📝 .env yapılandırma</li>
        <li>⚡ 7 performans indeksi</li>
        <li>🌍 CORS whitelist</li>
        <li>🗺️ Dynamic sitemap.xml + robots.txt</li>
        <li>🤖 Service Worker v7 (network-first)</li>
    </ul>
</div>

<div class="ub-card">
    <h2 style="margin-top:0;">v1.0 — İlk Yayın</h2>
    <ul>
        <li>📊 Glass morphism dashboard + bento grid</li>
        <li>🎮 Gamification (XP, level, achievements)</li>
        <li>🤖 Cyber Coach AI (Gemini)</li>
        <li>📝 Admin blog CMS</li>
        <li>📱 PWA + manifest</li>
        <li>🏙️ Şehir karşılaştırma</li>
    </ul>
</div>

<?php require __DIR__ . '/includes/public_foot.php'; ?>
