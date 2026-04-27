<?php
require_once 'middleware.php';
require_once 'config.php';
checkAdminAuth();

$admin = getAdminInfo($conn);
$counts = getDashboardCounts($conn);

// Handle search
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_results = null;

if (!empty($search_term)) {
    // Search for users by name, student ID, or email
    $search_sql = "SELECT * FROM Users WHERE Name LIKE ? OR StudentID LIKE ? OR Email LIKE ? LIMIT 5";
    $stmt = mysqli_prepare($conn, $search_sql);
    $search_param = "%$search_term%";
    mysqli_stmt_bind_param($stmt, "sss", $search_param, $search_param, $search_param);
    mysqli_stmt_execute($stmt);
    $search_results = mysqli_stmt_get_result($stmt);
}

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
    <style>
        /* Search Bar Styles */
        .dashboard-search {
            background: white;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
        }
        
        .search-container {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .search-input-wrapper {
            flex: 1;
            position: relative;
        }
        
        .search-input-wrapper i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
        }
        
        .search-input {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s;
            background: #f8fafc;
        }
        
        .search-input:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .search-btn {
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .search-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        
        .clear-btn {
            padding: 12px 20px;
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }
        
        .clear-btn:hover {
            background: #e2e8f0;
        }
        
        .search-results-info {
            margin-top: 15px;
            padding: 10px;
            background: #f1f5f9;
            border-radius: 8px;
            color: #475569;
            font-size: 13px;
        }
        
        .highlight {
            background-color: #fef3c7;
            padding: 2px 4px;
            border-radius: 4px;
            color: #92400e;
            font-weight: bold;
        }
        
        .search-user-card {
            background: white;
            border-radius: 12px;
            margin-top: 15px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
        }
        
        .search-user-header {
            background: #f8fafc;
            padding: 15px 20px;
            border-bottom: 1px solid #e2e8f0;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .search-user-header:hover {
            background: #f1f5f9;
        }
        
        .search-user-details {
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .search-user-name {
            font-weight: 600;
            color: #1e293b;
            font-size: 16px;
        }
        
        .search-user-id {
            font-size: 12px;
            color: #64748b;
        }
        
        .expand-icon {
            color: #667eea;
            transition: transform 0.3s;
        }
        
        .search-user-body {
            padding: 20px;
            display: none;
            border-top: 1px solid #e2e8f0;
        }
        
        .search-user-body.expanded {
            display: block;
        }
        
        .user-detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 15px;
        }
        
        .user-detail-section {
            background: #f8fafc;
            border-radius: 10px;
            padding: 12px;
        }
        
        .user-detail-section h5 {
            color: #1e293b;
            margin-bottom: 10px;
            font-size: 14px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 5px;
        }
        
        .detail-item {
            padding: 6px 0;
            font-size: 13px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .detail-item:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            color: #64748b;
            font-weight: 500;
        }
        
        .detail-value {
            color: #1e293b;
            font-weight: 600;
            float: right;
        }
        
        .status-badge {
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
        }
        
        .status-active {
            background: #dcfce7;
            color: #16a34a;
        }
        
        .status-expired {
            background: #fee;
            color: #ef4444;
        }
        
        .status-pending {
            background: #fff7ed;
            color: #ea580c;
        }
        
        @media (max-width: 768px) {
            .search-container {
                flex-direction: column;
            }
            
            .search-btn, .clear-btn {
                width: 100%;
                justify-content: center;
            }
            
            .search-user-details {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
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
                
                <!-- Search Bar Section -->
                <div class="dashboard-search">
                    <form method="GET" action="" id="searchForm">
                        <div class="search-container">
                            <div class="search-input-wrapper">
                                <i class="fa-solid fa-search"></i>
                                <input type="text" 
                                       name="search" 
                                       class="search-input" 
                                       placeholder="Search users by name, student ID, or email to see clubs, passes, orders, and more..."
                                       value="<?php echo htmlspecialchars($search_term); ?>">
                            </div>
                            <button type="submit" class="search-btn">
                                <i class="fa-solid fa-magnifying-glass"></i> Search
                            </button>
                            <?php if (!empty($search_term)): ?>
                                <a href="index.php" class="clear-btn">
                                    <i class="fa-solid fa-times"></i> Clear
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                    
                    <?php if (!empty($search_term) && $search_results && mysqli_num_rows($search_results) > 0): ?>
                        <div class="search-results-info">
                            <i class="fa-solid fa-chart-simple"></i> Found <?php echo mysqli_num_rows($search_results); ?> user(s) matching "<strong><?php echo htmlspecialchars($search_term); ?></strong>"
                        </div>
                        
                        <?php while($user = mysqli_fetch_assoc($search_results)): 
                            $user_id = $user['UserID'];
                            
                            // Get club memberships
                            $clubs_sql = "SELECT c.Name, cm.Role, cm.JoinDate 
                                         FROM ClubMemberships cm 
                                         JOIN Clubs c ON cm.ClubID = c.ClubID 
                                         WHERE cm.UserID = ? AND cm.Status = 'Active'";
                            $stmt = mysqli_prepare($conn, $clubs_sql);
                            mysqli_stmt_bind_param($stmt, "i", $user_id);
                            mysqli_stmt_execute($stmt);
                            $clubs = mysqli_stmt_get_result($stmt);
                            
                            // Get transport passes
                            $passes_sql = "SELECT * FROM TransportPasses WHERE UserID = ? ORDER BY ValidUntil DESC";
                            $stmt = mysqli_prepare($conn, $passes_sql);
                            mysqli_stmt_bind_param($stmt, "i", $user_id);
                            mysqli_stmt_execute($stmt);
                            $passes = mysqli_stmt_get_result($stmt);
                            
                            // Get orders
                            $orders_sql = "SELECT ItemName, Quantity, Price, Status, Timestamp 
                                          FROM Orders WHERE UserID = ? 
                                          ORDER BY Timestamp DESC LIMIT 3";
                            $stmt = mysqli_prepare($conn, $orders_sql);
                            mysqli_stmt_bind_param($stmt, "i", $user_id);
                            mysqli_stmt_execute($stmt);
                            $orders = mysqli_stmt_get_result($stmt);
                            
                            $highlighted_name = htmlspecialchars($user['Name']);
                            $highlighted_student_id = htmlspecialchars($user['StudentID']);
                            $highlighted_email = htmlspecialchars($user['Email']);
                            
                            if (!empty($search_term)) {
                                $highlighted_name = str_ireplace($search_term, "<span class='highlight'>$search_term</span>", $highlighted_name);
                                $highlighted_student_id = str_ireplace($search_term, "<span class='highlight'>$search_term</span>", $highlighted_student_id);
                                $highlighted_email = str_ireplace($search_term, "<span class='highlight'>$search_term</span>", $highlighted_email);
                            }
                        ?>
                        
                        <div class="search-user-card">
                            <div class="search-user-header" onclick="toggleUserDetails(this)">
                                <div class="search-user-details">
                                    <div>
                                        <div class="search-user-name"><?php echo $highlighted_name; ?></div>
                                        <div class="search-user-id">
                                            <i class="fa-regular fa-id-card"></i> <?php echo $highlighted_student_id; ?> | 
                                            <i class="fa-regular fa-envelope"></i> <?php echo $highlighted_email; ?>
                                        </div>
                                    </div>
                                    <div>
                                        <span class="status-badge status-<?php echo strtolower($user['MembershipStatus']); ?>">
                                            <?php echo $user['MembershipStatus']; ?>
                                        </span>
                                        <span class="status-badge" style="background: #e0f2fe; color: #0284c7;">
                                            <?php echo $user['Role']; ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="expand-icon">
                                    <i class="fa-solid fa-chevron-down"></i>
                                </div>
                            </div>
                            <div class="search-user-body">
                                <div class="user-detail-grid">
                                    <!-- Basic Info -->
                                    <div class="user-detail-section">
                                        <h5><i class="fa-solid fa-user"></i> Basic Info</h5>
                                        <div class="detail-item">
                                            <span class="detail-label">Points Balance:</span>
                                            <span class="detail-value"><?php echo number_format($user['PointsBalance']); ?> pts</span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label">Joined:</span>
                                            <span class="detail-value"><?php echo date('M d, Y', strtotime($user['CreatedAt'])); ?></span>
                                        </div>
                                    </div>
                                    
                                    <!-- Club Memberships -->
                                    <div class="user-detail-section">
                                        <h5><i class="fa-solid fa-users"></i> Clubs (<?php echo mysqli_num_rows($clubs); ?>)</h5>
                                        <?php if(mysqli_num_rows($clubs) > 0): ?>
                                            <?php while($club = mysqli_fetch_assoc($clubs)): ?>
                                                <div class="detail-item">
                                                    <span class="detail-label"><?php echo htmlspecialchars($club['Name']); ?>:</span>
                                                    <span class="detail-value"><?php echo $club['Role']; ?></span>
                                                </div>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <div class="detail-item">No club memberships</div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Transport Passes -->
                                    <div class="user-detail-section">
                                        <h5><i class="fa-solid fa-bus"></i> Transport Passes</h5>
                                        <?php if(mysqli_num_rows($passes) > 0): ?>
                                            <?php while($pass = mysqli_fetch_assoc($passes)): ?>
                                                <div class="detail-item">
                                                    <span class="detail-label"><?php echo htmlspecialchars($pass['RouteName']); ?>:</span>
                                                    <span class="detail-value">
                                                        <?php echo date('M d, Y', strtotime($pass['ValidUntil'])); ?>
                                                        <span class="status-badge status-<?php echo strtolower($pass['Status']); ?>" style="margin-left: 5px;">
                                                            <?php echo $pass['Status']; ?>
                                                        </span>
                                                    </span>
                                                </div>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <div class="detail-item">No transport passes</div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Recent Orders -->
                                    <div class="user-detail-section">
                                        <h5><i class="fa-solid fa-cart-shopping"></i> Recent Orders</h5>
                                        <?php if(mysqli_num_rows($orders) > 0): ?>
                                            <?php while($order = mysqli_fetch_assoc($orders)): ?>
                                                <div class="detail-item">
                                                    <span class="detail-label"><?php echo htmlspecialchars($order['ItemName']); ?> (x<?php echo $order['Quantity']; ?>):</span>
                                                    <span class="detail-value">
                                                        LKR <?php echo number_format($order['Price'] * $order['Quantity']); ?>
                                                        <span class="status-badge status-<?php echo strtolower($order['Status']); ?>" style="margin-left: 5px;">
                                                            <?php echo $order['Status']; ?>
                                                        </span>
                                                    </span>
                                                </div>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <div class="detail-item">No orders yet</div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <?php endwhile; ?>
                        
                    <?php elseif (!empty($search_term)): ?>
                        <div class="search-results-info" style="background: #fee; color: #991b1b;">
                            <i class="fa-solid fa-exclamation-circle"></i> No users found matching "<strong><?php echo htmlspecialchars($search_term); ?></strong>"
                        </div>
                    <?php endif; ?>
                </div>
                
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
                            <a href="activity_logs.php" class="view-all">View All</a>
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
    
    // Toggle user details expansion
    function toggleUserDetails(element) {
        const body = element.nextElementSibling;
        const icon = element.querySelector('.expand-icon i');
        
        body.classList.toggle('expanded');
        
        if (body.classList.contains('expanded')) {
            icon.classList.remove('fa-chevron-down');
            icon.classList.add('fa-chevron-up');
        } else {
            icon.classList.remove('fa-chevron-up');
            icon.classList.add('fa-chevron-down');
        }
    }
    
    // Auto-submit on Enter key
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.querySelector('.search-input');
        if (searchInput) {
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    document.getElementById('searchForm').submit();
                }
            });
        }
    });
    </script>
</body>
</html>