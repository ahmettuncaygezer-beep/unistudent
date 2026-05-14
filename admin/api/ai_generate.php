<?php
// ai_generate.php — V14 MASTER ORCHESTRATION ENGINE
// Phase A: Multi-Source Agentic Research
// Phase B: Gemini AI Deep Synthesis
// Phase C: Structured Text Parsing

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('max_execution_time', 300);
ini_set('memory_limit', '512M');
ob_start();

function sendJSON($data)
{
    ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR])) {
        if (ob_get_length())
            ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Kritik Hata: ' . $error['message']]);
        exit;
    }
});

// ===== CONFIG =====
if (!defined('FAL_API_KEY')) {
    require_once __DIR__ . '/../../config.php';
}
$FAL_KEY = FAL_API_KEY;

// ===== INPUT =====
$topic = trim($_GET['topic'] ?? '');
$tone = trim($_GET['tone'] ?? 'coach'); // coach | analyst | neutral
$depth = trim($_GET['depth'] ?? 'deep'); // deep | balanced
$lang = trim($_GET['lang'] ?? 'tr'); // tr | en

if (empty($topic)) {
    sendJSON(['error' => 'Konu başlığı (topic) gereklidir.']);
}

// ===== PHASE A: AGENTIC RESEARCH ENGINE =====

class ResearchEngine
{
    private static function fetch($url, $timeout = 15)
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/122.0 Safari/537.36',
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_ENCODING => 'gzip, deflate',
        ]);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }

    private static function extractUrls($html, $limit = 8)
    {
        $urls = [];
        // DuckDuckGo result links
        if (preg_match_all('/result__a[^>]*href="([^"]+)"/i', $html, $m)) {
            foreach ($m[1] as $url) {
                $url = urldecode($url);
                if (!preg_match('/youtube|facebook|instagram|twitter|pdf|google\.com\/search/i', $url)) {
                    $urls[] = $url;
                    if (count($urls) >= $limit)
                        break;
                }
            }
        }
        return $urls;
    }

    private static function cleanHtml($html)
    {
        $html = preg_replace('/<(script|style|nav|footer|header|aside|form|noscript)[^>]*>.*?<\/\1>/is', '', $html);
        $html = preg_replace('/<[^>]+>/', ' ', $html);
        $text = preg_replace('/\s+/', ' ', $html);
        return trim($text);
    }

    public static function research($topic)
    {
        // Three different search angles for maximum coverage
        $queries = [
            urlencode("$topic 2026 fiyatlar maliyet rehberi öğrenci"),
            urlencode("$topic yaşam giderleri kira ulaşım detaylı"),
            urlencode("$topic student life cost budget guide")
        ];

        $allUrls = [];
        foreach ($queries as $q) {
            $searchUrl = "https://html.duckduckgo.com/html/?q={$q}";
            $html = self::fetch($searchUrl, 20);
            if ($html) {
                $urls = self::extractUrls($html, 5);
                $allUrls = array_merge($allUrls, $urls);
            }
        }

        // Deduplicate and limit
        $allUrls = array_unique($allUrls);
        $allUrls = array_slice($allUrls, 0, 15);

        if (empty($allUrls)) {
            return ["Konu hakkında genel bilgi: $topic. Türkiye'deki öğrenci yaşam maliyetleri 2026 yılında değerlendirilmektedir."];
        }

        // Parallel scraping
        $mh = curl_multi_init();
        $handles = [];
        foreach ($allUrls as $i => $url) {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 12,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_USERAGENT => 'Mozilla/5.0 AppleWebKit/537.36 Chrome/122.0 Safari/537.36',
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_ENCODING => 'gzip, deflate',
            ]);
            curl_multi_add_handle($mh, $ch);
            $handles[$i] = $ch;
        }

        $running = null;
        do {
            curl_multi_exec($mh, $running);
        } while ($running);

        $sources = [];
        foreach ($handles as $ch) {
            $html = curl_multi_getcontent($ch);
            if ($html && strlen($html) > 800) {
                $text = self::cleanHtml($html);
                if (strlen($text) > 300) {
                    $sources[] = mb_substr($text, 0, 3500); // Summarized per source
                }
            }
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }
        curl_multi_close($mh);

        return $sources ?: ["Konu hakkında genel bilgi: $topic"];
    }
}

// ===== PHASE B: AI SYNTHESIS =====

$sources = ResearchEngine::research($topic);
$sourceCount = count($sources);
$sourceContext = implode("\n\n---KAYNAK SONU---\n\n", $sources);

$toneMap = [
    'coach' => 'Samimi, motivasyonel ve öğrenci dostu bir "Koç"',
    'analyst' => 'Soğukkanlı, veri odaklı ve akademik bir "Analist"',
    'neutral' => 'Tarafsız ve bilgilendirici bir "Editör"'
];
$toneDesc = $toneMap[$tone] ?? $toneMap['coach'];

$depthInstr = $depth === 'deep'
    ? 'ULTRA DERİN: Hedef 4000-5000 kelime. H2, H3, H4 hiyerarşisi zorunlu. Her H2 altında en az 6-8 paragraf.'
    : 'DENGELI: Hedef 2000-3000 kelime. H2 ve H3 yeterli. Her H2 altında 3-4 paragraf.';

$langInstr = $lang === 'en' ? 'Write in English.' : 'Türkçe yaz.';

$prompt = <<<PROMPT
# ROL:
Sen "{$toneDesc}" olarak "{$topic}" konusunda uzman bir içerik üreticisisin.
$langInstr

# ARAŞTIRMA VERİSİ ($sourceCount kaynak):
{$sourceContext}

# KESİN KURALLAR (V14 — MASTER ORCHESTRATION):

## ÇIKTI FORMATI (BU FORMATI ASLA BOZMA):
Aşağıdaki etiketleri kullanarak cevabını ver:

[TITLE]
Başlık buraya

[DESCRIPTION]
150 kelimelik SEO açıklaması buraya

[KEYWORDS]
virgül, ile, ayrılmış, 10-15, anahtar, kelime

[CHART_DATA]
[{"label":"Barınma","value":8500,"color":"#6c5ce7"},{"label":"Yemek","value":4000,"color":"#00b894"},{"label":"Ulaşım","value":1500,"color":"#0984e3"},{"label":"Eğlence","value":1200,"color":"#fdcb6e"},{"label":"Diğer","value":1800,"color":"#e17055"}]

[IMAGE_PROMPTS]
Görsel 1 İngilizce açıklaması|Görsel 2 İngilizce açıklaması|Görsel 3 İngilizce açıklaması

[CONTENT]
(TAM HTML İÇERİK BURAYA — AŞAĞIDA AÇIKLANAN KURALLARA UYGUN)

## İÇERİK KURALLARI:
$depthInstr

### Yapısal Hiyerarşi:
- Tüm bölümleri `<div class="blog-card-glass">` içine al
- H2 → H3 → H4 hiyerarşisini kullan
- Önemli kavramları: `<span class="blog-concept-chip">Kavram</span>`

### Görsel Zenginlik (ZORUNLU):
- `<div class="blog-stat-grid">` → fiyat istatistikleri
- `<div class="blog-alert-gradient tip">` → tasarruf ipuçları
- `<div class="blog-alert-gradient info">` → önemli bilgiler
- `<table class="blog-table-premium">` → karşılaştırma tabloları
- `<div class="blog-comparison-box">` → artı/eksi kutuları
- `<ul class="blog-list-colorful">` → renkli listeler

### JSON-LD Schema (Yazının sonunda):
`<div class="blog-schema-hidden"><script type="application/ld+json">{ "@context": "https://schema.org", "@type": "Article", "name": "Başlık" }</script></div>`

### 3 Senaryo Tablosu (ZORUNLU):
Yazının sonunda "Ekonomik", "Standart" ve "Premium" bütçe senaryolarını `<table class="blog-table-premium">` ile göster.

### ASLA YAPMA:
- Markdown kod bloğu (```) KULLANMA
- "Devamı gelecek" veya "..." yazma
- JSON içinde satır sonu kullanma
- Boş bölüm bırakma

PROMPT;

$payload = json_encode([
    'model' => 'google/gemini-flash-1.5',
    'prompt' => $prompt,
    'max_tokens' => 16000,
    'temperature' => 0.72
]);

$ch = curl_init('https://fal.run/fal-ai/any-llm');
curl_setopt_array($ch, [
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 180,
    CURLOPT_HTTPHEADER => [
        'Authorization: Key ' . $FAL_KEY,
        'Content-Type: application/json'
    ]
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($httpCode !== 200 || !$response) {
    sendJSON(['error' => "AI API Hatası: HTTP {$httpCode}. cURL: {$curlError}"]);
}

$json = json_decode($response, true);
if (!$json || !isset($json['output'])) {
    sendJSON(['error' => 'AI yanıtı okunamadı.', 'raw' => substr($response, 0, 500)]);
}

$aiOutput = $json['output'];

// Debug log
file_put_contents(__DIR__ . '/../../debug_ai_studio.txt', $aiOutput);

// ===== PHASE C: STRUCTURED PARSING =====
function extractTag($output, $tag)
{
    if (preg_match('/\[' . $tag . '\](.*?)(?=\[(?:TITLE|DESCRIPTION|KEYWORDS|CHART_DATA|IMAGE_PROMPTS|CONTENT)\]|$)/s', $output, $m)) {
        return trim($m[1]);
    }
    return '';
}

$parsed = [
    'title' => extractTag($aiOutput, 'TITLE'),
    'description' => extractTag($aiOutput, 'DESCRIPTION'),
    'keywords' => extractTag($aiOutput, 'KEYWORDS'),
    'chart_data' => extractTag($aiOutput, 'CHART_DATA'),
    'image_prompts' => extractTag($aiOutput, 'IMAGE_PROMPTS'),
    'content' => extractTag($aiOutput, 'CONTENT'),
    'source_count' => $sourceCount,
];

// Fallback: if no [CONTENT] tag, treat whole output as content
if (empty($parsed['content']) && strlen($aiOutput) > 200) {
    $parsed['content'] = $aiOutput;
    $parsed['title'] = $parsed['title'] ?: ($topic . ' Rehberi 2026');
}

// Validate chart_data JSON
if (!empty($parsed['chart_data'])) {
    $chartTest = json_decode($parsed['chart_data'], true);
    if (!$chartTest) {
        $parsed['chart_data'] = ''; // Invalid JSON, discard
    }
}

// Clean up stray markdown artifacts
$parsed['content'] = str_replace(['```html', '```'], '', $parsed['content']);

if (empty($parsed['content'])) {
    file_put_contents(__DIR__ . '/../../debug_failed_' . time() . '.txt', $aiOutput);
    sendJSON(['error' => 'İçerik ayrıştırılamadı.', 'debug' => substr($aiOutput, 0, 300)]);
}

sendJSON($parsed);
