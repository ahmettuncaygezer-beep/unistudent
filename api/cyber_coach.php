<?php
require_once __DIR__ . '/../config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

// ══ RATE LIMITER: 30 mesaj / 10 dakika (kullanıcı + IP) ══
$ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$ip = preg_replace('/[^a-zA-Z0-9._:-]/', '', $ip);
$uid = $_SESSION['user_id'] ?? 'guest';

$rateDir  = sys_get_temp_dir() . '/unibutce_rl';
if (!is_dir($rateDir)) mkdir($rateDir, 0700, true);

$rateFile  = $rateDir . '/rl_' . md5($uid . '|' . $ip) . '.json';
$limit     = 30;
$windowSec = 600; // 10 dakika
$now       = time();

// Atomic read-modify-write with exclusive lock (TOCTOU kapalı)
$fp = fopen($rateFile, 'c+');
if (!$fp) {
    http_response_code(500);
    echo json_encode(['error' => 'Rate limiter başlatılamadı.']);
    exit;
}
flock($fp, LOCK_EX);
$raw   = stream_get_contents($fp);
$saved = $raw ? json_decode($raw, true) : null;
$state = ['count' => 0, 'window_start' => $now];
if (is_array($saved) && isset($saved['window_start']) && ($now - $saved['window_start']) < $windowSec) {
    $state = $saved;
}

if ($state['count'] >= $limit) {
    flock($fp, LOCK_UN);
    fclose($fp);
    $remaining = $windowSec - ($now - $state['window_start']);
    $mins = ceil($remaining / 60);
    echo json_encode([
        'reply'      => "⏳ 10 dakikada $limit mesaj limitine ulaştın. Yaklaşık $mins dakika sonra tekrar sorabilirsin.",
        'rate_limit' => true,
    ]);
    exit;
}

$state['count']++;
ftruncate($fp, 0);
rewind($fp);
fwrite($fp, json_encode($state));
fflush($fp);
flock($fp, LOCK_UN);
fclose($fp);

// ══ MESAJ AL ══
$input       = json_decode(file_get_contents('php://input'), true);
$userMessage = trim($input['message'] ?? '');

if (empty($userMessage)) {
    echo json_encode(['error' => 'Mesaj boş.']);
    exit;
}

// ══ API ANAHTARI ══
$apiKey = getGeminiApiKey();
if (empty($apiKey)) {
    echo json_encode(['reply' => '⚠️ Yapay zeka bağlantısı kurulamıyor. Admin panelinden API anahtarını ekleyin.']);
    exit;
}

$systemPrompt = "Sen 'Cyber Coach' adlı yapay zeka finansal asistansın ve ÜniBütçe platformunda çalışıyorsun. "
    . "Türkiye'deki üniversite öğrencilerine YALNIZCA Türkçe yanıt ver. "
    . "Uzmanlık alanların: kişisel bütçe yönetimi, KYK kredi ve burs miktarları, Türk şehir yaşam maliyetleri, "
    . "öğrenci tasarruf stratejileri, part-time iş, burs başvuruları ve öğrenci yaşamı. "
    . "Yanıt tarzın: Kısa ve öz (2-4 cümle), samimi, pratik ve motive edici. "
    . "Para birimi olarak Türk Lirası (₺) kullan. Düz metin yaz, markdown işaret kullanma.";

// ══ MODEL FALLBACK ZİNCİRİ ══
$models = [
    'openai/gpt-4o-mini',
    'anthropic/claude-3-haiku',
    'google/gemma-4-31b-it:free',
];

$reply = null;
foreach ($models as $model) {
    $payload = json_encode([
        'model'       => $model,
        'messages'    => [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user',   'content' => $userMessage],
        ],
        'max_tokens'  => 350,
        'temperature' => 0.7,
    ]);

    $ch = curl_init('https://openrouter.ai/api/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
            'HTTP-Referer: http://localhost/unistudent',
            'X-Title: UniBütçe Cyber Coach',
        ],
        CURLOPT_TIMEOUT        => 25,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);

    $response  = curl_exec($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        error_log("Cyber Coach cURL ($model): $curlError");
        continue;
    }

    if ($httpCode === 200) {
        $data  = json_decode($response, true);
        $reply = $data['choices'][0]['message']['content'] ?? null;
        if (!empty(trim($reply))) break;
    }

    error_log("Cyber Coach $model HTTP $httpCode: " . substr($response, 0, 200));
}

if (empty($reply)) {
    echo json_encode(['reply' => '🔌 Şu an yapay zekaya ulaşılamıyor. Lütfen birkaç dakika sonra tekrar dene.']);
    exit;
}

// Kalan mesaj hakkını da döndür (opsiyonel UI bilgisi için)
echo json_encode([
    'reply'      => trim($reply),
    'remaining'  => $limit - $state['count'],
]);
?>
