<?php
require_once 'middleware.php';
require_once 'config.php';
checkAdminAuth();

$admin = getAdminInfo($conn);
$message = '';
$error = '';

// Handle tab parameter from URL
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'config';

// Handle pool operations
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_pool_config'])) {
        $facility_id = intval($_POST['facility_id']);
        $lanes = intval($_POST['lanes']);
        $water_temp = floatval($_POST['water_temp']);
        $depth = mysqli_real_escape_string($conn, $_POST['depth']);
        $lifeguards = intval($_POST['lifeguards']);
        $medical_required = isset($_POST['medical_required']) ? 1 : 0;
        $amenities = mysqli_real_escape_string($conn, $_POST['amenities']);
        
        // Get existing ExtraInfo
        $get_sql = "SELECT ExtraInfo FROM Facilities WHERE FacilityID = ?";
        $get_stmt = mysqli_prepare($conn, $get_sql);
        mysqli_stmt_bind_param($get_stmt, "i", $facility_id);
        mysqli_stmt_execute($get_stmt);
        $get_result = mysqli_stmt_get_result($get_stmt);
        $facility = mysqli_fetch_assoc($get_result);
        
        $extra_info = json_decode($facility['ExtraInfo'], true) ?? [];
        $extra_info['lanes'] = $lanes;
        $extra_info['waterTemp'] = $water_temp;
        $extra_info['depth'] = $depth;
        $extra_info['lifeguards'] = $lifeguards;
        $extra_info['medicalRequired'] = (bool)$medical_required;
        $extra_info['amenities'] = explode(',', $amenities);
        
        $extra_json = json_encode($extra_info);
        
        $update_sql = "UPDATE Facilities SET ExtraInfo = ? WHERE FacilityID = ?";
        $update_stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($update_stmt, "si", $extra_json, $facility_id);
        
        if (mysqli_stmt_execute($update_stmt)) {
            $message = "Pool configuration updated successfully!";
            logAdminActivity($conn, 'UPDATE_POOL', "Updated pool configuration for facility ID: $facility_id");
        } else {
            $error = "Error updating pool: " . mysqli_error($conn);
        }
    }
    
    if (isset($_POST['approve_medical'])) {
        $report_id = intval($_POST['report_id']);
        
        $sql = "UPDATE medical_reports SET is_valid = TRUE WHERE report_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $report_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $message = "Medical report approved!";
            logAdminActivity($conn, 'APPROVE_MEDICAL', "Approved medical report ID: $report_id");
        }
    }
    
    if (isset($_POST['reject_medical'])) {
        $report_id = intval($_POST['report_id']);
        
        $sql = "UPDATE medical_reports SET is_valid = FALSE WHERE report_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $report_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $message = "Medical report rejected!";
            logAdminActivity($conn, 'REJECT_MEDICAL', "Rejected medical report ID: $report_id");
        }
    }
    
    // Add lifeguard
    if (isset($_POST['add_lifeguard'])) {
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $phone = mysqli_real_escape_string($conn, $_POST['phone']);
        $certification = mysqli_real_escape_string($conn, $_POST['certification']);
        $experience = intval($_POST['experience']);
        
        $sql = "INSERT INTO lifeguards (name, email, phone, certification, experience, status) VALUES (?, ?, ?, ?, ?, 'active')";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssssi", $name, $email, $phone, $certification, $experience);
        
        if (mysqli_stmt_execute($stmt)) {
            $message = "Lifeguard added successfully!";
            logAdminActivity($conn, 'ADD_LIFEGUARD', "Added lifeguard: $name");
        } else {
            $error = "Error adding lifeguard: " . mysqli_error($conn);
        }
    }
    
    // Edit lifeguard
    if (isset($_POST['edit_lifeguard'])) {
        $lifeguard_id = intval($_POST['lifeguard_id']);
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $phone = mysqli_real_escape_string($conn, $_POST['phone']);
        $certification = mysqli_real_escape_string($conn, $_POST['certification']);
        $experience = intval($_POST['experience']);
        $status = mysqli_real_escape_string($conn, $_POST['status']);
        
        $sql = "UPDATE lifeguards SET name=?, email=?, phone=?, certification=?, experience=?, status=? WHERE lifeguard_id=?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssssisi", $name, $email, $phone, $certification, $experience, $status, $lifeguard_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $message = "Lifeguard updated successfully!";
            logAdminActivity($conn, 'UPDATE_LIFEGUARD', "Updated lifeguard: $name");
        } else {
            $error = "Error updating lifeguard: " . mysqli_error($conn);
        }
    }
    
    // Delete lifeguard
    if (isset($_POST['delete_lifeguard'])) {
        $lifeguard_id = intval($_POST['lifeguard_id']);
        
        $sql = "DELETE FROM lifeguards WHERE lifeguard_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $lifeguard_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $message = "Lifeguard deleted successfully!";
            logAdminActivity($conn, 'DELETE_LIFEGUARD', "Deleted lifeguard ID: $lifeguard_id");
        } else {
            $error = "Error deleting lifeguard: " . mysqli_error($conn);
        }
    }
    
    // Update lifeguard schedule
    if (isset($_POST['update_lifeguard_schedule'])) {
        $schedule_date = $_POST['schedule_date'];
        
        // Clear existing schedule for this date
        $clear_sql = "DELETE FROM lifeguard_schedule WHERE schedule_date = ?";
        $clear_stmt = mysqli_prepare($conn, $clear_sql);
        mysqli_stmt_bind_param($clear_stmt, "s", $schedule_date);
        mysqli_stmt_execute($clear_stmt);
        
        // Insert new schedule
        if (isset($_POST['time_slots']) && is_array($_POST['time_slots'])) {
            foreach ($_POST['time_slots'] as $index => $time_slot) {
                if (isset($_POST['lifeguards'][$index]) && is_array($_POST['lifeguards'][$index])) {
                    foreach ($_POST['lifeguards'][$index] as $lifeguard_id) {
                        $insert_sql = "INSERT INTO lifeguard_schedule (lifeguard_id, schedule_date, time_slot, status) VALUES (?, ?, ?, 'scheduled')";
                        $insert_stmt = mysqli_prepare($conn, $insert_sql);
                        mysqli_stmt_bind_param($insert_stmt, "iss", $lifeguard_id, $schedule_date, $time_slot);
                        mysqli_stmt_execute($insert_stmt);
                    }
                }
            }
        }
        
        $message = "Lifeguard schedule updated successfully!";
        logAdminActivity($conn, 'UPDATE_SCHEDULE', "Updated lifeguard schedule for date: $schedule_date");
    }
}

// Get all pool facilities
$pools_sql = "SELECT * FROM Facilities WHERE Type = 'Pool'";
$pools_result = mysqli_query($conn, $pools_sql);

// Get medical reports
$medical_sql = "SELECT mr.*, u.Name as user_name, u.Email, u.StudentID 
                FROM medical_reports mr
                JOIN Users u ON mr.user_id = u.UserID
                ORDER BY mr.upload_date DESC";
$medical_result = mysqli_query($conn, $medical_sql);

// Get pool bookings
$bookings_sql = "SELECT pb.*, u.Name as user_name, u.StudentID, f.Name as facility_name 
                 FROM pool_bookings pb
                 JOIN Users u ON pb.user_id = u.UserID
                 JOIN Facilities f ON pb.facility_id = f.FacilityID
                 WHERE pb.booking_date >= CURDATE()
                 ORDER BY pb.booking_date, pb.time_slot";
$bookings_result = mysqli_query($conn, $bookings_sql);

// Get all lifeguards
$lifeguards_sql = "SELECT * FROM lifeguards ORDER BY name";
$lifeguards_result = mysqli_query($conn, $lifeguards_sql);

// Get schedule for selected date
$selected_date = isset($_GET['schedule_date']) ? $_GET['schedule_date'] : date('Y-m-d');
$schedule_sql = "SELECT ls.*, l.name as lifeguard_name 
                 FROM lifeguard_schedule ls
                 JOIN lifeguards l ON ls.lifeguard_id = l.lifeguard_id
                 WHERE ls.schedule_date = ?
                 ORDER BY ls.time_slot, l.name";
$schedule_stmt = mysqli_prepare($conn, $schedule_sql);
mysqli_stmt_bind_param($schedule_stmt, "s", $selected_date);
mysqli_stmt_execute($schedule_stmt);
$schedule_result = mysqli_stmt_get_result($schedule_stmt);

// Group schedule by time slot
$schedule_by_time = [];
while ($schedule = mysqli_fetch_assoc($schedule_result)) {
    $schedule_by_time[$schedule['time_slot']][] = $schedule;
}

// Time slots for lifeguard schedule
$time_slots = [
    '06:00-09:00', '09:00-12:00', '12:00-15:00', 
    '15:00-18:00', '18:00-21:00', '21:00-23:00'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pool Management - Synergy Hub Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="admin.css">
    <style>
        .pool-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 10px;
            flex-wrap: wrap;
        }
        
        .pool-tab {
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
        
        .pool-tab:hover {
            background: #f1f5f9;
            color: #1e293b;
        }
        
        .pool-tab.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .pool-config {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .lane-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
            gap: 10px;
            margin: 20px 0;
        }
        
        .lane-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 8px;
            padding: 15px 5px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .lane-box:hover {
            transform: scale(1.05);
        }
        
        .lane-box.fast {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        }
        
        .lane-box.medium {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        }
        
        .lane-box.slow {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }
        
        .lane-number {
            font-size: 18px;
            font-weight: 700;
        }
        
        .lane-type {
            font-size: 11px;
            opacity: 0.9;
        }
        
        .temp-slider {
            width: 100%;
            margin: 20px 0;
        }
        
        .temp-display {
            font-size: 48px;
            font-weight: 700;
            color: #667eea;
            text-align: center;
        }
        
        .medical-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            border-left: 4px solid;
        }
        
        .medical-pending { border-left-color: #f59e0b; }
        .medical-valid { border-left-color: #10b981; }
        .medical-expired { border-left-color: #ef4444; }
        
        .medical-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .medical-user {
            font-size: 16px;
            font-weight: 600;
            color: #1e293b;
        }
        
        .medical-date {
            font-size: 13px;
            color: #64748b;
        }
        
        .medical-doc {
            background: #f8fafc;
            border-radius: 8px;
            padding: 10px;
            margin: 10px 0;
            font-size: 13px;
        }
        
        .medical-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            flex-wrap: wrap;
        }
        
        .schedule-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .schedule-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .schedule-time {
            font-weight: 600;
            color: #667eea;
            margin-bottom: 10px;
            font-size: 16px;
        }
        
        .lifeguard-list {
            list-style: none;
            margin-bottom: 10px;
        }
        
        .lifeguard-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .lifeguard-item:last-child {
            border-bottom: none;
        }
        
        .lifeguard-name {
            flex: 1;
        }
        
        .lifeguard-status {
            width: 10px;
            height: 10px;
            border-radius: 50%;
        }
        
        .status-on {
            background: #10b981;
        }
        
        .status-off {
            background: #ef4444;
        }
        
        .booking-card {
            background: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
        }
        
        .booking-lane {
            display: inline-block;
            padding: 4px 12px;
            background: #667eea;
            color: white;
            border-radius: 20px;
            font-size: 12px;
            margin-right: 10px;
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
        
        .btn-success {
            background: #10b981;
            color: white;
        }
        
        .btn-success:hover {
            background: #059669;
        }
        
        .badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
        }
        
        .badge-success {
            background: #dcfce7;
            color: #16a34a;
        }
        
        .badge-danger {
            background: #fee;
            color: #ef4444;
        }
        
        .badge-warning {
            background: #fff7ed;
            color: #ea580c;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
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
        
        .checkbox-group {
            display: flex;
            gap: 15px;
            margin: 10px 0;
            flex-wrap: wrap;
        }
        
        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
        }
        
        .status-pending { background: #f59e0b; color: white; }
        .status-confirmed { background: #10b981; color: white; }
        .status-cancelled { background: #ef4444; color: white; }
        .status-completed { background: #6b7280; color: white; }
        
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
        
        .modal-close {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: #64748b;
        }
        
        .lifeguard-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 12px;
            overflow: hidden;
        }
        
        .lifeguard-table th {
            background: #f8fafc;
            padding: 12px;
            text-align: left;
            font-size: 12px;
            color: #64748b;
            font-weight: 600;
        }
        
        .lifeguard-table td {
            padding: 12px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 13px;
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        
        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .medical-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .lifeguard-table {
                font-size: 12px;
            }
            
            .lifeguard-table td,
            .lifeguard-table th {
                padding: 8px;
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
                    <i class="fa-solid fa-person-swimming"></i> Pool Management
                </h1>
                
                <?php if($message): ?>
                    <div class="alert alert-success"><?php echo $message; ?></div>
                <?php endif; ?>
                <?php if($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <!-- Tabs with URL parameters -->
                <div class="pool-tabs">
                    <a href="pool_management.php?tab=config" class="pool-tab <?php echo $active_tab == 'config' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-sliders"></i> Pool Configuration
                    </a>
                    <a href="pool_management.php?tab=medical" class="pool-tab <?php echo $active_tab == 'medical' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-notes-medical"></i> Medical Reports
                    </a>
                    <a href="pool_management.php?tab=schedule" class="pool-tab <?php echo $active_tab == 'schedule' ? 'active' : ''; ?>">
                        <i class="fa-regular fa-calendar"></i> Lifeguard Schedule
                    </a>
                    <a href="pool_management.php?tab=bookings" class="pool-tab <?php echo $active_tab == 'bookings' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-clock"></i> Lane Bookings
                    </a>
                </div>
                
                <!-- Tab: Pool Configuration -->
                <div id="tab-config" class="tab-content <?php echo $active_tab == 'config' ? 'active' : ''; ?>">
                    <?php if(mysqli_num_rows($pools_result) > 0): ?>
                        <?php while($pool = mysqli_fetch_assoc($pools_result)): 
                            $extra = json_decode($pool['ExtraInfo'], true) ?? [];
                            $lanes = $extra['lanes'] ?? 8;
                            $water_temp = $extra['waterTemp'] ?? 27;
                            $depth = $extra['depth'] ?? '1.2m - 2.5m';
                            $lifeguards = $extra['lifeguards'] ?? 4;
                            $medical_required = $extra['medicalRequired'] ?? true;
                            $amenities = isset($extra['amenities']) ? implode(', ', $extra['amenities']) : 'Changing Rooms, Showers, Lockers';
                        ?>
                        <div class="pool-config">
                            <h3><?php echo htmlspecialchars($pool['Name']); ?></h3>
                            
                            <form method="POST">
                                <input type="hidden" name="facility_id" value="<?php echo $pool['FacilityID']; ?>">
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Number of Lanes</label>
                                        <input type="number" name="lanes" value="<?php echo $lanes; ?>" min="1" max="20">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Water Temperature (°C)</label>
                                        <input type="number" name="water_temp" value="<?php echo $water_temp; ?>" min="15" max="35" step="0.5">
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Depth Range</label>
                                        <input type="text" name="depth" value="<?php echo $depth; ?>">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Lifeguards on Duty</label>
                                        <input type="number" name="lifeguards" value="<?php echo $lifeguards; ?>" min="0">
                                    </div>
                                </div>
                                
                                <div class="checkbox-group">
                                    <div class="checkbox-item">
                                        <input type="checkbox" name="medical_required" id="medical_required" <?php echo $medical_required ? 'checked' : ''; ?>>
                                        <label for="medical_required">Medical Report Required</label>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label>Amenities (comma separated)</label>
                                    <input type="text" name="amenities" value="<?php echo $amenities; ?>">
                                </div>
                                
                                <!-- Lane Type Assignment -->
                                <h4>Lane Type Assignment</h4>
                                <div class="lane-grid">
                                    <?php for($i = 1; $i <= $lanes; $i++): 
                                        $type = isset($extra['lane_types'][$i]) ? $extra['lane_types'][$i] : 
                                               ($i % 3 == 0 ? 'slow' : ($i % 2 == 0 ? 'medium' : 'fast'));
                                    ?>
                                    <div class="lane-box <?php echo $type; ?>" onclick="toggleLaneType(<?php echo $i; ?>)">
                                        <div class="lane-number"><?php echo $i; ?></div>
                                        <div class="lane-type" id="lane-<?php echo $i; ?>"><?php echo ucfirst($type); ?></div>
                                        <input type="hidden" name="lane_type_<?php echo $i; ?>" id="lane_type_<?php echo $i; ?>" value="<?php echo $type; ?>">
                                    </div>
                                    <?php endfor; ?>
                                </div>
                                
                                <button type="submit" name="update_pool_config" class="btn btn-primary">
                                    <i class="fa-solid fa-save"></i> Save Configuration
                                </button>
                            </form>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div style="text-align: center; padding: 50px; color: #64748b;">
                            <i class="fa-solid fa-person-swimming" style="font-size: 48px; margin-bottom: 20px;"></i>
                            <h3>No pool facilities found</h3>
                            <p><a href="facility_management.php?tab=add" class="btn btn-primary">Add a pool facility</a></p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Tab: Medical Reports -->
                <div id="tab-medical" class="tab-content <?php echo $active_tab == 'medical' ? 'active' : ''; ?>">
                    <h3>Medical Report Approvals</h3>
                    
                    <?php if(mysqli_num_rows($medical_result) > 0): ?>
                        <?php while($report = mysqli_fetch_assoc($medical_result)): 
                            $expired = strtotime($report['expiry_date']) < time();
                            $status_class = 'medical-pending';
                            if($report['is_valid']) $status_class = 'medical-valid';
                            if($expired) $status_class = 'medical-expired';
                        ?>
                        <div class="medical-card <?php echo $status_class; ?>">
                            <div class="medical-header">
                                <div>
                                    <span class="medical-user"><?php echo htmlspecialchars($report['user_name']); ?></span>
                                    <span style="font-size: 12px; color: #64748b;">(<?php echo $report['StudentID']; ?>)</span>
                                </div>
                                <span class="medical-date">Uploaded: <?php echo date('M d, Y', strtotime($report['upload_date'])); ?></span>
                            </div>
                            
                            <div class="medical-doc">
                                <i class="fa-regular fa-file-pdf"></i> <?php echo htmlspecialchars($report['file_name']); ?> 
                                (<?php echo round($report['file_size'] / 1024); ?> KB)
                            </div>
                            
                            <div style="display: flex; gap: 20px; margin-bottom: 10px; flex-wrap: wrap;">
                                <div><strong>Emergency Contact:</strong> <?php echo htmlspecialchars($report['emergency_contact_name']); ?></div>
                                <div><strong>Phone:</strong> <?php echo htmlspecialchars($report['emergency_contact_phone']); ?></div>
                            </div>
                            
                            <?php if($report['medical_conditions']): ?>
                            <div style="background: #fff7ed; padding: 8px; border-radius: 4px; font-size: 13px;">
                                <strong>Medical Conditions:</strong> <?php echo htmlspecialchars($report['medical_conditions']); ?>
                            </div>
                            <?php endif; ?>
                            
                            <div style="margin: 10px 0;">
                                <strong>Expiry Date:</strong> <?php echo date('M d, Y', strtotime($report['expiry_date'])); ?>
                                <?php if($expired): ?>
                                    <span class="badge badge-danger">Expired</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="medical-actions">
                                <?php if(!$report['is_valid'] && !$expired): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="report_id" value="<?php echo $report['report_id']; ?>">
                                        <button type="submit" name="approve_medical" class="btn btn-success btn-sm">
                                            <i class="fa-solid fa-check"></i> Approve
                                        </button>
                                    </form>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="report_id" value="<?php echo $report['report_id']; ?>">
                                        <button type="submit" name="reject_medical" class="btn btn-danger btn-sm">
                                            <i class="fa-solid fa-xmark"></i> Reject
                                        </button>
                                    </form>
                                <?php elseif($report['is_valid']): ?>
                                    <span class="badge badge-success">Approved</span>
                                <?php endif; ?>
                                <a href="<?php echo $report['file_path']; ?>" target="_blank" class="btn btn-secondary btn-sm">
                                    <i class="fa-regular fa-eye"></i> View Document
                                </a>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div style="text-align: center; padding: 50px; color: #64748b;">
                            <i class="fa-regular fa-file" style="font-size: 48px; margin-bottom: 20px;"></i>
                            <h3>No medical reports found</h3>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Tab: Lifeguard Schedule -->
                <div id="tab-schedule" class="tab-content <?php echo $active_tab == 'schedule' ? 'active' : ''; ?>">
                    <div class="pool-config">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                            <h3>Lifeguard Management</h3>
                            <button class="btn btn-primary" onclick="showAddLifeguardModal()">
                                <i class="fa-solid fa-plus"></i> Add Lifeguard
                            </button>
                        </div>
                        
                        <!-- Lifeguard List Table -->
                        <h4>All Lifeguards</h4>
                        <table class="lifeguard-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Certification</th>
                                    <th>Experience</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(mysqli_num_rows($lifeguards_result) > 0): ?>
                                    <?php mysqli_data_seek($lifeguards_result, 0); ?>
                                    <?php while($lifeguard = mysqli_fetch_assoc($lifeguards_result)): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($lifeguard['name']); ?></td>
                                        <td><?php echo htmlspecialchars($lifeguard['email']); ?></td>
                                        <td><?php echo htmlspecialchars($lifeguard['phone']); ?></td>
                                        <td><?php echo htmlspecialchars($lifeguard['certification']); ?></td>
                                        <td><?php echo $lifeguard['experience']; ?> years</td>
                                        <td>
                                            <span class="status-badge status-<?php echo $lifeguard['status'] == 'active' ? 'confirmed' : 'cancelled'; ?>">
                                                <?php echo ucfirst($lifeguard['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn btn-primary btn-sm" onclick="editLifeguard(<?php echo $lifeguard['lifeguard_id']; ?>)">
                                                    <i class="fa-regular fa-pen-to-square"></i>
                                                </button>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this lifeguard?')">
                                                    <input type="hidden" name="lifeguard_id" value="<?php echo $lifeguard['lifeguard_id']; ?>">
                                                    <button type="submit" name="delete_lifeguard" class="btn btn-danger btn-sm">
                                                        <i class="fa-regular fa-trash-can"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" style="text-align: center; padding: 20px;">No lifeguards found. Add your first lifeguard.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                        
                        <hr style="margin: 30px 0;">
                        
                        <h3>Lifeguard Schedule - <?php echo date('F d, Y', strtotime($selected_date)); ?></h3>
                        
                        <form method="GET" style="margin-bottom: 20px;">
                            <input type="hidden" name="tab" value="schedule">
                            <div class="form-row" style="max-width: 400px;">
                                <div class="form-group">
                                    <label>Select Date</label>
                                    <input type="date" name="schedule_date" value="<?php echo $selected_date; ?>" onchange="this.form.submit()">
                                </div>
                            </div>
                        </form>
                        
                        <form method="POST">
                            <input type="hidden" name="schedule_date" value="<?php echo $selected_date; ?>">
                            
                            <div class="schedule-grid">
                                <?php foreach($time_slots as $index => $slot): ?>
                                <div class="schedule-card">
                                    <div class="schedule-time"><?php echo $slot; ?></div>
                                    
                                    <div class="form-group">
                                        <label>Assign Lifeguards</label>
                                        <select name="lifeguards[<?php echo $index; ?>][]" multiple size="4" style="width: 100%;">
                                            <?php 
                                            mysqli_data_seek($lifeguards_result, 0);
                                            while($lifeguard = mysqli_fetch_assoc($lifeguards_result)): 
                                                $selected = '';
                                                if (isset($schedule_by_time[$slot])) {
                                                    foreach ($schedule_by_time[$slot] as $scheduled) {
                                                        if ($scheduled['lifeguard_id'] == $lifeguard['lifeguard_id']) {
                                                            $selected = 'selected';
                                                            break;
                                                        }
                                                    }
                                                }
                                            ?>
                                            <option value="<?php echo $lifeguard['lifeguard_id']; ?>" <?php echo $selected; ?>>
                                                <?php echo htmlspecialchars($lifeguard['name']); ?> (<?php echo $lifeguard['experience']; ?> yrs)
                                            </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    <input type="hidden" name="time_slots[]" value="<?php echo $slot; ?>">
                                    
                                    <!-- Display currently assigned lifeguards -->
                                    <?php if(isset($schedule_by_time[$slot])): ?>
                                    <div style="margin-top: 10px;">
                                        <strong>Assigned:</strong>
                                        <ul class="lifeguard-list">
                                            <?php foreach($schedule_by_time[$slot] as $scheduled): ?>
                                            <li class="lifeguard-item">
                                                <span class="lifeguard-status status-on"></span>
                                                <span class="lifeguard-name"><?php echo htmlspecialchars($scheduled['lifeguard_name']); ?></span>
                                            </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <button type="submit" name="update_lifeguard_schedule" class="btn btn-primary">
                                <i class="fa-solid fa-save"></i> Save Schedule
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Tab: Lane Bookings -->
                <div id="tab-bookings" class="tab-content <?php echo $active_tab == 'bookings' ? 'active' : ''; ?>">
                    <h3>Today's Lane Bookings - <?php echo date('F d, Y'); ?></h3>
                    
                    <?php if(mysqli_num_rows($bookings_result) > 0): ?>
                        <?php while($booking = mysqli_fetch_assoc($bookings_result)): ?>
                        <div class="booking-card">
                            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                                <div>
                                    <span class="booking-lane">Lane <?php echo $booking['lane_number']; ?></span>
                                    <span><?php echo $booking['time_slot']; ?></span>
                                </div>
                                <span class="status-badge status-<?php echo $booking['status']; ?>">
                                    <?php echo ucfirst($booking['status']); ?>
                                </span>
                            </div>
                            <div style="margin-top: 10px;">
                                <strong><?php echo htmlspecialchars($booking['user_name']); ?></strong> 
                                (<?php echo $booking['StudentID']; ?>) - <?php echo $booking['facility_name']; ?>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div style="text-align: center; padding: 50px; color: #64748b;">
                            <i class="fa-regular fa-calendar" style="font-size: 48px; margin-bottom: 20px;"></i>
                            <h3>No lane bookings for today</h3>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Lifeguard Modal -->
    <div class="modal" id="addLifeguardModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Lifeguard</h3>
                <button class="modal-close" onclick="closeAddLifeguardModal()">&times;</button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="name" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="tel" name="phone" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Certification</label>
                        <select name="certification" required>
                            <option value="Lifeguard Certificate">Lifeguard Certificate</option>
                            <option value="First Aid">First Aid</option>
                            <option value="CPR">CPR</option>
                            <option value="Pool Operator">Pool Operator</option>
                            <option value="Water Safety Instructor">Water Safety Instructor</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Years of Experience</label>
                        <input type="number" name="experience" min="0" max="50" value="1" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeAddLifeguardModal()">Cancel</button>
                    <button type="submit" name="add_lifeguard" class="btn btn-primary">Add Lifeguard</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Edit Lifeguard Modal -->
    <div class="modal" id="editLifeguardModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Lifeguard</h3>
                <button class="modal-close" onclick="closeEditLifeguardModal()">&times;</button>
            </div>
            <form method="POST" id="editLifeguardForm">
                <div class="modal-body">
                    <input type="hidden" name="lifeguard_id" id="edit_lifeguard_id">
                    
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="name" id="edit_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" id="edit_email" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="tel" name="phone" id="edit_phone" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Certification</label>
                        <select name="certification" id="edit_certification" required>
                            <option value="Lifeguard Certificate">Lifeguard Certificate</option>
                            <option value="First Aid">First Aid</option>
                            <option value="CPR">CPR</option>
                            <option value="Pool Operator">Pool Operator</option>
                            <option value="Water Safety Instructor">Water Safety Instructor</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Years of Experience</label>
                        <input type="number" name="experience" id="edit_experience" min="0" max="50" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" id="edit_status" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="on_leave">On Leave</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeEditLifeguardModal()">Cancel</button>
                    <button type="submit" name="edit_lifeguard" class="btn btn-primary">Update Lifeguard</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Toggle lane type
        function toggleLaneType(lane) {
            const laneDiv = event.currentTarget;
            const typeSpan = document.getElementById('lane-' + lane);
            const typeInput = document.getElementById('lane_type_' + lane);
            
            let currentType = typeSpan.textContent.toLowerCase();
            let newType;
            
            if (currentType === 'fast') {
                newType = 'medium';
                laneDiv.className = 'lane-box medium';
            } else if (currentType === 'medium') {
                newType = 'slow';
                laneDiv.className = 'lane-box slow';
            } else {
                newType = 'fast';
                laneDiv.className = 'lane-box fast';
            }
            
            typeSpan.textContent = newType.charAt(0).toUpperCase() + newType.slice(1);
            typeInput.value = newType;
        }
        
        // Add Lifeguard Modal
        function showAddLifeguardModal() {
            document.getElementById('addLifeguardModal').classList.add('show');
        }
        
        function closeAddLifeguardModal() {
            document.getElementById('addLifeguardModal').classList.remove('show');
        }
        
        // Edit Lifeguard Modal
        function editLifeguard(id) {
            // Fetch lifeguard data via AJAX
            fetch('get_lifeguard.php?id=' + id)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('edit_lifeguard_id').value = data.lifeguard_id;
                    document.getElementById('edit_name').value = data.name;
                    document.getElementById('edit_email').value = data.email;
                    document.getElementById('edit_phone').value = data.phone;
                    document.getElementById('edit_certification').value = data.certification;
                    document.getElementById('edit_experience').value = data.experience;
                    document.getElementById('edit_status').value = data.status;
                    
                    document.getElementById('editLifeguardModal').classList.add('show');
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading lifeguard data');
                });
        }
        
        function closeEditLifeguardModal() {
            document.getElementById('editLifeguardModal').classList.remove('show');
        }
        
        // Close modals when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('show');
            }
        }
    </script>
</body>
</html>