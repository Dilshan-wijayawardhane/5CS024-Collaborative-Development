<?php
// send_silent_alert.php
require_once 'config.php';
require_once 'functions.php';

session_start();
header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

$user_id = $_SESSION['user_id'];

// In a real system, you would:
// 1. Log this alert in a database table (e.g., SilentAlerts)
// 2. Send notifications to security personnel (email, SMS, push)
// 3. Track the user's last known location
// 4. Potentially trigger automated responses

// For now, we'll just log it to a file and return success

$log_entry = date('Y-m-d H:i:s') . " - User ID: $user_id - Silent Alert Triggered\n";
file_put_contents('silent_alerts.log', $log_entry, FILE_APPEND);

// You could also insert into a database
/*
$sql = "INSERT INTO SilentAlerts (user_id, triggered_at, status) VALUES (?, NOW(), 'pending')";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
*/

echo json_encode([
    'success' => true,
    'message' => 'Silent alert sent successfully',
    'timestamp' => date('Y-m-d H:i:s')
]);
?>