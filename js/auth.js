const universities = [
    "Abant İzzet Baysal Üniversitesi",
    "Acıbadem Mehmet Ali Aydınlar Üniversitesi",
    "Adana Alparslan Türkeş Bilim ve Teknoloji Üniversitesi",
    "Adıyaman Üniversitesi",
    "Adnan Menderes Üniversitesi",
    "Afyon Kocatepe Üniversitesi",
    "Afyonkarahisar Sağlık Bilimleri Üniversitesi",
    "Ağrı İbrahim Çeçen Üniversitesi",
    "Ahi Evran Üniversitesi",
    "Akdeniz Üniversitesi",
    "Aksaray Üniversitesi",
    "Alanya Alaaddin Keykubat Üniversitesi",
    "Alanya Hamdullah Emin Paşa Üniversitesi",
    "Altınbaş Üniversitesi",
    "Amasya Üniversitesi",
    "Anadolu Üniversitesi",
    "Anka Teknoloji Üniversitesi",
    "Ankara Bilim Üniversitesi",
    "Ankara Hacı Bayram Veli Üniversitesi",
    "Ankara Medipol Üniversitesi",
    "Ankara Müzik ve Güzel Sanatlar Üniversitesi",
    "Ankara Sosyal Bilimler Üniversitesi",
    "Ankara Üniversitesi",
    "Ankara Yıldırım Beyazıt Üniversitesi",
    "Antalya Akev Üniversitesi",
    "Antalya Bilim Üniversitesi",
    "Ardahan Üniversitesi",
    "Artvin Çoruh Üniversitesi",
    "Ataşehir Adıgüzel Meslek Yüksekokulu",
    "Atatürk Üniversitesi",
    "Atılım Üniversitesi",
    "Avrasya Üniversitesi",
    "Aydın Adnan Menderes Üniversitesi",
    "Bahçeşehir Üniversitesi",
    "Balıkesir Üniversitesi",
    "Bandırma Onyedi Eylül Üniversitesi",
    "Bartın Üniversitesi",
    "Başkent Üniversitesi",
    "Batman Üniversitesi",
    "Bayburt Üniversitesi",
    "Beykent Üniversitesi",
    "Beykoz Üniversitesi",
    "Bezmialem Vakıf Üniversitesi",
    "Bilecik Şeyh Edebali Üniversitesi",
    "Bingöl Üniversitesi",
    "Biruni Üniversitesi",
    "Bitlis Eren Üniversitesi",
    "Boğaziçi Üniversitesi",
    "Bolu Abant İzzet Baysal Üniversitesi",
    "Burdur Mehmet Akif Ersoy Üniversitesi",
    "Bursa Teknik Üniversitesi",
    "Bursa Uludağ Üniversitesi",
    "Çağ Üniversitesi",
    "Çanakkale Onsekiz Mart Üniversitesi",
    "Çankaya Üniversitesi",
    "Çankırı Karatekin Üniversitesi",
    "Çukurova Üniversitesi",
    "Demiroğlu Bilim Üniversitesi",
    "Dicle Üniversitesi",
    "Doğuş Üniversitesi",
    "Dokuz Eylül Üniversitesi",
    "Düzce Üniversitesi",
    "Ege Üniversitesi",
    "Erciyes Üniversitesi",
    "Erzincan Binali Yıldırım Üniversitesi",
    "Erzurum Teknik Üniversitesi",
    "Eskişehir Osmangazi Üniversitesi",
    "Eskişehir Teknik Üniversitesi",
    "Fatih Sultan Mehmet Vakıf Üniversitesi",
    "Fenerbahçe Üniversitesi",
    "Fırat Üniversitesi",
    "Galatasaray Üniversitesi",
    "Gazi Üniversitesi",
    "Gaziantep İslam Bilim ve Teknoloji Üniversitesi",
    "Gaziantep Üniversitesi",
    "Gebze Teknik Üniversitesi",
    "Giresun Üniversitesi",
    "Gümüşhane Üniversitesi",
    "Hacettepe Üniversitesi",
    "Hakkari Üniversitesi",
    "Haliç Üniversitesi",
    "Harran Üniversitesi",
    "Hasan Kalyoncu Üniversitesi",
    "Hatay Mustafa Kemal Üniversitesi",
    "Hitit Üniversitesi",
    "Iğdır Üniversitesi",
    "Isparta Uygulamalı Bilimler Üniversitesi",
    "Işık Üniversitesi",
    "İbn Haldun Üniversitesi",
    "İhsan Doğramacı Bilkent Üniversitesi",
    "İnönü Üniversitesi",
    "İskenderun Teknik Üniversitesi",
    "İstanbul 29 Mayıs Üniversitesi",
    "İstanbul Arel Üniversitesi",
    "İstanbul Atlas Üniversitesi",
    "İstanbul Aydın Üniversitesi",
    "İstanbul Ayvansaray Üniversitesi",
    "İstanbul Bilgi Üniversitesi",
    "İstanbul Bilim Üniversitesi",
    "İstanbul Cerrahpaşa Üniversitesi",
    "İstanbul Esenyurt Üniversitesi",
    "İstanbul Galata Üniversitesi",
    "İstanbul Gedik Üniversitesi",
    "İstanbul Gelişim Üniversitesi",
    "İstanbul Kent Üniversitesi",
    "İstanbul Kültür Üniversitesi",
    "İstanbul Medeniyet Üniversitesi",
    "İstanbul Medipol Üniversitesi",
    "İstanbul Okan Üniversitesi",
    "İstanbul Rumeli Üniversitesi",
    "İstanbul Sabahattin Zaim Üniversitesi",
    "İstanbul Sağlık ve Teknoloji Üniversitesi",
    "İstanbul Şişli Meslek Yüksekokulu",
    "İstanbul Teknik Üniversitesi",
    "İstanbul Ticaret Üniversitesi",
    "İstanbul Topkapı Üniversitesi",
    "İstanbul Üniversitesi",
    "İstanbul Yeni Yüzyıl Üniversitesi",
    "İstinye Üniversitesi",
    "İzmir Bakırçay Üniversitesi",
    "İzmir Demokrasi Üniversitesi",
    "İzmir Ekonomi Üniversitesi",
    "İzmir Katip Çelebi Üniversitesi",
    "İzmir Kavram Meslek Yüksekokulu",
    "İzmir Tınaztepe Üniversitesi",
    "İzmir Yüksek Teknoloji Enstitüsü",
    "Kadir Has Üniversitesi",
    "Kafkas Üniversitesi",
    "Kahramanmaraş İstiklal Üniversitesi",
    "Kahramanmaraş Sütçü İmam Üniversitesi",
    "Kapadokya Üniversitesi",
    "Karabük Üniversitesi",
    "Karadeniz Teknik Üniversitesi",
    "Karamanoğlu Mehmetbey Üniversitesi",
    "Kastamonu Üniversitesi",
    "Kayseri Üniversitesi",
    "Kırıkkale Üniversitesi",
    "Kırklareli Üniversitesi",
    "Kırşehir Ahi Evran Üniversitesi",
    "Kilis 7 Aralık Üniversitesi",
    "Kocaeli Sağlık ve Teknoloji Üniversitesi",
    "Kocaeli Üniversitesi",
    "Koç Üniversitesi",
    "Konya Gıda ve Tarım Üniversitesi",
    "Konya Teknik Üniversitesi",
    "KTO Karatay Üniversitesi",
    "Kütahya Dumlupınar Üniversitesi",
    "Kütahya Sağlık Bilimleri Üniversitesi",
    "Lokman Hekim Üniversitesi",
    "Malatya Turgut Özal Üniversitesi",
    "Maltepe Üniversitesi",
    "Manisa Celal Bayar Üniversitesi",
    "Mardin Artuklu Üniversitesi",
    "Marmara Üniversitesi",
    "MEF Üniversitesi",
    "Mersin Üniversitesi",
    "Mimar Sinan Güzel Sanatlar Üniversitesi",
    "Mudanya Üniversitesi",
    "Muğla Sıtkı Koçman Üniversitesi",
    "Munzur Üniversitesi",
    "Muş Alparslan Üniversitesi",
    "Necmettin Erbakan Üniversitesi",
    "Nevşehir Hacı Bektaş Veli Üniversitesi",
    "Niğde Ömer Halisdemir Üniversitesi",
    "Nişantaşı Üniversitesi",
    "Nuh Naci Yazgan Üniversitesi",
    "Ondokuz Mayıs Üniversitesi",
    "Ordu Üniversitesi",
    "Orta Doğu Teknik Üniversitesi",
    "Osmaniye Korkut Ata Üniversitesi",
    "Ostim Teknik Üniversitesi",
    "Özyeğin Üniversitesi",
    "Pamukkale Üniversitesi",
    "Piri Reis Üniversitesi",
    "Recep Tayyip Erdoğan Üniversitesi",
    "Sabancı Üniversitesi",
    "Sağlık Bilimleri Üniversitesi",
    "Sakarya Uygulamalı Bilimler Üniversitesi",
    "Sakarya Üniversitesi",
    "Samsun Üniversitesi",
    "Sanko Üniversitesi",
    "Selçuk Üniversitesi",
    "Semerkand Bilim ve Medeniyet Üniversitesi",
    "Siirt Üniversitesi",
    "Sinop Üniversitesi",
    "Sivas Bilim ve Teknoloji Üniversitesi",
    "Sivas Cumhuriyet Üniversitesi",
    "Süleyman Demirel Üniversitesi",
    "Şırnak Üniversitesi",
    "Tarsus Üniversitesi",
    "TED Üniversitesi",
    "Tekirdağ Namık Kemal Üniversitesi",
    "TOBB Ekonomi ve Teknoloji Üniversitesi",
    "Tokat Gaziosmanpaşa Üniversitesi",
    "Toros Üniversitesi",
    "Trabzon Üniversitesi",
    "Trakya Üniversitesi",
    "Türk Hava Kurumu Üniversitesi",
    "Türk-Alman Üniversitesi",
    "Türk-Japon Bilim ve Teknoloji Üniversitesi",
    "Ufuk Üniversitesi",
    "Uşak Üniversitesi",
    "Üsküdar Üniversitesi",
    "Van Yüzüncü Yıl Üniversitesi",
    "Yalova Üniversitesi",
    "Yaşar Üniversitesi",
    "Yeditepe Üniversitesi",
    "Yıldız Teknik Üniversitesi",
    "Yozgat Bozok Üniversitesi",
    "Yüksek İhtisas Üniversitesi",
    "Zonguldak Bülent Ecevit Üniversitesi",
    // KKTC Üniversiteleri
    "Ada Kent Üniversitesi",
    "Akdeniz Karpaz Üniversitesi",
    "Arkın Yaratıcı Sanatlar ve Tasarım Üniversitesi",
    "Bahçeşehir Kıbrıs Üniversitesi",
    "Doğu Akdeniz Üniversitesi",
    "Girne Amerikan Üniversitesi",
    "Girne Üniversitesi",
    "Kıbrıs Amerikan Üniversitesi",
    "Kıbrıs Batı Üniversitesi",
    "Kıbrıs İlim Üniversitesi",
    "Kıbrıs Sağlık ve Toplum Bilimleri Üniversitesi",
    "Lefke Avrupa Üniversitesi",
    "Rauf Denktaş Üniversitesi",
    "Uluslararası Final Üniversitesi",
    "Uluslararası Kıbrıs Üniversitesi",
    "Yakın Doğu Üniversitesi",
    "Diğer Üniversite"
];

document.addEventListener('DOMContentLoaded', () => {
    const authModal = document.getElementById('authModal');
    const closeModal = document.querySelector('.close-modal');
    const authTabs = document.querySelectorAll('.auth-tab');
    const authForms = document.querySelectorAll('.auth-form');

    // Function to open modal (can be called from other scripts)
    // mode: 'login' (default) veya 'register'
    window.openAuthModal = (mode) => {
        authModal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        // Sekme değiştirme
        if (mode === 'register') {
            authTabs.forEach(t => t.classList.remove('active'));
            authForms.forEach(f => f.classList.remove('active'));
            const regTab = document.querySelector('.auth-tab[data-target="registerForm"]');
            const regForm = document.getElementById('registerForm');
            if (regTab)  regTab.classList.add('active');
            if (regForm) regForm.classList.add('active');
        } else {
            authTabs.forEach(t => t.classList.remove('active'));
            authForms.forEach(f => f.classList.remove('active'));
            const loginTab = document.querySelector('.auth-tab[data-target="loginForm"]');
            const loginForm = document.getElementById('loginForm');
            if (loginTab)  loginTab.classList.add('active');
            if (loginForm) loginForm.classList.add('active');
        }
    };

    // Close modal
    window.closeAuthModal = () => {
        authModal.style.display = 'none';
        document.body.style.overflow = 'auto'; // Enable scroll
    }

    closeModal.addEventListener('click', window.closeAuthModal);

    // Close when clicking outside
    window.addEventListener('click', (e) => {
        if (e.target === authModal) {
            window.closeAuthModal();
        }
    });

    // Tab switching
    authTabs.forEach(tab => {
        tab.addEventListener('click', () => {
            // Remove active class from all tabs and forms
            authTabs.forEach(t => t.classList.remove('active'));
            authForms.forEach(f => f.classList.remove('active'));

            // Add active class to clicked tab and target form
            tab.classList.add('active');
            const targetFormId = tab.getAttribute('data-target');
            document.getElementById(targetFormId).classList.add('active');
        });
    });

    // Custom Select Logic
    const uniSelectWrapper = document.querySelector('.custom-select-wrapper');
    const uniSelect = uniSelectWrapper.querySelector('.custom-select');
    const uniSelectTrigger = uniSelect.querySelector('.custom-select__trigger span');
    const uniOptionsContainer = uniSelect.querySelector('.custom-select__options');
    const uniHiddenInput = document.getElementById('registerUniversity');

    // Populate Options
    universities.forEach(uni => {
        const option = document.createElement('span');
        option.classList.add('custom-option');
        option.dataset.value = uni;
        option.textContent = uni;
        uniOptionsContainer.appendChild(option);
    });

    // Toggle Select
    uniSelect.addEventListener('click', () => {
        uniSelect.classList.toggle('open');
    });

    // Select Option
    const options = uniSelect.querySelectorAll('.custom-option');
    options.forEach(option => {
        option.addEventListener('click', (e) => {
            e.stopPropagation(); // Prevent closing immediately
            uniSelect.classList.remove('open');
            uniSelectTrigger.textContent = option.textContent;
            uniHiddenInput.value = option.dataset.value;

            options.forEach(opt => opt.classList.remove('selected'));
            option.classList.add('selected');
        });
    });

    // Close select when clicking outside
    window.addEventListener('click', (e) => {
        if (!uniSelect.contains(e.target)) {
            uniSelect.classList.remove('open');
        }
    });

    // Username Validation
    const usernameInput = document.getElementById('registerUsername');
    usernameInput.addEventListener('input', (e) => {
        let val = e.target.value;
        // Remove spaces and special chars immediately
        val = val.replace(/[^a-zA-Z0-9_]/g, '');
        e.target.value = val;
    });

    // ── Auth Handling (AJAX) ───────────────────────── //
    const handleAuthSubmit = async (e, action) => {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        formData.append('action', action);

        // Validation for Register
        if (action === 'register') {
            const uniInput = document.getElementById('registerUniversity');
            if (!uniInput || !uniInput.value) {
                const errorDiv = document.createElement('div');
                errorDiv.className = 'error-message';
                errorDiv.textContent = 'Lütfen bir üniversite seçin.';
                form.prepend(errorDiv);
                return;
            }
        }

        // reset errors
        form.querySelectorAll('.error-message').forEach(el => el.remove());
        form.querySelectorAll('.auth-success-message').forEach(el => el.remove());

        const messageDiv = form.querySelector('.auth-message');
        if (messageDiv) {
            messageDiv.textContent = 'İşlem yapılıyor...';
            messageDiv.className = 'auth-message';
        }

        try {
            // CSRF token'ı ekle
            if (window.CSRF_TOKEN && !formData.has('csrf_token')) {
                formData.append('csrf_token', window.CSRF_TOKEN);
            }
            const response = await fetch('api/auth_handler.php', {
                method: 'POST',
                headers: window.CSRF_TOKEN ? { 'X-CSRF-Token': window.CSRF_TOKEN } : {},
                body: formData,
                credentials: 'same-origin'
            });

            const result = await response.json();

            if (result.success) {
                // Show success message
                if (messageDiv) {
                    messageDiv.textContent = result.message;
                    messageDiv.classList.add('success');
                } else {
                    const successDiv = document.createElement('div');
                    successDiv.className = 'auth-success-message';
                    successDiv.style.cssText = "background: rgba(0, 230, 118, 0.2); color: #00e676; padding: 10px; border-radius: 8px; margin-bottom: 15px; text-align: center; border: 1px solid rgba(0, 230, 118, 0.3);";
                    successDiv.textContent = result.message;
                    form.prepend(successDiv);
                }

                // Close modal after delay and update UI
                setTimeout(() => {
                    window.closeAuthModal();
                    if ((action === 'login' || action === 'register') && result.user) {
                        window.isUserLoggedIn = true;
                        window.location.href = 'user_dashboard.php';
                    }
                }, 1500);

            } else {
                // Show error
                if (messageDiv) {
                    messageDiv.textContent = result.message;
                    messageDiv.classList.add('error');
                } else {
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'error-message';
                    errorDiv.textContent = result.message;
                    form.prepend(errorDiv);
                }
            }

        } catch (error) {
            console.error('Auth Error:', error);
            if (messageDiv) {
                messageDiv.textContent = 'Bir hata oluştu.';
                messageDiv.classList.add('error');
            } else {
                alert('Bir hata oluştu. Lütfen tekrar deneyin.');
            }
        }
    };

    const loginForm = document.getElementById('loginForm');
    if (loginForm) loginForm.addEventListener('submit', (e) => handleAuthSubmit(e, 'login'));

    const registerForm = document.getElementById('registerForm');
    if (registerForm) registerForm.addEventListener('submit', (e) => handleAuthSubmit(e, 'register'));

    // Bind Logout for the dynamic or static button
    document.body.addEventListener('click', (e) => {
        if (e.target.closest('#navLogoutBtn')) {
            handleLogout();
        }
    });

    // Dropdown Toggle Logic (Delegated for dynamic elements)
    document.body.addEventListener('click', (e) => {
        const trigger = e.target.closest('#userMenuBtn');
        const userMenu = e.target.closest('.nav-user-menu');
        const activeMenus = document.querySelectorAll('.nav-user-menu.active');

        if (trigger && userMenu) {
            e.stopPropagation();
            userMenu.classList.toggle('active');
        } else {
            // Close if clicking outside
            activeMenus.forEach(menu => {
                if (!menu.contains(e.target)) {
                    menu.classList.remove('active');
                }
            });
        }
    });
});

async function handleLogout() {
    const formData = new FormData();
    formData.append('action', 'logout');

    try {
        await fetch('api/auth_handler.php', { method: 'POST', body: formData });

        // If on a protected page (dashboard or profile), redirect to home
        if (window.location.pathname.includes('user_dashboard.php') || window.location.pathname.includes('profile.php')) {
            window.location.href = 'index.php';
            return;
        }

        // Remove User Dropdown
        const dropdownItem = document.querySelector('.nav-item-dropdown');
        if (dropdownItem) dropdownItem.remove();

        // Add Login Button if not exists
        const navLinks = document.getElementById('navLinks');
        if (!document.getElementById('navLoginItem')) {
            const li = document.createElement('li');
            li.id = 'navLoginItem';
            li.innerHTML = '<button onclick="window.openAuthModal()" class="nav-cta" style="border:none; cursor:pointer;">Giriş / Kayıt</button>';
            navLinks.appendChild(li);
        }

    } catch (error) {
        console.error('Logout failed:', error);
    }
}

function updateNavbarOnLogin(user) {
    const navLinks = document.getElementById('navLinks');
    const loginItem = document.getElementById('navLoginItem');

    if (loginItem) loginItem.remove(); // Remove Login Button

    // Check if dropdown already exists
    if (document.querySelector('.nav-item-dropdown')) return;

    // Create User Dropdown
    const li = document.createElement('li');
    li.className = 'nav-item-dropdown';
    li.innerHTML = `
        <div class="nav-user-menu">
            <div class="user-dropdown-trigger" id="userMenuBtn">
                <div class="user-avatar">${user.initials}</div>
                <span class="user-name">${user.name}</span>
                <span class="dropdown-arrow">▼</span>
            </div>
            <div class="user-dropdown-content" id="userDropdown">
                <div class="dropdown-header">
                     <span class="dropdown-role">${(user.role === 'verified') ? 'Doğrulanmış Öğrenci' : 'Öğrenci Hesabı'}</span>
                </div>
                <a href="profile.php" class="dropdown-item"><span class="icon">👤</span> Profilim</a>
                <div class="dropdown-divider"></div>
                <button id="navLogoutBtn" class="dropdown-item logout"><span class="icon">🚪</span> Çıkış Yap</button>
            </div>
        </div>
    `;
    navLinks.appendChild(li);
}
