<?php
/**
 * ÜniBütçe — Dashboard Sidebar (V6.0 Premium)
 * Tüm dashboard sayfaları bu tek sidebar'ı kullanır.
 */
$currentPage = basename($_SERVER['PHP_SELF']);

// Detect if we're inside a subdirectory (e.g. /tools/)
$sidebarBase = '';
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
if (strpos($requestUri, '/tools/') !== false || strpos(dirname($_SERVER['PHP_SELF']), 'tools') !== false) {
    $sidebarBase = '../';
}

// Okunmamış bildirim sayısı
$unreadCount = 0;
if (isset($pdo) && isset($_SESSION['user_id'])) {
    try {
        $nStmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $nStmt->execute([$_SESSION['user_id']]);
        $unreadCount = (int)$nStmt->fetchColumn();
    } catch (Exception $e) { /* tablo yoksa sessizce devam */ }
}
?>
<!-- Mobile Hamburger -->
<button class="mobile-hamburger" id="mobileHamburger" style="display:none;" aria-label="Menü">
    <span class="bar"></span>
</button>
<!-- Sidebar Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<aside class="sidebar" id="dashSidebar">
    <a href="<?= $sidebarBase ?>index.php" class="sidebar-header" style="text-decoration: none;">
        <div class="logo-icon">Ü</div>
        <span class="logo-text">ÜniBütçe</span>
    </a>
    <nav class="sidebar-nav">
        <a href="<?= $sidebarBase ?>user_dashboard.php" class="nav-item <?php echo $currentPage === 'user_dashboard.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-grid-2"></i>
            <span>Genel Bakış</span>
        </a>
        <a href="<?= $sidebarBase ?>transactions.php" class="nav-item <?php echo $currentPage === 'transactions.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-arrows-left-right-to-line"></i>
            <span>Harcamalarım</span>
        </a>
        <a href="<?= $sidebarBase ?>subscriptions.php" class="nav-item <?php echo $currentPage === 'subscriptions.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-rotate"></i>
            <span>Abonelikler</span>
        </a>
        <a href="<?= $sidebarBase ?>budgets.php" class="nav-item <?php echo $currentPage === 'budgets.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-wallet"></i>
            <span>Bütçem</span>
        </a>
        <a href="<?= $sidebarBase ?>goals.php" class="nav-item <?php echo $currentPage === 'goals.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-bullseye"></i>
            <span>Hedeflerim</span>
        </a>
        <a href="<?= $sidebarBase ?>reports.php" class="nav-item <?php echo $currentPage === 'reports.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-chart-line"></i>
            <span>Raporlar</span>
        </a>
        <a href="<?= $sidebarBase ?>profile.php" class="nav-item <?php echo $currentPage === 'profile.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-user-gear"></i>
            <span>Profilim</span>
        </a>

        <div style="border-top: 1px solid rgba(255,255,255,0.06); margin: 0.5rem 0;"></div>

        <a href="<?= $sidebarBase ?>household.php" class="nav-item <?php echo $currentPage === 'household.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-people-roof"></i>
            <span>Ev Bütçesi</span>
        </a>
        <a href="<?= $sidebarBase ?>scholarships.php" class="nav-item <?php echo $currentPage === 'scholarships.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-graduation-cap"></i>
            <span>Burslarım</span>
        </a>
        <a href="<?= $sidebarBase ?>investments.php" class="nav-item <?php echo $currentPage === 'investments.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-chart-line"></i>
            <span>Yatırımlar <span style="font-size:.65rem; opacity:.7;">PRO</span></span>
        </a>
        <a href="<?= $sidebarBase ?>insights.php" class="nav-item <?php echo $currentPage === 'insights.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-lightbulb"></i>
            <span>İçgörüler 🔍</span>
        </a>
        <a href="<?= $sidebarBase ?>kyk.php" class="nav-item <?php echo $currentPage === 'kyk.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-calendar-check"></i>
            <span>KYK Takvimi</span>
        </a>
        <a href="<?= $sidebarBase ?>discounts.php" class="nav-item <?php echo $currentPage === 'discounts.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-ticket"></i>
            <span>Öğrenci İndirimleri</span>
        </a>
        <a href="<?= $sidebarBase ?>challenges.php" class="nav-item <?php echo $currentPage === 'challenges.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-bullseye"></i>
            <span>Meydan Okumalar</span>
        </a>
        <a href="<?= $sidebarBase ?>benchmark.php" class="nav-item <?php echo $currentPage === 'benchmark.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-chart-column"></i>
            <span>Arkadaş Ligi</span>
        </a>
        <a href="<?= $sidebarBase ?>housing_compare.php" class="nav-item <?php echo $currentPage === 'housing_compare.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-house-chimney"></i>
            <span>Yurt vs Ev</span>
        </a>
        <a href="<?= $sidebarBase ?>deposits.php" class="nav-item <?php echo $currentPage === 'deposits.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-sack-dollar"></i>
            <span>Depozitolar</span>
        </a>
        <a href="<?= $sidebarBase ?>academic.php" class="nav-item <?php echo $currentPage === 'academic.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-book-open"></i>
            <span>Akademik Araçlar</span>
        </a>
        <a href="<?= $sidebarBase ?>telegram.php" class="nav-item <?php echo $currentPage === 'telegram.php' ? 'active' : ''; ?>">
            <i class="fa-brands fa-telegram"></i>
            <span>Telegram Bot</span>
        </a>
        <a href="<?= $sidebarBase ?>privacy.php" class="nav-item <?php echo $currentPage === 'privacy.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-lock"></i>
            <span>Gizlilik</span>
        </a>

        <div style="border-top: 1px solid rgba(255,255,255,0.06); margin: 0.5rem 0;"></div>

        <!-- Streak Display -->
        <?php
        $streakDays = 0;
        if (isset($pdo) && isset($_SESSION['user_id'])) {
            try {
                $sStmt = $pdo->prepare("SELECT login_streak FROM users WHERE id = ?");
                $sStmt->execute([$_SESSION['user_id']]);
                $sRow = $sStmt->fetch();
                $streakDays = (int)($sRow['login_streak'] ?? 0);
            } catch (Exception $e) {}
        }
        if ($streakDays > 0):
        ?>
        <div class="nav-item streak-display" style="cursor:default; gap:8px;">
            <span class="streak-fire">🔥</span>
            <span style="font-family:var(--font-mono); font-weight:700; font-size:0.85rem; color:var(--accent-orange);"><?php echo $streakDays; ?> Gün</span>
        </div>
        <?php endif; ?>

        <!-- Bildirim Merkezi -->
        <div class="nav-item notification-trigger" id="notifTrigger" style="cursor:pointer; position:relative;">
            <i class="fa-solid fa-bell"></i>
            <span>Bildirimler</span>
            <?php if ($unreadCount > 0): ?>
                <span class="notif-badge"><?php echo $unreadCount; ?></span>
            <?php endif; ?>
        </div>
        <div class="notif-dropdown" id="notifDropdown">
            <div class="notif-dropdown-header">
                <span>🔔 Bildirimler</span>
                <button id="markAllReadBtn" style="background:none;border:none;color:var(--accent-cyan);cursor:pointer;font-size:0.75rem;">Tümünü Oku</button>
            </div>
            <div class="notif-list" id="notifList">
                <div class="notif-empty">Yükleniyor...</div>
            </div>
        </div>

        <div style="border-top: 1px solid rgba(255,255,255,0.06); margin: 0.5rem 0;"></div>

        <a href="<?= $sidebarBase ?>index.php" class="nav-item">
            <i class="fa-solid fa-house"></i>
            <span>Ana Sayfa</span>
        </a>
        <a href="<?= $sidebarBase ?>blog.php" class="nav-item <?php echo $currentPage === 'blog.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-newspaper"></i>
            <span>Blog</span>
        </a>

        <div style="border-top: 1px solid rgba(255,255,255,0.06); margin: 0.5rem 0;"></div>

        <div class="nav-section-label" style="padding: 6px 16px; font-size: 0.65rem; text-transform: uppercase; letter-spacing: 1.5px; color: rgba(255,255,255,0.3); font-weight: 700;">Hesaplayıcılar</div>
        <a href="<?= $sidebarBase ?>tools/index.php" class="nav-item <?php echo $currentPage === 'index.php' && strpos($_SERVER['REQUEST_URI'] ?? '', '/tools/') !== false ? 'active' : ''; ?>">
            <i class="fa-solid fa-calculator"></i>
            <span>Tüm Araçlar</span>
        </a>
        <a href="<?= $sidebarBase ?>tools/bilesik-faiz.php" class="nav-item <?php echo $currentPage === 'bilesik-faiz.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-chart-line"></i>
            <span>Bileşik Faiz</span>
        </a>
        <a href="<?= $sidebarBase ?>tools/asgari-ucret.php" class="nav-item <?php echo $currentPage === 'asgari-ucret.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-money-bill-wave"></i>
            <span>Asgari Ücret</span>
        </a>
        <a href="<?= $sidebarBase ?>tools/kpss-puan.php" class="nav-item <?php echo $currentPage === 'kpss-puan.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-file-pen"></i>
            <span>KPSS Puan</span>
        </a>
        <a href="<?= $sidebarBase ?>tools/erasmus-grant.php" class="nav-item <?php echo $currentPage === 'erasmus-grant.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-earth-europe"></i>
            <span>Erasmus Hibe</span>
        </a>
        <a href="<?= $sidebarBase ?>tools/kyk-yurt-puani.php" class="nav-item <?php echo $currentPage === 'kyk-yurt-puani.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-building"></i>
            <span>KYK Yurt Puanı</span>
        </a>
        <a href="<?= $sidebarBase ?>tools/finansal-iq.php" class="nav-item <?php echo $currentPage === 'finansal-iq.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-brain"></i>
            <span>Finansal IQ</span>
        </a>
        <a href="<?= $sidebarBase ?>tools/yuksek-lisans-roi.php" class="nav-item <?php echo $currentPage === 'yuksek-lisans-roi.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-graduation-cap"></i>
            <span>YL ROI</span>
        </a>
    </nav>

    <div class="sidebar-footer">
        <button id="logoutBtnSide" class="nav-item logout" style="width:100%; text-align:left; background:none; border:none; cursor:pointer; color:inherit;">
            <i class="fa-solid fa-arrow-right-from-bracket"></i>
            <span>Çıkış Yap</span>
        </button>
    </div>
</aside>

<style>
    .notif-badge {
        position: absolute;
        top: 8px;
        right: 12px;
        width: 20px;
        height: 20px;
        background: var(--accent-red);
        border-radius: 50%;
        font-size: 0.7rem;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-weight: 700;
        box-shadow: 0 0 8px var(--accent-red);
        animation: pulse-badge 2s infinite;
    }
    @keyframes pulse-badge {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.15); }
    }
    .notif-dropdown {
        display: none;
        position: absolute;
        left: 270px;
        bottom: 80px;
        width: 320px;
        max-height: 380px;
        background: rgba(10, 10, 18, 0.97);
        border: 1px solid var(--accent-cyan);
        border-radius: 10px;
        z-index: 200;
        box-shadow: 0 10px 40px rgba(0,0,0,0.7), inset 0 0 15px rgba(0,240,255,0.05);
        backdrop-filter: blur(20px);
        overflow: hidden;
    }
    .notif-dropdown.open { display: block; }
    .notif-dropdown-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 16px;
        border-bottom: 1px solid rgba(255,255,255,0.08);
        font-weight: 600;
        font-size: 0.85rem;
        color: var(--accent-cyan);
    }
    .notif-list {
        overflow-y: auto;
        max-height: 320px;
        padding: 8px;
    }
    .notif-item {
        padding: 10px 12px;
        border-radius: 6px;
        font-size: 0.82rem;
        display: flex;
        gap: 10px;
        align-items: flex-start;
        transition: background 0.2s;
        cursor: default;
    }
    .notif-item:hover { background: rgba(255,255,255,0.04); }
    .notif-item.unread { border-left: 2px solid var(--accent-cyan); }
    .notif-item .notif-icon { font-size: 1.2rem; flex-shrink: 0; }
    .notif-item .notif-text { flex: 1; }
    .notif-item .notif-title { font-weight: 600; color: var(--text-primary); }
    .notif-item .notif-time { font-size: 0.7rem; color: var(--text-muted); margin-top: 3px; }
    .notif-empty { text-align: center; padding: 30px; color: var(--text-muted); font-size: 0.85rem; }

    [data-theme="light"] .notif-dropdown {
        background: rgba(255,255,255,0.97);
        border-color: rgba(0,100,200,0.3);
    }
</style>

<script>
    const sidebarBase = '<?= $sidebarBase ?>';

    // Logout
    document.getElementById('logoutBtnSide').addEventListener('click', async () => {
        if (!confirm('Çıkış yapmak istediğinize emin misiniz?')) return;
        const formData = new FormData();
        formData.append('action', 'logout');
        try {
            await fetch(sidebarBase + 'api/auth_handler.php', { method: 'POST', body: formData });
            window.location.href = sidebarBase + 'index.php';
        } catch (error) {
            console.error('Logout failed:', error);
            window.location.href = sidebarBase + 'index.php';
        }
    });

    // Always force navy (light) theme
    document.documentElement.setAttribute('data-theme', 'light');
    localStorage.setItem('ub-theme', 'light');

    // Notifications
    const notifTrigger = document.getElementById('notifTrigger');
    const notifDropdown = document.getElementById('notifDropdown');
    const notifList = document.getElementById('notifList');

    notifTrigger.addEventListener('click', () => {
        notifDropdown.classList.toggle('open');
        if (notifDropdown.classList.contains('open')) loadNotifications();
    });

    document.addEventListener('click', (e) => {
        if (!notifTrigger.contains(e.target) && !notifDropdown.contains(e.target)) {
            notifDropdown.classList.remove('open');
        }
    });

    async function loadNotifications() {
        try {
            const resp = await fetch(sidebarBase + 'api/notifications.php?action=list');
            const data = await resp.json();
            if (data.length === 0) {
                notifList.innerHTML = '<div class="notif-empty">Henüz bildirim yok 🎉</div>';
                return;
            }
            notifList.innerHTML = data.map(n => `
                <div class="notif-item ${n.is_read == 0 ? 'unread' : ''}">
                    <span class="notif-icon">${n.type === 'warning' ? '⚠️' : n.type === 'success' ? '✅' : '🔔'}</span>
                    <div class="notif-text">
                        <div class="notif-title">${n.title}</div>
                        <div class="notif-time">${n.created_at}</div>
                    </div>
                </div>
            `).join('');
        } catch(e) {
            notifList.innerHTML = '<div class="notif-empty">Bildirimler yüklenemedi</div>';
        }
    }

    document.getElementById('markAllReadBtn')?.addEventListener('click', async () => {
        const fd = new FormData();
        fd.append('action', 'mark_all_read');
        if (window.CSRF_TOKEN) fd.append('csrf_token', window.CSRF_TOKEN);
        await fetch(sidebarBase + 'api/notifications.php', { method: 'POST', body: fd });
        const badge = document.querySelector('.notif-badge');
        if (badge) badge.remove();
        loadNotifications();
    });

    // ── Mobile Sidebar Toggle ──
    const hamburger = document.getElementById('mobileHamburger');
    const dashSidebar = document.getElementById('dashSidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    function openMobileSidebar() {
        dashSidebar.classList.add('mobile-open');
        sidebarOverlay.classList.add('active');
        hamburger.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeMobileSidebar() {
        dashSidebar.classList.remove('mobile-open');
        sidebarOverlay.classList.remove('active');
        hamburger.classList.remove('active');
        document.body.style.overflow = '';
    }

    hamburger.addEventListener('click', () => {
        if (dashSidebar.classList.contains('mobile-open')) {
            closeMobileSidebar();
        } else {
            openMobileSidebar();
        }
    });

    sidebarOverlay.addEventListener('click', closeMobileSidebar);

    // Close sidebar when a nav item is clicked (mobile)
    dashSidebar.querySelectorAll('.nav-item').forEach(item => {
        item.addEventListener('click', () => {
            if (window.innerWidth <= 768) closeMobileSidebar();
        });
    });

    // Swipe to close sidebar
    let touchStartX = 0;
    dashSidebar.addEventListener('touchstart', (e) => {
        touchStartX = e.touches[0].clientX;
    }, { passive: true });

    dashSidebar.addEventListener('touchend', (e) => {
        const touchEndX = e.changedTouches[0].clientX;
        if (touchStartX - touchEndX > 80) closeMobileSidebar();
    }, { passive: true });
</script>

<!-- Mobile Bottom Navigation -->
<nav class="mobile-bottom-nav" id="mobileBottomNav">
    <a href="<?= $sidebarBase ?>user_dashboard.php" class="<?php echo $currentPage === 'user_dashboard.php' ? 'active' : ''; ?>">
        <i class="fa-solid fa-grid-2"></i>
        <span>Panel</span>
    </a>
    <a href="<?= $sidebarBase ?>transactions.php" class="<?php echo $currentPage === 'transactions.php' ? 'active' : ''; ?>">
        <i class="fa-solid fa-arrows-left-right-to-line"></i>
        <span>Harcama</span>
    </a>
    <a href="<?= $sidebarBase ?>budgets.php" class="<?php echo $currentPage === 'budgets.php' ? 'active' : ''; ?>">
        <i class="fa-solid fa-wallet"></i>
        <span>Bütçe</span>
    </a>
    <a href="<?= $sidebarBase ?>goals.php" class="<?php echo $currentPage === 'goals.php' ? 'active' : ''; ?>">
        <i class="fa-solid fa-bullseye"></i>
        <span>Hedefler</span>
    </a>
    <a href="<?= $sidebarBase ?>profile.php" class="<?php echo $currentPage === 'profile.php' ? 'active' : ''; ?>">
        <i class="fa-solid fa-user-gear"></i>
        <span>Profil</span>
    </a>
</nav>
