// =============================================
// ANIMATIONS.JS — ÜniBütçe Micro-Animation Engine
// Ripple, Confetti, Toast, Parallax, Typing, CountUp
// =============================================

(function () {
    'use strict';

    // ══════════════════════════════
    // RIPPLE EFFECT — Material Design
    // ══════════════════════════════
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.ripple-btn, .btn-premium-submit, .quick-btn, .mini-tool-btn, .ob-btn');
        if (!btn) return;

        const rect = btn.getBoundingClientRect();
        const ripple = document.createElement('span');
        ripple.className = 'ripple-effect';
        const size = Math.max(rect.width, rect.height);
        ripple.style.width = ripple.style.height = size + 'px';
        ripple.style.left = (e.clientX - rect.left - size / 2) + 'px';
        ripple.style.top = (e.clientY - rect.top - size / 2) + 'px';
        btn.style.position = 'relative';
        btn.style.overflow = 'hidden';
        btn.appendChild(ripple);
        setTimeout(() => ripple.remove(), 600);
    });

    // ══════════════════════════════
    // TOAST NOTIFICATION SYSTEM
    // ══════════════════════════════
    let toastCounter = 0;
    window.showToast = function (message, type = 'info', duration = 4000) {
        const icons = {
            success: '✅',
            error: '❌',
            warning: '⚠️',
            info: 'ℹ️'
        };

        const toast = document.createElement('div');
        toast.className = `toast-notification toast-${type}`;
        toast.id = 'toast-' + (++toastCounter);
        toast.style.top = (24 + (toastCounter - 1) * 80) + 'px';
        toast.innerHTML = `
            <span class="toast-icon">${icons[type] || icons.info}</span>
            <span class="toast-content">${message}</span>
            <button class="toast-close" onclick="dismissToast('${toast.id}')">&times;</button>
        `;
        document.body.appendChild(toast);

        setTimeout(() => dismissToast(toast.id), duration);

        return toast.id;
    };

    window.dismissToast = function (id) {
        const toast = document.getElementById(id);
        if (!toast) return;
        toast.classList.add('toast-out');
        setTimeout(() => {
            toast.remove();
            toastCounter = Math.max(0, toastCounter - 1);
        }, 300);
    };

    // ══════════════════════════════
    // CONFETTI CELEBRATION
    // ══════════════════════════════
    window.launchConfetti = function (count = 60) {
        const colors = ['#00F0FF', '#7000FF', '#39FF14', '#ff9100', '#f42c92', '#0088ff', '#FFD700'];
        const shapes = ['circle', 'square'];

        for (let i = 0; i < count; i++) {
            const piece = document.createElement('div');
            piece.className = 'confetti-piece';
            const color = colors[Math.floor(Math.random() * colors.length)];
            const shape = shapes[Math.floor(Math.random() * shapes.length)];

            piece.style.left = Math.random() * 100 + 'vw';
            piece.style.top = '-10px';
            piece.style.background = color;
            piece.style.borderRadius = shape === 'circle' ? '50%' : '2px';
            piece.style.width = (Math.random() * 8 + 6) + 'px';
            piece.style.height = (Math.random() * 8 + 6) + 'px';
            piece.style.animationDuration = (Math.random() * 1.5 + 1.5) + 's';
            piece.style.animationDelay = (Math.random() * 0.5) + 's';

            document.body.appendChild(piece);
            setTimeout(() => piece.remove(), 3000);
        }
    };

    // ══════════════════════════════
    // FLOATING XP ANIMATION
    // ══════════════════════════════
    window.showXPGain = function (amount, x, y) {
        const el = document.createElement('div');
        el.className = 'xp-float';
        el.textContent = `+${amount} XP`;
        el.style.left = (x || window.innerWidth / 2) + 'px';
        el.style.top = (y || 80) + 'px';
        document.body.appendChild(el);
        setTimeout(() => el.remove(), 1500);
    };

    // ══════════════════════════════
    // PARALLAX SCROLL
    // ══════════════════════════════
    function initParallax() {
        const parallaxEls = document.querySelectorAll('.floating-el, .parallax-layer');
        if (!parallaxEls.length) return;

        let ticking = false;
        window.addEventListener('scroll', function () {
            if (!ticking) {
                requestAnimationFrame(function () {
                    const scrollY = window.scrollY;
                    parallaxEls.forEach(function (el, i) {
                        const speed = parseFloat(el.style.getPropertyValue('--parallax-speed')) || (0.02 + i * 0.015);
                        const yOffset = -(scrollY * speed);
                        el.style.transform = `translateY(${yOffset}px)`;
                    });
                    ticking = false;
                });
                ticking = true;
            }
        });
    }

    // ══════════════════════════════
    // TYPING ANIMATION
    // ══════════════════════════════
    window.initTypingAnimation = function (elementSelector, phrases, speed) {
        const el = document.querySelector(elementSelector);
        if (!el) return;

        speed = speed || { type: 80, delete: 40, pause: 2000 };
        let phraseIdx = 0;
        let charIdx = 0;
        let isDeleting = false;

        function tick() {
            const currentPhrase = phrases[phraseIdx];

            if (isDeleting) {
                el.textContent = currentPhrase.substring(0, charIdx - 1);
                charIdx--;
            } else {
                el.textContent = currentPhrase.substring(0, charIdx + 1);
                charIdx++;
            }

            el.classList.add('typing-cursor');

            let delay = isDeleting ? speed.delete : speed.type;

            if (!isDeleting && charIdx === currentPhrase.length) {
                delay = speed.pause;
                isDeleting = true;
            } else if (isDeleting && charIdx === 0) {
                isDeleting = false;
                phraseIdx = (phraseIdx + 1) % phrases.length;
                delay = 400;
            }

            setTimeout(tick, delay);
        }

        tick();
    };

    // ══════════════════════════════
    // COUNT UP ANIMATION
    // ══════════════════════════════
    window.countUp = function (element, target, duration, suffix) {
        duration = duration || 1500;
        suffix = suffix || '';
        const start = 0;
        const startTime = performance.now();

        function step(currentTime) {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 3); // easeOutCubic
            const current = Math.round(start + (target - start) * eased);

            if (typeof element === 'string') {
                element = document.querySelector(element);
            }
            if (element) {
                element.textContent = current.toLocaleString('tr-TR') + suffix;
            }

            if (progress < 1) {
                requestAnimationFrame(step);
            }
        }

        requestAnimationFrame(step);
    };

    // ══════════════════════════════
    // CHART REVEAL ON SCROLL
    // ══════════════════════════════
    function initChartReveal() {
        const charts = document.querySelectorAll('.chart-reveal, canvas');
        if (!charts.length) return;

        const observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.2 });

        charts.forEach(function (chart) {
            const wrapper = chart.closest('.glass-card') || chart.closest('.dashboard-chart-card');
            if (wrapper) {
                wrapper.classList.add('chart-reveal');
                observer.observe(wrapper);
            }
        });
    }

    // ══════════════════════════════
    // SKELETON LOADING HELPER
    // ══════════════════════════════
    window.showSkeleton = function (container, count) {
        count = count || 3;
        if (typeof container === 'string') container = document.querySelector(container);
        if (!container) return;

        let html = '';
        for (let i = 0; i < count; i++) {
            html += `
                <div class="skeleton skeleton-card" style="margin-bottom: 12px;"></div>
            `;
        }
        container.innerHTML = html;
    };

    window.hideSkeleton = function (container) {
        if (typeof container === 'string') container = document.querySelector(container);
        if (!container) return;
        container.querySelectorAll('.skeleton').forEach(function (s) { s.remove(); });
    };

    // ══════════════════════════════
    // SMOOTH NUMBER CHANGE
    // ══════════════════════════════
    window.animateValue = function (element, newValue, prefix, suffix) {
        prefix = prefix || '';
        suffix = suffix || '';
        if (typeof element === 'string') element = document.querySelector(element);
        if (!element) return;

        element.classList.add('value-pop');
        element.textContent = prefix + newValue + suffix;
        setTimeout(function () { element.classList.remove('value-pop'); }, 400);
    };

    // ══════════════════════════════
    // STREAK DISPLAY
    // ══════════════════════════════
    window.showStreak = function (days) {
        const container = document.querySelector('.streak-display');
        if (!container) return;
        container.innerHTML = `
            <span class="streak-fire">🔥</span>
            <span class="streak-count">${days} Gün Serisi!</span>
        `;
        container.style.display = 'flex';
    };

    // ══════════════════════════════
    // INIT ON DOM READY
    // ══════════════════════════════
    document.addEventListener('DOMContentLoaded', function () {
        initParallax();
        initChartReveal();

        // Add cascade-in to dashboard cards
        document.querySelectorAll('.bento-grid > .glass-card, .premium-widgets-row > .glass-card').forEach(function (card) {
            card.classList.add('cascade-in');
        });

        // Add hover-lift to glass cards
        document.querySelectorAll('.glass-card').forEach(function (card) {
            if (!card.closest('.bento-grid')) return; // only dashboard cards
            card.classList.add('hover-lift');
        });

        // Page enter animation for main content
        const mainContent = document.querySelector('.main-content');
        if (mainContent) {
            mainContent.classList.add('page-transition-enter');
        }
    });

})();
