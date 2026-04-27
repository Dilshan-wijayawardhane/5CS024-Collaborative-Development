<?php
require_once 'middleware.php';
require_once 'config.php';
checkAdminAuth();

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Facility ID required']);
    exit();
}

$facility_id = intval($_GET['id']);

$sql = "SELECT * FROM Facilities WHERE FacilityID = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $facility_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($facility = mysqli_fetch_assoc($result)) {
    header('Content-Type: application/json');
    echo json_encode($facility);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Facility not found']);
}
?>