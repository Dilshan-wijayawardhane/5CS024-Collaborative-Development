<?php
require_once 'config.php';
checkAdminAuth();

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Schedule ID required']);
    exit();
}

$id = intval($_GET['id']);

$sql = "SELECT * FROM campus_transport WHERE route_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($schedule = mysqli_fetch_assoc($result)) {
    header('Content-Type: application/json');
    echo json_encode($schedule);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Schedule not found']);
}
?>