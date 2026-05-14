<?php
/**
 * ÜniBütçe — Veritabanı Bağlantı Dosyası (Frontend)
 * Config'den PDO bağlantısını kullanır ve $pdo değişkenini sağlar.
 */

require_once __DIR__ . '/../config.php';

// Tüm tabloları güvence altına al (ilk kullanımda oluşturur)
try {
    ensureAllTables();
} catch (Exception $e) {
    error_log('Table creation error: ' . $e->getMessage());
}

// $pdo değişkenini tüm include eden dosyalar için oluştur
$pdo = getDB();
