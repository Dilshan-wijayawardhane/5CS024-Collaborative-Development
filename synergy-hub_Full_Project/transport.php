<?php
require_once 'config.php';
require_once 'functions.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user's transport passes (including Pending status)
$passes_sql = "SELECT * FROM TransportPasses WHERE UserID = ? ORDER BY 
               CASE 
                   WHEN Status = 'Pending' THEN 1
                   WHEN Status = 'Active' THEN 2
                   ELSE 3
               END, ValidUntil DESC";
$passes_stmt = mysqli_prepare($conn, $passes_sql);
mysqli_stmt_bind_param($passes_stmt, "i", $user_id);
mysqli_stmt_execute($passes_stmt);
$passes_result = mysqli_stmt_get_result($passes_stmt);

// Get bus routes for tracking from bus_routes table
$bus_routes_sql = "SELECT * FROM bus_routes ORDER BY route_id";
$bus_routes_result = mysqli_query($conn, $bus_routes_sql);

// Get CINEC bus schedule from database
$campus_transport_sql = "SELECT * FROM campus_transport ORDER BY 
                          CASE 
                              WHEN from_campus = 'CINEC' THEN 1 
                              ELSE 0 
                          END, from_campus";
$campus_transport_result = mysqli_query($conn, $campus_transport_sql);

// Get user points and name
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

// Define route display names and prices
$route_details = [
    'cinec' => ['name' => 'Malabe', 'price' => 100, 'frequency' => 'Every 30 mins'],
    'gampaha1' => ['name' => 'Gampaha - 1', 'price' => 120, 'frequency' => 'Every 45 mins'],
    'gampaha2' => ['name' => 'Gampaha - 2', 'price' => 120, 'frequency' => 'Every 45 mins'],
    'hendala' => ['name' => 'Hendala', 'price' => 80, 'frequency' => 'Every 20 mins'],
    'moratuwa' => ['name' => 'Moratuwa', 'price' => 150, 'frequency' => 'Every 60 mins'],
    'negombo' => ['name' => 'Negombo', 'price' => 200, 'frequency' => 'Every 90 mins'],
];

// Check if user has pending request for a route
function hasPendingRequest($conn, $user_id, $route_name) {
    $sql = "SELECT COUNT(*) as count FROM TransportPasses WHERE UserID = ? AND RouteName = ? AND Status = 'Pending'";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "is", $user_id, $route_name);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $data = mysqli_fetch_assoc($result);
    return $data['count'] > 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transport - Synergy Hub</title>
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
        
        /* Sidebar Styles (same as before - keeping it compact) */
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
        .sidebar.active { left: 0; }
        .sidebar-header { padding: 25px 20px 20px 20px; border-bottom: 1px solid rgba(255, 255, 255, 0.1); margin-bottom: 15px; }
        .sidebar-header h2 { color: white; font-size: 24px; font-weight: 700; background: linear-gradient(135deg, #ffffff 0%, #a5b4fc 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .sidebar-header p { color: #94a3b8; font-size: 13px; }
        .sidebar-user { padding: 15px 20px; background: rgba(255, 255, 255, 0.03); margin: 0 15px 20px 15px; border-radius: 16px; display: flex; align-items: center; gap: 12px; }
        .sidebar-user-avatar { width: 45px; height: 45px; border-radius: 12px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; font-size: 20px; color: white; }
        .sidebar-user-info h4 { color: white; font-size: 15px; margin: 0 0 3px 0; }
        .sidebar-user-info p { color: #94a3b8; font-size: 12px; }
        .sidebar-user-info p i { color: #fbbf24; }
        .sidebar-nav { list-style: none; padding: 0; margin: 0; }
        .sidebar-nav-item { margin: 4px 12px; }
        .sidebar-nav-link { display: flex; align-items: center; padding: 12px 18px; color: #b8c7de; text-decoration: none; border-radius: 12px; transition: all 0.3s ease; gap: 12px; }
        .sidebar-nav-link:hover { background: rgba(168, 192, 255, 0.1); color: white; transform: translateX(5px); }
        .sidebar-nav-link.active { background: linear-gradient(90deg, rgba(168, 192, 255, 0.15) 0%, rgba(168, 192, 255, 0.05) 100%); color: white; border-left: 3px solid #a5b4fc; }
        .sidebar-badge { background: #ef4444; color: white; font-size: 10px; font-weight: 600; padding: 2px 6px; border-radius: 30px; margin-left: auto; }
        .sidebar-divider { height: 1px; background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent); margin: 20px 20px; }
        .sidebar-section-title { padding: 0 20px; margin: 25px 0 10px 0; color: #94a3b8; font-size: 11px; font-weight: 600; text-transform: uppercase; }
        .sidebar-stats { display: flex; justify-content: space-around; padding: 15px 10px; margin: 0 15px; background: rgba(255, 255, 255, 0.02); border-radius: 16px; }
        .sidebar-stat-value { color: white; font-size: 18px; font-weight: 700; background: linear-gradient(135deg, #fff, #a5b4fc); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .sidebar-stat-label { color: #94a3b8; font-size: 10px; text-transform: uppercase; }
        .sidebar-footer { padding: 20px 20px 30px 20px; }
        .sidebar-footer-links { display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 15px; }
        .sidebar-footer-links a { color: #94a3b8; text-decoration: none; font-size: 11px; }
        .sidebar-copyright { color: #64748b; font-size: 10px; text-align: center; }
        .sidebar-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.5); backdrop-filter: blur(3px); z-index: 9998; display: none; }
        .sidebar-overlay.active { display: block; }
        
        .transport-container {
            padding: 30px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .points-badge {
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(10px);
            color: white;
            padding: 15px 25px;
            border-radius: 50px;
            display: inline-block;
            margin-bottom: 30px;
            font-weight: 600;
            font-size: 18px;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .points-badge i {
            color: #22d3ee;
            margin-right: 8px;
        }
        
        .section-title {
            color: white;
            font-size: 24px;
            margin: 40px 0 20px;
            border-left: 5px solid #22d3ee;
            padding-left: 15px;
        }
        
        /* Passes Grid */
        .passes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .pass-card {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 20px;
            border: 1px solid rgba(255,255,255,0.2);
            transition: all 0.3s;
        }
        
        .pass-card:hover {
            transform: translateY(-5px);
            background: rgba(255,255,255,0.15);
        }
        
        .pass-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .pass-route {
            font-size: 18px;
            font-weight: 600;
            color: white;
        }
        
        .pass-status {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-Active {
            background: #10b981;
            color: white;
        }
        
        .status-Expired {
            background: #ef4444;
            color: white;
        }
        
        .status-Cancelled {
            background: #f59e0b;
            color: white;
        }
        
        .status-Pending {
            background: #f59e0b;
            color: white;
        }
        
        .pending-animation {
            animation: pulse 1.5s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        
        .pass-details {
            color: rgba(255,255,255,0.9);
            margin: 10px 0;
            line-height: 1.6;
        }
        
        .pass-details i {
            color: #22d3ee;
            width: 20px;
            margin-right: 8px;
        }
        
        .no-passes {
            color: rgba(255,255,255,0.7);
            font-style: italic;
            background: rgba(255,255,255,0.05);
            padding: 20px;
            border-radius: 15px;
            text-align: center;
        }
        
        /* Tracking Grid */
        .tracking-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
            margin-bottom: 40px;
        }
        
        .track-card {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(14px);
            border-radius: 18px;
            overflow: hidden;
            transition: 0.3s;
            display: flex;
            flex-direction: column;
        }
        
        .track-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 18px 40px rgba(0,0,0,0.35);
        }
        
        .track-card h3 {
            text-align: center;
            padding: 15px;
            margin: 0;
            color: white;
            background: rgba(0,0,0,0.2);
        }
        
        .map-area {
            height: 150px;
            overflow: hidden;
        }
        
        .map-area iframe {
            width: 100%;
            height: 100%;
            border: none;
        }
        
        .track-buttons {
            display: flex;
            justify-content: space-around;
            padding: 16px;
            background: rgba(255,255,255,0.05);
        }
        
        .track-buttons button {
            flex: 1;
            margin: 0 6px;
            padding: 10px 0;
            border: none;
            border-radius: 10px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
        }
        
        .track-buttons button:hover {
            transform: scale(1.05);
        }
        
        .status-text {
            font-size: 0.8rem;
            color: white;
            text-align: center;
            padding: 10px;
            background: rgba(0,0,0,0.2);
            margin: 0;
        }
        
        /* Campus Schedule */
        .campus-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .campus-card {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 25px;
            border: 1px solid rgba(255,255,255,0.2);
            transition: all 0.3s;
        }
        
        .campus-card:hover {
            transform: translateY(-5px);
            background: rgba(255,255,255,0.15);
        }
        
        .campus-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .campus-icon {
            width: 50px;
            height: 50px;
            background: rgba(34, 211, 238, 0.2);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: #22d3ee;
        }
        
        .campus-route {
            font-size: 18px;
            font-weight: 600;
            color: white;
        }
        
        .campus-details {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid rgba(255,255,255,0.1);
        }
        
        .next-departure {
            color: #22d3ee;
            font-size: 20px;
            font-weight: 600;
        }
        
        .next-departure.morning {
            color: #fbbf24;
        }
        
        .next-departure.evening {
            color: #22d3ee;
            font-size: 24px;
            font-weight: 700;
        }
        
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-On-Time { background: #10b981; color: white; }
        .status-Sharp { background: #22d3ee; color: white; }
        .status-Delayed { background: #f59e0b; color: white; }
        .status-Cancelled { background: #ef4444; color: white; }
        
        .frequency {
            color: rgba(255,255,255,0.7);
            font-size: 14px;
            margin-top: 5px;
        }
        
        /* Available Routes List */
        .routes-list {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 20px;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .route-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .route-item:last-child {
            border-bottom: none;
        }
        
        .route-info {
            flex: 2;
        }
        
        .route-name {
            color: white;
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .route-details {
            color: rgba(255,255,255,0.7);
            font-size: 14px;
        }
        
        .route-details i {
            color: #22d3ee;
            margin-right: 5px;
        }
        
        .route-price {
            flex: 1;
            text-align: right;
            color: #22d3ee;
            font-weight: 700;
            font-size: 18px;
            margin-right: 20px;
        }
        
        .request-btn {
            padding: 12px 25px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 30px;
            color: white;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            border: 1px solid rgba(255,255,255,0.2);
            min-width: 120px;
        }
        
        .request-btn:hover:not(:disabled) {
            transform: scale(1.05);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        
        .request-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }
        
        .request-btn.pending {
            background: #f59e0b;
        }
        
        .request-btn i {
            margin-right: 5px;
        }
        
        .back-btn {
            display: inline-block;
            margin-top: 30px;
            color: white;
            text-decoration: none;
            font-size: 16px;
            padding: 10px 20px;
            background: rgba(255,255,255,0.1);
            border-radius: 30px;
        }
        
        .back-btn:hover {
            background: rgba(255,255,255,0.2);
            color: #22d3ee;
        }
        
        /* Info Banner */
        .info-banner {
            background: rgba(34, 211, 238, 0.1);
            border-left: 4px solid #22d3ee;
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            color: rgba(255,255,255,0.9);
            font-size: 14px;
        }
        
        .info-banner i {
            color: #22d3ee;
            margin-right: 10px;
        }
        
        @media (max-width: 768px) {
            .tracking-grid {
                grid-template-columns: 1fr;
            }
            
            .campus-grid {
                grid-template-columns: 1fr;
            }
            
            .route-item {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .route-price {
                text-align: center;
                margin-right: 0;
            }
        }
    </style>
</head>
<body>

<div class="bg"></div>

<!-- SIDEBAR -->
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
        <li class="sidebar-nav-item"><a href="index.php" class="sidebar-nav-link"><i class="fa-solid fa-home"></i><span>Home</span></a></li>
        <li class="sidebar-nav-item"><a href="facilities.php" class="sidebar-nav-link"><i class="fa-solid fa-building"></i><span>Facilities</span><span class="sidebar-badge"><?php echo $facilities_count; ?></span></a></li>
        <li class="sidebar-nav-item"><a href="transport.php" class="sidebar-nav-link active"><i class="fa-solid fa-bus"></i><span>Transport</span></a></li>
        <li class="sidebar-nav-item"><a href="game.php" class="sidebar-nav-link"><i class="fa-solid fa-futbol"></i><span>Game Field</span></a></li>
        <li class="sidebar-nav-item"><a href="clubs.php" class="sidebar-nav-link"><i class="fa-solid fa-users"></i><span>Club Hub</span></a></li>
        <li class="sidebar-nav-item"><a href="qr.html" class="sidebar-nav-link"><i class="fa-solid fa-qrcode"></i><span>QR Scanner</span></a></li>
        <li class="sidebar-nav-item"><a href="notifications.php" class="sidebar-nav-link"><i class="fa-solid fa-bell"></i><span>Notifications</span><span class="sidebar-badge" id="sidebarNotificationBadge">3</span></a></li>
    </ul>
    
    <div class="sidebar-divider"></div>
    
    <div class="sidebar-stats">
        <div class="sidebar-stat-item"><div class="sidebar-stat-value">4</div><div class="sidebar-stat-label">Clubs</div></div>
        <div class="sidebar-stat-item"><div class="sidebar-stat-value">12</div><div class="sidebar-stat-label">Events</div></div>
        <div class="sidebar-stat-item"><div class="sidebar-stat-value"><?php echo $user['PointsBalance']; ?></div><div class="sidebar-stat-label">Points</div></div>
    </div>
    
    <div class="sidebar-footer">
        <div class="sidebar-footer-links">
            <a href="#"><i class="fa-regular fa-circle-question"></i> Help</a>
            <a href="#"><i class="fa-regular fa-gear"></i> Settings</a>
            <a href="#"><i class="fa-regular fa-message"></i> Feedback</a>
        </div>
        <div class="sidebar-copyright">© 2025 Synergy Hub</div>
    </div>
</div>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<!-- NAVBAR -->
<header class="navbar">
    <div class="menu-btn" onclick="toggleSidebar()">
        <i class="fa-solid fa-bars"></i>
    </div>
    
    <h1 class="logo">Synergy <span>Hub</span> - Transport</h1>
    
    <div class="icons">
        <div class="points">
            <i class="fa-solid fa-star"></i>
            <span id="pointsDisplay"><?php echo $user['PointsBalance']; ?></span>
        </div>
        <a href="index.php" class="home-link">
            <i class="fa-solid fa-home"></i>
        </a>
    </div>
</header>

<!-- MAIN CONTENT -->
<div class="transport-container">
    
    <!-- POINTS BADGE -->
    <div class="points-badge">
        <i class="fa-solid fa-star"></i> Your Points: <span id="currentPoints"><?php echo $user['PointsBalance']; ?></span>
    </div>
    
    <!-- INFO BANNER - Explain the request system -->
    <div class="info-banner">
        <i class="fa-solid fa-info-circle"></i> 
        Transport passes now require admin approval. Request a pass and it will be reviewed by our team. You'll be notified once approved!
    </div>
    
    <!-- CINEC BUS SCHEDULE SECTION -->
    <h2 class="section-title">🚌 CINEC Bus Schedule</h2>
    <div class="campus-grid">
        <?php 
        if(mysqli_num_rows($campus_transport_result) > 0):
            while($route = mysqli_fetch_assoc($campus_transport_result)):
                $is_evening = ($route['from_campus'] == 'CINEC');
        ?>
        <div class="campus-card">
            <div class="campus-header">
                <div class="campus-icon">
                    <i class="fa-solid fa-bus"></i>
                </div>
                <div class="campus-route">
                    <?php echo $route['from_campus']; ?> → <?php echo $route['to_campus']; ?>
                </div>
            </div>
            <div class="campus-details">
                <div>
                    <div class="next-departure <?php echo $is_evening ? 'evening' : 'morning'; ?>">
                        <?php echo $route['next_departure']; ?>
                    </div>
                    <div class="frequency"><?php echo $route['frequency']; ?></div>
                </div>
                <span class="status-badge status-<?php echo str_replace('-', '', $route['status']); ?>">
                    <?php echo $route['status']; ?>
                </span>
            </div>
        </div>
        <?php 
            endwhile;
        else:
        ?>
        <p style="color: white;">No bus schedules available</p>
        <?php endif; ?>
    </div>
    
    <!-- BUS TRACKING SECTION -->
    <h2 class="section-title">🚍 Live Bus Tracking</h2>
    <div class="tracking-grid">
        <?php 
        if(mysqli_num_rows($bus_routes_result) > 0):
            while($bus = mysqli_fetch_assoc($bus_routes_result)):
                $route_id = $bus['route_id'];
                $display_name = $route_details[$route_id]['name'] ?? ucfirst($route_id);
        ?>
        <div class="track-card" id="route-<?php echo $route_id; ?>">
            <h3><?php echo $display_name; ?></h3>
            <div class="map-area">
                <iframe id="map-<?php echo $route_id; ?>"
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3960.783515024766!2d79.97036937587595!3d6.916460618471185!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3ae256db1a677131%3A0x2c6145384bc19bc8!2sCINEC%20Campus!5e0!3m2!1sen!2slk!4v1700000000000" 
                    allowfullscreen="" loading="lazy">
                </iframe>
            </div>
            <div class="track-buttons">
                <button onclick="viewLocation('<?php echo $route_id; ?>')">
                    <i class="fa-solid fa-eye"></i> View
                </button>
                <button onclick="updateLocation('<?php echo $route_id; ?>')">
                    <i class="fa-solid fa-pen"></i> Update
                </button>
            </div>
            <p class="status-text" id="status-<?php echo $route_id; ?>">
                <?php if($bus['location']): ?>
                    <strong>Last Location:</strong> <?php echo $bus['location']; ?><br>
                    <strong>Updated at:</strong> <?php echo $bus['updated_time']; ?>
                <?php else: ?>
                    No updates yet
                <?php endif; ?>
            </p>
        </div>
        <?php 
            endwhile;
        endif;
        ?>
    </div>
    
    <!-- MY TRANSPORT PASSES SECTION -->
    <h2 class="section-title">🎫 My Transport Passes</h2>
    <div class="passes-grid">
        <?php if(mysqli_num_rows($passes_result) > 0): ?>
            <?php while($pass = mysqli_fetch_assoc($passes_result)): ?>
            <div class="pass-card">
                <div class="pass-header">
                    <span class="pass-route"><?php echo htmlspecialchars($pass['RouteName']); ?></span>
                    <span class="pass-status status-<?php echo $pass['Status']; ?> <?php echo $pass['Status'] == 'Pending' ? 'pending-animation' : ''; ?>">
                        <?php echo $pass['Status']; ?>
                        <?php if($pass['Status'] == 'Pending'): ?>
                            <i class="fa-regular fa-clock"></i>
                        <?php endif; ?>
                    </span>
                </div>
                <div class="pass-details">
                    <?php if($pass['Status'] == 'Pending'): ?>
                        <i class="fa-regular fa-hourglass-half"></i> Awaiting Admin Approval<br>
                        <i class="fa-regular fa-calendar"></i> Requested: <?php echo date('M d, Y', strtotime($pass['IssuedAt'])); ?>
                    <?php else: ?>
                        <i class="fa-regular fa-calendar"></i> Valid Until: <?php echo date('M d, Y', strtotime($pass['ValidUntil'])); ?><br>
                        <i class="fa-regular fa-clock"></i> Issued: <?php echo date('M d, Y', strtotime($pass['IssuedAt'])); ?>
                    <?php endif; ?>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="no-passes">
                <i class="fa-solid fa-ticket"></i> No transport passes yet. Request one below!
            </div>
        <?php endif; ?>
    </div>
    
    <!-- AVAILABLE ROUTES SECTION - REQUEST PASS BUTTONS -->
    <h2 class="section-title">🛒 Available Routes - Request Pass</h2>
    <div class="routes-list">
        <?php foreach($route_details as $route_id => $route): 
            $has_pending = hasPendingRequest($conn, $user_id, $route['name']);
            $can_request = ($user['PointsBalance'] >= $route['price']) && !$has_pending;
        ?>
        <div class="route-item">
            <div class="route-info">
                <div class="route-name"><?php echo $route['name']; ?></div>
                <div class="route-details">
                    <i class="fa-regular fa-clock"></i> <?php echo $route['frequency']; ?>
                </div>
            </div>
            <div class="route-price"><?php echo $route['price']; ?> points</div>
            <button class="request-btn <?php echo $has_pending ? 'pending' : ''; ?>" 
                    onclick="requestPass('<?php echo $route['name']; ?>', <?php echo $route['price']; ?>)"
                    <?php echo (!$can_request && !$has_pending) ? 'disabled' : ''; ?>>
                <?php if($has_pending): ?>
                    <i class="fa-regular fa-clock"></i> Pending Approval
                <?php elseif($user['PointsBalance'] < $route['price']): ?>
                    <i class="fa-solid fa-star"></i> Insufficient Points
                <?php else: ?>
                    <i class="fa-solid fa-paper-plane"></i> Request Pass
                <?php endif; ?>
            </button>
        </div>
        <?php endforeach; ?>
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

// Request Pass function (instead of direct buy)
function requestPass(routeName, price) {
    if(confirm(`Request transport pass for ${routeName} (${price} points)?\n\nYour request will be sent to admin for approval. Points will be deducted only after approval.`)) {
        fetch('request_pass.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'route=' + encodeURIComponent(routeName) + '&price=' + price
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                alert('✅ Pass request submitted! You will be notified once approved.');
                location.reload();
            } else {
                alert('❌ Error: ' + data.message);
            }
        })
        .catch(error => {
            alert('❌ Error processing request');
        });
    }
}

// Update bus location
function updateLocation(routeId) {
    let newPlace = prompt("Where is the bus now? (e.g., Malabe, Colombo, etc.)");
    
    if (!newPlace) return;
    
    let time = new Date().toLocaleTimeString([], {
        hour: '2-digit',
        minute: '2-digit'
    });
    
    fetch('save_location.php', {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body: 'route=' + routeId + '&place=' + encodeURIComponent(newPlace) + '&time=' + time
    })
    .then(res => res.text())
    .then(data => {
        renderStatus(routeId, newPlace, time);
        alert("✅ Location saved!");
    })
    .catch(error => {
        alert("❌ Error saving location");
    });
}

// View bus location
function viewLocation(routeId) {
    fetch('get_location.php?route=' + routeId)
    .then(res => res.json())
    .then(data => {
        if (!data || !data.location) {
            alert("No updates yet for this route");
            return;
        }
        
        let map = document.getElementById("map-" + routeId);
        if (map) {
            map.src = "https://maps.google.com/maps?q=" +
                      encodeURIComponent(data.location) +
                      "&output=embed";
        }
        
        renderStatus(routeId, data.location, data.updated_time);
    })
    .catch(error => {
        alert("❌ Error fetching location");
    });
}

function renderStatus(routeId, place, time) {
    let statusEl = document.getElementById('status-' + routeId);
    if (statusEl) {
        statusEl.innerHTML = '<strong>Last Location:</strong> ' + place + ' <br> <strong>Updated at:</strong> ' + time;
    }
}
</script>

</body>
</html>