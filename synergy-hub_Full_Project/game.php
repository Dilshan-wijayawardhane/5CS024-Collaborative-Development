<?php
require_once 'config.php';
require_once 'functions.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <meta charset="UTF-8">
    <title>Game Hub - Synergy Hub</title>
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
        
        .game-container {
            padding: 30px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .game-hub {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 30px;
            padding: 30px;
        }
        
        /* Game Tabs */
        .game-tabs {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 30px;
            border-bottom: 2px solid rgba(255,255,255,0.1);
            padding-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .tab-btn {
            padding: 15px 30px;
            background: rgba(255,255,255,0.05);
            border: 2px solid rgba(255,255,255,0.1);
            border-radius: 15px;
            color: white;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .tab-btn i {
            font-size: 24px;
        }
        
        .tab-btn:hover {
            background: rgba(255,255,255,0.1);
            transform: translateY(-2px);
        }
        
        .tab-btn.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-color: transparent;
        }
        
        /* Game Panels */
        .game-panel {
            display: none;
        }
        
        .game-panel.active {
            display: block;
        }
        
        /* Memory Game Styles */
        .memory-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            max-width: 600px;
            margin: 40px auto;
        }
        
        .memory-card {
            aspect-ratio: 1;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            color: white;
            transition: transform 0.3s;
            box-shadow: 0 10px 20px rgba(0,0,0,0.3);
        }
        
        .memory-card.flipped {
            background: white;
            color: #667eea;
            transform: rotateY(180deg);
        }
        
        .memory-card.matched {
            background: #10b981;
            color: white;
            cursor: default;
            opacity: 0.7;
        }
        
        /* Math Game Styles */
        .math-game {
            text-align: center;
            padding: 20px;
        }
        
        .question {
            font-size: 60px;
            color: white;
            margin: 30px 0;
            font-weight: 700;
        }
        
        .options {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            max-width: 500px;
            margin: 30px auto;
        }
        
        .option-btn {
            padding: 20px;
            font-size: 24px;
            background: rgba(255,255,255,0.1);
            border: 2px solid rgba(255,255,255,0.2);
            border-radius: 15px;
            color: white;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .option-btn:hover {
            background: rgba(255,255,255,0.2);
            transform: scale(1.05);
        }
        
        .option-btn.correct {
            background: #10b981;
            animation: correctPulse 0.5s;
        }
        
        .option-btn.wrong {
            background: #ef4444;
            animation: shake 0.5s;
        }
        
        @keyframes correctPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }
        
        /* Tic-Tac-Toe Styles */
        .tictactoe-board {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            max-width: 400px;
            margin: 30px auto;
        }
        
        .ttt-cell {
            aspect-ratio: 1;
            background: rgba(255,255,255,0.05);
            border: 2px solid rgba(255,255,255,0.2);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 60px;
            color: white;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .ttt-cell:hover {
            background: rgba(255,255,255,0.1);
            transform: scale(1.05);
        }
        
        .ttt-cell.winner {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            animation: winnerPulse 1s infinite;
        }
        
        @keyframes winnerPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        /* Common Game Elements */
        .game-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        
        .game-stats {
            display: flex;
            gap: 30px;
            color: white;
        }
        
        .stat-box {
            text-align: center;
            background: rgba(255,255,255,0.05);
            padding: 10px 20px;
            border-radius: 15px;
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: #22d3ee;
        }
        
        .stat-label {
            font-size: 12px;
            color: rgba(255,255,255,0.7);
        }
        
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            margin: 10px;
            transition: transform 0.3s;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: #22d3ee;
            color: #0f172a;
        }
        
        .game-modes {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin: 20px 0;
            flex-wrap: wrap;
        }
        
        .mode-btn {
            padding: 8px 16px;
            background: rgba(255,255,255,0.1);
            border: none;
            border-radius: 20px;
            color: white;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .mode-btn:hover {
            background: rgba(255,255,255,0.2);
        }
        
        .mode-btn.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .timer-bar {
            width: 100%;
            height: 10px;
            background: rgba(255,255,255,0.1);
            border-radius: 5px;
            margin: 20px 0;
            overflow: hidden;
        }
        
        .timer-fill {
            height: 100%;
            background: linear-gradient(90deg, #22d3ee, #667eea);
            width: 100%;
            transition: width 0.1s linear;
        }
        
        .player-turn {
            display: inline-block;
            padding: 10px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 30px;
            color: white;
            font-weight: 600;
        }
        
        @media (max-width: 768px) {
            .game-tabs {
                flex-direction: column;
                align-items: stretch;
            }
            
            .memory-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .options {
                grid-template-columns: 1fr;
            }
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
            <h4><?php echo htmlspecialchars($user['Name']); ?></h4>
            <p><i class="fa-solid fa-star"></i> <?php echo $user['PointsBalance']; ?> points</p>
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
            <a href="game.php" class="sidebar-nav-link active">
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
            <a href="notifications.php" class="sidebar-nav-link">
                <i class="fa-solid fa-bell"></i>
                <span>Notifications</span>
                <span class="sidebar-badge" id="sidebarNotificationBadge">3</span>
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
            <div class="sidebar-stat-value"><?php echo $user['PointsBalance']; ?></div>
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
    
    <h1 class="logo">Synergy <span>Hub</span> - Game Hub</h1>
    
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

<div class="game-container">
    <div class="game-hub">
        
        <!-- Game Tabs -->
        <div class="game-tabs">
            <button class="tab-btn active" onclick="switchGame('memory')">
                <i class="fa-solid fa-cards"></i> Memory Game
            </button>
            <button class="tab-btn" onclick="switchGame('math')">
                <i class="fa-solid fa-calculator"></i> Math Game
            </button>
            <button class="tab-btn" onclick="switchGame('tictactoe')">
                <i class="fa-solid fa-grid-2"></i> Tic-Tac-Toe
            </button>
        </div>
        
        <!-- MEMORY GAME PANEL -->
        <div id="memoryGame" class="game-panel active">
            <div class="game-header">
                <h2 style="color: white;">🎴 Memory Card Game</h2>
                <div class="game-stats">
                    <div class="stat-box">
                        <div class="stat-value" id="memoryMoves">0</div>
                        <div class="stat-label">Moves</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-value" id="memoryMatches">0</div>
                        <div class="stat-label">Matches</div>
                    </div>
                </div>
            </div>
            
            <div class="memory-grid" id="memoryGrid"></div>
            
            <div style="text-align: center;">
                <button class="btn btn-primary" onclick="initMemoryGame()">
                    <i class="fa-solid fa-rotate-right"></i> New Game
                </button>
            </div>
            
            <div id="memoryResult" style="text-align: center; color: #22d3ee; margin-top: 20px; font-size: 18px;"></div>
        </div>
        
        <!-- MATH GAME PANEL -->
        <div id="mathGame" class="game-panel">
            <div class="game-header">
                <h2 style="color: white;">🧮 Quick Math Challenge</h2>
                <div class="game-stats">
                    <div class="stat-box">
                        <div class="stat-value" id="mathScore">0</div>
                        <div class="stat-label">Score</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-value" id="mathQuestion">1/10</div>
                        <div class="stat-label">Question</div>
                    </div>
                </div>
            </div>
            
            <div class="timer-bar">
                <div class="timer-fill" id="mathTimer" style="width: 100%;"></div>
            </div>
            
            <div class="question" id="mathQuestionText">5 + 3 = ?</div>
            
            <div class="options" id="mathOptions"></div>
            
            <div style="text-align: center;">
                <button class="btn btn-primary" onclick="startMathGame()">
                    <i class="fa-solid fa-play"></i> Start Game
                </button>
            </div>
            
            <div id="mathResult" style="text-align: center; color: #22d3ee; margin-top: 20px; font-size: 18px;"></div>
        </div>
        
        <!-- TIC-TAC-TOE PANEL (FIXED VERSION) -->
        <div id="tictactoeGame" class="game-panel">
            <div class="game-header">
                <h2 style="color: white;">🎮 Tic-Tac-Toe</h2>
                <div class="game-modes">
                    <button class="mode-btn active" onclick="setTTTMode('cpu')">vs CPU</button>
                    <button class="mode-btn" onclick="setTTTMode('friend')">vs Friend</button>
                </div>
            </div>
            
            <div style="text-align: center; margin: 20px 0;">
                <span class="player-turn" id="tttTurn">Your turn (X)</span>
            </div>
            
            <div class="tictactoe-board" id="tttBoard"></div>
            
            <div style="text-align: center;">
                <button class="btn btn-primary" onclick="resetTTT()">
                    <i class="fa-solid fa-rotate-right"></i> New Game
                </button>
            </div>
            
            <div id="tttResult" style="text-align: center; color: #22d3ee; margin-top: 20px; font-size: 18px;"></div>
        </div>
    </div>
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

// ==================== GAME SWITCHING ====================
function switchGame(game) {
    // Hide all panels
    document.getElementById('memoryGame').classList.remove('active');
    document.getElementById('mathGame').classList.remove('active');
    document.getElementById('tictactoeGame').classList.remove('active');
    
    // Remove active class from all tabs
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Show selected panel
    if (game === 'memory') {
        document.getElementById('memoryGame').classList.add('active');
        document.querySelectorAll('.tab-btn')[0].classList.add('active');
        initMemoryGame();
    } else if (game === 'math') {
        document.getElementById('mathGame').classList.add('active');
        document.querySelectorAll('.tab-btn')[1].classList.add('active');
    } else if (game === 'tictactoe') {
        document.getElementById('tictactoeGame').classList.add('active');
        document.querySelectorAll('.tab-btn')[2].classList.add('active');
        initTTT();
    }
}

// ==================== MEMORY GAME ====================
let memoryCards = [];
let memoryFlipped = [];
let memoryMatched = 0;
let memoryMoves = 0;
let memoryGameActive = false;
const memorySymbols = ['🐶', '🐱', '🐭', '🐹', '🐰', '🦊', '🐻', '🐼'];

function initMemoryGame() {
    let cardSymbols = [...memorySymbols, ...memorySymbols];
    memoryCards = shuffleArray(cardSymbols).map((symbol, index) => ({
        id: index,
        symbol: symbol,
        flipped: false,
        matched: false
    }));
    
    memoryFlipped = [];
    memoryMatched = 0;
    memoryMoves = 0;
    memoryGameActive = true;
    
    updateMemoryDisplay();
    renderMemoryGrid();
    document.getElementById('memoryResult').innerHTML = '';
}

function renderMemoryGrid() {
    const grid = document.getElementById('memoryGrid');
    grid.innerHTML = '';
    
    memoryCards.forEach((card, index) => {
        const cardElement = document.createElement('div');
        cardElement.className = 'memory-card';
        if (card.flipped) cardElement.classList.add('flipped');
        if (card.matched) cardElement.classList.add('matched');
        cardElement.innerHTML = card.flipped || card.matched ? card.symbol : '?';
        cardElement.onclick = () => flipMemoryCard(index);
        grid.appendChild(cardElement);
    });
}

function flipMemoryCard(index) {
    if (!memoryGameActive) return;
    if (memoryCards[index].matched) return;
    if (memoryFlipped.length >= 2) return;
    if (memoryFlipped.includes(index)) return;
    
    memoryCards[index].flipped = true;
    memoryFlipped.push(index);
    renderMemoryGrid();
    
    if (memoryFlipped.length === 2) {
        memoryMoves++;
        updateMemoryDisplay();
        
        const card1 = memoryCards[memoryFlipped[0]];
        const card2 = memoryCards[memoryFlipped[1]];
        
        if (card1.symbol === card2.symbol) {
            setTimeout(() => {
                card1.matched = true;
                card2.matched = true;
                card1.flipped = false;
                card2.flipped = false;
                memoryMatched++;
                memoryFlipped = [];
                renderMemoryGrid();
                
                if (memoryMatched === memorySymbols.length) {
                    memoryGameActive = false;
                    let points = Math.max(10, 50 - memoryMoves);
                    document.getElementById('memoryResult').innerHTML = 
                        `🎉 Congratulations! You won ${points} points!`;
                    saveGameScore('Memory Game', points);
                }
                updateMemoryDisplay();
            }, 500);
        } else {
            setTimeout(() => {
                memoryCards[memoryFlipped[0]].flipped = false;
                memoryCards[memoryFlipped[1]].flipped = false;
                memoryFlipped = [];
                renderMemoryGrid();
            }, 1000);
        }
    }
}

function updateMemoryDisplay() {
    document.getElementById('memoryMoves').textContent = memoryMoves;
    document.getElementById('memoryMatches').textContent = memoryMatched;
}

// ==================== MATH GAME ====================
let mathScore = 0;
let mathQuestion = 1;
let mathTimeLeft = 10;
let mathTimer = null;
let mathGameActive = false;
let currentMathQuestion = null;

function startMathGame() {
    mathScore = 0;
    mathQuestion = 1;
    mathGameActive = true;
    document.getElementById('mathScore').textContent = '0';
    document.getElementById('mathResult').innerHTML = '';
    nextMathQuestion();
}

function nextMathQuestion() {
    if (mathQuestion > 10) {
        endMathGame();
        return;
    }
    
    document.getElementById('mathQuestion').textContent = `${mathQuestion}/10`;
    
    // Generate question
    let operators = ['+', '-', '×'];
    let op = operators[Math.floor(Math.random() * operators.length)];
    let num1, num2, answer;
    
    switch(op) {
        case '+':
            num1 = Math.floor(Math.random() * 20) + 1;
            num2 = Math.floor(Math.random() * 20) + 1;
            answer = num1 + num2;
            break;
        case '-':
            num1 = Math.floor(Math.random() * 30) + 10;
            num2 = Math.floor(Math.random() * num1);
            answer = num1 - num2;
            break;
        case '×':
            num1 = Math.floor(Math.random() * 10) + 1;
            num2 = Math.floor(Math.random() * 10) + 1;
            answer = num1 * num2;
            break;
    }
    
    currentMathQuestion = {
        text: `${num1} ${op} ${num2} = ?`,
        answer: answer
    };
    
    document.getElementById('mathQuestionText').textContent = currentMathQuestion.text;
    
    // Generate options
    let options = [answer];
    while(options.length < 4) {
        let wrong = answer + (Math.floor(Math.random() * 10) - 5);
        if (wrong > 0 && !options.includes(wrong)) {
            options.push(wrong);
        }
    }
    options = shuffleArray(options);
    
    let optionsHtml = '';
    options.forEach(opt => {
        optionsHtml += `<button class="option-btn" onclick="checkMathAnswer(${opt})">${opt}</button>`;
    });
    document.getElementById('mathOptions').innerHTML = optionsHtml;
    
    // Timer
    mathTimeLeft = 10;
    updateMathTimer();
    if (mathTimer) clearInterval(mathTimer);
    mathTimer = setInterval(() => {
        mathTimeLeft--;
        updateMathTimer();
        if (mathTimeLeft <= 0) {
            clearInterval(mathTimer);
            handleMathWrongAnswer();
        }
    }, 1000);
}

function updateMathTimer() {
    let percentage = (mathTimeLeft / 10) * 100;
    document.getElementById('mathTimer').style.width = percentage + '%';
}

function checkMathAnswer(answer) {
    if (!mathGameActive) return;
    clearInterval(mathTimer);
    
    if (answer === currentMathQuestion.answer) {
        document.querySelectorAll('.option-btn').forEach(btn => {
            if (btn.textContent == answer) {
                btn.classList.add('correct');
            }
        });
        
        let pointsToAdd = 10 + mathTimeLeft;
        mathScore += pointsToAdd;
        document.getElementById('mathScore').textContent = mathScore;
        
        mathQuestion++;
        setTimeout(nextMathQuestion, 1000);
    } else {
        handleMathWrongAnswer();
    }
}

function handleMathWrongAnswer() {
    document.querySelectorAll('.option-btn').forEach(btn => {
        if (btn.textContent == currentMathQuestion.answer) {
            btn.classList.add('correct');
        }
    });
    
    mathQuestion++;
    setTimeout(nextMathQuestion, 1000);
}

function endMathGame() {
    mathGameActive = false;
    let points = mathScore;
    document.getElementById('mathResult').innerHTML = `🎉 Game Over! You scored ${points} points!`;
    saveGameScore('Math Game', points);
}

// ==================== TIC-TAC-TOE (FIXED) ====================
let tttBoard = ['', '', '', '', '', '', '', '', ''];
let tttPlayer = 'X';
let tttActive = true;
let tttMode = 'cpu';
let tttGameOver = false;

function initTTT() {
    tttBoard = ['', '', '', '', '', '', '', '', ''];
    tttPlayer = 'X';
    tttActive = true;
    tttGameOver = false;
    renderTTT();
    document.getElementById('tttResult').innerHTML = '';
}

function renderTTT() {
    let boardHtml = '';
    for (let i = 0; i < 9; i++) {
        boardHtml += `<div class="ttt-cell" onclick="makeTTTMove(${i})" id="ttt-cell-${i}">${tttBoard[i]}</div>`;
    }
    document.getElementById('tttBoard').innerHTML = boardHtml;
    document.getElementById('tttTurn').innerHTML = `Your turn (${tttPlayer})`;
}

function makeTTTMove(pos) {
    // Game active check
    if (!tttActive || tttGameOver) return;
    
    // Cell already filled check
    if (tttBoard[pos] !== '') return;
    
    // In CPU mode, only allow X (player) to move, O (CPU) moves automatically
    if (tttMode === 'cpu' && tttPlayer === 'O') return;
    
    // Make the move
    tttBoard[pos] = tttPlayer;
    document.getElementById(`ttt-cell-${pos}`).textContent = tttPlayer;
    
    // Check win after player move
    if (checkTTTWin()) {
        endTTT(tttPlayer);
        return;
    }
    
    // Check draw after player move
    if (checkTTTDraw()) {
        endTTT('draw');
        return;
    }
    
    // Switch player for next turn
    tttPlayer = tttPlayer === 'X' ? 'O' : 'X';
    document.getElementById('tttTurn').innerHTML = `Your turn (${tttPlayer})`;
    
    // If CPU mode and now it's O's turn (CPU), make CPU move
    if (tttMode === 'cpu' && tttPlayer === 'O' && tttActive && !tttGameOver) {
        setTimeout(makeCPUTTTMove, 600);
    }
}

function makeCPUTTTMove() {
    // Double check if game is still active
    if (!tttActive || tttGameOver) return;
    if (tttPlayer !== 'O') return;
    
    // Find all empty cells
    let emptyCells = [];
    tttBoard.forEach((cell, index) => {
        if (cell === '') emptyCells.push(index);
    });
    
    if (emptyCells.length === 0) return;
    
    // Get best move using AI
    let moveIndex = getBestCPUMove(emptyCells);
    
    // Make the CPU move
    tttBoard[moveIndex] = 'O';
    document.getElementById(`ttt-cell-${moveIndex}`).textContent = 'O';
    
    // Check win after CPU move
    if (checkTTTWin()) {
        endTTT('O');
        return;
    }
    
    // Check draw after CPU move
    if (checkTTTDraw()) {
        endTTT('draw');
        return;
    }
    
    // Switch back to player (X)
    tttPlayer = 'X';
    document.getElementById('tttTurn').innerHTML = `Your turn (${tttPlayer})`;
}

// AI for CPU - tries to win, block player, or make smart move
function getBestCPUMove(emptyCells) {
    // 1. Check if CPU can win
    for (let i = 0; i < emptyCells.length; i++) {
        let pos = emptyCells[i];
        tttBoard[pos] = 'O';
        if (checkTTTWin()) {
            tttBoard[pos] = '';
            return pos;
        }
        tttBoard[pos] = '';
    }
    
    // 2. Check if player can win next move (block)
    for (let i = 0; i < emptyCells.length; i++) {
        let pos = emptyCells[i];
        tttBoard[pos] = 'X';
        if (checkTTTWin()) {
            tttBoard[pos] = '';
            return pos;
        }
        tttBoard[pos] = '';
    }
    
    // 3. Take center if available
    if (emptyCells.includes(4)) return 4;
    
    // 4. Take corners
    let corners = [0, 2, 6, 8].filter(pos => emptyCells.includes(pos));
    if (corners.length > 0) {
        return corners[Math.floor(Math.random() * corners.length)];
    }
    
    // 5. Random move
    return emptyCells[Math.floor(Math.random() * emptyCells.length)];
}

function checkTTTWin() {
    const winPatterns = [
        [0,1,2], [3,4,5], [6,7,8],
        [0,3,6], [1,4,7], [2,5,8],
        [0,4,8], [2,4,6]
    ];
    
    for (let pattern of winPatterns) {
        let [a,b,c] = pattern;
        if (tttBoard[a] && tttBoard[a] === tttBoard[b] && tttBoard[a] === tttBoard[c]) {
            // Highlight winning cells
            pattern.forEach(index => {
                let cell = document.getElementById(`ttt-cell-${index}`);
                if (cell) cell.classList.add('winner');
            });
            return true;
        }
    }
    return false;
}

function checkTTTDraw() {
    return tttBoard.every(cell => cell !== '');
}

function endTTT(result) {
    tttActive = false;
    tttGameOver = true;
    let points = 0;
    
    if (result === 'draw') {
        document.getElementById('tttResult').innerHTML = '🤝 Game Draw! +5 points';
        points = 5;
    } else if (result === 'X') {
        document.getElementById('tttResult').innerHTML = '🎉 You Win! +20 points';
        points = 20;
    } else if (result === 'O') {
        if (tttMode === 'cpu') {
            document.getElementById('tttResult').innerHTML = '🤖 CPU Wins! +5 points';
            points = 5;
        } else {
            document.getElementById('tttResult').innerHTML = '👥 Player O Wins! +10 points';
            points = 10;
        }
    }
    
    if (points > 0) {
        saveGameScore('Tic-Tac-Toe', points);
    }
}

function setTTTMode(mode) {
    tttMode = mode;
    document.querySelectorAll('#tictactoeGame .mode-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    event.target.classList.add('active');
    resetTTT();
}

function resetTTT() {
    initTTT();
}

// ==================== COMMON FUNCTIONS ====================
function shuffleArray(array) {
    for (let i = array.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [array[i], array[j]] = [array[j], array[i]];
    }
    return array;
}

function saveGameScore(gameType, points) {
    fetch('save_game_score.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'game_type=' + encodeURIComponent(gameType) + '&points_earned=' + points
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            let pointsDisplay = document.getElementById('pointsDisplay');
            let currentPoints = parseInt(pointsDisplay.textContent);
            pointsDisplay.textContent = currentPoints + points;
            
            document.querySelector('.points').classList.add('active');
            setTimeout(() => {
                document.querySelector('.points').classList.remove('active');
            }, 500);
        }
    })
    .catch(error => {
        console.error('Error saving score:', error);
    });
}

// Initialize first game
window.onload = function() {
    initMemoryGame();
};
</script>

</body>
</html>