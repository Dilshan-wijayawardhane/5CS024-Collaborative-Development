<?php
require_once 'middleware.php';
require_once 'config.php';
checkAdminAuth();

$admin = getAdminInfo($conn);
$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$user_id) {
    header("Location: users.php");
    exit();
}

// Get user details
$user_sql = "SELECT * FROM Users WHERE UserID = ?";
$user_stmt = mysqli_prepare($conn, $user_sql);
mysqli_stmt_bind_param($user_stmt, "i", $user_id);
mysqli_stmt_execute($user_stmt);
$user_result = mysqli_stmt_get_result($user_stmt);
$user = mysqli_fetch_assoc($user_result);

if (!$user) {
    header("Location: users.php");
    exit();
}

// Get user statistics
$stats = [];

// Total check-ins
$checkins_sql = "SELECT COUNT(*) as count, SUM(PointsAwarded) as total_points FROM CheckIns WHERE UserID = ?";
$checkins_stmt = mysqli_prepare($conn, $checkins_sql);
mysqli_stmt_bind_param($checkins_stmt, "i", $user_id);
mysqli_stmt_execute($checkins_stmt);
$checkins_result = mysqli_stmt_get_result($checkins_stmt);
$stats['checkins'] = mysqli_fetch_assoc($checkins_result);

// Total orders
$orders_sql = "SELECT COUNT(*) as count, SUM(Price * Quantity) as total_spent FROM Orders WHERE UserID = ?";
$orders_stmt = mysqli_prepare($conn, $orders_sql);
mysqli_stmt_bind_param($orders_stmt, "i", $user_id);
mysqli_stmt_execute($orders_stmt);
$orders_result = mysqli_stmt_get_result($orders_stmt);
$stats['orders'] = mysqli_fetch_assoc($orders_result);

// Book borrowings
$books_sql = "SELECT COUNT(*) as count FROM borrowed_books WHERE user_id = ? AND status = 'borrowed'";
$books_stmt = mysqli_prepare($conn, $books_sql);
mysqli_stmt_bind_param($books_stmt, "i", $user_id);
mysqli_stmt_execute($books_stmt);
$books_result = mysqli_stmt_get_result($books_stmt);
$stats['books'] = mysqli_fetch_assoc($books_result);

// Pool bookings
$pool_sql = "SELECT COUNT(*) as count FROM pool_bookings WHERE user_id = ? AND status = 'confirmed' AND booking_date >= CURDATE()";
$pool_stmt = mysqli_prepare($conn, $pool_sql);
mysqli_stmt_bind_param($pool_stmt, "i", $user_id);
mysqli_stmt_execute($pool_stmt);
$pool_result = mysqli_stmt_get_result($pool_stmt);
$stats['pool'] = mysqli_fetch_assoc($pool_result);

// Class bookings
$class_sql = "SELECT COUNT(*) as count FROM class_bookings WHERE user_id = ? AND status = 'booked'";
$class_stmt = mysqli_prepare($conn, $class_sql);
mysqli_stmt_bind_param($class_stmt, "i", $user_id);
mysqli_stmt_execute($class_stmt);
$class_result = mysqli_stmt_get_result($class_stmt);
$stats['classes'] = mysqli_fetch_assoc($class_result);

// Get recent activity
$activity_sql = "SELECT * FROM ActivityLogs WHERE UserID = ? ORDER BY Timestamp DESC LIMIT 20";
$activity_stmt = mysqli_prepare($conn, $activity_sql);
mysqli_stmt_bind_param($activity_stmt, "i", $user_id);
mysqli_stmt_execute($activity_stmt);
$activity_result = mysqli_stmt_get_result($activity_stmt);

// Get recent check-ins
$recent_checkins_sql = "SELECT c.*, f.Name as facility_name, f.Type as facility_type 
                        FROM CheckIns c
                        JOIN Facilities f ON c.FacilityID = f.FacilityID
                        WHERE c.UserID = ?
                        ORDER BY c.Timestamp DESC LIMIT 10";
$recent_checkins_stmt = mysqli_prepare($conn, $recent_checkins_sql);
mysqli_stmt_bind_param($recent_checkins_stmt, "i", $user_id);
mysqli_stmt_execute($recent_checkins_stmt);
$recent_checkins_result = mysqli_stmt_get_result($recent_checkins_stmt);

// Get recent orders
$recent_orders_sql = "SELECT * FROM Orders WHERE UserID = ? ORDER BY Timestamp DESC LIMIT 10";
$recent_orders_stmt = mysqli_prepare($conn, $recent_orders_sql);
mysqli_stmt_bind_param($recent_orders_stmt, "i", $user_id);
mysqli_stmt_execute($recent_orders_stmt);
$recent_orders_result = mysqli_stmt_get_result($recent_orders_stmt);

// Determine membership tier
$tier = 'Bronze';
$tier_color = '#CD7F32';
$tier_icon = 'fa-bronze';
if ($user['PointsBalance'] >= 5000) {
    $tier = 'Platinum';
    $tier_color = '#E5E4E2';
    $tier_icon = 'fa-crown';
} elseif ($user['PointsBalance'] >= 2000) {
    $tier = 'Gold';
    $tier_color = '#FFD700';
    $tier_icon = 'fa-gold';
} elseif ($user['PointsBalance'] >= 500) {
    $tier = 'Silver';
    $tier_color = '#C0C0C0';
    $tier_icon = 'fa-silver';
}

// Get next tier info
$next_tier = '';
$points_needed = 0;
if ($user['PointsBalance'] < 500) {
    $next_tier = 'Silver';
    $points_needed = 500 - $user['PointsBalance'];
} elseif ($user['PointsBalance'] < 2000) {
    $next_tier = 'Gold';
    $points_needed = 2000 - $user['PointsBalance'];
} elseif ($user['PointsBalance'] < 5000) {
    $next_tier = 'Platinum';
    $points_needed = 5000 - $user['PointsBalance'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile - <?php echo htmlspecialchars($user['Name']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="admin.css">
    <style>
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            color: white;
            position: relative;
            overflow: hidden;
        }
        
        .profile-header::before {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 200px;
            height: 200px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
        }
        
        .profile-header::after {
            content: '';
            position: absolute;
            bottom: -80px;
            left: -80px;
            width: 300px;
            height: 300px;
            background: rgba(255,255,255,0.05);
            border-radius: 50%;
        }
        
        .profile-avatar {
            width: 100px;
            height: 100px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            font-weight: 600;
            color: #667eea;
            border: 4px solid rgba(255,255,255,0.3);
            margin-bottom: 20px;
            position: relative;
            z-index: 2;
        }
        
        .profile-name {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 5px;
            position: relative;
            z-index: 2;
        }
        
        .profile-email {
            font-size: 16px;
            opacity: 0.9;
            margin-bottom: 15px;
            position: relative;
            z-index: 2;
        }
        
        .profile-badges {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            position: relative;
            z-index: 2;
            flex-wrap: wrap;
        }
        
        .profile-badge {
            background: rgba(255,255,255,0.2);
            padding: 8px 20px;
            border-radius: 30px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .profile-badge i {
            font-size: 16px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            text-align: center;
            transition: all 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.1);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            color: white;
            font-size: 24px;
        }
        
        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: #1e293b;
        }
        
        .stat-label {
            color: #64748b;
            font-size: 14px;
            margin-top: 5px;
        }
        
        .tier-card {
            background: linear-gradient(135deg, <?php echo $tier_color; ?>20 0%, <?php echo $tier_color; ?> 100%);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 30px;
            color: <?php echo $tier == 'Platinum' ? '#1e293b' : 'white'; ?>;
            position: relative;
            overflow: hidden;
        }
        
        .tier-name {
            font-size: 24px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }
        
        .tier-progress {
            margin: 15px 0;
        }
        
        .progress-bar {
            width: 100%;
            height: 10px;
            background: rgba(255,255,255,0.3);
            border-radius: 10px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: <?php echo $tier == 'Platinum' ? '#1e293b' : 'white'; ?>;
            border-radius: 10px;
            transition: width 0.3s;
        }
        
        .profile-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 10px;
            flex-wrap: wrap;
        }
        
        .profile-tab {
            padding: 10px 20px;
            background: none;
            border: none;
            color: #64748b;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            border-radius: 8px;
            transition: all 0.3s;
            text-decoration: none;
        }
        
        .profile-tab:hover {
            background: #f1f5f9;
            color: #1e293b;
        }
        
        .profile-tab.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .activity-timeline {
            position: relative;
            padding-left: 30px;
        }
        
        .activity-timeline::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 2px;
            background: linear-gradient(to bottom, #667eea, #764ba2);
        }
        
        .activity-item {
            position: relative;
            padding-bottom: 25px;
        }
        
        .activity-item::before {
            content: '';
            position: absolute;
            left: -34px;
            top: 0;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #667eea;
            border: 2px solid white;
        }
        
        .activity-icon {
            width: 40px;
            height: 40px;
            background: #f1f5f9;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #667eea;
            margin-right: 15px;
        }
        
        .activity-content {
            flex: 1;
        }
        
        .activity-title {
            font-weight: 600;
            color: #1e293b;
        }
        
        .activity-time {
            font-size: 12px;
            color: #64748b;
        }
        
        .item-card {
            background: white;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 10px;
            border-left: 4px solid #667eea;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .item-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        
        .item-name {
            font-weight: 600;
            color: #1e293b;
        }
        
        .item-date {
            font-size: 12px;
            color: #64748b;
        }
        
        .item-details {
            font-size: 13px;
            color: #475569;
        }
        
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: white;
            border-radius: 8px;
            color: #64748b;
            text-decoration: none;
            margin-bottom: 20px;
            transition: all 0.3s;
        }
        
        .back-btn:hover {
            background: #f1f5f9;
            color: #1e293b;
        }
        
        @media (max-width: 768px) {
            .profile-header {
                padding: 20px;
            }
            
            .profile-name {
                font-size: 24px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
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
            
            <!-- Content -->
            <div class="content">
                <a href="users.php" class="back-btn">
                    <i class="fa-solid fa-arrow-left"></i> Back to Users
                </a>
                
                <!-- Profile Header -->
                <div class="profile-header">
                    <div class="profile-avatar">
                        <?php echo strtoupper(substr($user['Name'], 0, 2)); ?>
                    </div>
                    <div class="profile-name"><?php echo htmlspecialchars($user['Name']); ?></div>
                    <div class="profile-email"><?php echo htmlspecialchars($user['Email']); ?></div>
                    
                    <div class="profile-badges">
                        <span class="profile-badge">
                            <i class="fa-solid fa-id-card"></i> ID: <?php echo $user['StudentID'] ?: 'N/A'; ?>
                        </span>
                        <span class="profile-badge">
                            <i class="fa-solid fa-star"></i> <?php echo $user['PointsBalance']; ?> Points
                        </span>
                        <span class="profile-badge">
                            <i class="fa-solid fa-tag"></i> Role: <?php echo $user['Role']; ?>
                        </span>
                        <span class="profile-badge">
                            <i class="fa-solid fa-circle-check"></i> Status: <?php echo $user['MembershipStatus']; ?>
                        </span>
                        <span class="profile-badge">
                            <i class="fa-regular fa-calendar"></i> Joined: <?php echo date('M d, Y', strtotime($user['CreatedAt'])); ?>
                        </span>
                    </div>
                </div>
                
                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fa-solid fa-location-dot"></i>
                        </div>
                        <div class="stat-value"><?php echo $stats['checkins']['count'] ?? 0; ?></div>
                        <div class="stat-label">Total Check-ins</div>
                        <div style="font-size: 12px; color: #64748b; margin-top: 5px;">
                            <i class="fa-solid fa-star"></i> <?php echo $stats['checkins']['total_points'] ?? 0; ?> points earned
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fa-solid fa-cart-shopping"></i>
                        </div>
                        <div class="stat-value"><?php echo $stats['orders']['count'] ?? 0; ?></div>
                        <div class="stat-label">Total Orders</div>
                        <div style="font-size: 12px; color: #64748b; margin-top: 5px;">
                            <i class="fa-solid fa-money-bill"></i> Rs. <?php echo number_format($stats['orders']['total_spent'] ?? 0, 2); ?>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fa-solid fa-book"></i>
                        </div>
                        <div class="stat-value"><?php echo $stats['books']['count'] ?? 0; ?></div>
                        <div class="stat-label">Books Borrowed</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fa-solid fa-person-swimming"></i>
                        </div>
                        <div class="stat-value"><?php echo $stats['pool']['count'] ?? 0; ?></div>
                        <div class="stat-label">Pool Bookings</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fa-solid fa-people-group"></i>
                        </div>
                        <div class="stat-value"><?php echo $stats['classes']['count'] ?? 0; ?></div>
                        <div class="stat-label">Class Bookings</div>
                    </div>
                </div>
                
                <!-- Tier Progress Card -->
                <?php if($next_tier): ?>
                <div class="tier-card">
                    <div class="tier-name">
                        <i class="fa-solid <?php echo $tier_icon; ?>"></i>
                        <?php echo $tier; ?> Member
                    </div>
                    <div class="tier-progress">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                            <span>Current: <?php echo $user['PointsBalance']; ?> points</span>
                            <span>Next: <?php echo $next_tier; ?> (<?php echo $points_needed; ?> points needed)</span>
                        </div>
                        <div class="progress-bar">
                            <?php 
                            $progress = ($user['PointsBalance'] / ($user['PointsBalance'] + $points_needed)) * 100;
                            ?>
                            <div class="progress-fill" style="width: <?php echo $progress; ?>%;"></div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Tabs -->
                <div class="profile-tabs">
                    <a href="#activity" class="profile-tab active" onclick="showTab('activity', event)">Activity Timeline</a>
                    <a href="#checkins" class="profile-tab" onclick="showTab('checkins', event)">Check-ins</a>
                    <a href="#orders" class="profile-tab" onclick="showTab('orders', event)">Orders</a>
                    <a href="#bookings" class="profile-tab" onclick="showTab('bookings', event)">Bookings</a>
                </div>
                
                <!-- Tab: Activity Timeline -->
                <div id="tab-activity" class="tab-content active">
                    <div class="activity-timeline">
                        <?php if(mysqli_num_rows($activity_result) > 0): ?>
                            <?php while($activity = mysqli_fetch_assoc($activity_result)): ?>
                            <div class="activity-item">
                                <div style="display: flex; align-items: flex-start;">
                                    <div class="activity-icon">
                                        <?php
                                        $icon = 'fa-circle-info';
                                        if($activity['Action'] == 'LOGIN') $icon = 'fa-right-to-bracket';
                                        else if($activity['Action'] == 'ORDER') $icon = 'fa-cart-shopping';
                                        else if($activity['Action'] == 'CHECKIN') $icon = 'fa-location-dot';
                                        else if($activity['Action'] == 'BOOK_FIELD') $icon = 'fa-futbol';
                                        else if($activity['Action'] == 'REGISTER') $icon = 'fa-user-plus';
                                        else if($activity['Action'] == 'POINTS_EARNED') $icon = 'fa-star';
                                        ?>
                                        <i class="fa-solid <?php echo $icon; ?>"></i>
                                    </div>
                                    <div class="activity-content">
                                        <div class="activity-title"><?php echo $activity['Action']; ?></div>
                                        <div style="color: #64748b; font-size: 13px;"><?php echo $activity['Details'] ?: ''; ?></div>
                                        <div class="activity-time">
                                            <i class="fa-regular fa-clock"></i> 
                                            <?php echo date('M d, Y h:i A', strtotime($activity['Timestamp'])); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p style="color: #64748b; text-align: center; padding: 30px;">No activity found</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Tab: Check-ins -->
                <div id="tab-checkins" class="tab-content">
                    <h3 style="margin-bottom: 20px;">Recent Check-ins</h3>
                    
                    <?php if(mysqli_num_rows($recent_checkins_result) > 0): ?>
                        <?php while($checkin = mysqli_fetch_assoc($recent_checkins_result)): ?>
                        <div class="item-card">
                            <div class="item-header">
                                <span class="item-name">
                                    <i class="fa-solid fa-building"></i> <?php echo htmlspecialchars($checkin['facility_name']); ?>
                                </span>
                                <span class="item-date"><?php echo date('M d, Y h:i A', strtotime($checkin['Timestamp'])); ?></span>
                            </div>
                            <div class="item-details">
                                <span class="badge badge-info"><?php echo $checkin['facility_type']; ?></span>
                                <span class="badge badge-success" style="margin-left: 10px;">
                                    <i class="fa-solid fa-star"></i> +<?php echo $checkin['PointsAwarded']; ?> points
                                </span>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p style="color: #64748b;">No check-ins found</p>
                    <?php endif; ?>
                </div>
                
                <!-- Tab: Orders -->
                <div id="tab-orders" class="tab-content">
                    <h3 style="margin-bottom: 20px;">Recent Orders</h3>
                    
                    <?php if(mysqli_num_rows($recent_orders_result) > 0): ?>
                        <?php while($order = mysqli_fetch_assoc($recent_orders_result)): ?>
                        <div class="item-card">
                            <div class="item-header">
                                <span class="item-name"><?php echo htmlspecialchars($order['ItemName']); ?></span>
                                <span class="item-date"><?php echo date('M d, Y h:i A', strtotime($order['Timestamp'])); ?></span>
                            </div>
                            <div class="item-details">
                                <span class="badge badge-<?php echo strtolower($order['Category']); ?>"><?php echo $order['Category']; ?></span>
                                <span style="margin-left: 15px;">Qty: <?php echo $order['Quantity']; ?></span>
                                <span style="margin-left: 15px; font-weight: 600; color: #667eea;">Rs. <?php echo number_format($order['Price'] * $order['Quantity'], 2); ?></span>
                                <span class="status-badge status-<?php echo strtolower($order['Status']); ?>" style="margin-left: 15px;">
                                    <?php echo $order['Status']; ?>
                                </span>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p style="color: #64748b;">No orders found</p>
                    <?php endif; ?>
                </div>
                
                <!-- Tab: Bookings -->
                <div id="tab-bookings" class="tab-content">
                    <h3 style="margin-bottom: 20px;">Upcoming Bookings</h3>
                    
                    <!-- Pool Bookings -->
                    <?php
                    $all_bookings_sql = "SELECT 'pool' as type, pb.*, f.Name as facility_name 
                                        FROM pool_bookings pb
                                        JOIN Facilities f ON pb.facility_id = f.FacilityID
                                        WHERE pb.user_id = ? AND pb.booking_date >= CURDATE()
                                        UNION ALL
                                        SELECT 'class' as type, cb.*, fc.name as facility_name
                                        FROM class_bookings cb
                                        JOIN fitness_classes fc ON cb.class_id = fc.class_id
                                        WHERE cb.user_id = ? AND cb.booking_date >= CURDATE()
                                        UNION ALL
                                        SELECT 'field' as type, fb.*, fb.field_name as facility_name
                                        FROM field_bookings fb
                                        WHERE fb.user_id = ? AND fb.booking_date >= CURDATE()
                                        ORDER BY booking_date ASC";
                    $all_bookings_stmt = mysqli_prepare($conn, $all_bookings_sql);
                    mysqli_stmt_bind_param($all_bookings_stmt, "iii", $user_id, $user_id, $user_id);
                    mysqli_stmt_execute($all_bookings_stmt);
                    $all_bookings_result = mysqli_stmt_get_result($all_bookings_stmt);
                    ?>
                    
                    <?php if(mysqli_num_rows($all_bookings_result) > 0): ?>
                        <?php while($booking = mysqli_fetch_assoc($all_bookings_result)): ?>
                        <div class="item-card">
                            <div class="item-header">
                                <span class="item-name">
                                    <?php if($booking['type'] == 'pool'): ?>
                                        <i class="fa-solid fa-person-swimming"></i> Pool - Lane <?php echo $booking['lane_number']; ?>
                                    <?php elseif($booking['type'] == 'class'): ?>
                                        <i class="fa-solid fa-people-group"></i> <?php echo htmlspecialchars($booking['facility_name']); ?>
                                    <?php else: ?>
                                        <i class="fa-solid fa-futbol"></i> <?php echo htmlspecialchars($booking['field_name']); ?>
                                    <?php endif; ?>
                                </span>
                                <span class="item-date"><?php echo date('M d, Y', strtotime($booking['booking_date'])); ?></span>
                            </div>
                            <div class="item-details">
                                <span><i class="fa-regular fa-clock"></i> <?php echo $booking['time_slot'] ?? 'N/A'; ?></span>
                                <span class="status-badge status-<?php echo strtolower($booking['status']); ?>" style="margin-left: 15px;">
                                    <?php echo ucfirst($booking['status']); ?>
                                </span>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p style="color: #64748b;">No upcoming bookings</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function showTab(tab, event) {
            event.preventDefault();
            
            // Update tab buttons
            document.querySelectorAll('.profile-tab').forEach(btn => {
                btn.classList.remove('active');
            });
            event.currentTarget.classList.add('active');
            
            // Update tab content
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            document.getElementById('tab-' + tab).classList.add('active');
        }
    </script>
</body>
</html>