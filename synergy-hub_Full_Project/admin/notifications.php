<?php
require_once 'middleware.php';
require_once 'config.php';
checkAdminAuth();

$admin = getAdminInfo($conn);

// Handle actions
$message = '';
$error = '';

// Send notification
// Send notification
if (isset($_POST['send_notification'])) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $message_text = mysqli_real_escape_string($conn, $_POST['message']);
    $type = mysqli_real_escape_string($conn, $_POST['type']);
    $target_type = mysqli_real_escape_string($conn, $_POST['target_type']);
    $target_value = isset($_POST['target_value']) ? mysqli_real_escape_string($conn, $_POST['target_value']) : '';
    $schedule = !empty($_POST['schedule_datetime']) ? $_POST['schedule_datetime'] : null;
    
    // Convert your type to match the ENUM in Notifications table
    $notif_type = match($type) {
        'gym' => 'Announcement',
        'event' => 'Event',
        'transport' => 'Transport',
        'emergency' => 'Announcement',
        default => 'Announcement'
    };
    
    // Get target users based on selection
    $user_ids = [];
    
    switch($target_type) {
        case 'all':
            $user_sql = "SELECT UserID FROM Users WHERE Role = 'User'";
            $user_result = mysqli_query($conn, $user_sql);
            while($user = mysqli_fetch_assoc($user_result)) {
                $user_ids[] = $user['UserID'];
            }
            break;
            
        case 'single_user':
            $user_ids = [intval($target_value)];
            break;
            
        case 'user_group':
            // Check if UserGroupMembers table exists
            $check_groups = mysqli_query($conn, "SHOW TABLES LIKE 'UserGroupMembers'");
            if (mysqli_num_rows($check_groups) > 0) {
                $group_sql = "SELECT UserID FROM UserGroupMembers WHERE GroupID = ?";
                $group_stmt = mysqli_prepare($conn, $group_sql);
                mysqli_stmt_bind_param($group_stmt, "i", $target_value);
                mysqli_stmt_execute($group_stmt);
                $group_result = mysqli_stmt_get_result($group_stmt);
                while($user = mysqli_fetch_assoc($group_result)) {
                    $user_ids[] = $user['UserID'];
                }
            }
            break;
            
        case 'location':
            // Check if CheckIns table exists
            $check_checkins = mysqli_query($conn, "SHOW TABLES LIKE 'CheckIns'");
            if (mysqli_num_rows($check_checkins) > 0) {
                $loc_sql = "SELECT DISTINCT UserID FROM CheckIns WHERE FacilityID = ? AND DATE(Timestamp) > DATE_SUB(NOW(), INTERVAL 30 DAY)";
                $loc_stmt = mysqli_prepare($conn, $loc_sql);
                mysqli_stmt_bind_param($loc_stmt, "i", $target_value);
                mysqli_stmt_execute($loc_stmt);
                $loc_result = mysqli_stmt_get_result($loc_stmt);
                while($user = mysqli_fetch_assoc($loc_result)) {
                    $user_ids[] = $user['UserID'];
                }
            }
            break;
            
        case 'tier':
            $points_threshold = intval($target_value);
            $tier_sql = "SELECT UserID FROM Users WHERE PointsBalance >= ?";
            $tier_stmt = mysqli_prepare($conn, $tier_sql);
            mysqli_stmt_bind_param($tier_stmt, "i", $points_threshold);
            mysqli_stmt_execute($tier_stmt);
            $tier_result = mysqli_stmt_get_result($tier_stmt);
            while($user = mysqli_fetch_assoc($tier_result)) {
                $user_ids[] = $user['UserID'];
            }
            break;
    }
    
    // Remove duplicates
    $user_ids = array_unique($user_ids);
    
    if (empty($user_ids)) {
        $error = "No users found for the selected target!";
    } else {
        // Start transaction
        mysqli_begin_transaction($conn);
        
        try {
            $sent_count = 0;
            $failed_count = 0;
            
            // Insert notification for each user using your uppercase Notifications table
            foreach($user_ids as $uid) {
                // Using uppercase Notifications table with correct columns
                $insert_sql = "INSERT INTO Notifications (UserID, Message, Type, Timestamp, Status) 
                               VALUES (?, ?, ?, NOW(), 'Unread')";
                $insert_stmt = mysqli_prepare($conn, $insert_sql);
                mysqli_stmt_bind_param($insert_stmt, "iss", $uid, $message_text, $notif_type);
                
                if (mysqli_stmt_execute($insert_stmt)) {
                    $sent_count++;
                } else {
                    $failed_count++;
                    error_log("Failed to insert notification: " . mysqli_error($conn));
                }
            }
            
            // Log the notification send
            $log_sql = "INSERT INTO NotificationLog 
                        (Title, Message, Type, TargetType, TargetValue, ScheduledFor, SentAt, Status, SentCount, FailedCount, CreatedBy) 
                        VALUES (?, ?, ?, ?, ?, ?, NOW(), 'sent', ?, ?, ?)";
            $log_stmt = mysqli_prepare($conn, $log_sql);
            mysqli_stmt_bind_param($log_stmt, "ssssssiii", 
                $title, $message_text, $type, $target_type, $target_value, 
                $schedule, $sent_count, $failed_count, $_SESSION['user_id']
            );
            mysqli_stmt_execute($log_stmt);
            
            mysqli_commit($conn);
            
            $message = "✅ Notification sent to $sent_count users" . ($failed_count > 0 ? " ($failed_count failed)" : "");
            
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error = "Error sending notification: " . $e->getMessage();
        }
    }
}
// Save template
if (isset($_POST['save_template'])) {
    $template_name = mysqli_real_escape_string($conn, $_POST['template_name']);
    $template_title = mysqli_real_escape_string($conn, $_POST['template_title']);
    $template_message = mysqli_real_escape_string($conn, $_POST['template_message']);
    $template_type = mysqli_real_escape_string($conn, $_POST['template_type']);
    
    $insert_sql = "INSERT INTO NotificationTemplates (Name, Title, Message, Type, CreatedBy) 
                   VALUES (?, ?, ?, ?, ?)";
    $insert_stmt = mysqli_prepare($conn, $insert_sql);
    mysqli_stmt_bind_param($insert_stmt, "ssssi", $template_name, $template_title, $template_message, $template_type, $_SESSION['user_id']);
    
    if (mysqli_stmt_execute($insert_stmt)) {
        $message = "✅ Template saved successfully!";
    } else {
        $error = "Error saving template: " . mysqli_error($conn);
    }
}

// Delete template
if (isset($_GET['delete_template'])) {
    $template_id = intval($_GET['delete_template']);
    $delete_sql = "DELETE FROM NotificationTemplates WHERE TemplateID = ?";
    $delete_stmt = mysqli_prepare($conn, $delete_sql);
    mysqli_stmt_bind_param($delete_stmt, "i", $template_id);
    
    if (mysqli_stmt_execute($delete_stmt)) {
        $message = "✅ Template deleted!";
    }
}

// Cancel scheduled notification
if (isset($_GET['cancel_scheduled'])) {
    $log_id = intval($_GET['cancel_scheduled']);
    $cancel_sql = "UPDATE NotificationLog SET Status = 'cancelled' WHERE LogID = ? AND Status = 'scheduled'";
    $cancel_stmt = mysqli_prepare($conn, $cancel_sql);
    mysqli_stmt_bind_param($cancel_stmt, "i", $log_id);
    mysqli_stmt_execute($cancel_stmt);
    $message = "✅ Scheduled notification cancelled";
}

// Get templates
$templates_sql = "SELECT * FROM NotificationTemplates ORDER BY CreatedAt DESC";
$templates_result = mysqli_query($conn, $templates_sql);

// Get notification history
$history_sql = "SELECT l.*, u.Name as CreatorName 
                FROM NotificationLog l
                LEFT JOIN Users u ON l.CreatedBy = u.UserID
                ORDER BY l.CreatedAt DESC 
                LIMIT 50";
$history_result = mysqli_query($conn, $history_sql);

// Get user groups
$groups_sql = "SELECT * FROM UserGroups ORDER BY GroupName";
$groups_result = mysqli_query($conn, $groups_sql);

// Get facilities for location targeting
$facilities_sql = "SELECT FacilityID, Name FROM Facilities WHERE Status = 'Open'";
$facilities_result = mysqli_query($conn, $facilities_sql);

// Get stats
$stats_sql = "SELECT 
                COUNT(*) as total_sent,
                SUM(CASE WHEN Status = 'sent' THEN 1 ELSE 0 END) as successful,
                SUM(CASE WHEN Status = 'scheduled' THEN 1 ELSE 0 END) as scheduled,
                SUM(CASE WHEN Status = 'failed' THEN 1 ELSE 0 END) as failed,
                SUM(SentCount) as total_recipients,
                SUM(OpenedCount) as total_opens
              FROM NotificationLog";
$stats_result = mysqli_query($conn, $stats_sql);
$stats = mysqli_fetch_assoc($stats_result);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notification Management - Synergy Hub Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="admin.css">
    <style>
        .notif-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 10px;
            flex-wrap: wrap;
        }
        
        .notif-tab {
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
        
        .notif-tab:hover {
            background: #f1f5f9;
            color: #1e293b;
        }
        
        .notif-tab.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .send-form {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 20px;
        }
        
        .form-section {
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .form-section h3 {
            color: #1e293b;
            font-size: 16px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .target-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin: 15px 0;
        }
        
        .target-option {
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
        }
        
        .target-option:hover {
            border-color: #667eea;
            background: white;
        }
        
        .target-option.selected {
            border-color: #667eea;
            background: #eef2ff;
        }
        
        .target-option input[type="radio"] {
            display: none;
        }
        
        .target-option i {
            font-size: 24px;
            color: #667eea;
            margin-bottom: 8px;
            display: block;
        }
        
        .target-option span {
            font-size: 14px;
            font-weight: 500;
            color: #1e293b;
        }
        
        .target-selector {
            margin-top: 15px;
        }
        
        .target-selector select,
        .target-selector input {
            width: 100%;
            padding: 12px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
        }
        
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: #1e293b;
            display: block;
        }
        
        .stat-label {
            color: #64748b;
            font-size: 13px;
        }
        
        .stat-icon {
            float: right;
            font-size: 32px;
            color: #667eea;
            opacity: 0.3;
        }
        
        .template-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .template-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border-left: 4px solid #667eea;
        }
        
        .template-name {
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 8px;
            font-size: 16px;
        }
        
        .template-preview {
            color: #64748b;
            font-size: 13px;
            margin: 10px 0;
            padding: 10px;
            background: #f8fafc;
            border-radius: 6px;
        }
        
        .template-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .emergency-btn {
            background: #ef4444;
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
        }
        
        .emergency-btn:hover {
            background: #dc2626;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
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
            padding: 15px;
            text-align: left;
            font-size: 12px;
            color: #64748b;
            font-weight: 600;
        }
        
        .history-table td {
            padding: 15px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 13px;
        }
        
        .status-badge {
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .status-sent {
            background: #dcfce7;
            color: #16a34a;
        }
        
        .status-scheduled {
            background: #fff7ed;
            color: #ea580c;
        }
        
        .status-sending {
            background: #dbeafe;
            color: #2563eb;
        }
        
        .status-failed {
            background: #fee;
            color: #ef4444;
        }
        
        .status-cancelled {
            background: #f1f5f9;
            color: #64748b;
        }
        
        .type-badge {
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .type-gym {
            background: #dbeafe;
            color: #2563eb;
        }
        
        .type-event {
            background: #dcfce7;
            color: #16a34a;
        }
        
        .type-transport {
            background: #fff7ed;
            color: #ea580c;
        }
        
        .type-general {
            background: #f3e8ff;
            color: #9333ea;
        }
        
        .type-emergency {
            background: #fee;
            color: #ef4444;
        }
        
        .open-rate {
            font-weight: 600;
            color: #1e293b;
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
            max-width: 600px;
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
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: #f1f5f9;
            color: #475569;
        }
        
        .btn-secondary:hover {
            background: #e2e8f0;
        }
        
        .btn-danger {
            background: #ef4444;
            color: white;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
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
        
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        @media (max-width: 768px) {
            .notif-tabs {
                flex-direction: column;
            }
            
            .target-options {
                grid-template-columns: 1fr 1fr;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .history-table {
                font-size: 11px;
            }
            
            .history-table td,
            .history-table th {
                padding: 8px 5px;
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
                    <i class="fa-solid fa-bell"></i> Notification Management
                </h1>
                
                <?php if($message): ?>
                    <div class="alert alert-success"><?php echo $message; ?></div>
                <?php endif; ?>
                
                <?php if($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <!-- Stats Cards -->
                <div class="stats-cards">
                    <div class="stat-card">
                        <i class="stat-icon fa-solid fa-paper-plane"></i>
                        <span class="stat-value"><?php echo number_format($stats['total_sent']); ?></span>
                        <span class="stat-label">Total Notifications</span>
                    </div>
                    <div class="stat-card">
                        <i class="stat-icon fa-solid fa-check-circle"></i>
                        <span class="stat-value"><?php echo number_format($stats['total_recipients']); ?></span>
                        <span class="stat-label">Total Recipients</span>
                    </div>
                    <div class="stat-card">
                        <i class="stat-icon fa-solid fa-eye"></i>
                        <span class="stat-value"><?php echo number_format($stats['total_opens']); ?></span>
                        <span class="stat-label">Total Opens</span>
                    </div>
                    <div class="stat-card">
                        <i class="stat-icon fa-solid fa-clock"></i>
                        <span class="stat-value"><?php echo $stats['scheduled']; ?></span>
                        <span class="stat-label">Scheduled</span>
                    </div>
                </div>
                
                <!-- Emergency Broadcast -->
                <div style="margin-bottom: 20px;">
                    <button class="emergency-btn" onclick="openEmergencyModal()">
                        <i class="fa-solid fa-triangle-exclamation"></i> Emergency Broadcast
                    </button>
                </div>
                
                <!-- Tabs -->
                <div class="notif-tabs">
                    <button class="notif-tab active" onclick="showTab('send', this)">
                        <i class="fa-solid fa-paper-plane"></i> Send Notification
                    </button>
                    <button class="notif-tab" onclick="showTab('templates', this)">
                        <i class="fa-solid fa-file"></i> Templates
                    </button>
                    <button class="notif-tab" onclick="showTab('history', this)">
                        <i class="fa-solid fa-clock-rotate-left"></i> History
                    </button>
                    <button class="notif-tab" onclick="showTab('scheduled', this)">
                        <i class="fa-solid fa-calendar-check"></i> Scheduled
                    </button>
                    <button class="notif-tab" onclick="showTab('stats', this)">
                        <i class="fa-solid fa-chart-bar"></i> Analytics
                    </button>
                </div>
                
                <!-- Tab: Send Notification -->
                <div id="tab-send" class="tab-content active">
                    <div class="send-form">
                        <form method="POST" id="notificationForm">
                            <div class="form-section">
                                <h3><i class="fa-solid fa-bullseye"></i> Target Audience</h3>
                                <div class="target-options">
                                    <label class="target-option">
                                        <input type="radio" name="target_type" value="all" checked onchange="toggleTargetSelector()">
                                        <i class="fa-solid fa-users"></i>
                                        <span>All Users</span>
                                    </label>
                                    <label class="target-option">
                                        <input type="radio" name="target_type" value="single_user" onchange="toggleTargetSelector()">
                                        <i class="fa-solid fa-user"></i>
                                        <span>Single User</span>
                                    </label>
                                    <label class="target-option">
                                        <input type="radio" name="target_type" value="user_group" onchange="toggleTargetSelector()">
                                        <i class="fa-solid fa-layer-group"></i>
                                        <span>User Group</span>
                                    </label>
                                    <label class="target-option">
                                        <input type="radio" name="target_type" value="location" onchange="toggleTargetSelector()">
                                        <i class="fa-solid fa-location-dot"></i>
                                        <span>Location</span>
                                    </label>
                                    <label class="target-option">
                                        <input type="radio" name="target_type" value="tier" onchange="toggleTargetSelector()">
                                        <i class="fa-solid fa-star"></i>
                                        <span>Points Tier</span>
                                    </label>
                                </div>
                                
                                <div class="target-selector" id="targetSelector">
                                    <!-- Single User Select -->
                                    <div id="target-single_user" style="display: none;">
                                        <select name="target_value" class="form-control">
                                            <option value="">Select User</option>
                                            <?php
                                            $user_sel_sql = "SELECT UserID, Name, Email FROM Users WHERE Role = 'User' ORDER BY Name";
                                            $user_sel_result = mysqli_query($conn, $user_sel_sql);
                                            while($u = mysqli_fetch_assoc($user_sel_result)):
                                            ?>
                                            <option value="<?php echo $u['UserID']; ?>">
                                                <?php echo htmlspecialchars($u['Name']); ?> (<?php echo $u['Email']; ?>)
                                            </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    
                                    <!-- User Group Select -->
                                    <div id="target-user_group" style="display: none;">
                                        <select name="target_value" class="form-control">
                                            <option value="">Select Group</option>
                                            <?php if($groups_result && mysqli_num_rows($groups_result) > 0): ?>
                                                <?php mysqli_data_seek($groups_result, 0); ?>
                                                <?php while($group = mysqli_fetch_assoc($groups_result)): ?>
                                                <option value="<?php echo $group['GroupID']; ?>">
                                                    <?php echo htmlspecialchars($group['GroupName']); ?>
                                                </option>
                                                <?php endwhile; ?>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                    
                                    <!-- Location Select -->
                                    <div id="target-location" style="display: none;">
                                        <select name="target_value" class="form-control">
                                            <option value="">Select Location</option>
                                            <?php if($facilities_result && mysqli_num_rows($facilities_result) > 0): ?>
                                                <?php while($fac = mysqli_fetch_assoc($facilities_result)): ?>
                                                <option value="<?php echo $fac['FacilityID']; ?>">
                                                    <?php echo htmlspecialchars($fac['Name']); ?>
                                                </option>
                                                <?php endwhile; ?>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                    
                                    <!-- Tier Select -->
                                    <div id="target-tier" style="display: none;">
                                        <select name="target_value" class="form-control">
                                            <option value="">Select Tier</option>
                                            <option value="500">Silver (500+ points)</option>
                                            <option value="2000">Gold (2000+ points)</option>
                                            <option value="5000">Platinum (5000+ points)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-section">
                                <h3><i class="fa-solid fa-pen"></i> Notification Content</h3>
                                
                                <div class="form-group">
                                    <label>Notification Type</label>
                                    <select name="type" required>
                                        <option value="general">General</option>
                                        <option value="gym">Gym</option>
                                        <option value="event">Event</option>
                                        <option value="transport">Transport</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label>Title</label>
                                    <input type="text" name="title" placeholder="e.g., Gym Maintenance" required>
                                </div>
                                
                                <div class="form-group">
                                    <label>Message</label>
                                    <textarea name="message" placeholder="Enter notification message..." required></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label>Schedule (Optional - leave empty for immediate send)</label>
                                    <input type="datetime-local" name="schedule_datetime">
                                </div>
                                
                                <div class="form-group">
                                    <label>Load from Template</label>
                                    <select onchange="loadTemplate(this.value)">
                                        <option value="">-- Select Template --</option>
                                        <?php if($templates_result && mysqli_num_rows($templates_result) > 0): ?>
                                            <?php mysqli_data_seek($templates_result, 0); ?>
                                            <?php while($template = mysqli_fetch_assoc($templates_result)): ?>
                                            <option value="<?php echo $template['TemplateID']; ?>">
                                                <?php echo htmlspecialchars($template['Name']); ?>
                                            </option>
                                            <?php endwhile; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <button type="submit" name="send_notification" class="btn btn-primary">
                                <i class="fa-solid fa-paper-plane"></i> Send Notification
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Tab: Templates -->
                <div id="tab-templates" class="tab-content">
                    <div class="send-form">
                        <h3 style="margin-bottom: 20px;">
                            <i class="fa-solid fa-plus"></i> Create New Template
                        </h3>
                        
                        <form method="POST">
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Template Name</label>
                                    <input type="text" name="template_name" placeholder="e.g., Gym Update" required>
                                </div>
                                <div class="form-group">
                                    <label>Template Type</label>
                                    <select name="template_type" required>
                                        <option value="general">General</option>
                                        <option value="gym">Gym</option>
                                        <option value="event">Event</option>
                                        <option value="transport">Transport</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Template Title</label>
                                <input type="text" name="template_title" placeholder="Notification title" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Template Message</label>
                                <textarea name="template_message" placeholder="Notification message" required></textarea>
                            </div>
                            
                            <button type="submit" name="save_template" class="btn btn-primary">
                                <i class="fa-solid fa-save"></i> Save Template
                            </button>
                        </form>
                        
                        <h3 style="margin: 30px 0 15px;">Your Templates</h3>
                        
                        <div class="template-grid">
                            <?php if($templates_result && mysqli_num_rows($templates_result) > 0): ?>
                                <?php mysqli_data_seek($templates_result, 0); ?>
                                <?php while($template = mysqli_fetch_assoc($templates_result)): ?>
                                <div class="template-card">
                                    <div class="template-name">
                                        <i class="fa-regular fa-file-lines"></i> 
                                        <?php echo htmlspecialchars($template['Name']); ?>
                                    </div>
                                    <div class="type-badge type-<?php echo $template['Type']; ?>">
                                        <?php echo ucfirst($template['Type']); ?>
                                    </div>
                                    <div class="template-preview">
                                        <strong><?php echo htmlspecialchars($template['Title']); ?></strong><br>
                                        <?php echo htmlspecialchars(substr($template['Message'], 0, 50)) . '...'; ?>
                                    </div>
                                    <div class="template-actions">
                                        <button class="btn btn-sm btn-secondary" onclick="useTemplate(<?php echo $template['TemplateID']; ?>)">
                                            <i class="fa-solid fa-paper-plane"></i> Use
                                        </button>
                                        <a href="?delete_template=<?php echo $template['TemplateID']; ?>" 
                                           class="btn btn-sm btn-danger" 
                                           onclick="return confirm('Delete this template?')">
                                            <i class="fa-solid fa-trash"></i>
                                        </a>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <p>No templates yet. Create one above!</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Tab: History -->
                <div id="tab-history" class="tab-content">
                    <div class="send-form">
                        <h3 style="margin-bottom: 20px;">
                            <i class="fa-solid fa-clock-rotate-left"></i> Notification History
                        </h3>
                        
                        <div class="table-responsive">
                            <table class="history-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Type</th>
                                        <th>Title</th>
                                        <th>Target</th>
                                        <th>Sent/Failed</th>
                                        <th>Opens</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if($history_result && mysqli_num_rows($history_result) > 0): ?>
                                        <?php while($log = mysqli_fetch_assoc($history_result)): ?>
                                        <tr>
                                            <td><?php echo date('M d, H:i', strtotime($log['CreatedAt'])); ?></td>
                                            <td>
                                                <span class="type-badge type-<?php echo $log['Type']; ?>">
                                                    <?php echo ucfirst($log['Type']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars(substr($log['Title'], 0, 20)); ?>...</td>
                                            <td><?php echo ucfirst(str_replace('_', ' ', $log['TargetType'])); ?></td>
                                            <td>
                                                <?php echo $log['SentCount']; ?> / <?php echo $log['FailedCount']; ?>
                                            </td>
                                            <td class="open-rate">
                                                <?php 
                                                if($log['SentCount'] > 0) {
                                                    $rate = round(($log['OpenedCount'] / $log['SentCount']) * 100);
                                                    echo $log['OpenedCount'] . ' (' . $rate . '%)';
                                                } else {
                                                    echo '0 (0%)';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <span class="status-badge status-<?php echo $log['Status']; ?>">
                                                    <?php echo ucfirst($log['Status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-secondary" onclick="viewDetails(<?php echo $log['LogID']; ?>)">
                                                    <i class="fa-solid fa-eye"></i>
                                                </button>
                                                <?php if($log['Status'] == 'scheduled'): ?>
                                                <a href="?cancel_scheduled=<?php echo $log['LogID']; ?>" 
                                                   class="btn btn-sm btn-danger"
                                                   onclick="return confirm('Cancel this scheduled notification?')">
                                                    <i class="fa-solid fa-ban"></i>
                                                </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" style="text-align: center; padding: 30px;">
                                                No notification history yet
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Tab: Scheduled -->
                <div id="tab-scheduled" class="tab-content">
                    <div class="send-form">
                        <h3 style="margin-bottom: 20px;">
                            <i class="fa-solid fa-calendar-check"></i> Scheduled Notifications
                        </h3>
                        
                        <div class="table-responsive">
                            <table class="history-table">
                                <thead>
                                    <tr>
                                        <th>Scheduled For</th>
                                        <th>Type</th>
                                        <th>Title</th>
                                        <th>Target</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $scheduled_sql = "SELECT l.*, u.Name as CreatorName 
                                                      FROM NotificationLog l
                                                      LEFT JOIN Users u ON l.CreatedBy = u.UserID
                                                      WHERE l.Status = 'scheduled' AND l.ScheduledFor > NOW()
                                                      ORDER BY l.ScheduledFor ASC";
                                    $scheduled_result = mysqli_query($conn, $scheduled_sql);
                                    ?>
                                    <?php if(mysqli_num_rows($scheduled_result) > 0): ?>
                                        <?php while($sch = mysqli_fetch_assoc($scheduled_result)): ?>
                                        <tr>
                                            <td><?php echo date('M d, H:i', strtotime($sch['ScheduledFor'])); ?></td>
                                            <td>
                                                <span class="type-badge type-<?php echo $sch['Type']; ?>">
                                                    <?php echo ucfirst($sch['Type']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($sch['Title']); ?></td>
                                            <td><?php echo ucfirst(str_replace('_', ' ', $sch['TargetType'])); ?></td>
                                            <td><?php echo date('M d', strtotime($sch['CreatedAt'])); ?></td>
                                            <td>
                                                <a href="?cancel_scheduled=<?php echo $sch['LogID']; ?>" 
                                                   class="btn btn-sm btn-danger"
                                                   onclick="return confirm('Cancel this scheduled notification?')">
                                                    <i class="fa-solid fa-ban"></i> Cancel
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" style="text-align: center; padding: 30px;">
                                                No scheduled notifications
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Tab: Analytics -->
                <div id="tab-stats" class="tab-content">
                    <div class="send-form">
                        <h3 style="margin-bottom: 20px;">
                            <i class="fa-solid fa-chart-bar"></i> Notification Analytics
                        </h3>
                        
                        <?php
                        // Get open rates by type
                        $type_stats_sql = "SELECT 
                                            Type,
                                            COUNT(*) as total,
                                            SUM(SentCount) as total_sent,
                                            SUM(OpenedCount) as total_opens,
                                            AVG(CASE WHEN SentCount > 0 THEN OpenedCount/SentCount ELSE 0 END) as avg_open_rate
                                          FROM NotificationLog
                                          WHERE Status = 'sent'
                                          GROUP BY Type";
                        $type_stats = mysqli_query($conn, $type_stats_sql);
                        
                        // Get daily stats for last 30 days
                        $daily_sql = "SELECT 
                                        DATE(CreatedAt) as date,
                                        COUNT(*) as count,
                                        SUM(SentCount) as sent,
                                        SUM(OpenedCount) as opened
                                      FROM NotificationLog
                                      WHERE CreatedAt > DATE_SUB(NOW(), INTERVAL 30 DAY)
                                      GROUP BY DATE(CreatedAt)
                                      ORDER BY date DESC";
                        $daily_result = mysqli_query($conn, $daily_sql);
                        ?>
                        
                        <div class="stats-grid" style="margin-bottom: 30px;">
                            <?php while($stat = mysqli_fetch_assoc($type_stats)): ?>
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <?php
                                    $icon = match($stat['Type']) {
                                        'gym' => 'fa-dumbbell',
                                        'event' => 'fa-calendar',
                                        'transport' => 'fa-bus',
                                        'emergency' => 'fa-triangle-exclamation',
                                        default => 'fa-bell'
                                    };
                                    ?>
                                    <i class="fa-solid <?php echo $icon; ?>"></i>
                                </div>
                                <span class="stat-value"><?php echo round($stat['avg_open_rate'] * 100); ?>%</span>
                                <span class="stat-label"><?php echo ucfirst($stat['Type']); ?> Open Rate</span>
                                <div style="font-size: 12px; color: #64748b; margin-top: 5px;">
                                    <?php echo $stat['total_opens']; ?>/<?php echo $stat['total_sent']; ?> opens
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                        
                        <h4 style="margin: 20px 0 10px;">Last 30 Days Activity</h4>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Notifications</th>
                                    <th>Total Sent</th>
                                    <th>Total Opens</th>
                                    <th>Open Rate</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($daily = mysqli_fetch_assoc($daily_result)): 
                                    $rate = $daily['sent'] > 0 ? round(($daily['opened'] / $daily['sent']) * 100) : 0;
                                ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($daily['date'])); ?></td>
                                    <td><?php echo $daily['count']; ?></td>
                                    <td><?php echo $daily['sent']; ?></td>
                                    <td><?php echo $daily['opened']; ?></td>
                                    <td><?php echo $rate; ?>%</td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Emergency Modal -->
    <div class="modal" id="emergencyModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fa-solid fa-triangle-exclamation" style="color: #ef4444;"></i> Emergency Broadcast</h3>
                <button class="modal-close" onclick="closeEmergencyModal()">&times;</button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="target_type" value="all">
                    
                    <div class="alert alert-danger" style="margin-bottom: 20px;">
                        <i class="fa-solid fa-exclamation-circle"></i>
                        This will send an URGENT notification to ALL users immediately.
                    </div>
                    
                    <div class="form-group">
                        <label>Emergency Title</label>
                        <input type="text" name="title" value="🚨 URGENT: " required>
                    </div>
                    
                    <div class="form-group">
                        <label>Emergency Message</label>
                        <textarea name="message" rows="4" placeholder="Describe the emergency situation..." required></textarea>
                    </div>
                    
                    <input type="hidden" name="type" value="emergency">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeEmergencyModal()">Cancel</button>
                    <button type="submit" name="send_notification" class="btn btn-danger">
                        <i class="fa-solid fa-bullhorn"></i> Send Emergency Broadcast
                    </button>
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
            
            document.querySelectorAll('.notif-tab').forEach(btn => {
                btn.classList.remove('active');
            });
            element.classList.add('active');
        }
        
        // Target selector toggle
        function toggleTargetSelector() {
            const selected = document.querySelector('input[name="target_type"]:checked').value;
            document.querySelectorAll('[id^="target-"]').forEach(el => {
                el.style.display = 'none';
            });
            document.getElementById('target-' + selected).style.display = 'block';
        }
        
        // Initialize target selector
        document.addEventListener('DOMContentLoaded', function() {
            toggleTargetSelector();
        });
        
        // Load template
        function loadTemplate(templateId) {
            if (!templateId) return;
            
            // In a real implementation, you'd fetch template data via AJAX
            // For now, we'll just show an alert
            alert('Template loaded! In production, this would populate the form fields.');
        }
        
        // Use template (quick send)
        function useTemplate(templateId) {
            // Switch to send tab and populate form
            document.querySelector('.notif-tab.active')?.classList.remove('active');
            document.querySelector('.notif-tab').classList.add('active');
            showTab('send', document.querySelector('.notif-tab'));
            
            alert('Switch to Send tab to use this template!');
        }
        
        // Emergency modal functions
        function openEmergencyModal() {
            document.getElementById('emergencyModal').classList.add('show');
        }
        
        function closeEmergencyModal() {
            document.getElementById('emergencyModal').classList.remove('show');
        }
        
        // View details (would show more info in a modal)
        function viewDetails(logId) {
            alert('View details for notification #' + logId);
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