<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth_system.php';
$auth = new AuthSystem($pdo);
if (!$auth->isLoggedIn()) { header('Location: index.php#hero'); exit; }
$__title = 'Telegram Bot Bağla';
$__desc  = 'WhatsApp gibi Telegram\'dan hızlıca gider/gelir gir — /gider 50 kahve.';
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
require __DIR__ . '/includes/seo_tools_head.php';
?>
<h1>💬 Telegram Bot</h1>
<p class="ub-lead">Uygulamayı açmadan, tek mesajla işlem gir. <code>/gider 50 kahve</code> yaz, olsun bitsin.</p>

<?php if (strpos(SITE_URL, 'localhost') !== false || strpos(SITE_URL, '127.0.0.1') !== false): ?>
<div class="ub-card" style="border-left:3px solid #ff9100; background:rgba(255,145,0,.08);">
    <b>⚠️ Yerel Geliştirme Notu:</b> Telegram webhook'u yalnızca HTTPS destekli public URL ile çalışır.
    Localhost'ta test etmek için <code>ngrok http 80</code> veya <code>cloudflared tunnel</code> kullanarak
    geçici bir public URL oluşturun, ardından Telegram <code>setWebhook</code> API'sini çağırın.
    <br><br>
    <code style="display:block; padding:8px 12px; background:rgba(0,0,0,.4); border-radius:6px; font-size:.85rem; margin-top:4px;">
        curl "https://api.telegram.org/bot&lt;TOKEN&gt;/setWebhook?url=https://&lt;PUBLIC_URL&gt;/api/telegram_webhook.php"
    </code>
</div>
<?php endif; ?>

<div class="ub-card" id="ubTgCard">
    <p style="opacity:.7;">Yükleniyor...</p>
</div>

<h2>📖 Komutlar</h2>
<div class="ub-card">
    <table style="width:100%; border-collapse:collapse;">
        <tr style="border-bottom:1px solid rgba(255,255,255,.08);">
            <td style="padding:10px 8px;"><code style="background:rgba(0,0,0,.4); padding:3px 8px; border-radius:4px;">/gider 50 kahve</code></td>
            <td style="padding:10px 8px; opacity:.75;">Hızlı gider ekle (kategori otomatik tahmin edilir)</td>
        </tr>
        <tr style="border-bottom:1px solid rgba(255,255,255,.08);">
            <td style="padding:10px 8px;"><code style="background:rgba(0,0,0,.4); padding:3px 8px; border-radius:4px;">/gelir 5000 maaş</code></td>
            <td style="padding:10px 8px; opacity:.75;">Gelir kaydet</td>
        </tr>
        <tr style="border-bottom:1px solid rgba(255,255,255,.08);">
            <td style="padding:10px 8px;"><code style="background:rgba(0,0,0,.4); padding:3px 8px; border-radius:4px;">/bakiye</code></td>
            <td style="padding:10px 8px; opacity:.75;">Bu ayın özeti (gelir, gider, net)</td>
        </tr>
        <tr>
            <td style="padding:10px 8px;"><code style="background:rgba(0,0,0,.4); padding:3px 8px; border-radius:4px;">/yardim</code></td>
            <td style="padding:10px 8px; opacity:.75;">Komut listesi</td>
        </tr>
    </table>
</div>

<script>
const api = (u, opts={}) => fetch(u, {credentials:'same-origin', headers:{'X-CSRF-Token':window.CSRF_TOKEN||'', ...(opts.headers||{})}, ...opts}).then(r=>r.json());

async function loadStatus() {
    const r = await api('api/telegram_link.php');
    const box = document.getElementById('ubTgCard');
    if (r.linked) {
        box.innerHTML = `
            <div style="display:flex; align-items:center; gap:12px;">
                <div style="font-size:2.5rem;">✅</div>
                <div style="flex:1;">
                    <h3 style="margin:0;">Bağlı</h3>
                    <div style="opacity:.7;">Bot aktif. İstediğin zaman <code>/yardim</code> yaz.</div>
                    <div style="opacity:.5; font-size:.8rem;">Bağlantı tarihi: ${new Date(r.linked_at).toLocaleDateString('tr-TR')}</div>
                </div>
                <button onclick="unlink()" class="ub-danger-btn" style="padding:8px 16px;">Bağlantıyı Kes</button>
            </div>`;
    } else {
        box.innerHTML = `
            <h3 style="margin-top:0;">Hesabını Telegram ile bağla</h3>
            <p style="opacity:.8;">Linki oluşturup aşağıdaki butona tıkla. Telegram açılacak, <code>Start</code> de ve botu ekle.</p>
            <button onclick="gen()" class="btn btn-primary" data-magnetic>🔗 Eşleştirme Linki Oluştur</button>
            <div id="ubTgLink" style="margin-top:16px;"></div>`;
    }
}

async function gen() {
    const fd = new FormData(); fd.append('action', 'generate');
    const r = await api('api/telegram_link.php', {method:'POST', body:fd});
    if (r.success) {
        document.getElementById('ubTgLink').innerHTML = `
            <div style="padding:14px; background:rgba(57,255,20,.08); border-left:3px solid #39ff14; border-radius:8px;">
                <a href="${r.deep_link}" target="_blank" rel="noopener" class="btn btn-primary" style="display:inline-block;" data-magnetic>📱 Telegram'da Botu Aç</a>
                <p style="margin:10px 0 0; opacity:.7; font-size:.85rem;">Ya da manuel: bot üzerinde <code>/start ${r.token}</code> yaz.</p>
            </div>`;
    }
}

async function unlink() {
    if (!confirm('Bağlantıyı kesmek istediğine emin misin?')) return;
    const fd = new FormData(); fd.append('action','unlink');
    await api('api/telegram_link.php', {method:'POST', body:fd});
    loadStatus();
}

loadStatus();
</script>

<?php require __DIR__ . '/includes/seo_tools_foot.php'; ?>
