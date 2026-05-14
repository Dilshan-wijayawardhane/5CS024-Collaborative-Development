<?php

require_once 'config.php';
require_once 'functions.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$user_sql = "SELECT PointsBalance, Name FROM Users WHERE UserID = ?";
$user_stmt = mysqli_prepare($conn, $user_sql);
mysqli_stmt_bind_param($user_stmt, "i", $user_id);
mysqli_stmt_execute($user_stmt);
$user_result = mysqli_stmt_get_result($user_stmt);
$user = mysqli_fetch_assoc($user_result);

$facilities_count_sql = "SELECT COUNT(*) as count FROM Facilities WHERE Status = 'Open'";
$facilities_count_result = mysqli_query($conn, $facilities_count_sql);
$facilities_count = mysqli_fetch_assoc($facilities_count_result)['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            position: relative;
        }
        
        /* NAVBAR - White/Blue theme */
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 32px;
            background: white;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.08);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .logo {
            font-size: 24px;
            font-weight: 700;
            color: #1e4a76;
        }
        
        .logo span {
            color: #2c7da0;
        }
        
        .icons {
            display: flex;
            gap: 20px;
            align-items: center;
        }
        
        .menu-btn {
            color: #1e4a76;
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
            background: linear-gradient(135deg, #1e4a76 0%, #2c7da0 100%);
            color: white;
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .points.active {
            transform: scale(1.1);
        }
        
        .home-link {
            color: #1e4a76;
            font-size: 20px;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .home-link:hover {
            color: #2c7da0;
        }
        
        /* SIDEBAR - White/Blue theme */
        .sidebar {
            position: fixed;
            left: -280px;
            top: 0;
            width: 280px;
            height: 100%;
            background: white;
            transition: 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 9999;
            box-shadow: 4px 0 30px rgba(0, 0, 0, 0.1);
            border-right: 1px solid rgba(0, 0, 0, 0.08);
            overflow-y: auto;
        }

        .sidebar.active {
            left: 0;
        }

        .sidebar-header {
            padding: 25px 20px 20px 20px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.08);
            margin-bottom: 15px;
            background: linear-gradient(135deg, #1e4a76 0%, #2c7da0 100%);
        }

        .sidebar-header h2 {
            color: white;
            font-size: 24px;
            font-weight: 700;
            margin: 0 0 5px 0;
        }

        .sidebar-header p {
            color: rgba(255, 255, 255, 0.8);
            font-size: 13px;
            margin: 0;
        }

        .sidebar-header p i {
            color: #22d3ee;
            margin-right: 5px;
        }

        .sidebar-user {
            padding: 15px 20px;
            background: #f8fafc;
            margin: 0 15px 20px 15px;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .sidebar-user-avatar {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            background: linear-gradient(135deg, #1e4a76 0%, #2c7da0 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: white;
        }

        .sidebar-user-info h4 {
            color: #1e293b;
            font-size: 15px;
            margin: 0 0 3px 0;
            font-weight: 600;
        }

        .sidebar-user-info p {
            color: #64748b;
            font-size: 12px;
            margin: 0;
        }

        .sidebar-user-info p i {
            color: #fbbf24;
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
            color: #475569;
            text-decoration: none;
            border-radius: 12px;
            transition: all 0.3s ease;
            gap: 12px;
            font-weight: 500;
            font-size: 15px;
        }

        .sidebar-nav-link i {
            width: 22px;
            font-size: 1.1rem;
            color: #94a3b8;
            transition: all 0.3s ease;
        }

        .sidebar-nav-link:hover {
            background: #e0f2fe;
            color: #1e4a76;
        }

        .sidebar-nav-link:hover i {
            color: #2c7da0;
        }

        .sidebar-nav-link.active {
            background: #e0f2fe;
            color: #1e4a76;
            border-left: 3px solid #2c7da0;
        }

        .sidebar-nav-link.active i {
            color: #2c7da0;
        }

        .sidebar-badge {
            background: #ef4444;
            color: white;
            font-size: 10px;
            font-weight: 600;
            padding: 2px 6px;
            border-radius: 30px;
            margin-left: auto;
        }

        .sidebar-divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(0, 0, 0, 0.1), transparent);
            margin: 20px 20px;
        }

        .sidebar-section-title {
            padding: 0 20px;
            margin: 25px 0 10px 0;
            color: #64748b;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .sidebar-club-preview {
            background: #f8fafc;
            border-radius: 16px;
            padding: 15px;
            margin: 0 15px 20px 15px;
            border: 1px solid #e2e8f0;
        }

        .sidebar-club-preview h4 {
            color: #1e4a76;
            font-size: 13px;
            margin: 0 0 12px 0;
        }

        .sidebar-club-preview h4 i {
            color: #fbbf24;
        }

        .sidebar-club-item {
            background: white;
            border-radius: 12px;
            padding: 12px;
            margin-bottom: 10px;
            border: 1px solid #e2e8f0;
            transition: transform 0.2s;
        }

        .sidebar-club-item:hover {
            transform: translateX(5px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .sidebar-club-item h5 {
            color: #1e293b;
            font-size: 14px;
            margin: 0 0 4px 0;
            font-weight: 600;
        }

        .sidebar-club-item p {
            color: #64748b;
            font-size: 11px;
            margin: 0 0 6px 0;
        }

        .sidebar-club-tag {
            background: #e0f2fe;
            color: #1e4a76;
            font-size: 9px;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 30px;
            display: inline-block;
        }

        .sidebar-stats {
            display: flex;
            justify-content: space-around;
            padding: 15px 10px;
            margin: 0 15px;
            background: #f8fafc;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
        }

        .sidebar-stat-value {
            color: #1e4a76;
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 3px;
        }

        .sidebar-stat-label {
            color: #64748b;
            font-size: 10px;
            text-transform: uppercase;
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
            color: #64748b;
            text-decoration: none;
            font-size: 11px;
            transition: color 0.2s;
        }

        .sidebar-footer-links a:hover {
            color: #1e4a76;
        }

        .sidebar-copyright {
            color: #94a3b8;
            font-size: 10px;
            text-align: center;
        }

        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(3px);
            z-index: 9998;
            display: none;
        }

        .sidebar-overlay.active {
            display: block;
        }

        .sidebar::-webkit-scrollbar {
            width: 4px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: transparent;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 20px;
        }
        
        /* GAME CONTAINER */
        .game-container {
            padding: 30px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .game-hub {
            background: white;
            border-radius: 30px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid #e2e8f0;
        }
        
        .game-tabs {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 30px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .tab-btn {
            padding: 15px 30px;
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 15px;
            color: #475569;
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
            color: #2c7da0;
        }
        
        .tab-btn:hover {
            background: #e0f2fe;
            transform: translateY(-2px);
            border-color: #2c7da0;
        }
        
        .tab-btn.active {
            background: linear-gradient(135deg, #1e4a76 0%, #2c7da0 100%);
            border-color: transparent;
            color: white;
        }
        
        .tab-btn.active i {
            color: white;
        }
        
        .game-panel {
            display: none;
        }
        
        .game-panel.active {
            display: block;
        }
        
        .game-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .game-header h2 {
            color: #1e4a76;
        }
        
        .game-stats {
            display: flex;
            gap: 30px;
            color: #1e293b;
        }
        
        .stat-box {
            text-align: center;
            background: #f8fafc;
            padding: 10px 20px;
            border-radius: 15px;
            border: 1px solid #e2e8f0;
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: #2c7da0;
        }
        
        .stat-label {
            font-size: 12px;
            color: #64748b;
        }
        
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            margin: 10px;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #1e4a76 0%, #2c7da0 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(30, 74, 118, 0.3);
        }
        
        /* MEMORY GAME */
        .memory-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            max-width: 600px;
            margin: 40px auto;
        }
        
        .memory-card {
            aspect-ratio: 1;
            background: linear-gradient(135deg, #1e4a76 0%, #2c7da0 100%);
            border-radius: 15px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            color: white;
            transition: all 0.3s;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .memory-card:hover {
            transform: scale(1.05);
        }
        
        .memory-card.flipped {
            background: white;
            color: #2c7da0;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
            border: 2px solid #2c7da0;
        }
        
        .memory-card.matched {
            background: #10b981;
            color: white;
            cursor: default;
            opacity: 0.7;
            transform: none;
        }
        
        /* MATH GAME */
        .math-game {
            text-align: center;
            padding: 20px;
        }
        
        .question {
            font-size: 60px;
            color: #1e4a76;
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
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 15px;
            color: #1e293b;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 600;
        }
        
        .option-btn:hover {
            background: #e0f2fe;
            transform: scale(1.05);
            border-color: #2c7da0;
        }
        
        .option-btn.correct {
            background: #10b981;
            color: white;
            animation: correctPulse 0.5s;
        }
        
        .option-btn.wrong {
            background: #ef4444;
            color: white;
            animation: shake 0.5s;
        }
        
        .timer-bar {
            width: 100%;
            height: 10px;
            background: #e2e8f0;
            border-radius: 5px;
            margin: 20px 0;
            overflow: hidden;
        }
        
        .timer-fill {
            height: 100%;
            background: linear-gradient(90deg, #2c7da0, #1e4a76);
            width: 100%;
            transition: width 0.1s linear;
        }
        
        /* TIC-TAC-TOE */
        .tictactoe-board {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            max-width: 400px;
            margin: 30px auto;
        }
        
        .ttt-cell {
            aspect-ratio: 1;
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 60px;
            color: #1e4a76;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 700;
        }
        
        .ttt-cell:hover {
            background: #e0f2fe;
            transform: scale(1.05);
            border-color: #2c7da0;
        }
        
        .ttt-cell.winner {
            background: linear-gradient(135deg, #1e4a76 0%, #2c7da0 100%);
            color: white;
            animation: winnerPulse 1s infinite;
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
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 20px;
            color: #475569;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 500;
        }
        
        .mode-btn:hover {
            background: #e0f2fe;
            border-color: #2c7da0;
        }
        
        .mode-btn.active {
            background: linear-gradient(135deg, #1e4a76 0%, #2c7da0 100%);
            color: white;
            border-color: transparent;
        }
        
        .player-turn {
            display: inline-block;
            padding: 10px 20px;
            background: linear-gradient(135deg, #1e4a76 0%, #2c7da0 100%);
            border-radius: 30px;
            color: white;
            font-weight: 600;
        }
        
        .result-message {
            text-align: center;
            color: #2c7da0;
            margin-top: 20px;
            font-size: 18px;
            font-weight: 600;
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
        
        @keyframes winnerPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
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
            
            .game-container {
                padding: 20px;
            }
            
            .game-hub {
                padding: 20px;
            }
        }
    </style>
</head>
<body>

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
        
        <div id="memoryGame" class="game-panel active">
            <div class="game-header">
                <h2>🎴 Memory Card Game</h2>
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
            
            <div id="memoryResult" class="result-message"></div>
        </div>
        
        <div id="mathGame" class="game-panel">
            <div class="game-header">
                <h2>🧮 Quick Math Challenge</h2>
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
            
            <div id="mathResult" class="result-message"></div>
        </div>
        
        <div id="tictactoeGame" class="game-panel">
            <div class="game-header">
                <h2>🎮 Tic-Tac-Toe</h2>
                <div class="game-modes">
                    <button class="mode-btn active" onclick="setTTTMode('cpu', this)">vs CPU</button>
                    <button class="mode-btn" onclick="setTTTMode('friend', this)">vs Friend</button>
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
            
            <div id="tttResult" class="result-message"></div>
        </div>
    </div>
</div>

<script>
function toggleSidebar() {
    const sidebar = document.querySelector(".sidebar");
    const overlay = document.getElementById("sidebarOverlay");
    const menuBtn = document.querySelector(".menu-btn");
    
    if(sidebar.classList.contains("active")) {
        sidebar.classList.remove("active");
        overlay.classList.remove("active");
        menuBtn.classList.remove("active");
    } else {
        sidebar.classList.add("active");
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
       sidebar.classList.contains("active")) {
        sidebar.classList.remove("active");
        overlay.classList.remove("active");
        btn.classList.remove("active");
    }
});

function switchGame(game) {
    document.getElementById('memoryGame').classList.remove('active');
    document.getElementById('mathGame').classList.remove('active');
    document.getElementById('tictactoeGame').classList.remove('active');
    
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
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

// Memory Game Logic
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
                    document.getElementById('memoryResult').innerHTML = `🎉 Congratulations! You won ${points} points!`;
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

// Math Game Logic
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

// Tic-Tac-Toe Logic
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
    document.getElementById('tttTurn').innerHTML = tttMode === 'cpu' ? `Your turn (${tttPlayer})` : `Player ${tttPlayer}'s turn`;
}

function makeTTTMove(pos) {
    if (!tttActive || tttGameOver) return;
    if (tttBoard[pos] !== '') return;
    if (tttMode === 'cpu' && tttPlayer === 'O') return;
    
    tttBoard[pos] = tttPlayer;
    document.getElementById(`ttt-cell-${pos}`).textContent = tttPlayer;
    
    if (checkTTTWin()) {
        endTTT(tttPlayer);
        return;
    }
    
    if (checkTTTDraw()) {
        endTTT('draw');
        return;
    }
    
    tttPlayer = tttPlayer === 'X' ? 'O' : 'X';
    document.getElementById('tttTurn').innerHTML = tttMode === 'cpu' ? (tttPlayer === 'X' ? 'Your turn (X)' : 'CPU turn (O)') : `Player ${tttPlayer}'s turn`;
    
    if (tttMode === 'cpu' && tttPlayer === 'O' && tttActive && !tttGameOver) {
        setTimeout(makeCPUTTTMove, 600);
    }
}

function makeCPUTTTMove() {
    if (!tttActive || tttGameOver) return;
    if (tttPlayer !== 'O') return;
    
    let emptyCells = [];
    tttBoard.forEach((cell, index) => {
        if (cell === '') emptyCells.push(index);
    });
    
    if (emptyCells.length === 0) return;
    
    let moveIndex = getBestCPUMove(emptyCells);
    
    tttBoard[moveIndex] = 'O';
    document.getElementById(`ttt-cell-${moveIndex}`).textContent = 'O';
    
    if (checkTTTWin()) {
        endTTT('O');
        return;
    }
    
    if (checkTTTDraw()) {
        endTTT('draw');
        return;
    }
    
    tttPlayer = 'X';
    document.getElementById('tttTurn').innerHTML = 'Your turn (X)';
}

function getBestCPUMove(emptyCells) {
    for (let i = 0; i < emptyCells.length; i++) {
        let pos = emptyCells[i];
        tttBoard[pos] = 'O';
        if (checkTTTWin()) {
            tttBoard[pos] = '';
            return pos;
        }
        tttBoard[pos] = '';
    }
    
    for (let i = 0; i < emptyCells.length; i++) {
        let pos = emptyCells[i];
        tttBoard[pos] = 'X';
        if (checkTTTWin()) {
            tttBoard[pos] = '';
            return pos;
        }
        tttBoard[pos] = '';
    }
    
    if (emptyCells.includes(4)) return 4;
    
    let corners = [0, 2, 6, 8].filter(pos => emptyCells.includes(pos));
    if (corners.length > 0) {
        return corners[Math.floor(Math.random() * corners.length)];
    }
    
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

function setTTTMode(mode, btnElement) {
    tttMode = mode;
    document.querySelectorAll('#tictactoeGame .mode-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    btnElement.classList.add('active');
    resetTTT();
}

function resetTTT() {
    initTTT();
}

// Utility Functions
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

window.onload = function() {
    initMemoryGame();
};
</script>

</body>
</html>