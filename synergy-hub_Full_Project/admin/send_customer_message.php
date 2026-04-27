<?php
require_once 'config.php';
checkAdminAuth();
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

// If you don't want notifications at all, just return success
echo json_encode(['success' => true, 'message' => 'Message feature disabled']);
exit();
?>