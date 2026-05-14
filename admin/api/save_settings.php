<?php
require_once '../auth_check.php';
require_once 'data_manager.php';
check_login();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dm = new DataManager();
    $result = $dm->updateSettings($_POST);

    // Save Gemini API key separately
    if (isset($_POST['gemini_api_key'])) {
        $configFile = __DIR__ . '/ai_config.json';
        $config = [];
        if (file_exists($configFile)) {
            $config = json_decode(file_get_contents($configFile), true) ?: [];
        }
        $config['gemini_api_key'] = trim($_POST['gemini_api_key']);
        file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT));
    }

    if ($result) {
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
