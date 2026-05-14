/**
 * ÜniBütçe — Paylaşımlı JS yardımcıları
 * Kullanım: <script src="js/utils.js"></script> (app.js'ten önce)
 */
(function () {
    'use strict';

    /** CSRF token'ı meta veya window.CSRF_TOKEN'dan alır */
    function getCsrfToken() {
        if (window.CSRF_TOKEN) return window.CSRF_TOKEN;
        const m = document.querySelector('meta[name="csrf-token"]');
        return m ? m.getAttribute('content') : '';
    }

    /**
     * CSRF başlıklı fetch wrapper.
     * JSON body gönderirken Content-Type kendisi set eder.
     */
    async function apiFetch(url, opts = {}) {
        const headers = Object.assign(
            { 'X-Requested-With': 'XMLHttpRequest' },
            opts.headers || {}
        );
        const method = (opts.method || 'GET').toUpperCase();
        if (method !== 'GET' && method !== 'HEAD') {
            headers['X-CSRF-Token'] = getCsrfToken();
        }

        let body = opts.body;
        if (body && typeof body === 'object' && !(body instanceof FormData) && !(body instanceof Blob)) {
            headers['Content-Type'] = 'application/json';
            body = JSON.stringify(body);
        }
        if (body instanceof FormData && method !== 'GET' && !body.has('csrf_token')) {
            body.append('csrf_token', getCsrfToken());
        }

        const res = await fetch(url, Object.assign({}, opts, { method, headers, body, credentials: 'same-origin' }));
        const ct = res.headers.get('Content-Type') || '';
        const data = ct.includes('application/json') ? await res.json() : await res.text();
        if (!res.ok) {
            const err = new Error((data && data.message) || ('HTTP ' + res.status));
            err.status = res.status;
            err.data = data;
            throw err;
        }
        return data;
    }

    /** ₺ 10.000,00 şeklinde Türkçe para formatı */
    const CURRENCY_FMT = new Intl.NumberFormat('tr-TR', {
        style: 'currency',
        currency: 'TRY',
        maximumFractionDigits: 2,
    });
    function formatCurrency(n) {
        const v = Number(n);
        return isFinite(v) ? CURRENCY_FMT.format(v) : '—';
    }

    const DATE_FMT = new Intl.DateTimeFormat('tr-TR', { day: '2-digit', month: 'long', year: 'numeric' });
    function formatDate(d) {
        const date = d instanceof Date ? d : new Date(d);
        return isNaN(date.getTime()) ? '—' : DATE_FMT.format(date);
    }

    /**
     * Form submit button loading state + duplicate submit koruması.
     * Kullanım:
     *   bindSubmit(form, async (data) => { ... });
     */
    function bindSubmit(form, handler) {
        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            const btn = form.querySelector('button[type="submit"]');
            const original = btn ? btn.innerHTML : '';
            if (btn) {
                if (btn.dataset.busy === '1') return; // çift submit engeli
                btn.dataset.busy = '1';
                btn.disabled = true;
                btn.innerHTML = '<span class="ub-spinner" aria-hidden="true"></span> Gönderiliyor...';
            }
            try {
                const fd = new FormData(form);
                await handler(fd, form);
            } catch (err) {
                console.error(err);
                if (window.showToast) window.showToast(err.message || 'Bir hata oluştu.', 'error');
            } finally {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = original;
                    delete btn.dataset.busy;
                }
            }
        });
    }

    /** Global namespace'e aç */
    window.UB = Object.assign(window.UB || {}, {
        getCsrfToken,
        apiFetch,
        formatCurrency,
        formatDate,
        bindSubmit,
    });
})();
