<?php
require_once 'config.php';
require_once 'functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['item_name']) && isset($_POST['category']) && isset($_POST['price']) && isset($_POST['payment'])) {
    
    $user_id = $_SESSION['user_id'];
    $item_name = $_POST['item_name'];
    $category = $_POST['category'];
    $price = floatval($_POST['price']);
    $points = intval($_POST['points']);
    $payment = $_POST['payment'];
    
    // If paying with points, check balance
    if($payment == 'points' && $points > 0) {
        $check_sql = "SELECT PointsBalance FROM Users WHERE UserID = ?";
        $check_stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($check_stmt, "i", $user_id);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);
        $user = mysqli_fetch_assoc($check_result);
        
        if($user['PointsBalance'] < $points) {
            echo json_encode(['success' => false, 'message' => 'Not enough points']);
            exit();
        }
        
        // Deduct points
        $deduct_sql = "UPDATE Users SET PointsBalance = PointsBalance - ? WHERE UserID = ?";
        $deduct_stmt = mysqli_prepare($conn, $deduct_sql);
        mysqli_stmt_bind_param($deduct_stmt, "ii", $points, $user_id);
        mysqli_stmt_execute($deduct_stmt);
    }
    
    // Insert order
    $status = 'Pending';
    $order_sql = "INSERT INTO Orders (UserID, ItemName, Category, Price, Status) VALUES (?, ?, ?, ?, ?)";
    $order_stmt = mysqli_prepare($conn, $order_sql);
    mysqli_stmt_bind_param($order_stmt, "issds", $user_id, $item_name, $category, $price, $status);
    
    if (mysqli_stmt_execute($order_stmt)) {
        logActivity($conn, $user_id, 'ORDER', 'Orders', mysqli_insert_id($conn));
        echo json_encode(['success' => true, 'message' => 'Order placed']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>