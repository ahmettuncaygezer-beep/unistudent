<?php
require_once __DIR__ . '/../../includes/db.php';

// Command line or Admin-only execution block
// if (php_sapi_name() !== 'cli' && !isset($_GET['admin_key'])) { die('Access Denied'); }

$stmt = $pdo->query("SELECT id, full_name, email FROM users");
$users = $stmt->fetchAll();

echo "<!DOCTYPE html><html lang='tr'><head><meta charset='UTF-8'><title>Haftalık Mail Simülasyonu</title></head>";
echo "<body style='background:#020205; color:#fff; font-family:\"Space Grotesk\", sans-serif; padding:40px;'>";
echo "<h2>🤖 Cyber Cron: Haftalık Raporlar Üretildi</h2><hr style='border-color:#333'>";

foreach ($users as $user) {
    // Generate the Mock Email Content
    $htmlEmail = "
    <div style='max-width:600px; margin:20px auto; background:#0a0a12; border:1px solid #7000FF; border-radius:12px; overflow:hidden;'>
        <div style='background:rgba(112,0,255,0.2); padding:20px; text-align:center;'>
            <h1 style='color:#00F0FF; margin:0;'>ÜniBütçe Cyber Coach</h1>
        </div>
        <div style='padding:30px;'>
            <h3>Selam Kaptan, " . htmlspecialchars($user['full_name']) . " 👋</h3>
            <p style='color:#ccc; line-height:1.6;'>Bu hafta finansal durumunu yakından izledik. Matrix içindeki harcamaların %12 oranında düştü. Tebrikler!</p>
            
            <div style='margin-top:20px; padding:15px; border-left:4px solid #39FF14; background:rgba(57,255,20,0.1);'>
                <strong>Yapay Zeka Tavsiyesi:</strong> Eğlence kaleminden kıstığın 150 TL'yi kripto veya birikim cüzdanına aktarabilirsin. 
            </div>
            
            <a href='<?php echo rtrim(defined('SITE_URL') ? SITE_URL : 'https://unibutce.com', '/'); ?>/user_dashboard.php' style='display:inline-block; margin-top:30px; padding:12px 24px; background:#7000FF; color:#fff; text-decoration:none; border-radius:4px;'>Terminali Aç</a>
        </div>
        <div style='padding:15px; text-align:center; font-size:12px; color:#666; border-top:1px solid #222;'>
            Bu e-pota ÜniBütçe Cron System (v5.0) tarafından otomatik oluşturulmuştur.
        </div>
    </div>
    ";
    
    // Output simulation to browser
    echo "<div style='margin-bottom:40px;'>";
    echo "MOCK EMAIL TO: <span style='color:#00F0FF'>" . htmlspecialchars($user['email']) . "</span><br>";
    echo $htmlEmail;
    echo "</div>";
    
    // TODO: Gerçek mail() veya PHPMailer scripti buraya eklenebilir.
}

echo "</body></html>";
?>
