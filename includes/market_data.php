<?php
declare(strict_types=1);

/**
 * ÜniBütçe — Canlı Piyasa Veri Modülü
 * 
 * Kaynaklar:
 *  - CoinGecko API (Kripto)   → Ücretsiz, API key gerektirmez
 *  - Yahoo Finance (BIST)     → Ücretsiz, unofficial
 *  - ExchangeRate API (Forex) → Ücretsiz
 *  - Altın: Döviz kuru × uluslararası ons fiyatı
 * 
 * Tüm veriler 5 dk cache ile saklanır (rate limit koruması).
 */

class MarketData {

    private string $cacheDir;
    private int $cacheTTL = 300; // 5 dk

    // CoinGecko ID mapping
    private array $cryptoMap = [
        'BTC'  => 'bitcoin',
        'ETH'  => 'ethereum',
        'SOL'  => 'solana',
        'AVAX' => 'avalanche-2',
        'BNB'  => 'binancecoin',
        'ADA'  => 'cardano',
        'DOT'  => 'polkadot',
        'XRP'  => 'ripple',
        'DOGE' => 'dogecoin',
        'LINK' => 'chainlink',
        'MATIC'=> 'matic-network',
        'UNI'  => 'uniswap',
        'ATOM' => 'cosmos',
        'APT'  => 'aptos',
        'ARB'  => 'arbitrum',
    ];

    // Yahoo Finance ticker mapping (BIST: .IS suffix)
    private array $bistMap = [
        'THYAO', 'SISE', 'ASELS', 'EREGL', 'TUPRS', 'BIMAS',
        'GARAN', 'AKBNK', 'YKBNK', 'SAHOL', 'KCHOL', 'TCELL',
        'FROTO', 'TOASO', 'KOZAL', 'PETKM', 'VESTL', 'TAVHL',
        'MGROS', 'ENKAI', 'SASA', 'EKGYO', 'HEKTS', 'TTKOM',
    ];

    public function __construct() {
        $this->cacheDir = __DIR__ . '/../data/cache';
        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0755, true);
        }
    }

    /**
     * Ana fonksiyon — tüm fiyatları döndürür
     * @return array ['TICKER' => ['price' => float, 'change_pct' => float, 'prev_close' => float]]
     */
    public function getAllPrices(): array {
        $prices = [];

        // 1. Crypto verileri
        $crypto = $this->getCryptoPrices();
        $prices = array_merge($prices, $crypto);

        // 2. BIST verileri
        $bist = $this->getBISTPrices();
        $prices = array_merge($prices, $bist);

        // 3. Forex verileri
        $forex = $this->getForexPrices();
        $prices = array_merge($prices, $forex);

        // 4. Altın
        $gold = $this->getGoldPrice();
        $prices = array_merge($prices, $gold);

        return $prices;
    }

    /**
     * Belirli ticker'ların fiyatlarını döndürür
     */
    public function getPrices(array $tickers): array {
        $all = $this->getAllPrices();
        $result = [];
        foreach ($tickers as $t) {
            $t = strtoupper($t);
            if (isset($all[$t])) {
                $result[$t] = $all[$t];
            }
        }
        return $result;
    }

    // ═══════════════════════════════════════════════════
    // CRYPTO — CoinGecko API
    // ═══════════════════════════════════════════════════
    private function getCryptoPrices(): array {
        $cacheKey = 'crypto_prices';
        $cached = $this->getCache($cacheKey);
        if ($cached !== null) return $cached;

        $ids = implode(',', array_values($this->cryptoMap));
        $url = "https://api.coingecko.com/api/v3/simple/price?ids={$ids}&vs_currencies=try&include_24hr_change=true";

        $data = $this->httpGet($url);
        if (!$data) return $this->getCryptoFallback();

        $json = json_decode($data, true);
        if (!$json) return $this->getCryptoFallback();

        $prices = [];
        $idToTicker = array_flip($this->cryptoMap);

        foreach ($json as $id => $info) {
            $ticker = $idToTicker[$id] ?? null;
            if (!$ticker) continue;

            $price = (float)($info['try'] ?? 0);
            $change = (float)($info['try_24h_change'] ?? 0);

            if ($price > 0) {
                $prevClose = $change != 0 ? $price / (1 + $change / 100) : $price;
                $prices[$ticker] = [
                    'price'      => round($price, 2),
                    'change_pct' => round($change, 2),
                    'prev_close' => round($prevClose, 2),
                    'source'     => 'coingecko',
                    'type'       => 'crypto',
                ];
            }
        }

        if (!empty($prices)) {
            $this->setCache($cacheKey, $prices);
        }
        return $prices;
    }

    // ═══════════════════════════════════════════════════
    // BIST — Yahoo Finance v8 (cURL, tek tek)
    // ═══════════════════════════════════════════════════
    private function getBISTPrices(): array {
        $cacheKey = 'bist_prices';
        $cached = $this->getCache($cacheKey);
        if ($cached !== null) return $cached;

        $prices = [];

        // cURL multi — paralel fetch
        if (!function_exists('curl_multi_init')) {
            // cURL yoksa fallback
            return $this->getBISTFallback();
        }

        $mh = curl_multi_init();
        $handles = [];

        foreach ($this->bistMap as $ticker) {
            $symbol = $ticker . '.IS';
            $url = "https://query1.finance.yahoo.com/v8/finance/chart/{$symbol}?interval=1d&range=2d";

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 10,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_HTTPHEADER     => [
                    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept: application/json',
                    'Accept-Language: tr-TR,tr;q=0.9',
                ],
            ]);

            curl_multi_add_handle($mh, $ch);
            $handles[$ticker] = $ch;
        }

        // Execute all
        $running = null;
        do {
            curl_multi_exec($mh, $running);
            curl_multi_select($mh, 0.5);
        } while ($running > 0);

        // Process results
        foreach ($handles as $ticker => $ch) {
            $response = curl_multi_getcontent($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);

            if ($httpCode !== 200 || !$response) continue;

            $json = json_decode($response, true);
            $result = $json['chart']['result'][0] ?? null;
            if (!$result) continue;

            $meta = $result['meta'] ?? [];
            $price = (float)($meta['regularMarketPrice'] ?? 0);
            $prevClose = (float)($meta['chartPreviousClose'] ?? $meta['previousClose'] ?? $price);

            if ($price > 0 && $prevClose > 0) {
                $changePct = round((($price - $prevClose) / $prevClose) * 100, 2);
                $prices[$ticker] = [
                    'price'      => round($price, 2),
                    'change_pct' => $changePct,
                    'prev_close' => round($prevClose, 2),
                    'source'     => 'yahoo',
                    'type'       => 'bist',
                ];
            }
        }

        curl_multi_close($mh);

        if (!empty($prices)) {
            $this->setCache($cacheKey, $prices);
        } else {
            // Fallback to previously cached data if API fails
            $old = $this->getCache($cacheKey, 3600);
            if ($old) return $old;
            return $this->getBISTFallback();
        }

        return $prices;
    }

    // ═══════════════════════════════════════════════════
    // FOREX — ExchangeRate API
    // ═══════════════════════════════════════════════════
    private function getForexPrices(): array {
        $cacheKey = 'forex_prices';
        $cached = $this->getCache($cacheKey);
        if ($cached !== null) return $cached;

        // Birden fazla kaynak deniyoruz
        $prices = $this->fetchForexFromExchangeRateAPI();

        if (!empty($prices)) {
            $this->setCache($cacheKey, $prices);
        }
        return $prices;
    }

    private function fetchForexFromExchangeRateAPI(): array {
        $url = "https://open.er-api.com/v6/latest/TRY";
        $data = $this->httpGet($url);
        if (!$data) return $this->getForexFallback();

        $json = json_decode($data, true);
        if (!$json || ($json['result'] ?? '') !== 'success') return $this->getForexFallback();

        $rates = $json['rates'] ?? [];
        $prices = [];

        // TRY bazlı kuru ters çeviriyoruz → 1 USD = ? TRY
        $currencies = ['USD', 'EUR', 'GBP', 'CHF', 'JPY'];
        foreach ($currencies as $cur) {
            if (isset($rates[$cur]) && $rates[$cur] > 0) {
                $tryRate = round(1 / $rates[$cur], 4);

                // Günlük değişimi hesapla (cache'teki önceki değerle karşılaştır)
                $prevCache = $this->getCache('forex_prev');
                $prev = $prevCache[$cur] ?? $tryRate;
                $changePct = $prev > 0 ? round((($tryRate - $prev) / $prev) * 100, 2) : 0;

                $prices[$cur] = [
                    'price'      => $tryRate,
                    'change_pct' => $changePct,
                    'prev_close' => $prev,
                    'source'     => 'exchangerate-api',
                    'type'       => 'forex',
                ];
            }
        }

        // Önceki değerleri kaydet (günlük değişim için)
        $prevData = [];
        foreach ($prices as $cur => $info) {
            $prevData[$cur] = $info['price'];
        }
        $this->setCache('forex_prev', $prevData, 86400); // 24h

        return $prices;
    }

    // ═══════════════════════════════════════════════════
    // GOLD — Döviz kuru × uluslararası ons fiyatı
    // ═══════════════════════════════════════════════════
    private function getGoldPrice(): array {
        $cacheKey = 'gold_price';
        $cached = $this->getCache($cacheKey);
        if ($cached !== null) return $cached;

        // Gold price in USD from free API
        $url = "https://api.coingecko.com/api/v3/simple/price?ids=pax-gold&vs_currencies=try&include_24hr_change=true";
        $data = $this->httpGet($url);

        $prices = [];

        if ($data) {
            $json = json_decode($data, true);
            $goldInfo = $json['pax-gold'] ?? null;
            if ($goldInfo) {
                $price = (float)($goldInfo['try'] ?? 0);
                $change = (float)($goldInfo['try_24h_change'] ?? 0);
                $prevClose = $change != 0 ? $price / (1 + $change / 100) : $price;

                // Gram altın = ons / 31.1035
                $gramPrice = round($price / 31.1035, 2);
                $gramPrev = round($prevClose / 31.1035, 2);

                $prices['ALTIN'] = [
                    'price'      => $gramPrice,
                    'change_pct' => round($change, 2),
                    'prev_close' => $gramPrev,
                    'source'     => 'coingecko-paxg',
                    'type'       => 'gold',
                ];
                $prices['GAU'] = $prices['ALTIN']; // Alias

                $this->setCache($cacheKey, $prices);
                return $prices;
            }
        }

        return $this->getGoldFallback();
    }

    // ═══════════════════════════════════════════════════
    // HTTP Helper
    // ═══════════════════════════════════════════════════
    private function httpGet(string $url, int $timeout = 8): ?string {
        $ctx = stream_context_create([
            'http' => [
                'timeout' => $timeout,
                'header'  => "User-Agent: UniBudget/3.0\r\nAccept: application/json\r\n",
                'ignore_errors' => true,
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ]);

        $result = @file_get_contents($url, false, $ctx);
        if ($result === false) {
            error_log("[MarketData] HTTP failed: {$url}");
            return null;
        }

        // Check HTTP status
        $status = $http_response_header[0] ?? '';
        if (strpos($status, '200') === false && strpos($status, '304') === false) {
            error_log("[MarketData] HTTP {$status}: {$url}");
            return null;
        }

        return $result;
    }

    // ═══════════════════════════════════════════════════
    // Cache System (file-based)
    // ═══════════════════════════════════════════════════
    private function getCache(string $key, ?int $ttl = null): ?array {
        $file = $this->cacheDir . '/' . $key . '.json';
        if (!file_exists($file)) return null;

        $maxAge = $ttl ?? $this->cacheTTL;
        if ((time() - filemtime($file)) > $maxAge) return null;

        $data = json_decode(file_get_contents($file), true);
        return is_array($data) ? $data : null;
    }

    private function setCache(string $key, array $data, ?int $ttl = null): void {
        $file = $this->cacheDir . '/' . $key . '.json';
        @file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
    }

    // ═══════════════════════════════════════════════════
    // Fallback Data (API erişilemezse)
    // ═══════════════════════════════════════════════════
    private function getCryptoFallback(): array {
        return [
            'BTC'  => ['price' => 2835000, 'change_pct' => 0, 'prev_close' => 2835000, 'source' => 'fallback', 'type' => 'crypto'],
            'ETH'  => ['price' => 112500, 'change_pct' => 0, 'prev_close' => 112500, 'source' => 'fallback', 'type' => 'crypto'],
            'SOL'  => ['price' => 5240, 'change_pct' => 0, 'prev_close' => 5240, 'source' => 'fallback', 'type' => 'crypto'],
            'AVAX' => ['price' => 1285, 'change_pct' => 0, 'prev_close' => 1285, 'source' => 'fallback', 'type' => 'crypto'],
            'BNB'  => ['price' => 20850, 'change_pct' => 0, 'prev_close' => 20850, 'source' => 'fallback', 'type' => 'crypto'],
            'ADA'  => ['price' => 14.82, 'change_pct' => 0, 'prev_close' => 14.82, 'source' => 'fallback', 'type' => 'crypto'],
            'DOT'  => ['price' => 228.50, 'change_pct' => 0, 'prev_close' => 228.50, 'source' => 'fallback', 'type' => 'crypto'],
            'XRP'  => ['price' => 17.85, 'change_pct' => 0, 'prev_close' => 17.85, 'source' => 'fallback', 'type' => 'crypto'],
            'DOGE' => ['price' => 5.42, 'change_pct' => 0, 'prev_close' => 5.42, 'source' => 'fallback', 'type' => 'crypto'],
            'LINK' => ['price' => 520, 'change_pct' => 0, 'prev_close' => 520, 'source' => 'fallback', 'type' => 'crypto'],
        ];
    }

    private function getBISTFallback(): array {
        return [
            'THYAO' => ['price' => 318.40, 'change_pct' => 0, 'prev_close' => 318.40, 'source' => 'fallback', 'type' => 'bist'],
            'SISE'  => ['price' => 52.90, 'change_pct' => 0, 'prev_close' => 52.90, 'source' => 'fallback', 'type' => 'bist'],
            'ASELS' => ['price' => 58.75, 'change_pct' => 0, 'prev_close' => 58.75, 'source' => 'fallback', 'type' => 'bist'],
            'GARAN' => ['price' => 122.80, 'change_pct' => 0, 'prev_close' => 122.80, 'source' => 'fallback', 'type' => 'bist'],
            'AKBNK' => ['price' => 56.90, 'change_pct' => 0, 'prev_close' => 56.90, 'source' => 'fallback', 'type' => 'bist'],
            'EREGL' => ['price' => 49.22, 'change_pct' => 0, 'prev_close' => 49.22, 'source' => 'fallback', 'type' => 'bist'],
            'TUPRS' => ['price' => 152.30, 'change_pct' => 0, 'prev_close' => 152.30, 'source' => 'fallback', 'type' => 'bist'],
            'FROTO' => ['price' => 1024.00, 'change_pct' => 0, 'prev_close' => 1024.00, 'source' => 'fallback', 'type' => 'bist'],
            'KOZAL' => ['price' => 110.70, 'change_pct' => 0, 'prev_close' => 110.70, 'source' => 'fallback', 'type' => 'bist'],
            'KCHOL' => ['price' => 181.20, 'change_pct' => 0, 'prev_close' => 181.20, 'source' => 'fallback', 'type' => 'bist'],
        ];
    }

    private function getForexFallback(): array {
        return [
            'USD' => ['price' => 38.42, 'change_pct' => 0, 'prev_close' => 38.42, 'source' => 'fallback', 'type' => 'forex'],
            'EUR' => ['price' => 41.85, 'change_pct' => 0, 'prev_close' => 41.85, 'source' => 'fallback', 'type' => 'forex'],
            'GBP' => ['price' => 48.70, 'change_pct' => 0, 'prev_close' => 48.70, 'source' => 'fallback', 'type' => 'forex'],
        ];
    }

    private function getGoldFallback(): array {
        return [
            'ALTIN' => ['price' => 3185.00, 'change_pct' => 0, 'prev_close' => 3185.00, 'source' => 'fallback', 'type' => 'gold'],
            'GAU'   => ['price' => 3185.00, 'change_pct' => 0, 'prev_close' => 3185.00, 'source' => 'fallback', 'type' => 'gold'],
        ];
    }
}
