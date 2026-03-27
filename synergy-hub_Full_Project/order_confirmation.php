<?php
require_once 'config.php';
require_once 'functions.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$order_ids = isset($_GET['order_id']) ? explode(',', $_GET['order_id']) : [];

if (empty($order_ids)) {
    header("Location: cafe_menu.php");
    exit();
}




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
            display: flex;
            align-items: center;
            justify-content: center;
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
        
        .confirmation-card {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 30px;
            padding: 50px;
            max-width: 600px;
            width: 90%;
            text-align: center;
            border: 1px solid rgba(255,255,255,0.2);
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
            color: white;
            font-size: 36px;
            margin-bottom: 10px;
        }
        
        .order-number {
            color: #22d3ee;
            font-size: 18px;
            margin-bottom: 30px;
            background: rgba(34, 211, 238, 0.1);
            padding: 10px 20px;
            border-radius: 50px;
            display: inline-block;
        }
        
        .order-details {
            background: rgba(0,0,0,0.2);
            border-radius: 20px;
            padding: 25px;
            margin: 30px 0;
            text-align: left;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            color: rgba(255,255,255,0.9);
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            color: rgba(255,255,255,0.6);
        }
        
        .detail-value {
            font-weight: 600;
            color: #22d3ee;
        }
        
        .items-list {
            margin-top: 15px;
            max-height: 200px;
            overflow-y: auto;
        }
        
        .item-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            color: rgba(255,255,255,0.8);
            font-size: 14px;
        }
        
        .total-amount {
            font-size: 24px;
            color: white;
            margin: 20px 0;
        }
        
        .total-amount span {
            color: #22d3ee;
            font-weight: 700;
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        
        .btn {
            flex: 1;
            padding: 15px;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: rgba(255,255,255,0.1);
            color: white;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .btn-secondary:hover {
            background: rgba(255,255,255,0.2);
        }
        
        .btn i {
            font-size: 16px;
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
        }
    </style>
</head>
<body>

<div class="bg"></div>



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
                <span><?php echo htmlspecialchars($order['ItemName']); ?></span>
                <span>Rs. <?php echo number_format($order['Price'], 2); ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    

    
    <div class="total-amount">
        Total: <span>Rs. <?php echo number_format($total_amount, 2); ?></span>
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