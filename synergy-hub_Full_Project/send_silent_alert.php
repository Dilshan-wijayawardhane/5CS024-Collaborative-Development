<?php
/**
 * Current Implementation:
 *  - Logs the alert to a simple text file (silent_alerts.log)
 *  - Returns success message with timestamp
 * 
 * Security Notes:
 *  - Requires user to be logged in
 *  - No database record is created
 *  - No notification sent to admins
 *  - No location capture
 */

require_once 'config.php';
require_once 'functions.php';

session_start();
header('Content-Type: application/json');

// Authentication
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Log the silent alert
$log_entry = date('Y-m-d H:i:s') . " - User ID: $user_id - Silent Alert Triggered\n";
file_put_contents('silent_alerts.log', $log_entry, FILE_APPEND);


// Success response
echo json_encode([
    'success' => true,
    'message' => 'Silent alert sent successfully',
    'timestamp' => date('Y-m-d H:i:s')
]);
?>