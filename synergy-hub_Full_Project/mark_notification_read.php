<?php
/**
 * API Endpoint: Mark a notification as read for the logged-in user
 * 
 * Security Notes:
 *  - Requires authentication
 *  - Uses prepared statements
 *  - No transaction needed
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

// Validate request method
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['notification_id'])) {
        $notif_id = intval($_POST['notification_id']);
        $sql = "UPDATE notifications SET is_read = TRUE WHERE notification_id = ? AND user_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $notif_id, $user_id);
    } else {
        // Mark all notifications as read
        $sql = "UPDATE notifications SET is_read = TRUE WHERE user_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
    }
    
    // Execute update
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>