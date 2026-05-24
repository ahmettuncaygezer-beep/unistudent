<div id="authModal" class="auth-modal">
    <div class="auth-modal-content glass-card">
        <span class="close-modal">&times;</span>
        <div class="auth-tabs">
            <button class="auth-tab active" data-target="loginForm">Giriş Yap</button>
            <button class="auth-tab" data-target="registerForm">Kayıt Ol</button>
        </div>

        <form id="loginForm" class="auth-form active">
            <h2>Hoş Geldiniz</h2>
            
            <div class="social-login-group">
                <button type="button" class="btn-social google" onclick="showOAuthComingSoon('Google')">
                    <img src="https://www.svgrepo.com/show/475656/google-color.svg" alt="Google">
                    Google ile Giriş Yap
                </button>
                <button type="button" class="btn-social facebook" onclick="showOAuthComingSoon('Facebook')">
                    <img src="https://www.svgrepo.com/show/475647/facebook-color.svg" alt="Facebook">
                    Facebook ile Giriş Yap
                </button>
            </div>

            <div class="divider"><span>veya e-posta ile</span></div>

            <div class="form-group">
                <label for="loginIdentifier">Email veya Kullanıcı Adı</label>
                <input type="text" id="loginIdentifier" name="identifier" required>
            </div>
            <div class="form-group">
                <label for="loginPassword">Şifre</label>
                <input type="password" id="loginPassword" name="password" required>
            </div>
            <button type="submit" class="btn-primary">Giriş Yap</button>
            <div id="loginMessage" class="auth-message"></div>
        </form>

        <form id="registerForm" class="auth-form">
            <h2>Hesap Oluştur</h2>
            
             <div class="social-login-group">
                <button type="button" class="btn-social google" onclick="showOAuthComingSoon('Google')">
                    <img src="https://www.svgrepo.com/show/475656/google-color.svg" alt="Google">
                    Google ile Kayıt Ol
                </button>
            </div>

            <div class="divider"><span>veya form ile</span></div>

            <div class="form-group">
                <label for="registerName">Ad Soyad</label>
                <input type="text" id="registerName" name="full_name" required>
            </div>
             <div class="form-group">
                <label for="registerUsername">Kullanıcı Adı</label>
                <input type="text" id="registerUsername" name="username" placeholder="örn: ali_yilmaz" required>
                <small class="form-hint">Sadece harf, rakam ve alt çizgi.</small>
            </div>
            <div class="form-group">
                <label for="registerUniversity">Üniversite</label>
                <div class="custom-select-wrapper">
                    <div class="custom-select">
                        <div class="custom-select__trigger"><span>Üniversite Seçiniz...</span>
                            <div class="arrow"></div>
                        </div>
                        <div class="custom-select__options">
                            <!-- Populated via JS -->
                        </div>
                    </div>
                    <input type="hidden" id="registerUniversity" name="university_name" required>
                </div>
            </div>
            <div class="form-group">
                <label for="registerEmail">Email (.edu.tr önerilir)</label>
                <input type="email" id="registerEmail" name="email" required>
            </div>
            <div class="form-group">
                <label for="registerPassword">Şifre</label>
                <input type="password" id="registerPassword" name="password" required>
            </div>
            <button type="submit" class="btn-primary">Kayıt Ol</button>
            <div id="registerMessage" class="auth-message"></div>
        </form>
    </div>
</div>
