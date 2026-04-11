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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Synergy Hub</title>
    <link rel="stylesheet" href="style.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
        }
        
        body {
            min-height: 100vh;
            position: relative;
        }
        
        .bg {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: -1;
        }
        
        .bg::before {
            content: "";
            position: absolute;
            inset: 0;
            background-image: url("campus.jpg");
            background-size: cover;
            background-position: center;
            filter: blur(4px) brightness(0.65);
            transform: scale(1.05);
            pointer-events: none;
        }
        
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 40px;
            background: rgba(0,0,0,0.2);
            backdrop-filter: blur(10px);
        }
        
        .logo {
            font-size: 24px;
            font-weight: 700;
            color: white;
        }
        
        .logo span {
            color: #22d3ee;
        }
        
        .points {
            display: flex;
            align-items: center;
            gap: 8px;
            background: rgba(255,255,255,0.1);
            padding: 8px 20px;
            border-radius: 30px;
            color: white;
        }
        
        .container {
            max-width: 1000px;
            margin: 30px auto;
            padding: 20px;
        }
        
        .page-title {
            color: white;
            font-size: 32px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .date-group {
            margin-bottom: 30px;
        }
        
        .date-header {
            color: #22d3ee;
            font-size: 18px;
            margin-bottom: 15px;
            padding-bottom: 5px;
            border-bottom: 2px solid rgba(34, 211, 238, 0.3);
        }
        
        .order-card {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 15px;
            border: 1px solid rgba(255,255,255,0.1);
            transition: all 0.3s;
        }
        
        .order-card:hover {
            background: rgba(255,255,255,0.15);
            transform: translateX(5px);
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .order-id {
            color: white;
            font-weight: 600;
        }
        
        .order-time {
            color: rgba(255,255,255,0.6);
            font-size: 14px;
        }
        
        .order-details {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .order-item {
            color: rgba(255,255,255,0.9);
        }
        
        .order-price {
            color: #22d3ee;
            font-weight: 600;
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-Pending {
            background: #f59e0b;
            color: white;
        }
        
        .status-Preparing {
            background: #3b82f6;
            color: white;
        }
        
        .status-Ready {
            background: #10b981;
            color: white;
        }
        
        .status-Completed {
            background: #6b7280;
            color: white;
        }
        
        .status-Cancelled {
            background: #ef4444;
            color: white;
        }
        
        .no-orders {
            text-align: center;
            padding: 60px;
            color: rgba(255,255,255,0.6);
        }
        
        .no-orders i {
            font-size: 60px;
            margin-bottom: 20px;
            color: rgba(255,255,255,0.3);
        }
        
        .back-btn {
            display: inline-block;
            margin-top: 30px;
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            background: rgba(255,255,255,0.1);
            border-radius: 30px;
            transition: all 0.3s;
        }
        
        .back-btn:hover {
            background: rgba(255,255,255,0.2);
            color: #22d3ee;
        }
    </style>
</head>
<body>

<div class="bg"></div>

<nav class="navbar">
    <div class="logo">Synergy <span>Hub</span></div>
    <div class="points">
        <i class="fa-solid fa-star"></i>
        <?php

        // Get current points for navbar
        $points_sql = "SELECT PointsBalance FROM Users WHERE UserID = ?";
        $points_stmt = mysqli_prepare($conn, $points_sql);
        mysqli_stmt_bind_param($points_stmt, "i", $user_id);
        mysqli_stmt_execute($points_stmt);
        $points_result = mysqli_stmt_get_result($points_stmt);
        $user_points = mysqli_fetch_assoc($points_result);
        echo $user_points['PointsBalance'];
        ?>
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
        <a href="cafe_menu.php?id=1" class="back-btn" style="margin-top: 20px;">
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
                    <span class="order-id">Order #<?php echo $order['OrderID']; ?></span>
                    <span class="order-time">
                        <i class="fa-regular fa-clock"></i> 
                        <?php echo date('h:i A', strtotime($order['Timestamp'])); ?>
                    </span>
                </div>
                <div class="order-details">
                    <div class="order-item">
                        <?php echo htmlspecialchars($order['ItemName']); ?>
                        <span style="color: rgba(255,255,255,0.5); margin-left: 10px;">
                            x<?php echo $order['Quantity']; ?>
                        </span>
                    </div>
                    <div class="order-price">
                        Rs. <?php echo number_format($order['Price'] * $order['Quantity'], 2); ?>
                    </div>
                </div>
                <div style="margin-top: 10px;">
                    <span class="status-badge status-<?php echo $order['Status']; ?>">
                        <?php echo $order['Status']; ?>
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