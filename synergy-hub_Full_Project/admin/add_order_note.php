<?php
require_once 'config.php';
checkAdminAuth();
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

$order_id = intval($_POST['order_id']);
$note = mysqli_real_escape_string($conn, $_POST['note']);
$admin_id = $_SESSION['user_id'];

$sql = "INSERT INTO OrderNotes (OrderID, UserID, Note, IsAdminNote) VALUES (?, ?, ?, 1)";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "iis", $order_id, $admin_id, $note);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
}
?>