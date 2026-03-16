<?php
require_once 'config.php';
require_once 'functions.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user points and name for sidebar
$user_sql = "SELECT PointsBalance, Name FROM Users WHERE UserID = ?";
$user_stmt = mysqli_prepare($conn, $user_sql);
mysqli_stmt_bind_param($user_stmt, "i", $user_id);
mysqli_stmt_execute($user_stmt);
$user_result = mysqli_stmt_get_result($user_stmt);
$user = mysqli_fetch_assoc($user_result);

// Get facilities count for badge
$facilities_count_sql = "SELECT COUNT(*) as count FROM Facilities WHERE Status = 'Open'";
$facilities_count_result = mysqli_query($conn, $facilities_count_sql);
$facilities_count = mysqli_fetch_assoc($facilities_count_result)['count'];

// Check if Notifications table exists and get column names
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'Notifications'");
$table_exists = mysqli_num_rows($table_check) > 0;

$notifications = [];

if ($table_exists) {
    // Try to get column names
    $columns = mysqli_query($conn, "SHOW COLUMNS FROM Notifications");
    $column_names = [];
    while($col = mysqli_fetch_assoc($columns)) {
        $column_names[] = $col['Field'];
    }
    
    // Determine the correct column names
    $user_id_col = in_array('UserID', $column_names) ? 'UserID' : (in_array('user_id', $column_names) ? 'user_id' : null);
    $type_col = in_array('Type', $column_names) ? 'Type' : (in_array('type', $column_names) ? 'type' : 'Notification');
    $message_col = in_array('Message', $column_names) ? 'Message' : (in_array('message', $column_names) ? 'message' : '');
    $timestamp_col = in_array('Timestamp', $column_names) ? 'Timestamp' : (in_array('timestamp', $column_names) ? 'timestamp' : 'created_at');
    $status_col = in_array('Status', $column_names) ? 'Status' : (in_array('status', $column_names) ? 'status' : 'Unread');
    
    if ($user_id_col) {
        // Get all notifications for this user
        $sql = "SELECT * FROM Notifications WHERE $user_id_col = ? ORDER BY $timestamp_col DESC";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        while($row = mysqli_fetch_assoc($result)) {
            $notifications[] = $row;
        }
        
        // Mark all as read when viewing page (if status column exists)
        if (in_array($status_col, $column_names)) {
            $mark_sql = "UPDATE Notifications SET $status_col = 'Read' WHERE $user_id_col = ?";
            $mark_stmt = mysqli_prepare($conn, $mark_sql);
            mysqli_stmt_bind_param($mark_stmt, "i", $user_id);
            mysqli_stmt_execute($mark_stmt);
        }
    }
}

// For demo purposes, if no notifications, create sample ones
if (empty($notifications)) {
    $notifications = [
        [
            'Type' => 'Event',
            'Message' => 'Coding Club meeting today at 3 PM in Lab 101',
            'Timestamp' => date('Y-m-d H:i:s', strtotime('-2 hours'))
        ],
        [
            'Type' => 'Transport',
            'Message' => 'Malabe bus delayed by 15 minutes due to traffic',
            'Timestamp' => date('Y-m-d H:i:s', strtotime('-5 hours'))
        ],
        [
            'Type' => 'Announcement',
            'Message' => 'Campus cafeteria will be closed for maintenance on Sunday',
            'Timestamp' => date('Y-m-d H:i:s', strtotime('-1 day'))
        ],
        [
            'Type' => 'Reminder',
            'Message' => 'You have 150 points available. Use them to buy transport passes!',
            'Timestamp' => date('Y-m-d H:i:s', strtotime('-2 days'))
        ]
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - Synergy Hub</title>
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
            padding: 16px 32px;
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
        
        .icons {
            display: flex;
            gap: 20px;
            align-items: center;
        }
        
        .menu-btn {
            color: white;
            font-size: 24px;
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        
        .menu-btn.active {
            transform: rotate(90deg);
        }
        
        .points {
            display: flex;
            align-items: center;
            gap: 6px;
            font-weight: 600;
            padding: 8px 15px;
            border-radius: 20px;
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(10px);
            color: white;
        }
        
        .home-link {
            color: white;
            font-size: 20px;
            text-decoration: none;
        }
        
        .home-link:hover {
            color: #22d3ee;
        }
        
        /* ========================================
           SYNERGY HUB SIDEBAR - LAS SANATA
           ======================================== */

        /* Sidebar Base */
        .sidebar {
            position: fixed;
            left: -280px;
            top: 0;
            width: 280px;
            height: 100%;
            background: linear-gradient(180deg, #1e2b3c 0%, #0d1a24 100%);
            backdrop-filter: blur(10px);
            transition: 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 9999;
            box-shadow: 4px 0 30px rgba(0, 0, 0, 0.3);
            border-right: 1px solid rgba(255, 255, 255, 0.1);
            overflow-y: auto;
        }

        .sidebar.active {
            left: 0;
        }

        /* Sidebar Header */
        .sidebar-header {
            padding: 25px 20px 20px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 15px;
            position: relative;
            overflow: hidden;
        }

        .sidebar-header::after {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, rgba(100, 108, 255, 0.15) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        .sidebar-header h2 {
            color: white;
            font-size: 24px;
            font-weight: 700;
            margin: 0 0 5px 0;
            letter-spacing: -0.5px;
            background: linear-gradient(135deg, #ffffff 0%, #a5b4fc 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .sidebar-header p {
            color: #94a3b8;
            font-size: 13px;
            margin: 0;
            font-weight: 400;
        }

        .sidebar-header p i {
            color: #22d3ee;
            margin-right: 5px;
            font-size: 10px;
        }

        /* User Info in Sidebar */
        .sidebar-user {
            padding: 15px 20px;
            background: rgba(255, 255, 255, 0.03);
            margin: 0 15px 20px 15px;
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.05);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .sidebar-user-avatar {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.2);
        }

        .sidebar-user-info h4 {
            color: white;
            font-size: 15px;
            margin: 0 0 3px 0;
            font-weight: 600;
        }

        .sidebar-user-info p {
            color: #94a3b8;
            font-size: 12px;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .sidebar-user-info p i {
            color: #fbbf24;
            font-size: 10px;
        }

        /* Sidebar Navigation */
        .sidebar-nav {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar-nav-item {
            margin: 4px 12px;
        }

        .sidebar-nav-link {
            display: flex;
            align-items: center;
            padding: 12px 18px;
            color: #b8c7de;
            text-decoration: none;
            border-radius: 12px;
            transition: all 0.3s ease;
            gap: 12px;
            font-weight: 500;
            font-size: 15px;
            position: relative;
            overflow: hidden;
        }

        .sidebar-nav-link i {
            width: 22px;
            font-size: 1.1rem;
            color: #5f7d9e;
            transition: all 0.3s ease;
            text-align: center;
        }

        .sidebar-nav-link:hover {
            background: rgba(168, 192, 255, 0.1);
            color: white;
            transform: translateX(5px);
        }

        .sidebar-nav-link:hover i {
            color: #a5b4fc;
        }

        .sidebar-nav-link.active {
            background: linear-gradient(90deg, rgba(168, 192, 255, 0.15) 0%, rgba(168, 192, 255, 0.05) 100%);
            color: white;
            border-left: 3px solid #a5b4fc;
        }

        .sidebar-nav-link.active i {
            color: #a5b4fc;
        }

        /* Sidebar Badge */
        .sidebar-badge {
            background: #ef4444;
            color: white;
            font-size: 10px;
            font-weight: 600;
            padding: 2px 6px;
            border-radius: 30px;
            margin-left: auto;
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        /* Sidebar Divider */
        .sidebar-divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            margin: 20px 20px;
        }

        /* Sidebar Section Title */
        .sidebar-section-title {
            padding: 0 20px;
            margin: 25px 0 10px 0;
            color: #94a3b8;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Club Preview in Sidebar */
        .sidebar-club-preview {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 16px;
            padding: 15px;
            margin: 0 15px 20px 15px;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .sidebar-club-preview h4 {
            color: white;
            font-size: 13px;
            margin: 0 0 12px 0;
            display: flex;
            align-items: center;
            gap: 6px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            opacity: 0.8;
        }

        .sidebar-club-preview h4 i {
            color: #fbbf24;
        }

        .sidebar-club-item {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 12px;
            padding: 12px;
            margin-bottom: 10px;
            border: 1px solid rgba(255, 255, 255, 0.03);
            transition: transform 0.2s;
        }

        .sidebar-club-item:hover {
            transform: translateX(5px);
            background: rgba(0, 0, 0, 0.3);
        }

        .sidebar-club-item:last-child {
            margin-bottom: 0;
        }

        .sidebar-club-item h5 {
            color: white;
            font-size: 14px;
            margin: 0 0 4px 0;
            font-weight: 600;
        }

        .sidebar-club-item p {
            color: #94a3b8;
            font-size: 11px;
            margin: 0 0 6px 0;
            line-height: 1.4;
        }

        .sidebar-club-tag {
            background: #2d4c6e;
            color: white;
            font-size: 9px;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 30px;
            display: inline-block;
            text-transform: uppercase;
        }

        /* Quick Stats */
        .sidebar-stats {
            display: flex;
            justify-content: space-around;
            padding: 15px 10px;
            margin: 0 15px;
            background: rgba(255, 255, 255, 0.02);
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.03);
        }

        .sidebar-stat-item {
            text-align: center;
        }

        .sidebar-stat-value {
            color: white;
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 3px;
            background: linear-gradient(135deg, #fff, #a5b4fc);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .sidebar-stat-label {
            color: #94a3b8;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        /* Footer Links */
        .sidebar-footer {
            padding: 20px 20px 30px 20px;
        }

        .sidebar-footer-links {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 15px;
        }

        .sidebar-footer-links a {
            color: #94a3b8;
            text-decoration: none;
            font-size: 11px;
            transition: color 0.2s;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .sidebar-footer-links a:hover {
            color: white;
        }

        .sidebar-footer-links a i {
            font-size: 10px;
        }

        .sidebar-copyright {
            color: #64748b;
            font-size: 10px;
            text-align: center;
        }

        /* Overlay for mobile */
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(3px);
            z-index: 9998;
            display: none;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .sidebar-overlay.active {
            display: block;
            opacity: 1;
        }

        /* Scrollbar Styling */
        .sidebar::-webkit-scrollbar {
            width: 4px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: transparent;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 20px;
        }

        .sidebar::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.3);
        }
        
        .container {
            padding: 30px;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .page-title {
            color: white;
            font-size: 32px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .clear-btn {
            padding: 10px 20px;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 30px;
            color: white;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .clear-btn:hover {
            background: rgba(255,255,255,0.2);
        }
        
        .notifications-list {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .notification-item {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            padding: 20px;
            border-bottom: 1px solid #e5e7eb;
            transition: background 0.3s;
        }
        
        .notification-item:hover {
            background: #f9fafb;
        }
        
        .notification-item:last-child {
            border-bottom: none;
        }
        
        .notification-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            flex-shrink: 0;
        }
        
        .notification-icon.gym {
            background: #dbeafe;
            color: #2563eb;
        }
        
        .notification-icon.event {
            background: #dcfce7;
            color: #16a34a;
        }
        
        .notification-icon.transport {
            background: #fff7ed;
            color: #ea580c;
        }
        
        .notification-icon.general {
            background: #f3e8ff;
            color: #9333ea;
        }
        
        .notification-content {
            flex: 1;
        }
        
        .notification-title {
            font-weight: 600;
            color: #111827;
            font-size: 16px;
            margin-bottom: 5px;
        }
        
        .notification-message {
            color: #4b5563;
            font-size: 14px;
            margin-bottom: 8px;
            line-height: 1.5;
        }
        
        .notification-time {
            color: #6b7280;
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .no-notifications {
            padding: 50px;
            text-align: center;
            color: #6b7280;
        }
        
        .no-notifications i {
            font-size: 48px;
            color: #9ca3af;
            margin-bottom: 15px;
        }
        
        .back-btn {
            display: inline-block;
            margin-top: 30px;
            color: white;
            text-decoration: none;
            font-size: 16px;
        }
        
        .back-btn:hover {
            color: #22d3ee;
        }
    </style>
</head>
<body>

<div class="bg"></div>

<!-- SIDEBAR -->
<div id="sidebar" class="sidebar">
    <!-- Header -->
    <div class="sidebar-header">
        <h2>Synergy Hub</h2>
        <p><i class="fa-solid fa-circle"></i> Connect · Collaborate · Create</p>
    </div>
    
    <!-- User Info -->
    <div class="sidebar-user">
        <div class="sidebar-user-avatar">
            <i class="fa-solid fa-user"></i>
        </div>
        <div class="sidebar-user-info">
            <h4><?php echo htmlspecialchars($user['Name'] ?? 'User'); ?></h4>
            <p><i class="fa-solid fa-star"></i> <?php echo $user['PointsBalance'] ?? 0; ?> points</p>
        </div>
    </div>
    
    <!-- Navigation -->
    <ul class="sidebar-nav">
        <li class="sidebar-nav-item">
            <a href="index.php" class="sidebar-nav-link">
                <i class="fa-solid fa-home"></i>
                <span>Home</span>
            </a>
        </li>
        <li class="sidebar-nav-item">
            <a href="facilities.php" class="sidebar-nav-link">
                <i class="fa-solid fa-building"></i>
                <span>Facilities</span>
                <span class="sidebar-badge"><?php echo $facilities_count; ?></span>
            </a>
        </li>
        <li class="sidebar-nav-item">
            <a href="transport.php" class="sidebar-nav-link">
                <i class="fa-solid fa-bus"></i>
                <span>Transport</span>
            </a>
        </li>
        <li class="sidebar-nav-item">
            <a href="game.php" class="sidebar-nav-link">
                <i class="fa-solid fa-futbol"></i>
                <span>Game Field</span>
            </a>
        </li>
        <li class="sidebar-nav-item">
            <a href="clubs.php" class="sidebar-nav-link">
                <i class="fa-solid fa-users"></i>
                <span>Club Hub</span>
            </a>
        </li>
        <li class="sidebar-nav-item">
            <a href="qr.html" class="sidebar-nav-link">
                <i class="fa-solid fa-qrcode"></i>
                <span>QR Scanner</span>
            </a>
        </li>
        <li class="sidebar-nav-item">
            <a href="notifications.php" class="sidebar-nav-link active">
                <i class="fa-solid fa-bell"></i>
                <span>Notifications</span>
                <span class="sidebar-badge"><?php echo count($notifications); ?></span>
            </a>
        </li>
    </ul>
    
    <div class="sidebar-divider"></div>
    
    <!-- My Clubs Preview -->
    <div class="sidebar-section-title">MY CLUBS</div>
    
    <div class="sidebar-club-preview">
        <h4><i class="fa-regular fa-star"></i> Active Clubs</h4>
        <div class="sidebar-club-item">
            <h5>Coding Club</h5>
            <p>Programming and software development...</p>
            <span class="sidebar-club-tag">Academic</span>
        </div>
        <div class="sidebar-club-item">
            <h5>IEEE Student Branch</h5>
            <p>IEEE student chapter...</p>
            <span class="sidebar-club-tag">Academic</span>
        </div>
    </div>
    
    <!-- Quick Stats -->
    <div class="sidebar-stats">
        <div class="sidebar-stat-item">
            <div class="sidebar-stat-value">4</div>
            <div class="sidebar-stat-label">Clubs</div>
        </div>
        <div class="sidebar-stat-item">
            <div class="sidebar-stat-value">12</div>
            <div class="sidebar-stat-label">Events</div>
        </div>
        <div class="sidebar-stat-item">
            <div class="sidebar-stat-value"><?php echo $user['PointsBalance'] ?? 0; ?></div>
            <div class="sidebar-stat-label">Points</div>
        </div>
    </div>
    
    <!-- Footer -->
    <div class="sidebar-footer">
        <div class="sidebar-footer-links">
            <a href="#"><i class="fa-regular fa-circle-question"></i> Help</a>
            <a href="#"><i class="fa-regular fa-gear"></i> Settings</a>
            <a href="#"><i class="fa-regular fa-message"></i> Feedback</a>
        </div>
        <div class="sidebar-copyright">
            © 2025 Synergy Hub
        </div>
    </div>
</div>

<!-- Sidebar Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<!-- NAVBAR -->
<header class="navbar">
    <div class="menu-btn" onclick="toggleSidebar()">
        <i class="fa-solid fa-bars"></i>
    </div>
    
    <h1 class="logo">Synergy <span>Hub</span> - Notifications</h1>
    
    <div class="icons">
        <div class="points">
            <i class="fa-solid fa-star"></i>
            <span><?php echo $user['PointsBalance'] ?? 0; ?></span>
        </div>
        <a href="index.php" class="home-link">
            <i class="fa-solid fa-home"></i>
        </a>
    </div>
</header>

<!-- MAIN CONTENT -->
<div class="container">
    <div class="page-title">
        <span>🔔 All Notifications</span>
        <?php if(!empty($notifications)): ?>
        <button class="clear-btn" onclick="clearAll()">Clear All</button>
        <?php endif; ?>
    </div>
    
    <div class="notifications-list">
        <?php if(!empty($notifications)): ?>
            <?php foreach($notifications as $notif): ?>
                <?php
                // Map the Type to icon and class
                $icon = 'fa-bell';
                $type_class = 'general';
                
                $type = $notif['Type'] ?? 'General';
                
                if(strpos($type, 'Event') !== false) {
                    $icon = 'fa-calendar';
                    $type_class = 'event';
                } else if(strpos($type, 'Transport') !== false) {
                    $icon = 'fa-bus';
                    $type_class = 'transport';
                } else if(strpos($type, 'Reminder') !== false) {
                    $icon = 'fa-clock';
                    $type_class = 'general';
                } else if(strpos($type, 'Announcement') !== false) {
                    $icon = 'fa-bullhorn';
                    $type_class = 'general';
                } else if(strpos($type, 'Gym') !== false) {
                    $icon = 'fa-dumbbell';
                    $type_class = 'gym';
                }
                
                $title = $type . ' Notification';
                $message = $notif['Message'] ?? 'No message';
                $timestamp = $notif['Timestamp'] ?? date('Y-m-d H:i:s');
                ?>
                <div class="notification-item">
                    <div class="notification-icon <?php echo $type_class; ?>">
                        <i class="fa-solid <?php echo $icon; ?>"></i>
                    </div>
                    <div class="notification-content">
                        <div class="notification-title"><?php echo htmlspecialchars($title); ?></div>
                        <div class="notification-message"><?php echo htmlspecialchars($message); ?></div>
                        <div class="notification-time">
                            <i class="fa-regular fa-clock"></i> 
                            <?php echo date('g:i A • M d, Y', strtotime($timestamp)); ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-notifications">
                <i class="fa-regular fa-bell-slash"></i>
                <p>No notifications yet</p>
            </div>
        <?php endif; ?>
    </div>
    
    <a href="index.php" class="back-btn">
        <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
    </a>
</div>

<script>
// ==================== SIDEBAR ====================
function toggleSidebar() {
    const sidebar = document.querySelector(".sidebar");
    const overlay = document.getElementById("sidebarOverlay");
    const menuBtn = document.querySelector(".menu-btn");
    
    if(sidebar.style.left === "0px") {
        sidebar.style.left = "-280px";
        overlay.classList.remove("active");
        menuBtn.classList.remove("active");
    } else {
        sidebar.style.left = "0px";
        overlay.classList.add("active");
        menuBtn.classList.add("active");
    }
}

document.addEventListener("click", function(e) {
    const sidebar = document.querySelector(".sidebar");
    const btn = document.querySelector(".menu-btn");
    const overlay = document.getElementById("sidebarOverlay");
    
    if(sidebar && btn && overlay && 
       !sidebar.contains(e.target) && 
       !btn.contains(e.target) && 
       sidebar.style.left === "0px") {
        sidebar.style.left = "-280px";
        overlay.classList.remove("active");
        btn.classList.remove("active");
    }
});

// ==================== NOTIFICATION FUNCTIONS ====================
function clearAll() {
    if(confirm('Clear all notifications?')) {
        fetch('mark_notification_read.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'all=1'
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                location.reload();
            } else {
                // For demo purposes, just reload
                location.reload();
            }
        })
        .catch(error => {
            // For demo purposes, just reload
            location.reload();
        });
    }
}
</script>

</body>
</html>