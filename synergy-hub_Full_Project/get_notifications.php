<?php
/**
 * API Endpoint: Fetch the latest notifications for the logged-in user.
 * 
 * Security Notes:
 *  - Only authenticated users can access this endpoint
 *  - Uses prepared statements to prevent SQL injection
 *  - Escapes output to prevent XSS
 *  - Limits results to the 10 most recent notifications to prevent data overload
 */

require_once 'config.php';
require_once 'functions.php';

header('Content-Type: application/json');

// Authentication
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Get unread notification count
$count_sql = "SELECT COUNT(*) as unread_count FROM notifications WHERE user_id = ? AND is_read = FALSE";
$count_stmt = mysqli_prepare($conn, $count_sql);
mysqli_stmt_bind_param($count_stmt, "i", $user_id);
mysqli_stmt_execute($count_stmt);
$count_result = mysqli_stmt_get_result($count_stmt);
$count_data = mysqli_fetch_assoc($count_result);

// Fetch latest 10 notifications
$notif_sql = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 10";
$notif_stmt = mysqli_prepare($conn, $notif_sql);
mysqli_stmt_bind_param($notif_stmt, "i", $user_id);
mysqli_stmt_execute($notif_stmt);
$notif_result = mysqli_stmt_get_result($notif_stmt);

$notifications = [];
while($row = mysqli_fetch_assoc($notif_result)) {
    $notifications[] = [
        'id' => $row['notification_id'],
        'title' => htmlspecialchars($row['title']),
        'message' => htmlspecialchars($row['message']),
        'type' => $row['type'],
        'is_read' => (bool)$row['is_read'],
        'time' => date('g:i A', strtotime($row['created_at'])),
        'date' => date('M d', strtotime($row['created_at']))
    ];
}

// Success response
echo json_encode([
    'success' => true,
    'unread_count' => $count_data['unread_count'],
    'notifications' => $notifications
]);
?>