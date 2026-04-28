<?php
require_once 'middleware.php';
require_once 'config.php';
checkAdminAuth();

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    $sql = "SELECT * FROM Rewards WHERE RewardID = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        header('Content-Type: application/json');
        echo json_encode($row);
        exit();
    }
}

http_response_code(404);
echo json_encode(['error' => 'Reward not found']);
?>