/* ============================================================
   ÜniBütçe — Multi-Tab Sync via BroadcastChannel
   Tab A adds expense → Tab B updates instantly.
   ============================================================ */
(function () {
    'use strict';
    if (!('BroadcastChannel' in window)) return;

    const ch = new BroadcastChannel('unibutce_sync');
    const UB = window.UB = window.UB || {};

    UB.sync = {
        broadcast(topic, payload) {
            try { ch.postMessage({ topic, payload, t: Date.now() }); } catch {}
        },
        on(topic, fn) {
            ch.addEventListener('message', e => {
                if (e.data?.topic === topic) fn(e.data.payload, e.data);
            });
        }
    };

    // Auto-reload dashboard signals
    UB.sync.on('transaction:added',  () => maybeRefresh('dashboard'));
    UB.sync.on('transaction:deleted', () => maybeRefresh('dashboard'));
    UB.sync.on('goal:updated',       () => maybeRefresh('goals'));
    UB.sync.on('logout',             () => location.href = 'index.php');

    function maybeRefresh(scope) {
        // Soft reload if user hasn't interacted in last 3s
        const last = UB._lastInteraction || 0;
        if (Date.now() - last < 3000) return;
        if (typeof window.refreshDashboard === 'function') {
            try { window.refreshDashboard(); return; } catch {}
        }
        // Visual nudge
        const toast = document.createElement('div');
        toast.textContent = '↻ Başka bir sekmede güncelleme yapıldı';
        toast.style.cssText = 'position:fixed;bottom:80px;right:24px;background:#0088ff;color:#fff;padding:10px 16px;border-radius:10px;z-index:9999;font-size:.85rem;cursor:pointer;box-shadow:0 10px 30px -6px rgba(0,136,255,.5)';
        toast.onclick = () => location.reload();
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 5000);
    }

    // Track activity
    ['click', 'keydown', 'mousemove'].forEach(ev =>
        addEventListener(ev, () => UB._lastInteraction = Date.now(), { passive: true }));
})();
