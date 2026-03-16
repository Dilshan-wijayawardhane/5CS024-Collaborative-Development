<?php
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

// Check if user already checked in today
$check_sql = "SELECT * FROM CheckIns WHERE UserID = ? AND FacilityID = ? AND DATE(Timestamp) = CURDATE()";
$check_stmt = mysqli_prepare($conn, $check_sql);
mysqli_stmt_bind_param($check_stmt, "ii", $user_id, $facility_id);
mysqli_stmt_execute($check_stmt);
$check_result = mysqli_stmt_get_result($check_stmt);
$already_checked_in = mysqli_num_rows($check_result) > 0;

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($facility['Name']); ?> - Synergy Hub</title>
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
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            position: relative;
            z-index: 1000;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .logo {
            font-size: 24px;
            font-weight: 700;
            color: white;
        }
        
        .logo span {
            color: #3b82f6;
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
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            color: white;
            transition: all 0.3s;
            border: 1px solid rgba(255,255,255,0.1);
        }
        
        .points.active {
            transform: scale(1.1);
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
        }
        
        .home-link {
            color: white;
            font-size: 20px;
            text-decoration: none;
        }
        
        .home-link:hover {
            color: #3b82f6;
        }
        
        /* ========================================
           SYNERGY HUB SIDEBAR - WHITE & BLUE
           ======================================== */

        .sidebar {
            position: fixed;
            left: -280px;
            top: 0;
            width: 280px;
            height: 100%;
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            transition: 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 9999;
            box-shadow: 4px 0 30px rgba(0, 0, 0, 0.1);
            border-right: 1px solid rgba(59, 130, 246, 0.2);
            overflow-y: auto;
        }

        .sidebar.active {
            left: 0;
        }

        .sidebar-header {
            padding: 25px 20px 20px 20px;
            border-bottom: 1px solid rgba(59, 130, 246, 0.2);
            margin-bottom: 15px;
            position: relative;
            overflow: hidden;
        }

        .sidebar-header h2 {
            color: #1e293b;
            font-size: 24px;
            font-weight: 700;
            margin: 0 0 5px 0;
            letter-spacing: -0.5px;
        }

        .sidebar-header p {
            color: #3b82f6;
            font-size: 13px;
            margin: 0;
            font-weight: 400;
        }

        .sidebar-header p i {
            color: #3b82f6;
            margin-right: 5px;
            font-size: 10px;
        }

        .sidebar-user {
            padding: 15px 20px;
            background: rgba(59, 130, 246, 0.05);
            margin: 0 15px 20px 15px;
            border-radius: 16px;
            border: 1px solid rgba(59, 130, 246, 0.2);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .sidebar-user-avatar {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: white;
            border: 2px solid white;
        }

        .sidebar-user-info h4 {
            color: #1e293b;
            font-size: 15px;
            margin: 0 0 3px 0;
            font-weight: 600;
        }

        .sidebar-user-info p {
            color: #3b82f6;
            font-size: 12px;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .sidebar-user-info p i {
            color: #3b82f6;
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
            color: #64748b;
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
            text-align: center;
        }

        .sidebar-nav-link:hover {
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
            transform: translateX(5px);
        }

        .sidebar-nav-link:hover i {
            color: #3b82f6;
        }

        .sidebar-nav-link.active {
            background: linear-gradient(90deg, rgba(59, 130, 246, 0.1) 0%, rgba(59, 130, 246, 0.05) 100%);
            color: #3b82f6;
            border-left: 3px solid #3b82f6;
        }

        .sidebar-nav-link.active i {
            color: #3b82f6;
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
            background: linear-gradient(90deg, transparent, rgba(59, 130, 246, 0.2), transparent);
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
            background: rgba(59, 130, 246, 0.03);
            border-radius: 16px;
            padding: 15px;
            margin: 0 15px 20px 15px;
            border: 1px solid rgba(59, 130, 246, 0.1);
        }

        .sidebar-club-preview h4 {
            color: #1e293b;
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
            color: #3b82f6;
        }

        .sidebar-club-item {
            background: white;
            border-radius: 12px;
            padding: 12px;
            margin-bottom: 10px;
            border: 1px solid rgba(59, 130, 246, 0.1);
            transition: transform 0.2s;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .sidebar-club-item:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
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
            line-height: 1.4;
        }

        .sidebar-club-tag {
            background: #3b82f6;
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
            background: white;
            border-radius: 16px;
            border: 1px solid rgba(59, 130, 246, 0.1);
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .sidebar-stat-item {
            text-align: center;
        }

        .sidebar-stat-value {
            color: #3b82f6;
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 3px;
        }

        .sidebar-stat-label {
            color: #64748b;
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
            color: #64748b;
            text-decoration: none;
            font-size: 11px;
            transition: color 0.2s;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .sidebar-footer-links a:hover {
            color: #3b82f6;
        }

        .sidebar-footer-links a i {
            font-size: 10px;
            color: #3b82f6;
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
            background: rgba(59, 130, 246, 0.3);
            border-radius: 20px;
        }

        .sidebar::-webkit-scrollbar-thumb:hover {
            background: #3b82f6;
        }
        
        .container {
            padding: 30px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        /* FACILITY HEADER */
        .facility-header {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 30px;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .facility-icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            color: white;
        }
        
        .facility-info {
            flex: 1;
        }
        
        .facility-name {
            font-size: 36px;
            color: white;
            margin-bottom: 10px;
        }
        
        .facility-type {
            color: #3b82f6;
            font-size: 18px;
            margin-bottom: 10px;
        }
        
        .facility-status {
            display: inline-block;
            padding: 8px 20px;
            border-radius: 30px;
            font-size: 14px;
            font-weight: 600;
            margin: 10px 0;
        }
        
        .status-Open {
            background: rgba(59, 130, 246, 0.2);
            color: #3b82f6;
            border: 1px solid #3b82f6;
        }
        
        .status-Closed {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
            border: 1px solid #ef4444;
        }
        
        .status-Maintenance {
            background: rgba(245, 158, 11, 0.2);
            color: #f59e0b;
            border: 1px solid #f59e0b;
        }
        
        /* CHECK-IN SECTION */
        .checkin-section {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            border: 1px solid rgba(255,255,255,0.2);
            text-align: center;
        }
        
        .points-info {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 30px;
            margin: 20px 0;
            flex-wrap: wrap;
        }
        
        .points-badge {
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(10px);
            color: white;
            padding: 15px 30px;
            border-radius: 50px;
            font-size: 20px;
            font-weight: 600;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .points-badge i {
            color: #3b82f6;
            margin-right: 8px;
        }
        
        .checkin-btn {
            padding: 15px 40px;
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            border: none;
            border-radius: 50px;
            color: white;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .checkin-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(59, 130, 246, 0.4);
        }
        
        .checkin-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }
        
        .checkin-message {
            margin-top: 15px;
            color: #3b82f6;
            font-size: 16px;
        }
        
        /* FEATURES GRID */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }
        
        .feature-card {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            border: 1px solid rgba(255,255,255,0.2);
            transition: all 0.3s;
            cursor: pointer;
            opacity: 0.5;
            pointer-events: none;
            text-align: center;
            text-decoration: none;
            color: white;
            display: block;
        }
        
        .feature-card.active {
            opacity: 1;
            pointer-events: all;
        }
        
        .feature-card.active:hover {
            transform: translateY(-5px);
            background: rgba(255,255,255,0.15);
            box-shadow: 0 10px 30px rgba(59, 130, 246, 0.3);
        }
        
        .feature-icon {
            font-size: 48px;
            color: #3b82f6;
            margin-bottom: 20px;
        }
        
        .feature-title {
            color: white;
            font-size: 22px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .feature-description {
            color: rgba(255,255,255,0.7);
            font-size: 14px;
            line-height: 1.5;
        }
        
        .back-btn {
            display: inline-block;
            margin-top: 40px;
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
            color: #3b82f6;
        }
        
        .back-btn i {
            margin-right: 8px;
        }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 10000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.7);
            backdrop-filter: blur(5px);
            overflow-y: auto;
        }

        .modal-content {
            background: white;
            margin: 50px auto;
            padding: 0;
            width: 90%;
            max-width: 1000px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(59, 130, 246, 0.3);
            border: 1px solid rgba(59, 130, 246, 0.2);
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            padding: 20px 30px;
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            border-bottom: 1px solid rgba(255,255,255,0.1);
            border-radius: 20px 20px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h2 {
            color: white;
            font-size: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .modal-header h2 i {
            color: white;
        }

        .close-btn {
            color: white;
            font-size: 30px;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.3s;
            opacity: 0.8;
        }

        .close-btn:hover {
            color: white;
            opacity: 1;
        }

        .modal-body {
            padding: 30px;
        }

        /* ========== STUDY ROOMS STYLES ========== */
        .date-selector {
            background: rgba(59, 130, 246, 0.05);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
            border: 1px solid rgba(59, 130, 246, 0.2);
        }

        .date-selector label {
            color: #1e293b;
            font-size: 16px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .date-selector label i {
            color: #3b82f6;
        }

        .date-input {
            padding: 10px 15px;
            background: white;
            border: 1px solid rgba(59, 130, 246, 0.3);
            border-radius: 10px;
            color: #1e293b;
            font-size: 14px;
            flex: 1;
            max-width: 200px;
        }

        .date-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        }

        .room-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .room-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            border: 2px solid transparent;
            transition: all 0.3s;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }

        .room-card.available {
            cursor: pointer;
            border: 1px solid rgba(59, 130, 246, 0.2);
        }

        .room-card.available:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 24px rgba(59, 130, 246, 0.2);
            border-color: #3b82f6;
        }

        .room-card.selected {
            border-color: #3b82f6;
            background: rgba(59, 130, 246, 0.05);
            transform: scale(1.02);
            box-shadow: 0 8px 24px rgba(59, 130, 246, 0.2);
        }

        .room-card.busy {
            opacity: 0.7;
            cursor: not-allowed;
            background: #f1f5f9;
        }

        .room-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .room-header h3 {
            color: #1e293b;
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .room-header h3 i {
            color: #3b82f6;
        }

        .premium-badge {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
            font-size: 10px;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 20px;
            margin-left: 10px;
            text-transform: uppercase;
        }

        .room-capacity {
            color: #3b82f6;
            font-size: 14px;
            font-weight: 600;
        }

        .room-capacity i {
            margin-right: 5px;
        }

        .room-features {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 15px;
        }

        .feature {
            background: rgba(59, 130, 246, 0.1);
            padding: 5px 10px;
            border-radius: 20px;
            color: #1e293b;
            font-size: 11px;
            font-weight: 500;
        }

        .feature i {
            color: #3b82f6;
            margin-right: 5px;
        }

        .room-status {
            font-size: 14px;
            margin-bottom: 15px;
            padding: 8px;
            border-radius: 8px;
            text-align: center;
            font-weight: 600;
        }

        .room-status.available {
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
            border: 1px solid #3b82f6;
        }

        .room-status.busy {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            border: 1px solid #ef4444;
        }

        .time-slots {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin: 15px 0;
        }

        .time-slot {
            padding: 6px 10px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            flex: 1 0 auto;
            text-align: center;
            min-width: 70px;
        }

        .time-slot.available {
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
            border: 1px solid #3b82f6;
        }

        .time-slot.available:hover {
            background: #3b82f6;
            color: white;
            transform: scale(1.05);
        }

        .time-slot.available.selected {
            background: #3b82f6;
            color: white;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.5);
        }

        .time-slot.busy {
            background: #f1f5f9;
            color: #94a3b8;
            border: 1px dashed #cbd5e1;
            cursor: not-allowed;
            text-decoration: line-through;
        }

        .next-available {
            color: #3b82f6;
            font-size: 13px;
            margin: 10px 0;
            text-align: center;
            padding: 5px;
            background: rgba(59, 130, 246, 0.1);
            border-radius: 8px;
        }

        .select-hint {
            display: block;
            color: #64748b;
            font-size: 11px;
            margin-top: 8px;
            text-align: center;
        }

        .book-btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            border: none;
            border-radius: 10px;
            color: white;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .book-btn:hover:not(:disabled) {
            transform: scale(1.02);
            box-shadow: 0 5px 20px rgba(59, 130, 246, 0.4);
        }

        .book-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .booking-summary {
            margin-top: 30px;
            background: rgba(59, 130, 246, 0.05);
            border-radius: 15px;
            padding: 25px;
            border: 1px solid rgba(59, 130, 246, 0.2);
            animation: slideUp 0.3s ease;
        }

        @keyframes slideUp {
            from {
                transform: translateY(20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .booking-summary h3 {
            color: #1e293b;
            font-size: 18px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .booking-summary h3 i {
            color: #3b82f6;
        }

        .summary-details {
            background: white;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid rgba(59, 130, 246, 0.1);
        }

        .summary-details p {
            color: #1e293b;
            margin: 8px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .summary-details p i {
            color: #3b82f6;
            width: 20px;
        }

        .summary-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
        }

        .confirm-btn {
            padding: 12px 25px;
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            border: none;
            border-radius: 10px;
            color: white;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .confirm-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 20px rgba(59, 130, 246, 0.4);
        }

        .cancel-btn {
            padding: 12px 25px;
            background: white;
            border: 1px solid rgba(59, 130, 246, 0.3);
            border-radius: 10px;
            color: #1e293b;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .cancel-btn:hover {
            background: #f8fafc;
            border-color: #3b82f6;
        }

        /* ========== PRINT SERVICES STYLES ========== */
        .print-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .print-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            border: 1px solid rgba(59, 130, 246, 0.2);
            transition: all 0.3s;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }

        .print-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 24px rgba(59, 130, 246, 0.2);
            border-color: #3b82f6;
        }

        .print-card.selected {
            border: 2px solid #3b82f6;
            background: rgba(59, 130, 246, 0.05);
        }

        .print-card i {
            font-size: 40px;
            color: #3b82f6;
            margin-bottom: 15px;
        }

        .print-card h3 {
            color: #1e293b;
            font-size: 18px;
            margin-bottom: 10px;
        }

        .print-price {
            color: #3b82f6;
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .print-price small {
            font-size: 12px;
            color: #64748b;
        }

        .print-description {
            color: #64748b;
            font-size: 12px;
            margin-bottom: 15px;
        }

        .print-info {
            background: rgba(59, 130, 246, 0.03);
            border-radius: 15px;
            padding: 25px;
            border: 1px solid rgba(59, 130, 246, 0.1);
            margin-top: 20px;
        }

        .print-info h4 {
            color: #1e293b;
            font-size: 16px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .print-info h4 i {
            color: #3b82f6;
        }

        .file-upload-area {
            background: white;
            border: 2px dashed rgba(59, 130, 246, 0.3);
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            margin-bottom: 15px;
            transition: all 0.3s;
            cursor: pointer;
        }

        .file-upload-area:hover {
            border-color: #3b82f6;
            background: rgba(59, 130, 246, 0.05);
        }

        .file-upload-area i {
            font-size: 40px;
            color: #3b82f6;
            margin-bottom: 10px;
        }

        .file-upload-area p {
            color: #1e293b;
            font-size: 14px;
        }

        .file-upload-area small {
            color: #64748b;
            font-size: 12px;
        }

        #fileUpload {
            display: none;
        }

        .file-name {
            color: #3b82f6;
            font-size: 14px;
            margin-top: 10px;
            font-weight: 600;
        }

        .print-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 20px;
        }

        .print-action-btn {
            padding: 12px 30px;
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            border: none;
            border-radius: 10px;
            color: white;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .print-action-btn:hover:not(:disabled) {
            transform: scale(1.05);
            box-shadow: 0 5px 20px rgba(59, 130, 246, 0.4);
        }

        .print-action-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .print-summary {
            background: rgba(59, 130, 246, 0.1);
            border-radius: 10px;
            padding: 15px;
            margin-top: 20px;
            border: 1px solid #3b82f6;
        }

        .print-summary p {
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
        }

        .print-summary i {
            color: #3b82f6;
        }
        
        @media (max-width: 768px) {
            .facility-header {
                flex-direction: column;
                text-align: center;
            }
            
            .points-info {
                flex-direction: column;
            }
            
            .features-grid {
                grid-template-columns: 1fr;
            }
            
            .modal-content {
                margin: 20px;
                width: auto;
            }
            
            .summary-actions {
                flex-direction: column;
            }
            
            .print-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>

<div class="bg"></div>

<!-- SIDEBAR - WHITE & BLUE -->
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
                <i class="fa-solid fa-home"></i> Home
            </a>
        </li>
        <li class="sidebar-nav-item">
            <a href="facilities.php" class="sidebar-nav-link active">
                <i class="fa-solid fa-building"></i> Facilities
                <span class="sidebar-badge"><?php echo $facilities_count; ?></span>
            </a>
        </li>
        <li class="sidebar-nav-item">
            <a href="transport.php" class="sidebar-nav-link">
                <i class="fa-solid fa-bus"></i> Transport
            </a>
        </li>
        <li class="sidebar-nav-item">
            <a href="game.php" class="sidebar-nav-link">
                <i class="fa-solid fa-futbol"></i> Game Field
            </a>
        </li>
        <li class="sidebar-nav-item">
            <a href="clubs.php" class="sidebar-nav-link">
                <i class="fa-solid fa-users"></i> Club Hub
            </a>
        </li>
        <li class="sidebar-nav-item">
            <a href="qr.html" class="sidebar-nav-link">
                <i class="fa-solid fa-qrcode"></i> QR Scanner
            </a>
        </li>
        <li class="sidebar-nav-item">
            <a href="notifications.php" class="sidebar-nav-link">
                <i class="fa-solid fa-bell"></i> Notifications
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
        <div class="sidebar-copyright">© 2025 Synergy Hub</div>
    </div>
</div>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<!-- NAVBAR -->
<header class="navbar">
    <div class="menu-btn" onclick="toggleSidebar()">
        <i class="fa-solid fa-bars"></i>
    </div>
    
    <h1 class="logo">Synergy <span>Hub</span> - <?php echo htmlspecialchars($facility['Name']); ?></h1>
    
    <div class="icons">
        <div class="points">
            <i class="fa-solid fa-star"></i>
            <span id="pointsDisplay"><?php echo $user['PointsBalance']; ?></span>
        </div>
        <a href="facilities.php" class="home-link">
            <i class="fa-solid fa-arrow-left"></i> Back
        </a>
    </div>
</header>

<!-- MAIN CONTENT -->
<div class="container">
    
    <!-- FACILITY HEADER -->
    <div class="facility-header">
        <?php
        $icon = 'fa-building';
        if($facility['Type'] == 'Gym') $icon = 'fa-dumbbell';
        else if($facility['Type'] == 'Library') $icon = 'fa-book';
        else if($facility['Type'] == 'Café') $icon = 'fa-mug-saucer';
        else if($facility['Type'] == 'GameField') $icon = 'fa-futbol';
        else if($facility['Type'] == 'Transport') $icon = 'fa-bus';
        ?>
        <div class="facility-icon">
            <i class="fa-solid <?php echo $icon; ?>"></i>
        </div>
        <div class="facility-info">
            <h1 class="facility-name"><?php echo htmlspecialchars($facility['Name']); ?></h1>
            <div class="facility-type"><?php echo htmlspecialchars($facility['Type']); ?></div>
            <div class="facility-status status-<?php echo $facility['Status']; ?>">
                <?php echo $facility['Status']; ?>
            </div>
        </div>
    </div>
    
    <!-- CHECK-IN SECTION -->
    <div class="checkin-section">
        <div class="points-info">
            <div class="points-badge">
                <i class="fa-solid fa-star"></i> Your Points: <span id="currentPoints"><?php echo $user['PointsBalance']; ?></span>
            </div>
            <button class="checkin-btn" id="checkinBtn" onclick="checkIn(<?php echo $facility_id; ?>)"
                <?php echo ($already_checked_in || $facility['Status'] != 'Open') ? 'disabled' : ''; ?>>
                <i class="fa-solid fa-location-dot"></i> Check In (+10 points)
            </button>
        </div>
        <div class="checkin-message" id="checkinMessage">
            <?php if($already_checked_in): ?>
                ✅ You have already checked in today! Access all features below.
            <?php elseif($facility['Status'] != 'Open'): ?>
                ⚠️ This facility is currently closed.
            <?php else: ?>
                Check in to access facility features and earn 10 points!
            <?php endif; ?>
        </div>
    </div>
    
    <!-- FACILITY FEATURES -->
    <h2 style="color: white; margin: 40px 0 20px; font-size: 28px;">📍 Facility Features</h2>
    <div class="features-grid">
        
        <?php if($facility['Type'] == 'Library'): ?>
        <!-- ==================== LIBRARY FEATURES ==================== -->
        <a href="library_books.php?id=<?php echo $facility_id; ?>&tab=browse" class="feature-card <?php echo $already_checked_in ? 'active' : ''; ?>">
            <div class="feature-icon">
                <i class="fa-solid fa-magnifying-glass"></i>
            </div>
            <div class="feature-title">Browse Books</div>
            <div class="feature-description">
                Search and browse available books in the library catalog. View book details, authors, and availability.
            </div>
        </a>
        
        <a href="library_books.php?id=<?php echo $facility_id; ?>&tab=borrowed" class="feature-card <?php echo $already_checked_in ? 'active' : ''; ?>">
            <div class="feature-icon">
                <i class="fa-solid fa-book"></i>
            </div>
            <div class="feature-title">My Borrowed Books</div>
            <div class="feature-description">
                View books you've borrowed, check due dates, and return books. Earn 2 points when you return!
            </div>
        </a>
        
        <!-- Study Rooms Card -->
        <div class="feature-card <?php echo $already_checked_in ? 'active' : ''; ?>" onclick="openStudyRoomsModal()">
            <div class="feature-icon">
                <i class="fa-solid fa-door-open"></i>
            </div>
            <div class="feature-title">Study Rooms</div>
            <div class="feature-description">
                Reserve study rooms for group work. Free WiFi, whiteboards, and projectors available.
            </div>
        </div>
        
        <!-- Print Services Card -->
        <div class="feature-card <?php echo $already_checked_in ? 'active' : ''; ?>" onclick="openPrintServicesModal()">
            <div class="feature-icon">
                <i class="fa-solid fa-print"></i>
            </div>
            <div class="feature-title">Print Services</div>
            <div class="feature-description">
                Print, scan, and photocopy documents. Pay with points or cash.
            </div>
        </div>
        
        <?php endif; ?>
    </div>
    
    <a href="facilities.php" class="back-btn">
        <i class="fa-solid fa-arrow-left"></i> Back to Facilities
    </a>
</div>

<!-- STUDY ROOMS MODAL -->
<div id="studyRoomsModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fa-solid fa-door-open"></i> Study Rooms</h2>
            <span class="close-btn" onclick="closeStudyRoomsModal()">&times;</span>
        </div>
        <div class="modal-body">
            
            <!-- Date Selector -->
            <div class="date-selector">
                <label><i class="fa-regular fa-calendar"></i> Select Date:</label>
                <input type="date" id="bookingDate" class="date-input" value="<?php echo date('Y-m-d'); ?>" min="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d', strtotime('+7 days')); ?>">
            </div>
            
            <!-- Room Cards -->
            <div class="room-grid">
                <!-- Room 101 -->
                <div class="room-card available" id="room101" onclick="selectRoom(101)">
                    <div class="room-header">
                        <h3><i class="fa-regular fa-door-open"></i> Room 101</h3>
                        <span class="room-capacity"><i class="fa-solid fa-users"></i> 4 people</span>
                    </div>
                    <div class="room-features">
                        <span class="feature"><i class="fa-solid fa-wifi"></i> WiFi</span>
                        <span class="feature"><i class="fa-solid fa-chalkboard"></i> Whiteboard</span>
                        <span class="feature"><i class="fa-solid fa-tv"></i> Projector</span>
                    </div>
                    <div class="room-status available">
                        <i class="fa-regular fa-circle-check"></i> Available Now
                    </div>
                    <div class="time-slots" id="room101-slots">
                        <div class="time-slot available" onclick="event.stopPropagation(); selectTimeSlot(101, '10:00 AM')">10:00 AM</div>
                        <div class="time-slot available" onclick="event.stopPropagation(); selectTimeSlot(101, '11:00 AM')">11:00 AM</div>
                        <div class="time-slot available" onclick="event.stopPropagation(); selectTimeSlot(101, '12:00 PM')">12:00 PM</div>
                        <div class="time-slot busy" onclick="event.stopPropagation();">1:00 PM</div>
                        <div class="time-slot available" onclick="event.stopPropagation(); selectTimeSlot(101, '2:00 PM')">2:00 PM</div>
                        <div class="time-slot available" onclick="event.stopPropagation(); selectTimeSlot(101, '3:00 PM')">3:00 PM</div>
                    </div>
                    <button class="book-btn" id="bookBtn101" onclick="bookRoom(101)" disabled>
                        <i class="fa-regular fa-calendar-check"></i> Book Selected Slot
                    </button>
                    <small class="select-hint">👆 Select a time slot first</small>
                </div>
                
                <!-- Room 102 -->
                <div class="room-card available" id="room102" onclick="selectRoom(102)">
                    <div class="room-header">
                        <h3><i class="fa-regular fa-door-open"></i> Room 102</h3>
                        <span class="room-capacity"><i class="fa-solid fa-users"></i> 6 people</span>
                    </div>
                    <div class="room-features">
                        <span class="feature"><i class="fa-solid fa-wifi"></i> WiFi</span>
                        <span class="feature"><i class="fa-solid fa-chalkboard"></i> Whiteboard</span>
                        <span class="feature"><i class="fa-solid fa-tv"></i> Projector</span>
                    </div>
                    <div class="room-status available">
                        <i class="fa-regular fa-circle-check"></i> Available Now
                    </div>
                    <div class="time-slots" id="room102-slots">
                        <div class="time-slot available" onclick="event.stopPropagation(); selectTimeSlot(102, '10:00 AM')">10:00 AM</div>
                        <div class="time-slot busy" onclick="event.stopPropagation();">11:00 AM</div>
                        <div class="time-slot available" onclick="event.stopPropagation(); selectTimeSlot(102, '12:00 PM')">12:00 PM</div>
                        <div class="time-slot available" onclick="event.stopPropagation(); selectTimeSlot(102, '1:00 PM')">1:00 PM</div>
                        <div class="time-slot available" onclick="event.stopPropagation(); selectTimeSlot(102, '2:00 PM')">2:00 PM</div>
                        <div class="time-slot busy" onclick="event.stopPropagation();">3:00 PM</div>
                    </div>
                    <button class="book-btn" id="bookBtn102" onclick="bookRoom(102)" disabled>
                        <i class="fa-regular fa-calendar-check"></i> Book Selected Slot
                    </button>
                    <small class="select-hint">👆 Select a time slot first</small>
                </div>
                
                <!-- Room 103 - Conference Room -->
                <div class="room-card busy">
                    <div class="room-header">
                        <h3><i class="fa-regular fa-door-open"></i> Room 103 <span class="premium-badge">PREMIUM</span></h3>
                        <span class="room-capacity"><i class="fa-solid fa-users"></i> 8 people</span>
                    </div>
                    <div class="room-features">
                        <span class="feature"><i class="fa-solid fa-wifi"></i> WiFi</span>
                        <span class="feature"><i class="fa-solid fa-chalkboard"></i> Whiteboard</span>
                        <span class="feature"><i class="fa-solid fa-tv"></i> Projector</span>
                        <span class="feature"><i class="fa-solid fa-phone"></i> Conference Phone</span>
                    </div>
                    <div class="room-status busy">
                        <i class="fa-regular fa-clock"></i> Occupied until 3:00 PM
                    </div>
                    <div class="next-available">
                        <i class="fa-regular fa-hourglass"></i> Next available: 3:30 PM
                    </div>
                    <button class="book-btn" disabled>
                        <i class="fa-regular fa-bell"></i> Notify Me
                    </button>
                </div>
            </div>
            
            <!-- Booking Summary -->
            <div class="booking-summary" id="bookingSummary" style="display: none;">
                <h3><i class="fa-regular fa-rectangle-list"></i> Booking Summary</h3>
                <div class="summary-details">
                    <p><i class="fa-regular fa-door-open"></i> <strong>Room:</strong> <span id="summaryRoom"></span></p>
                    <p><i class="fa-regular fa-calendar"></i> <strong>Date:</strong> <span id="summaryDate"></span></p>
                    <p><i class="fa-regular fa-clock"></i> <strong>Time:</strong> <span id="summaryTime"></span></p>
                    <p><i class="fa-regular fa-star"></i> <strong>Points:</strong> 10 points (refundable)</p>
                </div>
                <div class="summary-actions">
                    <button class="confirm-btn" onclick="confirmBooking()">
                        <i class="fa-regular fa-circle-check"></i> Confirm Booking
                    </button>
                    <button class="cancel-btn" onclick="cancelSelection()">
                        <i class="fa-regular fa-circle-xmark"></i> Cancel
                    </button>
                </div>
            </div>
            
        </div>
    </div>
</div>

<!-- PRINT SERVICES MODAL -->
<div id="printServicesModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fa-solid fa-print"></i> Print Services</h2>
            <span class="close-btn" onclick="closePrintServicesModal()">&times;</span>
        </div>
        <div class="modal-body">
            
            <!-- Print Options -->
            <div class="print-options">
                <div class="print-card" id="printBW" onclick="selectPrintType('bw')">
                    <i class="fa-solid fa-file-lines"></i>
                    <h3>Black & White</h3>
                    <div class="print-price">5 <small>points/page</small></div>
                    <div class="print-description">Single-sided black & white printing</div>
                </div>
                
                <div class="print-card" id="printColor" onclick="selectPrintType('color')">
                    <i class="fa-solid fa-file-image"></i>
                    <h3>Color Print</h3>
                    <div class="print-price">10 <small>points/page</small></div>
                    <div class="print-description">Full color printing, high quality</div>
                </div>
                
                <div class="print-card" id="printCopy" onclick="selectPrintType('copy')">
                    <i class="fa-solid fa-copy"></i>
                    <h3>Photocopy</h3>
                    <div class="print-price">3 <small>points/page</small></div>
                    <div class="print-description">Black & white photocopying</div>
                </div>
                
                <div class="print-card" id="printScan" onclick="selectPrintType('scan')">
                    <i class="fa-solid fa-scanner"></i>
                    <h3>Scan</h3>
                    <div class="print-price">2 <small>points/page</small></div>
                    <div class="print-description">Scan to PDF or image</div>
                </div>
            </div>
            
            <!-- File Upload -->
            <div class="print-info">
                <h4><i class="fa-solid fa-cloud-arrow-up"></i> Upload Document</h4>
                <div class="file-upload-area" onclick="document.getElementById('fileUpload').click()">
                    <i class="fa-solid fa-cloud-upload-alt"></i>
                    <p>Click to upload or drag and drop</p>
                    <small>Supported: PDF, DOC, DOCX, JPG, PNG (Max 10MB)</small>
                    <div id="fileName" class="file-name"></div>
                </div>
                <input type="file" id="fileUpload" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" onchange="updateFileName(this)">
                
                <!-- Pages Input -->
                <div style="margin-top: 20px;">
                    <label style="color: #1e293b; display: block; margin-bottom: 10px; font-weight: 600;"><i class="fa-regular fa-file"></i> Number of pages:</label>
                    <input type="number" id="pageCount" min="1" max="100" value="1" style="width: 100px; padding: 10px; border-radius: 8px; background: white; border: 1px solid rgba(59, 130, 246, 0.3); color: #1e293b;">
                </div>
                
                <!-- Print Summary -->
                <div id="printSummary" class="print-summary" style="display: none;">
                    <p><i class="fa-regular fa-circle-check"></i> <span id="printSummaryText"></span></p>
                </div>
                
                <!-- Actions -->
                <div class="print-actions">
                    <button class="print-action-btn" id="printSubmitBtn" onclick="submitPrintJob()" disabled>
                        <i class="fa-regular fa-print"></i> Submit Print Job
                    </button>
                    <button class="cancel-btn" onclick="closePrintServicesModal()">
                        <i class="fa-regular fa-circle-xmark"></i> Cancel
                    </button>
                </div>
            </div>
            
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

// ==================== CHECK-IN FUNCTION ====================
function checkIn(facilityId) {
    fetch('checkin.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'facility_id=' + facilityId
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            let pointsSpan = document.getElementById('pointsDisplay');
            let currentPoints = parseInt(pointsSpan.textContent);
            pointsSpan.textContent = data.new_points;
            document.getElementById('currentPoints').textContent = data.new_points;
            
            document.querySelector('.points').classList.add('active');
            setTimeout(() => {
                document.querySelector('.points').classList.remove('active');
            }, 500);
            
            document.getElementById('checkinBtn').disabled = true;
            document.getElementById('checkinMessage').innerHTML = '✅ Check-in successful! +10 points added. You can now access all features.';
            
            document.querySelectorAll('.feature-card').forEach(card => {
                card.classList.add('active');
            });
        } else {
            alert('Error: ' + data.message);
        }
    });
}

// ==================== MODAL FUNCTIONS ====================

// Study Rooms Modal
function openStudyRoomsModal() {
    document.getElementById('studyRoomsModal').style.display = 'block';
}

function closeStudyRoomsModal() {
    document.getElementById('studyRoomsModal').style.display = 'none';
    cancelSelection();
}

// Print Services Modal
function openPrintServicesModal() {
    document.getElementById('printServicesModal').style.display = 'block';
}

function closePrintServicesModal() {
    document.getElementById('printServicesModal').style.display = 'none';
    resetPrintSelection();
}

// ==================== STUDY ROOMS FUNCTIONS ====================
let selectedRoom = null;
let selectedTime = null;

function selectRoom(roomId) {
    document.querySelectorAll('.room-card').forEach(card => {
        card.classList.remove('selected');
    });
    document.getElementById(`room${roomId}`).classList.add('selected');
    selectedRoom = roomId;
}

function selectTimeSlot(roomId, time) {
    document.querySelectorAll(`#room${roomId}-slots .time-slot`).forEach(slot => {
        slot.classList.remove('selected');
    });
    event.target.classList.add('selected');
    selectedTime = time;
    selectedRoom = roomId;
    
    document.getElementById(`bookBtn${roomId}`).disabled = false;
    showBookingSummary(roomId, time);
}

function showBookingSummary(roomId, time) {
    let roomName = roomId === 101 ? 'Room 101' : (roomId === 102 ? 'Room 102' : 'Room 103');
    let date = document.getElementById('bookingDate').value;
    let formattedDate = new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
    
    document.getElementById('summaryRoom').textContent = roomName;
    document.getElementById('summaryDate').textContent = formattedDate;
    document.getElementById('summaryTime').textContent = time;
    document.getElementById('bookingSummary').style.display = 'block';
}

function cancelSelection() {
    selectedRoom = null;
    selectedTime = null;
    
    document.querySelectorAll('.room-card').forEach(card => {
        card.classList.remove('selected');
    });
    document.querySelectorAll('.time-slot').forEach(slot => {
        slot.classList.remove('selected');
    });
    document.querySelectorAll('[id^="bookBtn"]').forEach(btn => {
        btn.disabled = true;
    });
    document.getElementById('bookingSummary').style.display = 'none';
}

function bookRoom(roomId) {
    if(selectedRoom === roomId && selectedTime) {
        confirmBooking();
    }
}

function confirmBooking() {
    if (selectedRoom && selectedTime) {
        let roomName = selectedRoom === 101 ? 'Room 101' : (selectedRoom === 102 ? 'Room 102' : 'Room 103');
        let date = document.getElementById('bookingDate').value;
        let formattedDate = new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
        
        alert(`✅ Room ${roomName} booked successfully for ${formattedDate} at ${selectedTime}! Check your email for confirmation.`);
        cancelSelection();
        closeStudyRoomsModal();
    }
}

document.getElementById('bookingDate')?.addEventListener('change', function() {
    alert('Showing availability for ' + this.value);
    cancelSelection();
});

// ==================== PRINT SERVICES FUNCTIONS ====================
let selectedPrintType = null;
let selectedPrintPrice = 0;

function selectPrintType(type) {
    document.querySelectorAll('.print-card').forEach(card => {
        card.classList.remove('selected');
    });
    
    document.getElementById(`print${type.toUpperCase()}`).classList.add('selected');
    selectedPrintType = type;
    
    switch(type) {
        case 'bw': selectedPrintPrice = 5; break;
        case 'color': selectedPrintPrice = 10; break;
        case 'copy': selectedPrintPrice = 3; break;
        case 'scan': selectedPrintPrice = 2; break;
    }
    
    updatePrintSummary();
}

function updateFileName(input) {
    if(input.files && input.files[0]) {
        document.getElementById('fileName').textContent = '📄 ' + input.files[0].name;
        updatePrintSummary();
    }
}

function updatePrintSummary() {
    let pages = document.getElementById('pageCount').value;
    let file = document.getElementById('fileUpload').files[0];
    
    if(selectedPrintType && file) {
        let totalPoints = selectedPrintPrice * pages;
        let typeName = selectedPrintType === 'bw' ? 'Black & White' : 
                      (selectedPrintType === 'color' ? 'Color' : 
                      (selectedPrintType === 'copy' ? 'Photocopy' : 'Scan'));
        
        document.getElementById('printSummaryText').innerHTML = 
            `${typeName} • ${pages} page${pages > 1 ? 's' : ''} • ${totalPoints} points`;
        document.getElementById('printSummary').style.display = 'block';
        document.getElementById('printSubmitBtn').disabled = false;
    } else {
        document.getElementById('printSummary').style.display = 'none';
        document.getElementById('printSubmitBtn').disabled = true;
    }
}

function submitPrintJob() {
    let pages = document.getElementById('pageCount').value;
    let totalPoints = selectedPrintPrice * pages;
    let file = document.getElementById('fileUpload').files[0];
    
    if(confirm(`Submit print job? This will cost ${totalPoints} points.`)) {
        alert(`✅ Print job submitted successfully! Your document will be ready at the print station.`);
        closePrintServicesModal();
    }
}

function resetPrintSelection() {
    selectedPrintType = null;
    selectedPrintPrice = 0;
    document.querySelectorAll('.print-card').forEach(card => {
        card.classList.remove('selected');
    });
    document.getElementById('fileUpload').value = '';
    document.getElementById('fileName').textContent = '';
    document.getElementById('pageCount').value = '1';
    document.getElementById('printSummary').style.display = 'none';
    document.getElementById('printSubmitBtn').disabled = true;
}

document.getElementById('pageCount')?.addEventListener('input', updatePrintSummary);

// ==================== CLOSE MODAL WHEN CLICKING OUTSIDE ====================
window.onclick = function(event) {
    const studyModal = document.getElementById('studyRoomsModal');
    const printModal = document.getElementById('printServicesModal');
    
    if (event.target == studyModal) {
        studyModal.style.display = 'none';
        cancelSelection();
    }
    if (event.target == printModal) {
        printModal.style.display = 'none';
        resetPrintSelection();
    }
}

// ==================== FEATURE ACTION ====================
function featureAction(feature) {
    alert(`🔧 "${feature}" feature is coming soon! We're working on it.`);
}
</script>

</body>
</html>