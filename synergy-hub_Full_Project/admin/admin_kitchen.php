<?php
require_once 'config.php';
checkAdminAuth();

$admin = getAdminInfo($conn);

// Get kitchen orders (Pending, Preparing, Ready)
$kitchen_sql = "SELECT o.*, u.Name as CustomerName 
                FROM Orders o 
                JOIN Users u ON o.UserID = u.UserID 
                WHERE o.Status IN ('Pending', 'Preparing', 'Ready')
                ORDER BY FIELD(o.Status, 'Pending', 'Preparing', 'Ready'), o.Timestamp ASC";
$kitchen_result = mysqli_query($conn, $kitchen_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kitchen Display - Synergy Hub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="admin.css">
    <style>
        body {
            background: #0f172a;
            padding: 20px;
        }
        
        .kitchen-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            color: white;
        }
        
        .kitchen-title {
            font-size: 28px;
            font-weight: 700;
        }
        
        .kitchen-title span {
            color: #22d3ee;
        }
        
        .refresh-indicator {
            background: rgba(255,255,255,0.1);
            padding: 8px 16px;
            border-radius: 30px;
            font-size: 14px;
        }
        
        .orders-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 20px;
        }
        
        .kitchen-card {
            background: white;
            border-radius: 20px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        
        .kitchen-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
        }
        
        .kitchen-card.pending::before { background: #f59e0b; }
        .kitchen-card.preparing::before { background: #3b82f6; }
        .kitchen-card.ready::before { background: #10b981; }
        
        .kitchen-card.urgent {
            border: 2px solid #ef4444;
            animation: pulse 1s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { border-color: #ef4444; }
            50% { border-color: #fca5a5; }
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .order-number {
            font-size: 20px;
            font-weight: 700;
            color: #1e293b;
        }
        
        .timer {
            font-size: 14px;
            font-weight: 600;
            padding: 5px 12px;
            border-radius: 30px;
            background: #f1f5f9;
        }
        
        .timer.urgent { background: #fee; color: #ef4444; }
        
        .item-name {
            font-size: 24px;
            font-weight: 700;
            color: #1e293b;
            margin: 10px 0;
        }
        
        .item-details {
            display: flex;
            justify-content: space-between;
            margin: 15px 0;
            padding: 10px;
            background: #f8fafc;
            border-radius: 12px;
        }
        
        .quantity {
            font-size: 28px;
            font-weight: 700;
            color: #667eea;
        }
        
        .customer {
            color: #64748b;
            font-size: 14px;
        }
        
        .status-badge-large {
            display: inline-block;
            padding: 6px 16px;
            border-radius: 30px;
            font-size: 14px;
            font-weight: 600;
        }
        
        .status-Pending { background: #fff7ed; color: #ea580c; }
        .status-Preparing { background: #e0f2fe; color: #0284c7; }
        .status-Ready { background: #dcfce7; color: #16a34a; }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .action-buttons button {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-start { background: #3b82f6; color: white; }
        .btn-ready { background: #10b981; color: white; }
        .btn-complete { background: #8b5cf6; color: white; }
        
        .action-buttons button:hover { transform: scale(1.02); opacity: 0.9; }
        
        .sound-toggle {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: white;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            z-index: 100;
        }
        
        .no-orders {
            text-align: center;
            padding: 60px;
            color: #64748b;
            background: white;
            border-radius: 20px;
        }
        
        @media (max-width: 768px) {
            .orders-grid { grid-template-columns: 1fr; }
            .item-name { font-size: 20px; }
        }
    </style>
</head>
<body>
    <div class="kitchen-header">
        <div class="kitchen-title">
            🍽️ Kitchen <span>Display</span>
        </div>
        <div class="refresh-indicator">
            <i class="fa-solid fa-sync-alt fa-fw"></i>
            <span id="refreshStatus">Auto-refreshing in 15s</span>
        </div>
    </div>
    
    <div class="orders-grid" id="kitchenOrders">
        <?php if(mysqli_num_rows($kitchen_result) > 0): ?>
            <?php while($order = mysqli_fetch_assoc($kitchen_result)):
                $time_elapsed = time() - strtotime($order['Timestamp']);
                $minutes_elapsed = floor($time_elapsed / 60);
                $is_urgent = ($order['Status'] == 'Pending' && $minutes_elapsed > 10);
            ?>
            <div class="kitchen-card <?php echo strtolower($order['Status']); ?> <?php echo $is_urgent ? 'urgent' : ''; ?>" data-order-id="<?php echo $order['OrderID']; ?>">
                <div class="card-header">
                    <span class="order-number">Order #<?php echo $order['OrderID']; ?></span>
                    <span class="timer <?php echo $is_urgent ? 'urgent' : ''; ?>">
                        <i class="fa-regular fa-clock"></i> <?php echo $minutes_elapsed; ?> min ago
                    </span>
                </div>
                
                <div class="item-name"><?php echo htmlspecialchars($order['ItemName']); ?></div>
                
                <div class="item-details">
                    <div>
                        <div class="quantity">x<?php echo $order['Quantity']; ?></div>
                        <div class="customer"><?php echo htmlspecialchars($order['CustomerName']); ?></div>
                    </div>
                    <div>
                        <span class="status-badge-large status-<?php echo $order['Status']; ?>">
                            <?php echo $order['Status']; ?>
                        </span>
                    </div>
                </div>
                
                <div class="action-buttons">
                    <?php if($order['Status'] == 'Pending'): ?>
                        <button class="btn-start" onclick="updateStatus(<?php echo $order['OrderID']; ?>, 'Preparing')">
                            <i class="fa-solid fa-play"></i> Start Preparing
                        </button>
                    <?php elseif($order['Status'] == 'Preparing'): ?>
                        <button class="btn-ready" onclick="updateStatus(<?php echo $order['OrderID']; ?>, 'Ready')">
                            <i class="fa-solid fa-check"></i> Mark Ready
                        </button>
                    <?php elseif($order['Status'] == 'Ready'): ?>
                        <button class="btn-complete" onclick="updateStatus(<?php echo $order['OrderID']; ?>, 'Completed')">
                            <i class="fa-solid fa-circle-check"></i> Complete Order
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="no-orders">
                <i class="fa-regular fa-bell-slash" style="font-size: 48px; margin-bottom: 20px;"></i>
                <h3>No active orders</h3>
                <p>Kitchen is clear! New orders will appear here.</p>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="sound-toggle" onclick="toggleSound()">
        <i class="fa-solid fa-volume-up" id="soundIcon"></i>
    </div>
    
    <audio id="notificationSound" src="https://www.soundjay.com/misc/sounds/bell-ringing-05.mp3" preload="auto"></audio>
    
    <script>
        let lastOrderCount = <?php echo mysqli_num_rows($kitchen_result); ?>;
        let soundEnabled = localStorage.getItem('kitchenSound') !== 'false';
        
        function updateSoundIcon() {
            const icon = document.getElementById('soundIcon');
            if(icon) {
                icon.className = soundEnabled ? 'fa-solid fa-volume-up' : 'fa-solid fa-volume-mute';
            }
        }
        
        function toggleSound() {
            soundEnabled = !soundEnabled;
            localStorage.setItem('kitchenSound', soundEnabled);
            updateSoundIcon();
        }
        
        function playNotification() {
            if(soundEnabled) {
                const audio = document.getElementById('notificationSound');
                audio.play().catch(e => console.log('Audio play failed:', e));
            }
            
            // Browser notification
            if(Notification.permission === 'granted') {
                new Notification('New Order Arrived!', {
                    body: 'A new order has been placed.',
                    icon: '/favicon.ico'
                });
            } else if(Notification.permission !== 'denied') {
                Notification.requestPermission();
            }
        }
        
        function updateStatus(orderId, newStatus) {
            fetch('update_order_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `order_id=${orderId}&status=${newStatus}`
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            });
        }
        
        function loadKitchenOrders() {
            fetch('get_kitchen_orders.php')
                .then(response => response.json())
                .then(data => {
                    if(data.orders.length !== lastOrderCount && data.orders.length > lastOrderCount) {
                        playNotification();
                    }
                    lastOrderCount = data.orders.length;
                    
                    let html = '';
                    if(data.orders.length === 0) {
                        html = `<div class="no-orders">
                                    <i class="fa-regular fa-bell-slash" style="font-size: 48px; margin-bottom: 20px;"></i>
                                    <h3>No active orders</h3>
                                    <p>Kitchen is clear! New orders will appear here.</p>
                                </div>`;
                    } else {
                        data.orders.forEach(order => {
                            const minutesElapsed = Math.floor(order.time_elapsed / 60);
                            const isUrgent = order.Status === 'Pending' && minutesElapsed > 10;
                            
                            html += `
                                <div class="kitchen-card ${order.Status.toLowerCase()} ${isUrgent ? 'urgent' : ''}" data-order-id="${order.OrderID}">
                                    <div class="card-header">
                                        <span class="order-number">Order #${order.OrderID}</span>
                                        <span class="timer ${isUrgent ? 'urgent' : ''}">
                                            <i class="fa-regular fa-clock"></i> ${minutesElapsed} min ago
                                        </span>
                                    </div>
                                    <div class="item-name">${escapeHtml(order.ItemName)}</div>
                                    <div class="item-details">
                                        <div>
                                            <div class="quantity">x${order.Quantity}</div>
                                            <div class="customer">${escapeHtml(order.CustomerName)}</div>
                                        </div>
                                        <div>
                                            <span class="status-badge-large status-${order.Status}">${order.Status}</span>
                                        </div>
                                    </div>
                                    <div class="action-buttons">
                                        ${order.Status === 'Pending' ? 
                                            `<button class="btn-start" onclick="updateStatus(${order.OrderID}, 'Preparing')"><i class="fa-solid fa-play"></i> Start Preparing</button>` :
                                        order.Status === 'Preparing' ?
                                            `<button class="btn-ready" onclick="updateStatus(${order.OrderID}, 'Ready')"><i class="fa-solid fa-check"></i> Mark Ready</button>` :
                                        order.Status === 'Ready' ?
                                            `<button class="btn-complete" onclick="updateStatus(${order.OrderID}, 'Completed')"><i class="fa-solid fa-circle-check"></i> Complete Order</button>` : ''
                                        }
                                    </div>
                                </div>
                            `;
                        });
                    }
                    document.getElementById('kitchenOrders').innerHTML = html;
                    
                    let countdown = 15;
                    const refreshStatus = document.getElementById('refreshStatus');
                    const interval = setInterval(() => {
                        countdown--;
                        if(refreshStatus) refreshStatus.textContent = `Auto-refreshing in ${countdown}s`;
                        if(countdown <= 0) {
                            clearInterval(interval);
                            loadKitchenOrders();
                        }
                    }, 1000);
                });
        }
        
        function escapeHtml(text) {
            if(!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        updateSoundIcon();
        setInterval(loadKitchenOrders, 15000);
    </script>
</body>
</html>