    </div><!-- /.adm-content -->
</main><!-- /.adm-main -->

<!-- ══ TOAST CONTAINER ══ -->
<div class="adm-toast-container" id="admToastContainer"></div>

<!-- ══ CONFIRM MODAL ══ -->
<div class="adm-modal-overlay" id="admConfirmModal">
    <div class="adm-modal adm-modal-sm">
        <div class="adm-modal-body" style="text-align:center; padding: 32px 24px;">
            <div class="adm-confirm-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
            <div class="adm-confirm-title" id="admConfirmTitle">Emin misiniz?</div>
            <p class="adm-confirm-msg" id="admConfirmMsg">Bu işlem geri alınamaz.</p>
        </div>
        <div class="adm-modal-footer">
            <button class="adm-btn adm-btn-ghost" onclick="admCloseConfirm()">
                <i class="fa-solid fa-xmark"></i> İptal
            </button>
            <button class="adm-btn adm-btn-danger" id="admConfirmOkBtn">
                <i class="fa-solid fa-trash"></i> Evet, Devam Et
            </button>
        </div>
    </div>
</div>

<script>
/* ═══════════════════════════════
   Global Admin Utilities
═══════════════════════════════ */

// ── Clock ──
(function(){
    function tick(){
        const now = new Date();
        const pad = n => String(n).padStart(2,'0');
        const s = `${pad(now.getHours())}:${pad(now.getMinutes())}:${pad(now.getSeconds())}`;
        const el = document.getElementById('admClock');
        if(el) el.textContent = s;
    }
    tick(); setInterval(tick, 1000);
})();

// ── Toasts ──
function admToast(type, title, msg, duration = 4000) {
    const icons = {
        success: 'fa-circle-check',
        error:   'fa-circle-xmark',
        info:    'fa-circle-info',
        warning: 'fa-triangle-exclamation'
    };
    const container = document.getElementById('admToastContainer');
    const toast = document.createElement('div');
    toast.className = `adm-toast ${type}`;
    toast.innerHTML = `
        <div class="adm-toast-icon"><i class="fa-solid ${icons[type] || icons.info}"></i></div>
        <div class="adm-toast-content">
            <div class="adm-toast-title">${title}</div>
            ${msg ? `<div class="adm-toast-msg">${msg}</div>` : ''}
        </div>
        <span class="adm-toast-close" onclick="this.closest('.adm-toast').remove()">
            <i class="fa-solid fa-xmark"></i>
        </span>
    `;
    container.appendChild(toast);
    setTimeout(() => {
        toast.classList.add('hiding');
        setTimeout(() => toast.remove(), 320);
    }, duration);
}

// ── Modal helpers ──
function admOpenModal(id) {
    const m = document.getElementById(id);
    if(m) m.classList.add('open');
}
function admCloseModal(id) {
    const m = document.getElementById(id);
    if(m) m.classList.remove('open');
}

// Close modal on overlay click
document.addEventListener('click', e => {
    if(e.target.classList.contains('adm-modal-overlay')) {
        e.target.classList.remove('open');
    }
});
// Close modal on Escape
document.addEventListener('keydown', e => {
    if(e.key === 'Escape') {
        document.querySelectorAll('.adm-modal-overlay.open').forEach(m => m.classList.remove('open'));
    }
});

// ── Confirm Dialog ──
let _confirmCallback = null;
function admConfirm(title, msg, callback, okLabel = 'Evet, Devam Et', okIcon = 'fa-trash') {
    document.getElementById('admConfirmTitle').textContent = title;
    document.getElementById('admConfirmMsg').textContent   = msg;
    const okBtn = document.getElementById('admConfirmOkBtn');
    okBtn.innerHTML = `<i class="fa-solid ${okIcon}"></i> ${okLabel}`;
    _confirmCallback = callback;
    admOpenModal('admConfirmModal');
}
document.getElementById('admConfirmOkBtn').addEventListener('click', () => {
    admCloseModal('admConfirmModal');
    if(typeof _confirmCallback === 'function') _confirmCallback();
    _confirmCallback = null;
});
function admCloseConfirm() { admCloseModal('admConfirmModal'); _confirmCallback = null; }

// ── API helper ──
async function admFetch(url, data) {
    const fd = new FormData();
    if(data) Object.entries(data).forEach(([k,v]) => fd.append(k,v));
    const res = await fetch(url, { method: 'POST', body: fd });
    return res.json();
}

// ── Sidebar collapse (small screens) ──
document.addEventListener('DOMContentLoaded', () => {
    Chart.defaults.color = 'rgba(200,210,255,0.5)';
    Chart.defaults.font.family = "'Inter', system-ui, sans-serif";
    Chart.defaults.plugins.legend.labels.boxWidth = 10;
    Chart.defaults.plugins.legend.labels.usePointStyle = true;
    Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(8,12,24,0.95)';
    Chart.defaults.plugins.tooltip.borderColor = 'rgba(0,240,255,0.2)';
    Chart.defaults.plugins.tooltip.borderWidth = 1;
    Chart.defaults.plugins.tooltip.padding = 12;
    Chart.defaults.plugins.tooltip.titleColor = '#fff';
    Chart.defaults.plugins.tooltip.bodyColor = 'rgba(200,210,255,0.7)';
});
</script>
</body>
</html>
