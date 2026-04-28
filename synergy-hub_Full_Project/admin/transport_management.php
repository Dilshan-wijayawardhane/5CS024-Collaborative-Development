<?php
require_once 'config.php';
require_once 'middleware.php';
checkAdminAuth();

$admin = getAdminInfo($conn);
$message = '';
$error = '';

// Handle Bus Route Operations (Using your existing route definitions)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Update Route Settings (store in a settings table or config file)
    if (isset($_POST['save_route_settings'])) {
        $route_key = mysqli_real_escape_string($conn, $_POST['route_key']);
        $price = intval($_POST['price']);
        $frequency = mysqli_real_escape_string($conn, $_POST['frequency']);
        $capacity = intval($_POST['capacity']);
        $status = mysqli_real_escape_string($conn, $_POST['status']);
        
        // Store in session or create a transport_settings table
        if (!isset($_SESSION['transport_settings'])) {
            $_SESSION['transport_settings'] = [];
        }
        $_SESSION['transport_settings'][$route_key] = [
            'price' => $price,
            'frequency' => $frequency,
            'capacity' => $capacity,
            'status' => $status
        ];
        
        $message = "Route settings saved successfully!";
        logAdminActivity($conn, 'SAVE_ROUTE_SETTINGS', "Saved settings for route: $route_key");
    }
    
    // Update Bus Location (Using your existing bus_routes table)
    if (isset($_POST['update_location'])) {
        $route_key = mysqli_real_escape_string($conn, $_POST['route_key']);
        $location = mysqli_real_escape_string($conn, $_POST['location']);
        $updated_time = date('h:i A');
        
        // Check if route exists in bus_routes
        $check_sql = "SELECT id FROM bus_routes WHERE route_id = ?";
        $check_stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($check_stmt, "s", $route_key);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);
        
        if (mysqli_num_rows($check_result) > 0) {
            // Update existing
            $sql = "UPDATE bus_routes SET location=?, updated_time=? WHERE route_id=?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "sss", $location, $updated_time, $route_key);
        } else {
            // Insert new
            $sql = "INSERT INTO bus_routes (route_id, location, updated_time) VALUES (?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "sss", $route_key, $location, $updated_time);
        }
        
        if (mysqli_stmt_execute($stmt)) {
            $message = "Bus location updated!";
            logAdminActivity($conn, 'UPDATE_LOCATION', "Updated location for route: $route_key");
        } else {
            $error = "Error updating location: " . mysqli_error($conn);
        }
    }
    
    // Update Campus Transport Schedule (Using your existing campus_transport table)
    if (isset($_POST['save_schedule'])) {
        $schedule_id = intval($_POST['schedule_id']);
        $from_campus = mysqli_real_escape_string($conn, $_POST['from_campus']);
        $to_campus = mysqli_real_escape_string($conn, $_POST['to_campus']);
        $next_departure = mysqli_real_escape_string($conn, $_POST['next_departure']);
        $status = mysqli_real_escape_string($conn, $_POST['status']);
        $frequency = mysqli_real_escape_string($conn, $_POST['frequency']);
        
        if ($schedule_id > 0) {
            $sql = "UPDATE campus_transport SET from_campus=?, to_campus=?, next_departure=?, status=?, frequency=? WHERE route_id=?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "sssssi", $from_campus, $to_campus, $next_departure, $status, $frequency, $schedule_id);
        } else {
            $sql = "INSERT INTO campus_transport (from_campus, to_campus, next_departure, status, frequency) VALUES (?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "sssss", $from_campus, $to_campus, $next_departure, $status, $frequency);
        }
        
        if (mysqli_stmt_execute($stmt)) {
            $message = "Schedule updated successfully!";
            logAdminActivity($conn, 'UPDATE_SCHEDULE', "Updated schedule: $from_campus -> $to_campus");
        } else {
            $error = "Error updating schedule: " . mysqli_error($conn);
        }
    }
    
    // Delete Schedule
    if (isset($_POST['delete_schedule'])) {
        $schedule_id = intval($_POST['schedule_id']);
        $delete_sql = "DELETE FROM campus_transport WHERE route_id = ?";
        $delete_stmt = mysqli_prepare($conn, $delete_sql);
        mysqli_stmt_bind_param($delete_stmt, "i", $schedule_id);
        
        if (mysqli_stmt_execute($delete_stmt)) {
            $message = "Schedule deleted successfully!";
            logAdminActivity($conn, 'DELETE_SCHEDULE', "Deleted schedule ID: $schedule_id");
        } else {
            $error = "Error deleting schedule";
        }
    }
    
// Approve Pass Request
    if (isset($_POST['approve_pass'])) {
        $pass_id = intval($_POST['pass_id']);
        $valid_until = $_POST['valid_until'];
        
        // Get the pending pass details first
        $pass_sql = "SELECT UserID, RouteName, points_spent FROM TransportPasses WHERE pass_id = ? AND Status = 'Pending'";
        $pass_stmt = mysqli_prepare($conn, $pass_sql);
        mysqli_stmt_bind_param($pass_stmt, "i", $pass_id);
        mysqli_stmt_execute($pass_stmt);
        $pass_result = mysqli_stmt_get_result($pass_stmt);
        $pending_pass = mysqli_fetch_assoc($pass_result);
        
        if ($pending_pass) {
            // Start transaction
            mysqli_begin_transaction($conn);
            
            try {
                // Deduct points from user
                $deduct_sql = "UPDATE Users SET PointsBalance = PointsBalance - ? WHERE UserID = ?";
                $deduct_stmt = mysqli_prepare($conn, $deduct_sql);
                mysqli_stmt_bind_param($deduct_stmt, "ii", $pending_pass['points_spent'], $pending_pass['UserID']);
                mysqli_stmt_execute($deduct_stmt);
                
                // Update pass status
                $sql = "UPDATE TransportPasses SET Status='Active', ValidUntil=? WHERE pass_id=? AND Status='Pending'";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "si", $valid_until, $pass_id);
                mysqli_stmt_execute($stmt);
                
                mysqli_commit($conn);
                
                // Get user info for notification
                $user_sql = "SELECT Name, Email FROM Users WHERE UserID = ?";
                $user_stmt = mysqli_prepare($conn, $user_sql);
                mysqli_stmt_bind_param($user_stmt, "i", $pending_pass['UserID']);
                mysqli_stmt_execute($user_stmt);
                $user_result = mysqli_stmt_get_result($user_stmt);
                $user = mysqli_fetch_assoc($user_result);
                
                if ($user) {
                    $notif_sql = "INSERT INTO notifications (user_id, title, message, type, created_at) 
                                VALUES (?, 'Transport Pass Approved', 
                                        CONCAT('Your transport pass for ', ?, ' has been approved! Valid until ', ?), 
                                        'transport', NOW())";
                    $notif_stmt = mysqli_prepare($conn, $notif_sql);
                    mysqli_stmt_bind_param($notif_stmt, "isss", $pending_pass['UserID'], $pending_pass['RouteName'], $valid_until);
                    mysqli_stmt_execute($notif_stmt);
                }
                
                $message = "Pass approved and points deducted successfully!";
                logAdminActivity($conn, 'APPROVE_PASS', "Approved pass ID: $pass_id, deducted: {$pending_pass['points_spent']} points");
            } catch (Exception $e) {
                mysqli_rollback($conn);
                $error = "Error approving pass: " . $e->getMessage();
            }
        } else {
            $error = "Pass not found or already processed";
        }
    }

    // Reject Pass Request
    if (isset($_POST['reject_pass'])) {
        $pass_id = intval($_POST['pass_id']);
        $reason = mysqli_real_escape_string($conn, $_POST['reason'] ?? 'No reason provided');
        
        // Get user info before deleting
        $user_sql = "SELECT UserID, RouteName FROM TransportPasses WHERE pass_id = ? AND Status = 'Pending'";
        $user_stmt = mysqli_prepare($conn, $user_sql);
        mysqli_stmt_bind_param($user_stmt, "i", $pass_id);
        mysqli_stmt_execute($user_stmt);
        $user_result = mysqli_stmt_get_result($user_stmt);
        $user = mysqli_fetch_assoc($user_result);
        
        if ($user) {
            // Delete the pending request
            $sql = "DELETE FROM TransportPasses WHERE pass_id=? AND Status='Pending'";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $pass_id);
            
            if (mysqli_stmt_execute($stmt)) {
                // Add rejection notification
                $notif_sql = "INSERT INTO notifications (user_id, title, message, type, created_at) 
                            VALUES (?, 'Transport Pass Rejected', 
                                    CONCAT('Your transport pass request for ', ?, ' was rejected. Reason: ', ?), 
                                    'transport', NOW())";
                $notif_stmt = mysqli_prepare($conn, $notif_sql);
                mysqli_stmt_bind_param($notif_stmt, "isss", $user['UserID'], $user['RouteName'], $reason);
                mysqli_stmt_execute($notif_stmt);
                
                $message = "Pass request rejected and removed!";
                logAdminActivity($conn, 'REJECT_PASS', "Rejected pass ID: $pass_id, Reason: $reason");
            } else {
                $error = "Error rejecting pass";
            }
        } else {
            $error = "Pass not found or already processed";
        }
    }
    
    // Cancel Active Pass
    if (isset($_POST['cancel_pass'])) {
        $pass_id = intval($_POST['pass_id']);
        
        $sql = "UPDATE TransportPasses SET status='Cancelled' WHERE pass_id=? AND status='Active'";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $pass_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $message = "Pass cancelled successfully!";
            logAdminActivity($conn, 'CANCEL_PASS', "Cancelled pass ID: $pass_id");
        } else {
            $error = "Error cancelling pass";
        }
    }
}

// Get bus locations from your existing bus_routes table
$bus_locations_sql = "SELECT * FROM bus_routes ORDER BY route_id";
$bus_locations_result = mysqli_query($conn, $bus_locations_sql);

// Get campus transport schedules from your existing campus_transport table
$schedules_sql = "SELECT * FROM campus_transport ORDER BY 
                  CASE WHEN from_campus = 'CINEC' THEN 1 ELSE 0 END, from_campus";
$schedules_result = mysqli_query($conn, $schedules_sql);

// Get pass requests (Pending) from your existing TransportPasses table
$pending_passes_sql = "SELECT tp.*, u.Name, u.Email, u.StudentID, u.PointsBalance 
                       FROM TransportPasses tp
                       JOIN Users u ON tp.UserID = u.UserID
                       WHERE tp.status = 'Pending'
                       ORDER BY tp.IssuedAt DESC";
$pending_passes_result = mysqli_query($conn, $pending_passes_sql);

// Get active passes
$active_passes_sql = "SELECT tp.*, u.Name, u.Email, u.StudentID, u.PointsBalance 
                      FROM TransportPasses tp
                      JOIN Users u ON tp.UserID = u.UserID
                      WHERE tp.status IN ('Active', 'Expired')
                      ORDER BY tp.ValidUntil ASC
                      LIMIT 50";
$active_passes_result = mysqli_query($conn, $active_passes_sql);

// Get pass statistics
$stats_sql = "SELECT 
                COUNT(CASE WHEN status='Pending' THEN 1 END) as pending_count,
                COUNT(CASE WHEN status='Active' THEN 1 END) as active_count,
                COUNT(CASE WHEN status='Expired' THEN 1 END) as expired_count,
                COUNT(CASE WHEN status='Cancelled' THEN 1 END) as cancelled_count
              FROM TransportPasses";
$stats_result = mysqli_query($conn, $stats_sql);
$pass_stats = mysqli_fetch_assoc($stats_result);

// Route definitions with default settings (stored in session or can be moved to database)
$route_settings = $_SESSION['transport_settings'] ?? [];
$route_definitions = [
    'cinec' => [
        'name' => 'Malabe', 
        'price' => $route_settings['cinec']['price'] ?? 100, 
        'frequency' => $route_settings['cinec']['frequency'] ?? 'Every 30 mins', 
        'capacity' => $route_settings['cinec']['capacity'] ?? 40,
        'status' => $route_settings['cinec']['status'] ?? 'Active'
    ],
    'gampaha1' => [
        'name' => 'Gampaha - 1', 
        'price' => $route_settings['gampaha1']['price'] ?? 120, 
        'frequency' => $route_settings['gampaha1']['frequency'] ?? 'Every 45 mins', 
        'capacity' => $route_settings['gampaha1']['capacity'] ?? 35,
        'status' => $route_settings['gampaha1']['status'] ?? 'Active'
    ],
    'gampaha2' => [
        'name' => 'Gampaha - 2', 
        'price' => $route_settings['gampaha2']['price'] ?? 120, 
        'frequency' => $route_settings['gampaha2']['frequency'] ?? 'Every 45 mins', 
        'capacity' => $route_settings['gampaha2']['capacity'] ?? 35,
        'status' => $route_settings['gampaha2']['status'] ?? 'Active'
    ],
    'hendala' => [
        'name' => 'Hendala', 
        'price' => $route_settings['hendala']['price'] ?? 80, 
        'frequency' => $route_settings['hendala']['frequency'] ?? 'Every 20 mins', 
        'capacity' => $route_settings['hendala']['capacity'] ?? 30,
        'status' => $route_settings['hendala']['status'] ?? 'Active'
    ],
    'moratuwa' => [
        'name' => 'Moratuwa', 
        'price' => $route_settings['moratuwa']['price'] ?? 150, 
        'frequency' => $route_settings['moratuwa']['frequency'] ?? 'Every 60 mins', 
        'capacity' => $route_settings['moratuwa']['capacity'] ?? 45,
        'status' => $route_settings['moratuwa']['status'] ?? 'Active'
    ],
    'negombo' => [
        'name' => 'Negombo', 
        'price' => $route_settings['negombo']['price'] ?? 200, 
        'frequency' => $route_settings['negombo']['frequency'] ?? 'Every 90 mins', 
        'capacity' => $route_settings['negombo']['capacity'] ?? 50,
        'status' => $route_settings['negombo']['status'] ?? 'Active'
    ],
];

// Handle tab parameter from URL
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'routes';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transport Management - Synergy Hub Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="admin.css">
    <style>
        .transport-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 10px;
            flex-wrap: wrap;
        }
        
        .transport-tab {
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
        
        .transport-tab:hover {
            background: #f1f5f9;
            color: #1e293b;
        }
        
        .transport-tab.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
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
        
        .stat-info h3 {
            font-size: 24px;
            color: #1e293b;
            margin: 0;
        }
        
        .stat-info p {
            color: #64748b;
            margin: 5px 0 0;
            font-size: 14px;
        }
        
        .route-card, .schedule-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border-left: 4px solid #667eea;
        }
        
        .route-header, .schedule-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .route-name {
            font-size: 18px;
            font-weight: 600;
            color: #1e293b;
        }
        
        .route-price {
            font-size: 16px;
            color: #667eea;
            font-weight: 600;
        }
        
        .route-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
            margin: 15px 0;
            color: #475569;
            font-size: 13px;
        }
        
        .route-details i {
            color: #667eea;
            width: 20px;
        }
        
        .location-card {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 20px;
            color: white;
        }
        
        .location-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .location-name {
            font-size: 18px;
            font-weight: 600;
        }
        
        .location-status {
            font-size: 12px;
            padding: 4px 12px;
            border-radius: 20px;
            background: rgba(255,255,255,0.1);
        }
        
        .map-preview {
            height: 200px;
            background: #1a2a3a;
            border-radius: 12px;
            margin: 15px 0;
            overflow: hidden;
        }
        
        .map-preview iframe {
            width: 100%;
            height: 100%;
            border: none;
        }
        
        .location-input-group {
            display: flex;
            gap: 10px;
            margin: 15px 0;
        }
        
        .location-input-group input {
            flex: 1;
            padding: 10px;
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 8px;
            background: rgba(255,255,255,0.05);
            color: white;
        }
        
        .location-input-group input::placeholder {
            color: #64748b;
        }
        
        .pending-card {
            background: #fffbeb;
            border-left: 4px solid #f59e0b;
        }
        
        .pending-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .user-name {
            font-weight: 600;
            color: #1e293b;
        }
        
        .user-details {
            color: #64748b;
            font-size: 13px;
            margin: 5px 0;
        }
        
        .pending-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }
        
        .btn-warning {
            background: #f59e0b;
            color: white;
        }
        
        .btn-warning:hover {
            background: #d97706;
        }
        
        .active-pass-card {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
            border: 1px solid #e2e8f0;
        }
        
        .pass-status {
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .pass-status-active {
            background: #dcfce7;
            color: #16a34a;
        }
        
        .pass-status-expired {
            background: #fee;
            color: #ef4444;
        }
        
        .pass-status-pending {
            background: #fff7ed;
            color: #ea580c;
        }
        
        .form-container {
            background: white;
            border-radius: 12px;
            padding: 25px;
            max-width: 600px;
            margin: 0 auto 30px;
        }
        
        .schedule-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .schedule-table th,
        .schedule-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .schedule-table th {
            background: #f8fafc;
            color: #64748b;
            font-weight: 600;
            font-size: 12px;
        }
        
        .evening-bus {
            background: #e0f2fe;
        }
        
        .morning-bus {
            background: #fef9c3;
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
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #86efac;
        }
        
        .alert-danger {
            background: #fee;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        
        .status-OnTime, .status-On-Time {
            background: #10b981;
            color: white;
        }
        
        .status-Sharp {
            background: #22d3ee;
            color: white;
        }
        
        .status-Delayed {
            background: #f59e0b;
            color: white;
        }
        
        .status-Cancelled {
            background: #ef4444;
            color: white;
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .location-input-group {
                flex-direction: column;
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
                    <i class="fa-solid fa-bus"></i> Transport Management
                </h1>
                
                <?php if($message): ?>
                    <div class="alert alert-success"><?php echo $message; ?></div>
                <?php endif; ?>
                <?php if($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <!-- Pass Statistics -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fa-solid fa-clock"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $pass_stats['pending_count']; ?></h3>
                            <p>Pending Requests</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fa-solid fa-check-circle"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $pass_stats['active_count']; ?></h3>
                            <p>Active Passes</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fa-solid fa-calendar-times"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $pass_stats['expired_count']; ?></h3>
                            <p>Expired Passes</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fa-solid fa-ban"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $pass_stats['cancelled_count']; ?></h3>
                            <p>Cancelled Passes</p>
                        </div>
                    </div>
                </div>
                
                <!-- Tabs -->
                <div class="transport-tabs">
                    <a href="transport_management.php?tab=routes" class="transport-tab <?php echo $active_tab == 'routes' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-route"></i> Bus Routes
                    </a>
                    <a href="transport_management.php?tab=tracking" class="transport-tab <?php echo $active_tab == 'tracking' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-location-dot"></i> Live Tracking
                    </a>
                    <a href="transport_management.php?tab=schedule" class="transport-tab <?php echo $active_tab == 'schedule' ? 'active' : ''; ?>">
                        <i class="fa-regular fa-calendar"></i> CINEC Schedule
                    </a>
                    <a href="transport_management.php?tab=requests" class="transport-tab <?php echo $active_tab == 'requests' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-ticket"></i> Pass Requests
                        <?php if($pass_stats['pending_count'] > 0): ?>
                            <span class="badge badge-warning" style="margin-left: 5px; background: #f59e0b; color: white; padding: 2px 6px; border-radius: 10px;"><?php echo $pass_stats['pending_count']; ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="transport_management.php?tab=passes" class="transport-tab <?php echo $active_tab == 'passes' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-id-card"></i> Active Passes
                    </a>
                </div>
                
                <!-- Tab: Bus Routes -->
                <div id="tab-routes" class="tab-content <?php echo $active_tab == 'routes' ? 'active' : ''; ?>">
                    <div class="form-container">
                        <h3>Edit Route Settings</h3>
                        <form method="POST">
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Select Route</label>
                                    <select name="route_key" id="route_select" onchange="loadRouteSettings()" required>
                                        <option value="">Select a route</option>
                                        <?php foreach($route_definitions as $key => $route): ?>
                                        <option value="<?php echo $key; ?>"><?php echo $route['name']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Price (Points)</label>
                                    <input type="number" name="price" id="route_price" min="0" required>
                                </div>
                                <div class="form-group">
                                    <label>Frequency</label>
                                    <input type="text" name="frequency" id="route_frequency" placeholder="e.g., Every 30 mins">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Capacity</label>
                                    <input type="number" name="capacity" id="route_capacity" min="1">
                                </div>
                                <div class="form-group">
                                    <label>Status</label>
                                    <select name="status" id="route_status">
                                        <option value="Active">Active</option>
                                        <option value="Inactive">Inactive</option>
                                    </select>
                                </div>
                            </div>
                            
                            <button type="submit" name="save_route_settings" class="btn btn-primary">
                                <i class="fa-solid fa-save"></i> Save Settings
                            </button>
                        </form>
                    </div>
                    
                    <h3>Route Information</h3>
                    <?php foreach($route_definitions as $key => $route): ?>
                    <div class="route-card">
                        <div class="route-header">
                            <span class="route-name"><?php echo $route['name']; ?></span>
                            <span class="route-price"><?php echo $route['price']; ?> points</span>
                        </div>
                        <div class="route-details">
                            <div><i class="fa-solid fa-key"></i> Key: <?php echo $key; ?></div>
                            <div><i class="fa-regular fa-clock"></i> Frequency: <?php echo $route['frequency']; ?></div>
                            <div><i class="fa-solid fa-users"></i> Capacity: <?php echo $route['capacity']; ?></div>
                            <div><i class="fa-solid fa-circle"></i> Status: <?php echo $route['status']; ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Tab: Live Tracking -->
                <div id="tab-tracking" class="tab-content <?php echo $active_tab == 'tracking' ? 'active' : ''; ?>">
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fa-solid fa-bus"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo mysqli_num_rows($bus_locations_result); ?></h3>
                                <p>Buses Tracking</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fa-solid fa-clock"></i>
                            </div>
                            <div class="stat-info">
                                <h3>Real-time</h3>
                                <p>Live Updates</p>
                            </div>
                        </div>
                    </div>
                    
                    <?php foreach($route_definitions as $key => $route):
                        // Get current location from bus_routes table
                        $location_sql = "SELECT * FROM bus_routes WHERE route_id = ?";
                        $location_stmt = mysqli_prepare($conn, $location_sql);
                        mysqli_stmt_bind_param($location_stmt, "s", $key);
                        mysqli_stmt_execute($location_stmt);
                        $location_result = mysqli_stmt_get_result($location_stmt);
                        $location_data = mysqli_fetch_assoc($location_result);
                    ?>
                    <div class="location-card">
                        <div class="location-header">
                            <span class="location-name"><?php echo $route['name']; ?> Bus</span>
                            <span class="location-status">
                                <i class="fa-solid fa-circle" style="color: #10b981; font-size: 10px;"></i> Live
                            </span>
                        </div>
                        
                        <div class="map-preview">
                        <iframe id="map-<?php echo $key; ?>"
                            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3960.783515024766!2d79.97036937587595!3d6.916460618471185!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3ae256db1a677131%3A0x2c6145384bc19bc8!2sCINEC%20Campus!5e0!3m2!1sen!2slk!4v1700000000000" 
                            allowfullscreen="" loading="lazy">
                        </iframe>
                        </div>
                        
                        <form method="POST">
                            <input type="hidden" name="route_key" value="<?php echo $key; ?>">
                            <div class="location-input-group">
                                <input type="text" name="location" placeholder="Current Location" 
                                       value="<?php echo htmlspecialchars($location_data['location'] ?? ''); ?>">
                                <button type="submit" name="update_location" class="btn btn-primary">
                                    <i class="fa-solid fa-location-dot"></i> Update Location
                                </button>
                            </div>
                        </form>
                        
                        <div style="font-size: 12px; color: #94a3b8;">
                            <i class="fa-regular fa-clock"></i> 
                            Last Updated: <?php echo $location_data['updated_time'] ?? 'Never'; ?>
                            <?php if($location_data['location']): ?>
                                <br><strong>Last Location:</strong> <?php echo htmlspecialchars($location_data['location']); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Tab: CINEC Schedule -->
                <div id="tab-schedule" class="tab-content <?php echo $active_tab == 'schedule' ? 'active' : ''; ?>">
                    <div class="form-container">
                        <h3>Add/Edit Schedule</h3>
                        <form method="POST">
                            <input type="hidden" name="schedule_id" id="schedule_id" value="0">
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>From Campus</label>
                                    <input type="text" name="from_campus" id="from_campus" required placeholder="e.g., CINEC, City Campus">
                                </div>
                                <div class="form-group">
                                    <label>To Campus</label>
                                    <input type="text" name="to_campus" id="to_campus" required placeholder="e.g., Malabe, City Campus">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Next Departure</label>
                                    <input type="text" name="next_departure" id="next_departure" placeholder="e.g., 5:05 PM, 8:00 AM">
                                </div>
                                <div class="form-group">
                                    <label>Frequency</label>
                                    <input type="text" name="frequency" id="frequency" placeholder="e.g., Every 30 mins">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Status</label>
                                <select name="status" id="schedule_status">
                                    <option value="On-Time">On-Time</option>
                                    <option value="Sharp">Sharp</option>
                                    <option value="Delayed">Delayed</option>
                                    <option value="Cancelled">Cancelled</option>
                                </select>
                            </div>
                            
                            <div style="display: flex; gap: 10px;">
                                <button type="submit" name="save_schedule" class="btn btn-primary">
                                    <i class="fa-solid fa-save"></i> Save Schedule
                                </button>
                                <button type="button" class="btn btn-secondary" onclick="resetScheduleForm()">
                                    <i class="fa-solid fa-rotate"></i> Reset
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <h3>Bus Schedule</h3>
                    <table class="schedule-table">
                        <thead>
                            <tr>
                                <th>From</th>
                                <th>To</th>
                                <th>Departure</th>
                                <th>Frequency</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(mysqli_num_rows($schedules_result) > 0): ?>
                                <?php while($schedule = mysqli_fetch_assoc($schedules_result)):
                                    $is_evening = ($schedule['from_campus'] == 'CINEC');
                                ?>
                                <tr class="<?php echo $is_evening ? 'evening-bus' : 'morning-bus'; ?>">
                                    <td><?php echo htmlspecialchars($schedule['from_campus']); ?></td>
                                    <td><?php echo htmlspecialchars($schedule['to_campus']); ?></td>
                                    <td><strong><?php echo $schedule['next_departure']; ?></strong></td>
                                    <td><?php echo $schedule['frequency']; ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo str_replace('-', '', $schedule['status']); ?>">
                                            <?php echo $schedule['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-primary btn-sm" onclick="editSchedule(<?php echo $schedule['route_id']; ?>)">
                                            <i class="fa-regular fa-pen-to-square"></i> Edit
                                        </button>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this schedule?')">
                                            <input type="hidden" name="schedule_id" value="<?php echo $schedule['route_id']; ?>">
                                            <button type="submit" name="delete_schedule" class="btn btn-danger btn-sm">
                                                <i class="fa-regular fa-trash-can"></i> Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="6" style="text-align: center; padding: 30px;">No schedules found. Add your first schedule above.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Tab: Pass Requests (Pending Approvals) -->
                <div id="tab-requests" class="tab-content <?php echo $active_tab == 'requests' ? 'active' : ''; ?>">
                    <h3>Pending Pass Requests</h3>
                    <p>Users request passes which will be locked until approved by admin.</p>
                    
                    <?php if(mysqli_num_rows($pending_passes_result) > 0): ?>
                        <?php while($request = mysqli_fetch_assoc($pending_passes_result)): ?>
                        <div class="route-card pending-card">
                            <div class="pending-header">
                                <span class="user-name"><?php echo htmlspecialchars($request['Name']); ?></span>
                                <span class="pass-status pass-status-pending">Pending Approval</span>
                            </div>
                            <div class="user-details">
                                <i class="fa-solid fa-id-card"></i> <?php echo $request['StudentID']; ?> &nbsp;|&nbsp;
                                <i class="fa-regular fa-envelope"></i> <?php echo $request['Email']; ?> &nbsp;|&nbsp;
                                <i class="fa-solid fa-star"></i> <?php echo $request['PointsBalance']; ?> points
                            </div>
                            <div class="route-details">
                                <div><i class="fa-solid fa-route"></i> Route: <?php echo htmlspecialchars($request['RouteName']); ?></div>
                                <div><i class="fa-regular fa-calendar"></i> Requested: <?php echo date('M d, Y', strtotime($request['IssuedAt'])); ?></div>
                            </div>
                            <div class="pending-actions">
                                <button class="btn btn-success btn-sm" onclick="approvePass(<?php echo $request['pass_id']; ?>)">
                                    <i class="fa-solid fa-check"></i> Approve
                                </button>
                                <button class="btn btn-danger btn-sm" onclick="rejectPass(<?php echo $request['pass_id']; ?>)">
                                    <i class="fa-solid fa-times"></i> Reject
                                </button>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p style="text-align: center; padding: 40px; color: #64748b;">
                            <i class="fa-regular fa-bell-slash"></i> No pending pass requests
                        </p>
                    <?php endif; ?>
                </div>
                
                <!-- Tab: Active Passes -->
                <div id="tab-passes" class="tab-content <?php echo $active_tab == 'passes' ? 'active' : ''; ?>">
                    <h3>All Transport Passes</h3>
                    
                    <?php if(mysqli_num_rows($active_passes_result) > 0): ?>
                        <?php while($pass = mysqli_fetch_assoc($active_passes_result)): 
                            $is_expired = strtotime($pass['ValidUntil']) < time();
                            $status_class = $is_expired ? 'pass-status-expired' : 'pass-status-active';
                            $status_text = $is_expired ? 'Expired' : $pass['status'];
                        ?>
                        <div class="active-pass-card">
                            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                                <div>
                                    <strong><?php echo htmlspecialchars($pass['Name']); ?></strong><br>
                                    <small style="color: #64748b;"><?php echo $pass['StudentID']; ?> • <?php echo $pass['Email']; ?></small>
                                </div>
                                <div>
                                    <span class="pass-status <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                </div>
                            </div>
                            <div class="route-details" style="margin-top: 10px;">
                                <div><i class="fa-solid fa-route"></i> Route: <?php echo htmlspecialchars($pass['RouteName']); ?></div>
                                <div><i class="fa-regular fa-calendar"></i> Valid Until: <?php echo date('M d, Y', strtotime($pass['ValidUntil'])); ?></div>
                                <div><i class="fa-regular fa-clock"></i> Issued: <?php echo date('M d, Y', strtotime($pass['IssuedAt'])); ?></div>
                            </div>
                            <?php if($pass['status'] == 'Active' && !$is_expired): ?>
                            <div class="pending-actions" style="margin-top: 10px;">
                                <button class="btn btn-warning btn-sm" onclick="cancelPass(<?php echo $pass['pass_id']; ?>)">
                                    <i class="fa-solid fa-ban"></i> Cancel Pass
                                </button>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p style="text-align: center; padding: 40px; color: #64748b;">
                            <i class="fa-regular fa-ticket"></i> No passes found
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Approve Pass Modal -->
    <div class="modal" id="approveModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fa-solid fa-check-circle"></i> Approve Transport Pass</h3>
                <button class="modal-close" onclick="closeModal('approveModal')">&times;</button>
            </div>
            <form method="POST" id="approveForm">
                <div class="modal-body">
                    <input type="hidden" name="pass_id" id="approve_pass_id">
                    <div class="form-group">
                        <label>Valid Until Date</label>
                        <input type="date" name="valid_until" id="valid_until" value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>" required>
                        <small>Pass will expire on this date</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('approveModal')">Cancel</button>
                    <button type="submit" name="approve_pass" class="btn btn-success">Approve Pass</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Reject Pass Modal -->
    <div class="modal" id="rejectModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fa-solid fa-times-circle"></i> Reject Pass Request</h3>
                <button class="modal-close" onclick="closeModal('rejectModal')">&times;</button>
            </div>
            <form method="POST" id="rejectForm">
                <div class="modal-body">
                    <input type="hidden" name="pass_id" id="reject_pass_id">
                    <div class="form-group">
                        <label>Reason for Rejection</label>
                        <textarea name="reason" rows="3" class="form-control" placeholder="Enter reason for rejection..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('rejectModal')">Cancel</button>
                    <button type="submit" name="reject_pass" class="btn btn-danger">Reject Request</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Load route settings into form
        function loadRouteSettings() {
            const routeKey = document.getElementById('route_select').value;
            if (!routeKey) return;
            
            // Get route data from the displayed cards
            const routeCards = document.querySelectorAll('#tab-routes .route-card');
            for (let card of routeCards) {
                const nameSpan = card.querySelector('.route-name');
                if (nameSpan) {
                    // Extract data from the card (or you could fetch via AJAX)
                    const priceSpan = card.querySelector('.route-price');
                    const detailsSpans = card.querySelectorAll('.route-details div');
                    
                    if (priceSpan) {
                        const price = priceSpan.textContent.replace(' points', '');
                        document.getElementById('route_price').value = price;
                    }
                    
                    detailsSpans.forEach(detail => {
                        if (detail.innerHTML.includes('Frequency:')) {
                            const freq = detail.innerHTML.split('Frequency:')[1].trim();
                            document.getElementById('route_frequency').value = freq;
                        }
                        if (detail.innerHTML.includes('Capacity:')) {
                            const cap = detail.innerHTML.split('Capacity:')[1].trim();
                            document.getElementById('route_capacity').value = cap;
                        }
                        if (detail.innerHTML.includes('Status:')) {
                            const stat = detail.innerHTML.split('Status:')[1].trim();
                            document.getElementById('route_status').value = stat;
                        }
                    });
                }
            }
        }
        
        // Edit schedule function
        function editSchedule(id) {
            fetch('get_schedule.php?id=' + id)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('schedule_id').value = data.route_id;
                    document.getElementById('from_campus').value = data.from_campus;
                    document.getElementById('to_campus').value = data.to_campus;
                    document.getElementById('next_departure').value = data.next_departure;
                    document.getElementById('frequency').value = data.frequency;
                    document.getElementById('schedule_status').value = data.status;
                    document.querySelector('#tab-schedule .form-container').scrollIntoView({ behavior: 'smooth' });
                })
                .catch(error => console.error('Error:', error));
        }
        
        // Reset schedule form
        function resetScheduleForm() {
            document.getElementById('schedule_id').value = '0';
            document.getElementById('from_campus').value = '';
            document.getElementById('to_campus').value = '';
            document.getElementById('next_departure').value = '';
            document.getElementById('frequency').value = '';
            document.getElementById('schedule_status').value = 'On-Time';
        }
        
        // Approve pass function
        function approvePass(passId) {
            document.getElementById('approve_pass_id').value = passId;
            document.getElementById('approveModal').classList.add('show');
        }
        
        // Reject pass function
        function rejectPass(passId) {
            document.getElementById('reject_pass_id').value = passId;
            document.getElementById('rejectModal').classList.add('show');
        }
        
        // Cancel active pass
        function cancelPass(passId) {
            if (confirm('Are you sure you want to cancel this transport pass? This action cannot be undone.')) {
                let form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="pass_id" value="' + passId + '"><input type="hidden" name="cancel_pass" value="1">';
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Close modal function
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('show');
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