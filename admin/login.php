<?php
// Oturumu güvenli başlat (zaten başlatılmışsa tekrar başlatma)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Config'den admin bilgilerini al
require_once __DIR__ . '/../config.php';

$error = '';

// CSRF token üret
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF doğrula
    $csrf = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $csrf)) {
        $error = 'Güvenlik doğrulaması başarısız. Sayfayı yenileyin.';
    }

    $user = trim($_POST['username'] ?? '');
    $pass = $_POST['password'] ?? '';
    $pin  = $_POST['pin'] ?? '';

    // Rate limiting: session bazlı brute force koruması
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = 0;
        $_SESSION['login_last_attempt'] = time();
    }

    // 5 denemeden sonra 5 dakika kilitle
    if ($_SESSION['login_attempts'] >= 5) {
        $lockoutTime = 300; // 5 dakika
        $elapsed = time() - $_SESSION['login_last_attempt'];
        if ($elapsed < $lockoutTime) {
            $remaining = ceil(($lockoutTime - $elapsed) / 60);
            $error = "Çok fazla hatalı giriş denemesi. {$remaining} dakika bekleyin.";
        } else {
            // Kilidi sıfırla
            $_SESSION['login_attempts'] = 0;
        }
    }

    if (empty($error)) {
        $userOk = hash_equals(ADMIN_USERNAME, $user);
        $passOk = password_verify($pass, ADMIN_PASSWORD_HASH);
        $pinOk  = hash_equals(ADMIN_MASTER_PIN, $pin);

        if ($userOk && $passOk && $pinOk) {
            $_SESSION['login_attempts'] = 0;
            session_regenerate_id(true);
            $_SESSION['logged_in'] = true;
            $_SESSION['admin_login_time'] = time();
            header('Location: index.php');
            exit;
        } else {
            $_SESSION['login_attempts']++;
            $_SESSION['login_last_attempt'] = time();
            $error = 'Hatalı kullanıcı adı veya şifre! (' . $_SESSION['login_attempts'] . '/5)';
        }
    }
}

// Zaten giriş yapmışsa yönlendir
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Girişi - ÜniBütçe</title>
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        :root {
            --primary: #6c5ce7;
            --primary-hover: #5649c0;
            --bg-dark: #1e1e2e;
            --card-bg: #2d2d3f;
            --text-main: #ffffff;
            --text-muted: #b2bec3;
            --error: #ff7675;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background: linear-gradient(135deg, #1e1e2e 0%, #000000 100%);
            color: var(--text-main);
        }

        .login-container {
            width: 100%;
            max-width: 420px;
            padding: 2rem;
            animation: fadeIn 0.8s ease-out;
        }

        .login-card {
            background: rgba(45, 45, 63, 0.7);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            padding: 2.5rem;
            border-radius: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        .login-header { text-align: center; margin-bottom: 2rem; }
        .brand-icon {
            font-size: 3.5rem;
            margin-bottom: 1rem;
            display: inline-block;
            filter: drop-shadow(0 0 15px rgba(108, 92, 231, 0.3));
        }
        .login-header h2 { font-size: 1.8rem; font-weight: 700; margin-bottom: 0.5rem; }
        .login-header p { color: var(--text-muted); font-size: 0.95rem; }

        .form-group { margin-bottom: 1.5rem; position: relative; }
        .form-label { display: block; margin-bottom: 0.5rem; color: var(--text-muted); font-size: 0.9rem; font-weight: 500; }
        .input-wrapper { position: relative; display: flex; align-items: center; }
        .input-icon { position: absolute; left: 1rem; color: var(--text-muted); font-size: 1.2rem; pointer-events: none; }

        .form-input {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 0.8rem;
            color: var(--text-main);
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        .form-input:focus { outline: none; border-color: var(--primary); background: rgba(0, 0, 0, 0.4); box-shadow: 0 0 0 4px rgba(108, 92, 231, 0.15); }
        .form-input::placeholder { color: rgba(255, 255, 255, 0.3); }

        .btn-login {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, var(--primary) 0%, #5649c0 100%);
            color: white;
            border: none;
            border-radius: 0.8rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(108, 92, 231, 0.3);
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
        }
        .btn-login:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(108, 92, 231, 0.4); }
        .btn-login:active { transform: translateY(0); }

        .error-msg {
            background: rgba(255, 118, 117, 0.15);
            color: #ff7675;
            padding: 0.8rem;
            border-radius: 0.8rem;
            margin-bottom: 1.5rem;
            text-align: center;
            font-size: 0.9rem;
            border: 1px solid rgba(255, 118, 117, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .back-link { text-align: center; margin-top: 1.5rem; }
        .back-link a {
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }
        .back-link a:hover { color: var(--text-main); }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="brand-icon">🎓</div>
                <h2>Hoş Geldiniz</h2>
                <p>Yönetim paneline erişmek için giriş yapın</p>
            </div>

            <?php if ($error): ?>
                <div class="error-msg">
                    <i class="ph ph-warning-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <div class="form-group">
                    <label class="form-label">Kullanıcı Adı</label>
                    <div class="input-wrapper">
                        <i class="ph ph-user input-icon"></i>
                        <input type="text" name="username" class="form-input" placeholder="Kullanıcı adınız" required autofocus autocomplete="username">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Şifre</label>
                    <div class="input-wrapper">
                        <i class="ph ph-lock-key input-icon"></i>
                        <input type="password" name="password" class="form-input" placeholder="Şifreniz" required autocomplete="current-password">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label" style="color: #00F0FF;">Master PIN (Güvenlik Kodu)</label>
                    <div class="input-wrapper">
                        <i class="ph ph-shield-check input-icon" style="color: #00F0FF;"></i>
                        <input type="password" name="pin" class="form-input" style="border-color: rgba(0, 240, 255, 0.3);" placeholder="UNI-XXXX" required>
                    </div>
                </div>
                <button type="submit" class="submit-btn">
                    <i class="ph ph-sign-in"></i> Giriş Yap
                </button>
            </form>

            <div class="back-link">
                <a href="../index.php"><i class="ph ph-house"></i> Siteye Geri Dön</a>
            </div>
        </div>
    </div>
</body>
</html>
