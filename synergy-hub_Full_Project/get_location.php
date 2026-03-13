<?php
// get_location.php - Get bus location
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_GET['route'])) {
    echo json_encode(null);
    exit();
}

$route = $_GET['route'];

// Use prepared statement
$sql = "SELECT * FROM bus_routes WHERE route_id = ? LIMIT 1";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $route);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);

echo json_encode($data);
?>