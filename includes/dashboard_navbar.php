<?php
/**
 * ÜniBütçe — Dashboard Mega Menu Navbar (V7.0)
 * Sidebar yerine tüm dashboard sayfaları bu navbar'ı kullanır.
 */
$currentPage = basename($_SERVER['PHP_SELF']);

// Detect if we're inside a subdirectory (e.g. /tools/)
$navBase = '';
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
if (strpos($requestUri, '/tools/') !== false || strpos(dirname($_SERVER['PHP_SELF']), 'tools') !== false) {
    $navBase = '../';
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

// Streak
$streakDays = 0;
if (isset($pdo) && isset($_SESSION['user_id'])) {
    try {
        $sStmt = $pdo->prepare("SELECT login_streak FROM users WHERE id = ?");
        $sStmt->execute([$_SESSION['user_id']]);
        $sRow = $sStmt->fetch();
        $streakDays = (int)($sRow['login_streak'] ?? 0);
    } catch (Exception $e) {}
}

// User initials
$navInitials = '';
if (isset($_SESSION['user_name'])) {
    foreach (explode(' ', $_SESSION['user_name']) as $n) {
        if (!empty($n)) $navInitials .= strtoupper($n[0]);
    }
    $navInitials = substr($navInitials, 0, 2);
}
?>

<nav class="dash-navbar" id="dashNavbar">
    <div class="dash-navbar-inner">
        <!-- Logo -->
        <a href="<?= $navBase ?>index.php" class="dash-nav-logo" style="display:flex; align-items:center; gap:10px;">
            <svg class="pub-nav-logo-svg" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg" style="width: 32px; height: 32px;">
                <defs>
                    <filter id="pub-logo-glow" x="-30%" y="-30%" width="160%" height="160%">
                        <feGaussianBlur stdDeviation="3.5" result="blur"/>
                        <feComponentTransfer in="blur" result="glow1"><feFuncA type="linear" slope="1.5"/></feComponentTransfer>
                        <feMerge><feMergeNode in="glow1"/><feMergeNode in="SourceGraphic"/></feMerge>
                    </filter>
                </defs>
                <path d="M50 25 L15 40 L50 55 L85 40 Z" fill="#FFFFFF" opacity="0.95"/>
                <path d="M30 46 L30 65 Q50 80 70 65 L70 46" stroke="#FFFFFF" stroke-width="4" stroke-linecap="round" fill="none" opacity="0.8"/>
                <path d="M85 40 L85 65" stroke="#FFFFFF" stroke-width="4" stroke-linecap="round" opacity="0.8"/>
                <circle cx="85" cy="68" r="4" fill="#6FFF00" filter="url(#pub-logo-glow)"/>
                <path d="M10 80 L35 55 L55 70 L95 25" stroke="#6FFF00" stroke-width="6" stroke-linecap="round" stroke-linejoin="round" filter="url(#pub-logo-glow)"/>
                <path d="M75 25 L95 25 L95 45" stroke="#6FFF00" stroke-width="6" stroke-linecap="round" stroke-linejoin="round" filter="url(#pub-logo-glow)"/>
            </svg>
            <span class="dash-logo-text">ÜniBütçe</span>
        </a>

        <!-- Mega Menu Items -->
        <div class="dash-nav-menu" id="dashNavMenu">
            <!-- 1. Finans Yönetimi -->
            <div class="mega-menu-item">
                <button class="mega-trigger" data-menu="finans">
                    <i class="fa-solid fa-wallet"></i>
                    <span>Finans</span>
                    <i class="fa-solid fa-chevron-down mega-arrow"></i>
                </button>
                <div class="mega-dropdown" id="mega-finans">
                    <div class="mega-dropdown-inner">
                        <div class="mega-col-header">
                            <i class="fa-solid fa-wallet"></i> Finans Yönetimi
                        </div>
                        <div class="mega-links-grid">
                            <a href="<?= $navBase ?>user_dashboard.php" class="mega-link <?= $currentPage === 'user_dashboard.php' ? 'active' : '' ?>">
                                <i class="fa-solid fa-grid-2"></i>
                                <div>
                                    <span class="mega-link-title">Genel Bakış</span>
                                    <span class="mega-link-desc">Dashboard & özet</span>
                                </div>
                            </a>
                            <a href="<?= $navBase ?>transactions.php" class="mega-link <?= $currentPage === 'transactions.php' ? 'active' : '' ?>">
                                <i class="fa-solid fa-arrows-left-right-to-line"></i>
                                <div>
                                    <span class="mega-link-title">Harcamalarım</span>
                                    <span class="mega-link-desc">Gelir & gider kayıtları</span>
                                </div>
                            </a>
                            <a href="<?= $navBase ?>budgets.php" class="mega-link <?= $currentPage === 'budgets.php' ? 'active' : '' ?>">
                                <i class="fa-solid fa-chart-pie"></i>
                                <div>
                                    <span class="mega-link-title">Bütçem</span>
                                    <span class="mega-link-desc">Aylık bütçe planı</span>
                                </div>
                            </a>
                            <a href="<?= $navBase ?>subscriptions.php" class="mega-link <?= $currentPage === 'subscriptions.php' ? 'active' : '' ?>">
                                <i class="fa-solid fa-rotate"></i>
                                <div>
                                    <span class="mega-link-title">Abonelikler</span>
                                    <span class="mega-link-desc">Yinelenen ödemeler</span>
                                </div>
                            </a>
                            <a href="<?= $navBase ?>reports.php" class="mega-link <?= $currentPage === 'reports.php' ? 'active' : '' ?>">
                                <i class="fa-solid fa-chart-line"></i>
                                <div>
                                    <span class="mega-link-title">Raporlar</span>
                                    <span class="mega-link-desc">Detaylı analiz</span>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Hedefler & Tasarruf -->
            <div class="mega-menu-item">
                <button class="mega-trigger" data-menu="hedefler">
                    <i class="fa-solid fa-bullseye"></i>
                    <span>Hedefler</span>
                    <i class="fa-solid fa-chevron-down mega-arrow"></i>
                </button>
                <div class="mega-dropdown" id="mega-hedefler">
                    <div class="mega-dropdown-inner">
                        <div class="mega-col-header">
                            <i class="fa-solid fa-bullseye"></i> Hedefler & Tasarruf
                        </div>
                        <div class="mega-links-grid">
                            <a href="<?= $navBase ?>goals.php" class="mega-link <?= $currentPage === 'goals.php' ? 'active' : '' ?>">
                                <i class="fa-solid fa-flag-checkered"></i>
                                <div>
                                    <span class="mega-link-title">Hedeflerim</span>
                                    <span class="mega-link-desc">Birikim hedefleri</span>
                                </div>
                            </a>
                            <a href="<?= $navBase ?>challenges.php" class="mega-link <?= $currentPage === 'challenges.php' ? 'active' : '' ?>">
                                <i class="fa-solid fa-trophy"></i>
                                <div>
                                    <span class="mega-link-title">Meydan Okumalar</span>
                                    <span class="mega-link-desc">Tasarruf yarışmaları</span>
                                </div>
                            </a>
                            <a href="<?= $navBase ?>insights.php" class="mega-link <?= $currentPage === 'insights.php' ? 'active' : '' ?>">
                                <i class="fa-solid fa-lightbulb"></i>
                                <div>
                                    <span class="mega-link-title">İçgörüler</span>
                                    <span class="mega-link-desc">AI destekli tavsiyeler</span>
                                </div>
                            </a>
                            <a href="<?= $navBase ?>deposits.php" class="mega-link <?= $currentPage === 'deposits.php' ? 'active' : '' ?>">
                                <i class="fa-solid fa-sack-dollar"></i>
                                <div>
                                    <span class="mega-link-title">Depozitolar</span>
                                    <span class="mega-link-desc">Depozito takibi</span>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. Yaşam -->
            <div class="mega-menu-item">
                <button class="mega-trigger" data-menu="yasam">
                    <i class="fa-solid fa-house"></i>
                    <span>Yaşam</span>
                    <i class="fa-solid fa-chevron-down mega-arrow"></i>
                </button>
                <div class="mega-dropdown" id="mega-yasam">
                    <div class="mega-dropdown-inner">
                        <div class="mega-col-header">
                            <i class="fa-solid fa-house"></i> Öğrenci Yaşamı
                        </div>
                        <div class="mega-links-grid">
                            <a href="<?= $navBase ?>household.php" class="mega-link <?= $currentPage === 'household.php' ? 'active' : '' ?>">
                                <i class="fa-solid fa-people-roof"></i>
                                <div>
                                    <span class="mega-link-title">Ev Bütçesi</span>
                                    <span class="mega-link-desc">Ortak harcamalar</span>
                                </div>
                            </a>
                            <a href="<?= $navBase ?>housing_compare.php" class="mega-link <?= $currentPage === 'housing_compare.php' ? 'active' : '' ?>">
                                <i class="fa-solid fa-house-chimney"></i>
                                <div>
                                    <span class="mega-link-title">Yurt vs Ev</span>
                                    <span class="mega-link-desc">Konut karşılaştırma</span>
                                </div>
                            </a>
                            <a href="<?= $navBase ?>scholarships.php" class="mega-link <?= $currentPage === 'scholarships.php' ? 'active' : '' ?>">
                                <i class="fa-solid fa-graduation-cap"></i>
                                <div>
                                    <span class="mega-link-title">Burslarım</span>
                                    <span class="mega-link-desc">Burs başvuru takibi</span>
                                </div>
                            </a>
                            <a href="<?= $navBase ?>kyk.php" class="mega-link <?= $currentPage === 'kyk.php' ? 'active' : '' ?>">
                                <i class="fa-solid fa-calendar-check"></i>
                                <div>
                                    <span class="mega-link-title">KYK Takvimi</span>
                                    <span class="mega-link-desc">KYK önemli tarihler</span>
                                </div>
                            </a>
                            <a href="<?= $navBase ?>discounts.php" class="mega-link <?= $currentPage === 'discounts.php' ? 'active' : '' ?>">
                                <i class="fa-solid fa-ticket"></i>
                                <div>
                                    <span class="mega-link-title">Öğrenci İndirimleri</span>
                                    <span class="mega-link-desc">Kampanya & fırsatlar</span>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4. Hesaplayıcılar -->
            <div class="mega-menu-item">
                <button class="mega-trigger" data-menu="araclar">
                    <i class="fa-solid fa-calculator"></i>
                    <span>Araçlar</span>
                    <i class="fa-solid fa-chevron-down mega-arrow"></i>
                </button>
                <div class="mega-dropdown" id="mega-araclar">
                    <div class="mega-dropdown-inner">
                        <div class="mega-col-header">
                            <i class="fa-solid fa-calculator"></i> Hesaplayıcılar
                        </div>
                        <div class="mega-links-grid">
                            <a href="<?= $navBase ?>tools/index.php" class="mega-link <?= ($currentPage === 'index.php' && strpos($requestUri, '/tools/') !== false) ? 'active' : '' ?>">
                                <i class="fa-solid fa-grid-2"></i>
                                <div>
                                    <span class="mega-link-title">Tüm Araçlar</span>
                                    <span class="mega-link-desc">Araç kataloğu</span>
                                </div>
                            </a>
                            <a href="<?= $navBase ?>tools/bilesik-faiz.php" class="mega-link <?= $currentPage === 'bilesik-faiz.php' ? 'active' : '' ?>">
                                <i class="fa-solid fa-chart-line"></i>
                                <div>
                                    <span class="mega-link-title">Bileşik Faiz</span>
                                    <span class="mega-link-desc">Yatırım simülasyonu</span>
                                </div>
                            </a>
                            <a href="<?= $navBase ?>tools/asgari-ucret.php" class="mega-link <?= $currentPage === 'asgari-ucret.php' ? 'active' : '' ?>">
                                <i class="fa-solid fa-money-bill-wave"></i>
                                <div>
                                    <span class="mega-link-title">Asgari Ücret</span>
                                    <span class="mega-link-desc">Net maaş hesapla</span>
                                </div>
                            </a>
                            <a href="<?= $navBase ?>tools/kpss-puan.php" class="mega-link <?= $currentPage === 'kpss-puan.php' ? 'active' : '' ?>">
                                <i class="fa-solid fa-file-pen"></i>
                                <div>
                                    <span class="mega-link-title">KPSS Puan</span>
                                    <span class="mega-link-desc">Puan tahmini</span>
                                </div>
                            </a>
                            <a href="<?= $navBase ?>tools/erasmus-grant.php" class="mega-link <?= $currentPage === 'erasmus-grant.php' ? 'active' : '' ?>">
                                <i class="fa-solid fa-earth-europe"></i>
                                <div>
                                    <span class="mega-link-title">Erasmus Hibe</span>
                                    <span class="mega-link-desc">Hibe hesapla</span>
                                </div>
                            </a>
                            <a href="<?= $navBase ?>tools/kyk-yurt-puani.php" class="mega-link <?= $currentPage === 'kyk-yurt-puani.php' ? 'active' : '' ?>">
                                <i class="fa-solid fa-building"></i>
                                <div>
                                    <span class="mega-link-title">KYK Yurt Puanı</span>
                                    <span class="mega-link-desc">Yurt yerleşme</span>
                                </div>
                            </a>
                            <a href="<?= $navBase ?>tools/finansal-iq.php" class="mega-link <?= $currentPage === 'finansal-iq.php' ? 'active' : '' ?>">
                                <i class="fa-solid fa-brain"></i>
                                <div>
                                    <span class="mega-link-title">Finansal IQ</span>
                                    <span class="mega-link-desc">Kendini test et</span>
                                </div>
                            </a>
                            <a href="<?= $navBase ?>tools/yuksek-lisans-roi.php" class="mega-link <?= $currentPage === 'yuksek-lisans-roi.php' ? 'active' : '' ?>">
                                <i class="fa-solid fa-graduation-cap"></i>
                                <div>
                                    <span class="mega-link-title">YL ROI</span>
                                    <span class="mega-link-desc">Yüksek lisans yatırımı</span>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 5. Keşfet -->
            <div class="mega-menu-item">
                <button class="mega-trigger" data-menu="kesfet">
                    <i class="fa-solid fa-compass"></i>
                    <span>Keşfet</span>
                    <i class="fa-solid fa-chevron-down mega-arrow"></i>
                </button>
                <div class="mega-dropdown" id="mega-kesfet">
                    <div class="mega-dropdown-inner">
                        <div class="mega-col-header">
                            <i class="fa-solid fa-compass"></i> Keşfet
                        </div>
                        <div class="mega-links-grid">
                            <a href="<?= $navBase ?>investments.php" class="mega-link <?= $currentPage === 'investments.php' ? 'active' : '' ?>">
                                <i class="fa-solid fa-chart-line"></i>
                                <div>
                                    <span class="mega-link-title">Yatırımlar</span>
                                    <span class="mega-link-desc">Portföy yönetimi</span>
                                </div>
                            </a>
                            <a href="<?= $navBase ?>benchmark.php" class="mega-link <?= $currentPage === 'benchmark.php' ? 'active' : '' ?>">
                                <i class="fa-solid fa-chart-column"></i>
                                <div>
                                    <span class="mega-link-title">Arkadaş Ligi</span>
                                    <span class="mega-link-desc">Sıralama tablosu</span>
                                </div>
                            </a>
                            <a href="<?= $navBase ?>academic.php" class="mega-link <?= $currentPage === 'academic.php' ? 'active' : '' ?>">
                                <i class="fa-solid fa-book-open"></i>
                                <div>
                                    <span class="mega-link-title">Akademik Araçlar</span>
                                    <span class="mega-link-desc">GPA, kredi hesapla</span>
                                </div>
                            </a>
                            <a href="<?= $navBase ?>blog.php" class="mega-link <?= $currentPage === 'blog.php' ? 'active' : '' ?>">
                                <i class="fa-solid fa-newspaper"></i>
                                <div>
                                    <span class="mega-link-title">Blog</span>
                                    <span class="mega-link-desc">Finansal yazılar</span>
                                </div>
                            </a>
                            <a href="<?= $navBase ?>telegram.php" class="mega-link <?= $currentPage === 'telegram.php' ? 'active' : '' ?>">
                                <i class="fa-brands fa-telegram"></i>
                                <div>
                                    <span class="mega-link-title">Telegram Bot</span>
                                    <span class="mega-link-desc">Anlık bildirimler</span>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Side: Streak, Notifications, Profile, Logout -->
        <div class="dash-nav-actions">
            <?php if ($streakDays > 0): ?>
            <div class="dash-nav-streak" title="<?= $streakDays ?> günlük seri">
                <span class="streak-fire">🔥</span>
                <span class="streak-count"><?= $streakDays ?></span>
            </div>
            <?php endif; ?>

            <!-- Notifications -->
            <div class="dash-nav-notif" id="navNotifTrigger">
                <button class="dash-notif-btn" aria-label="Bildirimler">
                    <i class="fa-solid fa-bell"></i>
                    <?php if ($unreadCount > 0): ?>
                        <span class="dash-notif-badge"><?= $unreadCount ?></span>
                    <?php endif; ?>
                </button>
                <div class="dash-notif-dropdown" id="navNotifDropdown">
                    <div class="notif-dropdown-header">
                        <span>🔔 Bildirimler</span>
                        <button id="navMarkAllReadBtn">Tümünü Oku</button>
                    </div>
                    <div class="notif-list" id="navNotifList">
                        <div class="notif-empty">Yükleniyor...</div>
                    </div>
                </div>
            </div>

            <!-- Profile -->
            <a href="<?= $navBase ?>profile.php" class="dash-nav-profile" title="Profilim">
                <span><?= $navInitials ?></span>
            </a>

            <!-- Logout -->
            <a href="<?= $navBase ?>logout.php" class="dash-nav-logout" title="Çıkış Yap" onclick="return confirm('Çıkış yapmak istediğinize emin misiniz?');">
                <i class="fa-solid fa-arrow-right-from-bracket"></i>
            </a>

            <!-- Mobile Hamburger -->
            <button class="dash-hamburger" id="dashHamburger" aria-label="Menü">
                <span></span><span></span><span></span>
            </button>
        </div>
    </div>
</nav>

<!-- Mobile overlay -->
<div class="dash-nav-overlay" id="dashNavOverlay"></div>

<script>
const navBase = '<?= $navBase ?>';

// ── Mega Menu Toggle ──
document.querySelectorAll('.mega-trigger').forEach(trigger => {
    trigger.addEventListener('click', (e) => {
        e.stopPropagation();
        const menuId = 'mega-' + trigger.dataset.menu;
        const dropdown = document.getElementById(menuId);
        const wasOpen = dropdown.classList.contains('open');
        
        // Close all dropdowns
        document.querySelectorAll('.mega-dropdown.open').forEach(d => d.classList.remove('open'));
        document.querySelectorAll('.mega-trigger.active').forEach(t => t.classList.remove('active'));
        
        if (!wasOpen) {
            dropdown.classList.add('open');
            trigger.classList.add('active');
        }
    });
});

// Close dropdowns when clicking outside
document.addEventListener('click', (e) => {
    if (!e.target.closest('.mega-menu-item') && !e.target.closest('.dash-notif-dropdown')) {
        document.querySelectorAll('.mega-dropdown.open').forEach(d => d.classList.remove('open'));
        document.querySelectorAll('.mega-trigger.active').forEach(t => t.classList.remove('active'));
    }
});

// ── Mobile Hamburger ──
const dashHamburger = document.getElementById('dashHamburger');
const dashNavMenu = document.getElementById('dashNavMenu');
const dashNavOverlay = document.getElementById('dashNavOverlay');

dashHamburger?.addEventListener('click', () => {
    dashNavMenu.classList.toggle('mobile-open');
    dashHamburger.classList.toggle('active');
    dashNavOverlay.classList.toggle('active');
    document.body.style.overflow = dashNavMenu.classList.contains('mobile-open') ? 'hidden' : '';
});

dashNavOverlay?.addEventListener('click', () => {
    dashNavMenu.classList.remove('mobile-open');
    dashHamburger.classList.remove('active');
    dashNavOverlay.classList.remove('active');
    document.body.style.overflow = '';
});

// ── Notifications ──
const navNotifTrigger = document.getElementById('navNotifTrigger');
const navNotifDropdown = document.getElementById('navNotifDropdown');
const navNotifList = document.getElementById('navNotifList');

navNotifTrigger?.querySelector('.dash-notif-btn')?.addEventListener('click', (e) => {
    e.stopPropagation();
    navNotifDropdown.classList.toggle('open');
    if (navNotifDropdown.classList.contains('open')) loadNavNotifications();
});

document.addEventListener('click', (e) => {
    if (!navNotifTrigger?.contains(e.target)) {
        navNotifDropdown?.classList.remove('open');
    }
});

async function loadNavNotifications() {
    try {
        const resp = await fetch(navBase + 'api/notifications.php?action=list');
        const data = await resp.json();
        if (data.length === 0) {
            navNotifList.innerHTML = '<div class="notif-empty">Henüz bildirim yok 🎉</div>';
            return;
        }
        navNotifList.innerHTML = data.map(n => `
            <div class="notif-item ${n.is_read == 0 ? 'unread' : ''}">
                <span class="notif-icon">${n.type === 'warning' ? '⚠️' : n.type === 'success' ? '✅' : '🔔'}</span>
                <div class="notif-text">
                    <div class="notif-title">${n.title}</div>
                    <div class="notif-time">${n.created_at}</div>
                </div>
            </div>
        `).join('');
    } catch(e) {
        navNotifList.innerHTML = '<div class="notif-empty">Bildirimler yüklenemedi</div>';
    }
}

document.getElementById('navMarkAllReadBtn')?.addEventListener('click', async () => {
    const fd = new FormData();
    fd.append('action', 'mark_all_read');
    if (window.CSRF_TOKEN) fd.append('csrf_token', window.CSRF_TOKEN);
    await fetch(navBase + 'api/notifications.php', { method: 'POST', body: fd });
    const badge = document.querySelector('.dash-notif-badge');
    if (badge) badge.remove();
    loadNavNotifications();
});



// ── Force light theme (navy) ──
document.documentElement.setAttribute('data-theme', 'light');
localStorage.setItem('ub-theme', 'light');

// ── Navbar scroll effect ──
window.addEventListener('scroll', () => {
    document.getElementById('dashNavbar')?.classList.toggle('scrolled', window.scrollY > 10);
});
</script>
