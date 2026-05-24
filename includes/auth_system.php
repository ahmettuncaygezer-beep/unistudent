<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class AuthSystem
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Register a new user
     * 
     * @param string $fullName
     * @param string $username
     * @param string $email
     * @param string $password
     * @param string $universityName
     * @return array ['success' => bool, 'message' => string]
     */
    public function register($fullName, $username, $email, $password, $universityName)
    {
        // 1. Basic Validation
        if (empty($fullName) || empty($username) || empty($email) || empty($password) || empty($universityName)) {
            return ['success' => false, 'message' => 'Lütfen tüm alanları doldurun.'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Geçersiz email formatı.'];
        }

        // 2. Username Validation (No spaces, alphanumeric + underscore)
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            return ['success' => false, 'message' => 'Kullanıcı adı sadece harf, rakam ve alt çizgi içerebilir (boşluk kullanılamaz).'];
        }

        // 3. Check Uniqueness (Email & Username)
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $stmt->execute([$email, $username]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Bu email veya kullanıcı adı zaten kullanımda.'];
        }

        // 4. Role Determination
        $role = 'user';
        if (strpos($email, '.edu.tr') !== false) {
            $role = 'verified';
        }

        // 5. Password Hashing
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        // 6. Insert User
        try {
            $stmt = $this->pdo->prepare("INSERT INTO users (full_name, username, email, password_hash, university_name, role, auth_provider) VALUES (?, ?, ?, ?, ?, ?, 'local')");
            $stmt->execute([$fullName, $username, $email, $passwordHash, $universityName, $role]);

            // Auto Login
            $userId = $this->pdo->lastInsertId();
            $this->setSession($userId, $fullName, $role, $universityName);

            return ['success' => true, 'message' => 'Kayıt başarılı! Yönlendiriliyorsunuz...'];
        }
        catch (PDOException $e) {
            error_log($e->getMessage()); // Log error for debugging
            return ['success' => false, 'message' => 'Veritabanı hatası oluştu.'];
        }
    }

    /**
     * Login existing user (Email OR Username)
     * 
     * @param string $identifier (email or username)
     * @param string $password
     * @return array ['success' => bool, 'message' => string]
     */
    public function login($identifier, $password)
    {
        if (empty($identifier) || empty($password)) {
            return ['success' => false, 'message' => 'Kullanıcı adı/Email ve şifre gereklidir.'];
        }

        // Check against both email and username
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ? OR username = ?");
        $stmt->execute([$identifier, $identifier]);
        $user = $stmt->fetch();

        // Check password if user exists and has a local password
        if ($user) {
            if ($user['auth_provider'] !== 'local' && empty($user['password_hash'])) {
                return ['success' => false, 'message' => 'Bu hesap sosyal medya ile oluşturulmuş. Lütfen ilgili butonu kullanın.'];
            }

            if (password_verify($password, $user['password_hash'])) {
                $this->setSession($user['id'], $user['full_name'], $user['role'], $user['university_name']);

                // Calculate initials
                $initials = '';
                $names = explode(' ', $user['full_name']);
                foreach ($names as $name) {
                    $initials .= strtoupper(substr($name, 0, 1));
                }
                $initials = substr($initials, 0, 2);

                return [
                    'success' => true,
                    'message' => 'Giriş başarılı!',
                    'user' => [
                        'name' => $user['full_name'],
                        'initials' => $initials,
                        'role' => $user['role']
                    ]
                ];
            }
        }

        return ['success' => false, 'message' => 'Hatalı bilgiler.'];
    }

    /**
     * Handle OAuth Login/Register
     * 
     * @param string $provider 'google' or 'facebook'
     * @param string $providerId
     * @param string $email
     * @param string $fullName
     * @return bool
     */
    public function oauthLogin($provider, $providerId, $email, $fullName)
    {
        // Check if user exists by provider_id OR email
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE (auth_provider = ? AND provider_id = ?) OR email = ?");
        $stmt->execute([$provider, $providerId, $email]);
        $user = $stmt->fetch();

        if ($user) {
            // Mevcut kullanıcı — provider kontrolü
            if ($user['auth_provider'] === 'local') {
                // Yerel hesap var ama OAuth ile giriş deniyor.
                // Hesap birleştirme onaysız otomatik yapılmaz — güvenlik riski.
                error_log("OAuth login blocked: local account exists for email $email");
                return false; // Kullanıcı şifresiyle giriş yapmalıdır
            }

            $this->setSession($user['id'], $user['full_name'], $user['role'], $user['university_name']);
            return true;
        }
        else {
            // Register new OAuth user
            // Generate a unique username from email prefix + random suffix
            $usernameBase = explode('@', $email)[0];
            $username = $this->generateUniqueUsername($usernameBase);
            $role = (strpos($email, '.edu.tr') !== false) ? 'verified' : 'user';

            try {
                $stmt = $this->pdo->prepare("INSERT INTO users (full_name, username, email, university_name, role, auth_provider, provider_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
                // University is unknown initially for purely OAuth flows unless asked
                $stmt->execute([$fullName, $username, $email, 'Belirtilmemiş', $role, $provider, $providerId]);

                $userId = $this->pdo->lastInsertId();
                $this->setSession($userId, $fullName, $role, 'Belirtilmemiş');
                return true;
            }
            catch (PDOException $e) {
                error_log("OAuth Register Error: " . $e->getMessage());
                return false;
            }
        }
    }

    private function generateUniqueUsername($base): string
    {
        $username = preg_replace('/[^a-zA-Z0-9_]/', '', $base);
        if ($username === '') $username = 'user';
        $original = $username;
        $counter = 1;
        $maxAttempts = 100; // Sonsuz döngü koruması

        while ($counter <= $maxAttempts) {
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if (!$stmt->fetch()) {
                return $username;
            }
            $username = $original . '_' . $counter;
            $counter++;
        }
        // Son çare: timestamp suffixli benzersiz kullanıcı adı
        return $original . '_' . substr(uniqid(), -6);
    }

    public function logout()
    {
        session_unset();
        session_destroy();
        return ['success' => true, 'message' => 'Çıkış yapıldı.'];
    }

    private function setSession($id, $name, $role, $university): void
    {
        // Session fixation salgısını önle — yeni ID ata
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }

        $_SESSION['user_id'] = $id;
        $_SESSION['user_name'] = $name;
        $_SESSION['user_role'] = $role;
        $_SESSION['user_university'] = $university;

        // Update login streak
        try {
            $stmt = $this->pdo->prepare("SELECT last_login_date, login_streak FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch();

            $today = date('Y-m-d');
            $lastLogin = $row['last_login_date'] ?? null;
            $currentStreak = (int)($row['login_streak'] ?? 0);

            if ($lastLogin === $today) {
                // Already logged in today, keep streak
            } elseif ($lastLogin === date('Y-m-d', strtotime('-1 day'))) {
                // Consecutive day, increment streak
                $currentStreak++;
                $stmt = $this->pdo->prepare("UPDATE users SET login_streak = ?, last_login_date = ? WHERE id = ?");
                $stmt->execute([$currentStreak, $today, $id]);
            } else {
                // Streak broken, reset to 1
                $stmt = $this->pdo->prepare("UPDATE users SET login_streak = 1, last_login_date = ? WHERE id = ?");
                $stmt->execute([$today, $id]);
            }
        } catch (Exception $e) {
            // Silently fail if columns don't exist yet
        }
    }

    public function isLoggedIn()
    {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }

        // Session timeout: 2 saat hareketsizlik → çıkış
        $timeout = 2 * 60 * 60;
        $now = time();
        if (isset($_SESSION['last_activity']) && ($now - $_SESSION['last_activity']) > $timeout) {
            $this->logout();
            return false;
        }
        $_SESSION['last_activity'] = $now;
        return true;
    }

    public function getCurrentUser()
    {
        if (!$this->isLoggedIn()) {
            return null;
        }
        return [
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'],
            'role' => $_SESSION['user_role'],
            'university' => $_SESSION['user_university']
        ];
    }
}
?>
