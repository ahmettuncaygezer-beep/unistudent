<?php
require_once '../auth_check.php';
require_once 'data_manager.php';
check_login();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $index = $_POST['index'] ?? null;
    $rentSingle = $_POST['rentSingle'] ?? null;

    if ($index === null) {
        echo json_encode(['success' => false, 'message' => 'Missing index']);
        exit;
    }

    $dm = new DataManager();
    $result = $dm->updateCity($index, $_POST);

    if ($result === true) {
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
