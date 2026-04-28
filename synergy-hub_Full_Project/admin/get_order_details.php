<?php
require_once 'config.php';
checkAdminAuth();
header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Order ID required']);
    exit();
}

$order_id = intval($_GET['id']);

// Get order details
$order_sql = "SELECT * FROM Orders WHERE OrderID = ?";
$order_stmt = mysqli_prepare($conn, $order_sql);
mysqli_stmt_bind_param($order_stmt, "i", $order_id);
mysqli_stmt_execute($order_stmt);
$order_result = mysqli_stmt_get_result($order_stmt);
$order = mysqli_fetch_assoc($order_result);

if (!$order) {
    echo json_encode(['success' => false, 'message' => 'Order not found']);
    exit();
}

// Get customer details
$customer_sql = "SELECT Name, Email FROM Users WHERE UserID = ?";
$customer_stmt = mysqli_prepare($conn, $customer_sql);
mysqli_stmt_bind_param($customer_stmt, "i", $order['UserID']);
mysqli_stmt_execute($customer_stmt);
$customer_result = mysqli_stmt_get_result($customer_stmt);
$customer = mysqli_fetch_assoc($customer_result);

// Get customer stats
$stats_sql = "SELECT COUNT(*) as total_orders, SUM(Price * Quantity) as total_spent FROM Orders WHERE UserID = ?";
$stats_stmt = mysqli_prepare($conn, $stats_sql);
mysqli_stmt_bind_param($stats_stmt, "i", $order['UserID']);
mysqli_stmt_execute($stats_stmt);
$stats_result = mysqli_stmt_get_result($stats_stmt);
$stats = mysqli_fetch_assoc($stats_result);

// Get status timeline
$timeline_sql = "SELECT l.*, u.Name as ChangedByName 
                 FROM OrderStatusLog l
                 LEFT JOIN Users u ON l.ChangedBy = u.UserID
                 WHERE l.OrderID = ?
                 ORDER BY l.ChangedAt ASC";
$timeline_stmt = mysqli_prepare($conn, $timeline_sql);
mysqli_stmt_bind_param($timeline_stmt, "i", $order_id);
mysqli_stmt_execute($timeline_stmt);
$timeline_result = mysqli_stmt_get_result($timeline_stmt);

$timeline = [];
while($row = mysqli_fetch_assoc($timeline_result)) {
    $timeline[] = [
        'old_status' => $row['OldStatus'],
        'new_status' => $row['NewStatus'],
        'changed_at' => date('M d, Y h:i A', strtotime($row['ChangedAt'])),
        'changed_by' => $row['ChangedByName'],
        'notes' => $row['Notes']
    ];
}

// Get notes
$notes_sql = "SELECT * FROM OrderNotes WHERE OrderID = ? ORDER BY CreatedAt DESC";
$notes_stmt = mysqli_prepare($conn, $notes_sql);
mysqli_stmt_bind_param($notes_stmt, "i", $order_id);
mysqli_stmt_execute($notes_stmt);
$notes_result = mysqli_stmt_get_result($notes_stmt);

$notes = [];
while($row = mysqli_fetch_assoc($notes_result)) {
    $notes[] = [
        'note' => $row['Note'],
        'created_at' => date('M d, Y h:i A', strtotime($row['CreatedAt'])),
        'is_admin' => $row['IsAdminNote']
    ];
}

echo json_encode([
    'success' => true,
    'order' => $order,
    'customer' => $customer,
    'stats' => $stats,
    'timeline' => $timeline,
    'notes' => $notes
]);
?>