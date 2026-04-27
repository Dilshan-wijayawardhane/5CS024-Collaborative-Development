<?php
require_once 'config.php';
checkAdminAuth();

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="orders_' . date('Y-m-d') . '.csv"');

$output = fopen('php://output', 'w');
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

// Headers
fputcsv($output, ['Order ID', 'Customer Name', 'Item', 'Category', 'Quantity', 'Price', 'Total', 'Status', 'Order Date']);

// Get orders based on filters
$where = "1=1";
if(isset($_GET['status']) && $_GET['status']) {
    $where .= " AND Status = '" . mysqli_real_escape_string($conn, $_GET['status']) . "'";
}
if(isset($_GET['category']) && $_GET['category']) {
    $where .= " AND Category = '" . mysqli_real_escape_string($conn, $_GET['category']) . "'";
}
if(isset($_GET['search']) && $_GET['search']) {
    $search = mysqli_real_escape_string($conn, $_GET['search']);
    $where .= " AND (OrderID LIKE '%$search%' OR ItemName LIKE '%$search%')";
}
if(isset($_GET['date_filter'])) {
    if($_GET['date_filter'] == 'today') {
        $where .= " AND DATE(Timestamp) = CURDATE()";
    } elseif($_GET['date_filter'] == 'week') {
        $where .= " AND Timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
    } elseif($_GET['date_filter'] == 'month') {
        $where .= " AND Timestamp >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    }
}
if(isset($_GET['user_id']) && $_GET['user_id']) {
    $where .= " AND UserID = " . intval($_GET['user_id']);
}

$sql = "SELECT o.*, u.Name as CustomerName 
        FROM Orders o 
        JOIN Users u ON o.UserID = u.UserID 
        WHERE $where 
        ORDER BY o.Timestamp DESC";
$result = mysqli_query($conn, $sql);

while($row = mysqli_fetch_assoc($result)) {
    fputcsv($output, [
        $row['OrderID'],
        $row['CustomerName'],
        $row['ItemName'],
        $row['Category'],
        $row['Quantity'],
        $row['Price'],
        $row['Price'] * $row['Quantity'],
        $row['Status'],
        $row['Timestamp']
    ]);
}

fclose($output);
exit();
?>