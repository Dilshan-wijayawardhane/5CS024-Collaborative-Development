<?php

/**
 * Displays user's cafe order history.
 * 
 * This page shows all past orders grouped by date, with details like item name, quantity, price, and order status.
 * 
 * Security Notes:
 *  - Requires login
 *  - Uses prepared statements for user-specific sections
 *  - Status badges are styled based on order status for better UX
 */

require_once 'config.php';
require_once 'functions.php';

// Authentication check
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user's orders, newest first
$orders_sql = "SELECT * FROM Orders 
               WHERE UserID = ? 
               ORDER BY Timestamp DESC";
$orders_stmt = mysqli_prepare($conn, $orders_sql);
mysqli_stmt_bind_param($orders_stmt, "i", $user_id);
mysqli_stmt_execute($orders_stmt);
$orders_result = mysqli_stmt_get_result($orders_stmt);

// Group orders by date for better UX
$grouped_orders = [];
while ($order = mysqli_fetch_assoc($orders_result)) {
    $date = date('Y-m-d', strtotime($order['Timestamp']));
    if (!isset($grouped_orders[$date])) {
        $grouped_orders[$date] = [];
    }
    $grouped_orders[$date][] = $order;
}

// Get user points for navbar
$points_sql = "SELECT PointsBalance FROM Users WHERE UserID = ?";
$points_stmt = mysqli_prepare($conn, $points_sql);
mysqli_stmt_bind_param($points_stmt, "i", $user_id);
mysqli_stmt_execute($points_stmt);
$points_result = mysqli_stmt_get_result($points_stmt);
$user_points = mysqli_fetch_assoc($points_result);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Synergy Hub</title>
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
        }
        
        /* NAVBAR - White/Blue theme */
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 32px;
            background: white;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.08);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .logo {
            font-size: 24px;
            font-weight: 700;
            color: #1e4a76;
        }
        
        .logo span {
            color: #2c7da0;
        }
        
        .points {
            display: flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, #1e4a76 0%, #2c7da0 100%);
            padding: 8px 20px;
            border-radius: 30px;
            color: white;
            font-weight: 600;
        }
        
        .points i {
            color: #fbbf24;
        }
        
        .container {
            max-width: 1000px;
            margin: 30px auto;
            padding: 20px;
        }
        
        .page-title {
            color: #1e4a76;
            font-size: 32px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .page-title i {
            color: #2c7da0;
        }
        
        .date-group {
            margin-bottom: 30px;
        }
        
        .date-header {
            color: #2c7da0;
            font-size: 18px;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid #e0f2fe;
            font-weight: 600;
        }
        
        .date-header i {
            margin-right: 8px;
        }
        
        .order-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 15px;
            border: 1px solid #e2e8f0;
            transition: all 0.3s;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
        
        .order-card:hover {
            background: #fafcff;
            transform: translateX(5px);
            border-color: #2c7da0;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .order-id {
            color: #1e4a76;
            font-weight: 700;
            font-size: 15px;
        }
        
        .order-time {
            color: #64748b;
            font-size: 13px;
        }
        
        .order-time i {
            margin-right: 4px;
        }
        
        .order-details {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }
        
        .order-item {
            color: #1e293b;
            font-weight: 500;
        }
        
        .order-item span {
            color: #64748b;
            font-weight: normal;
            margin-left: 8px;
        }
        
        .order-price {
            color: #2c7da0;
            font-weight: 700;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 30px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-Pending {
            background: #fef3c7;
            color: #d97706;
        }
        
        .status-Preparing {
            background: #dbeafe;
            color: #2563eb;
        }
        
        .status-Ready {
            background: #d1fae5;
            color: #059669;
        }
        
        .status-Completed {
            background: #f1f5f9;
            color: #475569;
        }
        
        .status-Cancelled {
            background: #fee2e2;
            color: #dc2626;
        }
        
        .no-orders {
            text-align: center;
            padding: 60px;
            background: white;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
        }
        
        .no-orders i {
            font-size: 60px;
            margin-bottom: 20px;
            color: #cbd5e1;
        }
        
        .no-orders h3 {
            color: #1e4a76;
            margin-bottom: 10px;
        }
        
        .no-orders p {
            color: #64748b;
            margin-bottom: 20px;
        }
        
        .back-btn {
            display: inline-block;
            margin-top: 30px;
            color: #1e4a76;
            text-decoration: none;
            padding: 12px 25px;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 30px;
            transition: all 0.3s;
            font-weight: 500;
        }
        
        .back-btn:hover {
            background: #1e4a76;
            color: white;
            border-color: #1e4a76;
        }
        
        .back-btn i {
            margin-right: 8px;
        }
        
        @media (max-width: 768px) {
            .navbar {
                padding: 12px 20px;
            }
            
            .logo {
                font-size: 18px;
            }
            
            .points {
                padding: 6px 15px;
                font-size: 14px;
            }
            
            .container {
                padding: 15px;
            }
            
            .page-title {
                font-size: 24px;
            }
            
            .order-card {
                padding: 15px;
            }
            
            .order-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 5px;
            }
        }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="logo">Synergy <span>Hub</span></div>
    <div class="points">
        <i class="fa-solid fa-star"></i>
        <?php echo number_format($user_points['PointsBalance'] ?? 0); ?>
    </div>
</nav>

<div class="container">
    <h1 class="page-title">
        <i class="fa-solid fa-receipt"></i> My Orders
    </h1>
    
    <?php if (empty($grouped_orders)): ?>
    <div class="no-orders">
        <i class="fa-regular fa-face-frown"></i>
        <h3>No orders yet</h3>
        <p>Start by ordering from our café!</p>
        <a href="cafe_menu.php?id=1" class="back-btn" style="margin-top: 20px; display: inline-block;">
            <i class="fa-solid fa-utensils"></i> Browse Menu
        </a>
    </div>
    <?php else: ?>
        <?php foreach($grouped_orders as $date => $orders): ?>
        <div class="date-group">
            <div class="date-header">
                <i class="fa-regular fa-calendar"></i> 
                <?php echo date('l, F j, Y', strtotime($date)); ?>
            </div>
            
            <?php foreach($orders as $order): ?>
            <div class="order-card">
                <div class="order-header">
                    <span class="order-id"><i class="fa-regular fa-receipt"></i> Order #<?php echo $order['OrderID']; ?></span>
                    <span class="order-time">
                        <i class="fa-regular fa-clock"></i> 
                        <?php echo date('h:i A', strtotime($order['Timestamp'])); ?>
                    </span>
                </div>
                <div class="order-details">
                    <div class="order-item">
                        <?php echo htmlspecialchars($order['ItemName']); ?>
                        <span>x<?php echo $order['Quantity']; ?></span>
                    </div>
                    <div class="order-price">
                        Rs. <?php echo number_format($order['Price'] * $order['Quantity'], 2); ?>
                    </div>
                </div>
                <div>
                    <span class="status-badge status-<?php echo $order['Status']; ?>">
                        <?php 
                        switch($order['Status']) {
                            case 'Pending': echo '⏳ Pending'; break;
                            case 'Preparing': echo '🍳 Preparing'; break;
                            case 'Ready': echo '✅ Ready for Pickup'; break;
                            case 'Completed': echo '✓ Completed'; break;
                            case 'Cancelled': echo '✗ Cancelled'; break;
                            default: echo $order['Status'];
                        }
                        ?>
                    </span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
        
        <a href="cafe_menu.php?id=1" class="back-btn">
            <i class="fa-solid fa-arrow-left"></i> Back to Menu
        </a>
    <?php endif; ?>
</div>

</body>
</html>