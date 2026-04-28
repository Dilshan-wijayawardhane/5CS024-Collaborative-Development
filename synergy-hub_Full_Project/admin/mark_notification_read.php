<?php
require_once 'middleware.php';
require_once 'config.php';
require_once 'functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false]);
    exit();
}

$user_id = $_SESSION['user_id'];

if (isset($_POST['notification_id'])) {
    // Mark single notification as read
    $notif_id = intval($_POST['notification_id']);
    $sql = "UPDATE Notifications SET Status = 'Read' WHERE NotificationID = ? AND UserID = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $notif_id, $user_id);
} else {
    // Mark all as read
    $sql = "UPDATE Notifications SET Status = 'Read' WHERE UserID = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
}

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}
?>