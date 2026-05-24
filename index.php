<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/csrf.php';
$__appUrl = rtrim(env('APP_URL', SITE_URL), '/');
$__csrf   = csrf_token();
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Üniversite öğrencileri için yapay zeka destekli bütçe hesaplayıcı. Aylık giderlerinizi planlayın, şehir maliyetlerini karşılaştırın.">
    <meta name="theme-color" content="#050510">
    <link rel="manifest" href="manifest.json">
    <link rel="apple-touch-icon" href="assets/icon-192x192.png">
    <meta name="keywords" content="üniversite, bütçe, öğrenci, yaşam maliyeti, kyk, burs, yapay zeka">
    <link rel="canonical" href="<?= htmlspecialchars(rtrim($__appUrl, '/'), ENT_QUOTES) ?>">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= htmlspecialchars($__appUrl . '/index.php', ENT_QUOTES) ?>">
    <meta property="og:title" content="ÜniBütçe — Üniversite Yaşam Maliyeti Hesaplayıcı">
    <meta property="og:description" content="Üniversite öğrencileri için yapay zeka destekli bütçe hesaplayıcı. Aylık giderlerinizi planlayın.">
    <meta property="og:image" content="<?= htmlspecialchars($__appUrl . '/assets/og-image.jpg', ENT_QUOTES) ?>">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="<?= htmlspecialchars($__appUrl . '/index.php', ENT_QUOTES) ?>">
    <meta property="twitter:title" content="ÜniBütçe — Üniversite Yaşam Maliyeti Hesaplayıcı">
    <meta property="twitter:description" content="Üniversite öğrencileri için yapay zeka destekli bütçe hesaplayıcı. Aylık giderlerinizi planlayın.">
    <meta property="twitter:image" content="<?= htmlspecialchars($__appUrl . '/assets/og-image.jpg', ENT_QUOTES) ?>">

    <!-- Schema.org Organization + WebSite -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@graph": [
        {
          "@type": "Organization",
          "name": "ÜniBütçe",
          "url": "<?= $__appUrl ?>",
          "logo": "<?= $__appUrl ?>/assets/icon-192x192.png",
          "description": "Türkiye'deki üniversite öğrencileri için bütçe ve yaşam maliyeti platformu."
        },
        {
          "@type": "WebSite",
          "name": "ÜniBütçe",
          "url": "<?= $__appUrl ?>",
          "potentialAction": {
            "@type": "SearchAction",
            "target": "<?= $__appUrl ?>/blog.php?q={search_term_string}",
            "query-input": "required name=search_term_string"
          }
        }
      ]
    }
    </script>

    <title>ÜniBütçe — Üniversite Yaşam Maliyeti Hesaplayıcı</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Anton&family=Condiment&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime(__DIR__.'/style.css'); ?>">
    <link rel="stylesheet" href="css/auth.css">
    <link rel="stylesheet" href="css/ui-helpers.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/orbis-theme.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/mobile.css?v=<?php echo time(); ?>">
    <meta name="csrf-token" content="<?= htmlspecialchars($__csrf, ENT_QUOTES) ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script>
        window.isUserLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
        window.CSRF_TOKEN = <?= json_encode($__csrf) ?>;
        
        // PWA Service Worker Registration
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/unistudent/sw.js').then(registration => {
                    console.log('SW registered: ', registration);
                }).catch(registrationError => {
                    console.log('SW registration failed: ', registrationError);
                });
            });
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="js/utils.js?v=<?php echo time(); ?>" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <link rel="stylesheet" href="css/animations.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/premium.css?v=<?php echo time(); ?>">
    <script src="js/animations.js?v=<?php echo time(); ?>" defer></script>
    <script src="js/premium-ui.js?v=<?php echo time(); ?>" defer></script>
    <script src="js/command-palette.js?v=<?php echo time(); ?>" defer></script>
    <script src="js/tab-sync.js?v=<?php echo time(); ?>" defer></script>
</head>

<body data-theme="light">

    <!-- Orbis Texture Overlay -->
    <div class="orbis-texture-overlay"></div>

    <!-- ════════════ NAVIGATION ════════════ -->
    <?php require_once __DIR__ . '/includes/public_mega_nav.php'; ?>

    <!-- ════════════ HERO ════════════ -->
    <section class="hero" id="hero">
        <!-- Orbis Video Background -->
        <div class="orbis-video-bg">
            <video autoplay loop muted playsinline>
                <source src="https://d8j0ntlcm91z4.cloudfront.net/user_38xzZboKViGWJOttwIXH07lWA1P/hf_20260331_045634_e1c98c76-1265-4f5c-882a-4276f2080894.mp4" type="video/mp4">
            </video>
        </div>
        <div class="bg-orb"></div>
        <div class="bg-orb"></div>
        <div class="bg-orb"></div>
        <div class="container">
            <div class="hero-content">
                <div class="hero-badge">
                    <span>🤖</span> Yapay Zeka Destekli Platform
                </div>
                <h1 class="hero-title" style="position:relative;">
                    Üniversite Bütçeni<br>
                    <span class="text-gradient typing-cursor" id="heroTyping">Akıllıca Planla</span>
                    <span class="hero-cursive-accent">Öğrenci Platformu</span>
                </h1>
                <p class="hero-description">
                    Aylık giderlerini hesapla, şehir maliyetlerini karşılaştır,
                    KYK/burs simülasyonu yap ve yapay zeka destekli tasarruf önerileri al.
                    Öğrenci hayatını kontrol altına al.
                </p>
                <div class="hero-buttons">
                    <a href="#budget" class="btn btn-primary">
                        📊 Bütçemi Hesapla
                    </a>
                    <a href="#cities" class="btn btn-secondary">
                        🏙️ Şehirleri Karşılaştır
                    </a>
                </div>
                <div class="hero-stats">
                    <div class="hero-stat">
                        <div class="hero-stat-number" data-target="81">0</div>
                        <div class="hero-stat-label">Şehir Verisi</div>
                    </div>
                    <div class="hero-stat">
                        <div class="hero-stat-number" data-target="7">0</div>
                        <div class="hero-stat-label">Gider Kategorisi</div>
                    </div>
                    <div class="hero-stat">
                        <div class="hero-stat-number" data-target="50">0</div>
                        <div class="hero-stat-label">Tasarruf İpucu</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <hr class="section-separator">

    <!-- ════════════ TESTIMONIALS ════════════ -->
    <section class="section" id="testimonials">
        <div class="container">
            <div class="reveal" style="text-align: center;">
                <div class="section-badge">💬 Öğrenci Yorumları</div>
                <h2 class="section-title">Binlerce Öğrenci <span class="text-gradient">ÜniBütçe Kullanıyor</span></h2>
                <p class="section-subtitle" style="margin: 0 auto;">Platformumuzdan yararlanan öğrencilerin deneyimleri.</p>
            </div>
        </div>
        <div class="testimonials-wrapper reveal" style="overflow: hidden; margin-top: 40px;">
            <div class="testimonial-track">
                <?php
                $testimonials = [
                    ['bg' => 'linear-gradient(135deg,#0088ff,#00F0FF)', 'ini' => 'EK', 'name' => 'Elif K.', 'uni' => 'İTÜ - Bilgisayar Müh.', 'text' => '"Aylık harcamalarımı ilk kez bu kadar net görebildim. Şehir karşılaştırma özelliği Erasmus kararımda çok yardımcı oldu!"', 'stars' => '★★★★★'],
                    ['bg' => 'linear-gradient(135deg,#7000FF,#b026ff)', 'ini' => 'AY', 'name' => 'Ahmet Y.', 'uni' => 'ODTÜ - Endüstri Müh.', 'text' => '"KYK simülatörü ile burs+kredi gelirimi giderlerimle karşılaştırdım ve ayda 800₺ tasarruf edebileceğimi keşfettim."', 'stars' => '★★★★★'],
                    ['bg' => 'linear-gradient(135deg,#39FF14,#00b894)', 'ini' => 'SD', 'name' => 'Selin D.', 'uni' => 'Hacettepe - Tıp', 'text' => '"Cyber Coach ile bütçe konusunda her soruyu sorabiliyorum. Abonelik takibi sayesinde gereksiz 3 aboneliği iptal ettim!"', 'stars' => '★★★★★', 'color' => '#000'],
                    ['bg' => 'linear-gradient(135deg,#ff9100,#ff5252)', 'ini' => 'MB', 'name' => 'Murat B.', 'uni' => 'Ege Üni. - İktisat', 'text' => '"Hedefler sayfası ile yeni laptop biriktirme planımı takip ediyorum. Isı haritası ise günlük harcamalarımı kontrol etmemi sağlıyor."', 'stars' => '★★★★☆'],
                    ['bg' => 'linear-gradient(135deg,#f42c92,#ff6b6b)', 'ini' => 'ZA', 'name' => 'Zeynep A.', 'uni' => 'Boğaziçi - Psikoloji', 'text' => '"İstanbul\'da öğrenci olmak zor ama ÜniBütçe ile harcamalarımı optimize ettim. Artık ayın sonunu getirmek sorun değil!"', 'stars' => '★★★★★'],
                    ['bg' => 'linear-gradient(135deg,#10b981,#34d399)', 'ini' => 'CT', 'name' => 'Caner T.', 'uni' => 'Bilkent - Hukuk', 'text' => '"Hukuk kitapları çok pahalı. Bütçemi planlayabilmek stresimi azalttı."', 'stars' => '★★★★★'],
                    ['bg' => 'linear-gradient(135deg,#6366f1,#818cf8)', 'ini' => 'AC', 'name' => 'Aslı C.', 'uni' => 'Gazi Üni. - Diş Hekimliği', 'text' => '"Malzeme masrafları bel büküyor, ÜniBütçe\'nin kategorilere ayırması harika!"', 'stars' => '★★★★★'],
                    ['bg' => 'linear-gradient(135deg,#ec4899,#f472b6)', 'ini' => 'KM', 'name' => 'Kerem M.', 'uni' => 'Yıldız Teknik - Mimarlık', 'text' => '"Proje harcamalarımı tek tek giriyorum, ay sonu ne kadar harcadığımı net görüyorum."', 'stars' => '★★★★☆'],
                    ['bg' => 'linear-gradient(135deg,#8b5cf6,#a78bfa)', 'ini' => 'AR', 'name' => 'Ayşe R.', 'uni' => 'Ankara Üni. - İletişim', 'text' => '"Kredi kartı taksitlerimi yönetmek kabustu, buradaki abonelik/gider sistemi çok iyi."', 'stars' => '★★★★★'],
                    ['bg' => 'linear-gradient(135deg,#eab308,#fde047)', 'ini' => 'BS', 'name' => 'Burak S.', 'uni' => 'Marmara Üni. - İşletme', 'text' => '"İşletme okuduğum için bütçe tablolarını severim, buradaki grafikler bir harika."', 'stars' => '★★★★★', 'color' => '#000'],
                    ['bg' => 'linear-gradient(135deg,#ef4444,#f87171)', 'ini' => 'DH', 'name' => 'Deniz H.', 'uni' => 'Dokuz Eylül - Hukuk', 'text' => '"Öğrenci evi masraflarımızı arkadaşlarla planlarken bütçe sayfasını kullanıyoruz."', 'stars' => '★★★★★'],
                    ['bg' => 'linear-gradient(135deg,#06b6d4,#22d3ee)', 'ini' => 'BK', 'name' => 'Buse K.', 'uni' => 'Akdeniz Üni. - Turizm', 'text' => '"Antalya\'da yaşamak pahalı. Gelir-gider tabloları sayesinde artık para biriktirebiliyorum!"', 'stars' => '★★★★★']
                ];
                
                // Print items TWICE for perfect infinite scroll
                for ($loop = 0; $loop < 2; $loop++) {
                    foreach ($testimonials as $t) {
                        $txtClr = $t['color'] ?? '#fff';
                        echo '<div class="testimonial-card">
                            <div style="display:flex; align-items:center; gap:12px; margin-bottom:16px;">
                                <div style="width:42px;height:42px;border-radius:50%;background:'.$t['bg'].';display:flex;align-items:center;justify-content:center;font-weight:700;color:'.$txtClr.';">'.$t['ini'].'</div>
                                <div><div style="font-weight:600;">'.$t['name'].'</div><div style="font-size:0.75rem;color:var(--text-muted);">'.$t['uni'].'</div></div>
                            </div>
                            <p style="color:var(--text-secondary);font-size:0.9rem;line-height:1.7;">'.$t['text'].'</p>
                            <div style="color:#FFD700;margin-top:12px;">'.$t['stars'].'</div>
                        </div>';
                    }
                }
                ?>
            </div>
        </div>
    </section>

    <hr class="section-separator">

    <!-- ════════════ BUDGET CALCULATOR ════════════ -->
    <section class="section" id="budget">
        <div class="container">
            <div class="reveal">
                <div class="section-badge">💰 Ana Hesaplayıcı</div>
                <h2 class="section-title">Aylık Bütçe <span class="text-gradient">Hesaplayıcı</span></h2>
                <p class="section-subtitle">Gider kalemlerini ayarla, toplam aylık maliyetini anında gör.</p>
            </div>
            <div class="budget-grid">
                <!-- Left: Expense Inputs -->
                <div class="budget-inputs reveal" id="budgetInputs">
                    <!-- Generated via JS -->
                </div>
                <!-- Right: Summary -->
                <div class="budget-summary reveal">
                    <div class="glass-card budget-summary-card">
                        <div class="summary-total">
                            <div class="summary-total-label">Aylık Toplam Gider</div>
                            <div class="summary-total-amount" id="totalMonthly">0 ₺</div>
                            <div class="summary-total-yearly">Yıllık: <span id="totalYearly">0 ₺</span></div>
                        </div>
                        <div class="chart-container">
                            <canvas id="budgetChart"></canvas>
                        </div>
                        <div class="summary-breakdown" id="summaryBreakdown">
                            <!-- Generated via JS -->
                        </div>
                        <div class="trust-actions">
                            <button class="btn btn-save-budget" id="saveBudgetBtn">
                                💾 Bütçeyi Kaydet
                            </button>
                            <button class="btn btn-share-animated" id="shareExpensesBtn" onclick="shareCurrentExpenses()">
                                🌟 Giderini Paylaş & Katkıda Bulun
                            </button>
                            <button class="btn btn-pdf" id="downloadPdf" onclick="downloadBudgetPDF()">
                                📄 PDF Olarak İndir
                            </button>
                        </div>
                        <div class="trust-badge" id="budgetTrustBadge">
                            <span class="trust-badge-icon">✅</span>
                            <span class="trust-badge-text">Bu veriler <strong id="trustStudentCount">0</strong> öğrenci
                                tarafından güncellendi</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <hr class="section-separator">

    <!-- ════════════ KYK / BURS SIMULATOR ════════════ -->
    <section class="section orbis-section-video" id="kyk">
        <!-- Blurred Video Background -->
        <div class="orbis-video-bg orbis-video-blurred">
            <video autoplay loop muted playsinline>
                <source src="https://d8j0ntlcm91z4.cloudfront.net/user_38xzZboKViGWJOttwIXH07lWA1P/hf_20260331_151551_992053d1-3d3e-4b8c-abac-45f22158f411.mp4" type="video/mp4">
            </video>
        </div>
        <div class="container" style="position:relative; z-index:2;">
            <div class="reveal">
                <div class="section-badge">🎓 Gelir Simülasyonu</div>
                <h2 class="section-title">KYK / Burs <span class="text-gradient">Simülatörü</span></h2>
                <p class="section-subtitle">Gelirlerini gir, bütçe dengenin ne durumda olduğunu gör.</p>
            </div>

            <div class="kyk-grid">
                <!-- Left: Income Inputs -->
                <div class="income-inputs reveal">
                    <!-- KYK Selection -->
                    <div class="glass-card income-item">
                        <div class="income-header">
                            <div class="income-label">
                                <span class="income-icon">🏦</span> KYK Kredi / Burs
                            </div>
                            <div class="income-amount" id="kykAmount">0 ₺</div>
                        </div>
                        <select class="kyk-select" id="kykSelect" onchange="updateKYKSimulation()">
                            <!-- Generated via JS -->
                        </select>
                    </div>

                    <!-- Other Scholarships -->
                    <div class="glass-card income-item">
                        <div class="income-header">
                            <div class="income-label">
                                <span class="income-icon">🏅</span> Burs Geliri
                            </div>
                            <div class="income-amount" id="scholarshipDisplay">0 ₺</div>
                        </div>
                        <input type="number" class="kyk-input" id="scholarshipInput"
                            placeholder="Aylık burs miktarı (₺)" value="0" min="0" oninput="updateKYKSimulation()">
                    </div>

                    <!-- Part-time Job -->
                    <div class="glass-card income-item">
                        <div class="income-header">
                            <div class="income-label">
                                <span class="income-icon">💼</span> Yarı Zamanlı İş
                            </div>
                            <div class="income-amount" id="partTimeDisplay">0 ₺</div>
                        </div>
                        <input type="number" class="kyk-input" id="partTimeInput" placeholder="Aylık iş geliri (₺)"
                            value="0" min="0" oninput="updateKYKSimulation()">
                    </div>

                    <!-- Family Support -->
                    <div class="glass-card income-item">
                        <div class="income-header">
                            <div class="income-label">
                                <span class="income-icon">👨‍👩‍👧</span> Aile Desteği
                            </div>
                            <div class="income-amount" id="familyDisplay">0 ₺</div>
                        </div>
                        <input type="number" class="kyk-input" id="familyInput" placeholder="Aylık aile desteği (₺)"
                            value="0" min="0" oninput="updateKYKSimulation()">
                    </div>
                </div>

                <!-- Right: KYK Result -->
                <div class="reveal">
                    <div class="glass-card kyk-result-card">
                        <div class="kyk-balance positive" id="kykBalance">
                            <div class="kyk-balance-label">Aylık Gelir - Gider Farkı</div>
                            <div class="kyk-balance-amount" id="kykBalanceAmount">0 ₺</div>
                            <div class="kyk-balance-status" id="kykBalanceStatus">Gelir ve gider bilgilerinizi girin
                            </div>
                        </div>

                        <div class="kyk-progress">
                            <div class="kyk-progress-header">
                                <span style="color: var(--accent-green);">Gelir</span>
                                <span style="color: var(--text-muted);" id="progressPct">0%</span>
                            </div>
                            <div class="kyk-progress-bar-bg">
                                <div class="kyk-progress-bar" id="kykProgressBar" style="width: 0%"></div>
                            </div>
                        </div>

                        <div class="kyk-detail-rows" id="kykDetails">
                            <!-- Generated via JS -->
                        </div>

                        <div class="kyk-chart-container">
                            <canvas id="kykChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <hr class="section-separator">

    <!-- ════════════ MINI TOOLS ════════════ -->
    <section class="section" id="tools">
        <div class="container">
            <div class="reveal">
                <div class="section-badge">📱 Hızlı Araçlar</div>
                <h2 class="section-title">Mini <span class="text-gradient">Hesaplamalar</span></h2>
                <p class="section-subtitle">Sık ihtiyaç duyulan hesaplamalar bir tıkla.</p>
            </div>

            <div class="mini-tools-grid reveal">
                <!-- Tool 1: Daily Limit -->
                <div class="glass-card mini-tool-card">
                    <div class="mini-tool-header">
                        <div class="mini-tool-icon">📅</div>
                        <div class="mini-tool-title-wrap">
                            <div class="mini-tool-title">Günlük Harcama Limiti</div>
                            <div class="mini-tool-desc">Aylık bütçenden günlük limitini hesapla</div>
                        </div>
                    </div>
                    <div class="mini-tool-body">
                        <div class="mini-tool-input-group">
                            <input type="number" class="mini-tool-input" id="dailyBudgetInput" placeholder="Aylık bütçe (₺)">
                        </div>
                        <button class="mini-tool-btn" onclick="calcDailyLimit()">📊 Hesapla</button>
                        <div class="mini-tool-result">
                            <div class="mini-tool-result-text" id="dailyLimitResult">Bütçeni gir →</div>
                        </div>
                    </div>
                </div>

                <!-- Tool 2: Meal Plan -->
                <div class="glass-card mini-tool-card">
                    <div class="mini-tool-header">
                        <div class="mini-tool-icon">🍽️</div>
                        <div class="mini-tool-title-wrap">
                            <div class="mini-tool-title">Yemek Planı Hesaplayıcı</div>
                            <div class="mini-tool-desc">Günlük kaç TL yemek bütçen var?</div>
                        </div>
                    </div>
                    <div class="mini-tool-body">
                        <div class="mini-tool-input-group">
                            <input type="number" class="mini-tool-input" id="mealBudgetInput" placeholder="Aylık yemek bütçesi (₺)">
                        </div>
                        <button class="mini-tool-btn" onclick="calcMealPlan()">🍴 Hesapla</button>
                        <div class="mini-tool-result">
                            <div class="mini-tool-result-text" id="mealPlanResult">Yemek bütçeni gir →</div>
                        </div>
                    </div>
                </div>

                <!-- Tool 3: Savings Goal -->
                <div class="glass-card mini-tool-card">
                    <div class="mini-tool-header">
                        <div class="mini-tool-icon">🎯</div>
                        <div class="mini-tool-title-wrap">
                            <div class="mini-tool-title">Tasarruf Hedefi</div>
                            <div class="mini-tool-desc">Hedef tutarına ulaşmak için günlük tasarruf</div>
                        </div>
                    </div>
                    <div class="mini-tool-body">
                        <div class="mini-tool-input-group">
                            <input type="number" class="mini-tool-input" id="savingsGoalInput" placeholder="Hedef tutar (₺)">
                            <input type="number" class="mini-tool-input" id="savingsMonthsInput" placeholder="Kaç ay?">
                        </div>
                        <button class="mini-tool-btn" onclick="calcSavingsGoal()">🎯 Hesapla</button>
                        <div class="mini-tool-result">
                            <div class="mini-tool-result-text" id="savingsGoalResult">Hedefini gir →</div>
                        </div>
                    </div>
                </div>

                <!-- Tool 4: KYK Repayment -->
                <div class="glass-card mini-tool-card">
                    <div class="mini-tool-header">
                        <div class="mini-tool-icon">🏦</div>
                        <div class="mini-tool-title-wrap">
                            <div class="mini-tool-title">KYK Kredi Geri Ödeme</div>
                            <div class="mini-tool-desc">Mezuniyet sonrası aylık taksit hesabı</div>
                        </div>
                    </div>
                    <div class="mini-tool-body">
                        <div class="mini-tool-input-group">
                            <input type="number" class="mini-tool-input" id="kykLoanInput" placeholder="Aylık kredi (₺)">
                            <input type="number" class="mini-tool-input" id="kykYearsInput" placeholder="Kaç yıl aldın?">
                        </div>
                        <button class="mini-tool-btn" onclick="calcKYKRepayment()">🏦 Hesapla</button>
                        <div class="mini-tool-result">
                            <div class="mini-tool-result-text" id="kykRepayResult">Kredi bilgilerini gir →</div>
                        </div>
                    </div>
                </div>

                <!-- Tool 5: Currency Converter -->
                <div class="glass-card mini-tool-card">
                    <div class="mini-tool-header">
                        <div class="mini-tool-icon">💱</div>
                        <div class="mini-tool-title-wrap">
                            <div class="mini-tool-title">Döviz Çevirici</div>
                            <div class="mini-tool-desc">Erasmus / yurt dışı için anlık kur hesabı</div>
                        </div>
                    </div>
                    <div class="mini-tool-body">
                        <div class="mini-tool-input-group">
                            <input type="number" class="mini-tool-input" id="currencyAmountInput" placeholder="Miktar">
                            <select class="mini-tool-input" id="currencyFrom">
                                <option value="USD">USD ($) — Amerikan Doları</option>
                                <option value="EUR">EUR (€) — Euro</option>
                                <option value="GBP">GBP (£) — İngiliz Sterlini</option>
                            </select>
                        </div>
                        <button class="mini-tool-btn" onclick="calcCurrency()">💱 TRY'ye Çevir</button>
                        <div class="mini-tool-result">
                            <div class="mini-tool-result-text" id="currencyResult">Döviz miktarını gir →</div>
                        </div>
                    </div>
                </div>

                <!-- Tool 6: Roommate Splitter -->
                <div class="glass-card mini-tool-card">
                    <div class="mini-tool-header">
                        <div class="mini-tool-icon">👥</div>
                        <div class="mini-tool-title-wrap">
                            <div class="mini-tool-title">Ev Arkadaşı Bölüştürücü</div>
                            <div class="mini-tool-desc">Ortak harcamaları kişi başı hesapla</div>
                        </div>
                    </div>
                    <div class="mini-tool-body">
                        <div class="mini-tool-input-group">
                            <input type="number" class="mini-tool-input" id="splitTotalInput" placeholder="Toplam tutar (₺)">
                            <input type="number" class="mini-tool-input" id="splitPeopleInput" placeholder="Kişi sayısı" min="2" value="2">
                        </div>
                        <button class="mini-tool-btn" onclick="calcRoommateSplit()">👥 Bölüştür</button>
                        <div class="mini-tool-result">
                            <div class="mini-tool-result-text" id="splitResult">Tutar ve kişi sayısı gir →</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <hr class="section-separator">

    <!-- ════════════ CITY COMPARISON ════════════ -->
    <section class="section orbis-section-video" id="cities">
        <!-- Video Background -->
        <div class="orbis-video-bg orbis-video-blurred">
            <video autoplay loop muted playsinline>
                <source src="https://d8j0ntlcm91z4.cloudfront.net/user_38xzZboKViGWJOttwIXH07lWA1P/hf_20260331_055729_72d66327-b59e-4ae9-bb70-de6ccb5ecdb0.mp4" type="video/mp4">
            </video>
        </div>
        <div class="container" style="position:relative; z-index:2;">
            <div class="reveal">
                <div class="section-badge">🏙️ Şehir Analizi</div>
                <h2 class="section-title">Şehir Bazlı <span class="text-gradient">Maliyet Karşılaştırma</span></h2>
                <p class="section-subtitle">İki üniversite şehrini yan yana karşılaştır, hangisi daha uygun?</p>
            </div>

            <div class="city-selector-group reveal">
                <div class="city-selector">
                    <label for="city1">Birinci Şehir</label>
                    <select class="city-select" id="city1" onchange="updateCityComparison()">
                        <!-- Generated via JS -->
                    </select>
                </div>
                <div class="vs-badge">VS</div>
                <div class="city-selector">
                    <label for="city2">İkinci Şehir</label>
                    <select class="city-select" id="city2" onchange="updateCityComparison()">
                        <!-- Generated via JS -->
                    </select>
                </div>
            </div>

            <div class="comparison-results reveal" id="comparisonResults">
                <!-- Two City Cards -->
                <div class="glass-card city-card" id="cityCard1"></div>
                <div class="glass-card city-card" id="cityCard2"></div>
            </div>

            <div class="comparison-chart-wrapper reveal">
                <div class="glass-card comparison-chart-card">
                    <div class="dashboard-chart-title">📊 Maliyet Karşılaştırma Grafiği</div>
                    <div class="dashboard-chart-container">
                        <canvas id="comparisonChart"></canvas>
                    </div>
                    <div class="comparison-diff" id="comparisonDiff">
                        <!-- Generated via JS -->
                    </div>
                </div>
            </div>
        </div>
    </section>

    <hr class="section-separator">

    <!-- ════════════ AI TIPS ════════════ -->
    <section class="section" id="ai-tips">
        <div class="container">
            <div class="reveal">
                <div class="section-badge">🤖 Yapay Zeka Önerileri</div>
                <h2 class="section-title">AI Destekli <span class="text-gradient">Tasarruf Tavsiyeleri</span></h2>
                <p class="section-subtitle">Bütçene göre kişiselleştirilmiş tasarruf önerileri ve ipuçları.</p>
            </div>

            <div class="ai-tips-grid reveal" id="aiTipsGrid">
                <!-- Generated via JS -->
            </div>

            <div class="ai-motivation reveal">
                <div class="glass-card motivation-card">
                    <div class="motivation-text" id="motivationText"></div>
                </div>
            </div>

            <div class="savings-target reveal">
                <div class="glass-card savings-target-card">
                    <div class="section-badge" style="margin-bottom: 8px;">🎯 Bu Ayın Hedefi</div>
                    <div class="savings-target-amount" id="savingsTarget">0 ₺</div>
                    <div class="savings-target-desc">Önerilen aylık tasarruf hedefi (gelirinizin %10'u)</div>
                </div>
            </div>
        </div>
    </section>

    <hr class="section-separator">

    <!-- ════════════ DASHBOARD ════════════ -->
    <section class="section" id="dashboard">
        <div class="container">
            <div class="reveal">
                <div class="section-badge">📊 Analiz Paneli</div>
                <h2 class="section-title">İnteraktif <span class="text-gradient">Dashboard</span></h2>
                <p class="section-subtitle">Tüm verilerini tek bir panelden takip et.</p>
            </div>

            <div class="dashboard-grid reveal">
                <div class="glass-card dashboard-chart-card">
                    <div class="dashboard-chart-title">🍩 Harcama Dağılımı</div>
                    <div class="dashboard-chart-container">
                        <canvas id="dashboardDonut"></canvas>
                    </div>
                </div>
                <div class="glass-card dashboard-chart-card">
                    <div class="dashboard-chart-title">📊 Gelir vs. Gider</div>
                    <div class="dashboard-chart-container">
                        <canvas id="dashboardBar"></canvas>
                    </div>
                </div>
                <div class="glass-card dashboard-chart-card" style="grid-column: 1 / -1;">
                    <div class="dashboard-chart-title">📈 Aylık Trend Simülasyonu</div>
                    <div class="dashboard-chart-container">
                        <canvas id="dashboardLine"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <hr class="section-separator">

    <!-- ════════════ HOW IT WORKS ════════════ -->
    <section class="section" id="how-it-works">
        <div class="container">
            <div class="reveal" style="text-align: center;">
                <div class="section-badge">🚀 Nasıl Çalışır?</div>
                <h2 class="section-title">4 Adımda <span class="text-gradient">Bütçe Ustası</span> Ol</h2>
                <p class="section-subtitle" style="margin: 0 auto;">ÜniBütçe ile finansal özgürlüğüne giden yol çok basit.</p>
            </div>
            <div class="how-steps-grid reveal">
                <div class="glass-card timeline-step hover-lift">
                    <div class="step-number">01</div>
                    <div class="step-icon">📝</div>
                    <h4>Hesap Oluştur</h4>
                    <p>E-posta ile saniyeler içinde kayıt ol ve dashboard'una eriş.</p>
                </div>
                <div class="glass-card timeline-step hover-lift">
                    <div class="step-number">02</div>
                    <div class="step-icon">💰</div>
                    <h4>Bütçeni Belirle</h4>
                    <p>Gelirlerini ve aylık gider kalemlerini hesaplayıcı ile planla.</p>
                </div>
                <div class="glass-card timeline-step hover-lift">
                    <div class="step-number">03</div>
                    <div class="step-icon">📊</div>
                    <h4>Harcamanı Takip Et</h4>
                    <p>Gelir ve giderlerini kaydet, isı haritası ve grafiklerle analiz et.</p>
                </div>
                <div class="glass-card timeline-step hover-lift">
                    <div class="step-number">04</div>
                    <div class="step-icon">🎯</div>
                    <h4>Tasarruf Et</h4>
                    <p>AI destekli öneriler al, hedefler koy ve finansal sağlığını güçlendir.</p>
                </div>
            </div>
        </div>
    </section>

    <hr class="section-separator">

    <!-- ════════════ FAQ ════════════ -->
    <section class="section" id="faq">
        <div class="container">
            <div class="reveal" style="text-align: center;">
                <div class="section-badge">❓ Sık Sorulan Sorular</div>
                <h2 class="section-title">Merak <span class="text-gradient">Edilenler</span></h2>
                <p class="section-subtitle" style="margin: 0 auto;">Öğrenci yaşamı, burs ve bütçe hakkında en çok
                    sorulan sorular.</p>
            </div>
            <div class="faq-list reveal" id="faqList">
                <!-- Generated via JS -->
            </div>
        </div>
    </section>

    <hr class="section-separator">

    <!-- ════════════ CTA — ORBIS STYLE ════════════ -->
    <section class="orbis-cta-section">
        <div class="orbis-video-bg">
            <video autoplay loop muted playsinline>
                <source src="https://d8j0ntlcm91z4.cloudfront.net/user_38xzZboKViGWJOttwIXH07lWA1P/hf_20260331_055729_72d66327-b59e-4ae9-bb70-de6ccb5ecdb0.mp4" type="video/mp4">
            </video>
        </div>
        <div class="container">
            <span class="orbis-cta-cursive">Geleceğini Planla</span>
            <h2 class="orbis-cta-heading">KATIL.<br>BÜTÇENİ YÖNETMEYİ ÖĞREN.<br>GELECEĞİNİ ŞEKİLLENDİR.</h2>
            <a href="#budget" class="orbis-cta-btn">
                🚀 Hemen Başla
            </a>
        </div>
    </section>

    <hr class="section-separator">

    <!-- ════════════ FOOTER ════════════ -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-brand">
                    <div class="footer-brand-name" style="display:flex; align-items:center; gap:8px;">
                        <svg class="pub-nav-logo-svg" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg" style="width: 28px; height: 28px;">
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
                        ÜniBütçe
                    </div>
                    <p class="footer-brand-desc">
                        Üniversite öğrencilerinin finansal farkındalığını artırmak ve bütçe yönetimini kolaylaştırmak
                        için tasarlanmış yapay zeka destekli platform.
                    </p>

                </div>
                <div>
                    <div class="footer-heading">Hızlı Erişim</div>
                    <ul class="footer-links">
                        <li><a href="#budget">Bütçe Hesaplayıcı</a></li>
                        <li><a href="#cities">Şehir Karşılaştırma</a></li>
                        <li><a href="#kyk">KYK Simülatörü</a></li>
                        <li><a href="#tools">Mini Araçlar</a></li>
                    </ul>
                </div>
                <div>
                    <div class="footer-heading">Kaynaklar</div>
                    <ul class="footer-links">
                        <li><a href="#faq">Sıkça Sorulan Sorular</a></li>
                        <li><a href="#ai-tips">Tasarruf Önerileri</a></li>
                        <li><a href="#dashboard">Dashboard</a></li>
                        <li><a href="#">İletişim</a></li>
                    </ul>
                </div>
                <div>
                    <div class="footer-heading">Hesaplama Araçları</div>
                    <ul class="footer-links">
                        <li><a href="tools/bilesik-faiz.php">Bileşik Faiz Hesaplayıcı</a></li>
                        <li><a href="tools/asgari-ucret.php">Asgari Ücret Net</a></li>
                        <li><a href="tools/kpss-puan.php">KPSS Puan Tahmin</a></li>
                        <li><a href="tools/erasmus-grant.php">Erasmus Hibe</a></li>
                        <li><a href="tools/kyk-yurt-puani.php">KYK Yurt Puanı</a></li>
                        <li><a href="tools/finansal-iq.php">Finansal IQ Testi</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>© 2026 ÜniBütçe — Öğrenciler için, öğrenciler tarafından ❤️ ile yapıldı.</p>
            </div>
        </div>
    </footer>


    <script src="api/dynamic_data.js.php?v=<?php echo time(); ?>"></script>
    <script src="data.js?v=<?php echo time(); ?>"></script>
    <script>
        console.log('Test dynamic_data:', typeof CITY_DATA);
        console.log('Test data.js:', typeof EXPENSE_CATEGORIES);
    </script>
    <script src="app.js?v=<?php echo time(); ?>"></script>
    <script src="js/auth.js?v=<?php echo time(); ?>"></script>
    
    <!-- Auth Modal Include -->
    <?php include 'includes/auth_modal.php'; ?>

    <!-- ══ CYBER COACH — Floating Chat Widget ══ -->
    <div id="cyberCoachWidget" class="coach-widget">
        <!-- Trigger Button -->
        <button id="coachToggleBtn" class="coach-toggle-btn" onclick="toggleCoach()" aria-label="Cyber Coach Aç">
            <div class="coach-btn-inner">
                <span class="coach-btn-icon">🤖</span>
                <span class="coach-btn-label">Cyber Coach</span>
                <span class="coach-btn-pulse"></span>
            </div>
        </button>

        <!-- Chat Panel -->
        <div id="coachPanel" class="coach-panel" role="dialog" aria-label="Cyber Coach Sohbet Paneli">
            <div class="coach-header">
                <div class="coach-avatar">🤖</div>
                <div class="coach-header-info">
                    <span class="coach-name">Cyber Coach</span>
                    <span class="coach-status"><span class="status-dot"></span> Aktif</span>
                </div>
                <button class="coach-close-btn" onclick="toggleCoach()" aria-label="Kapat">✕</button>
            </div>

            <div id="coachMessages" class="coach-messages">
                <div class="coach-msg bot">
                    <div class="msg-bubble">&#128075; Merhaba! Ben <strong>Cyber Coach</strong>. KYK burs miktar&#305;, &#351;ehir maliyetleri, tasarruf stratejileri veya &#246;&#287;renci b&#252;t&#231;esi hakk&#305;nda her &#351;eyi sorabilirsin!</div>
                </div>
                <div class="coach-quick-asks">
                    <button onclick="sendQuickAsk('İstanbul\'da öğrenci olarak aylık ne kadar harcama yapılır?')">&#127961;&#65039; &#304;stanbul maliyeti</button>
                    <button onclick="sendQuickAsk('KYK burs miktarları 2025\'te ne kadar?')">&#127891; KYK burs miktar&#305;</button>
                    <button onclick="sendQuickAsk('Aylık 5000 TL ile nasıl tasarruf yapabilirim?')">&#128176; Tasarruf t&#252;yolar&#305;</button>
                    <button onclick="sendQuickAsk('Öğrenciler için en uygun part-time iş önerileri neler?')">&#128188; Part-time i&#351;</button>
                </div>
            </div>

            <div class="coach-input-area">
                <div class="coach-input-wrapper">
                    <input
                        type="text"
                        id="coachInput"
                        class="coach-input"
                        placeholder="Bir &#351;ey sor..."
                        maxlength="500"
                        onkeydown="if(event.key==='Enter' && !event.shiftKey){ event.preventDefault(); sendCoachMessage(); }"
                    />
                    <span id="coachCounter" class="coach-counter" title="10 dakikada kalan mesaj hakkı">30/30</span>
                </div>
                <button class="coach-send-btn" onclick="sendCoachMessage()" id="coachSendBtn">
                    <i class="fa-solid fa-paper-plane"></i>
                </button>
            </div>
        </div>
    </div>

    <script>
    // ═══ CYBER COACH WIDGET ═══
    function toggleCoach() {
        const panel = document.getElementById('coachPanel');
        const btn = document.getElementById('coachToggleBtn');
        const isOpen = panel.classList.toggle('open');
        btn.classList.toggle('active', isOpen);
        if (isOpen) {
            setTimeout(() => document.getElementById('coachInput')?.focus(), 300);
        }
    }

    function sendQuickAsk(text) {
        document.getElementById('coachInput').value = text;
        sendCoachMessage();
    }

    async function sendCoachMessage() {
        const input   = document.getElementById('coachInput');
        const sendBtn = document.getElementById('coachSendBtn');
        const messages = document.getElementById('coachMessages');
        const counter  = document.getElementById('coachCounter');
        const text = input.value.trim();
        if (!text) return;

        // Remove quick asks on first message
        document.querySelector('.coach-quick-asks')?.remove();

        appendBubble(messages, 'user', text);
        input.value = '';
        sendBtn.disabled = true;

        // Typing indicator
        const typingId = 'typing-' + Date.now();
        messages.insertAdjacentHTML('beforeend', `
            <div id="${typingId}" class="coach-msg bot typing-indicator">
                <div class="msg-bubble"><span></span><span></span><span></span></div>
            </div>
        `);
        messages.scrollTop = messages.scrollHeight;

        try {
            const res  = await fetch('api/cyber_coach.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message: text })
            });
            const data = await res.json();
            document.getElementById(typingId)?.remove();

            if (data.rate_limit) {
                appendBubble(messages, 'bot rate-limit-msg', data.reply);
                if (counter) { counter.textContent = '0/30'; counter.classList.add('exhausted'); }
                sendBtn.disabled = false;
                messages.scrollTop = messages.scrollHeight;
                return;
            }

            appendBubble(messages, 'bot', data.reply || data.error || '⚠️ Bir hata oluştu.');

            // Update remaining counter
            if (counter && data.remaining !== undefined) {
                counter.textContent = `${data.remaining}/30`;
                counter.classList.toggle('low', data.remaining <= 5);
                counter.classList.remove('exhausted');
            }
        } catch (e) {
            document.getElementById(typingId)?.remove();
            appendBubble(messages, 'bot', '🔌 Bağlantı hatası. Lütfen tekrar dene.');
        }

        sendBtn.disabled = false;
        messages.scrollTop = messages.scrollHeight;
        input.focus();
    }

    function appendBubble(container, classes, text) {
        const div = document.createElement('div');
        div.className = `coach-msg ${classes}`;
        div.innerHTML = `<div class="msg-bubble">${text}</div>`;
        container.appendChild(div);
        container.scrollTop = container.scrollHeight;
    }
    </script>

    <!-- How It Works + Testimonials + Typing styles -->
    <style>
        .how-steps-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 24px;
            margin-top: 40px;
        }
        .how-steps-grid .timeline-step {
            text-align: center;
            padding: 36px 24px;
            position: relative;
        }
        .how-steps-grid .step-number {
            font-family: var(--font-mono);
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, rgba(0,240,255,0.2), rgba(112,0,255,0.2));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
            margin-bottom: 12px;
        }
        .how-steps-grid .step-icon {
            font-size: 2.5rem;
            margin-bottom: 16px;
        }
        .how-steps-grid h4 {
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 8px;
        }
        .how-steps-grid p {
            font-size: 0.88rem;
            color: var(--text-secondary);
            line-height: 1.6;
        }
        @media (max-width: 900px) {
            .how-steps-grid { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 500px) {
            .how-steps-grid { grid-template-columns: 1fr; }
        }
    </style>

    <script>
        // Typing animation for hero
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof initTypingAnimation === 'function') {
                initTypingAnimation('#heroTyping', [
                    'Akıllıca Planla',
                    'Kontrol Altına Al',
                    'Güvence Altına Al',
                    'Kolayca Yönet'
                ]);
            }
        });
    </script>
</body>
</html>
