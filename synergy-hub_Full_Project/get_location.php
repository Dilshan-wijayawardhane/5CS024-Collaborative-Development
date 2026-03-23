<?php
/**
 * API Endpoint: Fetch bus route details based on route_id for the transportation page.
 * 
 * Security Notes:
 *  - Public endpoint (no login required)
 *  - Uses prepared statements to prevent SQL injection
 *  - Returns null if route_id is missing or not found, rather than an error message
 */

require_once 'config.php';

header('Content-Type: application/json');

// Validate input
if (!isset($_GET['route'])) {
    echo json_encode(null);
    exit();
}

$route = $_GET['route'];

//Fetch route details
$sql = "SELECT * FROM bus_routes WHERE route_id = ? LIMIT 1";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $route);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);

//Return route details or null if not found
echo json_encode($data);
?>