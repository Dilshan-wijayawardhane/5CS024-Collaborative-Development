<?php
require_once 'config.php';
require_once 'functions.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$booking_id = intval($_POST['booking_id']);
$rating = intval($_POST['rating']);
$comment = mysqli_real_escape_string($conn, $_POST['comment']);

$sql = "INSERT INTO class_reviews (booking_id, rating, comment) VALUES (?, ?, ?)";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "iis", $booking_id, $rating, $comment);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
}
?>