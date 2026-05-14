<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';
require_once 'functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'unread_count' => 0, 'notifications' => []]);
    exit();
}

$user_id = $_SESSION['user_id'];

// Check if notifications table exists
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'notifications'");
$table_exists = mysqli_num_rows($table_check) > 0;

$unread_count = 0;
$notifications = [];

if ($table_exists) {
    // Check if user_id column exists
    $col_check = mysqli_query($conn, "SHOW COLUMNS FROM notifications LIKE 'user_id'");
    if(mysqli_num_rows($col_check) > 0) {
        $unread_sql = "SELECT COUNT(*) as unread FROM notifications WHERE user_id = ? AND is_read = 0";
        $unread_stmt = mysqli_prepare($conn, $unread_sql);
        mysqli_stmt_bind_param($unread_stmt, "i", $user_id);
        mysqli_stmt_execute($unread_stmt);
        $unread_result = mysqli_stmt_get_result($unread_stmt);
        $unread_data = mysqli_fetch_assoc($unread_result);
        $unread_count = $unread_data ? (int)$unread_data['unread'] : 0;
        
        $sql = "SELECT notification_id, title, message, type, is_read, created_at 
                FROM notifications 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT 10";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        while($row = mysqli_fetch_assoc($result)) {
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
    }
}

// Only show demo data if table doesn't exist
if (!$table_exists && empty($notifications)) {
    $unread_count = 3;
    $notifications = [
        ['id' => 1, 'title' => 'Welcome to Synergy Hub!', 'message' => 'Thank you for joining. Explore our facilities!', 'type' => 'general', 'is_read' => false, 'time' => date('g:i A'), 'date' => date('M d')],
        ['id' => 2, 'title' => 'Gym Special Offer', 'message' => 'Get 20% off on gym membership this week!', 'type' => 'gym', 'is_read' => false, 'time' => date('g:i A', strtotime('-1 day')), 'date' => date('M d', strtotime('-1 day'))],
        ['id' => 3, 'title' => 'Transport Schedule', 'message' => 'New bus schedule has been published.', 'type' => 'transport', 'is_read' => false, 'time' => date('g:i A', strtotime('-2 days')), 'date' => date('M d', strtotime('-2 days'))]
    ];
}

echo json_encode(['success' => true, 'unread_count' => $unread_count, 'notifications' => $notifications]);
?>