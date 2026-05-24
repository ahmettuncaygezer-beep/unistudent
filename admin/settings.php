<?php
$PAGE_TITLE = 'Sistem Ayarları';
require_once 'header.php';
require_once 'api/data_manager.php';
require_once 'api/database.php';

$dm       = new DataManager();
$settings = $dm->getSettings();
$kyk      = $settings['kyk'] ?? [];
$cats     = $settings['categories'] ?? [];
$db       = getDB();

// Read AI key
$geminiKey = '';
$aiConf = __DIR__.'/api/ai_config.json';
if(file_exists($aiConf)) {
    $conf = json_decode(file_get_contents($aiConf), true);
    $geminiKey = $conf['gemini_api_key'] ?? '';
}

// Ensure feature_flags table (matches config.php schema)
try {
    // Config.php creates this with slug+enabled, but we extend with description
    try { $db->query("ALTER TABLE feature_flags ADD COLUMN description TEXT"); } catch(Exception $e){}
    try { $db->query("ALTER TABLE feature_flags ADD COLUMN flag_name VARCHAR(100)"); } catch(Exception $e){}

    // Seed default flags using config.php schema (slug, enabled)
    $flags = [
        ['challenges_enabled',   'Görev / Challenge sistemi aktif', 1],
        ['discounts_enabled',    'Öğrenci indirimleri aktif', 1],
        ['subscriptions_enabled','Abonelik takip sistemi aktif', 1],
        ['ai_blog_enabled',      'AI Blog Studio aktif', 1],
        ['notifications_enabled','Bildirim sistemi aktif', 1],
        ['guest_access_enabled', 'Misafir erişimi (demo login) aktif', 0],
        ['registration_open',    'Yeni üye kaydı açık', 1],
        ['maintenance_mode',     'Bakım modu (site kapalı)', 0],
        ['xp_system_enabled',    'XP & Seviye sistemi aktif', 1],
        ['leaderboard_enabled',  'Liderlik tablosu aktif', 1],
    ];
    $st = $db->prepare("INSERT IGNORE INTO feature_flags(slug, enabled, description) VALUES(?,?,?)");
    foreach($flags as [$slug, $desc, $val]) $st->execute([$slug, $val, $desc]);
} catch(Exception $e) {}

$featureFlags = $db->query("SELECT slug AS flag_name, enabled AS is_enabled, description, updated_at FROM feature_flags ORDER BY slug")->fetchAll(PDO::FETCH_ASSOC);


// Handle password change
$pwMsg = ''; $pwError = false;
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['_section']) && $_POST['_section']==='password'){
    require_once __DIR__ . '/../config.php';
    $cur  = $_POST['current_password'] ?? '';
    $new  = $_POST['new_password'] ?? '';
    $new2 = $_POST['new_password2'] ?? '';
    if(!password_verify($cur, ADMIN_PASSWORD_HASH)){
        $pwMsg = 'Mevcut şifre hatalı.'; $pwError = true;
    } elseif(strlen($new) < 12){
        $pwMsg = 'Yeni şifre en az 12 karakter olmalı.'; $pwError = true;
    } elseif($new !== $new2){
        $pwMsg = 'Yeni şifreler eşleşmiyor.'; $pwError = true;
    } else {
        $newHash = password_hash($new, PASSWORD_DEFAULT);
        $pwMsg = 'Yeni şifre hash\'i: <code style="color:var(--cyan);">'.$newHash.'</code><br><small>Bu değeri config.php\'deki ADMIN_PASSWORD_HASH sabitine yapıştırın.</small>';
    }
}

// Read site info
$siteUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
$phpVersion = PHP_VERSION;
$serverSoft = $_SERVER['SERVER_SOFTWARE'] ?? 'N/A';
?>

<!-- ══ TABS ══ -->
<div style="display:flex;gap:4px;margin-bottom:24px;border-bottom:1px solid var(--border);">
    <?php $tabs = ['general'=>'Genel','kyk'=>'Burs & KYK','expenses'=>'Varsayılan Giderler','ai'=>'AI & API','flags'=>'Özellik Bayrakları','security'=>'Güvenlik']; ?>
    <?php foreach($tabs as $tk=>$tl): ?>
    <button class="adm-btn adm-btn-ghost adm-btn-sm settings-tab" data-tab="<?php echo $tk; ?>"
        style="border-radius:var(--radius-sm) var(--radius-sm) 0 0;border-bottom:0;">
        <?php if($tk==='flags'): ?><i class="fa-solid fa-flag" style="font-size:0.7rem;"></i> <?php endif; ?>
        <?php echo $tl; ?>
    </button>
    <?php endforeach; ?>
</div>

<!-- ══ TAB: GENERAL ══ -->
<div class="settings-panel" id="tab-general">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">

        <!-- Site Info Card -->
        <div class="adm-card">
            <div class="adm-card-header">
                <div class="adm-card-title"><i class="fa-solid fa-globe"></i> Site Bilgileri</div>
            </div>
            <div style="display:flex;flex-direction:column;gap:14px;">
                <?php $infoItems = [
                    ['Platform','ÜniBütçe — Öğrenci Finans Paneli','fa-building'],
                    ['Site URL', $siteUrl,'fa-link'],
                    ['PHP Sürümü', $phpVersion, 'fa-php'],
                    ['Sunucu', $serverSoft, 'fa-server'],
                    ['Admin Dizini', __DIR__, 'fa-folder'],
                ]; foreach($infoItems as [$l,$v,$ic]): ?>
                <div style="display:flex;align-items:center;gap:12px;padding:10px 14px;background:var(--bg-card);border-radius:var(--radius-sm);border:1px solid var(--border);">
                    <div style="width:32px;height:32px;background:var(--cyan-dim);border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="fa-brands <?php echo $ic; ?>" style="color:var(--cyan);font-size:0.85rem;"></i>
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div style="font-size:0.72rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:1px;"><?php echo $l; ?></div>
                        <div class="adm-code" style="font-size:0.78rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?php echo htmlspecialchars($v); ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Platform Stats Card -->
        <div class="adm-card">
            <div class="adm-card-header">
                <div class="adm-card-title"><i class="fa-solid fa-chart-simple"></i> Anlık Platform Özeti</div>
            </div>
            <?php
            $dbStats = [];
            try {
                $dbStats = [
                    ['Kayıtlı Üye', (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn(), 'fa-users','cyan'],
                    ['Toplam İşlem', (int)$db->query("SELECT COUNT(*) FROM transactions")->fetchColumn(), 'fa-arrows-left-right-to-line','purple'],
                    ['Blog Yazısı', (int)$db->query("SELECT COUNT(*) FROM blogs")->fetchColumn(), 'fa-newspaper','orange'],
                    ['Aktif Abonelik', (int)$db->query("SELECT COUNT(*) FROM subscriptions WHERE is_active=1")->fetchColumn(), 'fa-rotate','green'],
                ];
            } catch(Exception $e){}
            ?>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                <?php foreach($dbStats as [$l,$v,$ic,$col]): ?>
                <div style="padding:14px;background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-md);text-align:center;">
                    <i class="fa-solid <?php echo $ic; ?>" style="color:var(--<?php echo $col; ?>);font-size:1.2rem;margin-bottom:6px;display:block;"></i>
                    <div style="font-size:1.4rem;font-weight:800;color:var(--<?php echo $col; ?>);"><?php echo number_format($v); ?></div>
                    <div style="font-size:0.72rem;color:var(--text-muted);"><?php echo $l; ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <div style="margin-top:14px;padding:12px;background:var(--bg-card);border-radius:var(--radius-md);border:1px solid var(--border);">
                <div style="font-size:0.75rem;color:var(--text-muted);margin-bottom:6px;">Disk Kullanımı</div>
                <?php
                $free = disk_free_space(DIRECTORY_SEPARATOR);
                $total= disk_total_space(DIRECTORY_SEPARATOR);
                $pct  = round(($total-$free)/$total*100,1);
                $col  = $pct>80?'red':($pct>60?'orange':'cyan');
                ?>
                <div class="adm-progress">
                    <div class="adm-progress-fill" style="width:<?php echo $pct; ?>%;background:var(--<?php echo $col; ?>);"></div>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:0.72rem;color:var(--text-muted);margin-top:5px;">
                    <span><?php echo round($free/1073741824,1); ?> GB boş</span>
                    <span style="color:var(--<?php echo $col; ?>);"><?php echo $pct; ?>% dolu</span>
                    <span><?php echo round($total/1073741824,1); ?> GB toplam</span>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- ══ TAB: KYK ══ -->
<div class="settings-panel" id="tab-kyk" style="display:none;">
    <div class="adm-card">
        <div class="adm-card-header">
            <div class="adm-card-title"><i class="fa-solid fa-graduation-cap"></i> Burs & KYK Tutarları</div>
            <div class="adm-card-subtitle">data.js'ye kaydedilir ve tüm hesaplamalarda kullanılır</div>
        </div>
        <form method="POST" action="api/settings_handler.php" style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
            <input type="hidden" name="_section" value="kyk">
            <?php
            $kykFields = [
                'kyk_yurt_fiyati'     => 'KYK Yurt Fiyatı (Aylık ₺)',
                'kyk_yemek_fiyati'    => 'KYK Yemek Fiyatı (Aylık ₺)',
                'bursvesaire_miktari' => 'Burs Miktarı (Aylık ₺)',
                'yuksek_burs'         => 'Yüksek Burs (Aylık ₺)',
                'butceli_burs'        => 'Bütçeli Burs (Aylık ₺)',
            ];
            foreach($kykFields as $key=>$label): ?>
            <div class="adm-form-group">
                <label class="adm-form-label"><?php echo $label; ?></label>
                <input type="number" class="adm-input" name="<?php echo $key; ?>"
                    value="<?php echo htmlspecialchars($kyk[$key] ?? ''); ?>" placeholder="0">
            </div>
            <?php endforeach; ?>
            <div style="grid-column:1/-1;">
                <button type="submit" class="adm-btn adm-btn-primary">
                    <i class="fa-solid fa-check"></i> Kaydet
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ══ TAB: EXPENSES ══ -->
<div class="settings-panel" id="tab-expenses" style="display:none;">
    <div class="adm-card">
        <div class="adm-card-header">
            <div class="adm-card-title"><i class="fa-solid fa-list"></i> Varsayılan Gider Kategorileri</div>
            <div class="adm-card-subtitle">Bu kategoriler kullanıcıların gider eklerken göreceği seçeneklerdir</div>
        </div>
        <form method="POST" action="api/settings_handler.php">
            <input type="hidden" name="_section" value="categories">
            <div id="catList" style="display:flex;flex-direction:column;gap:8px;margin-bottom:16px;">
                <?php foreach(($cats) as $i=>$cat): ?>
                <div class="adm-cat-row" style="display:flex;align-items:center;gap:8px;">
                    <i class="fa-solid fa-grip-lines" style="color:var(--text-muted);cursor:move;"></i>
                    <input class="adm-input adm-btn-sm" name="categories[]" value="<?php echo htmlspecialchars($cat); ?>" style="flex:1;">
                    <button type="button" class="adm-btn adm-btn-danger adm-btn-icon adm-btn-sm remove-cat"><i class="fa-solid fa-trash"></i></button>
                </div>
                <?php endforeach; ?>
            </div>
            <div style="display:flex;gap:8px;margin-bottom:16px;">
                <button type="button" class="adm-btn adm-btn-ghost adm-btn-sm" id="addCatBtn">
                    <i class="fa-solid fa-plus"></i> Kategori Ekle
                </button>
                <button type="submit" class="adm-btn adm-btn-primary">
                    <i class="fa-solid fa-check"></i> Kaydet
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ══ TAB: AI & API ══ -->
<div class="settings-panel" id="tab-ai" style="display:none;">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">

        <div class="adm-card">
            <div class="adm-card-header">
                <div class="adm-card-title"><i class="fa-solid fa-robot"></i> Google Gemini API</div>
            </div>
            <form method="POST" action="api/settings_handler.php">
                <input type="hidden" name="_section" value="ai_key">
                <div class="adm-form-group">
                    <label class="adm-form-label">Gemini API Anahtarı</label>
                    <div style="position:relative;">
                        <input type="password" class="adm-input" name="gemini_key" id="geminiKeyInput"
                            value="<?php echo htmlspecialchars($geminiKey); ?>" placeholder="AIzaSy...">
                        <button type="button" id="toggleGeminiKey"
                            style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-muted);cursor:pointer;font-size:0.9rem;">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                    <div style="font-size:0.75rem;color:var(--text-muted);margin-top:6px;">
                        <a href="https://aistudio.google.com/app/apikey" target="_blank" style="color:var(--cyan);">
                            <i class="fa-solid fa-arrow-up-right-from-square"></i> Google AI Studio'dan alın
                        </a>
                    </div>
                </div>
                <div class="adm-form-group" style="padding:12px;background:rgba(57,255,20,0.05);border:1px solid rgba(57,255,20,0.15);border-radius:var(--radius-sm);">
                    <div style="font-size:0.75rem;color:var(--text-muted);margin-bottom:4px;">Mevcut Durum</div>
                    <?php if($geminiKey): ?>
                    <span class="adm-badge green"><i class="fa-solid fa-circle-check"></i> API Anahtarı Tanımlı</span>
                    <?php else: ?>
                    <span class="adm-badge red"><i class="fa-solid fa-circle-xmark"></i> API Anahtarı Eksik</span>
                    <?php endif; ?>
                </div>
                <button type="submit" class="adm-btn adm-btn-primary" style="width:100%;">
                    <i class="fa-solid fa-check"></i> Kaydet
                </button>
            </form>
        </div>

        <div class="adm-card">
            <div class="adm-card-header">
                <div class="adm-card-title"><i class="fa-solid fa-key"></i> Diğer API Ayarları</div>
            </div>
            <div style="display:flex;flex-direction:column;gap:10px;">
                <?php
                $configPath = __DIR__ . '/../config.php';
                $configContent = file_exists($configPath) ? file_get_contents($configPath) : '';
                $hasTelegram = strpos($configContent, 'TELEGRAM_BOT_TOKEN') !== false;
                $hasAppSecret = strpos($configContent, 'APP_SECRET') !== false;
                $items = [
                    ['Telegram Bot Token', $hasTelegram, 'fa-telegram', 'fa-brands'],
                    ['APP_SECRET (CSRF)', $hasAppSecret, 'fa-shield', 'fa-solid'],
                    ['Google OAuth Client', strpos($configContent,'GOOGLE_CLIENT_ID')!==false, 'fa-google','fa-brands'],
                    ['Facebook OAuth', strpos($configContent,'FACEBOOK_APP_ID')!==false, 'fa-facebook','fa-brands'],
                ];
                foreach($items as [$l,$ok,$ic,$pfx]): ?>
                <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 14px;background:var(--bg-card);border-radius:var(--radius-sm);border:1px solid var(--border);">
                    <div style="display:flex;align-items:center;gap:8px;font-size:0.83rem;">
                        <i class="<?php echo $pfx; ?> <?php echo $ic; ?>" style="color:var(--<?php echo $ok?'green':'red'; ?>);width:16px;"></i>
                        <?php echo $l; ?>
                    </div>
                    <span class="adm-badge <?php echo $ok?'green':'red'; ?>"><?php echo $ok?'Tanımlı':'Eksik'; ?></span>
                </div>
                <?php endforeach; ?>
                <div style="padding:10px 14px;background:var(--cyan-dim);border-radius:var(--radius-sm);border:1px solid var(--border-accent);font-size:0.78rem;color:var(--cyan);">
                    <i class="fa-solid fa-circle-info"></i> API anahtarlarını düzenlemek için <code>config.php</code> dosyasını düzenleyin.
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ══ TAB: FEATURE FLAGS ══ -->
<div class="settings-panel" id="tab-flags" style="display:none;">
    <div class="adm-card">
        <div class="adm-card-header">
            <div class="adm-card-title"><i class="fa-solid fa-flag"></i> Özellik Bayrakları</div>
            <div class="adm-card-subtitle">Platfom özelliklerini hızlıca etkinleştirip devre dışı bırakın</div>
        </div>
        <div id="flagsContainer" style="display:flex;flex-direction:column;gap:6px;">
            <?php foreach($featureFlags as $flag): ?>
            <?php
            $flagIcons = [
                'challenges_enabled'    => 'fa-trophy',
                'discounts_enabled'     => 'fa-ticket',
                'subscriptions_enabled' => 'fa-rotate',
                'ai_blog_enabled'       => 'fa-robot',
                'notifications_enabled' => 'fa-bell',
                'guest_access_enabled'  => 'fa-user-secret',
                'registration_open'     => 'fa-user-plus',
                'maintenance_mode'      => 'fa-triangle-exclamation',
                'xp_system_enabled'     => 'fa-star',
                'leaderboard_enabled'   => 'fa-ranking-star',
            ];
            $icon = $flagIcons[$flag['flag_name']] ?? 'fa-flag';
            $isDanger = in_array($flag['flag_name'],['maintenance_mode']);
            ?>
            <div style="display:flex;align-items:center;gap:14px;padding:14px 16px;background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-md);transition:var(--transition);" class="flag-row" data-flag="<?php echo $flag['flag_name']; ?>">
                <div style="width:38px;height:38px;border-radius:10px;background:rgba(255,255,255,0.04);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fa-solid <?php echo $icon; ?>" style="color:var(--<?php echo $isDanger?'red':'cyan'; ?>);"></i>
                </div>
                <div style="flex:1;">
                    <div style="font-weight:600;font-size:0.88rem;<?php echo $isDanger?'color:var(--red);':''; ?>">
                        <?php
                        $flagLabels = [
                            'challenges_enabled'    => 'Challenge Sistemi',
                            'discounts_enabled'     => 'Öğrenci İndirimleri',
                            'subscriptions_enabled' => 'Abonelik Takibi',
                            'ai_blog_enabled'       => 'AI Blog Studio',
                            'notifications_enabled' => 'Bildirimler',
                            'guest_access_enabled'  => 'Misafir Erişimi (Demo Login)',
                            'registration_open'     => 'Üye Kaydı',
                            'maintenance_mode'      => '⚠️ Bakım Modu',
                            'xp_system_enabled'     => 'XP & Seviye Sistemi',
                            'leaderboard_enabled'   => 'Liderlik Tablosu',
                        ];
                        echo $flagLabels[$flag['flag_name']] ?? $flag['flag_name'];
                        ?>
                    </div>
                    <div style="font-size:0.75rem;color:var(--text-muted);"><?php echo htmlspecialchars($flag['description']??''); ?></div>
                </div>
                <div style="display:flex;align-items:center;gap:10px;">
                    <span class="flag-status adm-badge <?php echo $flag['is_enabled']?'green':'red'; ?>">
                        <?php echo $flag['is_enabled']?'Aktif':'Kapalı'; ?>
                    </span>
                    <label class="adm-toggle">
                        <input type="checkbox" class="flag-toggle" <?php echo $flag['is_enabled']?'checked':''; ?>
                            data-flag="<?php echo $flag['flag_name']; ?>">
                        <span class="adm-toggle-slider"></span>
                    </label>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div style="margin-top:14px;padding:12px 14px;background:rgba(255,145,0,0.06);border:1px solid rgba(255,145,0,0.2);border-radius:var(--radius-sm);font-size:0.8rem;color:var(--orange);">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <strong>Bakım Modu</strong> etkinleştirilirse bu admin paneli dışındaki tüm sayfalar erişilemez hale gelir.
        </div>
    </div>
</div>

<!-- ══ TAB: SECURITY ══ -->
<div class="settings-panel" id="tab-security" style="display:none;">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">

        <!-- Change Password -->
        <div class="adm-card">
            <div class="adm-card-header">
                <div class="adm-card-title"><i class="fa-solid fa-lock"></i> Admin Şifresi Değiştir</div>
            </div>
            <?php if($pwMsg): ?>
            <div style="padding:12px 16px;background:<?php echo $pwError?'rgba(255,59,48,0.1)':'rgba(57,255,20,0.08)'; ?>;border:1px solid <?php echo $pwError?'rgba(255,59,48,0.3)':'rgba(57,255,20,0.3)'; ?>;border-radius:var(--radius-sm);margin-bottom:16px;font-size:0.85rem;">
                <?php echo $pwMsg; ?>
            </div>
            <?php endif; ?>
            <form method="POST">
                <input type="hidden" name="_section" value="password">
                <div class="adm-form-group">
                    <label class="adm-form-label">Mevcut Şifre</label>
                    <input type="password" class="adm-input" name="current_password" autocomplete="current-password">
                </div>
                <div class="adm-form-group">
                    <label class="adm-form-label">Yeni Şifre (min. 6 karakter)</label>
                    <input type="password" class="adm-input" name="new_password" autocomplete="new-password">
                </div>
                <div class="adm-form-group">
                    <label class="adm-form-label">Yeni Şifre (Tekrar)</label>
                    <input type="password" class="adm-input" name="new_password2" autocomplete="new-password">
                </div>
                <button type="submit" class="adm-btn adm-btn-primary" style="width:100%;">
                    <i class="fa-solid fa-lock"></i> Şifreyi Değiştir
                </button>
            </form>
        </div>

        <!-- Security Info -->
        <div class="adm-card">
            <div class="adm-card-header">
                <div class="adm-card-title"><i class="fa-solid fa-shield-halved"></i> Güvenlik Durumu</div>
            </div>
            <div style="display:flex;flex-direction:column;gap:8px;">
                <?php
                $secChecks = [
                    ['HTTPS Bağlantı', isset($_SERVER['HTTPS']),'Aktif etkinleştirin'],
                    ['Session Koruma', ini_get('session.cookie_httponly'),'php.ini: session.cookie_httponly=1'],
                    ['PHP Hata Gösterme Kapalı', !ini_get('display_errors'),'php.ini: display_errors=Off'],
                    ['Admin Şifresi Hash\'li', defined('ADMIN_PASSWORD_HASH'),'config.php doğru yapılandırılmış'],
                    ['Feature Flags Aktif', true,'Sistem hazır'],
                ];
                foreach($secChecks as [$l,$ok,$alt]):
                ?>
                <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 14px;background:var(--bg-card);border-radius:var(--radius-sm);border:1px solid var(--border);">
                    <div>
                        <div style="font-size:0.83rem;font-weight:500;"><?php echo $l; ?></div>
                        <?php if(!$ok): ?><div style="font-size:0.72rem;color:var(--orange);"><?php echo $alt; ?></div><?php endif; ?>
                    </div>
                    <span class="adm-badge <?php echo $ok?'green':'orange'; ?>">
                        <?php echo $ok?'✓':'⚠'; ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>

            <div style="margin-top:14px;padding:12px 14px;background:var(--bg-card);border-radius:var(--radius-md);border:1px solid var(--border);">
                <div style="font-size:0.72rem;text-transform:uppercase;letter-spacing:1px;color:var(--text-muted);margin-bottom:8px;">Oturum Bilgisi</div>
                <div style="font-size:0.82rem;display:flex;flex-direction:column;gap:5px;">
                    <div>IP: <span class="adm-code" style="font-size:0.78rem;"><?php echo $_SERVER['REMOTE_ADDR']??'N/A'; ?></span></div>
                    <div>UA: <span style="color:var(--text-muted);font-size:0.75rem;"><?php echo substr(htmlspecialchars($_SERVER['HTTP_USER_AGENT']??''), 0, 60).'...'; ?></span></div>
                    <div>Giriş: <span style="color:var(--cyan);"><?php echo date('d.m.Y H:i'); ?></span></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// ── Tab System ──
const tabs = document.querySelectorAll('.settings-tab');
const panels = document.querySelectorAll('.settings-panel');

function switchTab(name) {
    tabs.forEach(t => {
        const isActive = t.dataset.tab === name;
        t.style.cssText = isActive
            ? 'background:var(--cyan-dim);color:var(--cyan);border-color:rgba(0,240,255,0.3);border-radius:var(--radius-sm) var(--radius-sm) 0 0;border-bottom:0;'
            : '';
    });
    panels.forEach(p => { p.style.display = p.id === 'tab-' + name ? '' : 'none'; });
    localStorage.setItem('settingsTab', name);
}
tabs.forEach(t => t.addEventListener('click', () => switchTab(t.dataset.tab)));

// Restore last tab
const savedTab = localStorage.getItem('settingsTab') || 'general';
switchTab(savedTab);

// ── Gemini Key Toggle ──
document.getElementById('toggleGeminiKey')?.addEventListener('click', function() {
    const inp = document.getElementById('geminiKeyInput');
    const isPass = inp.type === 'password';
    inp.type = isPass ? 'text' : 'password';
    this.innerHTML = `<i class="fa-solid fa-eye${isPass?'-slash':''}"></i>`;
});

// ── Category Manager ──
document.getElementById('addCatBtn')?.addEventListener('click', () => {
    const row = document.createElement('div');
    row.className = 'adm-cat-row';
    row.style.cssText = 'display:flex;align-items:center;gap:8px;';
    row.innerHTML = `<i class="fa-solid fa-grip-lines" style="color:var(--text-muted);cursor:move;"></i>
        <input class="adm-input adm-btn-sm" name="categories[]" placeholder="Yeni kategori..." style="flex:1;">
        <button type="button" class="adm-btn adm-btn-danger adm-btn-icon adm-btn-sm remove-cat"><i class="fa-solid fa-trash"></i></button>`;
    document.getElementById('catList').appendChild(row);
    row.querySelector('input').focus();
});
document.getElementById('catList')?.addEventListener('click', e => {
    if(e.target.closest('.remove-cat')) e.target.closest('.adm-cat-row').remove();
});

// ── Feature Flag Toggles ──
document.querySelectorAll('.flag-toggle').forEach(toggle => {
    toggle.addEventListener('change', async function() {
        const flagName = this.dataset.flag;
        const enabled  = this.checked ? 1 : 0;
        const row = this.closest('.flag-row');
        const statusBadge = row.querySelector('.flag-status');

        const d = await admFetch('api/admin_analytics.php', { action: 'set_flag', flag: flagName, enabled });
        if(d.success) {
            if(statusBadge) {
                statusBadge.textContent = enabled ? 'Aktif' : 'Kapalı';
                statusBadge.className = `flag-status adm-badge ${enabled ? 'green' : 'red'}`;
            }
            admToast(enabled ? 'success' : 'warning',
                enabled ? 'Özellik Aktif' : 'Özellik Kapatıldı',
                `"${flagName}" ${enabled ? 'etkinleştirildi' : 'devre dışı bırakıldı'}.`
            );
        } else {
            this.checked = !this.checked;
            admToast('error', 'Hata', d.message);
        }
    });
});
</script>

<?php require_once 'footer.php'; ?>
