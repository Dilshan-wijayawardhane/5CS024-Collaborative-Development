<?php
require_once 'config.php';
require_once 'functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Check if notifications table exists
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'notifications'");
if (mysqli_num_rows($table_check) == 0) {
    echo json_encode(['success' => true, 'message' => 'No notifications table']);
    exit();
}

$all = isset($_POST['all']) ? true : false;
$notification_id = isset($_POST['notification_id']) ? intval($_POST['notification_id']) : 0;

if ($all) {
    $sql = "UPDATE notifications SET is_read = 1 WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
} else if ($notification_id > 0) {
    $sql = "UPDATE notifications SET is_read = 1 WHERE notification_id = ? AND user_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ii", $notification_id, $user_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

echo json_encode(['success' => true]);
?>