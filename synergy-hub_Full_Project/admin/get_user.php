<?php
require_once 'middleware.php';
require_once 'config.php';
checkAdminAuth();

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'User ID required']);
    exit();
}

$user_id = (int)$_GET['id'];

$sql = "SELECT UserID, Name, Email, StudentID, Role, PointsBalance, MembershipStatus, CreatedAt 
        FROM Users WHERE UserID = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($user = mysqli_fetch_assoc($result)) {
    header('Content-Type: application/json');
    echo json_encode($user);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'User not found']);
}
?>