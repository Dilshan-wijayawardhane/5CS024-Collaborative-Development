<?php
require_once 'middleware.php';
require_once 'config.php';
checkAdminAuth();

$admin = getAdminInfo($conn);
$counts = getDashboardCounts($conn);

// Get recent activity from ActivityLogs
$activity_result = false;
$check_activity = mysqli_query($conn, "SHOW TABLES LIKE 'ActivityLogs'");
if (mysqli_num_rows($check_activity) > 0) {
    $activity_sql = "SELECT al.*, u.Name as UserName 
                     FROM ActivityLogs al
                     JOIN Users u ON al.UserID = u.UserID
                     ORDER BY al.Timestamp DESC 
                     LIMIT 10";
    $activity_result = mysqli_query($conn, $activity_sql);
}

// Get recent orders
$orders_result = false;
$check_orders = mysqli_query($conn, "SHOW TABLES LIKE 'Orders'");
if (mysqli_num_rows($check_orders) > 0) {
    $orders_sql = "SELECT o.*, u.Name as UserName 
                   FROM Orders o
                   JOIN Users u ON o.UserID = u.UserID
                   ORDER BY o.Timestamp DESC 
                   LIMIT 5";
    $orders_result = mysqli_query($conn, $orders_sql);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Synergy Hub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="admin.css">
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Top Bar -->
            <?php include 'includes/topbar.php'; ?>
            
            <!-- Dashboard Content -->
            <div class="content">
                <h1 class="page-title">
                    <i class="fa-solid fa-gauge-high"></i> Dashboard
                </h1>
                
                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon blue">
                            <i class="fa-solid fa-users"></i>
                        </div>
                        <div class="stat-info">
                            <span class="stat-value"><?php echo $counts['total_users']; ?></span>
                            <span class="stat-label">Total Users</span>
                        </div>
                        <div class="stat-change positive">
                            <i class="fa-solid fa-arrow-up"></i> <?php echo $counts['active_today']; ?> active today
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon green">
                            <i class="fa-solid fa-building"></i>
                        </div>
                        <div class="stat-info">
                            <span class="stat-value"><?php echo $counts['total_facilities']; ?></span>
                            <span class="stat-label">Facilities</span>
                        </div>
                        <div class="stat-change">
                            <i class="fa-solid fa-circle-check"></i> <?php echo $counts['open_facilities']; ?> open now
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon purple">
                            <i class="fa-solid fa-calendar"></i>
                        </div>
                        <div class="stat-info">
                            <span class="stat-value"><?php echo $counts['total_events']; ?></span>
                            <span class="stat-label">Events</span>
                        </div>
                        <div class="stat-change">
                            <i class="fa-solid fa-clock"></i> <?php echo $counts['upcoming_events']; ?> upcoming
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon orange">
                            <i class="fa-solid fa-cart-shopping"></i>
                        </div>
                        <div class="stat-info">
                            <span class="stat-value"><?php echo $counts['orders_today']; ?></span>
                            <span class="stat-label">Orders Today</span>
                        </div>
                        <div class="stat-change">
                            <i class="fa-solid fa-hourglass-half"></i> <?php echo $counts['pending_orders']; ?> pending
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon yellow">
                            <i class="fa-solid fa-star"></i>
                        </div>
                        <div class="stat-info">
                            <span class="stat-value"><?php echo number_format($counts['total_points']); ?></span>
                            <span class="stat-label">Total Points</span>
                        </div>
                        <div class="stat-change">
                            <i class="fa-solid fa-gift"></i> System-wide
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="quick-actions">
                    <h2 class="section-title">
                        <i class="fa-solid fa-bolt"></i> Quick Actions
                    </h2>
                    <div class="actions-grid">
                        <a href="users.php" class="action-card">
                            <i class="fa-solid fa-user-plus"></i>
                            <span>Manage Users</span>
                        </a>
                        <a href="points.php" class="action-card">
                            <i class="fa-solid fa-star"></i>
                            <span>Points System</span>
                        </a>
                        <a href="../facility_management.php" target="_blank" class="action-card">
                            <i class="fa-solid fa-building"></i>
                            <span>View Facilities</span>
                        </a>
                        <a href="../cafe_menu.php?id=1" target="_blank" class="action-card">
                            <i class="fa-solid fa-mug-saucer"></i>
                            <span>Café Menu</span>
                        </a>
                        <a href="../library_books.php?id=2" target="_blank" class="action-card">
                            <i class="fa-solid fa-book"></i>
                            <span>Library</span>
                        </a>
                        <a href="../transport.php" target="_blank" class="action-card">
                            <i class="fa-solid fa-bus"></i>
                            <span>Transport</span>
                        </a>
                        <!-- Add these after existing action cards -->
                        <a href="facility_management.php" class="action-card">
                            <i class="fa-solid fa-building"></i>
                            <span>Manage Facilities</span>
                        </a>

                        <a href="cafe_menu_admin.php" class="action-card">
                            <i class="fa-solid fa-mug-saucer"></i>
                            <span>Café Menu</span>
                        </a>

                        <a href="library_management.php" class="action-card">
                            <i class="fa-solid fa-book"></i>
                            <span>Library</span>
                        </a>

                        <a href="pool_management.php" class="action-card">
                            <i class="fa-solid fa-person-swimming"></i>
                            <span>Pool Management</span>
                        </a>
                    </div>
                </div>
                
                <!-- Recent Activity & Orders -->
                <div class="dashboard-grid">
                    <!-- Recent Activity -->
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3><i class="fa-solid fa-clock-rotate-left"></i> Recent Activity</h3>
                            <a href="#" class="view-all" onclick="alert('Activity log feature coming soon!')">View All</a>
                        </div>
                        <div class="activity-list">
                            <?php if($activity_result && mysqli_num_rows($activity_result) > 0): ?>
                                <?php while($activity = mysqli_fetch_assoc($activity_result)): ?>
                                <div class="activity-item">
                                    <div class="activity-icon">
                                        <?php
                                        $icon = 'fa-circle-info';
                                        if($activity['Action'] == 'LOGIN') $icon = 'fa-right-to-bracket';
                                        else if($activity['Action'] == 'ORDER') $icon = 'fa-cart-shopping';
                                        else if($activity['Action'] == 'CHECKIN') $icon = 'fa-location-dot';
                                        else if($activity['Action'] == 'BOOK_FIELD') $icon = 'fa-futbol';
                                        else if($activity['Action'] == 'REGISTER') $icon = 'fa-user-plus';
                                        else if($activity['Action'] == 'ADMIN_LOGIN') $icon = 'fa-user-tie';
                                        ?>
                                        <i class="fa-solid <?php echo $icon; ?>"></i>
                                    </div>
                                    <div class="activity-details">
                                        <div class="activity-user"><?php echo htmlspecialchars($activity['UserName']); ?></div>
                                        <div class="activity-action"><?php echo $activity['Action']; ?></div>
                                        <div class="activity-time"><?php echo date('h:i A', strtotime($activity['Timestamp'])); ?></div>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div style="text-align: center; padding: 30px; color: #64748b;">
                                    <i class="fa-regular fa-clock" style="font-size: 40px; margin-bottom: 10px;"></i>
                                    <p>No recent activity</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Recent Orders -->
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3><i class="fa-solid fa-cart-shopping"></i> Recent Orders</h3>
                            <a href="../my_orders.php" target="_blank" class="view-all">View All</a>
                        </div>
                        <div class="orders-list">
                            <?php if($orders_result && mysqli_num_rows($orders_result) > 0): ?>
                                <?php while($order = mysqli_fetch_assoc($orders_result)): ?>
                                <div class="order-item">
                                    <div class="order-info">
                                        <div class="order-user"><?php echo htmlspecialchars($order['UserName']); ?></div>
                                        <div class="order-desc"><?php echo htmlspecialchars($order['ItemName']); ?> (x<?php echo $order['Quantity']; ?>)</div>
                                    </div>
                                    <div class="order-status status-<?php echo strtolower($order['Status']); ?>">
                                        <?php echo $order['Status']; ?>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div style="text-align: center; padding: 30px; color: #64748b;">
                                    <i class="fa-regular fa-receipt" style="font-size: 40px; margin-bottom: 10px;"></i>
                                    <p>No recent orders</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    // Simple toggle for mobile sidebar
    function toggleSidebar() {
        document.querySelector('.sidebar').classList.toggle('show');
    }
    </script>
</body>
</html>