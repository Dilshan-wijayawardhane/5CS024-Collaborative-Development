<?php

require_once 'config.php';
require_once 'functions.php';

session_start();
header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

$user_id = $_SESSION['user_id'];



$log_entry = date('Y-m-d H:i:s') . " - User ID: $user_id - Silent Alert Triggered\n";
file_put_contents('silent_alerts.log', $log_entry, FILE_APPEND);



echo json_encode([
    'success' => true,
    'message' => 'Silent alert sent successfully',
    'timestamp' => date('Y-m-d H:i:s')
]);
?>