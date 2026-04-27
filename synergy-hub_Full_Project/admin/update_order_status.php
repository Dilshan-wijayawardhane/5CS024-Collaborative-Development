<?php
require_once 'config.php';
checkAdminAuth();
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

$order_id = intval($_POST['order_id']);
$new_status = mysqli_real_escape_string($conn, $_POST['status']);
$admin_id = $_SESSION['user_id'];

// Get current status
$current_sql = "SELECT Status, UserID FROM Orders WHERE OrderID = ?";
$current_stmt = mysqli_prepare($conn, $current_sql);
mysqli_stmt_bind_param($current_stmt, "i", $order_id);
mysqli_stmt_execute($current_stmt);
$current_result = mysqli_stmt_get_result($current_stmt);
$order = mysqli_fetch_assoc($current_result);

if (!$order) {
    echo json_encode(['success' => false, 'message' => 'Order not found']);
    exit();
}

$old_status = $order['Status'];
$user_id = $order['UserID'];

mysqli_begin_transaction($conn);

try {
    // Update order status
    $update_sql = "UPDATE Orders SET Status = ? WHERE OrderID = ?";
    $update_stmt = mysqli_prepare($conn, $update_sql);
    mysqli_stmt_bind_param($update_stmt, "si", $new_status, $order_id);
    mysqli_stmt_execute($update_stmt);
    
    // Log status change
    $log_sql = "INSERT INTO OrderStatusLog (OrderID, OldStatus, NewStatus, ChangedBy, Notes) VALUES (?, ?, ?, ?, ?)";
    $log_stmt = mysqli_prepare($conn, $log_sql);
    $notes = "Status changed by admin";
    mysqli_stmt_bind_param($log_stmt, "issis", $order_id, $old_status, $new_status, $admin_id, $notes);
    mysqli_stmt_execute($log_stmt);
    
    // Award points if completed (5 points)
    if ($new_status == 'Completed' && $old_status != 'Completed') {
        $points_sql = "UPDATE Users SET PointsBalance = PointsBalance + 5 WHERE UserID = ?";
        $points_stmt = mysqli_prepare($conn, $points_sql);
        mysqli_stmt_bind_param($points_stmt, "i", $user_id);
        mysqli_stmt_execute($points_stmt);
    }
    
    // Refund points if cancelled from completed (optional)
    if ($new_status == 'Cancelled' && $old_status == 'Completed') {
        $points_sql = "UPDATE Users SET PointsBalance = PointsBalance - 5 WHERE UserID = ?";
        $points_stmt = mysqli_prepare($conn, $points_sql);
        mysqli_stmt_bind_param($points_stmt, "i", $user_id);
        mysqli_stmt_execute($points_stmt);
    }
    
    mysqli_commit($conn);
    echo json_encode(['success' => true, 'message' => 'Status updated']);
    
} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>