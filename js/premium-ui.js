/* ============================================================
   ÜniBütçe — Premium UI Layer (cursor, magnetic, 3D tilt, haptic)
   Vanilla JS, no dependencies, <6KB gzipped.
   ============================================================ */
(function () {
    'use strict';

    const prefersReduced = matchMedia('(prefers-reduced-motion: reduce)').matches;
    const isFinePointer  = matchMedia('(hover: hover) and (pointer: fine)').matches;
    if (prefersReduced) return;

    const UB = window.UB = window.UB || {};

    // ---------- Utility: lerp ----------
    const lerp = (a, b, t) => a + (b - a) * t;

    // ---------- Custom Cursor ----------
    function initCursor() {
        if (!isFinePointer) return;

        document.documentElement.classList.add('ub-premium-cursor');

        const dot  = document.createElement('div');
        const ring = document.createElement('div');
        dot.className  = 'ub-cursor-dot';
        ring.className = 'ub-cursor-ring';
        document.body.appendChild(dot);
        document.body.appendChild(ring);

        let mx = innerWidth / 2, my = innerHeight / 2;
        let rx = mx, ry = my;

        addEventListener('mousemove', e => {
            mx = e.clientX; my = e.clientY;
            dot.style.transform = `translate3d(${mx - 5}px, ${my - 5}px, 0)`;
        }, { passive: true });

        function animate() {
            rx = lerp(rx, mx, 0.18);
            ry = lerp(ry, my, 0.18);
            ring.style.transform = `translate3d(${rx - 18}px, ${ry - 18}px, 0)`;
            requestAnimationFrame(animate);
        }
        animate();

        const HOVER_SEL = 'a, button, [role="button"], input, textarea, select, [data-cursor-hover]';
        document.addEventListener('mouseover', e => {
            if (e.target.closest?.(HOVER_SEL)) {
                dot.classList.add('ub-hover');
                ring.classList.add('ub-hover');
            }
        });
        document.addEventListener('mouseout', e => {
            if (e.target.closest?.(HOVER_SEL)) {
                dot.classList.remove('ub-hover');
                ring.classList.remove('ub-hover');
            }
        });
        addEventListener('mousedown', () => dot.classList.add('ub-click'));
        addEventListener('mouseup',   () => dot.classList.remove('ub-click'));
    }

    // ---------- Magnetic Buttons ----------
    function initMagnetic() {
        const els = document.querySelectorAll('[data-magnetic], .btn-primary, .nav-cta');
        els.forEach(el => {
            if (el.dataset.ubMagnetic) return;
            el.dataset.ubMagnetic = '1';
            el.classList.add('ub-magnetic');
            // Wrap inner if needed
            if (!el.querySelector('.ub-magnetic-inner')) {
                const inner = document.createElement('span');
                inner.className = 'ub-magnetic-inner';
                while (el.firstChild) inner.appendChild(el.firstChild);
                el.appendChild(inner);
            }
            const inner = el.querySelector('.ub-magnetic-inner');
            const STRENGTH = parseFloat(el.dataset.magneticStrength || '0.3');

            el.addEventListener('mousemove', e => {
                const r = el.getBoundingClientRect();
                const x = e.clientX - r.left - r.width / 2;
                const y = e.clientY - r.top  - r.height / 2;
                el.style.transform    = `translate(${x * STRENGTH}px, ${y * STRENGTH}px)`;
                inner.style.transform = `translate(${x * STRENGTH * 0.5}px, ${y * STRENGTH * 0.5}px)`;
            });
            el.addEventListener('mouseleave', () => {
                el.style.transform    = '';
                inner.style.transform = '';
            });
        });
    }

    // ---------- 3D Tilt Cards ----------
    function initTilt() {
        const cards = document.querySelectorAll('[data-tilt], .stat-card, .bento-card, .ub-achievement');
        cards.forEach(card => {
            if (card.dataset.ubTilt) return;
            card.dataset.ubTilt = '1';
            card.classList.add('ub-tilt');
            const MAX = parseFloat(card.dataset.tiltMax || '8');

            card.addEventListener('mousemove', e => {
                const r = card.getBoundingClientRect();
                const px = (e.clientX - r.left) / r.width;
                const py = (e.clientY - r.top)  / r.height;
                const rx = (0.5 - py) * MAX;
                const ry = (px - 0.5) * MAX;
                card.style.transform = `perspective(1000px) rotateX(${rx}deg) rotateY(${ry}deg) translateZ(0)`;
                card.style.setProperty('--mx', `${px * 100}%`);
                card.style.setProperty('--my', `${py * 100}%`);
                card.classList.add('ub-tilting');
            });
            card.addEventListener('mouseleave', () => {
                card.style.transform = '';
                card.classList.remove('ub-tilting');
            });
        });
    }

    // ---------- Scroll Reveal ----------
    function initReveal() {
        const els = document.querySelectorAll('.ub-reveal, [data-reveal]');
        if (!('IntersectionObserver' in window)) {
            els.forEach(el => el.classList.add('ub-revealed'));
            return;
        }
        const io = new IntersectionObserver(entries => {
            entries.forEach(e => {
                if (e.isIntersecting) {
                    e.target.classList.add('ub-revealed');
                    io.unobserve(e.target);
                }
            });
        }, { threshold: 0.14 });
        els.forEach(el => io.observe(el));
    }

    // ---------- Haptic Feedback ----------
    UB.haptic = function (pattern = 10) {
        try { navigator.vibrate?.(pattern); } catch { /* ignore */ }
    };

    // ---------- Subtle UI Sound ----------
    let audioCtx = null;
    UB.beep = function (freq = 880, duration = 80, vol = 0.04) {
        try {
            audioCtx = audioCtx || new (window.AudioContext || window.webkitAudioContext)();
            const o = audioCtx.createOscillator();
            const g = audioCtx.createGain();
            o.frequency.value = freq;
            g.gain.value      = vol;
            o.connect(g); g.connect(audioCtx.destination);
            o.start();
            o.stop(audioCtx.currentTime + duration / 1000);
            g.gain.exponentialRampToValueAtTime(0.0001, audioCtx.currentTime + duration / 1000);
        } catch { /* silent */ }
    };

    // ---------- View Transitions ----------
    UB.navigate = function (url) {
        if (document.startViewTransition) {
            document.startViewTransition(() => { location.href = url; });
        } else {
            location.href = url;
        }
    };

    // ---------- Auto-init ----------
    function boot() {
        initCursor();
        initMagnetic();
        initTilt();
        initReveal();

        // Re-init on DOM changes (e.g. modal open)
        new MutationObserver(() => {
            initMagnetic();
            initTilt();
            initReveal();
        }).observe(document.body, { childList: true, subtree: true });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
