<?php
require_once 'middleware.php';
require_once 'config.php';
checkAdminAuth();

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Item ID required']);
    exit();
}

$item_id = intval($_GET['id']);

$sql = "SELECT * FROM cafe_menu WHERE item_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $item_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($item = mysqli_fetch_assoc($result)) {
    header('Content-Type: application/json');
    echo json_encode($item);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Item not found']);
}
?>