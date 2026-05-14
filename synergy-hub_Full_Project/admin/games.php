<?php
// admin/games.php - Game Management Page
require_once '../config.php';
require_once '../functions.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || $_SESSION['user_role'] != 'Admin') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'dashboard';

// Get game statistics
$stats = [];

// Total games played
$total_games_sql = "SELECT COUNT(*) as total FROM GameField";
$total_games_result = mysqli_query($conn, $total_games_sql);
$stats['total_games'] = mysqli_fetch_assoc($total_games_result)['total'] ?? 0;

// Total points earned from games
$points_earned_sql = "SELECT SUM(PointsEarned) as total FROM GameField";
$points_earned_result = mysqli_query($conn, $points_earned_sql);
$stats['points_earned'] = mysqli_fetch_assoc($points_earned_result)['total'] ?? 0;

// Total points used in games
$points_used_sql = "SELECT SUM(PointsUsed) as total FROM GameField";
$points_used_result = mysqli_query($conn, $points_used_sql);
$stats['points_used'] = mysqli_fetch_assoc($points_used_result)['total'] ?? 0;

// Unique players
$unique_players_sql = "SELECT COUNT(DISTINCT UserID) as total FROM GameField";
$unique_players_result = mysqli_query($conn, $unique_players_sql);
$stats['unique_players'] = mysqli_fetch_assoc($unique_players_result)['total'] ?? 0;

// Total users
$total_users_sql = "SELECT COUNT(*) as total FROM Users WHERE Role = 'User'";
$total_users_result = mysqli_query($conn, $total_users_sql);
$stats['total_users'] = mysqli_fetch_assoc($total_users_result)['total'] ?? 0;

// Games by type
$games_by_type_sql = "SELECT GameType, COUNT(*) as count, SUM(PointsEarned) as points 
                      FROM GameField 
                      GROUP BY GameType 
                      ORDER BY count DESC";
$games_by_type_result = mysqli_query($conn, $games_by_type_sql);

// Recent games
$recent_games_sql = "SELECT g.*, u.Name as user_name 
                     FROM GameField g
                     JOIN Users u ON g.UserID = u.UserID
                     ORDER BY g.Timestamp DESC 
                     LIMIT 10";
$recent_games_result = mysqli_query($conn, $recent_games_sql);

// Top players
$top_players_sql = "SELECT u.UserID, u.Name, u.PointsBalance,
                    SUM(g.PointsEarned) as total_points_earned,
                    COUNT(g.GameID) as games_played
                    FROM GameField g
                    JOIN Users u ON g.UserID = u.UserID
                    GROUP BY u.UserID, u.Name, u.PointsBalance
                    ORDER BY total_points_earned DESC
                    LIMIT 5";
$top_players_result = mysqli_query($conn, $top_players_sql);

// Daily game activity (last 7 days)
$daily_activity_sql = "SELECT DATE(Timestamp) as date, COUNT(*) as games_played, SUM(PointsEarned) as points_earned
                       FROM GameField
                       WHERE Timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                       GROUP BY DATE(Timestamp)
                       ORDER BY date ASC";
$daily_activity_result = mysqli_query($conn, $daily_activity_sql);

// Get game configurations from pointsconfig
$game_configs = [];
$config_sql = "SELECT ActionType, Points, MaxPerDay FROM pointsconfig WHERE ActionType LIKE 'GAME_%'";
$config_result = mysqli_query($conn, $config_sql);
while($row = mysqli_fetch_assoc($config_result)) {
    $game_configs[str_replace('GAME_', '', $row['ActionType'])] = [
        'points' => $row['Points'],
        'max_per_day' => $row['MaxPerDay']
    ];
}

// Handle configuration update
$message = '';
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_config'])) {
    $game_name = $_POST['game_name'];
    $base_points = intval($_POST['base_points']);
    $max_per_day = intval($_POST['max_per_day']) ?: null;
    
    $action_type = 'GAME_' . strtoupper(str_replace(' ', '_', $game_name));
    
    $check_sql = "SELECT ConfigID FROM pointsconfig WHERE ActionType = ?";
    $check_stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($check_stmt, "s", $action_type);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    
    if (mysqli_num_rows($check_result) > 0) {
        $update_sql = "UPDATE pointsconfig SET Points = ?, MaxPerDay = ? WHERE ActionType = ?";
        $update_stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($update_stmt, "iis", $base_points, $max_per_day, $action_type);
        mysqli_stmt_execute($update_stmt);
    } else {
        $insert_sql = "INSERT INTO pointsconfig (ActionType, Points, MaxPerDay, Description) VALUES (?, ?, ?, ?)";
        $insert_stmt = mysqli_prepare($conn, $insert_sql);
        $description = "Points earned from playing $game_name";
        mysqli_stmt_bind_param($insert_stmt, "siis", $action_type, $base_points, $max_per_day, $description);
        mysqli_stmt_execute($insert_stmt);
    }
    
    $message = '<div class="success-message">✅ Configuration saved successfully!</div>';
}

// Handle adding points to player
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_points'])) {
    $player_id = intval($_POST['player_id']);
    $points = intval($_POST['points']);
    $reason = mysqli_real_escape_string($conn, $_POST['reason']);
    
    // Update user points
    $update_sql = "UPDATE Users SET PointsBalance = PointsBalance + ? WHERE UserID = ?";
    $update_stmt = mysqli_prepare($conn, $update_sql);
    mysqli_stmt_bind_param($update_stmt, "ii", $points, $player_id);
    mysqli_stmt_execute($update_stmt);
    
    // Log the transaction
    $history_sql = "INSERT INTO pointsHistory (UserID, PointsChange, ActionType, Description) 
                    VALUES (?, ?, 'ADMIN_ADJUSTMENT', ?)";
    $history_stmt = mysqli_prepare($conn, $history_sql);
    $desc = "Admin adjustment: $reason";
    mysqli_stmt_bind_param($history_stmt, "iis", $player_id, $points, $desc);
    mysqli_stmt_execute($history_stmt);
    
    $message = '<div class="success-message">✅ Points added successfully!</div>';
}

// Default games list
$available_games = [
    ['name' => 'Memory Game', 'icon' => 'fa-cards', 'min_points' => 5, 'max_points' => 50],
    ['name' => 'Math Game', 'icon' => 'fa-calculator', 'min_points' => 10, 'max_points' => 200],
    ['name' => 'Tic-Tac-Toe', 'icon' => 'fa-grid-2', 'min_points' => 5, 'max_points' => 20],
    ['name' => 'Quiz Challenge', 'icon' => 'fa-question-circle', 'min_points' => 10, 'max_points' => 100],
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Game Management - Synergy Hub Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
        }

        body {
            background: #f1f5f9;
            min-height: 100vh;
        }

        /* Admin Layout */
        .admin-wrapper {
            display: flex;
        }

        /* Sidebar Styles - DARK */
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, #0f172a 0%, #0a0f1a 100%);
            min-height: 100vh;
            position: sticky;
            top: 0;
            border-right: 1px solid #1e293b;
        }

        .sidebar-header {
            padding: 24px 20px;
            border-bottom: 1px solid #1e293b;
        }

        .sidebar-header h2 {
            color: white;
            font-size: 24px;
        }

        .sidebar-header h2 span {
            color: #22d3ee;
        }

        .sidebar-header p {
            color: #64748b;
            font-size: 12px;
            margin-top: 5px;
        }

        .sidebar-nav {
            padding: 20px 0;
        }

        .nav-section {
            margin-bottom: 20px;
        }

        .nav-section-title {
            padding: 8px 20px;
            color: #64748b;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .sidebar-nav a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 20px;
            color: #94a3b8;
            text-decoration: none;
            transition: all 0.3s;
            font-size: 14px;
        }

        .sidebar-nav a i {
            width: 20px;
            font-size: 16px;
        }

        .sidebar-nav a:hover {
            background: rgba(34, 211, 238, 0.1);
            color: #22d3ee;
        }

        .sidebar-nav a.active {
            background: rgba(34, 211, 238, 0.15);
            color: #22d3ee;
            border-left: 3px solid #22d3ee;
        }

        .sidebar-footer {
            padding: 20px;
            border-top: 1px solid #1e293b;
            margin-top: 20px;
        }

        .sidebar-footer a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px;
            color: #64748b;
            text-decoration: none;
            border-radius: 8px;
        }

        .sidebar-footer a:hover {
            background: rgba(34, 211, 238, 0.1);
            color: #22d3ee;
        }

        /* Main Content - WHITE BACKGROUND */
        .main-content {
            flex: 1;
            padding: 24px 32px;
            background: #f1f5f9;
        }

        /* Top Bar */
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background: white;
            padding: 16px 24px;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .page-title h1 {
            color: #1e293b;
            font-size: 24px;
        }

        .page-title p {
            color: #64748b;
            margin-top: 5px;
            font-size: 14px;
        }

        .admin-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .points-badge {
            background: #f8fafc;
            padding: 8px 16px;
            border-radius: 30px;
            color: #1e293b;
            border: 1px solid #e2e8f0;
        }

        .points-badge i {
            color: #fbbf24;
        }

        .logout-btn {
            background: #ef4444;
            padding: 8px 20px;
            border-radius: 8px;
            color: white;
            text-decoration: none;
            font-size: 14px;
        }

        .logout-btn:hover {
            background: #dc2626;
        }

        /* Tabs */
        .tabs {
            display: flex;
            gap: 5px;
            background: white;
            padding: 5px;
            border-radius: 12px;
            margin-bottom: 25px;
            width: fit-content;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        .tab-btn {
            padding: 10px 24px;
            background: transparent;
            border: none;
            color: #64748b;
            cursor: pointer;
            border-radius: 8px;
            transition: all 0.3s;
            font-weight: 500;
        }

        .tab-btn.active {
            background: #1e4a76;
            color: white;
        }

        .tab-btn i {
            margin-right: 8px;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        .stat-card .icon {
            font-size: 32px;
            color: #1e4a76;
            margin-bottom: 12px;
        }

        .stat-card .value {
            font-size: 28px;
            font-weight: 700;
            color: #1e293b;
        }

        .stat-card .label {
            color: #64748b;
            font-size: 13px;
            margin-top: 5px;
        }

        /* Cards */
        .card {
            background: white;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            margin-bottom: 30px;
            overflow: hidden;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        .card-header {
            padding: 16px 20px;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-header h3 {
            color: #1e293b;
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .card-header h3 i {
            color: #1e4a76;
        }

        /* Tables */
        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px 16px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
            color: #475569;
        }

        th {
            background: #f8fafc;
            color: #64748b;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
        }

        tr:hover td {
            background: #f8fafc;
        }

        /* Game Types Grid */
        .game-types-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin: 20px;
        }

        .game-card {
            background: #f8fafc;
            border-radius: 16px;
            padding: 20px;
            text-align: center;
            border: 1px solid #e2e8f0;
            transition: all 0.3s;
        }

        .game-card:hover {
            border-color: #1e4a76;
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .game-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #1e4a76, #2c7da0);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 28px;
            color: white;
        }

        .game-name {
            color: #1e293b;
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .game-stats {
            color: #64748b;
            font-size: 12px;
            margin-bottom: 15px;
        }

        .game-config {
            display: inline-block;
            padding: 6px 16px;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            color: #1e4a76;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .game-config:hover {
            background: #1e4a76;
            color: white;
            border-color: #1e4a76;
        }

        /* Chart Bars */
        .chart-container {
            padding: 20px;
        }

        .chart-bars {
            display: flex;
            align-items: flex-end;
            gap: 20px;
            height: 200px;
        }

        .chart-bar-wrapper {
            flex: 1;
            text-align: center;
        }

        .chart-bar {
            background: linear-gradient(180deg, #1e4a76, #2c7da0);
            border-radius: 8px 8px 0 0;
            transition: height 0.3s;
            position: relative;
        }

        .chart-label {
            margin-top: 10px;
            color: #64748b;
            font-size: 11px;
        }

        /* Buttons */
        .btn-secondary {
            padding: 6px 12px;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            color: #475569;
            cursor: pointer;
            font-size: 12px;
            text-decoration: none;
        }

        .btn-secondary:hover {
            background: #e2e8f0;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.show {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 20px;
            width: 450px;
            max-width: 90%;
            border: 1px solid #e2e8f0;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }

        .modal-header {
            padding: 20px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            color: #1e293b;
        }

        .modal-close {
            background: none;
            border: none;
            color: #94a3b8;
            font-size: 24px;
            cursor: pointer;
        }

        .modal-body {
            padding: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            color: #475569;
            margin-bottom: 8px;
            font-size: 13px;
            font-weight: 500;
        }

        .form-group input, .form-group select {
            width: 100%;
            padding: 10px 12px;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            color: #1e293b;
        }

        .form-group input:focus {
            outline: none;
            border-color: #1e4a76;
            box-shadow: 0 0 0 2px rgba(30, 74, 118, 0.1);
        }

        .btn-primary {
            padding: 10px 20px;
            background: linear-gradient(135deg, #1e4a76, #2c7da0);
            border: none;
            border-radius: 8px;
            color: white;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(30, 74, 118, 0.2);
        }

        .success-message {
            background: #10b981;
            color: white;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .text-center {
            text-align: center;
        }

        .rank-badge {
            background: #fbbf24;
            color: #1e293b;
            padding: 2px 8px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }

        .search-input {
            padding: 6px 12px;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            color: #1e293b;
            width: 200px;
        }

        .search-input:focus {
            outline: none;
            border-color: #1e4a76;
        }

        @media (max-width: 1024px) {
            .stats-grid, .game-types-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                display: none;
            }
            .stats-grid, .game-types-grid {
                grid-template-columns: 1fr;
            }
            .main-content {
                padding: 16px;
            }
        }
    </style>
</head>
<body>
<div class="admin-wrapper">
    <!-- Sidebar - DARK -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>Synergy <span>Hub</span></h2>
            <p>Admin Panel</p>
        </div>
        
        <nav class="sidebar-nav">
            <div class="nav-section">
                <div class="nav-section-title">Main</div>
                <a href="index.php"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>
            </div>
            
            <div class="nav-section">
                <div class="nav-section-title">User Management</div>
                <a href="users.php"><i class="fa-solid fa-users"></i> All Users</a>
                <a href="points.php"><i class="fa-solid fa-star"></i> Points System</a>
                <a href="user_stats.php"><i class="fa-solid fa-chart-line"></i> User Statistics</a>
                <a href="login_history.php"><i class="fa-solid fa-clock-rotate-left"></i> Login History</a>
            </div>
            
            <div class="nav-section">
                <div class="nav-section-title">🎮 Game Management</div>
                <a href="games.php" class="active"><i class="fa-solid fa-gamepad"></i> Game Dashboard</a>
                <a href="games.php?tab=players"><i class="fa-solid fa-ranking-star"></i> Players Stats</a>
                <a href="games.php?tab=config"><i class="fa-solid fa-gear"></i> Game Configuration</a>
            </div>
            
            <div class="nav-section">
                <div class="nav-section-title">Club Management</div>
                <a href="club_management.php"><i class="fa-solid fa-users"></i> Manage Clubs</a>
                <a href="club_management.php?tab=requests"><i class="fa-solid fa-ticket"></i> Join Requests</a>
            </div>
            
            <div class="nav-section">
                <div class="nav-section-title">Events</div>
                <a href="admin_events.php"><i class="fa-solid fa-calendar-alt"></i> Manage Events</a>
            </div>
            
            <div class="nav-section">
                <div class="nav-section-title">Facility Management</div>
                <a href="facility_management.php"><i class="fa-solid fa-building"></i> All Facilities</a>
                <a href="facility_management.php?tab=add"><i class="fa-solid fa-plus-circle"></i> Add Facility</a>
                <a href="facility_management.php?tab=crowd"><i class="fa-solid fa-users"></i> Crowd Management</a>
            </div>
            
            <div class="nav-section">
                <div class="nav-section-title">Café Management</div>
                <a href="cafe_menu_admin.php"><i class="fa-solid fa-mug-saucer"></i> Menu Items</a>
                <a href="cafe_menu_admin.php?tab=offers"><i class="fa-solid fa-tag"></i> Special Offers</a>
            </div>
            
            <div class="nav-section">
                <div class="nav-section-title">Library Management</div>
                <a href="library_management.php"><i class="fa-solid fa-book"></i> Books</a>
                <a href="library_management.php?tab=rooms"><i class="fa-solid fa-door-open"></i> Study Rooms</a>
            </div>
            
            <div class="nav-section">
                <div class="nav-section-title">Pool Management</div>
                <a href="pool_management.php"><i class="fa-solid fa-person-swimming"></i> Pool Dashboard</a>
                <a href="pool_management.php?tab=medical"><i class="fa-solid fa-notes-medical"></i> Medical Reports</a>
                <a href="pool_management.php?tab=bookings"><i class="fa-solid fa-clock"></i> Lane Bookings</a>
            </div>
            
            <div class="nav-section">
                <div class="nav-section-title">Gym Management</div>
                <a href="gym_management.php"><i class="fa-solid fa-dumbbell"></i> Gym Manager</a>
            </div>
            
            <div class="nav-section">
                <div class="nav-section-title">Transport</div>
                <a href="transport_management.php"><i class="fa-solid fa-bus"></i> Transport Manager</a>
            </div>
            
            <div class="nav-section">
                <div class="nav-section-title">Orders</div>
                <a href="admin_orders.php"><i class="fa-solid fa-cart-shopping"></i> Manage Orders</a>
                <a href="admin_kitchen.php"><i class="fa-solid fa-utensils"></i> Kitchen Display</a>
            </div>
            
            <div class="nav-section">
                <div class="nav-section-title">Communications</div>
                <a href="notifications.php"><i class="fa-solid fa-bell"></i> Notifications</a>
            </div>
            
            <div class="nav-section">
                <div class="nav-section-title">System</div>
                <a href="settings.php"><i class="fa-solid fa-gear"></i> Settings</a>
            </div>
        </nav>
        
        <div class="sidebar-footer">
            <a href="../index.php" target="_blank"><i class="fa-solid fa-eye"></i> View Site</a>
            <a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        </div>
    </div>
    
    <!-- Main Content - WHITE BACKGROUND -->
    <div class="main-content">
        <div class="top-bar">
            <div class="page-title">
                <h1><i class="fa-solid fa-gamepad"></i> Game Management</h1>
                <p>Monitor and configure mini-games, view player statistics</p>
            </div>
            <div class="admin-info">
                <div class="points-badge">
                    <i class="fa-solid fa-star"></i> <?php echo getUserPoints($conn, $user_id); ?> points
                </div>
                <a href="../logout.php" class="logout-btn"><i class="fa-solid fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
        
        <?php echo $message; ?>
        
        <!-- Tabs -->
        <div class="tabs">
            <button class="tab-btn <?php echo $active_tab == 'dashboard' ? 'active' : ''; ?>" onclick="window.location.href='?tab=dashboard'">
                <i class="fa-solid fa-chart-line"></i> Dashboard
            </button>
            <button class="tab-btn <?php echo $active_tab == 'players' ? 'active' : ''; ?>" onclick="window.location.href='?tab=players'">
                <i class="fa-solid fa-users"></i> Players
            </button>
            <button class="tab-btn <?php echo $active_tab == 'config' ? 'active' : ''; ?>" onclick="window.location.href='?tab=config'">
                <i class="fa-solid fa-gear"></i> Configuration
            </button>
        </div>
        
        <?php if ($active_tab == 'dashboard'): ?>
        <!-- Dashboard Tab -->
        
        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="icon"><i class="fa-solid fa-gamepad"></i></div>
                <div class="value"><?php echo number_format($stats['total_games']); ?></div>
                <div class="label">Total Games Played</div>
            </div>
            <div class="stat-card">
                <div class="icon"><i class="fa-solid fa-star"></i></div>
                <div class="value"><?php echo number_format($stats['points_earned']); ?></div>
                <div class="label">Points Earned</div>
            </div>
            <div class="stat-card">
                <div class="icon"><i class="fa-solid fa-users"></i></div>
                <div class="value"><?php echo $stats['unique_players']; ?></div>
                <div class="label">Active Players</div>
            </div>
            <div class="stat-card">
                <div class="icon"><i class="fa-solid fa-percent"></i></div>
                <div class="value"><?php echo $stats['total_users'] > 0 ? round(($stats['unique_players'] / $stats['total_users']) * 100) : 0; ?>%</div>
                <div class="label">Player Engagement</div>
            </div>
        </div>
        
        <!-- Games by Type -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fa-solid fa-chart-pie"></i> Games by Type</h3>
            </div>
            <div class="game-types-grid" style="margin: 0;">
                <?php 
                mysqli_data_seek($games_by_type_result, 0);
                $game_icons = [
                    'Memory Game' => 'fa-cards',
                    'Math Game' => 'fa-calculator',
                    'Tic-Tac-Toe' => 'fa-grid-2',
                    'Quiz Challenge' => 'fa-question-circle',
                ];
                if(mysqli_num_rows($games_by_type_result) > 0):
                while($type = mysqli_fetch_assoc($games_by_type_result)): 
                    $icon = $game_icons[$type['GameType']] ?? 'fa-gamepad';
                ?>
                <div class="game-card">
                    <div class="game-icon"><i class="fa-solid <?php echo $icon; ?>"></i></div>
                    <div class="game-name"><?php echo htmlspecialchars($type['GameType']); ?></div>
                    <div class="game-stats">
                        <?php echo number_format($type['count']); ?> plays • <?php echo number_format($type['points']); ?> pts
                    </div>
                    <span class="game-config" onclick="openConfigModal('<?php echo $type['GameType']; ?>')">
                        <i class="fa-solid fa-gear"></i> Configure
                    </span>
                </div>
                <?php endwhile; else: ?>
                <div class="text-center" style="padding: 40px; grid-column: span 4;">No game data available yet</div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Daily Activity Chart -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fa-solid fa-chart-line"></i> Daily Game Activity (Last 7 Days)</h3>
            </div>
            <div class="chart-container">
                <?php
                $daily_data = [];
                $max_games = 1;
                while($day = mysqli_fetch_assoc($daily_activity_result)) {
                    $daily_data[] = $day;
                    $max_games = max($max_games, $day['games_played']);
                }
                $max_games = max($max_games, 5);
                ?>
                <?php if(!empty($daily_data)): ?>
                <div class="chart-bars">
                    <?php foreach($daily_data as $day): 
                        $height = ($day['games_played'] / $max_games) * 160;
                        $date_display = date('M d', strtotime($day['date']));
                    ?>
                    <div class="chart-bar-wrapper">
                        <div class="chart-bar" style="height: <?php echo max(20, $height); ?>px;">
                            <div style="position: absolute; top: -22px; left: 50%; transform: translateX(-50%); font-size: 11px; color: #1e4a76; white-space: nowrap;">
                                <?php echo $day['points_earned']; ?> pts
                            </div>
                        </div>
                        <div class="chart-label">
                            <?php echo $date_display; ?><br>
                            <small><?php echo $day['games_played']; ?> games</small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="text-center" style="padding: 40px; color: #64748b;">No game activity in the last 7 days</div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Top Players & Recent Games -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <!-- Top Players -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fa-solid fa-trophy"></i> Top Players</h3>
                    <a href="?tab=players" class="btn-secondary" style="text-decoration: none;">View All →</a>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr><th>Rank</th><th>Player</th><th>Games</th><th>Points</th></tr>
                        </thead>
                        <tbody>
                            <?php 
                            $rank = 1;
                            mysqli_data_seek($top_players_result, 0);
                            if(mysqli_num_rows($top_players_result) > 0):
                            while($player = mysqli_fetch_assoc($top_players_result)): 
                            ?>
                            <tr>
                                <td><span class="rank-badge">#<?php echo $rank++; ?></span></td>
                                <td><?php echo htmlspecialchars($player['Name']); ?></td>
                                <td><?php echo $player['games_played']; ?></td>
                                <td><span style="color: #fbbf24;">⭐</span> <?php echo number_format($player['total_points_earned']); ?></td>
                            </tr>
                            <?php endwhile; else: ?>
                            <tr><td colspan="4" class="text-center" style="padding: 30px;">No data available</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Recent Games -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fa-solid fa-clock"></i> Recent Games</h3>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr><th>Time</th><th>Player</th><th>Game</th><th>Points</th></tr>
                        </thead>
                        <tbody>
                            <?php 
                            mysqli_data_seek($recent_games_result, 0);
                            if(mysqli_num_rows($recent_games_result) > 0):
                            while($game = mysqli_fetch_assoc($recent_games_result)): 
                            ?>
                            <tr>
                                <td><?php echo date('M d, H:i', strtotime($game['Timestamp'])); ?></td>
                                <td><?php echo htmlspecialchars($game['user_name']); ?></td>
                                <td><?php echo htmlspecialchars($game['GameType']); ?></td>
                                <td><span style="color: #10b981;">+<?php echo $game['PointsEarned']; ?></span></td>
                            </tr>
                            <?php endwhile; else: ?>
                            <tr><td colspan="4" class="text-center" style="padding: 30px;">No recent games</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <?php elseif ($active_tab == 'players'): ?>
        <!-- Players Tab -->
        
        <div class="card">
            <div class="card-header">
                <h3><i class="fa-solid fa-users"></i> All Players</h3>
                <div>
                    <input type="text" id="playerSearch" class="search-input" placeholder="Search players...">
                </div>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr><th>ID</th><th>Name</th><th>Email</th><th>Games</th><th>Points Earned</th><th>Balance</th><th>Last Played</th><th>Actions</th></tr>
                    </thead>
                    <tbody id="playersTableBody">
                        <?php
                        $all_players_sql = "SELECT u.UserID, u.Name, u.Email, u.PointsBalance,
                                            COUNT(g.GameID) as games_played,
                                            SUM(g.PointsEarned) as points_earned,
                                            MAX(g.Timestamp) as last_played
                                            FROM Users u
                                            LEFT JOIN GameField g ON u.UserID = g.UserID
                                            WHERE u.Role = 'User'
                                            GROUP BY u.UserID, u.Name, u.Email, u.PointsBalance
                                            ORDER BY points_earned DESC";
                        $all_players_result = mysqli_query($conn, $all_players_sql);
                        while($player = mysqli_fetch_assoc($all_players_result)):
                        ?>
                        <tr>
                            <td>#<?php echo $player['UserID']; ?></td>
                            <td><?php echo htmlspecialchars($player['Name']); ?></td>
                            <td><?php echo htmlspecialchars($player['Email']); ?></td>
                            <td><?php echo $player['games_played'] ?: 0; ?></td>
                            <td><span style="color: #10b981;">+<?php echo number_format($player['points_earned'] ?: 0); ?></span></td>
                            <td><span style="color: #fbbf24;">⭐ <?php echo number_format($player['PointsBalance']); ?></span></td>
                            <td><?php echo $player['last_played'] ? date('M d, H:i', strtotime($player['last_played'])) : 'Never'; ?></td>
                            <td>
                                <button class="btn-secondary" style="padding: 4px 10px;" onclick="openAddPointsModal(<?php echo $player['UserID']; ?>, '<?php echo htmlspecialchars($player['Name']); ?>')">
                                    <i class="fa-solid fa-plus"></i> Add Points
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <?php elseif ($active_tab == 'config'): ?>
        <!-- Configuration Tab -->
        
        <div class="card">
            <div class="card-header">
                <h3><i class="fa-solid fa-gear"></i> Game Points Configuration</h3>
            </div>
            <div class="table-responsive">
                <form method="POST" action="">
                    <table>
                        <thead>
                            <tr><th>Game Name</th><th>Base Points</th><th>Max Plays/Day</th><th>Range</th><th>Action</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach($available_games as $game): 
                                $points = $game_configs[$game['name']]['points'] ?? $game['min_points'];
                                $max_per_day = $game_configs[$game['name']]['max_per_day'] ?? '';
                            ?>
                            <tr>
                                <td>
                                    <?php echo $game['name']; ?>
                                    <input type="hidden" name="game_name" value="<?php echo $game['name']; ?>">
                                 </td>
                                <td>
                                    <input type="number" name="base_points" value="<?php echo $points; ?>" min="1" max="500" style="width: 100px; padding: 8px; border: 1px solid #e2e8f0; border-radius: 6px;">
                                 </td>
                                <td>
                                    <input type="number" name="max_per_day" value="<?php echo $max_per_day; ?>" min="1" max="100" placeholder="Unlimited" style="width: 100px; padding: 8px; border: 1px solid #e2e8f0; border-radius: 6px;">
                                 </td>
                                <td>
                                    <?php echo $game['min_points']; ?> - <?php echo $game['max_points']; ?> points
                                 </td>
                                <td>
                                    <button type="submit" name="update_config" class="btn-secondary">Save</button>
                                 </td>
                             </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </form>
            </div>
        </div>
        
        <?php endif; ?>
    </div>
</div>

<!-- Add Points Modal -->
<div id="addPointsModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fa-solid fa-plus-circle"></i> Add Points to Player</h3>
            <button class="modal-close" onclick="closeAddPointsModal()">&times;</button>
        </div>
        <form method="POST" action="">
            <div class="modal-body">
                <input type="hidden" name="player_id" id="modal_player_id">
                <div class="form-group">
                    <label>Player Name</label>
                    <input type="text" id="modal_player_name" readonly style="background: #f1f5f9;">
                </div>
                <div class="form-group">
                    <label>Points to Add</label>
                    <input type="number" name="points" required min="1" max="1000">
                </div>
                <div class="form-group">
                    <label>Reason</label>
                    <input type="text" name="reason" placeholder="e.g., Bonus, Reward, Compensation" required>
                </div>
                <button type="submit" name="add_points" class="btn-primary">Add Points</button>
            </div>
        </form>
    </div>
</div>

<script>
function openConfigModal(gameName) {
    alert('Configure ' + gameName + '\n\nGo to Configuration tab to adjust points settings.');
    window.location.href = '?tab=config';
}

function openAddPointsModal(userId, userName) {
    document.getElementById('modal_player_id').value = userId;
    document.getElementById('modal_player_name').value = userName;
    document.getElementById('addPointsModal').classList.add('show');
}

function closeAddPointsModal() {
    document.getElementById('addPointsModal').classList.remove('show');
}

// Search functionality
document.getElementById('playerSearch')?.addEventListener('keyup', function() {
    let searchValue = this.value.toLowerCase();
    let rows = document.querySelectorAll('#playersTableBody tr');
    
    rows.forEach(row => {
        let text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchValue) ? '' : 'none';
    });
});
</script>

</body>
</html>