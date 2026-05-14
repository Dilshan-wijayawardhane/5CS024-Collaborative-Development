<?php
// pool_medical.php - COMPLETE FULL VERSION with white/blue theme
require_once 'config.php';
require_once 'functions.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$facility_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get facility details
$facility_sql = "SELECT * FROM Facilities WHERE FacilityID = ?";
$facility_stmt = mysqli_prepare($conn, $facility_sql);
mysqli_stmt_bind_param($facility_stmt, "i", $facility_id);
mysqli_stmt_execute($facility_stmt);
$facility_result = mysqli_stmt_get_result($facility_stmt);
$facility = mysqli_fetch_assoc($facility_result);

// Get existing medical reports
$medical_sql = "SELECT * FROM medical_reports 
                WHERE user_id = ? 
                ORDER BY upload_date DESC";
$medical_stmt = mysqli_prepare($conn, $medical_sql);
mysqli_stmt_bind_param($medical_stmt, "i", $user_id);
mysqli_stmt_execute($medical_stmt);
$medical_result = mysqli_stmt_get_result($medical_stmt);

// Get latest valid report
$valid_sql = "SELECT * FROM medical_reports 
              WHERE user_id = ? AND is_valid = TRUE 
              AND (expiry_date IS NULL OR expiry_date >= CURDATE())
              ORDER BY upload_date DESC LIMIT 1";
$valid_stmt = mysqli_prepare($conn, $valid_sql);
mysqli_stmt_bind_param($valid_stmt, "i", $user_id);
mysqli_stmt_execute($valid_stmt);
$valid_result = mysqli_stmt_get_result($valid_stmt);
$has_valid = mysqli_num_rows($valid_result) > 0;
$valid_report = mysqli_fetch_assoc($valid_result);

// Get user points
$user_sql = "SELECT PointsBalance FROM Users WHERE UserID = ?";
$user_stmt = mysqli_prepare($conn, $user_sql);
mysqli_stmt_bind_param($user_stmt, "i", $user_id);
mysqli_stmt_execute($user_stmt);
$user_result = mysqli_stmt_get_result($user_stmt);
$user = mysqli_fetch_assoc($user_result);

// Handle form submission
$message = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $emergency_name = $_POST['emergency_name'];
    $emergency_phone = $_POST['emergency_phone'];
    $medical_conditions = $_POST['medical_conditions'];
    $expiry_date = $_POST['expiry_date'];
    
    // Handle file upload
    if (isset($_FILES['medical_file']) && $_FILES['medical_file']['error'] == 0) {
        $allowed = ['pdf', 'jpg', 'jpeg', 'png'];
        $filename = $_FILES['medical_file']['name'];
        $filesize = $_FILES['medical_file']['size'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (!in_array($ext, $allowed)) {
            $message = '<div class="error-msg"><i class="fa-solid fa-circle-exclamation"></i> ❌ Only PDF, JPG, PNG files are allowed</div>';
        } elseif ($filesize > 5 * 1024 * 1024) {
            $message = '<div class="error-msg"><i class="fa-solid fa-circle-exclamation"></i> ❌ File size must be less than 5MB</div>';
        } else {
            // Create upload directory if not exists
            $upload_dir = 'uploads/medical/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Generate unique filename
            $new_filename = 'medical_' . $user_id . '_' . time() . '.' . $ext;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['medical_file']['tmp_name'], $upload_path)) {
                // Insert into database
                $insert_sql = "INSERT INTO medical_reports (user_id, file_name, file_path, file_size, expiry_date, emergency_contact_name, emergency_contact_phone, medical_conditions) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $insert_stmt = mysqli_prepare($conn, $insert_sql);
                mysqli_stmt_bind_param($insert_stmt, "ississss", 
                    $user_id, 
                    $filename, 
                    $upload_path, 
                    $filesize, 
                    $expiry_date, 
                    $emergency_name, 
                    $emergency_phone, 
                    $medical_conditions
                );
                
                if (mysqli_stmt_execute($insert_stmt)) {
                    $message = '<div class="success-msg"><i class="fa-solid fa-check-circle"></i> ✅ Medical report uploaded successfully!</div>';
                    
                    // Log activity
                    logActivity($conn, $user_id, 'MEDICAL_UPLOAD', 'medical_reports', mysqli_insert_id($conn));
                    
                    // Refresh the page
                    echo '<meta http-equiv="refresh" content="2">';
                } else {
                    $message = '<div class="error-msg"><i class="fa-solid fa-circle-exclamation"></i> ❌ Database error: ' . mysqli_error($conn) . '</div>';
                }
            } else {
                $message = '<div class="error-msg"><i class="fa-solid fa-circle-exclamation"></i> ❌ Failed to upload file</div>';
            }
        }
    } else {
        $message = '<div class="error-msg"><i class="fa-solid fa-circle-exclamation"></i> ❌ Please select a file to upload</div>';
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $report_id = intval($_GET['delete']);
    
    // Get file path
    $file_sql = "SELECT file_path FROM medical_reports WHERE report_id = ? AND user_id = ?";
    $file_stmt = mysqli_prepare($conn, $file_sql);
    mysqli_stmt_bind_param($file_stmt, "ii", $report_id, $user_id);
    mysqli_stmt_execute($file_stmt);
    $file_result = mysqli_stmt_get_result($file_stmt);
    $file = mysqli_fetch_assoc($file_result);
    
    if ($file) {
        // Delete file
        if (file_exists($file['file_path'])) {
            unlink($file['file_path']);
        }
        
        // Delete from database
        $delete_sql = "DELETE FROM medical_reports WHERE report_id = ? AND user_id = ?";
        $delete_stmt = mysqli_prepare($conn, $delete_sql);
        mysqli_stmt_bind_param($delete_stmt, "ii", $report_id, $user_id);
        
        if (mysqli_stmt_execute($delete_stmt)) {
            $message = '<div class="success-msg"><i class="fa-solid fa-check-circle"></i> ✅ Report deleted successfully</div>';
            echo '<meta http-equiv="refresh" content="2">';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Report - Synergy Hub</title>
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
        }
        
        /* NAVBAR */
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
        
        /* SIDEBAR */
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

        .sidebar-stat-item {
            text-align: center;
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

        /* Container */
        .container {
            padding: 30px;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .page-title {
            color: #1e4a76;
            font-size: 32px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .facility-name {
            color: #2c7da0;
            font-size: 18px;
            margin-bottom: 30px;
        }
        
        /* Status Card */
        .status-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            border: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }
        
        .status-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
        }
        
        .status-icon.valid {
            background: #d1fae5;
            color: #10b981;
        }
        
        .status-icon.invalid {
            background: #fee2e2;
            color: #ef4444;
        }
        
        .status-text {
            flex: 1;
            color: #1e293b;
        }
        
        .status-text h3 {
            margin-bottom: 5px;
            font-size: 20px;
        }
        
        .status-text p {
            color: #64748b;
            margin-top: 5px;
        }
        
        /* Upload Form */
        .upload-form {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }
        
        .form-title {
            color: #1e4a76;
            font-size: 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-title i {
            color: #2c7da0;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            color: #475569;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            background: white;
            color: #1e293b;
            font-size: 14px;
            outline: none;
            transition: all 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #2c7da0;
            box-shadow: 0 0 0 3px rgba(44, 125, 160, 0.1);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .file-upload {
            border: 2px dashed #e2e8f0;
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            margin-bottom: 20px;
        }
        
        .file-upload:hover {
            border-color: #2c7da0;
            background: #f8fafc;
        }
        
        .file-upload i {
            font-size: 40px;
            color: #2c7da0;
            margin-bottom: 10px;
        }
        
        .file-upload p {
            color: #64748b;
        }
        
        .file-info {
            color: #94a3b8;
            font-size: 12px;
            margin-top: 10px;
        }
        
        .submit-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #1e4a76 0%, #2c7da0 100%);
            border: none;
            border-radius: 10px;
            color: white;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(30, 74, 118, 0.4);
        }
        
        /* Previous Reports */
        .reports-list {
            background: white;
            border-radius: 20px;
            padding: 30px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }
        
        .report-item {
            background: #f8fafc;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 15px;
            border: 1px solid #e2e8f0;
            transition: all 0.3s;
        }
        
        .report-item:hover {
            transform: translateX(5px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
        
        .report-icon {
            width: 40px;
            height: 40px;
            background: #e0f2fe;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #2c7da0;
            font-size: 20px;
        }
        
        .report-details {
            flex: 1;
        }
        
        .report-name {
            color: #1e293b;
            font-weight: 600;
            margin-bottom: 3px;
        }
        
        .report-meta {
            color: #64748b;
            font-size: 12px;
        }
        
        .report-status {
            padding: 4px 12px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-valid {
            background: #10b981;
            color: white;
        }
        
        .status-expired {
            background: #ef4444;
            color: white;
        }
        
        .delete-btn {
            background: #ef4444;
            color: white;
            border: none;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            cursor: pointer;
            margin-left: 10px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        
        .delete-btn:hover {
            background: #dc2626;
            transform: scale(1.05);
        }
        
        .no-reports {
            text-align: center;
            color: #94a3b8;
            padding: 30px 0;
        }
        
        .no-reports i {
            font-size: 40px;
            margin-bottom: 10px;
            opacity: 0.5;
        }
        
        .success-msg {
            background: #d1fae5;
            border: 1px solid #10b981;
            color: #065f46;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .error-msg {
            background: #fee2e2;
            border: 1px solid #ef4444;
            color: #991b1b;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .back-btn {
            display: inline-block;
            margin-top: 30px;
            color: #1e4a76;
            text-decoration: none;
            font-size: 16px;
            padding: 10px 20px;
            background: #f1f5f9;
            border-radius: 30px;
            transition: all 0.3s;
        }
        
        .back-btn:hover {
            background: #1e4a76;
            color: white;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .status-card {
                flex-direction: column;
                text-align: center;
            }
            
            .navbar {
                flex-direction: column;
                gap: 10px;
            }
            
            .icons {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>

<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <h2>Synergy Hub</h2>
        <p><i class="fa-solid fa-circle"></i> Connect · Collaborate · Create</p>
    </div>
    
    <div class="sidebar-user">
        <div class="sidebar-user-avatar">
            <i class="fa-solid fa-user"></i>
        </div>
        <div class="sidebar-user-info">
            <h4><?php echo htmlspecialchars($_SESSION['name'] ?? 'User'); ?></h4>
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
        <div class="sidebar-club-item">
            <h5>Sports Club</h5>
            <p>Sports and fitness activities...</p>
            <span class="sidebar-club-tag">Sports</span>
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
    
    <h1 class="logo">Synergy <span>Hub</span> - Medical Report</h1>
    
    <div class="icons">
        <div class="points">
            <i class="fa-solid fa-star"></i>
            <span><?php echo $user['PointsBalance']; ?></span>
        </div>
        <a href="pool.php?id=<?php echo $facility_id; ?>" class="home-link">
            <i class="fa-solid fa-arrow-left"></i> Back to Pool
        </a>
    </div>
</header>

<div class="container">
    
    <h1 class="page-title">
        <i class="fa-solid fa-notes-medical"></i> Medical Report
    </h1>
    <div class="facility-name">for <?php echo htmlspecialchars($facility['Name']); ?></div>
    
    <?php echo $message; ?>
    
    <!-- Current Status -->
    <div class="status-card">
        <div class="status-icon <?php echo $has_valid ? 'valid' : 'invalid'; ?>">
            <i class="fa-solid <?php echo $has_valid ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?>"></i>
        </div>
        <div class="status-text">
            <h3><?php echo $has_valid ? 'Medical Report Verified' : 'No Valid Medical Report'; ?></h3>
            <?php if($has_valid): ?>
                <p>Valid until <?php echo date('F d, Y', strtotime($valid_report['expiry_date'] ?? '+1 year')); ?></p>
                <p>Emergency Contact: <?php echo htmlspecialchars($valid_report['emergency_contact_name']); ?> (<?php echo htmlspecialchars($valid_report['emergency_contact_phone']); ?>)</p>
                <?php if(!empty($valid_report['medical_conditions'])): ?>
                    <p>Medical Conditions: <?php echo htmlspecialchars($valid_report['medical_conditions']); ?></p>
                <?php endif; ?>
            <?php else: ?>
                <p>Please upload a valid medical report to book pool lanes</p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Upload Form -->
    <div class="upload-form">
        <h3 class="form-title">
            <i class="fa-solid fa-cloud-upload-alt"></i> Upload New Medical Report
        </h3>
        
        <form method="POST" action="" enctype="multipart/form-data">
            <div class="file-upload" onclick="document.getElementById('medicalFile').click()">
                <i class="fa-solid fa-cloud-upload-alt"></i>
                <p>Click to upload or drag and drop</p>
                <p class="file-info">PDF, JPG, PNG (Max 5MB)</p>
                <input type="file" id="medicalFile" name="medical_file" style="display: none;" accept=".pdf,.jpg,.jpeg,.png" required>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label><i class="fa-solid fa-user"></i> Emergency Contact Name</label>
                    <input type="text" name="emergency_name" required placeholder="Full name of emergency contact">
                </div>
                <div class="form-group">
                    <label><i class="fa-solid fa-phone"></i> Emergency Contact Phone</label>
                    <input type="tel" name="emergency_phone" required placeholder="07X XXX XXXX">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label><i class="fa-regular fa-calendar"></i> Expiry Date</label>
                    <input type="date" name="expiry_date" value="<?php echo date('Y-m-d', strtotime('+1 year')); ?>" required>
                </div>
                <div class="form-group">
                    <label>&nbsp;</label>
                    <div></div>
                </div>
            </div>
            
            <div class="form-group">
                <label><i class="fa-solid fa-stethoscope"></i> Medical Conditions (if any)</label>
                <textarea name="medical_conditions" rows="3" placeholder="List any medical conditions, allergies, or medications..."></textarea>
            </div>
            
            <button type="submit" class="submit-btn">
                <i class="fa-solid fa-upload"></i> Upload Report
            </button>
        </form>
    </div>
    
    <!-- Previous Reports -->
    <div class="reports-list">
        <h3 class="form-title">
            <i class="fa-solid fa-history"></i> Upload History
        </h3>
        
        <?php if(mysqli_num_rows($medical_result) > 0): ?>
            <?php while($report = mysqli_fetch_assoc($medical_result)): 
                $is_valid = $report['is_valid'] && (strtotime($report['expiry_date'] ?? '+1 year') >= time());
            ?>
            <div class="report-item">
                <div class="report-icon">
                    <i class="fa-solid fa-file-medical"></i>
                </div>
                <div class="report-details">
                    <div class="report-name"><?php echo htmlspecialchars($report['file_name']); ?></div>
                    <div class="report-meta">
                        Uploaded on <?php echo date('M d, Y', strtotime($report['upload_date'])); ?> • 
                        <?php echo round($report['file_size'] / 1024); ?> KB
                        <?php if($report['expiry_date']): ?>
                            • Expires: <?php echo date('M d, Y', strtotime($report['expiry_date'])); ?>
                        <?php endif; ?>
                    </div>
                    <?php if(!empty($report['emergency_contact_name'])): ?>
                        <div class="report-meta">Emergency: <?php echo htmlspecialchars($report['emergency_contact_name']); ?></div>
                    <?php endif; ?>
                </div>
                <div>
                    <span class="report-status <?php echo $is_valid ? 'status-valid' : 'status-expired'; ?>">
                        <?php echo $is_valid ? 'Valid' : 'Expired'; ?>
                    </span>
                    <a href="?id=<?php echo $facility_id; ?>&delete=<?php echo $report['report_id']; ?>" 
                       class="delete-btn" 
                       onclick="return confirm('Delete this report?')">
                        <i class="fa-solid fa-trash"></i> Delete
                    </a>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="no-reports">
                <i class="fa-regular fa-file"></i>
                <p>No medical reports uploaded yet</p>
                <p style="font-size: 12px; margin-top: 5px;">Upload your medical report to start booking pool lanes</p>
            </div>
        <?php endif; ?>
    </div>
    
    <a href="pool.php?id=<?php echo $facility_id; ?>" class="back-btn">
        <i class="fa-solid fa-arrow-left"></i> Back to Pool
    </a>
</div>

<script>
function toggleSidebar() {
    const sidebar = document.querySelector("#sidebar");
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
    const sidebar = document.querySelector("#sidebar");
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

// Show filename when selected
document.getElementById('medicalFile').addEventListener('change', function(e) {
    let fileName = e.target.files[0]?.name;
    if (fileName) {
        document.querySelector('.file-upload p').innerHTML = 'Selected: ' + fileName;
        document.querySelector('.file-upload i').innerHTML = 'fa-solid fa-file-check';
    }
});
</script>

</body>
</html>