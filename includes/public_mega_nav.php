<?php
/**
 * ÜniBütçe — Public Mega Menu Navbar
 * Ana sayfa, blog ve diğer public sayfalar için
 * $navPrefix: Subdirectorydeyse '../' geçir
 */
$navPrefix = $navPrefix ?? '';
$loggedIn  = !empty($_SESSION['user_id']);

// User initials for logged-in
$pubInitials = '';
if ($loggedIn && !empty($_SESSION['user_name'])) {
    foreach (explode(' ', $_SESSION['user_name']) as $n) {
        if (!empty($n)) $pubInitials .= strtoupper($n[0]);
    }
    $pubInitials = substr($pubInitials, 0, 2);
}

$activePage = basename($_SERVER['PHP_SELF']);
?>
<nav class="pub-mega-nav" id="pubNavbar">
    <div class="pub-nav-inner">
        <!-- Logo -->
        <a href="<?= $navPrefix ?>index.php" class="pub-nav-logo">
            <svg class="pub-nav-logo-svg" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
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
            <span class="pub-nav-brand">ÜniBütçe</span>
        </a>

        <!-- Desktop Mega Menu -->
        <div class="pub-nav-menu" id="pubNavMenu">

            <!-- Platform -->
            <div class="pub-mega-item">
                <button class="pub-mega-trigger" data-pmenu="platform">
                    <span>Platform</span>
                    <i class="fa-solid fa-chevron-down pub-mega-arrow"></i>
                </button>
                <div class="pub-mega-panel" id="pmenu-platform">
                    <div class="pub-mega-panel-inner">
                        <div class="pub-mega-section-label"><i class="fa-solid fa-grid-2"></i> Ana Sayfa Bölümleri</div>
                        <div class="pub-mega-grid">
                            <a href="<?= $navPrefix ?>index.php#budget" class="pub-mega-link">
                                <i class="fa-solid fa-wallet"></i>
                                <div><span class="pml-title">Bütçe Hesaplayıcı</span><span class="pml-desc">Aylık gider planı</span></div>
                            </a>
                            <a href="<?= $navPrefix ?>index.php#cities" class="pub-mega-link">
                                <i class="fa-solid fa-city"></i>
                                <div><span class="pml-title">Şehir Karşılaştırma</span><span class="pml-desc">81 şehir verisi</span></div>
                            </a>
                            <a href="<?= $navPrefix ?>index.php#kyk" class="pub-mega-link">
                                <i class="fa-solid fa-graduation-cap"></i>
                                <div><span class="pml-title">KYK / Burs Simülatörü</span><span class="pml-desc">Gelir-gider dengesi</span></div>
                            </a>
                            <a href="<?= $navPrefix ?>index.php#ai-tips" class="pub-mega-link">
                                <i class="fa-solid fa-robot"></i>
                                <div><span class="pml-title">AI Öneriler</span><span class="pml-desc">Kişisel tasarruf tavsiyeleri</span></div>
                            </a>
                            <a href="<?= $navPrefix ?>index.php#tools" class="pub-mega-link">
                                <i class="fa-solid fa-screwdriver-wrench"></i>
                                <div><span class="pml-title">Mini Araçlar</span><span class="pml-desc">Hızlı hesaplamalar</span></div>
                            </a>
                            <a href="<?= $navPrefix ?>index.php#faq" class="pub-mega-link">
                                <i class="fa-solid fa-circle-question"></i>
                                <div><span class="pml-title">SSS</span><span class="pml-desc">Sık sorulan sorular</span></div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Panel (Dashboard) -->
            <div class="pub-mega-item">
                <button class="pub-mega-trigger" data-pmenu="panel">
                    <span>Panel</span>
                    <i class="fa-solid fa-chevron-down pub-mega-arrow"></i>
                </button>
                <div class="pub-mega-panel" id="pmenu-panel">
                    <div class="pub-mega-panel-inner">
                        <div class="pub-mega-section-label"><i class="fa-solid fa-wallet"></i> Finans Yönetimi</div>
                        <div class="pub-mega-grid">
                            <a href="<?= $navPrefix ?>user_dashboard.php" class="pub-mega-link">
                                <i class="fa-solid fa-grid-2"></i>
                                <div><span class="pml-title">Genel Bakış</span><span class="pml-desc">Dashboard & özet</span></div>
                            </a>
                            <a href="<?= $navPrefix ?>transactions.php" class="pub-mega-link">
                                <i class="fa-solid fa-arrows-left-right-to-line"></i>
                                <div><span class="pml-title">Harcamalarım</span><span class="pml-desc">Gelir & gider kayıtları</span></div>
                            </a>
                            <a href="<?= $navPrefix ?>budgets.php" class="pub-mega-link">
                                <i class="fa-solid fa-chart-pie"></i>
                                <div><span class="pml-title">Bütçem</span><span class="pml-desc">Aylık bütçe planı</span></div>
                            </a>
                            <a href="<?= $navPrefix ?>goals.php" class="pub-mega-link">
                                <i class="fa-solid fa-bullseye"></i>
                                <div><span class="pml-title">Hedeflerim</span><span class="pml-desc">Birikim hedefleri</span></div>
                            </a>
                            <a href="<?= $navPrefix ?>reports.php" class="pub-mega-link">
                                <i class="fa-solid fa-chart-line"></i>
                                <div><span class="pml-title">Raporlar</span><span class="pml-desc">Detaylı analiz</span></div>
                            </a>
                            <a href="<?= $navPrefix ?>investments.php" class="pub-mega-link">
                                <i class="fa-solid fa-coins"></i>
                                <div><span class="pml-title">Yatırımlar</span><span class="pml-desc">Portföy yönetimi</span></div>
                            </a>
                        </div>
                        <div class="pub-mega-section-label" style="margin-top:12px;"><i class="fa-solid fa-house"></i> Öğrenci Yaşamı</div>
                        <div class="pub-mega-grid">
                            <a href="<?= $navPrefix ?>household.php" class="pub-mega-link">
                                <i class="fa-solid fa-people-roof"></i>
                                <div><span class="pml-title">Ev Bütçesi</span><span class="pml-desc">Ortak harcamalar</span></div>
                            </a>
                            <a href="<?= $navPrefix ?>scholarships.php" class="pub-mega-link">
                                <i class="fa-solid fa-medal"></i>
                                <div><span class="pml-title">Burslarım</span><span class="pml-desc">Burs başvuru takibi</span></div>
                            </a>
                            <a href="<?= $navPrefix ?>kyk.php" class="pub-mega-link">
                                <i class="fa-solid fa-calendar-check"></i>
                                <div><span class="pml-title">KYK Takvimi</span><span class="pml-desc">Önemli tarihler</span></div>
                            </a>
                            <a href="<?= $navPrefix ?>discounts.php" class="pub-mega-link">
                                <i class="fa-solid fa-ticket"></i>
                                <div><span class="pml-title">Öğrenci İndirimleri</span><span class="pml-desc">Kampanya & fırsatlar</span></div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Hesaplayıcılar -->
            <div class="pub-mega-item">
                <button class="pub-mega-trigger" data-pmenu="hesap">
                    <span>Hesaplayıcılar</span>
                    <i class="fa-solid fa-chevron-down pub-mega-arrow"></i>
                </button>
                <div class="pub-mega-panel" id="pmenu-hesap">
                    <div class="pub-mega-panel-inner">
                        <div class="pub-mega-section-label"><i class="fa-solid fa-calculator"></i> Araçlar</div>
                        <div class="pub-mega-grid">
                            <a href="<?= $navPrefix ?>tools/bilesik-faiz.php" class="pub-mega-link">
                                <i class="fa-solid fa-chart-line"></i>
                                <div><span class="pml-title">Bileşik Faiz</span><span class="pml-desc">Yatırım simülasyonu</span></div>
                            </a>
                            <a href="<?= $navPrefix ?>tools/asgari-ucret.php" class="pub-mega-link">
                                <i class="fa-solid fa-money-bill-wave"></i>
                                <div><span class="pml-title">Asgari Ücret</span><span class="pml-desc">Net maaş hesabı</span></div>
                            </a>
                            <a href="<?= $navPrefix ?>tools/kpss-puan.php" class="pub-mega-link">
                                <i class="fa-solid fa-file-pen"></i>
                                <div><span class="pml-title">KPSS Puan</span><span class="pml-desc">Puan tahmini</span></div>
                            </a>
                            <a href="<?= $navPrefix ?>tools/erasmus-grant.php" class="pub-mega-link">
                                <i class="fa-solid fa-earth-europe"></i>
                                <div><span class="pml-title">Erasmus Hibe</span><span class="pml-desc">Hibe hesapla</span></div>
                            </a>
                            <a href="<?= $navPrefix ?>tools/kyk-yurt-puani.php" class="pub-mega-link">
                                <i class="fa-solid fa-building"></i>
                                <div><span class="pml-title">KYK Yurt Puanı</span><span class="pml-desc">Yurt yerleşme puanı</span></div>
                            </a>
                            <a href="<?= $navPrefix ?>tools/finansal-iq.php" class="pub-mega-link">
                                <i class="fa-solid fa-brain"></i>
                                <div><span class="pml-title">Finansal IQ</span><span class="pml-desc">Kendini test et</span></div>
                            </a>
                            <a href="<?= $navPrefix ?>tools/yuksek-lisans-roi.php" class="pub-mega-link">
                                <i class="fa-solid fa-graduation-cap"></i>
                                <div><span class="pml-title">YL ROI</span><span class="pml-desc">Yüksek lisans yatırımı</span></div>
                            </a>
                            <a href="<?= $navPrefix ?>tools/index.php" class="pub-mega-link" style="border:1px dashed rgba(0,240,255,0.2);">
                                <i class="fa-solid fa-grid-2"></i>
                                <div><span class="pml-title">Tüm Araçlar →</span><span class="pml-desc">Araç kataloğu</span></div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Blog -->
            <a href="<?= $navPrefix ?>blog.php" class="pub-nav-link <?= $activePage === 'blog.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-newspaper"></i> Blog
            </a>
        </div>

        <!-- Right Actions -->
        <div class="pub-nav-actions">
            <?php if ($loggedIn): ?>
                <div class="pub-mega-item">
                    <button class="pub-mega-trigger" data-pmenu="userbox" style="border: 1px solid rgba(255,255,255,0.15); border-radius: 99px; padding: 7px 16px;">
                        <span><?= htmlspecialchars($_SESSION['user_name'] ?? 'Kullanıcı') ?></span>
                        <i class="fa-solid fa-chevron-down pub-mega-arrow"></i>
                    </button>
                    <div class="pub-mega-panel pub-user-panel" id="pmenu-userbox">
                        <div class="pub-mega-panel-inner" style="padding: 12px;">
                            <a href="<?= $navPrefix ?>profile.php" class="pub-mega-link" style="padding: 10px;">
                                <i class="fa-solid fa-user"></i>
                                <div><span class="pml-title">Profilim</span></div>
                            </a>
                            <a href="<?= $navPrefix ?>logout.php" class="pub-mega-link" style="padding: 10px; margin-top: 4px;">
                                <i class="fa-solid fa-arrow-right-from-bracket" style="color:#ff3b30; background:rgba(255,59,48,0.1); box-shadow:none;"></i>
                                <div><span class="pml-title" style="color:#ff3b30;">Çıkış Yap</span></div>
                            </a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <button onclick="window.openAuthModal ? window.openAuthModal() : (window.location.href='<?= $navPrefix ?>index.php')" class="pub-nav-cta">
                    <i class="fa-solid fa-user-astronaut"></i> Giriş / Kayıt
                </button>
            <?php endif; ?>
            <!-- Mobile hamburger -->
            <button class="pub-hamburger" id="pubHamburger" aria-label="Menü">
                <span></span><span></span><span></span>
            </button>
        </div>
    </div>
</nav>
<div class="pub-nav-overlay" id="pubNavOverlay"></div>

<style>
/* ══ PUBLIC MEGA NAV ══════════════════════════════════════════════ */
.pub-mega-nav {
    position: fixed;
    top: 0; left: 0; right: 0;
    z-index: 1000;
    background: transparent;
    border-bottom: 1px solid transparent;
    transition: background 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease, backdrop-filter 0.3s ease;
}
.pub-mega-nav.scrolled {
    background: rgba(5, 5, 16, 0.82);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border-bottom: 1px solid rgba(255,255,255,0.06);
    box-shadow: 0 4px 30px rgba(0,0,0,0.5);
}
.pub-nav-inner {
    max-width: 1280px;
    margin: 0 auto;
    padding: 0 24px;
    height: 66px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
}
.pub-nav-logo {
    display: flex; align-items: center; gap: 10px;
    text-decoration: none; flex-shrink: 0;
}
.pub-nav-logo-svg { width: 36px; height: 36px; }
.pub-nav-brand {
    font-weight: 800; font-size: 1.1rem;
    color: #fff; letter-spacing: .5px;
}
/* Menu center */
.pub-nav-menu {
    display: flex; align-items: center; gap: 4px; flex: 1; justify-content: center;
}
.pub-mega-item { position: relative; }
.pub-mega-trigger {
    display: flex; align-items: center; gap: 6px;
    padding: 8px 14px;
    background: transparent;
    border: 1px solid transparent;
    border-radius: 10px;
    color: rgba(255,255,255,0.78);
    font-size: 0.88rem; font-weight: 600;
    cursor: pointer; font-family: inherit;
    transition: all 0.22s ease;
}
.pub-mega-trigger:hover {
    background: rgba(255,255,255,0.06);
    border-color: rgba(255,255,255,0.1);
    color: #fff;
}
.pub-mega-trigger.active {
    background: rgba(0,136,255,0.12);
    border-color: rgba(0,240,255,0.25);
    color: #fff;
}
.pub-mega-arrow {
    font-size: 0.58rem !important;
    opacity: .45;
    transition: transform .3s ease;
}
.pub-mega-trigger.active .pub-mega-arrow { transform: rotate(180deg); opacity: 1; }

/* Panel */
.pub-mega-panel {
    position: absolute;
    top: calc(100% + 8px);
    left: 50%;
    transform: translateX(-50%) translateY(10px);
    width: 480px;
    background: rgba(7, 12, 28, 0.97);
    backdrop-filter: blur(24px);
    border: 1px solid rgba(0,136,255,0.18);
    border-radius: 18px;
    box-shadow: 0 24px 70px rgba(0,0,0,0.6), 0 0 40px rgba(0,136,255,0.07);
    opacity: 0; visibility: hidden; pointer-events: none;
    transition: all 0.28s cubic-bezier(0.4,0,0.2,1);
    z-index: 1100;
}
.pub-mega-panel.open {
    opacity: 1; visibility: visible; pointer-events: auto;
    transform: translateX(-50%) translateY(0);
}
.pub-user-panel {
    transform: translateX(-80%) translateY(10px);
    width: 220px;
}
.pub-user-panel.open {
    transform: translateX(-80%) translateY(0);
}
.pub-mega-panel-inner { padding: 20px; }
.pub-mega-section-label {
    font-size: 0.68rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: 1.5px;
    color: rgba(0,240,255,0.8);
    padding: 0 8px 10px;
    margin-bottom: 6px;
    border-bottom: 1px solid rgba(0,136,255,0.1);
    display: flex; align-items: center; gap: 7px;
}
.pub-mega-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 3px;
}
.pub-mega-link {
    display: flex; align-items: center; gap: 10px;
    padding: 9px 10px;
    border-radius: 10px;
    text-decoration: none;
    color: rgba(230,241,255,0.85);
    border: 1px solid transparent;
    transition: all 0.2s ease;
}
.pub-mega-link:hover {
    background: rgba(0,136,255,0.09);
    border-color: rgba(0,136,255,0.15);
    color: #fff;
}
.pub-mega-link > i {
    width: 32px; height: 32px;
    display: flex; align-items: center; justify-content: center;
    background: rgba(0,136,255,0.08);
    border-radius: 7px;
    color: rgba(0,240,255,0.8);
    font-size: 0.78rem;
    flex-shrink: 0;
    transition: all .2s ease;
}
.pub-mega-link:hover > i {
    background: rgba(0,240,255,0.14);
    color: var(--accent-cyan, #00f0ff);
    box-shadow: 0 0 8px rgba(0,240,255,0.15);
}
.pml-title { display: block; font-size: 0.82rem; font-weight: 600; line-height: 1.2; }
.pml-desc  { display: block; font-size: 0.69rem; color: rgba(255,255,255,0.4); margin-top: 2px; }

/* Blog plain link */
.pub-nav-link {
    padding: 8px 14px;
    color: rgba(255,255,255,0.78);
    text-decoration: none;
    font-size: 0.88rem; font-weight: 600;
    border-radius: 10px;
    border: 1px solid transparent;
    display: flex; align-items: center; gap: 6px;
    transition: all .22s ease;
}
.pub-nav-link:hover { background: rgba(255,255,255,0.06); color: #fff; }
.pub-nav-link.active { color: var(--orbis-neon, #6fff00) !important; }

/* Right actions */
.pub-nav-actions { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }

.pub-nav-cta {
    position: relative;
    padding: 10px 24px;
    background: rgba(0, 240, 255, 0.1);
    color: var(--accent-cyan, #00f0ff);
    border: 1px solid rgba(0, 240, 255, 0.4);
    border-radius: 999px;
    font-size: 0.85rem; 
    font-weight: 700;
    cursor: pointer; 
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    overflow: hidden;
    backdrop-filter: blur(8px);
    display: flex;
    align-items: center;
    gap: 8px;
}
.pub-nav-cta::before {
    content: '';
    position: absolute;
    top: 0; left: -100%; width: 100%; height: 100%;
    background: linear-gradient(90deg, transparent, rgba(0,240,255,0.2), transparent);
    transition: all 0.5s ease;
}
.pub-nav-cta:hover { 
    transform: translateY(-2px); 
    background: rgba(0, 240, 255, 0.2);
    box-shadow: 0 8px 25px rgba(0, 240, 255, 0.2), inset 0 0 10px rgba(0, 240, 255, 0.1);
    border-color: rgba(0, 240, 255, 0.8);
    color: #fff;
}
.pub-nav-cta:hover::before {
    left: 100%;
}

/* Hamburger */
.pub-hamburger {
    display: none;
    flex-direction: column; gap: 5px;
    width: 38px; height: 38px;
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 10px;
    align-items: center; justify-content: center;
    cursor: pointer; padding: 8px;
}
.pub-hamburger span {
    display: block; width: 20px; height: 2px;
    background: #fff; border-radius: 2px;
    transition: all 0.3s ease;
}
.pub-hamburger.active span:nth-child(1) { transform: translateY(7px) rotate(45deg); }
.pub-hamburger.active span:nth-child(2) { opacity: 0; }
.pub-hamburger.active span:nth-child(3) { transform: translateY(-7px) rotate(-45deg); }

.pub-nav-overlay {
    display: none; position: fixed;
    inset: 0; background: rgba(0,0,0,0.55); z-index: 990;
    backdrop-filter: blur(3px);
}
.pub-nav-overlay.active { display: block; }

/* Mobile */
@media (max-width: 900px) {
    .pub-hamburger { display: flex; }
    .pub-nav-menu {
        position: fixed;
        top: 66px; left: 0; right: 0; bottom: 0;
        background: rgba(5,5,16,0.97);
        backdrop-filter: blur(20px);
        flex-direction: column;
        align-items: stretch;
        gap: 0; padding: 12px;
        overflow-y: auto;
        transform: translateX(-100%);
        transition: transform .32s ease;
        z-index: 995;
    }
    .pub-nav-menu.mob-open { transform: translateX(0); }
    .pub-mega-item { width: 100%; }
    .pub-mega-trigger {
        width: 100%; padding: 13px 14px;
        justify-content: space-between;
        border: 1px solid rgba(255,255,255,0.07);
        border-radius: 12px; margin-bottom: 4px;
    }
    .pub-mega-panel {
        position: static; transform: none;
        width: auto; margin: 0 0 8px;
        border-radius: 12px;
    }
    .pub-mega-panel.open { transform: none; }
    .pub-mega-grid { grid-template-columns: 1fr; }
    .pml-desc { display: none; }
    .pub-nav-link { padding: 13px 14px; border: 1px solid rgba(255,255,255,0.07); border-radius: 12px; margin-bottom: 4px;}
    .pub-nav-dashboard-btn span { display: none; }
}
/* Body top padding reset for public pages */
body:not(.dashboard-body) { padding-top: 66px; }
</style>

<script>
// ── Public Mega Menu ──
document.querySelectorAll('.pub-mega-trigger').forEach(trigger => {
    trigger.addEventListener('click', e => {
        e.stopPropagation();
        const panelId = 'pmenu-' + trigger.dataset.pmenu;
        const panel   = document.getElementById(panelId);
        const wasOpen = panel.classList.contains('open');

        document.querySelectorAll('.pub-mega-panel.open').forEach(p => p.classList.remove('open'));
        document.querySelectorAll('.pub-mega-trigger.active').forEach(t => t.classList.remove('active'));

        if (!wasOpen) {
            panel.classList.add('open');
            trigger.classList.add('active');
        }
    });
});

document.addEventListener('click', e => {
    if (!e.target.closest('.pub-mega-item')) {
        document.querySelectorAll('.pub-mega-panel.open').forEach(p => p.classList.remove('open'));
        document.querySelectorAll('.pub-mega-trigger.active').forEach(t => t.classList.remove('active'));
    }
});

// ── Hamburger ──
const pubHamburger  = document.getElementById('pubHamburger');
const pubNavMenu    = document.getElementById('pubNavMenu');
const pubNavOverlay = document.getElementById('pubNavOverlay');

pubHamburger?.addEventListener('click', () => {
    pubNavMenu.classList.toggle('mob-open');
    pubHamburger.classList.toggle('active');
    pubNavOverlay.classList.toggle('active');
    document.body.style.overflow = pubNavMenu.classList.contains('mob-open') ? 'hidden' : '';
});
pubNavOverlay?.addEventListener('click', () => {
    pubNavMenu.classList.remove('mob-open');
    pubHamburger.classList.remove('active');
    pubNavOverlay.classList.remove('active');
    document.body.style.overflow = '';
});

// ── Scroll effect ──
window.addEventListener('scroll', () => {
    document.getElementById('pubNavbar')?.classList.toggle('scrolled', window.scrollY > 20);
}, { passive: true });
</script>
