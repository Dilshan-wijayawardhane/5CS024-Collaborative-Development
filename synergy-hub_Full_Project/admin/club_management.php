<?php
require_once 'config.php';
checkAdminAuth();

$admin = getAdminInfo($conn);
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'dashboard';
$message = '';
$error = '';

// Handle Add Club
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_club'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $meeting_day = mysqli_real_escape_string($conn, $_POST['meeting_day']);
    $meeting_time = mysqli_real_escape_string($conn, $_POST['meeting_time']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $leader_search = mysqli_real_escape_string($conn, $_POST['leader_search']);
    
    $leader_id = null;
    if (!empty($leader_search)) {
        $leader_sql = "SELECT UserID FROM Users WHERE (StudentID = ? OR Email = ?) AND Role IN ('Admin', 'User')";
        $leader_stmt = mysqli_prepare($conn, $leader_sql);
        mysqli_stmt_bind_param($leader_stmt, "ss", $leader_search, $leader_search);
        mysqli_stmt_execute($leader_stmt);
        $leader_result = mysqli_stmt_get_result($leader_stmt);
        $leader = mysqli_fetch_assoc($leader_result);
        if ($leader) $leader_id = $leader['UserID'];
    }
    
    $insert_sql = "INSERT INTO Clubs (Name, Description, LeaderID, Category, MeetingDay, MeetingTime, Status) 
                   VALUES (?, ?, ?, ?, ?, ?, ?)";
    $insert_stmt = mysqli_prepare($conn, $insert_sql);
    
    // Fix: Handle null leader_id properly
    $null_leader = null;
    if ($leader_id === null) {
        mysqli_stmt_bind_param($insert_stmt, "ssissss", $name, $description, $null_leader, $category, $meeting_day, $meeting_time, $status);
    } else {
        mysqli_stmt_bind_param($insert_stmt, "ssissss", $name, $description, $leader_id, $category, $meeting_day, $meeting_time, $status);
    }
    
    if (mysqli_stmt_execute($insert_stmt)) {
        $message = "Club created successfully!";
        logAdminActivity($conn, 'ADD_CLUB', "Added club: $name");
    } else {
        $error = "Error: " . mysqli_error($conn);
    }
}

// Handle Delete Club
if (isset($_POST['delete_club'])) {
    $club_id = intval($_POST['club_id']);
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Delete club memberships first
        $delete_members_sql = "DELETE FROM ClubMemberships WHERE ClubID = ?";
        $delete_members_stmt = mysqli_prepare($conn, $delete_members_sql);
        mysqli_stmt_bind_param($delete_members_stmt, "i", $club_id);
        mysqli_stmt_execute($delete_members_stmt);
        
        // Delete join requests
        $delete_requests_sql = "DELETE FROM JoinRequests WHERE ClubID = ?";
        $delete_requests_stmt = mysqli_prepare($conn, $delete_requests_sql);
        mysqli_stmt_bind_param($delete_requests_stmt, "i", $club_id);
        mysqli_stmt_execute($delete_requests_stmt);
        
        // Delete the club
        $delete_sql = "DELETE FROM Clubs WHERE ClubID = ?";
        $delete_stmt = mysqli_prepare($conn, $delete_sql);
        mysqli_stmt_bind_param($delete_stmt, "i", $club_id);
        mysqli_stmt_execute($delete_stmt);
        
        mysqli_commit($conn);
        $message = "Club deleted successfully!";
        logAdminActivity($conn, 'DELETE_CLUB', "Deleted club ID: $club_id");
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $error = "Error deleting club: " . $e->getMessage();
    }
}

// Handle Approve Request - FIXED VERSION
if (isset($_POST['approve_request'])) {
    $request_id = intval($_POST['request_id']);
    $club_id = intval($_POST['club_id']);
    $user_id = intval($_POST['user_id']);
    $admin_notes = mysqli_real_escape_string($conn, $_POST['admin_notes'] ?? '');
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Check if user already has an active membership
        $check_member_sql = "SELECT MembershipID FROM ClubMemberships WHERE ClubID = ? AND UserID = ? AND Status = 'Active'";
        $check_member_stmt = mysqli_prepare($conn, $check_member_sql);
        mysqli_stmt_bind_param($check_member_stmt, "ii", $club_id, $user_id);
        mysqli_stmt_execute($check_member_stmt);
        $check_member_result = mysqli_stmt_get_result($check_member_stmt);
        
        if (mysqli_num_rows($check_member_result) > 0) {
            throw new Exception('User is already a member of this club');
        }
        
        // FIRST: Delete any existing approved/rejected/pending requests for this user and club to avoid duplicate constraint
        $delete_old_sql = "DELETE FROM JoinRequests WHERE UserID = ? AND ClubID = ?";
        $delete_old_stmt = mysqli_prepare($conn, $delete_old_sql);
        mysqli_stmt_bind_param($delete_old_stmt, "ii", $user_id, $club_id);
        mysqli_stmt_execute($delete_old_stmt);
        
        // THEN: Insert new approved request (or update if you want to keep history)
        $insert_request_sql = "INSERT INTO JoinRequests (UserID, ClubID, Status, AdminNotes, ReviewedBy, RequestDate, ReviewedAt) 
                               VALUES (?, ?, 'Approved', ?, ?, NOW(), NOW())";
        $insert_request_stmt = mysqli_prepare($conn, $insert_request_sql);
        mysqli_stmt_bind_param($insert_request_stmt, "iisi", $user_id, $club_id, $admin_notes, $_SESSION['user_id']);
        mysqli_stmt_execute($insert_request_stmt);
        
        // Check if user already has membership (in case of soft delete)
        $check_existing = "SELECT MembershipID FROM ClubMemberships WHERE ClubID = ? AND UserID = ?";
        $check_existing_stmt = mysqli_prepare($conn, $check_existing);
        mysqli_stmt_bind_param($check_existing_stmt, "ii", $club_id, $user_id);
        mysqli_stmt_execute($check_existing_stmt);
        $existing_result = mysqli_stmt_get_result($check_existing_stmt);
        
        if (mysqli_num_rows($existing_result) > 0) {
            // Update existing membership to active
            $update_member_sql = "UPDATE ClubMemberships SET Status = 'Active', Role = 'Member', JoinDate = NOW() WHERE ClubID = ? AND UserID = ?";
            $update_member_stmt = mysqli_prepare($conn, $update_member_sql);
            mysqli_stmt_bind_param($update_member_stmt, "ii", $club_id, $user_id);
            mysqli_stmt_execute($update_member_stmt);
        } else {
            // Add new membership
            $insert_sql = "INSERT INTO ClubMemberships (ClubID, UserID, Role, Status, JoinDate) VALUES (?, ?, 'Member', 'Active', NOW())";
            $insert_stmt = mysqli_prepare($conn, $insert_sql);
            mysqli_stmt_bind_param($insert_stmt, "ii", $club_id, $user_id);
            mysqli_stmt_execute($insert_stmt);
        }
        
        // Add 20 points to user
        $points_sql = "UPDATE Users SET PointsBalance = PointsBalance + 20 WHERE UserID = ?";
        $points_stmt = mysqli_prepare($conn, $points_sql);
        mysqli_stmt_bind_param($points_stmt, "i", $user_id);
        mysqli_stmt_execute($points_stmt);
        
        // Add to points history (check if table exists)
        $check_history = mysqli_query($conn, "SHOW TABLES LIKE 'PointsHistory'");
        if (mysqli_num_rows($check_history) > 0) {
            $history_sql = "INSERT INTO PointsHistory (UserID, PointsChange, ActionType, Description, CreatedAt) VALUES (?, 20, 'CLUB_JOIN', 'Joined club via approval', NOW())";
            $history_stmt = mysqli_prepare($conn, $history_sql);
            mysqli_stmt_bind_param($history_stmt, "i", $user_id);
            mysqli_stmt_execute($history_stmt);
        }
        
        // Get club name for notification
        $club_sql = "SELECT Name FROM Clubs WHERE ClubID = ?";
        $club_stmt = mysqli_prepare($conn, $club_sql);
        mysqli_stmt_bind_param($club_stmt, "i", $club_id);
        mysqli_stmt_execute($club_stmt);
        $club_result = mysqli_stmt_get_result($club_stmt);
        $club_name = mysqli_fetch_assoc($club_result)['Name'];
        
        // Check if notifications table exists and has correct columns
        $check_notif = mysqli_query($conn, "SHOW TABLES LIKE 'notifications'");
        if (mysqli_num_rows($check_notif) > 0) {
            // First check the column structure
            $columns = mysqli_query($conn, "SHOW COLUMNS FROM notifications");
            $has_user_id = false;
            $has_title = false;
            $has_message = false;
            $has_type = false;
            
            while($col = mysqli_fetch_assoc($columns)) {
                if($col['Field'] == 'user_id') $has_user_id = true;
                if($col['Field'] == 'title') $has_title = true;
                if($col['Field'] == 'message') $has_message = true;
                if($col['Field'] == 'type') $has_type = true;
            }
            
            if($has_user_id && $has_title && $has_message) {
                $notif_sql = "INSERT INTO notifications (user_id, title, message, type, created_at) 
                             VALUES (?, 'Club Join Request Approved', 
                                    CONCAT('Your request to join ', ?, ' has been approved! You received 20 points.'), 
                                    'club', NOW())";
                $notif_stmt = mysqli_prepare($conn, $notif_sql);
                mysqli_stmt_bind_param($notif_stmt, "is", $user_id, $club_name);
                mysqli_stmt_execute($notif_stmt);
            }
        }
        
        mysqli_commit($conn);
        $message = "Request approved! User added to club and received 20 points.";
        logAdminActivity($conn, 'APPROVE_REQUEST', "Approved request ID: $request_id");
        
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $error = "Error: " . $e->getMessage();
    }
}

// Handle Reject Request - FIXED VERSION
if (isset($_POST['reject_request'])) {
    $request_id = intval($_POST['request_id']);
    $admin_notes = mysqli_real_escape_string($conn, $_POST['admin_notes'] ?? '');
    
    // Get user and club info
    $info_sql = "SELECT jr.UserID, jr.ClubID, c.Name as ClubName, u.Name as UserName 
                 FROM JoinRequests jr
                 JOIN Clubs c ON jr.ClubID = c.ClubID
                 JOIN Users u ON jr.UserID = u.UserID
                 WHERE jr.RequestID = ?";
    $info_stmt = mysqli_prepare($conn, $info_sql);
    mysqli_stmt_bind_param($info_stmt, "i", $request_id);
    mysqli_stmt_execute($info_stmt);
    $info_result = mysqli_stmt_get_result($info_stmt);
    $info = mysqli_fetch_assoc($info_result);
    
    if ($info) {
        // Start transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Delete existing requests for this user and club
            $delete_old_sql = "DELETE FROM JoinRequests WHERE UserID = ? AND ClubID = ?";
            $delete_old_stmt = mysqli_prepare($conn, $delete_old_sql);
            mysqli_stmt_bind_param($delete_old_stmt, "ii", $info['UserID'], $info['ClubID']);
            mysqli_stmt_execute($delete_old_stmt);
            
            // Insert new rejected request
            $insert_sql = "INSERT INTO JoinRequests (UserID, ClubID, Status, AdminNotes, ReviewedBy, RequestDate, ReviewedAt) 
                          VALUES (?, ?, 'Rejected', ?, ?, NOW(), NOW())";
            $insert_stmt = mysqli_prepare($conn, $insert_sql);
            mysqli_stmt_bind_param($insert_stmt, "iisi", $info['UserID'], $info['ClubID'], $admin_notes, $_SESSION['user_id']);
            mysqli_stmt_execute($insert_stmt);
            
            // Check if notifications table exists
            $check_notif = mysqli_query($conn, "SHOW TABLES LIKE 'notifications'");
            if (mysqli_num_rows($check_notif) > 0) {
                $notif_sql = "INSERT INTO notifications (user_id, title, message, type, created_at) 
                             VALUES (?, 'Club Join Request Rejected', 
                                    CONCAT('Your request to join ', ?, ' has been rejected. Reason: ', ?), 
                                    'club', NOW())";
                $notif_stmt = mysqli_prepare($conn, $notif_sql);
                mysqli_stmt_bind_param($notif_stmt, "isss", $info['UserID'], $info['ClubName'], $admin_notes);
                mysqli_stmt_execute($notif_stmt);
            }
            
            mysqli_commit($conn);
            $message = "Request rejected.";
            logAdminActivity($conn, 'REJECT_REQUEST', "Rejected request for club: {$info['ClubName']}");
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error = "Error: " . $e->getMessage();
        }
    } else {
        $error = "Request not found";
    }
}

// Get statistics
$stats_sql = "SELECT 
                (SELECT COUNT(*) FROM Clubs WHERE Status = 'Active') as total_clubs,
                (SELECT COUNT(*) FROM ClubMemberships WHERE Status = 'Active') as total_members,
                (SELECT COUNT(*) FROM JoinRequests WHERE Status = 'Pending') as pending_requests,
                (SELECT COUNT(*) FROM Users WHERE Role = 'User' AND MembershipStatus = 'Active') as active_users";
$stats_result = mysqli_query($conn, $stats_sql);
$stats = mysqli_fetch_assoc($stats_result);

// Get pending join requests (only Pending status)
$pending_sql = "SELECT jr.*, c.Name as ClubName, u.Name as UserName, u.Email, u.StudentID 
                FROM JoinRequests jr
                JOIN Clubs c ON jr.ClubID = c.ClubID
                JOIN Users u ON jr.UserID = u.UserID
                WHERE jr.Status = 'Pending'
                ORDER BY jr.RequestDate DESC";
$pending_result = mysqli_query($conn, $pending_sql);

// Get recent members
$recent_members_sql = "SELECT cm.*, c.Name as ClubName, u.Name as UserName, u.Email
                       FROM ClubMemberships cm
                       JOIN Clubs c ON cm.ClubID = c.ClubID
                       JOIN Users u ON cm.UserID = u.UserID
                       WHERE cm.Status = 'Active'
                       ORDER BY cm.JoinDate DESC
                       LIMIT 5";
$recent_members_result = mysqli_query($conn, $recent_members_sql);

// Get all clubs for clubs tab
$clubs_sql = "SELECT c.*, u.Name as LeaderName, 
             (SELECT COUNT(*) FROM ClubMemberships WHERE ClubID = c.ClubID AND Status = 'Active') as MemberCount
             FROM Clubs c
             LEFT JOIN Users u ON c.LeaderID = u.UserID
             ORDER BY c.Name";
$clubs_result = mysqli_query($conn, $clubs_sql);

// Get all join requests for requests tab (excluding duplicates - show only the latest per user-club)
$all_requests_sql = "SELECT jr1.*, c.Name as ClubName, u.Name as UserName, u.Email, u.StudentID 
                    FROM JoinRequests jr1
                    JOIN Clubs c ON jr1.ClubID = c.ClubID
                    JOIN Users u ON jr1.UserID = u.UserID
                    WHERE jr1.RequestID IN (
                        SELECT MAX(RequestID) 
                        FROM JoinRequests 
                        GROUP BY UserID, ClubID
                    )
                    ORDER BY FIELD(jr1.Status, 'Pending'), jr1.RequestDate DESC";
$all_requests_result = mysqli_query($conn, $all_requests_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Club Management - Synergy Hub Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="admin.css">
    <style>
        .club-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 10px;
            flex-wrap: wrap;
        }
        .club-tab {
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
            display: inline-block;
        }
        .club-tab:hover { background: #f1f5f9; color: #1e293b; }
        .club-tab.active { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .stat-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
        }
        .stat-info h3 { font-size: 24px; color: #1e293b; margin: 0; }
        .stat-info p { color: #64748b; margin: 5px 0 0; font-size: 14px; }
        
        .club-card, .request-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border-left: 4px solid #667eea;
        }
        .pending-card { border-left-color: #f59e0b; background: #fffbeb; }
        .approved-card { border-left-color: #10b981; }
        .rejected-card { border-left-color: #ef4444; }
        
        .club-header, .request-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 10px;
        }
        .club-name { font-size: 18px; font-weight: 600; color: #1e293b; }
        .club-category { background: #e0f2fe; color: #0284c7; padding: 4px 12px; border-radius: 20px; font-size: 12px; }
        .club-details { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px; margin: 15px 0; color: #475569; font-size: 13px; }
        .club-details i { color: #667eea; width: 20px; }
        .action-buttons { display: flex; gap: 10px; margin-top: 15px; }
        .btn-sm { padding: 6px 12px; font-size: 12px; }
        
        .request-user { font-weight: 600; color: #1e293b; }
        .request-details { color: #64748b; font-size: 13px; margin: 5px 0; }
        .pending-actions { display: flex; gap: 10px; margin-top: 15px; }
        
        .form-container { background: white; border-radius: 12px; padding: 25px; max-width: 600px; margin: 0 auto; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; color: #475569; font-size: 14px; font-weight: 500; margin-bottom: 5px; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 14px; }
        
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
        .modal.show { display: flex; }
        .modal-content {
            background: white;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            max-height: 80vh;
            overflow-y: auto;
        }
        .modal-header { padding: 20px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
        .modal-body { padding: 20px; }
        .modal-footer { padding: 20px; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 10px; }
        .modal-close { background: none; border: none; font-size: 24px; cursor: pointer; color: #64748b; }
        
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #86efac; }
        .alert-danger { background: #fee; color: #991b1b; border: 1px solid #fecaca; }
        
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th, .data-table td { padding: 12px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        .data-table th { background: #f8fafc; color: #64748b; font-weight: 600; font-size: 12px; }
        
        .status-badge { padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; display: inline-block; }
        .status-active { background: #dcfce7; color: #16a34a; }
        .status-inactive { background: #fee; color: #ef4444; }
        
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
        }
        .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .btn-secondary { background: #64748b; color: white; }
        .btn-success { background: #10b981; color: white; }
        .btn-danger { background: #ef4444; color: white; }
        .btn-sm { padding: 4px 8px; font-size: 12px; }
        
        .view-all {
            display: inline-block;
            margin-top: 10px;
            color: #667eea;
            text-decoration: none;
            font-size: 13px;
        }
        .dashboard-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
        }
        
        /* Utility classes */
        .text-center { text-align: center; }
        .mt-3 { margin-top: 15px; }
    </style>
</head>
<body>
    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include 'includes/topbar.php'; ?>
            
            <div class="content">
                <h1 class="page-title"><i class="fa-solid fa-users"></i> Club Management</h1>
                
                <?php if($message): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>
                <?php if($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <!-- Stats -->
                <div class="stats-grid">
                    <div class="stat-card"><div class="stat-icon"><i class="fa-solid fa-building"></i></div><div class="stat-info"><h3><?php echo $stats['total_clubs']; ?></h3><p>Total Clubs</p></div></div>
                    <div class="stat-card"><div class="stat-icon"><i class="fa-solid fa-users"></i></div><div class="stat-info"><h3><?php echo $stats['total_members']; ?></h3><p>Total Members</p></div></div>
                    <div class="stat-card"><div class="stat-icon"><i class="fa-solid fa-clock"></i></div><div class="stat-info"><h3><?php echo $stats['pending_requests']; ?></h3><p>Pending Requests</p></div></div>
                    <div class="stat-card"><div class="stat-icon"><i class="fa-solid fa-user-check"></i></div><div class="stat-info"><h3><?php echo $stats['active_users']; ?></h3><p>Active Users</p></div></div>
                </div>
                
                <!-- Tabs -->
                <div class="club-tabs">
                    <a href="?tab=dashboard" class="club-tab <?php echo $active_tab == 'dashboard' ? 'active' : ''; ?>"><i class="fa-solid fa-chart-line"></i> Dashboard</a>
                    <a href="?tab=clubs" class="club-tab <?php echo $active_tab == 'clubs' ? 'active' : ''; ?>"><i class="fa-solid fa-list"></i> Manage Clubs</a>
                    <a href="?tab=add" class="club-tab <?php echo $active_tab == 'add' ? 'active' : ''; ?>"><i class="fa-solid fa-plus"></i> Add Club</a>
                    <a href="?tab=requests" class="club-tab <?php echo $active_tab == 'requests' ? 'active' : ''; ?>"><i class="fa-solid fa-ticket"></i> Join Requests <?php if($stats['pending_requests'] > 0): ?><span style="background: #f59e0b; color: white; padding: 2px 6px; border-radius: 10px; margin-left: 5px;"><?php echo $stats['pending_requests']; ?></span><?php endif; ?></a>
                    <a href="?tab=members" class="club-tab <?php echo $active_tab == 'members' ? 'active' : ''; ?>"><i class="fa-solid fa-id-card"></i> Members</a>
                </div>
                
                <!-- Dashboard Tab -->
                <div id="tab-dashboard" class="tab-content <?php echo $active_tab == 'dashboard' ? 'active' : ''; ?>">
                    <div class="dashboard-grid">
                        <div class="dashboard-card">
                            <h3>Pending Join Requests</h3>
                            <?php if(mysqli_num_rows($pending_result) > 0): ?>
                                <?php while($request = mysqli_fetch_assoc($pending_result)): ?>
                                <div class="request-card pending-card" style="margin-bottom: 10px;">
                                    <div><strong><?php echo htmlspecialchars($request['UserName']); ?></strong> wants to join <strong><?php echo htmlspecialchars($request['ClubName']); ?></strong></div>
                                    <div class="request-details">Requested: <?php echo date('M d, Y', strtotime($request['RequestDate'])); ?></div>
                                    <div class="pending-actions">
                                        <form method="POST" action="" style="display: inline-block;">
                                            <input type="hidden" name="request_id" value="<?php echo $request['RequestID']; ?>">
                                            <input type="hidden" name="club_id" value="<?php echo $request['ClubID']; ?>">
                                            <input type="hidden" name="user_id" value="<?php echo $request['UserID']; ?>">
                                            <button type="submit" name="approve_request" class="btn btn-success btn-sm" onclick="return confirm('Approve this request? User will receive 20 points.');">Approve</button>
                                        </form>
                                        <form method="POST" action="" style="display: inline-block;">
                                            <input type="hidden" name="request_id" value="<?php echo $request['RequestID']; ?>">
                                            <input type="text" name="admin_notes" placeholder="Reason" style="padding: 6px; width: 200px;">
                                            <button type="submit" name="reject_request" class="btn btn-danger btn-sm">Reject</button>
                                        </form>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <p>No pending requests</p>
                            <?php endif; ?>
                            <a href="?tab=requests" class="view-all">View All Requests →</a>
                        </div>
                        
                        <div class="dashboard-card">
                            <h3>Recent Members</h3>
                            <?php if(mysqli_num_rows($recent_members_result) > 0): ?>
                                <?php while($member = mysqli_fetch_assoc($recent_members_result)): ?>
                                <div style="padding: 10px; border-bottom: 1px solid #e2e8f0;">
                                    <div><strong><?php echo htmlspecialchars($member['UserName']); ?></strong> joined <strong><?php echo htmlspecialchars($member['ClubName']); ?></strong></div>
                                    <div class="request-details"><?php echo date('M d, Y', strtotime($member['JoinDate'])); ?></div>
                                </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <p>No recent members</p>
                            <?php endif; ?>
                            <a href="?tab=members" class="view-all">View All Members →</a>
                        </div>
                    </div>
                </div>
                
                <!-- Clubs Tab -->
                <div id="tab-clubs" class="tab-content <?php echo $active_tab == 'clubs' ? 'active' : ''; ?>">
                    <?php if(mysqli_num_rows($clubs_result) > 0): ?>
                        <?php while($club = mysqli_fetch_assoc($clubs_result)): ?>
                        <div class="club-card">
                            <div class="club-header">
                                <div><span class="club-name"><?php echo htmlspecialchars($club['Name']); ?></span> <span class="club-category"><?php echo htmlspecialchars($club['Category']); ?></span></div>
                                <div><span class="status-badge status-<?php echo strtolower($club['Status']); ?>"><?php echo $club['Status']; ?></span></div>
                            </div>
                            <div class="club-details">
                                <div><i class="fa-regular fa-calendar"></i> Since <?php echo date('Y', strtotime($club['CreatedAt'])); ?></div>
                                <div><i class="fa-solid fa-users"></i> Members: <?php echo $club['MemberCount']; ?></div>
                                <div><i class="fa-solid fa-crown"></i> Leader: <?php echo htmlspecialchars($club['LeaderName'] ?: 'Not Assigned'); ?></div>
                            </div>
                            <div class="club-details">
                                <?php if($club['MeetingDay']): ?><div><i class="fa-regular fa-calendar-alt"></i> <?php echo htmlspecialchars($club['MeetingDay']); ?> at <?php echo htmlspecialchars($club['MeetingTime']); ?></div><?php endif; ?>
                            </div>
                            <div class="action-buttons">
                                <button class="btn btn-primary btn-sm" onclick="editClub(<?php echo $club['ClubID']; ?>)">Edit</button>
                                <button class="btn btn-secondary btn-sm" onclick="viewMembers(<?php echo $club['ClubID']; ?>)">Members</button>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this club? This will remove all memberships and requests.');">
                                    <input type="hidden" name="club_id" value="<?php echo $club['ClubID']; ?>">
                                    <button type="submit" name="delete_club" class="btn btn-danger btn-sm">Delete</button>
                                </form>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p>No clubs found. <a href="?tab=add">Create your first club</a></p>
                    <?php endif; ?>
                </div>
                
                <!-- Add Club Tab -->
                <div id="tab-add" class="tab-content <?php echo $active_tab == 'add' ? 'active' : ''; ?>">
                    <div class="form-container">
                        <h3>Add New Club</h3>
                        <form method="POST">
                            <div class="form-row">
                                <div class="form-group"><label>Club Name *</label><input type="text" name="name" required></div>
                                <div class="form-group"><label>Category *</label><select name="category" required><option value="Technical">Technical</option><option value="Cultural">Cultural</option><option value="Sports">Sports</option><option value="Academic">Academic</option><option value="Arts">Arts</option><option value="Other">Other</option></select></div>
                            </div>
                            <div class="form-group"><label>Description *</label><textarea name="description" rows="4" required></textarea></div>
                            <div class="form-row">
                                <div class="form-group"><label>Meeting Day</label><input type="text" name="meeting_day" placeholder="e.g., Monday"></div>
                                <div class="form-group"><label>Meeting Time</label><input type="text" name="meeting_time" placeholder="e.g., 3:00 PM"></div>
                            </div>
                            <div class="form-row">
                                <div class="form-group"><label>Leader (Student ID or Email)</label><input type="text" name="leader_search" placeholder="Search by Student ID or Email"></div>
                                <div class="form-group"><label>Status</label><select name="status"><option value="Active">Active</option><option value="Inactive">Inactive</option></select></div>
                            </div>
                            <button type="submit" name="add_club" class="btn btn-primary">Create Club</button>
                        </form>
                    </div>
                </div>
                
                <!-- Requests Tab -->
                <div id="tab-requests" class="tab-content <?php echo $active_tab == 'requests' ? 'active' : ''; ?>">
                    <h3>All Join Requests</h3>
                    <?php if(mysqli_num_rows($all_requests_result) > 0): ?>
                        <?php while($request = mysqli_fetch_assoc($all_requests_result)): 
                            $status_class = $request['Status'] == 'Pending' ? 'pending-card' : ($request['Status'] == 'Approved' ? 'approved-card' : 'rejected-card');
                        ?>
                        <div class="request-card <?php echo $status_class; ?>">
                            <div class="request-header">
                                <div><span class="request-user"><?php echo htmlspecialchars($request['UserName']); ?></span> wants to join <strong><?php echo htmlspecialchars($request['ClubName']); ?></strong></div>
                                <span class="status-badge" style="background: <?php echo $request['Status'] == 'Pending' ? '#f59e0b' : ($request['Status'] == 'Approved' ? '#10b981' : '#ef4444'); ?>; color: white;"><?php echo $request['Status']; ?></span>
                            </div>
                            <div class="request-details">Student ID: <?php echo htmlspecialchars($request['StudentID']); ?> | Requested: <?php echo date('M d, Y', strtotime($request['RequestDate'])); ?></div>
                            <?php if($request['Status'] == 'Pending'): ?>
                            <div class="pending-actions">
                                <form method="POST" action="" style="display: inline-block;">
                                    <input type="hidden" name="request_id" value="<?php echo $request['RequestID']; ?>">
                                    <input type="hidden" name="club_id" value="<?php echo $request['ClubID']; ?>">
                                    <input type="hidden" name="user_id" value="<?php echo $request['UserID']; ?>">
                                    <button type="submit" name="approve_request" class="btn btn-success btn-sm" onclick="return confirm('Approve this request? User will receive 20 points.');">Approve</button>
                                </form>
                                <form method="POST" action="" style="display: inline-block;">
                                    <input type="hidden" name="request_id" value="<?php echo $request['RequestID']; ?>">
                                    <input type="text" name="admin_notes" placeholder="Reason" style="padding: 6px; width: 200px;">
                                    <button type="submit" name="reject_request" class="btn btn-danger btn-sm">Reject</button>
                                </form>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p>No join requests found.</p>
                    <?php endif; ?>
                </div>
                
                <!-- Members Tab -->
                <div id="tab-members" class="tab-content <?php echo $active_tab == 'members' ? 'active' : ''; ?>">
                    <div class="form-container" style="margin-bottom: 20px;">
                        <div class="form-row">
                            <div class="form-group"><label>Select Club</label>
                                <select id="member_club_select" onchange="loadMembers()">
                                    <option value="">Select a club</option>
                                    <?php
                                    mysqli_data_seek($clubs_result, 0);
                                    while($c = mysqli_fetch_assoc($clubs_result)):
                                    ?>
                                    <option value="<?php echo $c['ClubID']; ?>"><?php echo htmlspecialchars($c['Name']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="form-group"><label>Search Member</label><input type="text" id="member_search" placeholder="Search by name or email"></div>
                        </div>
                        <button class="btn btn-secondary btn-sm" onclick="exportMembers()"><i class="fa-solid fa-download"></i> Export to CSV</button>
                    </div>
                    <div id="members_list"></div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function editClub(clubId) {
            window.location.href = 'edit_club.php?id=' + clubId;
        }
        
        function viewMembers(clubId) {
            document.getElementById('member_club_select').value = clubId;
            document.querySelector('a[href="?tab=members"]').click();
            loadMembers();
        }
        
        function loadMembers() {
            const clubId = document.getElementById('member_club_select').value;
            if (!clubId) { document.getElementById('members_list').innerHTML = ''; return; }
            fetch('get_club_members.php?club_id=' + clubId)
                .then(response => response.json())
                .then(data => {
                    let html = '<div class="table-container"><table class="data-table"><thead><tr><th>Name</th><th>Student ID</th><th>Email</th><th>Role</th><th>Join Date</th><th>Actions</th></tr></thead><tbody>';
                    data.forEach(member => {
                        html += `<tr>
                                      <td>${escapeHtml(member.Name)}</td>
                                      <td>${escapeHtml(member.StudentID || 'N/A')}</td>
                                      <td>${escapeHtml(member.Email)}</td>
                                      <td>
                                        <select onchange="updateRole(${member.UserID}, ${member.ClubID}, this.value)">
                                            <option value="Member" ${member.Role == 'Member' ? 'selected' : ''}>Member</option>
                                            <option value="Leader" ${member.Role == 'Leader' ? 'selected' : ''}>Leader</option>
                                        </select>
                                      </td>
                                      <td>${member.JoinDate}</td>
                                      <td><button class="btn btn-danger btn-sm" onclick="removeMember(${member.UserID}, ${member.ClubID})">Remove</button></td>
                                  </tr>`;
                    });
                    html += '</tbody><table></div>';
                    document.getElementById('members_list').innerHTML = html;
                    
                    const searchInput = document.getElementById('member_search');
                    if(searchInput) {
                        searchInput.onkeyup = function() {
                            const searchTerm = this.value.toLowerCase();
                            const rows = document.querySelectorAll('#members_list tbody tr');
                            rows.forEach(row => {
                                const text = row.textContent.toLowerCase();
                                row.style.display = text.includes(searchTerm) ? '' : 'none';
                            });
                        };
                    }
                })
                .catch(error => {
                    console.error('Error loading members:', error);
                    document.getElementById('members_list').innerHTML = '<p class="text-center">Error loading members. Please make sure get_club_members.php exists.</p>';
                });
        }
        
        function escapeHtml(text) {
            if(!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        function updateRole(userId, clubId, role) {
            fetch('update_member_role.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `user_id=${userId}&club_id=${clubId}&role=${role}`
            }).then(response => response.json()).then(data => { if(data.success) loadMembers(); });
        }
        
        function removeMember(userId, clubId) {
            if(confirm('Remove this member from the club?')) {
                fetch('remove_member.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `user_id=${userId}&club_id=${clubId}`
                }).then(response => response.json()).then(data => { if(data.success) loadMembers(); });
            }
        }
        
        function exportMembers() {
            const clubId = document.getElementById('member_club_select').value;
            if(clubId) window.location.href = 'export_members.php?club_id=' + clubId;
        }
        
        // Set active tab on page load
        document.addEventListener('DOMContentLoaded', function() {
            const activeTab = '<?php echo $active_tab; ?>';
            if(activeTab === 'members' && document.getElementById('member_club_select').value) {
                loadMembers();
            }
        });
    </script>
</body>
</html>