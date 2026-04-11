<?php

/**
 * Features:
 *  - Accepts cart items via JSON POST
 *  - Supports two payment method: points and cash
 *  - Validates points balance before placing order
 *  - Logs order activity
 * 
 * Security Notes:
 *  - Points validation is done server-side
 */

require_once 'config.php';
require_once 'functions.php';

header('Content-Type: application/json');

// Authentication check
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Read and validate JSON input
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['items']) || !isset($data['payment'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

$items = $data['items'];
$payment = $data['payment'];
$total_points = isset($data['total_points']) ? $data['total_points'] : 0;

// Handle points payment
if ($payment === 'points') {
    $check_sql = "SELECT PointsBalance FROM Users WHERE UserID = ?";
    $check_stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($check_stmt, "i", $user_id);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    $user = mysqli_fetch_assoc($check_result);
    
    if ($user['PointsBalance'] < $total_points) {
        echo json_encode(['success' => false, 'message' => 'Not enough points']);
        exit();
    }
    
    


    $deduct_sql = "UPDATE Users SET PointsBalance = PointsBalance - ? WHERE UserID = ?";
    $deduct_stmt = mysqli_prepare($conn, $deduct_sql);
    mysqli_stmt_bind_param($deduct_stmt, "ii", $total_points, $user_id);
    mysqli_stmt_execute($deduct_stmt);
}

// Create order records for each item
$success = true;
foreach ($items as $item) {
    for ($i = 0; $i < $item['qty']; $i++) {
        $order_sql = "INSERT INTO Orders (UserID, ItemName, Price, Status) VALUES (?, ?, ?, 'Pending')";
        $order_stmt = mysqli_prepare($conn, $order_sql);
        $price = $item['price'] ?: 0;
        mysqli_stmt_bind_param($order_stmt, "isd", $user_id, $item['name'], $price);
        
        if (!mysqli_stmt_execute($order_stmt)) {
            $success = false;
        }
    }
}

// Log activity and respond
if ($success) {
    logActivity($conn, $user_id, 'ORDER', 'Orders', 0);
    echo json_encode(['success' => true, 'message' => 'Order placed']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>