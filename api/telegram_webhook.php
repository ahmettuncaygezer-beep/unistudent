<?php
declare(strict_types=1);
/**
 * Telegram Bot Webhook
 * Kurulum:
 *   1) BotFather'dan bot oluştur, TOKEN al.
 *   2) .env'e TELEGRAM_BOT_TOKEN=... ekle.
 *   3) setWebhook: https://api.telegram.org/bot<TOKEN>/setWebhook?url=https://sen.com/api/telegram_webhook.php
 *
 * Komutlar:
 *   /start TOKEN           - Hesabı bağla (TOKEN UniBütçe'den alınır)
 *   /gider 50 kahve        - Hızlı gider ekle
 *   /gelir 5000 maaş       - Gelir ekle
 *   /bakiye                - Bu ay özet
 *   /yardim                - Komut listesi
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json; charset=utf-8');

$TOKEN = env('TELEGRAM_BOT_TOKEN', '');
if (!$TOKEN) { http_response_code(500); echo json_encode(['error'=>'Bot token yapılandırılmamış']); exit; }

$raw = file_get_contents('php://input');
$update = json_decode($raw, true);
if (!$update || !isset($update['message'])) { http_response_code(200); echo '{}'; exit; }

$msg = $update['message'];
$chatId = (int)($msg['chat']['id'] ?? 0);
$text = trim($msg['text'] ?? '');
if (!$chatId || $text === '') { echo '{}'; exit; }

function tg_send(string $token, int $chatId, string $text): void {
    $ch = curl_init("https://api.telegram.org/bot{$token}/sendMessage");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 8,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'chat_id' => $chatId, 'text' => $text, 'parse_mode' => 'Markdown'
        ]),
    ]);
    curl_exec($ch); curl_close($ch);
}

function lookup_user_by_chat(PDO $pdo, int $chatId): ?int {
    $s = $pdo->prepare("SELECT user_id FROM telegram_links WHERE chat_id=? AND linked_at IS NOT NULL");
    $s->execute([$chatId]);
    $r = $s->fetchColumn();
    return $r ? (int)$r : null;
}

// /start TOKEN
if (preg_match('/^\/start\s+([a-f0-9]{16,32})/i', $text, $m)) {
    $linkToken = $m[1];
    $s = $pdo->prepare("SELECT user_id FROM telegram_links WHERE link_token=?");
    $s->execute([$linkToken]);
    $uid = $s->fetchColumn();
    if (!$uid) { tg_send($TOKEN, $chatId, "❌ Geçersiz token. ÜniBütçe'den yeni bir link oluştur."); echo '{}'; exit; }
    $u = $pdo->prepare("UPDATE telegram_links SET chat_id=?, linked_at=NOW() WHERE link_token=?");
    $u->execute([$chatId, $linkToken]);
    tg_send($TOKEN, $chatId, "✅ Hesap bağlandı!\n\nKullanım:\n/gider 50 kahve\n/gelir 5000 maaş\n/bakiye\n/yardim");
    echo '{}'; exit;
}

$uid = lookup_user_by_chat($pdo, $chatId);
if (!$uid) {
    tg_send($TOKEN, $chatId, "👋 Merhaba!\n\nBu botu kullanmak için ÜniBütçe hesabınla eşleştir.\n\n1. unibutce.com → Profil → Telegram Bağla\n2. Aldığın linki burada aç\n\n(ya da /start <token> yaz)");
    echo '{}'; exit;
}

if (preg_match('/^\/gider\s+([\d.,]+)\s*(.*)/ui', $text, $m)) {
    $amount = (float)str_replace(',', '.', $m[1]);
    $desc = trim($m[2]);
    if ($amount <= 0) { tg_send($TOKEN, $chatId, "❌ Tutar geçersiz."); echo '{}'; exit; }
    // kategori tahmini
    $cat = 'Diğer';
    $keywords = [
        'Yemek' => ['yemek','food','lahmacun','pizza','burger','kebap','dönek','döner','wolt','yemeksepeti','getir','trendyol'],
        'Kahve' => ['kahve','coffee','starbucks','kahve dünyası','espressolab','latte','cappuccino'],
        'Market'=> ['market','bim','a101','şok','migros','carrefour'],
        'Ulaşım'=> ['taksi','taxi','uber','bitaksi','dolmuş','otobüs','metro','iett'],
        'Eğlence'=> ['sinema','konser','netflix','spotify','bar','maç'],
        'Kitap' => ['kitap','book','d&r'],
    ];
    $low = mb_strtolower($desc, 'UTF-8');
    foreach ($keywords as $c => $ws) foreach ($ws as $w) if (strpos($low, $w) !== false) { $cat = $c; break 2; }
    $s = $pdo->prepare("INSERT INTO transactions (user_id, type, amount, category, description, transaction_date) VALUES (?, 'expense', ?, ?, ?, NOW())");
    $s->execute([$uid, $amount, $cat, $desc ?: $cat]);
    tg_send($TOKEN, $chatId, "✅ *{$amount}₺* gider eklendi\n📂 Kategori: {$cat}\n📝 " . ($desc ?: '-'));
    echo '{}'; exit;
}

if (preg_match('/^\/gelir\s+([\d.,]+)\s*(.*)/ui', $text, $m)) {
    $amount = (float)str_replace(',', '.', $m[1]);
    $desc = trim($m[2]);
    if ($amount <= 0) { tg_send($TOKEN, $chatId, "❌ Tutar geçersiz."); echo '{}'; exit; }
    $s = $pdo->prepare("INSERT INTO transactions (user_id, type, amount, category, description, transaction_date) VALUES (?, 'income', ?, 'Gelir', ?, NOW())");
    $s->execute([$uid, $amount, $desc ?: 'Gelir']);
    tg_send($TOKEN, $chatId, "✅ *{$amount}₺* gelir eklendi\n📝 " . ($desc ?: 'Gelir'));
    echo '{}'; exit;
}

if (preg_match('/^\/bakiye/i', $text)) {
    $s = $pdo->prepare("SELECT type, SUM(amount) total FROM transactions WHERE user_id=? AND MONTH(transaction_date)=MONTH(NOW()) AND YEAR(transaction_date)=YEAR(NOW()) GROUP BY type");
    $s->execute([$uid]);
    $inc = 0; $exp = 0;
    foreach ($s as $r) { if ($r['type']==='income') $inc = (float)$r['total']; else $exp = (float)$r['total']; }
    $net = $inc - $exp;
    tg_send($TOKEN, $chatId, "📊 *Bu Ay*\n\n💰 Gelir: " . number_format($inc, 0, ',', '.') . "₺\n💸 Gider: " . number_format($exp, 0, ',', '.') . "₺\n🎯 Net: " . ($net>=0?'+':'') . number_format($net, 0, ',', '.') . "₺");
    echo '{}'; exit;
}

if (preg_match('/^\/(yardim|help|start)/i', $text)) {
    tg_send($TOKEN, $chatId, "📖 *ÜniBütçe Bot Komutları*\n\n`/gider 50 kahve` — hızlı gider\n`/gelir 5000 maaş` — gelir ekle\n`/bakiye` — bu ay özeti\n`/yardim` — bu mesaj");
    echo '{}'; exit;
}

tg_send($TOKEN, $chatId, "🤔 Anlamadım. /yardim yaz.");
echo '{}';
