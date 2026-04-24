<?php
require_once 'config.php';
require_once 'functions.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$booking_id = intval($_POST['booking_id']);
$code = uniqid('GYM_' . $booking_id . '_');

$sql = "UPDATE class_bookings SET check_in_code = ? WHERE booking_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "si", $code, $booking_id);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(['success' => true, 'qr_data' => $code]);
} else {
    echo json_encode(['success' => false]);
}
?>