<?php
/**
 * Gym Equipment Page: Displays available gym equipment, fitness classes, and allows users to book classes.
 * 
 * Security Notes:
 *  - Requires login
 *  - Join/cancel handled via seperate API endpoints
 *  - Spots left calculated live
 */

require_once 'config.php';
require_once 'functions.php';

// Authentication check
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$facility_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Load gym equipment inventory
$equip_sql = "SELECT * FROM gym_equipment ORDER BY category, name";
$equip_result = mysqli_query($conn, $equip_sql);

//Load fitness classes
$classes_sql = "SELECT * FROM fitness_classes ORDER BY time";
$classes_result = mysqli_query($conn, $classes_sql);

// Load users' active class bookings
$bookings_sql = "SELECT cb.*, fc.name as class_name, fc.time, fc.instructor 
                 FROM class_bookings cb
                 JOIN fitness_classes fc ON cb.class_id = fc.class_id
                 WHERE cb.user_id = ? AND cb.status = 'booked'";
$bookings_stmt = mysqli_prepare($conn, $bookings_sql);
mysqli_stmt_bind_param($bookings_stmt, "i", $user_id);
mysqli_stmt_execute($bookings_stmt);
$bookings_result = mysqli_stmt_get_result($bookings_stmt);

//Load user points and name
$user_sql = "SELECT PointsBalance, Name FROM Users WHERE UserID = ?";
$user_stmt = mysqli_prepare($conn, $user_sql);
mysqli_stmt_bind_param($user_stmt, "i", $user_id);
mysqli_stmt_execute($user_stmt);
$user_result = mysqli_stmt_get_result($user_stmt);
$user = mysqli_fetch_assoc($user_result);

// Count open facilities
$facilities_count_sql = "SELECT COUNT(*) as count FROM Facilities WHERE Status = 'Open'";
$facilities_count_result = mysqli_query($conn, $facilities_count_sql);
$facilities_count = mysqli_fetch_assoc($facilities_count_result)['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <meta charset="UTF-8">
    <title>Gym Equipment - Synergy Hub</title>
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
            transition: all 0.3s;
        }
        
        .points.active {
            transform: scale(1.1);
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .home-link {
            color: white;
            font-size: 20px;
            text-decoration: none;
        }
        
        .home-link:hover {
            color: #22d3ee;
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
        
        .container {
            padding: 30px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .page-title {
            color: white;
            font-size: 32px;
            margin-bottom: 30px;
            text-align: center;
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
        
        .tabs {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .tab-btn {
            padding: 15px 30px;
            background: rgba(255,255,255,0.1);
            border: 2px solid rgba(255,255,255,0.2);
            border-radius: 15px;
            color: white;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .tab-btn:hover {
            background: rgba(255,255,255,0.2);
        }
        
        .tab-btn.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-color: transparent;
        }
        
        .equipment-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }
        
        .equipment-card {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 25px;
            border: 1px solid rgba(255,255,255,0.2);
            transition: all 0.3s;
        }
        
        .equipment-card:hover {
            transform: translateY(-5px);
            background: rgba(255,255,255,0.15);
        }
        
        .equipment-icon {
            font-size: 48px;
            color: #22d3ee;
            margin-bottom: 15px;
            text-align: center;
        }
        
        .equipment-name {
            color: white;
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 10px;
            text-align: center;
        }
        
        .equipment-category {
            text-align: center;
            color: rgba(255,255,255,0.6);
            font-size: 14px;
            margin-bottom: 15px;
        }
        
        .equipment-stats {
            display: flex;
            justify-content: space-between;
            margin: 15px 0;
            color: rgba(255,255,255,0.8);
        }
        
        .classes-list {
            margin-top: 20px;
        }
        
        .class-item {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .class-info h3 {
            color: white;
            margin-bottom: 5px;
        }
        
        .class-info p {
            color: rgba(255,255,255,0.7);
            font-size: 14px;
            margin: 3px 0;
        }
        
        .class-info p i {
            color: #22d3ee;
            width: 20px;
            margin-right: 5px;
        }
        
        .class-status {
            text-align: right;
        }
        
        .spots-left {
            color: #22d3ee;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .join-btn {
            padding: 10px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            color: white;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .join-btn:hover {
            transform: scale(1.05);
        }
        
        .join-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .join-btn.joined {
            background: #10b981;
        }
        
        .bookings-list {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 20px;
            margin-top: 20px;
        }
        
        .booking-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            color: white;
        }
        
        .booking-item:last-child {
            border-bottom: none;
        }
        
        .booking-item strong {
            color: white;
            font-size: 16px;
        }
        
        .booking-item small {
            color: rgba(255,255,255,0.7);
            font-size: 13px;
        }
        
        .no-bookings {
            color: rgba(255,255,255,0.7);
            text-align: center;
            padding: 30px;
            font-style: italic;
        }
        
        .no-bookings i {
            font-size: 40px;
            color: #94a3b8;
            margin-bottom: 10px;
        }
        
        .cancel-btn {
            padding: 5px 15px;
            background: #ef4444;
            border: none;
            border-radius: 8px;
            color: white;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .cancel-btn:hover {
            background: #dc2626;
            transform: scale(1.05);
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
            transition: all 0.3s;
        }
        
        .back-btn:hover {
            background: rgba(255,255,255,0.2);
            color: #22d3ee;
        }
        
        .back-btn i {
            margin-right: 8px;
        }
        
        @media (max-width: 768px) {
            .class-item {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }
            
            .class-status {
                text-align: center;
            }
            
            .tabs {
                flex-direction: column;
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
            <h4><?php echo htmlspecialchars($user['Name'] ?? 'User'); ?></h4>
            <p><i class="fa-solid fa-star"></i> <?php echo $user['PointsBalance'] ?? 0; ?> points</p>
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
                <span class="sidebar-badge">3</span>
            </a>
        </li>
    </ul>
    
    <div class="sidebar-divider"></div>
    
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
    
    <h1 class="logo">Synergy <span>Hub</span> - Gym</h1>
    
    <div class="icons">
        <div class="points">
            <i class="fa-solid fa-star"></i>
            <span id="pointsDisplay"><?php echo $user['PointsBalance']; ?></span>
        </div>
        <a href="facility_details.php?id=<?php echo $facility_id; ?>" class="home-link">
            <i class="fa-solid fa-arrow-left"></i> Back
        </a>
    </div>
</header>

<div class="container">
    
    <div class="points-badge">
        <i class="fa-solid fa-star"></i> Your Points: <span id="currentPoints"><?php echo $user['PointsBalance']; ?></span>
    </div>
    
    <h1 class="page-title">💪 Gym Equipment & Classes</h1>
    
    <div class="tabs">
        <button class="tab-btn active" onclick="switchTab('equipment')">Equipment</button>
        <button class="tab-btn" onclick="switchTab('classes')">Fitness Classes</button>
        <button class="tab-btn" onclick="switchTab('bookings')">My Bookings</button>
    </div>
    
    <div id="equipmentTab">
        <h2 class="section-title">Available Equipment</h2>
        <div class="equipment-grid">
            <?php if(mysqli_num_rows($equip_result) > 0): ?>
                <?php while($item = mysqli_fetch_assoc($equip_result)): ?>
                <div class="equipment-card">
                    <div class="equipment-icon">
                        <i class="fa-solid <?php echo htmlspecialchars($item['image_icon']); ?>"></i>
                    </div>
                    <div class="equipment-name"><?php echo htmlspecialchars($item['name']); ?></div>
                    <div class="equipment-category"><?php echo htmlspecialchars($item['category']); ?></div>
                    <div class="equipment-stats">
                        <span>Total: <?php echo $item['quantity']; ?></span>
                        <span>Available: <?php echo $item['available']; ?></span>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="color: white;">No equipment found.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <div id="classesTab" style="display: none;">
        <h2 class="section-title">Fitness Classes</h2>
        <div class="classes-list">
            <?php if(mysqli_num_rows($classes_result) > 0): ?>
                <?php while($class = mysqli_fetch_assoc($classes_result)): 
                    $spots = $class['capacity'] - $class['booked'];
                    
                    $check_joined_sql = "SELECT * FROM class_bookings WHERE user_id = ? AND class_id = ? AND status = 'booked'";
                    $check_joined_stmt = mysqli_prepare($conn, $check_joined_sql);
                    mysqli_stmt_bind_param($check_joined_stmt, "ii", $user_id, $class['class_id']);
                    mysqli_stmt_execute($check_joined_stmt);
                    $check_joined_result = mysqli_stmt_get_result($check_joined_stmt);
                    $already_joined = mysqli_num_rows($check_joined_result) > 0;
                ?>
                <div class="class-item">
                    <div class="class-info">
                        <h3><?php echo htmlspecialchars($class['name']); ?></h3>
                        <p><i class="fa-regular fa-clock"></i> <?php echo $class['time']; ?></p>
                        <p><i class="fa-solid fa-user"></i> Instructor: <?php echo htmlspecialchars($class['instructor']); ?></p>
                    </div>
                    <div class="class-status">
                        <div class="spots-left"><?php echo $spots; ?> spots left</div>
                        <?php if($already_joined): ?>
                            <button class="join-btn joined" disabled>Already Joined</button>
                        <?php else: ?>
                            <button class="join-btn" onclick="joinClass(<?php echo $class['class_id']; ?>)"
                                <?php echo ($spots <= 0) ? 'disabled' : ''; ?>>
                                Join Class
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="color: white;">No classes available.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <div id="bookingsTab" style="display: none;">
        <h2 class="section-title">My Class Bookings</h2>
        <div class="bookings-list">
            <?php if(mysqli_num_rows($bookings_result) > 0): ?>
                <?php while($booking = mysqli_fetch_assoc($bookings_result)): ?>
                <div class="booking-item">
                    <div>
                        <strong><?php echo htmlspecialchars($booking['class_name']); ?></strong><br>
                        <small><?php echo $booking['time']; ?> with <?php echo htmlspecialchars($booking['instructor']); ?></small>
                    </div>
                    <button class="cancel-btn" onclick="cancelBooking(<?php echo $booking['booking_id']; ?>)">Cancel</button>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-bookings">
                    <i class="fa-solid fa-calendar-xmark"></i><br>
                    No class bookings yet. Join a class!
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <a href="facility_details.php?id=<?php echo $facility_id; ?>" class="back-btn">
        <i class="fa-solid fa-arrow-left"></i> Back to Facility
    </a>
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

function switchTab(tab) {
    const tabs = document.querySelectorAll('.tab-btn');
    const equipmentTab = document.getElementById('equipmentTab');
    const classesTab = document.getElementById('classesTab');
    const bookingsTab = document.getElementById('bookingsTab');
    
    tabs.forEach(t => t.classList.remove('active'));
    
    if(tab === 'equipment') {
        tabs[0].classList.add('active');
        equipmentTab.style.display = 'block';
        classesTab.style.display = 'none';
        bookingsTab.style.display = 'none';
    } else if(tab === 'classes') {
        tabs[1].classList.add('active');
        equipmentTab.style.display = 'none';
        classesTab.style.display = 'block';
        bookingsTab.style.display = 'none';
    } else {
        tabs[2].classList.add('active');
        equipmentTab.style.display = 'none';
        classesTab.style.display = 'none';
        bookingsTab.style.display = 'block';
    }
}

function joinClass(classId) {
    fetch('join_class.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'class_id=' + classId
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            alert('Successfully joined the class!');
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    });
}

function cancelBooking(bookingId) {
    if(confirm('Cancel this booking?')) {
        fetch('cancel_booking.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'booking_id=' + bookingId
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                alert('Booking cancelled');
                location.reload();
            }
        });
    }
}
</script>

</body>
</html>