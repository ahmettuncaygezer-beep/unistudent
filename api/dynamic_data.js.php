<?php
/**
 * ÜniBütçe - Dinamik Veri API'si (JS)
 * Veritabanındaki cities tablosundan CITY_DATA objesini JS formatında sunar.
 */
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/javascript; charset=utf-8');
header('Cache-Control: public, max-age=3600');
header('Vary: Accept-Encoding');

try {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM cities ORDER BY id ASC");
    $cities = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $cityData = [];
    foreach ($cities as $c) {
        $cityData[$c['slug']] = [
            'name' => $c['name'],
            'emoji' => $c['emoji'],
            'description' => $c['description'],
            'livingScore' => (float)$c['living_score'],
            'costs' => [
                'rentSingle' => (int)$c['rent_single'],
                'rentShared' => (int)$c['rent_shared'],
                'dormPrivate' => (int)$c['dorm_private'],
                'dormKYK' => (int)$c['dorm_kyk'],
                'food' => (int)$c['food'],
                'transport' => (int)$c['transport'],
                'entertainment' => (int)$c['entertainment'],
                'utilities' => (int)$c['utilities']
            ],
            'tips' => [
                $c['tip_1'],
                $c['tip_2']
            ]
        ];
    }
    
    echo "const CITY_DATA = " . json_encode($cityData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . ";\n";
    
} catch (Exception $e) {
    // Fallback: Boş obje döndür
    echo "const CITY_DATA = {};\n// City data unavailable\n";
}
