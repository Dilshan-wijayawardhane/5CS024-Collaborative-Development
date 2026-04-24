<?php
require_once 'config.php';
require_once 'functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];
$booking_id = intval($_POST['booking_id']);

// Check if booking belongs to user
$check_sql = "SELECT * FROM pool_bookings WHERE booking_id = ? AND user_id = ?";
$check_stmt = mysqli_prepare($conn, $check_sql);
mysqli_stmt_bind_param($check_stmt, "ii", $booking_id, $user_id);
mysqli_stmt_execute($check_stmt);
$check_result = mysqli_stmt_get_result($check_stmt);

if (mysqli_num_rows($check_result) == 0) {
    echo json_encode(['success' => false, 'message' => 'Booking not found']);
    exit();
}

// Update booking status
$update_sql = "UPDATE pool_bookings SET status = 'cancelled' WHERE booking_id = ?";
$update_stmt = mysqli_prepare($conn, $update_sql);
mysqli_stmt_bind_param($update_stmt, "i", $booking_id);

if (mysqli_stmt_execute($update_stmt)) {
    logActivity($conn, $user_id, 'BOOKING_CANCELLED', 'pool_bookings', $booking_id);
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>