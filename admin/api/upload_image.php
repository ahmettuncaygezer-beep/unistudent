<?php
require_once '../auth_check.php';
check_login();

header('Content-Type: application/json');

// Define upload directory
$uploadDir = '../../assets/images/blog/';
// Ensure directory exists
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

/**
 * Gerçek bir görsel dosyası mı? Magic byte + getimagesize doğrulaması.
 * Uzantı uyumsuzsa veya dosya görsel değilse false döner.
 */
function verifyImage(string $pathOrTmp, array $allowedMimes): array
{
    if (!is_file($pathOrTmp)) return ['ok' => false, 'ext' => null];
    $info = @getimagesize($pathOrTmp);
    if ($info === false || empty($info['mime'])) return ['ok' => false, 'ext' => null];
    $mime = $info['mime'];
    if (!isset($allowedMimes[$mime])) return ['ok' => false, 'ext' => null];
    return ['ok' => true, 'ext' => $allowedMimes[$mime]];
}

$allowedMimes = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/gif'  => 'gif',
    'image/webp' => 'webp',
];

// 1. Handle URL Download (from Search)
if (isset($_POST['url'])) {
    $imageUrl = $_POST['url'];

    // Basic validation
    if (!filter_var($imageUrl, FILTER_VALIDATE_URL)) {
        echo json_encode(['location' => '', 'error' => 'Geçersiz URL']);
        exit;
    }

    // Use cURL for better download handling
    $ch = curl_init($imageUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
    $content = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($content === false || $httpCode !== 200) {
        echo json_encode(['location' => '', 'error' => 'Görsel indirilemedi (HTTP ' . $httpCode . ').']);
        exit;
    }

    // Geçici dosyaya yazıp magic byte doğrulaması yap
    $tmp = tempnam(sys_get_temp_dir(), 'blogdl_');
    file_put_contents($tmp, $content);
    $verify = verifyImage($tmp, $allowedMimes);
    if (!$verify['ok']) {
        @unlink($tmp);
        echo json_encode(['location' => '', 'error' => 'İndirilen dosya geçerli bir görsel değil.']);
        exit;
    }

    $filename   = uniqid('blog_search_') . '.' . $verify['ext'];
    $targetPath = $uploadDir . $filename;

    if (rename($tmp, $targetPath)) {
        @chmod($targetPath, 0644);
        echo json_encode(['location' => 'assets/images/blog/' . $filename]);
    }
    else {
        @unlink($tmp);
        echo json_encode(['location' => '', 'error' => 'Dosya kaydedilemedi']);
    }
    exit;
}

// 2. Handle File Upload (from PC)
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['location' => '', 'error' => 'Dosya yüklenemedi.']);
    exit;
}

$file = $_FILES['file'];

// Magic-byte doğrulaması: uzantıya güvenme
$verify = verifyImage($file['tmp_name'], $allowedMimes);
if (!$verify['ok']) {
    echo json_encode(['location' => '', 'error' => 'Geçersiz görsel dosyası.']);
    exit;
}

$filename   = uniqid('blog_upload_') . '.' . $verify['ext'];
$targetPath = $uploadDir . $filename;

if (move_uploaded_file($file['tmp_name'], $targetPath)) {
    @chmod($targetPath, 0644);
    echo json_encode(['location' => 'assets/images/blog/' . $filename]);
}
else {
    echo json_encode(['location' => '', 'error' => 'Dosya taşınamadı.']);
}
?>
