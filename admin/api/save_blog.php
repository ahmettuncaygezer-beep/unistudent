<?php
declare(strict_types=1);
require_once '../auth_check.php';
require_once 'blog_manager.php';
check_login();

header('Content-Type: application/json');

/**
 * Admin blog içeriği için izin verilen HTML etiket listesi ile sanitize.
 * script/iframe/on* saldırı yüzeylerini keser.
 */
function sanitizeBlogHtml(string $html): string
{
    // Script/style/iframe bloklarını tamamen çıkar
    $html = preg_replace('#<(script|style|iframe|object|embed)\b[^>]*>.*?</\1>#is', '', $html);
    $html = preg_replace('#<(script|style|iframe|object|embed)\b[^>]*/?>#i', '', $html);
    // Tehlikeli nitelikler: on*, javascript:, data: URI
    $html = preg_replace('#\son[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)#i', '', $html);
    $html = preg_replace('#(href|src)\s*=\s*("|\')\s*javascript:[^"\']*(\2)#i', '$1=$2#$2', $html);

    $allowed = '<p><h1><h2><h3><h4><br><hr><strong><em><u><ul><ol><li><a><img><blockquote><code><pre><table><thead><tbody><tr><td><th><span><div>';
    return strip_tags($html, $allowed);
}

// Sadece filename için güvenli karakterler
function sanitizeBlogFilename(string $name): string
{
    $name = basename($name);
    $name = preg_replace('/[^A-Za-z0-9._-]/', '-', $name);
    return substr($name, 0, 120);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bm = new BlogManager();

    // Check if delete or save
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $filename = sanitizeBlogFilename($_POST['filename'] ?? '');
        if ($bm->deleteBlog($filename)) {
            echo json_encode(['success' => true]);
        }
        else {
            echo json_encode(['success' => false, 'message' => 'Delete failed']);
        }
        exit;
    }

    // Save
    $status = in_array($_POST['status'] ?? 'published', ['draft', 'published'], true)
        ? $_POST['status'] : 'published';
    $data = [
        'filename'    => sanitizeBlogFilename($_POST['filename'] ?? ''),
        'title'       => trim($_POST['title'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'keywords'    => trim($_POST['keywords'] ?? ''),
        'content'     => sanitizeBlogHtml($_POST['content'] ?? ''),
        'cover_image' => trim($_POST['cover_image'] ?? ''),
        'status'      => $status,
    ];

    if (empty($data['filename']) || empty($data['title'])) {
        echo json_encode(['success' => false, 'message' => 'Missing fields']);
        exit;
    }

    if ($bm->saveBlog($data)) {
        echo json_encode(['success' => true]);
    }
    else {
        echo json_encode(['success' => false, 'message' => 'Save failed']);
    }
}
else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>
