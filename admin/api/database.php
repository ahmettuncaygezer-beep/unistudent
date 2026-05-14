<?php
/**
 * ÜniBütçe Admin — Veritabanı Bağlantısı
 * Merkezi config.php'yi kullanır.
 */

// Config'i yükle (zaten tanımlanmış ise tekrar tanımlama)
if (!defined('DB_HOST')) {
    require_once __DIR__ . '/../../config.php';
}

// ensureTables — BlogManager backward compat için
function ensureTables(): bool {
    ensureAllTables();
    return true;
}
