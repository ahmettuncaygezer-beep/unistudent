/* ============================================================
   ÜniBütçe — Command Palette (Cmd/Ctrl + K)
   Fuzzy search over navigation + actions. Dependency-free.
   ============================================================ */
(function () {
    'use strict';

    const isLoggedIn = !!document.body?.dataset?.userId || !!window.CURRENT_USER_ID;

    const COMMANDS = [
        { icon: '🏠', label: 'Ana Sayfa',            hint: 'Home',      keywords: 'home anasayfa ana index',          run: () => UB.navigate?.('index.php') || (location.href = 'index.php') },
        { icon: '📊', label: 'Panel (Dashboard)',    hint: 'Dashboard', keywords: 'dashboard panel özet',              run: () => go('user_dashboard.php') },
        { icon: '💸', label: 'İşlemler',             hint: 'Transactions', keywords: 'transactions işlem gider gelir', run: () => go('transactions.php') },
        { icon: '🎯', label: 'Hedefler',              hint: 'Goals',     keywords: 'goals hedef tasarruf',              run: () => go('goals.php') },
        { icon: '📅', label: 'Abonelikler',           hint: 'Subs',      keywords: 'subscriptions abone netflix spotify', run: () => go('subscriptions.php') },
        { icon: '💰', label: 'Bütçe Planla',          hint: 'Budget',    keywords: 'budget bütçe planla',               run: () => go('budgets.php') },
        { icon: '📈', label: 'Raporlar',              hint: 'Reports',   keywords: 'reports rapor grafik',              run: () => go('reports.php') },
        { icon: '👤', label: 'Profilim',              hint: 'Profile',   keywords: 'profile profil hesap',              run: () => go('profile.php') },
        { icon: '📝', label: 'Blog',                  hint: 'Blog',      keywords: 'blog yazı makale',                  run: () => go('blog.php') },
        { icon: '🔍', label: 'İçgörüler & Anomaliler', hint: 'Insights',  keywords: 'insights içgörü anomali dijest',    run: () => go('insights.php') },
        { icon: '👥', label: 'Paylaşımlı Ev Bütçesi',  hint: 'Household', keywords: 'household ev arkadaş split bölüş',  run: () => go('household.php') },
        { icon: '🎓', label: 'Burs Takipçisi',         hint: 'Scholarships', keywords: 'scholarship burs başvuru',       run: () => go('scholarships.php') },
        { icon: '📈', label: 'Yatırım Portföyü',       hint: 'Investments', keywords: 'investment yatırım borsa bist crypto', run: () => go('investments.php') },
        { icon: '💎', label: 'ÜniBütçe Pro',           hint: 'Pricing',   keywords: 'pro pricing fiyat yükselt',          run: () => go('pricing.php') },
        { icon: '🗺️', label: 'Yol Haritası',          hint: 'Roadmap',   keywords: 'roadmap yol harita',                run: () => go('roadmap.php') },
        { icon: '📜', label: 'Değişiklik Günlüğü',    hint: 'Changelog', keywords: 'changelog güncelleme',              run: () => go('changelog.php') },
        { icon: '🔒', label: 'Gizlilik & Verilerim',  hint: 'Privacy',   keywords: 'privacy gizlilik gdpr export',      run: () => go('privacy.php') },
        { icon: '💚', label: 'Sistem Durumu',         hint: 'Status',    keywords: 'status durum uptime',               run: () => go('status.php') },
        { icon: '🎓', label: 'KYK Takvimi',            hint: 'KYK',       keywords: 'kyk burs kredi yatış ödeme',        run: () => go('kyk.php') },
        { icon: '🎟️', label: 'Öğrenci İndirimleri',    hint: 'Discounts', keywords: 'discounts indirim spotify jetbrains', run: () => go('discounts.php') },
        { icon: '🎯', label: 'Meydan Okumalar',        hint: 'Challenges',keywords: 'challenge tasarruf kahve market',   run: () => go('challenges.php') },
        { icon: '👥', label: 'Arkadaş Ligi',            hint: 'Benchmark', keywords: 'benchmark arkadaş ortalama',         run: () => go('benchmark.php') },
        { icon: '🏠', label: 'Yurt vs Ev',              hint: 'Housing',   keywords: 'housing yurt ev kira karşılaştır',   run: () => go('housing_compare.php') },
        { icon: '💰', label: 'Depozitolar',             hint: 'Deposits',  keywords: 'depozito ev sahibi',                 run: () => go('deposits.php') },
        { icon: '📚', label: 'Akademik Araçlar',       hint: 'Academic',  keywords: 'academic ders staj erasmus',         run: () => go('academic.php') },
        { icon: '💬', label: 'Telegram Bot',            hint: 'Telegram',  keywords: 'telegram bot',                       run: () => go('telegram.php') },
        { icon: '🛡️', label: 'KVKK',                   hint: 'KVKK',      keywords: 'kvkk gdpr aydınlatma',              run: () => go('kvkk.php') },

        // Actions
        { icon: '➕', label: 'Yeni Gider Ekle',      hint: 'Action',    keywords: 'add expense yeni gider',            run: () => triggerModal('expense') },
        { icon: '➕', label: 'Yeni Gelir Ekle',       hint: 'Action',    keywords: 'add income yeni gelir',             run: () => triggerModal('income') },
        { icon: '🎯', label: 'Yeni Hedef Oluştur',    hint: 'Action',    keywords: 'new goal hedef oluştur',            run: () => go('goals.php#new') },
        { icon: '🌐', label: 'Yurtdışı Modu Aç/Kapat',hint: 'Toggle',    keywords: 'abroad erasmus currency yurtdışı', run: () => toggleAbroad() },
        { icon: '🌙', label: 'Tema Değiştir',         hint: 'Theme',     keywords: 'theme dark light tema',             run: () => toggleTheme() },
        { icon: '🚪', label: 'Çıkış Yap',              hint: 'Logout',    keywords: 'logout çıkış signout',              run: () => { fetch('api/auth_handler.php?action=logout', {credentials:'same-origin'}).finally(() => location.href = 'index.php'); } },
    ];

    function go(url) { if (window.UB?.navigate) UB.navigate(url); else location.href = url; }
    function triggerModal(kind) {
        const fn = window[`open${kind[0].toUpperCase()}${kind.slice(1)}Modal`];
        if (typeof fn === 'function') return fn();
        go('transactions.php');
    }
    function toggleAbroad() {
        const cur = localStorage.getItem('ub_abroad_mode') === '1' ? 0 : 1;
        localStorage.setItem('ub_abroad_mode', cur);
        fetch('api/settings.php', {
            method: 'POST',
            headers: { 'Content-Type':'application/json', 'X-CSRF-Token': window.CSRF_TOKEN || '' },
            credentials: 'same-origin',
            body: JSON.stringify({ abroad_mode: cur })
        }).finally(() => location.reload());
    }
    function toggleTheme() {
        const html = document.documentElement;
        const next = html.dataset.theme === 'dark' ? 'light' : 'dark';
        html.dataset.theme = next;
        localStorage.setItem('ub_theme', next);
    }

    // ---------- Fuzzy match: score based on substring & subseq ----------
    function score(query, cmd) {
        const q = query.toLowerCase();
        const hay = (cmd.label + ' ' + cmd.keywords).toLowerCase();
        if (!q) return 1;
        if (hay.includes(q)) return 100 - hay.indexOf(q);
        // subsequence
        let qi = 0;
        for (let i = 0; i < hay.length && qi < q.length; i++) if (hay[i] === q[qi]) qi++;
        return qi === q.length ? 1 : 0;
    }

    // ---------- DOM ----------
    function build() {
        const back = document.createElement('div');
        back.className = 'ub-cmdk-backdrop';
        const box  = document.createElement('div');
        box.className = 'ub-cmdk';
        box.setAttribute('role', 'dialog');
        box.setAttribute('aria-label', 'Komut Paleti');
        box.innerHTML = `
            <input type="text" class="ub-cmdk-input" placeholder="Komut veya sayfa ara..." autocomplete="off" spellcheck="false" />
            <div class="ub-cmdk-list"></div>
            <div class="ub-cmdk-footer">
                <span><kbd class="ub-kbd">↑↓</kbd> gezin · <kbd class="ub-kbd">↵</kbd> seç · <kbd class="ub-kbd">Esc</kbd> kapat</span>
                <span>ÜniBütçe ⌘K</span>
            </div>`;
        document.body.append(back, box);

        const input = box.querySelector('.ub-cmdk-input');
        const list  = box.querySelector('.ub-cmdk-list');
        let active = 0;
        let filtered = COMMANDS.slice();

        function render() {
            if (!filtered.length) {
                list.innerHTML = '<div class="ub-cmdk-empty">Sonuç bulunamadı. Farklı bir kelime dene.</div>';
                return;
            }
            list.innerHTML = filtered.map((c, i) =>
                `<div class="ub-cmdk-item ${i === active ? 'ub-active' : ''}" data-idx="${i}">
                    <span class="ub-cmdk-item-icon">${c.icon}</span>
                    <span class="ub-cmdk-item-label">${c.label}</span>
                    <span class="ub-cmdk-item-hint">${c.hint}</span>
                </div>`).join('');
        }

        function filter(q) {
            const scored = COMMANDS.map(c => ({ c, s: score(q, c) })).filter(x => x.s > 0);
            scored.sort((a, b) => b.s - a.s);
            filtered = scored.map(x => x.c);
            active = 0;
            render();
        }

        input.addEventListener('input', () => filter(input.value));
        input.addEventListener('keydown', e => {
            if (e.key === 'ArrowDown') { e.preventDefault(); active = (active + 1) % filtered.length; render(); }
            else if (e.key === 'ArrowUp') { e.preventDefault(); active = (active - 1 + filtered.length) % filtered.length; render(); }
            else if (e.key === 'Enter') {
                e.preventDefault();
                const cmd = filtered[active];
                if (cmd) { close(); cmd.run(); UB.haptic?.(10); }
            } else if (e.key === 'Escape') { close(); }
        });

        list.addEventListener('click', e => {
            const it = e.target.closest('.ub-cmdk-item');
            if (!it) return;
            const cmd = filtered[+it.dataset.idx];
            if (cmd) { close(); cmd.run(); }
        });
        back.addEventListener('click', close);

        function open() {
            filter('');
            input.value = '';
            back.classList.add('ub-open');
            box.classList.add('ub-open');
            setTimeout(() => input.focus(), 50);
        }
        function close() {
            back.classList.remove('ub-open');
            box.classList.remove('ub-open');
        }

        window.UB = window.UB || {};
        UB.openCommandPalette = open;
        UB.closeCommandPalette = close;

        addEventListener('keydown', e => {
            if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
                e.preventDefault(); open();
            }
            if (e.key === '/' && document.activeElement?.tagName !== 'INPUT' &&
                document.activeElement?.tagName !== 'TEXTAREA' &&
                !document.activeElement?.isContentEditable) {
                e.preventDefault(); open();
            }
        });

        // Floating launcher
        const launcher = document.createElement('button');
        launcher.className = 'ub-cmdk-launcher';
        launcher.type = 'button';
        launcher.innerHTML = '⌘K <span style="opacity:.6">Hızlı Komut</span>';
        launcher.addEventListener('click', open);
        document.body.appendChild(launcher);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', build);
    } else {
        build();
    }
})();
