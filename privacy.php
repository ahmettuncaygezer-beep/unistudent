<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth_system.php';
require_once __DIR__ . '/includes/csrf.php';

$auth = new AuthSystem($pdo);
$loggedIn = $auth->isLoggedIn();
$user_id  = $loggedIn ? (int)$_SESSION['user_id'] : 0;

$auditRows = [];
if ($loggedIn) {
    $stmt = $pdo->prepare("SELECT action, details, ip_address, created_at FROM user_audit_log WHERE user_id = ? ORDER BY created_at DESC LIMIT 30");
    $stmt->execute([$user_id]);
    $auditRows = $stmt->fetchAll();
}

$__title = 'Gizlilik & Verilerim';
$__desc  = 'Hesap verilerinizi indirebilir, silebilir ve gizlilik tercihlerinizi yönetebilirsiniz.';
require __DIR__ . '/includes/public_head.php';
?>
<h1>🔒 Gizlilik & Verilerim</h1>
<p class="ub-lead">Senin verin, senin kontrolün. Aşağıda ÜniBütçe'nin hangi verini sakladığını, nasıl kullandığını ve istediğin zaman nasıl silebileceğini göreceksin.</p>

<h2>Neyi Saklıyoruz?</h2>
<div class="ub-card">
    <ul>
        <li><b>Hesap bilgileri:</b> Ad, e-posta, kullanıcı adı, üniversite</li>
        <li><b>Finansal işlemler:</b> Gelir/gider kayıtları, kategoriler, açıklamalar</li>
        <li><b>Tercihler:</b> Tema, para birimi, dashboard düzeni</li>
        <li><b>Güvenlik günlüğü:</b> Giriş, şifre değişikliği, veri dışa aktarma kayıtları (IP + tarih)</li>
        <li><b>Başarımlar & XP:</b> Gamification ilerlemen</li>
    </ul>
    <p style="margin:0; opacity:.8;"><b>Saklamadıklarımız:</b> Banka kartı bilgileri, üçüncü taraf API'lere aktarılan ham içerik (yalnızca anonim istatistik).</p>
</div>

<?php if ($loggedIn): ?>
<h2>Verilerimi İndir (GDPR)</h2>
<div class="ub-card">
    <p>Tüm kişisel verilerinin tam kopyasını JSON veya CSV olarak indirebilirsin.</p>
    <a href="api/export_data.php?format=json" class="btn btn-primary">📥 JSON İndir</a>
    <a href="api/export_data.php?format=csv"  class="btn btn-secondary">📄 CSV İndir</a>
</div>

<h2>Güvenlik Günlüğü (Son 30 Kayıt)</h2>
<div class="ub-card" style="padding:0; overflow:hidden;">
    <?php if (!$auditRows): ?>
        <p style="padding:20px; opacity:.6;">Henüz kayıt yok.</p>
    <?php else: ?>
        <table style="width:100%; border-collapse:collapse;">
            <thead><tr style="background:rgba(255,255,255,.04);">
                <th style="text-align:left; padding:12px;">Eylem</th>
                <th style="text-align:left; padding:12px;">IP</th>
                <th style="text-align:left; padding:12px;">Tarih</th>
            </tr></thead>
            <tbody>
            <?php foreach ($auditRows as $r): ?>
                <tr style="border-top:1px solid rgba(255,255,255,.06);">
                    <td style="padding:10px 12px; font-family:ui-monospace,monospace; font-size:.85rem;"><?= htmlspecialchars($r['action']) ?></td>
                    <td style="padding:10px 12px; opacity:.7; font-size:.85rem;"><?= htmlspecialchars($r['ip_address'] ?? '—') ?></td>
                    <td style="padding:10px 12px; opacity:.7; font-size:.85rem;"><?= htmlspecialchars($r['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<h2 style="color:#ff3b30;">⚠️ Hesabı Kalıcı Sil</h2>
<div class="ub-card" style="border-color:rgba(255,59,48,.3);">
    <p><b>Dikkat:</b> Bu işlem geri alınamaz. Tüm işlemlerin, bütçelerin, başarımların ve verilerin kalıcı olarak silinir.</p>
    <p>Onaylamak için kutuya tam olarak <code>HESABIMI SIL</code> yaz.</p>
    <form id="ubDeleteForm" style="display:flex; gap:10px; flex-wrap:wrap;">
        <input type="text" name="confirm" placeholder="HESABIMI SIL" required
               style="flex:1; min-width:240px; padding:12px; border-radius:10px; background:rgba(0,0,0,.4); color:#fff; border:1px solid rgba(255,255,255,.1);">
        <button type="submit" class="ub-danger-btn">Kalıcı Olarak Sil</button>
    </form>
    <div id="ubDeleteMsg" style="margin-top:12px;"></div>
</div>

<script>
document.getElementById('ubDeleteForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (!confirm('Son kez soruyoruz: hesabını kalıcı olarak silmek istediğine emin misin?')) return;
    const fd = new FormData(e.target);
    const r = await fetch('api/delete_account.php', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'X-CSRF-Token': window.CSRF_TOKEN || '' },
        body: fd
    }).then(r => r.json()).catch(() => ({ success:false, message:'Ağ hatası' }));
    const msg = document.getElementById('ubDeleteMsg');
    msg.textContent = r.message || (r.success ? 'Silindi.' : 'Hata.');
    msg.style.color = r.success ? '#39ff14' : '#ff3b30';
    if (r.success) setTimeout(() => location.href = 'index.php', 2000);
});
</script>
<?php else: ?>
<div class="ub-card" style="text-align:center;">
    <p>Verilerini görmek ve indirmek için <a href="index.php#hero">giriş yap</a>.</p>
</div>
<?php endif; ?>

<?php require __DIR__ . '/includes/public_foot.php'; ?>
