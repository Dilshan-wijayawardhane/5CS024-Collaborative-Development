<?php


require_once 'config.php';
require_once 'functions.php';

header('Content-Type: application/json');


if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['route']) && isset($_POST['price'])) {
    $user_id = $_SESSION['user_id'];
    $route = $_POST['route'];
    $price = intval($_POST['price']);
    

   
    $points_sql = "SELECT PointsBalance FROM Users WHERE UserID = ?";
    $points_stmt = mysqli_prepare($conn, $points_sql);
    mysqli_stmt_bind_param($points_stmt, "i", $user_id);
    mysqli_stmt_execute($points_stmt);
    $points_result = mysqli_stmt_get_result($points_stmt);
    $user = mysqli_fetch_assoc($points_result);
    
    if ($user['PointsBalance'] < $price) {
        echo json_encode(['success' => false, 'message' => 'Not enough points']);
        exit();
    }
    
   
    $valid_until = date('Y-m-d', strtotime('+30 days'));
    $pass_sql = "INSERT INTO TransportPasses (UserID, RouteName, ValidUntil, Status) VALUES (?, ?, ?, 'Active')";
    $pass_stmt = mysqli_prepare($conn, $pass_sql);
    mysqli_stmt_bind_param($pass_stmt, "iss", $user_id, $route, $valid_until);
    
    if (mysqli_stmt_execute($pass_stmt)) {
        
        $update_sql = "UPDATE Users SET PointsBalance = PointsBalance - ? WHERE UserID = ?";
        $update_stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($update_stmt, "ii", $price, $user_id);
        mysqli_stmt_execute($update_stmt);
        
        
        logActivity($conn, $user_id, 'BUY_PASS', 'TransportPasses', mysqli_insert_id($conn));
        
        echo json_encode(['success' => true, 'message' => 'Pass purchased']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>