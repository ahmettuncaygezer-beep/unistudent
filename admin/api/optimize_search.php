<?php
header('Content-Type: application/json; charset=utf-8');

$title = $_POST['title'] ?? $_GET['title'] ?? '';

if (empty($title)) {
    echo json_encode(['error' => 'Title required']);
    exit;
}

function optimizeQuery($title)
{
    $title = mb_strtolower(trim($title), 'UTF-8');

    // Turkish stopwords
    $stopwords = [
        've', 'ile', 'icin', 'bir', 'bu', 'su', 'o', 'en', 'daha', 'cok',
        'mi', 'mu', 'nasil', 'nedir', 'hakkinda', 'rehberi', 'listesi',
        'tarihleri', 'tum', 'hangi', 'ne', 'kadar', 'da', 'de',
        'ki', 'ama', 'fakat', 'veya', 'ya', 'hem', 'gibi',
        'yeni', 'eski', 'tam', 'sadece', 'artik',
        '2024', '2025', '2026', '2027'
    ];

    // Also remove with Turkish chars
    $stopwordsTR = [
        've', 'ile', 'icin', 'bir', 'bu', 'su', 'en', 'mi', 'mu',
        'nasil', 'hakkinda', 'fiyatlari', 'ucretleri',
        'rehberi', 'listesi', 'tarihleri'
    ];

    // Normalize special Turkish chars for matching
    $normalized = strtr($title, [
        'ş' => 's', 'ğ' => 'g', 'ü' => 'u', 'ö' => 'o', 'ç' => 'c', 'ı' => 'i',
        'Ş' => 'S', 'Ğ' => 'G', 'Ü' => 'U', 'Ö' => 'O', 'Ç' => 'C', 'İ' => 'I'
    ]);

    // Remove numbers
    $normalized = preg_replace('/[0-9]+/', '', $normalized);

    $words = preg_split('/\s+/', $normalized);
    $originalWords = preg_split('/\s+/', preg_replace('/[0-9]+/', '', $title));

    $filtered = [];
    $filteredOriginal = [];
    for ($i = 0; $i < count($words); $i++) {
        $w = trim($words[$i]);
        if (!in_array($w, $stopwords) && !in_array($w, $stopwordsTR) && mb_strlen($w) > 2) {
            $filtered[] = $w;
            $filteredOriginal[] = isset($originalWords[$i]) ? trim($originalWords[$i]) : $w;
        }
    }

    // Turkish-English dictionary for translation
    $dict = [
        'ogrenci' => 'student', 'universite' => 'university', 'kampus' => 'campus',
        'yurt' => 'dormitory', 'kyk' => 'dormitory', 'burs' => 'scholarship',
        'kredi' => 'student loan', 'butce' => 'budget planning',
        'para' => 'money savings', 'tasarruf' => 'saving money',
        'ekonomi' => 'economy', 'gider' => 'expenses cost',
        'gezi' => 'travel landscape', 'seyahat' => 'travel',
        'tatil' => 'vacation beach', 'yemek' => 'food cuisine',
        'tarif' => 'recipe cooking', 'mutfak' => 'kitchen cooking',
        'ev' => 'apartment home', 'kira' => 'rent apartment',
        'arkadas' => 'friends group', 'ders' => 'classroom study',
        'sinav' => 'exam study', 'vize' => 'midterm exam',
        'istanbul' => 'istanbul aerial cityscape', 'ankara' => 'ankara city',
        'izmir' => 'izmir seaside', 'eskisehir' => 'eskisehir city',
        'antalya' => 'antalya beach', 'bursa' => 'bursa green city',
        'trabzon' => 'trabzon landscape', 'konya' => 'konya city',
        'teknoloji' => 'technology modern', 'bilgisayar' => 'laptop computer',
        'yazilim' => 'coding software', 'telefon' => 'smartphone',
        'konser' => 'concert crowd', 'festival' => 'festival event',
        'ulasim' => 'public transport', 'metro' => 'metro subway',
        'otobus' => 'bus transportation', 'kart' => 'transport card',
        'saglik' => 'health wellness', 'spor' => 'gym workout fitness',
        'kosu' => 'running jogging', 'beslenme' => 'nutrition healthy food',
        'kitap' => 'books reading', 'kutuphane' => 'library studying',
        'is' => 'work office', 'staj' => 'internship office',
        'mezuniyet' => 'graduation ceremony', 'diploma' => 'diploma graduation',
        'banka' => 'banking finance', 'hesap' => 'account finance',
        'market' => 'grocery shopping', 'alisveris' => 'shopping',
        'cafe' => 'coffee shop cafe', 'kahve' => 'coffee aesthetic',
        'yemekhane' => 'cafeteria dining hall',
        'aylik' => 'monthly', 'haftalik' => 'weekly'
    ];

    // Topic detection for context enrichment
    $topics = [
        'education' => ['ogrenci', 'universite', 'kampus', 'yurt', 'kyk', 'burs', 'ders', 'sinav', 'kutuphane', 'mezuniyet', 'staj'],
        'finance' => ['butce', 'para', 'tasarruf', 'kredi', 'gider', 'kira', 'banka', 'hesap', 'ekonomi'],
        'food' => ['yemek', 'tarif', 'mutfak', 'beslenme', 'yemekhane', 'market'],
        'travel' => ['gezi', 'seyahat', 'tatil', 'istanbul', 'ankara', 'izmir', 'antalya', 'eskisehir', 'bursa', 'trabzon', 'konya'],
        'tech' => ['teknoloji', 'bilgisayar', 'yazilim', 'telefon'],
        'fitness' => ['spor', 'kosu', 'saglik']
    ];

    $detectedTopic = 'general';
    foreach ($topics as $topic => $keywords) {
        foreach ($filtered as $w) {
            if (in_array($w, $keywords)) {
                $detectedTopic = $topic;
                break 2;
            }
        }
    }

    // Build Turkish query (keep original chars)
    $turkishCore = array_slice($filteredOriginal, 0, 4);
    $turkishSuffix = '';
    switch ($detectedTopic) {
        case 'education':
            $turkishSuffix = ' universite kampus';
            break;
        case 'finance':
            $turkishSuffix = ' para finans';
            break;
        case 'food':
            $turkishSuffix = ' yemek sunumu';
            break;
        case 'travel':
            $turkishSuffix = ' manzara sehir';
            break;
        case 'tech':
            $turkishSuffix = ' modern teknoloji';
            break;
        case 'fitness':
            $turkishSuffix = ' spor salonu';
            break;
    }
    $turkishQuery = implode(' ', $turkishCore) . $turkishSuffix;

    // Build English query
    $englishParts = [];
    foreach ($filtered as $w) {
        if (isset($dict[$w])) {
            $englishParts[] = $dict[$w];
        }
    }

    if (empty($englishParts)) {
        // Generic fallback based on topic
        switch ($detectedTopic) {
            case 'education':
                $englishParts[] = 'university students campus';
                break;
            case 'finance':
                $englishParts[] = 'budget planning money';
                break;
            case 'food':
                $englishParts[] = 'food photography cuisine';
                break;
            case 'travel':
                $englishParts[] = 'city landscape aerial view';
                break;
            case 'tech':
                $englishParts[] = 'modern technology laptop';
                break;
            case 'fitness':
                $englishParts[] = 'gym workout fitness';
                break;
            default:
                $englishParts[] = 'university student lifestyle';
                break;
        }
    }

    $englishSuffix = '';
    switch ($detectedTopic) {
        case 'education':
            $englishSuffix = ' students university aesthetic';
            break;
        case 'finance':
            $englishSuffix = ' office charts professional';
            break;
        case 'food':
            $englishSuffix = ' food photography professional';
            break;
        case 'travel':
            $englishSuffix = ' landscape aerial high quality';
            break;
        case 'tech':
            $englishSuffix = ' modern digital professional';
            break;
        case 'fitness':
            $englishSuffix = ' gym workout professional';
            break;
        default:
            $englishSuffix = ' aesthetic high quality photo';
            break;
    }
    $englishQuery = implode(' ', array_slice($englishParts, 0, 3)) . $englishSuffix;

    return [
        'turkish_query' => trim($turkishQuery),
        'english_query' => trim($englishQuery),
        'topic' => $detectedTopic,
        'priority' => 'turkish-first'
    ];
}

echo json_encode(optimizeQuery($title), JSON_UNESCAPED_UNICODE);
