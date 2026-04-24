<?php
require_once 'config.php';
require_once 'functions.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];
$class_id = intval($_POST['class_id']);

// Check if already on waitlist
$check_sql = "SELECT * FROM class_waitlist WHERE class_id = ? AND user_id = ? AND status = 'waiting'";
$check_stmt = mysqli_prepare($conn, $check_sql);
mysqli_stmt_bind_param($check_stmt, "ii", $class_id, $user_id);
mysqli_stmt_execute($check_stmt);
if (mysqli_num_rows(mysqli_stmt_get_result($check_stmt)) > 0) {
    echo json_encode(['success' => false, 'message' => 'Already on waitlist']);
    exit();
}

$sql = "INSERT INTO class_waitlist (class_id, user_id) VALUES (?, ?)";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $class_id, $user_id);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(['success' => true, 'message' => 'Added to waitlist']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>