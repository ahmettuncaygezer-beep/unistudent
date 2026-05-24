<?php
require_once '../auth_check.php';
check_login();

header('Content-Type: application/json');

$query = $_GET['q'] ?? '';
$queryEN = $_GET['q_en'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1)
    $page = 1;

if (empty($query) && empty($queryEN)) {
    echo json_encode(['error' => 'Query is required', 'results' => []]);
    exit;
}

/**
 * Fetch URL content using cURL (more reliable than file_get_contents on XAMPP)
 */
function curlFetch($url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, (getenv('APP_ENV') !== 'local'));
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, (getenv('APP_ENV') !== 'local') ? 2 : 0);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8',
        'Accept-Language: tr-TR,tr;q=0.9,en-US;q=0.8,en;q=0.7',
        'Referer: https://www.bing.com/',
        'DNT: 1'
    ]);
    $html = curl_exec($ch);
    curl_close($ch);
    return $html;
}

/**
 * Scrape Bing Images
 */
function searchBing($query, $page = 1)
{
    $results = [];
    // Bing pagination: first=1, first=35, first=69...
    $bingOffset = 1 + (($page - 1) * 35);
    $bingUrl = "https://www.bing.com/images/search?q=" . urlencode($query) . "&first=" . $bingOffset . "&count=35&qft=+filterui:photo-photo&form=IRFLTR";

    $html = curlFetch($bingUrl);
    if (!$html)
        return $results;

    // Pattern 1: murl&quot;:&quot;URL&quot; (HTML-encoded JSON in data attributes)
    preg_match_all('/murl&quot;:&quot;(.*?)&quot;/', $html, $m1);
    // Pattern 2: "murl":"URL" (standard JSON)
    preg_match_all('/"murl":"(.*?)"/', $html, $m2);
    // Pattern 3: murl\\u0022:\\u0022URL\\u0022
    preg_match_all('/murl\\\\u0022:\\\\u0022(.*?)\\\\u0022/', $html, $m3);
    // Pattern 4: data-src with bing thumbnail
    preg_match_all('/src="(https:\/\/tse\d+\.mm\.bing\.net\/th[^"]+)"/', $html, $m4);

    $allUrls = array_merge($m1[1] ?? [], $m2[1] ?? [], $m3[1] ?? []);
    $thumbUrls = $m4[1] ?? [];

    // Process direct image URLs (murl)
    $seen = [];
    foreach ($allUrls as $imgUrl) {
        $url = html_entity_decode(urldecode(stripslashes($imgUrl)));
        if (isset($seen[$url]))
            continue;
        $seen[$url] = true;

        if (filter_var($url, FILTER_VALIDATE_URL)) {
            $results[] = [
                'url' => $url,
                'thumb' => $url
            ];
        }
        if (count($results) >= 16)
            break;
    }

    // If murl parsing failed, use Bing thumbnails (tse*.mm.bing.net)
    if (count($results) < 4 && !empty($thumbUrls)) {
        foreach ($thumbUrls as $thumbUrl) {
            $url = html_entity_decode($thumbUrl);
            if (isset($seen[$url]))
                continue;
            $seen[$url] = true;

            $results[] = [
                'url' => $url,
                'thumb' => $url
            ];
            if (count($results) >= 16)
                break;
        }
    }

    return $results;
}

/**
 * Scrape Pexels
 */
function searchPexels($query, $page = 1)
{
    $results = [];
    $url = "https://www.pexels.com/search/" . urlencode($query) . "/?page=" . $page;
    $html = curlFetch($url);
    if (!$html)
        return $results;

    // Match Pexels photo URLs
    preg_match_all('/src="(https:\/\/images\.pexels\.com\/photos\/[^"]+)"/', $html, $matches);
    if (!empty($matches[1])) {
        $unique = array_unique($matches[1]);
        foreach ($unique as $imgUrl) {
            $base = explode('?', $imgUrl)[0];
            $results[] = [
                'url' => $base . '?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1',
                'thumb' => $base . '?auto=compress&cs=tinysrgb&w=400'
            ];
            if (count($results) >= 16)
                break;
        }
    }

    return $results;
}

/**
 * Scrape Pixabay
 */
function searchPixabay($query, $page = 1)
{
    $results = [];
    $url = "https://pixabay.com/images/search/" . urlencode($query) . "/?pagi=" . $page;
    $html = curlFetch($url);
    if (!$html)
        return $results;

    // Pixabay stores images in srcset or src with cdn.pixabay.com
    preg_match_all('/src="(https:\/\/cdn\.pixabay\.com\/photo\/[^"]+)"/', $html, $matches);
    if (!empty($matches[1])) {
        $unique = array_unique($matches[1]);
        foreach ($unique as $imgUrl) {
            $results[] = [
                'url' => $imgUrl,
                'thumb' => $imgUrl
            ];
            if (count($results) >= 12)
                break;
        }
    }

    return $results;
}

// ====== MAIN SEARCH STRATEGY ======
$results = [];

// Step 1: Bing with Turkish query
if (!empty($query)) {
    $results = searchBing($query, $page);
}

// Step 2: Bing with English query (if Turkish gave few)
if (count($results) < 8 && !empty($queryEN)) {
    $enResults = searchBing($queryEN, $page);
    // Merge without duplicates
    $existingUrls = array_column($results, 'url');
    foreach ($enResults as $r) {
        if (!in_array($r['url'], $existingUrls)) {
            $results[] = $r;
        }
        if (count($results) >= 16)
            break;
    }
}

// Step 3: Pexels fallback (English query preferred)
if (count($results) < 8) {
    $pexelsQuery = !empty($queryEN) ? $queryEN : $query;
    $pexelsResults = searchPexels($pexelsQuery, $page);
    $existingUrls = array_column($results, 'url');
    foreach ($pexelsResults as $r) {
        if (!in_array($r['url'], $existingUrls)) {
            $results[] = $r;
        }
        if (count($results) >= 16)
            break;
    }
}

// Step 4: Pixabay fallback
if (count($results) < 8) {
    $pixQuery = !empty($queryEN) ? $queryEN : $query;
    $pixResults = searchPixabay($pixQuery, $page);
    $existingUrls = array_column($results, 'url');
    foreach ($pixResults as $r) {
        if (!in_array($r['url'], $existingUrls)) {
            $results[] = $r;
        }
        if (count($results) >= 16)
            break;
    }
}

echo json_encode(['results' => $results, 'count' => count($results)]);
