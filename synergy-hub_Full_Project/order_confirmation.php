<?php

/**
 * Features:
 *  - Displays confirmation with success icon
 *  - Shows order details
 *  - Groups multiple orders if multiple were placed in one checkout
 *  - Provide action buttons
 * 
 * Security Notes:
 *  - Requires login
 *  - Uses prepared statements
 *  - Safe output with htmlspecialchars()
 *  - Handles empty or invalid order_id gracefully
 */

require_once 'config.php';
require_once 'functions.php';

// Authentication
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
// Get order IDs from URL
$order_ids = isset($_GET['order_id']) ? explode(',', $_GET['order_id']) : [];

if (empty($order_ids)) {
    header("Location: cafe_menu.php");
    exit();
}

// Fetch orders with dynamic IN clause
$placeholders = implode(',', array_fill(0, count($order_ids), '?'));
$types = str_repeat('i', count($order_ids));

$order_sql = "SELECT * FROM Orders WHERE OrderID IN ($placeholders) AND UserID = ? ORDER BY Timestamp DESC";
$order_stmt = mysqli_prepare($conn, $order_sql);

$params = array_merge($order_ids, [$user_id]);
mysqli_stmt_bind_param($order_stmt, $types . "i", ...$params);
mysqli_stmt_execute($order_stmt);
$order_result = mysqli_stmt_get_result($order_stmt);

$orders = [];
$total_amount = 0;
while ($order = mysqli_fetch_assoc($order_result)) {
    $orders[] = $order;
    $total_amount += $order['Price'] * $order['Quantity'];
}

$first_order = $orders[0] ?? null;
$item_count = count($orders);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmed - Synergy Hub</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
        }
        
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .confirmation-card {
            background: white;
            border-radius: 30px;
            padding: 50px;
            max-width: 550px;
            width: 100%;
            text-align: center;
            border: 1px solid #e2e8f0;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            animation: slideUp 0.5s ease;
        }
        
        @keyframes slideUp {
            from {
                transform: translateY(50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .success-icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #10b981, #059669);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            font-size: 50px;
            color: white;
            box-shadow: 0 10px 30px rgba(16, 185, 129, 0.3);
        }
        
        h1 {
            color: #1e4a76;
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .order-number {
            color: #2c7da0;
            font-size: 16px;
            margin-bottom: 30px;
            background: #e0f2fe;
            padding: 10px 20px;
            border-radius: 50px;
            display: inline-block;
            font-weight: 500;
        }
        
        .order-number i {
            margin-right: 5px;
        }
        
        .order-details {
            background: #f8fafc;
            border-radius: 20px;
            padding: 25px;
            margin: 30px 0;
            text-align: left;
            border: 1px solid #e2e8f0;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #e2e8f0;
            color: #1e293b;
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            color: #64748b;
        }
        
        .detail-value {
            font-weight: 600;
            color: #2c7da0;
        }
        
        .items-list {
            margin-top: 15px;
            max-height: 250px;
            overflow-y: auto;
        }
        
        .items-list::-webkit-scrollbar {
            width: 4px;
        }
        
        .items-list::-webkit-scrollbar-track {
            background: #e2e8f0;
            border-radius: 10px;
        }
        
        .items-list::-webkit-scrollbar-thumb {
            background: #2c7da0;
            border-radius: 10px;
        }
        
        .item-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            color: #475569;
            font-size: 14px;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .item-row:last-child {
            border-bottom: none;
        }
        
        .total-amount {
            font-size: 24px;
            margin: 20px 0;
            padding-top: 20px;
            border-top: 2px solid #e2e8f0;
        }
        
        .total-amount span:first-child {
            color: #64748b;
            font-size: 16px;
            font-weight: normal;
        }
        
        .total-amount span:last-child {
            color: #2c7da0;
            font-weight: 700;
            font-size: 28px;
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }
        
        .btn {
            flex: 1;
            padding: 14px 20px;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #1e4a76 0%, #2c7da0 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(30, 74, 118, 0.3);
        }
        
        .btn-secondary {
            background: #f8fafc;
            color: #1e4a76;
            border: 1px solid #e2e8f0;
        }
        
        .btn-secondary:hover {
            background: #e0f2fe;
            border-color: #2c7da0;
        }
        
        .btn i {
            font-size: 14px;
        }
        
        .badge {
            background: #f59e0b;
            color: white;
            padding: 4px 12px;
            border-radius: 30px;
            font-size: 12px;
            display: inline-block;
            margin-left: 10px;
        }
        
        @media (max-width: 768px) {
            .confirmation-card {
                padding: 30px 20px;
            }
            
            h1 {
                font-size: 28px;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .total-amount span:last-child {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>

<div class="confirmation-card">
    <div class="success-icon">
        <i class="fa-solid fa-check"></i>
    </div>
    
    <h1>Order Confirmed! 🎉</h1>
    <div class="order-number">
        <i class="fa-regular fa-clock"></i> 
        <?php echo date('M d, Y - h:i A', strtotime($first_order['Timestamp'] ?? 'now')); ?>
    </div>
    
    <div class="order-details">
        <div class="detail-row">
            <span class="detail-label">Order ID</span>
            <span class="detail-value">#<?php echo $order_ids[0]; ?> <?php if($item_count > 1) echo ' + ' . ($item_count-1) . ' more'; ?></span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Items</span>
            <span class="detail-value"><?php echo $item_count; ?> item<?php echo $item_count > 1 ? 's' : ''; ?></span>
        </div>
        
        <div class="items-list">
            <?php foreach($orders as $order): ?>
            <div class="item-row">
                <span><i class="fa-regular fa-circle-check" style="color: #10b981; margin-right: 8px;"></i> <?php echo htmlspecialchars($order['ItemName']); ?></span>
                <span>Rs. <?php echo number_format($order['Price'], 2); ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <div class="total-amount">
        <span>Total Amount:</span> <span>Rs. <?php echo number_format($total_amount, 2); ?></span>
    </div>
    
    <div class="action-buttons">
        <a href="cafe_menu.php?id=<?php echo $first_order ? 1 : 0; ?>" class="btn btn-secondary">
            <i class="fa-solid fa-utensils"></i> Order More
        </a>
        <a href="my_orders.php" class="btn btn-primary">
            <i class="fa-solid fa-list"></i> View My Orders
        </a>
    </div>
</div>

</body>
</html>