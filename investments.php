<?php
declare(strict_types=1);
header('Content-Type: text/html; charset=UTF-8');
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth_system.php';
require_once __DIR__ . '/includes/audit.php';
require_once __DIR__ . '/includes/csrf.php';
$__csrf = csrf_token();

$auth = new AuthSystem($pdo);
if (!$auth->isLoggedIn()) { header('Location: index.php#hero'); exit; }
$currentUser = $auth->getCurrentUser();
$user_id = (int)$_SESSION['user_id'];
$isPro = is_pro($user_id);
?>
<!DOCTYPE html>
<html lang="tr" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yatırım Portföyü — ÜniBütçe</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/auth.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/dashboard.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/mobile.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/animations.css?v=<?php echo time(); ?>">
    <script src="js/animations.js?v=<?php echo time(); ?>" defer></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <meta name="csrf-token" content="<?= htmlspecialchars($__csrf, ENT_QUOTES) ?>">
    <script>window.CSRF_TOKEN = <?= json_encode($__csrf) ?>;</script>
    <script src="js/utils.js?v=<?php echo time(); ?>" defer></script>
    <style>
    /* ── Investment Portfolio Premium Styles ── */
    .inv-stats-grid {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 16px;
        margin-bottom: 24px;
    }
    .inv-stat-card {
        background: rgba(12, 26, 51, 0.85);
        border: 1px solid rgba(0, 136, 255, 0.2);
        border-radius: 16px;
        padding: 20px;
        text-align: center;
        position: relative;
        overflow: hidden;
        transition: all 0.3s ease;
    }
    .inv-stat-card:hover {
        border-color: rgba(0, 240, 255, 0.5);
        transform: translateY(-3px);
        box-shadow: 0 8px 30px rgba(0, 136, 255, 0.2);
    }
    .inv-stat-card .stat-label {
        font-size: 0.78rem;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 1.5px;
        margin-bottom: 8px;
    }
    .inv-stat-card .stat-value {
        font-family: var(--font-mono);
        font-size: 1.6rem;
        font-weight: 800;
        color: #fff;
    }
    .inv-stat-card .stat-sub {
        font-size: 0.82rem;
        margin-top: 4px;
        font-weight: 600;
    }
    .inv-stat-card .stat-icon {
        position: absolute;
        top: 12px;
        right: 14px;
        font-size: 1.4rem;
        opacity: 0.15;
    }
    .inv-stat-card.hero-card {
        grid-column: span 2;
        background: linear-gradient(135deg, rgba(0, 136, 255, 0.12), rgba(125, 92, 255, 0.08));
        border-color: rgba(0, 136, 255, 0.35);
    }
    .inv-stat-card.hero-card .stat-value {
        font-size: 2.2rem;
        background: linear-gradient(135deg, #fff, #a0d8ff);
        -webkit-background-clip: text; background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    /* Tabs */
    .inv-tabs {
        display: flex;
        gap: 4px;
        background: rgba(12, 26, 51, 0.6);
        border: 1px solid rgba(0, 136, 255, 0.15);
        border-radius: 14px;
        padding: 4px;
        margin-bottom: 24px;
        width: fit-content;
    }
    .inv-tab {
        padding: 10px 22px;
        border-radius: 10px;
        font-size: 0.88rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        color: var(--text-muted);
        border: none;
        background: transparent;
        white-space: nowrap;
    }
    .inv-tab:hover { color: #fff; background: rgba(255,255,255,0.05); }
    .inv-tab.active {
        background: linear-gradient(135deg, rgba(0, 136, 255, 0.25), rgba(0, 240, 255, 0.15));
        color: #fff;
        box-shadow: 0 2px 12px rgba(0, 136, 255, 0.2);
    }

    /* Holdings Table */
    .inv-table {
        width: 100%;
        border-collapse: collapse;
    }
    .inv-table th {
        text-align: left;
        padding: 12px 16px;
        font-size: 0.75rem;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 1.2px;
        border-bottom: 1px solid rgba(0, 136, 255, 0.12);
    }
    .inv-table td {
        padding: 16px;
        font-size: 0.92rem;
        border-bottom: 1px solid rgba(255,255,255,0.03);
        vertical-align: middle;
    }
    .inv-table tr:hover td {
        background: rgba(0, 136, 255, 0.04);
    }
    .inv-table .ticker-cell {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .ticker-icon {
        width: 40px;
        height: 40px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 0.75rem;
        color: #fff;
        flex-shrink: 0;
    }
    .ticker-icon.bist { background: linear-gradient(135deg, #0088ff, #00d2ff); }
    .ticker-icon.crypto { background: linear-gradient(135deg, #f7931a, #ffb347); }
    .ticker-icon.forex { background: linear-gradient(135deg, #39ff14, #00e676); }
    .ticker-icon.fund { background: linear-gradient(135deg, #7d5cff, #b026ff); }
    .ticker-icon.gold { background: linear-gradient(135deg, #ffd700, #ffaa00); }
    .ticker-name { font-weight: 700; font-size: 1rem; }
    .ticker-type { font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; }

    .pnl-positive { color: #39ff14; }
    .pnl-negative { color: #ff3b30; }
    .daily-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 3px 10px;
        border-radius: 999px;
        font-size: 0.78rem;
        font-weight: 600;
    }
    .daily-badge.up { background: rgba(57, 255, 20, 0.12); color: #39ff14; }
    .daily-badge.down { background: rgba(255, 59, 48, 0.12); color: #ff3b30; }

    /* Allocation Ring */
    .alloc-ring-container {
        display: flex;
        align-items: center;
        gap: 32px;
        padding: 8px;
    }
    .alloc-legend { flex: 1; }
    .alloc-legend-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 0;
        border-bottom: 1px solid rgba(255,255,255,0.04);
    }
    .alloc-legend-item:last-child { border: none; }
    .alloc-dot {
        width: 12px;
        height: 12px;
        border-radius: 4px;
        margin-right: 10px;
        flex-shrink: 0;
    }

    /* Market Card */
    .market-mini-card {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 16px;
        border-radius: 12px;
        background: rgba(255,255,255,0.02);
        border: 1px solid rgba(255,255,255,0.04);
        margin-bottom: 8px;
        transition: all 0.2s;
    }
    .market-mini-card:hover {
        background: rgba(0, 136, 255, 0.06);
        border-color: rgba(0, 136, 255, 0.2);
    }

    /* Add Holding Modal */
    .inv-form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 14px;
    }
    .inv-form-grid .full-width { grid-column: span 2; }

    /* Watchlist */
    .watchlist-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 12px;
    }
    .watch-card {
        background: rgba(12, 26, 51, 0.7);
        border: 1px solid rgba(0, 136, 255, 0.15);
        border-radius: 14px;
        padding: 16px;
        text-align: center;
        transition: all 0.3s;
        position: relative;
    }
    .watch-card:hover {
        border-color: rgba(0, 240, 255, 0.4);
        transform: translateY(-2px);
    }
    .watch-card .watch-ticker { font-weight: 800; font-size: 1.1rem; margin-bottom: 4px; }
    .watch-card .watch-price { font-family: var(--font-mono); font-size: 1rem; font-weight: 700; }
    .watch-card .watch-remove {
        position: absolute;
        top: 8px;
        right: 8px;
        width: 22px; height: 22px;
        border-radius: 50%;
        border: 1px solid rgba(255,59,48,.3);
        background: transparent;
        color: #ff3b30;
        cursor: pointer;
        font-size: 0.65rem;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.2s;
    }
    .watch-card:hover .watch-remove { opacity: 1; }

    /* Pro Gate Overlay */
    .pro-gate-overlay {
        position: relative;
        overflow: hidden;
    }
    .pro-gate-blur {
        filter: blur(6px);
        pointer-events: none;
        user-select: none;
        opacity: 0.6;
    }
    .pro-gate-cta {
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        z-index: 10;
        background: rgba(6, 17, 33, 0.75);
        backdrop-filter: blur(8px);
        border-radius: 12px;
        padding: 40px;
        text-align: center;
    }
    .pro-gate-cta .gate-icon {
        font-size: 3.5rem;
        margin-bottom: 16px;
        animation: pulse-glow 2s ease infinite;
    }
    @keyframes pulse-glow {
        0%, 100% { transform: scale(1); filter: drop-shadow(0 0 8px rgba(125, 92, 255, 0.4)); }
        50% { transform: scale(1.08); filter: drop-shadow(0 0 20px rgba(125, 92, 255, 0.7)); }
    }
    .pro-badge-inline {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 3px 10px;
        border-radius: 999px;
        font-size: 0.7rem;
        font-weight: 700;
        background: linear-gradient(135deg, #7d5cff, #b026ff);
        color: #fff;
        letter-spacing: 1px;
        text-transform: uppercase;
    }

    .inv-empty-state {
        text-align: center;
        padding: 60px 20px;
    }
    .inv-empty-state .empty-icon {
        font-size: 4rem;
        margin-bottom: 16px;
        opacity: 0.4;
    }

    /* Feature Showcase */
    .feature-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
        margin-top: 20px;
    }
    .feature-item {
        background: rgba(12, 26, 51, 0.6);
        border: 1px solid rgba(0, 136, 255, 0.15);
        border-radius: 14px;
        padding: 24px 20px;
        text-align: center;
        transition: all 0.3s;
    }
    .feature-item:hover { border-color: rgba(125, 92, 255, 0.4); transform: translateY(-3px); }
    .feature-item .feat-icon { font-size: 2rem; margin-bottom: 12px; }
    .feature-item h4 { margin: 0 0 8px; font-size: 0.95rem; }
    .feature-item p { margin: 0; font-size: 0.82rem; color: var(--text-muted); line-height: 1.5; }

    /* Live indicator */
    .live-indicator {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 6px 14px;
        background: rgba(57, 255, 20, 0.08);
        border: 1px solid rgba(57, 255, 20, 0.25);
        border-radius: 999px;
        font-size: 0.82rem;
        font-weight: 600;
        color: #39ff14;
    }
    .live-dot {
        width: 8px; height: 8px;
        background: #39ff14;
        border-radius: 50%;
        animation: live-pulse 1.5s ease infinite;
    }
    @keyframes live-pulse {
        0%, 100% { opacity: 1; box-shadow: 0 0 4px #39ff14; }
        50% { opacity: 0.3; box-shadow: 0 0 12px #39ff14; }
    }
    .refresh-info {
        font-size: 0.75rem;
        color: var(--text-muted);
        text-align: right;
        margin-top: 4px;
    }
    .source-badge {
        display: inline-block;
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 0.65rem;
        font-weight: 600;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        margin-left: 6px;
    }
    .source-badge.live { background: rgba(57,255,20,.12); color: #39ff14; }
    .source-badge.cached { background: rgba(255,145,0,.12); color: #ff9100; }
    .source-badge.fallback { background: rgba(255,59,48,.12); color: #ff3b30; }

    @media (max-width: 1200px) { .inv-stats-grid { grid-template-columns: repeat(3, 1fr); } .inv-stat-card.hero-card { grid-column: span 1; } }
    @media (max-width: 768px) {
        .inv-stats-grid { grid-template-columns: 1fr 1fr; }
        .feature-grid { grid-template-columns: 1fr; }
        .alloc-ring-container { flex-direction: column; }
        .inv-form-grid { grid-template-columns: 1fr; }
        .inv-form-grid .full-width { grid-column: span 1; }
        .inv-table th:nth-child(n+4), .inv-table td:nth-child(n+4) { display: none; }
    }
    </style>
</head>
<body class="dashboard-body">

    <?php include 'includes/dashboard_navbar.php'; ?>

    <main class="main-content">
        <header class="topbar">
            <div class="welcome-text">
                <h1>📈 Yatırım Portföyü <span class="pro-badge-inline">💎 PRO</span></h1>
                <p>Hisse, kripto, fon ve altın — tüm yatırımların tek panelde.</p>
            </div>
            <div class="topbar-actions">
                <div class="live-indicator" id="liveIndicator">
                    <span class="live-dot"></span>
                    <span>Canlı</span>
                    <span id="lastUpdate" style="opacity:.7; font-size:.75rem;"></span>
                </div>
                <div class="date-display">
                    <i class="fa-regular fa-calendar"></i>
                    <?php echo date('d F Y'); ?>
                </div>
            </div>
        </header>

        <div class="profile-overhaul-container">

<?php if (!$isPro): ?>
        <!-- ═══════════════════════════════════════════════════════ -->
        <!-- PRO GATE: Show preview but locked                       -->
        <!-- ═══════════════════════════════════════════════════════ -->

        <!-- Preview Stats (blurred) -->
        <div class="pro-gate-overlay" style="margin-bottom:24px;">
            <div class="pro-gate-blur">
                <div class="inv-stats-grid">
                    <div class="inv-stat-card hero-card">
                        <div class="stat-label">Portföy Değeri</div>
                        <div class="stat-value">₺48.750</div>
                        <div class="stat-sub pnl-positive">+₺6.230 (+14.6%)</div>
                    </div>
                    <div class="inv-stat-card">
                        <div class="stat-label">Maliyet</div>
                        <div class="stat-value">₺42.520</div>
                    </div>
                    <div class="inv-stat-card">
                        <div class="stat-label">Günlük</div>
                        <div class="stat-value pnl-positive">+₺385</div>
                    </div>
                    <div class="inv-stat-card">
                        <div class="stat-label">Pozisyon</div>
                        <div class="stat-value">12</div>
                    </div>
                </div>
            </div>
            <div class="pro-gate-cta">
                <div class="gate-icon">💎</div>
                <h2 style="margin:0 0 12px; font-size:1.6rem;">Yatırım Portföyünü Aç</h2>
                <p style="opacity:.7; max-width:420px; line-height:1.7; margin-bottom:24px;">
                    BIST hisseleri, kripto paralar, döviz, altın ve fon yatırımlarını tek panelden takip et.
                    Gerçek zamanlı fiyatlar, kar/zarar analizi, portföy dağılımı ve daha fazlası.
                </p>
                <a href="pricing.php" class="btn-premium-submit" style="display:inline-block; padding:14px 40px; text-decoration:none; width:auto; border-radius:999px; font-size:1rem;">
                    <i class="fa-solid fa-crown"></i> Pro'ya Yükselt — ₺29/ay
                </a>
            </div>
        </div>

        <!-- Feature Showcase -->
        <div class="glass-card" style="padding:32px;">
            <h3 style="margin-top:0; text-align:center; font-size:1.3rem; margin-bottom:8px;">
                <i class="fa-solid fa-wand-magic-sparkles" style="color:var(--accent-purple);"></i> Pro Yatırım Özellikleri
            </h3>
            <p style="text-align:center; opacity:.6; margin-bottom:24px;">Öğrenci yatırımcılar için tasarlandı</p>
            <div class="feature-grid">
                <div class="feature-item">
                    <div class="feat-icon">📊</div>
                    <h4>Portföy Analizi</h4>
                    <p>Varlık dağılımı, sektörel analiz ve risk değerlendirmesi ile portföyünü optimize et.</p>
                </div>
                <div class="feature-item">
                    <div class="feat-icon">📈</div>
                    <h4>Canlı Fiyatlar</h4>
                    <p>BIST, Bitcoin, Ethereum, döviz ve altın fiyatları anlık olarak güncellenir.</p>
                </div>
                <div class="feature-item">
                    <div class="feat-icon">💰</div>
                    <h4>Kar/Zarar Takibi</h4>
                    <p>Her pozisyonun PnL'ini, yüzdesel getirisini ve toplam performansını görüntüle.</p>
                </div>
                <div class="feature-item">
                    <div class="feat-icon">👁️</div>
                    <h4>Watchlist</h4>
                    <p>İlgilendiğin hisseleri ve kripto paraları takip listene ekle, fırsatları kaçırma.</p>
                </div>
                <div class="feature-item">
                    <div class="feat-icon">🏆</div>
                    <h4>En İyi/Kötü Performans</h4>
                    <p>Portföyündeki yıldız ve sorunlu pozisyonları anında gör, strateji belirle.</p>
                </div>
                <div class="feature-item">
                    <div class="feat-icon">🔔</div>
                    <h4>Piyasa Hareketleri</h4>
                    <p>Günün en çok yükselen ve düşen varlıklarını takip et, trendleri yakala.</p>
                </div>
            </div>
        </div>

        <!-- Demo Market Data -->
        <div class="glass-card" style="padding:24px; margin-top:24px;">
            <div class="widget-header">
                <h3><i class="fa-solid fa-fire" style="color:#ff9100;"></i> Piyasa Hareketleri (Demo)</h3>
                <span class="pro-badge-inline">🔒 PRO</span>
            </div>
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
                <div>
                    <h4 style="color:#39ff14; font-size:0.85rem; margin:0 0 12px;"><i class="fa-solid fa-arrow-trend-up"></i> EN ÇOK YÜKSELEN</h4>
                    <div id="demoGainers" class="pro-gate-blur" style="pointer-events:auto;">
                        <div class="market-mini-card"><span style="font-weight:700;">DOGE</span><span class="daily-badge up"><i class="fa-solid fa-caret-up"></i> +6.70%</span></div>
                        <div class="market-mini-card"><span style="font-weight:700;">SOL</span><span class="daily-badge up"><i class="fa-solid fa-caret-up"></i> +5.86%</span></div>
                        <div class="market-mini-card"><span style="font-weight:700;">ADA</span><span class="daily-badge up"><i class="fa-solid fa-caret-up"></i> +4.23%</span></div>
                        <div class="market-mini-card"><span style="font-weight:700;">BTC</span><span class="daily-badge up"><i class="fa-solid fa-caret-up"></i> +3.42%</span></div>
                    </div>
                </div>
                <div>
                    <h4 style="color:#ff3b30; font-size:0.85rem; margin:0 0 12px;"><i class="fa-solid fa-arrow-trend-down"></i> EN ÇOK DÜŞEN</h4>
                    <div class="pro-gate-blur">
                        <div class="market-mini-card"><span style="font-weight:700;">PETKM</span><span class="daily-badge down"><i class="fa-solid fa-caret-down"></i> -2.10%</span></div>
                        <div class="market-mini-card"><span style="font-weight:700;">TOASO</span><span class="daily-badge down"><i class="fa-solid fa-caret-down"></i> -1.52%</span></div>
                        <div class="market-mini-card"><span style="font-weight:700;">EREGL</span><span class="daily-badge down"><i class="fa-solid fa-caret-down"></i> -1.20%</span></div>
                        <div class="market-mini-card"><span style="font-weight:700;">AVAX</span><span class="daily-badge down"><i class="fa-solid fa-caret-down"></i> -1.15%</span></div>
                    </div>
                </div>
            </div>
        </div>

<?php else: ?>
        <!-- ═══════════════════════════════════════════════════════ -->
        <!-- PRO USER: Full Investment Dashboard                      -->
        <!-- ═══════════════════════════════════════════════════════ -->

        <!-- Stats Bar -->
        <div class="inv-stats-grid" id="invStats">
            <div class="inv-stat-card hero-card">
                <div class="stat-icon"><i class="fa-solid fa-wallet"></i></div>
                <div class="stat-label">Portföy Değeri</div>
                <div class="stat-value" id="totalValue">₺0</div>
                <div class="stat-sub" id="totalPnl">—</div>
            </div>
            <div class="inv-stat-card">
                <div class="stat-icon"><i class="fa-solid fa-money-bill"></i></div>
                <div class="stat-label">Toplam Maliyet</div>
                <div class="stat-value" id="totalCost">₺0</div>
            </div>
            <div class="inv-stat-card">
                <div class="stat-icon"><i class="fa-solid fa-trophy"></i></div>
                <div class="stat-label">En İyi</div>
                <div class="stat-value" id="bestPerformer">—</div>
                <div class="stat-sub pnl-positive" id="bestPct"></div>
            </div>
            <div class="inv-stat-card">
                <div class="stat-icon"><i class="fa-solid fa-exclamation-triangle"></i></div>
                <div class="stat-label">En Kötü</div>
                <div class="stat-value" id="worstPerformer">—</div>
                <div class="stat-sub pnl-negative" id="worstPct"></div>
            </div>
            <div class="inv-stat-card">
                <div class="stat-icon"><i class="fa-solid fa-layer-group"></i></div>
                <div class="stat-label">Pozisyon</div>
                <div class="stat-value" id="totalCount">0</div>
            </div>
        </div>

        <!-- Tabs -->
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
            <div class="inv-tabs">
                <button class="inv-tab active" onclick="switchTab('portfolio')"><i class="fa-solid fa-briefcase"></i> Portföy</button>
                <button class="inv-tab" onclick="switchTab('watchlist')"><i class="fa-solid fa-eye"></i> Watchlist</button>
                <button class="inv-tab" onclick="switchTab('market')"><i class="fa-solid fa-fire-flame-curved"></i> Piyasa</button>
                <button class="inv-tab" onclick="switchTab('add')"><i class="fa-solid fa-plus"></i> Pozisyon Ekle</button>
            </div>
        </div>

        <!-- TAB: Portfolio -->
        <div id="tab-portfolio">
            <div style="display:grid; grid-template-columns: 2fr 1fr; gap:20px;">
                <!-- Holdings Table -->
                <div class="glass-card" style="padding:0; overflow:hidden;">
                    <div style="padding:20px 24px 0;">
                        <div class="widget-header" style="margin-bottom:8px;">
                            <h3><i class="fa-solid fa-briefcase"></i> Pozisyonlarım</h3>
                            <span id="holdingsCount" style="font-size:.85rem; color:var(--text-muted);"></span>
                        </div>
                    </div>
                    <div id="holdingsBody" style="overflow-x:auto;">
                        <p style="padding:40px; text-align:center; opacity:.5;">Yükleniyor...</p>
                    </div>
                </div>

                <!-- Allocation Chart -->
                <div class="glass-card" style="padding:24px;">
                    <div class="widget-header" style="margin-bottom:16px;">
                        <h3><i class="fa-solid fa-chart-pie"></i> Dağılım</h3>
                    </div>
                    <div id="allocBody">
                        <p style="text-align:center; opacity:.5;">Yükleniyor...</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB: Watchlist -->
        <div id="tab-watchlist" style="display:none;">
            <div class="glass-card" style="padding:24px;">
                <div class="widget-header">
                    <h3><i class="fa-solid fa-eye"></i> Takip Listem</h3>
                    <button class="btn-premium-submit" style="width:auto; padding:8px 18px; font-size:.85rem; margin:0;" onclick="showWatchAdd()">
                        <i class="fa-solid fa-plus"></i> Ekle
                    </button>
                </div>
                <div id="watchAddForm" style="display:none; margin-bottom:20px;">
                    <form id="watchForm" style="display:flex; gap:10px; margin-top:14px;">
                        <select name="asset_type" id="watchAssetType" class="input-glass" style="width:140px;" onchange="updateWatchTickers()">
                            <option value="bist">BIST</option>
                            <option value="crypto">Crypto</option>
                            <option value="forex">Döviz</option>
                            <option value="gold">Altın</option>
                        </select>
                        <select name="ticker" id="watchTicker" required class="input-glass" style="flex:1;">
                        </select>
                        <button type="submit" class="btn-premium-submit" style="width:auto; padding:10px 24px; margin:0;">Ekle</button>
                    </form>
                </div>
                <div id="watchlistBody" class="watchlist-grid">
                    <p style="opacity:.5; grid-column:1/-1; text-align:center; padding:40px;">Yükleniyor...</p>
                </div>
            </div>
        </div>

        <!-- TAB: Market -->
        <div id="tab-market" style="display:none;">
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
                <div class="glass-card" style="padding:24px;">
                    <h3 style="margin-top:0; color:#39ff14;"><i class="fa-solid fa-arrow-trend-up"></i> En Çok Yükselen</h3>
                    <div id="marketGainers"><p style="opacity:.5;">Yükleniyor...</p></div>
                </div>
                <div class="glass-card" style="padding:24px;">
                    <h3 style="margin-top:0; color:#ff3b30;"><i class="fa-solid fa-arrow-trend-down"></i> En Çok Düşen</h3>
                    <div id="marketLosers"><p style="opacity:.5;">Yükleniyor...</p></div>
                </div>
            </div>
        </div>

        <!-- TAB: Add Position -->
        <div id="tab-add" style="display:none;">
            <div class="glass-card" style="padding:32px; max-width:700px;">
                <h3 style="margin-top:0;"><i class="fa-solid fa-plus-circle" style="color:var(--accent-cyan);"></i> Yeni Pozisyon Ekle</h3>
                <p style="opacity:.6; margin-bottom:24px;">Hisse, kripto, döviz, altın veya fon pozisyonu ekle.</p>
                <form id="addForm">
                    <div class="inv-form-grid">
                        <div>
                            <label style="font-size:.85rem; color:var(--text-muted); display:block; margin-bottom:6px;">Varlık Türü</label>
                            <select name="asset_type" id="addAssetType" class="input-glass" style="width:100%;" onchange="updateAddTickers()">
                                <option value="bist">🏦 BIST Hissesi</option>
                                <option value="crypto">₿ Kripto Para</option>
                                <option value="forex">💱 Döviz</option>
                                <option value="gold">🥇 Altın</option>
                            </select>
                        </div>
                        <div>
                            <label style="font-size:.85rem; color:var(--text-muted); display:block; margin-bottom:6px;">Ticker / Sembol</label>
                            <select name="ticker" id="addTicker" required class="input-glass" style="width:100%;">
                            </select>
                        </div>
                        <div>
                            <label style="font-size:.85rem; color:var(--text-muted); display:block; margin-bottom:6px;">Miktar / Adet</label>
                            <input type="number" step="0.000001" name="quantity" placeholder="0.00" required class="input-glass" style="width:100%;">
                        </div>
                        <div>
                            <label style="font-size:.85rem; color:var(--text-muted); display:block; margin-bottom:6px;">Alış Fiyatı (₺)</label>
                            <input type="number" step="0.01" name="buy_price" placeholder="0.00" required class="input-glass" style="width:100%;">
                        </div>
                        <div>
                            <label style="font-size:.85rem; color:var(--text-muted); display:block; margin-bottom:6px;">Alış Tarihi</label>
                            <input type="date" name="buy_date" class="input-glass" style="width:100%;" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div>
                            <label style="font-size:.85rem; color:var(--text-muted); display:block; margin-bottom:6px;">Not (opsiyonel)</label>
                            <input type="text" name="notes" placeholder="Uzun vadeli, kısa vade..." class="input-glass" style="width:100%;">
                        </div>
                        <div class="full-width">
                            <button type="submit" class="btn-premium-submit" style="margin-top:8px;">
                                <i class="fa-solid fa-plus"></i> Pozisyonu Ekle
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
<?php endif; ?>

        </div>
    </main>

<?php if ($isPro): ?>
<script>
const api = (u, opts = {}) => fetch(u, {
    credentials: 'same-origin',
    headers: { 'X-CSRF-Token': window.CSRF_TOKEN || '', ...(opts.headers || {}) },
    ...opts
}).then(r => r.json());

const TYPE_LABELS = { bist: 'BIST', crypto: 'Kripto', forex: 'Döviz', gold: 'Altın', fund: 'Fon' };
const TYPE_COLORS = { bist: '#0088ff', crypto: '#f7931a', forex: '#39ff14', gold: '#ffd700', fund: '#7d5cff' };
const fmt = n => parseFloat(n).toLocaleString('tr-TR', { maximumFractionDigits: 2 });

const TICKER_MAP = {
    bist: ['THYAO', 'SISE', 'ASELS', 'EREGL', 'TUPRS', 'BIMAS', 'GARAN', 'AKBNK', 'YKBNK', 'SAHOL', 'KCHOL', 'TCELL', 'FROTO', 'TOASO', 'KOZAL', 'PETKM', 'VESTL', 'TAVHL', 'MGROS', 'ENKAI', 'SASA', 'EKGYO', 'HEKTS', 'TTKOM'],
    crypto: ['BTC', 'ETH', 'SOL', 'AVAX', 'BNB', 'ADA', 'DOT', 'XRP', 'DOGE', 'LINK', 'MATIC', 'UNI', 'ATOM', 'APT', 'ARB'],
    forex: ['USD', 'EUR', 'GBP', 'CHF', 'JPY'],
    gold: ['ALTIN'],
    fund: []
};

function populateTickerSelect(assetTypeSelectId, tickerSelectId) {
    const typeObj = document.getElementById(assetTypeSelectId);
    const tickerObj = document.getElementById(tickerSelectId);
    if (!typeObj || !tickerObj) return;
    const val = typeObj.value;
    tickerObj.innerHTML = '';
    const arr = TICKER_MAP[val] || [];
    arr.sort().forEach(t => {
        tickerObj.innerHTML += `<option value="${t}">${t}</option>`;
    });
}
function updateWatchTickers() { populateTickerSelect('watchAssetType', 'watchTicker'); }
function updateAddTickers() { populateTickerSelect('addAssetType', 'addTicker'); }

setTimeout(() => { updateWatchTickers(); updateAddTickers(); }, 100);

// ─── Tab Switching ───
function switchTab(name) {
    document.querySelectorAll('[id^="tab-"]').forEach(t => t.style.display = 'none');
    document.getElementById('tab-' + name).style.display = 'block';
    document.querySelectorAll('.inv-tab').forEach(t => t.classList.remove('active'));
    event.currentTarget.classList.add('active');

    if (name === 'watchlist') loadWatchlist();
    if (name === 'market') loadMarket();
}

// ─── Portfolio ───
async function loadPortfolio() {
    const r = await api('api/investments.php');
    if (!r.success) return;
    const t = r.totals;

    // Stats
    document.getElementById('totalValue').textContent = '₺' + fmt(t.market_value);
    document.getElementById('totalCost').textContent = '₺' + fmt(t.cost_basis);
    document.getElementById('totalCount').textContent = t.count;

    const pnlEl = document.getElementById('totalPnl');
    if (t.pnl >= 0) {
        pnlEl.className = 'stat-sub pnl-positive';
        pnlEl.textContent = `+₺${fmt(t.pnl)} (+${t.pnl_pct}%)`;
    } else {
        pnlEl.className = 'stat-sub pnl-negative';
        pnlEl.textContent = `₺${fmt(t.pnl)} (${t.pnl_pct}%)`;
    }

    if (r.best) {
        document.getElementById('bestPerformer').textContent = r.best.ticker;
        document.getElementById('bestPct').textContent = `+${r.best.pnl_pct}%`;
    }
    if (r.worst) {
        document.getElementById('worstPerformer').textContent = r.worst.ticker;
        document.getElementById('worstPct').textContent = `${r.worst.pnl_pct}%`;
    }

    // Holdings Table
    const body = document.getElementById('holdingsBody');
    if (!r.holdings.length) {
        body.innerHTML = `
            <div class="inv-empty-state">
                <div class="empty-icon">📊</div>
                <h3>Henüz pozisyon yok</h3>
                <p style="opacity:.6; margin-bottom:20px;">İlk yatırımını ekleyerek portföyünü oluştur.</p>
                <button class="btn-premium-submit" style="width:auto; padding:10px 28px;" onclick="switchTab('add');document.querySelectorAll('.inv-tab')[3].classList.add('active');">
                    <i class="fa-solid fa-plus"></i> Pozisyon Ekle
                </button>
            </div>`;
        document.getElementById('holdingsCount').textContent = '';
    } else {
        document.getElementById('holdingsCount').textContent = `${r.holdings.length} pozisyon`;
        body.innerHTML = `<table class="inv-table">
            <thead><tr>
                <th>Varlık</th>
                <th>Miktar</th>
                <th>Alış</th>
                <th>Güncel</th>
                <th>Değer</th>
                <th>Kar/Zarar</th>
                <th>Günlük</th>
                <th></th>
            </tr></thead>
            <tbody>${r.holdings.map(h => {
                const pnlClass = h.pnl >= 0 ? 'pnl-positive' : 'pnl-negative';
                const dailyClass = h.daily_change >= 0 ? 'up' : 'down';
                const dailyIcon = h.daily_change >= 0 ? 'fa-caret-up' : 'fa-caret-down';
                return `<tr>
                    <td>
                        <div class="ticker-cell">
                            <div class="ticker-icon ${h.asset_type}">${h.ticker.substring(0,2)}</div>
                            <div>
                                <div class="ticker-name">${h.ticker}</div>
                                <div class="ticker-type">${TYPE_LABELS[h.asset_type] || h.asset_type}</div>
                            </div>
                        </div>
                    </td>
                    <td style="font-family:var(--font-mono);">${h.quantity}</td>
                    <td style="font-family:var(--font-mono);">₺${fmt(h.buy_price)}</td>
                    <td style="font-family:var(--font-mono);">₺${fmt(h.current_price)}</td>
                    <td style="font-family:var(--font-mono); font-weight:700;">₺${fmt(h.current_value)}</td>
                    <td>
                        <div class="${pnlClass}" style="font-weight:700;">${h.pnl >= 0 ? '+' : ''}₺${fmt(h.pnl)}</div>
                        <div class="${pnlClass}" style="font-size:.8rem;">${h.pnl_pct >= 0 ? '+' : ''}${h.pnl_pct}%</div>
                    </td>
                    <td><span class="daily-badge ${dailyClass}"><i class="fa-solid ${dailyIcon}"></i> ${Math.abs(h.daily_change)}%</span></td>
                    <td><button onclick="delHolding(${h.id})" style="background:transparent; border:1px solid rgba(255,59,48,.25); color:#ff3b30; padding:6px 10px; border-radius:8px; cursor:pointer; font-size:.8rem;" title="Sil"><i class="fa-solid fa-trash"></i></button></td>
                </tr>`;
            }).join('')}</tbody>
        </table>`;
    }

    // Allocation
    const allocEl = document.getElementById('allocBody');
    if (!r.allocation.length) {
        allocEl.innerHTML = '<p style="text-align:center; opacity:.5; padding:20px;">Pozisyon ekleyin.</p>';
    } else {
        const total = r.allocation.reduce((s, a) => s + a.value, 0);
        // Build donut segments
        let cumulAngle = 0;
        const segments = r.allocation.map((a, i) => {
            const color = TYPE_COLORS[a.type] || '#888';
            const angle = (a.pct / 100) * 360;
            const seg = { start: cumulAngle, end: cumulAngle + angle, color };
            cumulAngle += angle;
            return seg;
        });

        // SVG donut
        const size = 180, cx = 90, cy = 90, r1 = 70, r2 = 50;
        let paths = '';
        segments.forEach(s => {
            const startRad = (s.start - 90) * Math.PI / 180;
            const endRad = (s.end - 90) * Math.PI / 180;
            const largeArc = (s.end - s.start) > 180 ? 1 : 0;
            const x1o = cx + r1 * Math.cos(startRad), y1o = cy + r1 * Math.sin(startRad);
            const x2o = cx + r1 * Math.cos(endRad), y2o = cy + r1 * Math.sin(endRad);
            const x1i = cx + r2 * Math.cos(endRad), y1i = cy + r2 * Math.sin(endRad);
            const x2i = cx + r2 * Math.cos(startRad), y2i = cy + r2 * Math.sin(startRad);
            paths += `<path d="M${x1o},${y1o} A${r1},${r1} 0 ${largeArc} 1 ${x2o},${y2o} L${x1i},${y1i} A${r2},${r2} 0 ${largeArc} 0 ${x2i},${y2i} Z" fill="${s.color}" opacity="0.85"/>`;
        });

        const legend = r.allocation.map(a => `
            <div class="alloc-legend-item">
                <div style="display:flex;align-items:center;">
                    <div class="alloc-dot" style="background:${TYPE_COLORS[a.type] || '#888'}"></div>
                    <span style="font-weight:600;">${TYPE_LABELS[a.type] || a.type}</span>
                </div>
                <div>
                    <span style="font-family:var(--font-mono); font-weight:700;">${a.pct}%</span>
                    <span style="font-size:.8rem; opacity:.6; margin-left:6px;">₺${fmt(a.value)}</span>
                </div>
            </div>`).join('');

        allocEl.innerHTML = `
            <div class="alloc-ring-container">
                <svg width="${size}" height="${size}" viewBox="0 0 ${size} ${size}">
                    ${paths}
                    <text x="${cx}" y="${cy-4}" text-anchor="middle" fill="#fff" font-size="14" font-weight="800" font-family="var(--font-mono)">₺${fmt(total)}</text>
                    <text x="${cx}" y="${cy+14}" text-anchor="middle" fill="rgba(255,255,255,0.5)" font-size="10">TOPLAM</text>
                </svg>
                <div class="alloc-legend">${legend}</div>
            </div>`;
    }
}

async function delHolding(id) {
    if (!confirm('Bu pozisyonu silmek istiyor musun?')) return;
    const fd = new FormData(); fd.append('action', 'delete'); fd.append('id', id);
    await api('api/investments.php', { method: 'POST', body: fd });
    loadPortfolio();
}

// ─── Add Position ───
document.getElementById('addForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const fd = new FormData(e.target);
    fd.append('action', 'create');
    const r = await api('api/investments.php', { method: 'POST', body: fd });
    if (r.success) {
        e.target.reset();
        document.querySelector('[name="buy_date"]').value = new Date().toISOString().split('T')[0];
        loadPortfolio();
        // Switch to portfolio tab
        document.querySelectorAll('.inv-tab')[0].click();
    } else {
        alert(r.message || 'Hata oluştu');
    }
});

// ─── Watchlist ───
function showWatchAdd() {
    const f = document.getElementById('watchAddForm');
    f.style.display = f.style.display === 'none' ? 'block' : 'none';
}

async function loadWatchlist() {
    const r = await api('api/investments.php?action=watchlist');
    const body = document.getElementById('watchlistBody');
    if (!r.success || !r.watchlist?.length) {
        body.innerHTML = `
            <div style="grid-column:1/-1; text-align:center; padding:40px;">
                <div style="font-size:3rem; opacity:.3; margin-bottom:12px;">👁️</div>
                <p style="opacity:.6;">Takip listesi boş. İlgilendiğin varlıkları ekle!</p>
            </div>`;
        return;
    }
    body.innerHTML = r.watchlist.map(w => {
        const chgClass = w.change_pct >= 0 ? 'pnl-positive' : 'pnl-negative';
        const arrow = w.change_pct >= 0 ? '▲' : '▼';
        return `<div class="watch-card">
            <button class="watch-remove" onclick="unwatch(${w.id})" title="Kaldır"><i class="fa-solid fa-xmark"></i></button>
            <div class="watch-ticker">${w.ticker}</div>
            <div style="font-size:.75rem; color:var(--text-muted); margin-bottom:8px;">${TYPE_LABELS[w.asset_type] || w.asset_type}</div>
            <div class="watch-price">${w.current_price ? '₺' + fmt(w.current_price) : '—'}</div>
            <div class="${chgClass}" style="font-size:.85rem; font-weight:600; margin-top:4px;">${arrow} ${Math.abs(w.change_pct)}%</div>
        </div>`;
    }).join('');
}

document.getElementById('watchForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const fd = new FormData(e.target);
    fd.append('action', 'watch');
    const r = await api('api/investments.php', { method: 'POST', body: fd });
    if (r.success) { e.target.reset(); loadWatchlist(); }
    else alert(r.message || 'Hata');
});

async function unwatch(id) {
    const fd = new FormData(); fd.append('action', 'unwatch'); fd.append('id', id);
    await api('api/investments.php', { method: 'POST', body: fd });
    loadWatchlist();
}

// ─── Market ───
async function loadMarket() {
    const r = await api('api/investments.php?action=market');
    if (!r.success) return;

    const srcBadge = (s) => {
        if (s === 'fallback') return '<span class="source-badge fallback">offline</span>';
        return '<span class="source-badge live">canlı</span>';
    };

    document.getElementById('marketGainers').innerHTML = r.gainers.map(g => `
        <div class="market-mini-card">
            <div><span style="font-weight:700; font-size:1rem;">${g.ticker}</span>${srcBadge(g.source)} <span style="font-family:var(--font-mono); font-size:.9rem; margin-left:8px;">₺${fmt(g.price)}</span></div>
            <span class="daily-badge up"><i class="fa-solid fa-caret-up"></i> +${g.change_pct}%</span>
        </div>`).join('');

    document.getElementById('marketLosers').innerHTML = r.losers.map(l => `
        <div class="market-mini-card">
            <div><span style="font-weight:700; font-size:1rem;">${l.ticker}</span>${srcBadge(l.source)} <span style="font-family:var(--font-mono); font-size:.9rem; margin-left:8px;">₺${fmt(l.price)}</span></div>
            <span class="daily-badge down"><i class="fa-solid fa-caret-down"></i> ${l.change_pct}%</span>
        </div>`).join('');

    if (r.updated) updateTimestamp(r.updated);
}

// ─── Live Update System ───
let refreshInterval = null;
let countdown = 60;
const REFRESH_SECONDS = 60; // Her 60 saniyede bir güncelle

function updateTimestamp(time) {
    const el = document.getElementById('lastUpdate');
    if (el) el.textContent = time;
}

function startAutoRefresh() {
    countdown = REFRESH_SECONDS;
    if (refreshInterval) clearInterval(refreshInterval);
    refreshInterval = setInterval(async () => {
        countdown--;
        if (countdown <= 0) {
            countdown = REFRESH_SECONDS;
            // Aktif tab'a göre veriyi yenile
            const activeTabEl = document.querySelector('.inv-tab.active');
            const activeText = activeTabEl?.textContent?.trim() || '';
            if (activeText.includes('Piyasa')) {
                await loadMarket();
            } else if (activeText.includes('Watchlist')) {
                await loadWatchlist();
            } else {
                await loadPortfolio();
            }
        }
    }, 1000);
}

// ─── Init ───
loadPortfolio();
startAutoRefresh();
</script>
<?php endif; ?>
</body>
</html>
