<?php
// generate_image.php — Async Fal.ai Image Generation Endpoint
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

$FAL_KEY = '3cbaf339-e188-4226-9d2a-2d9c978e122c:54f62c8a7e9a2699ab9aca57f7154f64';

$prompt = $_GET['prompt'] ?? '';
if (empty($prompt)) {
    echo json_encode(['error' => 'Prompt gereklidir.']);
    exit;
}

$payload = json_encode([
    'prompt' => $prompt,
    'image_size' => 'landscape_16_9',
    'num_inference_steps' => 28,
    'guidance_scale' => 7.5,
    'num_images' => 1,
    'enable_safety_checker' => false
]);

$ch = curl_init('https://fal.run/fal-ai/fast-sdxl');
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 120);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Key ' . $FAL_KEY,
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200 || !$response) {
    echo json_encode(['error' => 'Görsel oluşturulamadı. HTTP: ' . $httpCode]);
    exit;
}

$data = json_decode($response, true);
$imageUrl = $data['images'][0]['url'] ?? ($data['image']['url'] ?? null);

if (!$imageUrl) {
    echo json_encode(['error' => 'Görsel URL bulunamadı.', 'raw' => $data]);
    exit;
}

echo json_encode(['image_url' => $imageUrl]);
