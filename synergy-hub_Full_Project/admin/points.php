<?php
require_once 'middleware.php';
require_once 'config.php';
checkAdminAuth();

$admin = getAdminInfo($conn);

// Handle actions
$message = '';
$error = '';

// Update points config
if (isset($_POST['update_config'])) {
    foreach ($_POST['points'] as $action => $points) {
        // Check if config exists
        $check_sql = "SELECT ConfigID FROM PointsConfig WHERE ActionType = ?";
        $check_stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($check_stmt, "s", $action);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);
        
        if (mysqli_num_rows($check_result) > 0) {
            $update_sql = "UPDATE PointsConfig SET Points = ? WHERE ActionType = ?";
            $update_stmt = mysqli_prepare($conn, $update_sql);
            mysqli_stmt_bind_param($update_stmt, "is", $points, $action);
        } else {
            $update_sql = "INSERT INTO PointsConfig (ActionType, Points) VALUES (?, ?)";
            $update_stmt = mysqli_prepare($conn, $update_sql);
            mysqli_stmt_bind_param($update_stmt, "si", $action, $points);
        }
        mysqli_stmt_execute($update_stmt);
    }
    $message = "Points configuration updated successfully!";
    logAdminActivity($conn, 'UPDATE_POINTS_CONFIG', 'Updated points configuration');
}

// Add manual points adjustment
if (isset($_POST['adjust_points'])) {
    $user_id = intval($_POST['user_id']);
    $points = intval($_POST['points']);
    $action_type = mysqli_real_escape_string($conn, $_POST['action_type']);
    $reason = mysqli_real_escape_string($conn, $_POST['reason']);
    
    // Get current points
    $user_sql = "SELECT PointsBalance FROM Users WHERE UserID = ?";
    $user_stmt = mysqli_prepare($conn, $user_sql);
    mysqli_stmt_bind_param($user_stmt, "i", $user_id);
    mysqli_stmt_execute($user_stmt);
    $user_result = mysqli_stmt_get_result($user_stmt);
    $user = mysqli_fetch_assoc($user_result);
    
    if ($user) {
        $points_before = $user['PointsBalance'];
        $points_after = $points_before + $points;
        
        if ($points_after >= 0) {
            // Start transaction
            mysqli_begin_transaction($conn);
            
            try {
                // Update user points
                $update_sql = "UPDATE Users SET PointsBalance = PointsBalance + ? WHERE UserID = ?";
                $update_stmt = mysqli_prepare($conn, $update_sql);
                mysqli_stmt_bind_param($update_stmt, "ii", $points, $user_id);
                mysqli_stmt_execute($update_stmt);
                
                // Log in points history
                $history_sql = "INSERT INTO PointsHistory (UserID, PointsChange, ActionType, Description) 
                               VALUES (?, ?, ?, ?)";
                $history_stmt = mysqli_prepare($conn, $history_sql);
                $description = $action_type . " - " . $reason;
                mysqli_stmt_bind_param($history_stmt, "iiss", $user_id, $points, $action_type, $description);
                mysqli_stmt_execute($history_stmt);
                
                mysqli_commit($conn);
                $message = "Points adjusted successfully!";
                logAdminActivity($conn, 'ADJUST_POINTS', "User ID: $user_id, Points: $points, Reason: $reason");
            } catch (Exception $e) {
                mysqli_rollback($conn);
                $error = "Error adjusting points: " . $e->getMessage();
            }
        } else {
            $error = "Insufficient points!";
        }
    }
}

// Get points configuration
$config_sql = "SELECT * FROM PointsConfig ORDER BY ActionType";
$config_result = mysqli_query($conn, $config_sql);

// Get streak bonuses
$streak_result = false;
$check_streak = mysqli_query($conn, "SHOW TABLES LIKE 'StreakBonuses'");
if (mysqli_num_rows($check_streak) > 0) {
    $streak_sql = "SELECT * FROM StreakBonuses ORDER BY StreakType, StreakDays";
    $streak_result = mysqli_query($conn, $streak_sql);
}

// Get membership tiers
$tiers_result = false;
$check_tiers = mysqli_query($conn, "SHOW TABLES LIKE 'MembershipTiers'");
if (mysqli_num_rows($check_tiers) > 0) {
    $tiers_sql = "SELECT * FROM MembershipTiers ORDER BY MinPoints";
    $tiers_result = mysqli_query($conn, $tiers_sql);
}

// Get users with points
$users_sql = "SELECT UserID, Name, Email, StudentID, PointsBalance FROM Users WHERE Role = 'User' ORDER BY PointsBalance DESC";
$users_result = mysqli_query($conn, $users_sql);

// Get recent points history
$history_result = false;
$check_history = mysqli_query($conn, "SHOW TABLES LIKE 'PointsHistory'");
if (mysqli_num_rows($check_history) > 0) {
    $history_sql = "SELECT ph.*, u.Name as UserName 
                    FROM PointsHistory ph
                    JOIN Users u ON ph.UserID = u.UserID
                    ORDER BY ph.CreatedAt DESC 
                    LIMIT 50";
    $history_result = mysqli_query($conn, $history_sql);
}

// Get points expiration settings
$expire_settings = ['ExpiryDays' => 365, 'LastEarnedPointsExpire' => 1, 'NotificationDays' => 30, 'Enabled' => 1];
$check_expire = mysqli_query($conn, "SHOW TABLES LIKE 'PointsExpiration'");
if (mysqli_num_rows($check_expire) > 0) {
    $expire_sql = "SELECT * FROM PointsExpiration WHERE ExpirationID = 1";
    $expire_result = mysqli_query($conn, $expire_sql);
    $expire_settings = mysqli_fetch_assoc($expire_result);
}

// Get rewards
$rewards_result = false;
$check_rewards = mysqli_query($conn, "SHOW TABLES LIKE 'Rewards'");
if (mysqli_num_rows($check_rewards) > 0) {
    $rewards_sql = "SELECT * FROM Rewards ORDER BY PointsRequired ASC";
    $rewards_result = mysqli_query($conn, $rewards_sql);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Points & Rewards - Synergy Hub Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="admin.css">
    <style>
        .points-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 10px;
            flex-wrap: wrap;
        }
        
        .points-tab {
            padding: 10px 20px;
            background: none;
            border: none;
            color: #64748b;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .points-tab:hover {
            background: #f1f5f9;
            color: #1e293b;
        }
        
        .points-tab.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .config-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .config-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .config-card h3 {
            color: #1e293b;
            font-size: 16px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .config-card .action-type {
            color: #667eea;
            font-weight: 600;
        }
        
        .config-card .points-input {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 15px 0;
        }
        
        .config-card .points-input input {
            width: 100px;
            padding: 8px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            text-align: center;
        }
        
        .config-card .points-input span {
            color: #64748b;
            font-size: 14px;
        }
        
        .config-card .description {
            color: #64748b;
            font-size: 12px;
            margin-top: 10px;
        }
        
        .streak-item {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-left: 4px solid #667eea;
        }
        
        .streak-days {
            font-weight: 600;
            color: #1e293b;
        }
        
        .streak-multiplier {
            color: #667eea;
            font-weight: 600;
        }
        
        .streak-bonus {
            background: #dcfce7;
            color: #16a34a;
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 12px;
        }
        
        .tier-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 12px;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }
        
        .tier-name {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .tier-points {
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 15px;
        }
        
        .tier-benefits {
            list-style: none;
            margin: 15px 0;
        }
        
        .tier-benefits li {
            margin-bottom: 8px;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .tier-benefits li i {
            color: #22d3ee;
        }
        
        .tier-multiplier {
            background: rgba(255,255,255,0.2);
            padding: 8px;
            border-radius: 8px;
            text-align: center;
            font-size: 14px;
            margin-top: 10px;
        }
        
        .adjust-points-form {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .user-selector {
            width: 100%;
            padding: 12px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            margin-bottom: 15px;
            font-size: 14px;
        }
        
        .points-adjust-input {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .points-adjust-input input,
        .points-adjust-input select {
            padding: 12px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
        }
        
        .history-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 12px;
            overflow: hidden;
        }
        
        .history-table th {
            background: #f8fafc;
            padding: 12px;
            text-align: left;
            font-size: 12px;
            color: #64748b;
            font-weight: 600;
        }
        
        .history-table td {
            padding: 12px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 13px;
        }
        
        .points-positive {
            color: #10b981;
            font-weight: 600;
        }
        
        .points-negative {
            color: #ef4444;
            font-weight: 600;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .user-points-badge {
            background: #f1f5f9;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 12px;
            margin-left: 5px;
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        
        .btn-sm {
            padding: 8px 16px;
            font-size: 12px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: #f1f5f9;
            color: #475569;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }
        
        .btn-secondary:hover {
            background: #e2e8f0;
        }
        
        .btn-danger {
            background: #ef4444;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.show {
            display: flex;
        }
        
        .modal-content {
            background: white;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .modal-header {
            padding: 20px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-body {
            padding: 20px;
        }
        
        .modal-footer {
            padding: 20px;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            color: #475569;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 5px;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 12px;
            overflow: hidden;
        }
        
        .data-table th {
            background: #f8fafc;
            padding: 12px;
            text-align: left;
            font-size: 12px;
            color: #64748b;
            font-weight: 600;
        }
        
        .data-table td {
            padding: 12px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 13px;
        }
        
        .badge {
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .badge-success {
            background: #dcfce7;
            color: #16a34a;
        }
        
        .badge-warning {
            background: #fff7ed;
            color: #ea580c;
        }
        
        .badge-danger {
            background: #fee;
            color: #ef4444;
        }
        
        .badge-secondary {
            background: #f1f5f9;
            color: #64748b;
        }
        
        @media (max-width: 768px) {
            .points-tabs {
                flex-direction: column;
            }
            
            .points-tab {
                width: 100%;
                text-align: left;
            }
            
            .config-grid {
                grid-template-columns: 1fr;
            }
            
            .points-adjust-input {
                grid-template-columns: 1fr;
            }
            
            .history-table {
                font-size: 12px;
            }
            
            .history-table td, 
            .history-table th {
                padding: 8px;
            }
            
            .form-row {
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
                <h1 class="page-title">
                    <i class="fa-solid fa-star"></i> Points & Rewards Management
                </h1>
                
                <?php if($message): ?>
                    <div class="alert alert-success">
                        <i class="fa-solid fa-circle-check"></i> <?php echo $message; ?>
                    </div>
                <?php endif; ?>
                
                <?php if($error): ?>
                    <div class="alert alert-danger">
                        <i class="fa-solid fa-circle-exclamation"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Tabs -->
                <div class="points-tabs">
                    <button class="points-tab active" onclick="showTab('config', this)">
                        <i class="fa-solid fa-gear"></i> Configuration
                    </button>
                    <button class="points-tab" onclick="showTab('users', this)">
                        <i class="fa-solid fa-users"></i> User Points
                    </button>
                    <button class="points-tab" onclick="showTab('tiers', this)">
                        <i class="fa-solid fa-layer-group"></i> Membership Tiers
                    </button>
                    <button class="points-tab" onclick="showTab('history', this)">
                        <i class="fa-solid fa-clock-rotate-left"></i> Transaction History
                    </button>
                    <button class="points-tab" onclick="showTab('rewards', this)">
                        <i class="fa-solid fa-gift"></i> Rewards Catalog
                    </button>
                </div>
                
                <!-- Tab: Configuration -->
                <div id="tab-config" class="tab-content active">
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3><i class="fa-solid fa-sliders"></i> Points Configuration</h3>
                        </div>
                        <form method="POST">
                            <div class="config-grid">
                                <?php if(mysqli_num_rows($config_result) > 0): ?>
                                    <?php while($config = mysqli_fetch_assoc($config_result)): ?>
                                    <div class="config-card">
                                        <h3>
                                            <i class="fa-solid <?php 
                                                echo match($config['ActionType']) {
                                                    'LOGIN' => 'fa-right-to-bracket',
                                                    'FACILITY_VISIT' => 'fa-location-dot',
                                                    'EVENT_ATTENDANCE' => 'fa-calendar-check',
                                                    'BOOK_BORROW' => 'fa-book',
                                                    'GAME_PLAY' => 'fa-gamepad',
                                                    'CLUB_JOIN' => 'fa-users',
                                                    'REFERRAL' => 'fa-user-plus',
                                                    default => 'fa-star'
                                                }; ?>"></i>
                                            <span class="action-type"><?php echo $config['ActionType']; ?></span>
                                        </h3>
                                        <div class="points-input">
                                            <input type="number" name="points[<?php echo $config['ActionType']; ?>]" 
                                                   value="<?php echo $config['Points']; ?>" min="0" step="1">
                                            <span>points</span>
                                        </div>
                                        <div class="description"><?php echo $config['Description']; ?></div>
                                        <?php if($config['MaxPerDay']): ?>
                                        <div class="limits">
                                            <i class="fa-regular fa-clock"></i> Max <?php echo $config['MaxPerDay']; ?> times per day
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <p>No points configuration found.</p>
                                <?php endif; ?>
                            </div>
                            
                            <h3 style="margin: 30px 0 15px;"><i class="fa-solid fa-chart-line"></i> Streak Bonuses</h3>
                            
                            <?php if($streak_result && mysqli_num_rows($streak_result) > 0): ?>
                                <?php while($streak = mysqli_fetch_assoc($streak_result)): ?>
                                <div class="streak-item">
                                    <div>
                                        <span class="streak-days"><?php echo $streak['StreakDays']; ?>-day <?php echo $streak['StreakType']; ?> streak</span>
                                        <div style="color: #64748b; font-size: 12px; margin-top: 3px;">
                                            <?php echo $streak['Description']; ?>
                                        </div>
                                    </div>
                                    <div style="display: flex; gap: 15px; align-items: center;">
                                        <span class="streak-multiplier"><?php echo $streak['Multiplier']; ?>x multiplier</span>
                                        <?php if($streak['BonusPoints'] > 0): ?>
                                        <span class="streak-bonus">+<?php echo $streak['BonusPoints']; ?> bonus</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <p>No streak bonuses configured.</p>
                            <?php endif; ?>
                            
                            <button type="submit" name="update_config" class="btn btn-primary" style="margin-top: 20px;">
                                <i class="fa-solid fa-save"></i> Save Configuration
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Tab: User Points -->
                <div id="tab-users" class="tab-content">
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3><i class="fa-solid fa-pen"></i> Manual Points Adjustment</h3>
                        </div>
                        
                        <form method="POST" class="adjust-points-form">
                            <select name="user_id" class="user-selector" required>
                                <option value="">-- Select User --</option>
                                <?php 
                                mysqli_data_seek($users_result, 0);
                                while($user = mysqli_fetch_assoc($users_result)): 
                                ?>
                                <option value="<?php echo $user['UserID']; ?>">
                                    <?php echo htmlspecialchars($user['Name']); ?> (<?php echo $user['StudentID']; ?>) - 
                                    <?php echo $user['PointsBalance']; ?> points
                                </option>
                                <?php endwhile; ?>
                            </select>
                            
                            <div class="points-adjust-input">
                                <input type="number" name="points" placeholder="Points (+ to add, - to deduct)" required>
                                <select name="action_type">
                                    <option value="ADMIN_ADJUSTMENT">Manual Adjustment</option>
                                    <option value="BONUS">Bonus</option>
                                    <option value="PENALTY">Penalty</option>
                                    <option value="REWARD_REDEMPTION">Reward Redemption</option>
                                </select>
                            </div>
                            
                            <input type="text" name="reason" placeholder="Reason for adjustment" 
                                   style="width: 100%; padding: 12px; margin-bottom: 15px; border: 1px solid #e2e8f0; border-radius: 8px;" required>
                            
                            <button type="submit" name="adjust_points" class="btn btn-primary">
                                <i class="fa-solid fa-check"></i> Apply Adjustment
                            </button>
                        </form>
                        
                        <h3 style="margin: 30px 0 15px;">User Points Overview</h3>
                        
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Student ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Points Balance</th>
                                        <th>Tier</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    mysqli_data_seek($users_result, 0);
                                    while($user = mysqli_fetch_assoc($users_result)):
                                        // Determine tier
                                        $tier = 'Bronze';
                                        $tier_color = '#CD7F32';
                                        if ($user['PointsBalance'] >= 5000) {
                                            $tier = 'Platinum';
                                            $tier_color = '#E5E4E2';
                                        } elseif ($user['PointsBalance'] >= 2000) {
                                            $tier = 'Gold';
                                            $tier_color = '#FFD700';
                                        } elseif ($user['PointsBalance'] >= 500) {
                                            $tier = 'Silver';
                                            $tier_color = '#C0C0C0';
                                        }
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($user['StudentID']); ?></td>
                                        <td><?php echo htmlspecialchars($user['Name']); ?></td>
                                        <td><?php echo htmlspecialchars($user['Email']); ?></td>
                                        <td><strong><?php echo $user['PointsBalance']; ?></strong></td>
                                        <td>
                                            <span style="color: <?php echo $tier_color; ?>; font-weight: 600;">
                                                <i class="fa-solid fa-crown"></i> <?php echo $tier; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn-sm btn-primary" onclick="quickAdjust(<?php echo $user['UserID']; ?>)">
                                                    <i class="fa-solid fa-pen"></i> Adjust
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Tab: Membership Tiers -->
                <div id="tab-tiers" class="tab-content">
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3><i class="fa-solid fa-layer-group"></i> Membership Tiers</h3>
                            <button class="btn btn-primary btn-sm" onclick="openTierModal()">
                                <i class="fa-solid fa-plus"></i> Add Tier
                            </button>
                        </div>
                        
                        <div class="config-grid">
                            <?php if($tiers_result && mysqli_num_rows($tiers_result) > 0): ?>
                                <?php while($tier = mysqli_fetch_assoc($tiers_result)):
                                    $benefits = json_decode($tier['Benefits'], true);
                                ?>
                                <div class="tier-card" style="background: linear-gradient(135deg, <?php echo $tier['Color']; ?>30 0%, <?php echo $tier['Color']; ?> 100%);">
                                    <div class="tier-name">
                                        <i class="fa-solid <?php echo $tier['Icon']; ?>"></i>
                                        <?php echo $tier['TierName']; ?>
                                    </div>
                                    <div class="tier-points">
                                        <?php echo number_format($tier['MinPoints']); ?> - 
                                        <?php echo $tier['MaxPoints'] ? number_format($tier['MaxPoints']) : '∞'; ?> points
                                    </div>
                                    <ul class="tier-benefits">
                                        <?php if($benefits): ?>
                                            <?php if(isset($benefits['points_multiplier'])): ?>
                                            <li><i class="fa-solid fa-star"></i> <?php echo $benefits['points_multiplier']; ?>x points multiplier</li>
                                            <?php endif; ?>
                                            <?php if(isset($benefits['free_drinks']) && $benefits['free_drinks'] > 0): ?>
                                            <li><i class="fa-solid fa-mug-saucer"></i> <?php echo $benefits['free_drinks']; ?> free drinks/month</li>
                                            <?php endif; ?>
                                            <?php if(isset($benefits['priority_booking']) && $benefits['priority_booking']): ?>
                                            <li><i class="fa-solid fa-clock"></i> Priority booking</li>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </ul>
                                    <div class="tier-multiplier">
                                        <i class="fa-solid fa-calculator"></i> Points Multiplier: <?php echo $tier['Multiplier']; ?>x
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <p>No membership tiers configured.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Tab: Transaction History -->
                <div id="tab-history" class="tab-content">
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3><i class="fa-solid fa-clock-rotate-left"></i> Points Transaction History</h3>
                            <button class="btn btn-secondary btn-sm" onclick="exportHistory()">
                                <i class="fa-solid fa-download"></i> Export
                            </button>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="history-table">
                                <thead>
                                    <tr>
                                        <th>Date & Time</th>
                                        <th>User</th>
                                        <th>Action</th>
                                        <th>Points Change</th>
                                        <th>Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if($history_result && mysqli_num_rows($history_result) > 0): ?>
                                        <?php while($history = mysqli_fetch_assoc($history_result)): ?>
                                        <tr>
                                            <td><?php echo date('M d, Y h:i A', strtotime($history['CreatedAt'])); ?></td>
                                            <td><?php echo htmlspecialchars($history['UserName']); ?></td>
                                            <td><?php echo $history['ActionType']; ?></td>
                                            <td class="<?php echo $history['PointsChange'] > 0 ? 'points-positive' : 'points-negative'; ?>">
                                                <?php echo $history['PointsChange'] > 0 ? '+' : ''; ?><?php echo $history['PointsChange']; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($history['Description']); ?></td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" style="text-align: center; padding: 30px; color: #94a3b8;">
                                                <i class="fa-regular fa-clock" style="font-size: 24px; margin-bottom: 10px; display: block;"></i>
                                                No transaction history found
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Tab: Rewards Catalog -->
                <div id="tab-rewards" class="tab-content">
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3><i class="fa-solid fa-gift"></i> Rewards Catalog</h3>
                            <button class="btn btn-primary btn-sm" onclick="openRewardModal()">
                                <i class="fa-solid fa-plus"></i> Add Reward
                            </button>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Reward Name</th>
                                        <th>Description</th>
                                        <th>Points Required</th>
                                        <th>Availability</th>
                                        <th>Quantity</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if($rewards_result && mysqli_num_rows($rewards_result) > 0): ?>
                                        <?php while($reward = mysqli_fetch_assoc($rewards_result)): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($reward['Name']); ?></td>
                                            <td><?php echo htmlspecialchars(substr($reward['Description'], 0, 50)) . '...'; ?></td>
                                            <td><strong><?php echo $reward['PointsRequired']; ?></strong> ⭐</td>
                                            <td>
                                                <span class="badge badge-<?php 
                                                    echo match($reward['Availability']) {
                                                        'Available' => 'success',
                                                        'Limited' => 'warning',
                                                        'Out of Stock' => 'danger',
                                                        default => 'secondary'
                                                    }; ?>">
                                                    <?php echo $reward['Availability']; ?>
                                                </span>
                                            </td>
                                            <td><?php echo $reward['Quantity'] ?? '∞'; ?></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button class="btn-sm btn-secondary" onclick="editReward(<?php echo $reward['RewardID']; ?>)">
                                                        <i class="fa-solid fa-edit"></i>
                                                    </button>
                                                    <button class="btn-sm btn-danger" onclick="deleteReward(<?php echo $reward['RewardID']; ?>)">
                                                        <i class="fa-solid fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                    <tr>
                                        <td colspan="6" style="text-align: center; padding: 30px; color: #94a3b8;">
                                            <i class="fa-solid fa-gift" style="font-size: 24px; margin-bottom: 10px; display: block;"></i>
                                            No rewards found. Click "Add Reward" to create one.
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Reward Modal -->
    <div class="modal" id="rewardModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fa-solid fa-gift"></i> Add Reward</h3>
                <button class="modal-close" onclick="closeRewardModal()">&times;</button>
            </div>
            <form method="POST" action="save_reward.php">
                <div class="modal-body">
                    <input type="hidden" name="reward_id" id="reward_id">
                    
                    <div class="form-group">
                        <label>Reward Name</label>
                        <input type="text" name="name" id="reward_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" id="reward_description" rows="3" required></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Points Required</label>
                            <input type="number" name="points_required" id="reward_points" min="1" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Availability</label>
                            <select name="availability" id="reward_availability">
                                <option value="Available">Available</option>
                                <option value="Limited">Limited</option>
                                <option value="Out of Stock">Out of Stock</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Quantity (leave empty for unlimited)</label>
                        <input type="number" name="quantity" id="reward_quantity" min="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeRewardModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Reward</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Tier Modal -->
    <div class="modal" id="tierModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fa-solid fa-layer-group"></i> Add Tier</h3>
                <button class="modal-close" onclick="closeTierModal()">&times;</button>
            </div>
            <form method="POST" action="save_tier.php">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Tier Name</label>
                        <select name="tier_name" required>
                            <option value="Bronze">Bronze</option>
                            <option value="Silver">Silver</option>
                            <option value="Gold">Gold</option>
                            <option value="Platinum">Platinum</option>
                        </select>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Minimum Points</label>
                            <input type="number" name="min_points" min="0" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Maximum Points (optional)</label>
                            <input type="number" name="max_points" min="0">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Points Multiplier</label>
                        <input type="number" name="multiplier" step="0.1" min="1" value="1" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeTierModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Tier</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Tab switching
        function showTab(tabName, element) {
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.getElementById('tab-' + tabName).classList.add('active');
            
            document.querySelectorAll('.points-tab').forEach(btn => {
                btn.classList.remove('active');
            });
            element.classList.add('active');
        }
        
        // Reward modal functions
        function openRewardModal() {
            document.getElementById('rewardModal').classList.add('show');
            document.getElementById('reward_id').value = '';
            document.getElementById('reward_name').value = '';
            document.getElementById('reward_description').value = '';
            document.getElementById('reward_points').value = '';
            document.getElementById('reward_availability').value = 'Available';
            document.getElementById('reward_quantity').value = '';
        }
        
        function closeRewardModal() {
            document.getElementById('rewardModal').classList.remove('show');
        }
        
        function editReward(id) {
            alert('Edit reward feature - ID: ' + id);
            // Implement edit functionality
        }
        
        function deleteReward(id) {
            if (confirm('Are you sure you want to delete this reward?')) {
                window.location.href = 'delete_reward.php?id=' + id;
            }
        }
        
        // Tier modal functions
        function openTierModal() {
            document.getElementById('tierModal').classList.add('show');
        }
        
        function closeTierModal() {
            document.getElementById('tierModal').classList.remove('show');
        }
        
        // Quick adjust points
        function quickAdjust(userId) {
            const select = document.querySelector('select[name="user_id"]');
            if (select) {
                select.value = userId;
                document.querySelector('.adjust-points-form').scrollIntoView({ behavior: 'smooth' });
            }
        }
        
        // Export history
        function exportHistory() {
            window.location.href = 'export_points_history.php';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('show');
            }
        }
    </script>
</body>
</html>