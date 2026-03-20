<?php
/**
 * Club Hub page - main interface for viewing and managing campus clubs.
 * 
 * Features:
 *  - Displays all active clubs with details
 *  - Join button awards 20 points
 *  - Leave button to exit club
 *  - Sidebar stats: my clubs count, total clubs count, points
 *  - Responsive grid layout for club cards
 */

require_once 'config.php';
require_once 'functions.php';

// Authentication
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Load all active clubs
$clubs_sql = "SELECT c.*, u.Name as LeaderName 
              FROM Clubs c 
              LEFT JOIN Users u ON c.LeaderID = u.UserID 
              WHERE c.Status = 'Active'
              ORDER BY c.Name";
$clubs_result = mysqli_query($conn, $clubs_sql);

// Load users' active club memberships
$my_clubs_sql = "SELECT c.*, cm.Role 
                 FROM ClubMemberships cm
                 JOIN Clubs c ON cm.ClubID = c.ClubID
                 WHERE cm.UserID = ? AND cm.Status = 'Active'";
$my_clubs_stmt = mysqli_prepare($conn, $my_clubs_sql);
mysqli_stmt_bind_param($my_clubs_stmt, "i", $user_id);
mysqli_stmt_execute($my_clubs_stmt);
$my_clubs_result = mysqli_stmt_get_result($my_clubs_stmt);

// Load user points & name
$user_sql = "SELECT PointsBalance, Name FROM Users WHERE UserID = ?";
$user_stmt = mysqli_prepare($conn, $user_sql);
mysqli_stmt_bind_param($user_stmt, "i", $user_id);
mysqli_stmt_execute($user_stmt);
$user_result = mysqli_stmt_get_result($user_stmt);
$user = mysqli_fetch_assoc($user_result);

// Prepare arrays for easier frontend use
$facilities_count_sql = "SELECT COUNT(*) as count FROM Facilities WHERE Status = 'Open'";
$facilities_count_result = mysqli_query($conn, $facilities_count_sql);
$facilities_count = mysqli_fetch_assoc($facilities_count_result)['count'];

$my_clubs_array = [];
while($row = mysqli_fetch_assoc($my_clubs_result)) {
    $my_clubs_array[] = $row;
}
$my_clubs_count = count($my_clubs_array);

$all_clubs_array = [];
while($row = mysqli_fetch_assoc($clubs_result)) {
    $all_clubs_array[] = $row;
}
$total_clubs_count = count($all_clubs_array);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <meta charset="UTF-8">
    <title>Club Hub - Synergy Hub</title>
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

        .sidebar-divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            margin: 20px 20px;
        }

        .sidebar-section-title {
            padding: 0 20px;
            margin: 25px 0 10px 0;
            color: #94a3b8;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

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
        
        .club-container {
            padding: 30px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .section-title {
            color: white;
            font-size: 28px;
            margin: 40px 0 20px;
            border-left: 5px solid #22d3ee;
            padding-left: 15px;
        }
        
        .clubs-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }
        
        .club-card {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 25px;
            border: 1px solid rgba(255,255,255,0.2);
            transition: transform 0.3s;
        }
        
        .club-card:hover {
            transform: translateY(-5px);
            background: rgba(255,255,255,0.15);
        }
        
        .club-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .club-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: white;
        }
        
        .club-name {
            font-size: 20px;
            font-weight: 600;
            color: white;
        }
        
        .club-category {
            background: rgba(255,255,255,0.2);
            padding: 4px 12px;
            border-radius: 30px;
            font-size: 12px;
            color: white;
            display: inline-block;
            margin-top: 5px;
        }
        
        .club-description {
            color: rgba(255,255,255,0.9);
            margin: 15px 0;
            line-height: 1.6;
            font-size: 14px;
        }
        
        .club-meta {
            display: flex;
            justify-content: space-between;
            color: rgba(255,255,255,0.6);
            font-size: 13px;
            margin: 15px 0;
            padding: 10px 0;
            border-top: 1px solid rgba(255,255,255,0.1);
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .club-meta i {
            color: #22d3ee;
            margin-right: 5px;
        }
        
        .club-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .join-btn {
            flex: 1;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 12px;
            color: white;
            cursor: pointer;
            font-weight: 600;
            transition: transform 0.3s;
        }
        
        .join-btn:hover:not(:disabled) {
            transform: scale(1.02);
            opacity: 0.9;
        }
        
        .join-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .leave-btn {
            flex: 1;
            padding: 12px;
            background: #ef4444;
            border: none;
            border-radius: 12px;
            color: white;
            cursor: pointer;
            font-weight: 600;
            transition: transform 0.3s;
        }
        
        .leave-btn:hover {
            transform: scale(1.02);
            opacity: 0.9;
        }
        
        .my-clubs-section {
            margin-bottom: 40px;
        }
        
        .role-badge {
            background: #22d3ee;
            color: #0f172a;
            padding: 2px 12px;
            border-radius: 30px;
            font-size: 11px;
            font-weight: 600;
            margin-left: 10px;
            text-transform: uppercase;
        }
        
        .points-badge {
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(10px);
            color: white;
            padding: 15px 25px;
            border-radius: 50px;
            display: inline-block;
            margin-bottom: 20px;
            font-size: 18px;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .points-badge i {
            color: #22d3ee;
            margin-right: 8px;
        }
        
        @media (max-width: 768px) {
            .clubs-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<div class="bg"></div>

<div id="sidebar" class="sidebar">
    <div class="sidebar-header">
        <h2>Synergy Hub</h2>
        <p><i class="fa-solid fa-circle"></i> Connect · Collaborate · Create</p>
    </div>
    
    <div class="sidebar-user">
        <div class="sidebar-user-avatar">
            <i class="fa-solid fa-user"></i>
        </div>
        <div class="sidebar-user-info">
            <h4><?php echo htmlspecialchars($user['Name']); ?></h4>
            <p><i class="fa-solid fa-star"></i> <?php echo $user['PointsBalance']; ?> points</p>
        </div>
    </div>
    
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
            <a href="clubs.php" class="sidebar-nav-link active">
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
            <a href="notifications.php" class="sidebar-nav-link">
                <i class="fa-solid fa-bell"></i>
                <span>Notifications</span>
                <span class="sidebar-badge" id="sidebarNotificationBadge">3</span>
            </a>
        </li>
    </ul>
    
    <div class="sidebar-divider"></div>
    
    <div class="sidebar-section-title">MY CLUBS</div>
    
    <div class="sidebar-club-preview">
        <h4><i class="fa-regular fa-star"></i> Active Clubs</h4>
        <?php 
        if(!empty($my_clubs_array)):
            $preview_count = 0;
            foreach($my_clubs_array as $preview_club):
                if($preview_count >= 2) break;
        ?>
        <div class="sidebar-club-item">
            <h5><?php echo htmlspecialchars($preview_club['Name']); ?></h5>
            <p><?php echo htmlspecialchars(substr($preview_club['Description'], 0, 30)) . '...'; ?></p>
            <span class="sidebar-club-tag"><?php echo htmlspecialchars($preview_club['Category']); ?></span>
        </div>
        <?php 
                $preview_count++;
            endforeach;
        else:
        ?>
        <div style="color: #94a3b8; text-align: center; padding: 10px; font-size: 12px;">
            No clubs joined yet
        </div>
        <?php endif; ?>
    </div>
    
    <div class="sidebar-stats">
        <div class="sidebar-stat-item">
            <div class="sidebar-stat-value"><?php echo $my_clubs_count; ?></div>
            <div class="sidebar-stat-label">My Clubs</div>
        </div>
        <div class="sidebar-stat-item">
            <div class="sidebar-stat-value"><?php echo $total_clubs_count; ?></div>
            <div class="sidebar-stat-label">Total Clubs</div>
        </div>
        <div class="sidebar-stat-item">
            <div class="sidebar-stat-value"><?php echo $user['PointsBalance']; ?></div>
            <div class="sidebar-stat-label">Points</div>
        </div>
    </div>
    
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

<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<header class="navbar">
    <div class="menu-btn" onclick="toggleSidebar()">
        <i class="fa-solid fa-bars"></i>
    </div>
    
    <h1 class="logo">Synergy <span>Hub</span> - Club Hub</h1>
    
    <div class="icons">
        <div class="points">
            <i class="fa-solid fa-star"></i>
            <span><?php echo $user['PointsBalance']; ?></span>
        </div>
        <a href="index.php" class="home-link">
            <i class="fa-solid fa-home"></i>
        </a>
    </div>
</header>

<div class="club-container">
    
    <div class="points-badge">
        <i class="fa-solid fa-star"></i> Your Points: <?php echo $user['PointsBalance']; ?>
    </div>
    
    <?php if(!empty($my_clubs_array)): ?>
    <div class="my-clubs-section">
        <h2 class="section-title">My Clubs</h2>
        <div class="clubs-grid">
            <?php foreach($my_clubs_array as $club): ?>
            <div class="club-card">
                <div class="club-header">
                    <div class="club-icon">
                        <i class="fa-solid fa-users"></i>
                    </div>
                    <div>
                        <div class="club-name">
                            <?php echo htmlspecialchars($club['Name']); ?>
                            <span class="role-badge"><?php echo htmlspecialchars($club['Role']); ?></span>
                        </div>
                        <div class="club-category"><?php echo htmlspecialchars($club['Category']); ?></div>
                    </div>
                </div>
                <div class="club-description">
                    <?php echo htmlspecialchars(substr($club['Description'], 0, 100)) . '...'; ?>
                </div>
                <button class="leave-btn" onclick="leaveClub(<?php echo $club['ClubID']; ?>)">
                    <i class="fa-solid fa-sign-out-alt"></i> Leave Club
                </button>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <h2 class="section-title">All Clubs</h2>
    <div class="clubs-grid">
        <?php foreach($all_clubs_array as $club):
            // Check if user is already a member
            $is_member = false;
            foreach($my_clubs_array as $my_club) {
                if($my_club['ClubID'] == $club['ClubID']) {
                    $is_member = true;
                    break;
                }
            }
            
            // Dynamic icon based on club name (fun touch)
            $icon = 'fa-users';
            if(strpos($club['Name'], 'Coding') !== false) $icon = 'fa-code';
            else if(strpos($club['Name'], 'Cyber') !== false) $icon = 'fa-shield';
            else if(strpos($club['Name'], 'IEEE') !== false) $icon = 'fa-microchip';
            else if(strpos($club['Name'], 'Robotics') !== false) $icon = 'fa-robot';
        ?>
        <div class="club-card">
            <div class="club-header">
                <div class="club-icon">
                    <i class="fa-solid <?php echo $icon; ?>"></i>
                </div>
                <div>
                    <div class="club-name"><?php echo htmlspecialchars($club['Name']); ?></div>
                    <div class="club-category"><?php echo htmlspecialchars($club['Category']); ?></div>
                </div>
            </div>
            <div class="club-description">
                <?php echo htmlspecialchars(substr($club['Description'], 0, 150)) . '...'; ?>
            </div>
            <div class="club-meta">
                <span><i class="fa-regular fa-calendar"></i> Since <?php echo date('Y', strtotime($club['CreatedAt'])); ?></span>
                <span><i class="fa-solid fa-crown"></i> <?php echo htmlspecialchars($club['LeaderName'] ?: 'TBD'); ?></span>
            </div>
            <?php if($is_member): ?>
                <button class="join-btn" disabled style="opacity: 0.5;">
                    <i class="fa-solid fa-check"></i> Already Joined
                </button>
            <?php else: ?>
                <button class="join-btn" onclick="joinClub(<?php echo $club['ClubID']; ?>)">
                    <i class="fa-solid fa-plus"></i> Join Club
                </button>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
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

/**
 * Join a club via AJAX (calls join_club.php)
 * Awards 20 points on success
 */
function joinClub(clubId) {
    if(confirm('Join this club? You will get 20 points!')) {
        fetch('join_club.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'club_id=' + clubId
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                alert('Successfully joined the club! +20 points');
                location.reload();  // Refresh to update UI
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            alert('Error processing request');
        });
    }
}

// Leave a club via AJAX (calls leave_club.php)
function leaveClub(clubId) {
    if(confirm('Are you sure you want to leave this club?')) {
        fetch('leave_club.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'club_id=' + clubId
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                alert('Left the club');
                location.reload();
            }
        })
        .catch(error => {
            alert('Error processing request');
        });
    }
}
</script>

</body>
</html>