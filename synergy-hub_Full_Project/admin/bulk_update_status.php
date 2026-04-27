<?php
require_once 'config.php';
checkAdminAuth();
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

$order_ids = explode(',', $_POST['order_ids']);
$new_status = mysqli_real_escape_string($conn, $_POST['status']);
$admin_id = $_SESSION['user_id'];
$updated_count = 0;

mysqli_begin_transaction($conn);

try {
    foreach ($order_ids as $order_id) {
        $order_id = intval($order_id);
        
        $current_sql = "SELECT Status, UserID FROM Orders WHERE OrderID = ?";
        $current_stmt = mysqli_prepare($conn, $current_sql);
        mysqli_stmt_bind_param($current_stmt, "i", $order_id);
        mysqli_stmt_execute($current_stmt);
        $current_result = mysqli_stmt_get_result($current_stmt);
        $order = mysqli_fetch_assoc($current_result);
        
        if ($order) {
            $old_status = $order['Status'];
            
            $update_sql = "UPDATE Orders SET Status = ? WHERE OrderID = ?";
            $update_stmt = mysqli_prepare($conn, $update_sql);
            mysqli_stmt_bind_param($update_stmt, "si", $new_status, $order_id);
            mysqli_stmt_execute($update_stmt);
            
            $log_sql = "INSERT INTO OrderStatusLog (OrderID, OldStatus, NewStatus, ChangedBy) VALUES (?, ?, ?, ?)";
            $log_stmt = mysqli_prepare($conn, $log_sql);
            mysqli_stmt_bind_param($log_stmt, "issi", $order_id, $old_status, $new_status, $admin_id);
            mysqli_stmt_execute($log_stmt);
            
            // Award points if completed
            if ($new_status == 'Completed' && $old_status != 'Completed') {
                $points_sql = "UPDATE Users SET PointsBalance = PointsBalance + 5 WHERE UserID = ?";
                $points_stmt = mysqli_prepare($conn, $points_sql);
                mysqli_stmt_bind_param($points_stmt, "i", $order['UserID']);
                mysqli_stmt_execute($points_stmt);
            }
            
            $updated_count++;
        }
    }
    
    mysqli_commit($conn);
    echo json_encode(['success' => true, 'updated_count' => $updated_count]);
    
} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>