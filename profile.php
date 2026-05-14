<?php
header('Content-Type: text/html; charset=UTF-8');
require_once 'includes/db.php';
require_once 'includes/auth_system.php';

$auth = new AuthSystem($pdo);
if (!$auth->isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$currentUser = $stmt->fetch();

if (!$currentUser) {
    header('Location: index.php');
    exit;
}

$message = '';

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $fullName = $_POST['full_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $university = $_POST['university_name'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';

    if (empty($fullName) || empty($email)) {
        $message = '<div class="toast error">Ad Soyad ve E-posta zorunludur.</div>';
    }
    else {
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $currentUser['id']]);
            if ($stmt->fetch()) {
                $message = '<div class="toast error">Bu e-posta adresi başka bir kullanıcı tarafından kullanılıyor.</div>';
            }
            else {
                $sql = "UPDATE users SET full_name = ?, email = ?, university_name = ? WHERE id = ?";
                $params = [$fullName, $email, $university, $currentUser['id']];

                if (!empty($newPassword)) {
                    $currentPassword = $_POST['current_password'] ?? '';
                    if (empty($currentPassword)) {
                        $message = '<div class="toast error">Şifre değiştirmek için şu anki şifreniz gereklidir.</div>';
                        throw new Exception('Current password missing');
                    }
                    if (!password_verify($currentPassword, $currentUser['password_hash'])) {
                        $message = '<div class="toast error">Şu anki şifreniz hatalı.</div>';
                        throw new Exception('Current password incorrect');
                    }
                    $sql = "UPDATE users SET full_name = ?, email = ?, university_name = ?, password_hash = ? WHERE id = ?";
                    $params = [$fullName, $email, $university, password_hash($newPassword, PASSWORD_DEFAULT), $currentUser['id']];
                }


                $stmt = $pdo->prepare($sql);
                if ($stmt->execute($params)) {
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                    $stmt->execute([$currentUser['id']]);
                    $currentUser = $stmt->fetch();
                    $_SESSION['user_name'] = $fullName;
                    $message = '<div class="toast success">Profiliniz başarıyla güncellendi!</div>';
                }
                else {
                    $message = '<div class="toast error">Güncelleme başarısız oldu.</div>';
                }
            }
        }
        catch (Exception $e) {
        // Already handled in message variable
        }
        catch (PDOException $e) {
            $message = '<div class="toast error">Veritabanı hatası: ' . $e->getMessage() . '</div>';
        }

    }
}

// Calculate Initials
$initials = '';
$names = explode(' ', $currentUser['full_name']);
foreach ($names as $name) {
    if (!empty($name)) {
        $initials .= strtoupper(substr($name, 0, 1));
    }
}
$initials = substr($initials, 0, 2);
?>

<!DOCTYPE html>
<html lang="tr" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profilim - ÜniBütçe</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/auth.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/dashboard.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/mobile.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/animations.css?v=<?php echo time(); ?>">
    <script src="js/animations.js?v=<?php echo time(); ?>" defer></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="dashboard-body">

    <?php include 'includes/dashboard_navbar.php'; ?>

    <main class="main-content">
        <div class="toast-container" style="position: fixed; top: 20px; right: 20px; z-index: 1000;">
            <?php echo $message; ?>
        </div>

        <header class="topbar">
            <div class="welcome-text">
                <h1>Hesap Ayarları</h1>
                <p>Platform deneyimini kişiselleştir ve güvenliğini yönet.</p>
            </div>
            <div class="topbar-actions">
                <div class="date-display">
                    <i class="fa-regular fa-calendar-check"></i>
                    <?php echo date('d F Y'); ?>
                </div>
            </div>
        </header>

        <div class="profile-overhaul-container">
            <!-- Hero Card -->
            <section class="profile-hero-card">
                <div class="avatar-large-hub">
                    <div class="avatar-main">
                        <?php echo substr($initials, 0, 2); ?>
                    </div>
                    <div class="status-badge-premium">
                        <i class="fa-solid fa-shield-check"></i> <?php echo($currentUser['role'] === 'verified') ? 'Onaylı' : 'Üye'; ?>
                    </div>
                </div>
                <div class="profile-info-content">
                    <h2><?php echo htmlspecialchars($currentUser['full_name']); ?></h2>
                    <p style="color: var(--text-secondary); margin-bottom: 12px;">ÜniBütçe topluluğunun bir parçası olduğun için teşekkürler!</p>
                    <div class="profile-meta-grid">
                        <div class="meta-item-glass">
                            <i class="fa-solid fa-at"></i>
                            <span>@<?php echo htmlspecialchars($currentUser['username'] ?? 'kullanici'); ?></span>
                        </div>
                        <div class="meta-item-glass">
                            <i class="fa-solid fa-graduation-cap"></i>
                            <span><?php echo htmlspecialchars($currentUser['university_name'] ?: 'Üniversite Belirtilmedi'); ?></span>
                        </div>
                        <div class="meta-item-glass">
                            <i class="fa-solid fa-envelope"></i>
                            <span><?php echo htmlspecialchars($currentUser['email']); ?></span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Navigation Tabs -->
            <div class="profile-tabs">
                <button class="profile-tab-btn active" onclick="switchSection('general', this)">
                    <i class="fa-solid fa-id-card"></i> Kimlik Bilgileri
                </button>
                <button class="profile-tab-btn" onclick="switchSection('budget', this)">
                    <i class="fa-solid fa-wallet"></i> Bütçe Genel Bakış
                </button>
                <button class="profile-tab-btn" onclick="switchSection('security', this)">
                    <i class="fa-solid fa-user-shield"></i> Güvenlik & Şifre
                </button>
            </div>

            <!-- Form Sections -->
            <div id="section-budget" class="form-section-card" style="display: none; animation: fadeIn 0.5s ease;">
                <div class="section-title-premium">
                    <i class="fa-solid fa-chart-pie"></i>
                    <span>Kayıtlı Bütçe Analizi</span>
                </div>
                
                <?php
$budgetData = json_decode($currentUser['budget_json'] ?? '', true);
if ($budgetData && isset($budgetData['expenses'])):
    $total = $budgetData['total'] ?? 0;
    $lastSaved = isset($budgetData['lastSaved']) ? date('d.m.Y H:i', strtotime($budgetData['lastSaved'])) : 'Belirtilmedi';
?>
                    <div class="budget-summary-header" style="margin-bottom: 30px; padding: 25px; background: rgba(0, 136, 255, 0.05); border-radius: 20px; border: 1px solid rgba(0, 136, 255, 0.15); box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <h3 style="font-size: 1.5rem; font-weight: 800; color: white; margin-bottom: 5px;">Toplam: ₺<?php echo number_format((float)($total), 0, ',', '.'); ?></h3>
                                <p style="font-size: 0.8rem; color: var(--text-secondary);">Son Kayıt: <?php echo $lastSaved; ?></p>
                            </div>
                            <a href="index.php#budget" class="btn-premium-submit" style="width: auto; margin: 0; padding: 10px 25px; font-size: 0.8rem;">Hesaplayıcıya Git</a>
                        </div>
                    </div>

                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
                        <?php
    $catNames = ['housing' => 'Barınma', 'food' => 'Yemek', 'transport' => 'Ulaşım', 'social' => 'Sosyal', 'education' => 'Eğitim', 'personal' => 'Kişisel', 'utilities' => 'Faturalar', 'emergency' => 'Acil Durum'];
    $catIcons = ['housing' => 'fa-house', 'food' => 'fa-utensils', 'transport' => 'fa-bus', 'social' => 'fa-masks-theater', 'education' => 'fa-book', 'personal' => 'fa-user', 'utilities' => 'fa-file-invoice-dollar', 'emergency' => 'fa-kit-medical'];

    foreach ($budgetData['expenses'] as $id => $amount):
        $name = $catNames[$id] ?? ucfirst($id);
        $icon = $catIcons[$id] ?? 'fa-wallet';
        $pct = $total > 0 ? round(($amount / $total) * 100) : 0;
?>
                            <div class="budget-stat-premium" style="display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 25px 15px;">
                                <i class="fa-solid <?php echo $icon; ?>" style="font-size: 1.5rem; color: #00F0FF; margin-bottom: 15px; display: block;"></i>
                                <h4 style="font-size: 0.75rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;"><?php echo $name; ?></h4>
                                <div style="font-size: 1.2rem; font-weight: 800; color: white;">₺<?php echo number_format((float)($amount), 0, ',', '.'); ?></div>
                                <div style="font-size: 0.7rem; color: #00F0FF; font-weight: 700; margin-top: 5px;">%<?php echo $pct; ?></div>
                            </div>
                        <?php
    endforeach; ?>
                        </div>
                    </div>
                <?php
else: ?>
                    <div style="text-align: center; padding: 60px 20px; background: rgba(255, 255, 255, 0.02); border-radius: 30px; border: 2px dashed rgba(255, 255, 255, 0.05);">
                        <i class="fa-solid fa-wallet" style="font-size: 3rem; color: rgba(255, 255, 255, 0.1); margin-bottom: 20px; display: block;"></i>
                        <p style="color: var(--text-secondary); margin-bottom: 20px;">Henüz kaydedilmiş bir bütçen bulunmuyor.</p>
                        <a href="index.php#budget" class="btn-premium-submit" style="width: auto; display: inline-block; padding: 14px 40px;">Bütçeni Şimdi Planla</a>
                    </div>
                <?php
endif; ?>
            </div>

            <div id="section-general" class="form-section-card" style="animation: fadeIn 0.5s ease;">
                <div class="section-title-premium">
                    <i class="fa-solid fa-pen-to-square"></i>
                    <span>Bilgilerini Güncelle</span>
                </div>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="update_profile">
                    <div class="profile-form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                        <div class="form-group-premium">
                            <label>Tam Ad Soyad</label>
                            <input type="text" name="full_name" class="input-glass" value="<?php echo htmlspecialchars($currentUser['full_name']); ?>" required>
                        </div>
                        <div class="form-group-premium">
                            <label>Üniversite / Bölüm</label>
                            <input type="text" name="university_name" class="input-glass" value="<?php echo htmlspecialchars($currentUser['university_name']); ?>">
                        </div>
                        <div class="form-group-premium">
                            <label>E-posta Adresi</label>
                            <input type="email" name="email" class="input-glass" value="<?php echo htmlspecialchars($currentUser['email']); ?>" required>
                        </div>
                        <div style="display: flex; align-items: flex-end;">
                            <button type="submit" class="btn-premium-submit" style="margin: 0; padding: 14px 40px; width: auto;">
                                Güncelleştirmeleri Uygula
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <div id="section-security" class="form-section-card" style="display: none; animation: fadeIn 0.5s ease;">
                <div class="section-title-premium">
                    <i class="fa-solid fa-lock"></i>
                    <span>Hesap Güvenliği</span>
                </div>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="update_profile">
                    <input type="hidden" name="full_name" value="<?php echo htmlspecialchars($currentUser['full_name']); ?>">
                    <input type="hidden" name="email" value="<?php echo htmlspecialchars($currentUser['email']); ?>">
                    
                    <div class="profile-form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                        <div class="form-group-premium">
                            <label>Şu Anki Şifre</label>
                            <input type="password" name="current_password" class="input-glass" placeholder="••••••••••••">
                        </div>
                        <div class="form-group-premium">
                            <label>Yeni Güçlü Şifre</label>
                            <input type="password" name="new_password" class="input-glass" placeholder="••••••••••••">
                        </div>
                        <div class="form-group-premium">
                            <label>Şifre Onayı</label>
                            <input type="password" class="input-glass" placeholder="••••••••••••">
                        </div>
                        <div style="display: flex; align-items: flex-end;">
                            <button type="submit" class="btn-premium-submit" style="margin: 0; padding: 14px 40px; width: auto;">
                                Güvenli Şifreyi Kaydet
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script>
        function switchSection(sectionId, btn) {
            document.getElementById('section-general').style.display = 'none';
            document.getElementById('section-security').style.display = 'none';
            document.getElementById('section-budget').style.display = 'none';
            document.getElementById('section-' + sectionId).style.display = 'block';
            
            document.querySelectorAll('.profile-tab-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
        }

        window.addEventListener('load', () => {
            const toasts = document.querySelectorAll('.toast');
            toasts.forEach(t => {
                setTimeout(() => {
                    t.style.opacity = '0';
                    setTimeout(() => t.remove(), 300);
                }, 4000);
            });
        });
    </script>

    <!-- ══ CYBER COACH — Floating Chat Widget ══ -->
    <div id="cyberCoachWidget" class="coach-widget">
        <button id="coachToggleBtn" class="coach-toggle-btn" onclick="toggleCoach()" aria-label="Cyber Coach Aç">
            <div class="coach-btn-inner">
                <span class="coach-btn-icon">🤖</span>
                <span class="coach-btn-label">Cyber Coach</span>
                <span class="coach-btn-pulse"></span>
            </div>
        </button>
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
                    <input type="text" id="coachInput" class="coach-input" placeholder="Bir &#351;ey sor..." maxlength="500"
                        onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();sendCoachMessage();}" />
                    <span id="coachCounter" class="coach-counter" title="10 dakikada kalan mesaj hakkı">30/30</span>
                </div>
                <button class="coach-send-btn" onclick="sendCoachMessage()" id="coachSendBtn">
                    <i class="fa-solid fa-paper-plane"></i>
                </button>
            </div>
        </div>
    </div>
</body>
</html>


