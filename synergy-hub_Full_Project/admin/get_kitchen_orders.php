<?php
require_once 'config.php';
checkAdminAuth();
header('Content-Type: application/json');

$kitchen_sql = "SELECT o.OrderID, o.ItemName, o.Quantity, o.Status, o.Timestamp, u.Name as CustomerName,
                TIMESTAMPDIFF(SECOND, o.Timestamp, NOW()) as time_elapsed
                FROM Orders o 
                JOIN Users u ON o.UserID = u.UserID 
                WHERE o.Status IN ('Pending', 'Preparing', 'Ready')
                ORDER BY FIELD(o.Status, 'Pending', 'Preparing', 'Ready'), o.Timestamp ASC";
$kitchen_result = mysqli_query($conn, $kitchen_sql);

$orders = [];
while($order = mysqli_fetch_assoc($kitchen_result)) {
    $orders[] = $order;
}

echo json_encode(['success' => true, 'orders' => $orders]);
?>