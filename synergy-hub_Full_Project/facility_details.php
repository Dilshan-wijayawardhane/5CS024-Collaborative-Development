<?php

require_once 'config.php';
require_once 'functions.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$facility_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$facility_sql = "SELECT * FROM Facilities WHERE FacilityID = ?";
$facility_stmt = mysqli_prepare($conn, $facility_sql);
mysqli_stmt_bind_param($facility_stmt, "i", $facility_id);
mysqli_stmt_execute($facility_stmt);
$facility_result = mysqli_stmt_get_result($facility_stmt);
$facility = mysqli_fetch_assoc($facility_result);

$check_sql = "SELECT * FROM CheckIns WHERE UserID = ? AND FacilityID = ? AND DATE(Timestamp) = CURDATE()";
$check_stmt = mysqli_prepare($conn, $check_sql);
mysqli_stmt_bind_param($check_stmt, "ii", $user_id, $facility_id);
mysqli_stmt_execute($check_stmt);
$check_result = mysqli_stmt_get_result($check_stmt);
$already_checked_in = mysqli_num_rows($check_result) > 0;

$user_sql = "SELECT PointsBalance, Name FROM Users WHERE UserID = ?";
$user_stmt = mysqli_prepare($conn, $user_sql);
mysqli_stmt_bind_param($user_stmt, "i", $user_id);
mysqli_stmt_execute($user_stmt);
$user_result = mysqli_stmt_get_result($user_stmt);
$user = mysqli_fetch_assoc($user_result);

$facilities_count_sql = "SELECT COUNT(*) as count FROM Facilities WHERE Status = 'Open'";
$facilities_count_result = mysqli_query($conn, $facilities_count_sql);
$facilities_count = mysqli_fetch_assoc($facilities_count_result)['count'];

// Get bus routes for schedule
$bus_routes_sql = "SELECT * FROM bus_routes ORDER BY route_id";
$bus_routes_result = mysqli_query($conn, $bus_routes_sql);

// Get campus transport schedule
$campus_transport_sql = "SELECT * FROM campus_transport ORDER BY 
                          CASE 
                              WHEN from_campus = 'CINEC' THEN 1 
                              ELSE 0 
                          END, from_campus";
$campus_transport_result = mysqli_query($conn, $campus_transport_sql);

// Get user passes
$passes_sql = "SELECT * FROM TransportPasses WHERE UserID = ? ORDER BY ValidUntil DESC";
$passes_stmt = mysqli_prepare($conn, $passes_sql);
mysqli_stmt_bind_param($passes_stmt, "i", $user_id);
mysqli_stmt_execute($passes_stmt);
$passes_result = mysqli_stmt_get_result($passes_stmt);

// Hardcoded route details
$route_details = [
    'cinec' => ['name' => 'Malabe', 'price' => 100, 'frequency' => 'Every 30 mins'],
    'gampaha1' => ['name' => 'Gampaha - 1', 'price' => 120, 'frequency' => 'Every 45 mins'],
    'gampaha2' => ['name' => 'Gampaha - 2', 'price' => 120, 'frequency' => 'Every 45 mins'],
    'hendala' => ['name' => 'Hendala', 'price' => 80, 'frequency' => 'Every 20 mins'],
    'moratuwa' => ['name' => 'Moratuwa', 'price' => 150, 'frequency' => 'Every 60 mins'],
    'negombo' => ['name' => 'Negombo', 'price' => 200, 'frequency' => 'Every 90 mins'],
];

// Feature images mapping
$feature_images = [
    'menu' => 'viewmenu.jpg',
    'order' => 'orderfood.jpg',
    'offer' => 'offer.jpg',
    'reserve' => 'reservedtable.jpg',
    'books' => 'borrowsbook.jpg',
    'borrowed' => 'borrowbooks.jpg',
    'study' => 'studyarea.jpg',
    'print' => 'print.jpg',
    'gym_equipment' => 'https://images.pexels.com/photos/1954524/pexels-photo-1954524.jpeg?auto=compress&cs=tinysrgb&w=600',
    'fitness' => 'fitness.jpg',
    'trainer' => 'trainer.jpg',
    'locker' => 'locker.jpg',
    'field' => 'bookfield.jpg',
    'sports_equipment' => 'sports_equipment.jpg',
    'tournament' => 'tournament.jpg',
    'scoreboard' => 'scoreboard.jpg',
    'routes' => 'shuttle.jpg',
    'ticket' => 'pass.jpg',
    'tracking' => 'livetrack.jpg',
    'schedule' => 'bustimetable.jpg',
    'pool' => 'Pool.jpg',
    'lane' => 'lanepool.jpg',
    'swim_lesson' => 'swim_lesson.jpg',
    'default' => 'deffult.jpg'
];
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
            position: relative;
            z-index: 1000;
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
        }
        
        .points.active {
            transform: scale(1.1);
        }
        
        .home-link {
            color: #1e4a76;
            font-size: 20px;
            text-decoration: none;
            transition: color 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
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
            text-align: center;
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
            display: flex;
            align-items: center;
            gap: 4px;
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
        
        /* MAIN CONTAINER */
        .container {
            padding: 30px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        /* FACILITY HEADER CARD */
        .facility-header {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 30px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .facility-icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #1e4a76 0%, #2c7da0 100%);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            color: white;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .facility-info {
            flex: 1;
        }
        
        .facility-name {
            font-size: 36px;
            color: #1e4a76;
            margin-bottom: 10px;
        }
        
        .facility-type {
            color: #2c7da0;
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
            background: #10b981;
            color: white;
        }
        
        .status-Closed {
            background: #ef4444;
            color: white;
        }
        
        .status-Maintenance {
            background: #f59e0b;
            color: white;
        }
        
        /* CHECK-IN SECTION */
        .checkin-section {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            border: 1px solid #e2e8f0;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
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
            background: #f8fafc;
            color: #1e4a76;
            padding: 15px 30px;
            border-radius: 50px;
            font-size: 20px;
            font-weight: 600;
            border: 1px solid #e2e8f0;
        }
        
        .points-badge i {
            color: #fbbf24;
            margin-right: 8px;
        }
        
        .checkin-btn {
            padding: 15px 40px;
            background: linear-gradient(135deg, #1e4a76 0%, #2c7da0 100%);
            border: none;
            border-radius: 50px;
            color: white;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .checkin-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(30, 74, 118, 0.3);
        }
        
        .checkin-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }
        
        .checkin-message {
            margin-top: 15px;
            color: #2c7da0;
            font-size: 16px;
        }
        
        /* FEATURES SECTION */
        .section-title {
            color: #1e4a76;
            font-size: 28px;
            margin: 40px 0 20px;
            border-left: 5px solid #2c7da0;
            padding-left: 15px;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }
        
        .feature-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.3s;
            cursor: pointer;
            opacity: 0.5;
            pointer-events: none;
            text-decoration: none;
            color: #1e293b;
            display: block;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
        }
        
        .feature-card.active {
            opacity: 1;
            pointer-events: all;
        }
        
        .feature-card.active:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }
        
        /* FEATURE CARD IMAGE SECTION */
        .feature-card-image {
            width: 100%;
            height: 160px;
            background-size: cover;
            background-position: center;
            position: relative;
        }
        
        .feature-card-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(to top, rgba(0,0,0,0.7), transparent);
            padding: 12px 15px;
        }
        
        .feature-card-icon {
            font-size: 28px;
            color: white;
            margin-bottom: 5px;
            display: inline-block;
            background: rgba(0,0,0,0.5);
            padding: 8px;
            border-radius: 12px;
        }
        
        .feature-card-title {
            color: white;
            font-size: 18px;
            font-weight: 600;
        }
        
        /* FEATURE CARD CONTENT */
        .feature-card-content {
            padding: 20px;
        }
        
        .feature-card-description {
            color: #64748b;
            font-size: 14px;
            line-height: 1.5;
        }
        
        .back-btn {
            display: inline-block;
            margin-top: 40px;
            color: #1e4a76;
            text-decoration: none;
            font-size: 16px;
            padding: 12px 25px;
            background: white;
            border-radius: 30px;
            border: 1px solid #e2e8f0;
            transition: all 0.3s;
            font-weight: 500;
        }
        
        .back-btn:hover {
            background: #1e4a76;
            color: white;
            border-color: #1e4a76;
        }
        
        .back-btn i {
            margin-right: 8px;
        }
        
        /* MODAL STYLES */
        .modal {
            display: none;
            position: fixed;
            z-index: 10000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            backdrop-filter: blur(5px);
            overflow-y: auto;
        }

        .modal-content {
            background: white;
            margin: 50px auto;
            padding: 0;
            width: 90%;
            max-width: 800px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
            border: 1px solid #e2e8f0;
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
            background: linear-gradient(135deg, #1e4a76 0%, #2c7da0 100%);
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

        .close-btn {
            color: rgba(255,255,255,0.8);
            font-size: 30px;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.3s;
        }

        .close-btn:hover {
            color: #ef4444;
        }

        .modal-body {
            padding: 30px;
            background: white;
            border-radius: 0 0 20px 20px;
        }
        
        /* Schedule Table */
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
            color: #1e4a76;
            font-weight: 600;
        }
        
        .schedule-table tr:hover {
            background: #f8fafc;
        }
        
        /* Tracking Grid inside Modal */
        .tracking-modal-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .track-modal-card {
            background: #f8fafc;
            border-radius: 15px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }
        
        .track-modal-card h3 {
            text-align: center;
            padding: 12px;
            margin: 0;
            color: white;
            background: linear-gradient(135deg, #1e4a76 0%, #2c7da0 100%);
        }
        
        .track-modal-map {
            height: 180px;
        }
        
        .track-modal-map iframe {
            width: 100%;
            height: 100%;
            border: none;
        }
        
        .track-modal-buttons {
            display: flex;
            gap: 10px;
            padding: 12px;
        }
        
        .track-modal-buttons button {
            flex: 1;
            padding: 8px;
            border: none;
            border-radius: 8px;
            background: linear-gradient(135deg, #1e4a76 0%, #2c7da0 100%);
            color: white;
            cursor: pointer;
            font-weight: 500;
        }
        
        .track-modal-status {
            padding: 10px;
            font-size: 12px;
            color: #64748b;
            text-align: center;
            border-top: 1px solid #e2e8f0;
            background: white;
        }
        
        /* Pass Card in Modal */
        .pass-card-modal {
            background: #f8fafc;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 15px;
            border: 1px solid #e2e8f0;
        }
        
        .pass-header-modal {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .pass-route-modal {
            font-weight: 600;
            color: #1e293b;
        }
        
        .pass-status-modal {
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .status-Active {
            background: #10b981;
            color: white;
        }
        
        .status-Expired {
            background: #ef4444;
            color: white;
        }
        
        .routes-list-modal {
            margin-top: 20px;
        }
        
        .route-item-modal {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .route-item-modal:last-child {
            border-bottom: none;
        }
        
        .buy-btn-modal {
            padding: 8px 20px;
            background: linear-gradient(135deg, #1e4a76 0%, #2c7da0 100%);
            border: none;
            border-radius: 25px;
            color: white;
            cursor: pointer;
            font-weight: 500;
        }
        
        .buy-btn-modal:disabled {
            opacity: 0.5;
            cursor: not-allowed;
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
			.pending-animation {
				animation: pulse 1.5s infinite;
			}

			@keyframes pulse {
				0%, 100% { opacity: 1; }
				50% { opacity: 0.7; }
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

<div class="container">
    
    <div class="facility-header">
        <?php
        $icon = 'fa-building';
        if($facility['Type'] == 'Gym') $icon = 'fa-dumbbell';
        else if($facility['Type'] == 'Library') $icon = 'fa-book';
        else if($facility['Type'] == 'Café') $icon = 'fa-mug-saucer';
        else if($facility['Type'] == 'GameField') $icon = 'fa-futbol';
        else if($facility['Type'] == 'Transport') $icon = 'fa-bus';
        else if($facility['Type'] == 'Pool') $icon = 'fa-person-swimming';
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
    
    <h2 class="section-title">📍 Facility Features</h2>
    <div class="features-grid">
        
        <!-- ========== CAFÉ FEATURES (unchanged) ========== -->
        <?php if($facility['Type'] == 'Café'): ?>
        <a href="cafe_menu.php?id=<?php echo $facility_id; ?>" class="feature-card <?php echo $already_checked_in ? 'active' : ''; ?>">
            <div class="feature-card-image" style="background-image: url('<?php echo $feature_images['menu']; ?>');">
                <div class="feature-card-overlay">
                    <div class="feature-card-icon"><i class="fa-solid fa-mug-saucer"></i></div>
                    <div class="feature-card-title">View Menu</div>
                </div>
            </div>
            <div class="feature-card-content">
                <div class="feature-card-description">Browse our full menu with prices, photos, and availability.</div>
            </div>
        </a>
        
        <a href="cafe_order.php?id=<?php echo $facility_id; ?>" class="feature-card <?php echo $already_checked_in ? 'active' : ''; ?>">
            <div class="feature-card-image" style="background-image: url('<?php echo $feature_images['order']; ?>');">
                <div class="feature-card-overlay">
                    <div class="feature-card-icon"><i class="fa-solid fa-cart-shopping"></i></div>
                    <div class="feature-card-title">Order Food</div>
                </div>
            </div>
            <div class="feature-card-content">
                <div class="feature-card-description">Place orders for pickup. Pay with cash or points!</div>
            </div>
        </a>
        
        <a href="special_offers.php?id=<?php echo $facility_id; ?>" class="feature-card <?php echo $already_checked_in ? 'active' : ''; ?>">
            <div class="feature-card-image" style="background-image: url('<?php echo $feature_images['offer']; ?>');">
                <div class="feature-card-overlay">
                    <div class="feature-card-icon"><i class="fa-solid fa-tag"></i></div>
                    <div class="feature-card-title">Special Offers</div>
                </div>
            </div>
            <div class="feature-card-content">
                <div class="feature-card-description">Check daily specials and combo offers.</div>
            </div>
        </a>
        
        <a href="reserve_table.php?id=<?php echo $facility_id; ?>" class="feature-card <?php echo $already_checked_in ? 'active' : ''; ?>">
            <div class="feature-card-image" style="background-image: url('<?php echo $feature_images['reserve']; ?>');">
                <div class="feature-card-overlay">
                    <div class="feature-card-icon"><i class="fa-solid fa-chair"></i></div>
                    <div class="feature-card-title">Reserve Table</div>
                </div>
            </div>
            <div class="feature-card-content">
                <div class="feature-card-description">Reserve a table during peak hours.</div>
            </div>
        </a>
        
        <!-- ========== LIBRARY FEATURES (unchanged) ========== -->
        <?php elseif($facility['Type'] == 'Library'): ?>
        <a href="library_books.php?id=<?php echo $facility_id; ?>&tab=browse" class="feature-card <?php echo $already_checked_in ? 'active' : ''; ?>">
            <div class="feature-card-image" style="background-image: url('<?php echo $feature_images['books']; ?>');">
                <div class="feature-card-overlay">
                    <div class="feature-card-icon"><i class="fa-solid fa-magnifying-glass"></i></div>
                    <div class="feature-card-title">Browse Books</div>
                </div>
            </div>
            <div class="feature-card-content">
                <div class="feature-card-description">Search and browse available books in the library catalog.</div>
            </div>
        </a>
        
        <a href="library_books.php?id=<?php echo $facility_id; ?>&tab=borrowed" class="feature-card <?php echo $already_checked_in ? 'active' : ''; ?>">
            <div class="feature-card-image" style="background-image: url('<?php echo $feature_images['borrowed']; ?>');">
                <div class="feature-card-overlay">
                    <div class="feature-card-icon"><i class="fa-solid fa-book"></i></div>
                    <div class="feature-card-title">My Borrowed Books</div>
                </div>
            </div>
            <div class="feature-card-content">
                <div class="feature-card-description">View books you've borrowed, check due dates.</div>
            </div>
        </a>
        
        <div class="feature-card <?php echo $already_checked_in ? 'active' : ''; ?>" onclick="openStudyRoomsModal()">
            <div class="feature-card-image" style="background-image: url('<?php echo $feature_images['study']; ?>');">
                <div class="feature-card-overlay">
                    <div class="feature-card-icon"><i class="fa-solid fa-door-open"></i></div>
                    <div class="feature-card-title">Study Rooms</div>
                </div>
            </div>
            <div class="feature-card-content">
                <div class="feature-card-description">Reserve study rooms for group work.</div>
            </div>
        </div>
        
        <div class="feature-card <?php echo $already_checked_in ? 'active' : ''; ?>" onclick="openPrintServicesModal()">
            <div class="feature-card-image" style="background-image: url('<?php echo $feature_images['print']; ?>');">
                <div class="feature-card-overlay">
                    <div class="feature-card-icon"><i class="fa-solid fa-print"></i></div>
                    <div class="feature-card-title">Print Services</div>
                </div>
            </div>
            <div class="feature-card-content">
                <div class="feature-card-description">Print, scan, and photocopy documents.</div>
            </div>
        </div>
        
        <!-- ========== GYM FEATURES (unchanged) ========== -->
        <?php elseif($facility['Type'] == 'Gym'): ?>
        <a href="gym_equipment.php?id=<?php echo $facility_id; ?>" class="feature-card <?php echo $already_checked_in ? 'active' : ''; ?>">
            <div class="feature-card-image" style="background-image: url('<?php echo $feature_images['gym_equipment']; ?>');">
                <div class="feature-card-overlay">
                    <div class="feature-card-icon"><i class="fa-solid fa-dumbbell"></i></div>
                    <div class="feature-card-title">Gym Equipment</div>
                </div>
            </div>
            <div class="feature-card-content">
                <div class="feature-card-description">View all available gym equipment.</div>
            </div>
        </a>
        
        <a href="gym_equipment.php?id=<?php echo $facility_id; ?>&tab=classes" class="feature-card <?php echo $already_checked_in ? 'active' : ''; ?>">
            <div class="feature-card-image" style="background-image: url('<?php echo $feature_images['fitness']; ?>');">
                <div class="feature-card-overlay">
                    <div class="feature-card-icon"><i class="fa-solid fa-people-group"></i></div>
                    <div class="feature-card-title">Fitness Classes</div>
                </div>
            </div>
            <div class="feature-card-content">
                <div class="feature-card-description">Join yoga, zumba, HIIT, and strength training classes.</div>
            </div>
        </a>
        
        <div class="feature-card <?php echo $already_checked_in ? 'active' : ''; ?>" onclick="openTrainerModal()">
            <div class="feature-card-image" style="background-image: url('<?php echo $feature_images['trainer']; ?>');">
                <div class="feature-card-overlay">
                    <div class="feature-card-icon"><i class="fa-solid fa-user"></i></div>
                    <div class="feature-card-title">Personal Trainer</div>
                </div>
            </div>
            <div class="feature-card-content">
                <div class="feature-card-description">Book a personal trainer session.</div>
            </div>
        </div>
        
        <div class="feature-card <?php echo $already_checked_in ? 'active' : ''; ?>" onclick="openLockerModal()">
            <div class="feature-card-image" style="background-image: url('<?php echo $feature_images['locker']; ?>');">
                <div class="feature-card-overlay">
                    <div class="feature-card-icon"><i class="fa-solid fa-locker"></i></div>
                    <div class="feature-card-title">Locker Room</div>
                </div>
            </div>
            <div class="feature-card-content">
                <div class="feature-card-description">Access locker rooms with showers and changing facilities.</div>
            </div>
        </div>
        
        <!-- ========== GAME FIELD FEATURES (unchanged) ========== -->
        <?php elseif($facility['Type'] == 'GameField'): ?>
        <a href="game_field.php?id=<?php echo $facility_id; ?>" class="feature-card <?php echo $already_checked_in ? 'active' : ''; ?>">
            <div class="feature-card-image" style="background-image: url('<?php echo $feature_images['field']; ?>');">
                <div class="feature-card-overlay">
                    <div class="feature-card-icon"><i class="fa-solid fa-calendar-check"></i></div>
                    <div class="feature-card-title">Book Field</div>
                </div>
            </div>
            <div class="feature-card-content">
                <div class="feature-card-description">Reserve sports fields for matches.</div>
            </div>
        </a>
        
        <div class="feature-card <?php echo $already_checked_in ? 'active' : ''; ?>" onclick="featureAction('Sports Equipment')">
            <div class="feature-card-image" style="background-image: url('<?php echo $feature_images['sports_equipment']; ?>');">
                <div class="feature-card-overlay">
                    <div class="feature-card-icon"><i class="fa-solid fa-futbol"></i></div>
                    <div class="feature-card-title">Sports Equipment</div>
                </div>
            </div>
            <div class="feature-card-content">
                <div class="feature-card-description">Borrow sports equipment.</div>
            </div>
        </div>
        
        <div class="feature-card <?php echo $already_checked_in ? 'active' : ''; ?>" onclick="featureAction('Tournaments')">
            <div class="feature-card-image" style="background-image: url('<?php echo $feature_images['tournament']; ?>');">
                <div class="feature-card-overlay">
                    <div class="feature-card-icon"><i class="fa-solid fa-trophy"></i></div>
                    <div class="feature-card-title">Tournaments</div>
                </div>
            </div>
            <div class="feature-card-content">
                <div class="feature-card-description">Join upcoming tournaments and win prizes!</div>
            </div>
        </div>
        
        <div class="feature-card <?php echo $already_checked_in ? 'active' : ''; ?>" onclick="featureAction('Score Board')">
            <div class="feature-card-image" style="background-image: url('<?php echo $feature_images['scoreboard']; ?>');">
                <div class="feature-card-overlay">
                    <div class="feature-card-icon"><i class="fa-solid fa-chart-simple"></i></div>
                    <div class="feature-card-title">Score Board</div>
                </div>
            </div>
            <div class="feature-card-content">
                <div class="feature-card-description">View live scores and match schedules.</div>
            </div>
        </div>
        
        <!-- ========== TRANSPORT FEATURES (UPDATED WITH MODALS) ========== -->
        <?php elseif($facility['Type'] == 'Transport'): ?>
        <!-- View Routes Card -->
        <div class="feature-card <?php echo $already_checked_in ? 'active' : ''; ?>" onclick="openRoutesModal()">
            <div class="feature-card-image" style="background-image: url('<?php echo $feature_images['routes']; ?>');">
                <div class="feature-card-overlay">
                    <div class="feature-card-icon"><i class="fa-solid fa-map"></i></div>
                    <div class="feature-card-title">View Routes</div>
                </div>
            </div>
            <div class="feature-card-content">
                <div class="feature-card-description">View routes and schedules.</div>
            </div>
        </div>
        
        <!-- Buy Passes Card -->
        <div class="feature-card <?php echo $already_checked_in ? 'active' : ''; ?>" onclick="openPassesModal()">
            <div class="feature-card-image" style="background-image: url('<?php echo $feature_images['ticket']; ?>');">
                <div class="feature-card-overlay">
                    <div class="feature-card-icon"><i class="fa-solid fa-ticket"></i></div>
                    <div class="feature-card-title">Buy Passes</div>
                </div>
            </div>
            <div class="feature-card-content">
                <div class="feature-card-description">Purchase transport passes using points.</div>
            </div>
        </div>
        
        <!-- Live Tracking Card -->
        <div class="feature-card <?php echo $already_checked_in ? 'active' : ''; ?>" onclick="openTrackingModal()">
            <div class="feature-card-image" style="background-image: url('<?php echo $feature_images['tracking']; ?>');">
                <div class="feature-card-overlay">
                    <div class="feature-card-icon"><i class="fa-solid fa-location-dot"></i></div>
                    <div class="feature-card-title">Live Tracking</div>
                </div>
            </div>
            <div class="feature-card-content">
                <div class="feature-card-description">Track buses in real-time on the map.</div>
            </div>
        </div>
        
        <!-- Time Table Card -->
        <div class="feature-card <?php echo $already_checked_in ? 'active' : ''; ?>" onclick="openScheduleModal()">
            <div class="feature-card-image" style="background-image: url('<?php echo $feature_images['schedule']; ?>');">
                <div class="feature-card-overlay">
                    <div class="feature-card-icon"><i class="fa-regular fa-clock"></i></div>
                    <div class="feature-card-title">Time Table</div>
                </div>
            </div>
            <div class="feature-card-content">
                <div class="feature-card-description">View complete bus schedule for all routes.</div>
            </div>
        </div>

        <!-- ========== POOL FEATURES (unchanged) ========== -->
        <?php elseif($facility['Type'] == 'Pool'): ?>
        <a href="pool.php?id=<?php echo $facility_id; ?>" class="feature-card <?php echo $already_checked_in ? 'active' : ''; ?>">
            <div class="feature-card-image" style="background-image: url('<?php echo $feature_images['pool']; ?>');">
                <div class="feature-card-overlay">
                    <div class="feature-card-icon"><i class="fa-solid fa-person-swimming"></i></div>
                    <div class="feature-card-title">Pool Dashboard</div>
                </div>
            </div>
            <div class="feature-card-content">
                <div class="feature-card-description">View lane availability and pool status.</div>
            </div>
        </a>

        <a href="pool_booking.php?id=<?php echo $facility_id; ?>" class="feature-card <?php echo $already_checked_in ? 'active' : ''; ?>">
            <div class="feature-card-image" style="background-image: url('<?php echo $feature_images['lane']; ?>');">
                <div class="feature-card-overlay">
                    <div class="feature-card-icon"><i class="fa-solid fa-calendar-check"></i></div>
                    <div class="feature-card-title">Book a Lane</div>
                </div>
            </div>
            <div class="feature-card-content">
                <div class="feature-card-description">Reserve a swimming lane for your workout.</div>
            </div>
        </a>

        <div class="feature-card <?php echo $already_checked_in ? 'active' : ''; ?>" onclick="openSwimLessonsModal()">
            <div class="feature-card-image" style="background-image: url('<?php echo $feature_images['swim_lesson']; ?>');">
                <div class="feature-card-overlay">
                    <div class="feature-card-icon"><i class="fa-solid fa-chalkboard-user"></i></div>
                    <div class="feature-card-title">Swim Lessons</div>
                </div>
            </div>
            <div class="feature-card-content">
                <div class="feature-card-description">Join swimming lessons for all skill levels.</div>
            </div>
        </div>
        
        <?php endif; ?>
    </div>
    
    <a href="facilities.php" class="back-btn">
        <i class="fa-solid fa-arrow-left"></i> Back to Facilities
    </a>
</div>

<!-- ==================== MODALS FOR TRANSPORT ONLY ==================== -->

<!-- MODAL 1: View Routes (CINEC Bus Schedule) -->
<div id="routesModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fa-solid fa-bus"></i> CINEC Bus Schedule</h2>
            <span class="close-btn" onclick="closeRoutesModal()">&times;</span>
        </div>
        <div class="modal-body">
            <?php if(mysqli_num_rows($campus_transport_result) > 0): ?>
            <table class="schedule-table">
                <thead>
                    <tr>
                        <th>From</th>
                        <th>To</th>
                        <th>Departure Time</th>
                        <th>Frequency</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    mysqli_data_seek($campus_transport_result, 0);
                    while($route = mysqli_fetch_assoc($campus_transport_result)): 
                    ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($route['from_campus']); ?></strong></td>
                        <td><?php echo htmlspecialchars($route['to_campus']); ?></td>
                        <td><?php echo htmlspecialchars($route['next_departure']); ?></td>
                        <td><?php echo htmlspecialchars($route['frequency']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                    <tr style="background: #e0f2fe;">
                        <td><strong>Note:</strong></td>
                        <td colspan="3">All buses depart from CINEC at 5:05 PM</td>
                    </tr>
                </tbody>
            </table>
            <?php else: ?>
            <p style="text-align: center; color: #64748b;">No bus schedules available</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- MODAL 2: Buy Passes (My Passes + Available Passes) -->
<!-- MODAL 2: Request Passes (My Passes + Available Routes with Admin Approval) -->
<div id="passesModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fa-solid fa-ticket"></i> My Transport Passes</h2>
            <span class="close-btn" onclick="closePassesModal()">&times;</span>
        </div>
        <div class="modal-body">
            <div id="modalPassesContainer">
                <?php 
                mysqli_data_seek($passes_result, 0);
                if(mysqli_num_rows($passes_result) > 0): 
                    while($pass = mysqli_fetch_assoc($passes_result)):
                ?>
                <div class="pass-card-modal">
                    <div class="pass-header-modal">
                        <span class="pass-route-modal"><?php echo htmlspecialchars($pass['RouteName']); ?></span>
                        <span class="pass-status-modal status-<?php echo $pass['Status']; ?> <?php echo $pass['Status'] == 'Pending' ? 'pending-animation' : ''; ?>">
                            <?php echo $pass['Status']; ?>
                            <?php if($pass['Status'] == 'Pending'): ?>
                                <i class="fa-regular fa-clock"></i>
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="pass-details">
                        <?php if($pass['Status'] == 'Pending'): ?>
                            <i class="fa-regular fa-hourglass-half"></i> Awaiting Admin Approval<br>
                            <i class="fa-regular fa-calendar"></i> Requested: <?php echo date('M d, Y', strtotime($pass['IssuedAt'])); ?>
                        <?php else: ?>
                            <i class="fa-regular fa-calendar"></i> Valid Until: <?php echo date('M d, Y', strtotime($pass['ValidUntil'])); ?><br>
                            <i class="fa-regular fa-clock"></i> Issued: <?php echo date('M d, Y', strtotime($pass['IssuedAt'])); ?>
                        <?php endif; ?>
                    </div>
                </div>
                <?php 
                    endwhile;
                else: 
                ?>
                <div style="text-align: center; padding: 20px; color: #64748b;">
                    <i class="fa-solid fa-ticket"></i> No transport passes yet.<br>
                    <small>Request a pass from the list below!</small>
                </div>
                <?php endif; ?>
            </div>
            
            <h3 style="color: #1e4a76; margin: 30px 0 15px; font-size: 18px;">📦 Available Routes - Request Pass</h3>
            <div class="routes-list-modal">
                <?php foreach($route_details as $route_id => $route): 
                    // Check if user has pending request for this route
                    $pending_check_sql = "SELECT COUNT(*) as count FROM TransportPasses WHERE UserID = ? AND RouteName = ? AND Status = 'Pending'";
                    $pending_stmt = mysqli_prepare($conn, $pending_check_sql);
                    mysqli_stmt_bind_param($pending_stmt, "is", $user_id, $route['name']);
                    mysqli_stmt_execute($pending_stmt);
                    $pending_result = mysqli_stmt_get_result($pending_stmt);
                    $pending_data = mysqli_fetch_assoc($pending_result);
                    $has_pending = $pending_data['count'] > 0;
                    
                    $can_request = ($user['PointsBalance'] >= $route['price']) && !$has_pending;
                ?>
                <div class="route-item-modal">
                    <div>
                        <div class="route-name" style="font-weight: 600;"><?php echo $route['name']; ?></div>
                        <div style="font-size: 12px; color:#64748b;"><?php echo $route['frequency']; ?></div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <div style="color: #2c7da0; font-weight: 700;"><?php echo $route['price']; ?> pts</div>
                        <button class="buy-btn-modal <?php echo $has_pending ? 'pending' : ''; ?>" 
                                onclick="requestPassFromModal('<?php echo $route['name']; ?>', <?php echo $route['price']; ?>)"
                                <?php echo (!$can_request && !$has_pending) ? 'disabled' : ''; ?>
                                style="<?php echo $has_pending ? 'background: #f59e0b;' : ''; ?>">
                            <?php if($has_pending): ?>
                                <i class="fa-regular fa-clock"></i> Pending
                            <?php elseif($user['PointsBalance'] < $route['price']): ?>
                                <i class="fa-solid fa-star"></i> Insufficient
                            <?php else: ?>
                                <i class="fa-solid fa-paper-plane"></i> Request Pass
                            <?php endif; ?>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div style="margin-top: 20px; padding: 12px; background: #f0fdf4; border-radius: 12px; font-size: 13px; color: #065f46;">
                <i class="fa-solid fa-info-circle"></i> 
                <strong>Note:</strong> Pass requests require admin approval. Points will be deducted only after approval. You'll be notified once approved!
            </div>
        </div>
    </div>
</div>

<!-- MODAL 3: Live Tracking -->
<div id="trackingModal" class="modal">
    <div class="modal-content" style="max-width: 1000px;">
        <div class="modal-header">
            <h2><i class="fa-solid fa-location-dot"></i> Live Bus Tracking</h2>
            <span class="close-btn" onclick="closeTrackingModal()">&times;</span>
        </div>
        <div class="modal-body">
            <div class="tracking-modal-grid">
                <?php 
                mysqli_data_seek($bus_routes_result, 0);
                if(mysqli_num_rows($bus_routes_result) > 0):
                    while($bus = mysqli_fetch_assoc($bus_routes_result)):
                        $route_id = $bus['route_id'];
                        $display_name = $route_details[$route_id]['name'] ?? ucfirst($route_id);
                ?>
                <div class="track-modal-card" id="track-<?php echo $route_id; ?>">
                    <h3><?php echo $display_name; ?></h3>
                    <div class="track-modal-map">
                        <iframe id="modal-map-<?php echo $route_id; ?>"
                            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3960.783515024766!2d79.97036937587595!3d6.916460618471185!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3ae256db1a677131%3A0x2c6145384bc19bc8!2sCINEC%20Campus!5e0!3m2!1sen!2slk!4v1700000000000" 
                            allowfullscreen="" loading="lazy">
                        </iframe>
                    </div>
                    <div class="track-modal-buttons">
                        <button onclick="viewLocationModal('<?php echo $route_id; ?>')">
                            <i class="fa-solid fa-eye"></i> View
                        </button>
                        <button onclick="updateLocationModal('<?php echo $route_id; ?>')">
                            <i class="fa-solid fa-pen"></i> Update
                        </button>
                    </div>
                    <div class="track-modal-status" id="modal-status-<?php echo $route_id; ?>">
                        <?php if($bus['location']): ?>
                            <strong>Last Location:</strong> <?php echo $bus['location']; ?><br>
                            <strong>Updated at:</strong> <?php echo $bus['updated_time']; ?>
                        <?php else: ?>
                            No updates yet
                        <?php endif; ?>
                    </div>
                </div>
                <?php 
                    endwhile;
                else:
                ?>
                <p style="text-align: center; color: #64748b;">No tracking data available</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- MODAL 4: Time Table (Complete Schedule) -->
<div id="scheduleModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fa-regular fa-clock"></i> Complete Bus Schedule</h2>
            <span class="close-btn" onclick="closeScheduleModal()">&times;</span>
        </div>
        <div class="modal-body">
            <table class="schedule-table">
                <thead>
                    <tr>
                        <th>Route</th>
                        <th>From</th>
                        <th>To</th>
                        <th>Departure Time</th>
                        <th>Frequency</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    mysqli_data_seek($campus_transport_result, 0);
                    if(mysqli_num_rows($campus_transport_result) > 0):
                        while($route = mysqli_fetch_assoc($campus_transport_result)): 
                    ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($route['from_campus']); ?> → <?php echo htmlspecialchars($route['to_campus']); ?></strong></td>
                        <td><?php echo htmlspecialchars($route['from_campus']); ?></td>
                        <td><?php echo htmlspecialchars($route['to_campus']); ?></td>
                        <td><?php echo htmlspecialchars($route['next_departure']); ?></td>
                        <td><?php echo htmlspecialchars($route['frequency']); ?></td>
                    </tr>
                    <?php 
                        endwhile;
                    endif;
                    ?>
                    <?php foreach($route_details as $route_id => $route): ?>
                    <tr>
                        <td><strong><?php echo $route['name']; ?></strong></td>
                        <td>CINEC</td>
                        <td><?php echo $route['name']; ?></td>
                        <td>5:05 PM</td>
                        <td><?php echo $route['frequency']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div style="margin-top: 20px; padding: 15px; background: #e0f2fe; border-radius: 12px;">
                <i class="fa-solid fa-circle-info" style="color: #2c7da0;"></i>
                <strong>Note:</strong> All buses depart from CINEC at 5:05 PM. Times may vary during holidays.
            </div>
        </div>
    </div>
</div>

<!-- Other Modals (Study Rooms, Print Services, Trainer, Locker, Swim Lessons - unchanged) -->
<!-- Study Rooms Modal -->
<div id="studyRoomsModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fa-solid fa-door-open"></i> Study Rooms</h2>
            <span class="close-btn" onclick="closeStudyRoomsModal()">&times;</span>
        </div>
        <div class="modal-body">
            <div class="date-selector" style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 20px; border-radius: 12px; margin-bottom: 25px; display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
                <label style="color: #1e293b; font-size: 16px; display: flex; align-items: center; gap: 8px;"><i class="fa-regular fa-calendar" style="color: #2c7da0;"></i> Select Date:</label>
                <input type="date" id="bookingDate" class="date-input" value="<?php echo date('Y-m-d'); ?>" min="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d', strtotime('+7 days')); ?>" style="padding: 10px 15px; background: white; border: 1px solid #e2e8f0; border-radius: 10px; color: #1e293b; font-size: 14px; flex: 1; max-width: 200px;">
            </div>
            
            <div class="room-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                <div class="room-card available" id="room101" onclick="selectRoom(101)" style="background: white; border: 1px solid #e2e8f0; border-radius: 15px; padding: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); cursor: pointer; transition: all 0.3s;">
                    <div class="room-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                        <h3 style="color: #1e293b; font-size: 18px; display: flex; align-items: center; gap: 8px;"><i class="fa-regular fa-door-open" style="color: #2c7da0;"></i> Room 101</h3>
                        <span class="room-capacity" style="color: #2c7da0; font-size: 14px;"><i class="fa-solid fa-users"></i> 4 people</span>
                    </div>
                    <div class="room-features" style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 15px;">
                        <span class="feature" style="background: #e2e8f0; padding: 5px 10px; border-radius: 20px; color: #1e293b; font-size: 11px;"><i class="fa-solid fa-wifi" style="color: #2c7da0;"></i> WiFi</span>
                        <span class="feature" style="background: #e2e8f0; padding: 5px 10px; border-radius: 20px; color: #1e293b; font-size: 11px;"><i class="fa-solid fa-chalkboard" style="color: #2c7da0;"></i> Whiteboard</span>
                        <span class="feature" style="background: #e2e8f0; padding: 5px 10px; border-radius: 20px; color: #1e293b; font-size: 11px;"><i class="fa-solid fa-tv" style="color: #2c7da0;"></i> Projector</span>
                    </div>
                    <div class="room-status available" style="background: #e2e8f0; color: #2c7da0; border: 1px solid #2c7da0; font-size: 14px; margin-bottom: 15px; padding: 8px; border-radius: 8px; text-align: center;">
                        <i class="fa-regular fa-circle-check"></i> Available Now
                    </div>
                    <div class="time-slots" id="room101-slots" style="display: flex; flex-wrap: wrap; gap: 8px; margin: 15px 0;">
                        <div class="time-slot available" onclick="event.stopPropagation(); selectTimeSlot(101, '10:00 AM')" style="padding: 6px 10px; border-radius: 8px; font-size: 12px; font-weight: 500; cursor: pointer; flex: 1 0 auto; text-align: center; min-width: 70px; background: #e2e8f0; color: #2c7da0; border: 1px solid #2c7da0;">10:00 AM</div>
                        <div class="time-slot available" onclick="event.stopPropagation(); selectTimeSlot(101, '11:00 AM')" style="padding: 6px 10px; border-radius: 8px; font-size: 12px; font-weight: 500; cursor: pointer; flex: 1 0 auto; text-align: center; min-width: 70px; background: #e2e8f0; color: #2c7da0; border: 1px solid #2c7da0;">11:00 AM</div>
                        <div class="time-slot available" onclick="event.stopPropagation(); selectTimeSlot(101, '12:00 PM')" style="padding: 6px 10px; border-radius: 8px; font-size: 12px; font-weight: 500; cursor: pointer; flex: 1 0 auto; text-align: center; min-width: 70px; background: #e2e8f0; color: #2c7da0; border: 1px solid #2c7da0;">12:00 PM</div>
                        <div class="time-slot busy" onclick="event.stopPropagation();" style="padding: 6px 10px; border-radius: 8px; font-size: 12px; font-weight: 500; flex: 1 0 auto; text-align: center; min-width: 70px; background: #fee2e2; color: #ef4444; border: 1px dashed #ef4444; opacity: 0.5; cursor: not-allowed; text-decoration: line-through;">1:00 PM</div>
                        <div class="time-slot available" onclick="event.stopPropagation(); selectTimeSlot(101, '2:00 PM')" style="padding: 6px 10px; border-radius: 8px; font-size: 12px; font-weight: 500; cursor: pointer; flex: 1 0 auto; text-align: center; min-width: 70px; background: #e2e8f0; color: #2c7da0; border: 1px solid #2c7da0;">2:00 PM</div>
                        <div class="time-slot available" onclick="event.stopPropagation(); selectTimeSlot(101, '3:00 PM')" style="padding: 6px 10px; border-radius: 8px; font-size: 12px; font-weight: 500; cursor: pointer; flex: 1 0 auto; text-align: center; min-width: 70px; background: #e2e8f0; color: #2c7da0; border: 1px solid #2c7da0;">3:00 PM</div>
                    </div>
                    <button class="book-btn" id="bookBtn101" onclick="bookRoom(101)" disabled style="width: 100%; padding: 12px; background: linear-gradient(135deg, #1e4a76, #2c7da0); border: none; border-radius: 10px; color: white; font-weight: 600; cursor: pointer; transition: all 0.3s; opacity: 0.5;">
                        <i class="fa-regular fa-calendar-check"></i> Book Selected Slot
                    </button>
                    <small class="select-hint" style="display: block; color: #64748b; font-size: 11px; margin-top: 8px; text-align: center;">👆 Select a time slot first</small>
                </div>
                
                <div class="room-card available" id="room102" onclick="selectRoom(102)" style="background: white; border: 1px solid #e2e8f0; border-radius: 15px; padding: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); cursor: pointer; transition: all 0.3s;">
                    <div class="room-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                        <h3 style="color: #1e293b; font-size: 18px; display: flex; align-items: center; gap: 8px;"><i class="fa-regular fa-door-open" style="color: #2c7da0;"></i> Room 102</h3>
                        <span class="room-capacity" style="color: #2c7da0; font-size: 14px;"><i class="fa-solid fa-users"></i> 6 people</span>
                    </div>
                    <div class="room-features" style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 15px;">
                        <span class="feature" style="background: #e2e8f0; padding: 5px 10px; border-radius: 20px; color: #1e293b; font-size: 11px;"><i class="fa-solid fa-wifi" style="color: #2c7da0;"></i> WiFi</span>
                        <span class="feature" style="background: #e2e8f0; padding: 5px 10px; border-radius: 20px; color: #1e293b; font-size: 11px;"><i class="fa-solid fa-chalkboard" style="color: #2c7da0;"></i> Whiteboard</span>
                        <span class="feature" style="background: #e2e8f0; padding: 5px 10px; border-radius: 20px; color: #1e293b; font-size: 11px;"><i class="fa-solid fa-tv" style="color: #2c7da0;"></i> Projector</span>
                    </div>
                    <div class="room-status available" style="background: #e2e8f0; color: #2c7da0; border: 1px solid #2c7da0; font-size: 14px; margin-bottom: 15px; padding: 8px; border-radius: 8px; text-align: center;">
                        <i class="fa-regular fa-circle-check"></i> Available Now
                    </div>
                    <div class="time-slots" id="room102-slots" style="display: flex; flex-wrap: wrap; gap: 8px; margin: 15px 0;">
                        <div class="time-slot available" onclick="event.stopPropagation(); selectTimeSlot(102, '10:00 AM')" style="padding: 6px 10px; border-radius: 8px; font-size: 12px; font-weight: 500; cursor: pointer; flex: 1 0 auto; text-align: center; min-width: 70px; background: #e2e8f0; color: #2c7da0; border: 1px solid #2c7da0;">10:00 AM</div>
                        <div class="time-slot busy" onclick="event.stopPropagation();" style="padding: 6px 10px; border-radius: 8px; font-size: 12px; font-weight: 500; flex: 1 0 auto; text-align: center; min-width: 70px; background: #fee2e2; color: #ef4444; border: 1px dashed #ef4444; opacity: 0.5; cursor: not-allowed; text-decoration: line-through;">11:00 AM</div>
                        <div class="time-slot available" onclick="event.stopPropagation(); selectTimeSlot(102, '12:00 PM')" style="padding: 6px 10px; border-radius: 8px; font-size: 12px; font-weight: 500; cursor: pointer; flex: 1 0 auto; text-align: center; min-width: 70px; background: #e2e8f0; color: #2c7da0; border: 1px solid #2c7da0;">12:00 PM</div>
                        <div class="time-slot available" onclick="event.stopPropagation(); selectTimeSlot(102, '1:00 PM')" style="padding: 6px 10px; border-radius: 8px; font-size: 12px; font-weight: 500; cursor: pointer; flex: 1 0 auto; text-align: center; min-width: 70px; background: #e2e8f0; color: #2c7da0; border: 1px solid #2c7da0;">1:00 PM</div>
                        <div class="time-slot available" onclick="event.stopPropagation(); selectTimeSlot(102, '2:00 PM')" style="padding: 6px 10px; border-radius: 8px; font-size: 12px; font-weight: 500; cursor: pointer; flex: 1 0 auto; text-align: center; min-width: 70px; background: #e2e8f0; color: #2c7da0; border: 1px solid #2c7da0;">2:00 PM</div>
                        <div class="time-slot busy" onclick="event.stopPropagation();" style="padding: 6px 10px; border-radius: 8px; font-size: 12px; font-weight: 500; flex: 1 0 auto; text-align: center; min-width: 70px; background: #fee2e2; color: #ef4444; border: 1px dashed #ef4444; opacity: 0.5; cursor: not-allowed; text-decoration: line-through;">3:00 PM</div>
                    </div>
                    <button class="book-btn" id="bookBtn102" onclick="bookRoom(102)" disabled style="width: 100%; padding: 12px; background: linear-gradient(135deg, #1e4a76, #2c7da0); border: none; border-radius: 10px; color: white; font-weight: 600; cursor: pointer; transition: all 0.3s; opacity: 0.5;">
                        <i class="fa-regular fa-calendar-check"></i> Book Selected Slot
                    </button>
                    <small class="select-hint" style="display: block; color: #64748b; font-size: 11px; margin-top: 8px; text-align: center;">👆 Select a time slot first</small>
                </div>
            </div>
            
            <div class="booking-summary" id="bookingSummary" style="display: none; margin-top: 30px; background: #f8fafc; border-radius: 15px; padding: 25px; border: 1px solid #2c7da0;">
                <h3 style="color: #1e293b; font-size: 18px; margin-bottom: 15px; display: flex; align-items: center; gap: 8px;"><i class="fa-regular fa-rectangle-list" style="color: #2c7da0;"></i> Booking Summary</h3>
                <div class="summary-details" style="background: white; border-radius: 12px; padding: 15px; margin-bottom: 20px; border: 1px solid #e2e8f0;">
                    <p style="color: #1e293b; margin: 8px 0; display: flex; align-items: center; gap: 10px;"><i class="fa-regular fa-door-open" style="color: #2c7da0; width: 20px;"></i> <strong>Room:</strong> <span id="summaryRoom"></span></p>
                    <p style="color: #1e293b; margin: 8px 0; display: flex; align-items: center; gap: 10px;"><i class="fa-regular fa-calendar" style="color: #2c7da0; width: 20px;"></i> <strong>Date:</strong> <span id="summaryDate"></span></p>
                    <p style="color: #1e293b; margin: 8px 0; display: flex; align-items: center; gap: 10px;"><i class="fa-regular fa-clock" style="color: #2c7da0; width: 20px;"></i> <strong>Time:</strong> <span id="summaryTime"></span></p>
                    <p style="color: #1e293b; margin: 8px 0; display: flex; align-items: center; gap: 10px;"><i class="fa-regular fa-star" style="color: #2c7da0; width: 20px;"></i> <strong>Points:</strong> 10 points (refundable)</p>
                </div>
                <div class="summary-actions" style="display: flex; gap: 15px; justify-content: flex-end;">
                    <button class="confirm-btn" onclick="confirmBooking()" style="padding: 12px 25px; background: linear-gradient(135deg, #1e4a76, #2c7da0); border: none; border-radius: 10px; color: white; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px;">
                        <i class="fa-regular fa-circle-check"></i> Confirm Booking
                    </button>
                    <button class="cancel-btn" onclick="cancelSelection()" style="padding: 12px 25px; background: white; border: 1px solid #e2e8f0; border-radius: 10px; color: #1e293b; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px;">
                        <i class="fa-regular fa-circle-xmark"></i> Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Print Services Modal -->
<div id="printServicesModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fa-solid fa-print"></i> Print Services</h2>
            <span class="close-btn" onclick="closePrintServicesModal()">&times;</span>
        </div>
        <div class="modal-body">
            <div class="print-options" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 30px;">
                <div class="print-card" id="printBW" onclick="selectPrintType('bw')" style="background: white; border: 1px solid #e2e8f0; border-radius: 15px; padding: 25px; text-align: center; box-shadow: 0 4px 12px rgba(0,0,0,0.05); cursor: pointer; transition: all 0.3s;">
                    <i class="fa-solid fa-file-lines" style="font-size: 40px; color: #2c7da0; margin-bottom: 15px;"></i>
                    <h3 style="color: #1e293b; font-size: 18px; margin-bottom: 10px;">Black & White</h3>
                    <div class="print-price" style="color: #2c7da0; font-size: 20px; font-weight: 700; margin-bottom: 10px;">5 <small style="font-size: 12px; color: #64748b;">points/page</small></div>
                    <div class="print-description" style="color: #64748b; font-size: 12px;">Single-sided black & white printing</div>
                </div>
                
                <div class="print-card" id="printColor" onclick="selectPrintType('color')" style="background: white; border: 1px solid #e2e8f0; border-radius: 15px; padding: 25px; text-align: center; box-shadow: 0 4px 12px rgba(0,0,0,0.05); cursor: pointer; transition: all 0.3s;">
                    <i class="fa-solid fa-file-image" style="font-size: 40px; color: #2c7da0; margin-bottom: 15px;"></i>
                    <h3 style="color: #1e293b; font-size: 18px; margin-bottom: 10px;">Color Print</h3>
                    <div class="print-price" style="color: #2c7da0; font-size: 20px; font-weight: 700; margin-bottom: 10px;">10 <small style="font-size: 12px; color: #64748b;">points/page</small></div>
                    <div class="print-description" style="color: #64748b; font-size: 12px;">Full color printing, high quality</div>
                </div>
                
                <div class="print-card" id="printCopy" onclick="selectPrintType('copy')" style="background: white; border: 1px solid #e2e8f0; border-radius: 15px; padding: 25px; text-align: center; box-shadow: 0 4px 12px rgba(0,0,0,0.05); cursor: pointer; transition: all 0.3s;">
                    <i class="fa-solid fa-copy" style="font-size: 40px; color: #2c7da0; margin-bottom: 15px;"></i>
                    <h3 style="color: #1e293b; font-size: 18px; margin-bottom: 10px;">Photocopy</h3>
                    <div class="print-price" style="color: #2c7da0; font-size: 20px; font-weight: 700; margin-bottom: 10px;">3 <small style="font-size: 12px; color: #64748b;">points/page</small></div>
                    <div class="print-description" style="color: #64748b; font-size: 12px;">Black & white photocopying</div>
                </div>
                
                <div class="print-card" id="printScan" onclick="selectPrintType('scan')" style="background: white; border: 1px solid #e2e8f0; border-radius: 15px; padding: 25px; text-align: center; box-shadow: 0 4px 12px rgba(0,0,0,0.05); cursor: pointer; transition: all 0.3s;">
                    <i class="fa-solid fa-scanner" style="font-size: 40px; color: #2c7da0; margin-bottom: 15px;"></i>
                    <h3 style="color: #1e293b; font-size: 18px; margin-bottom: 10px;">Scan</h3>
                    <div class="print-price" style="color: #2c7da0; font-size: 20px; font-weight: 700; margin-bottom: 10px;">2 <small style="font-size: 12px; color: #64748b;">points/page</small></div>
                    <div class="print-description" style="color: #64748b; font-size: 12px;">Scan to PDF or image</div>
                </div>
            </div>
            
            <div class="print-info" style="background: #f8fafc; border-radius: 15px; padding: 25px; border: 1px solid #e2e8f0; margin-top: 20px;">
                <h4 style="color: #1e293b; font-size: 16px; margin-bottom: 15px; display: flex; align-items: center; gap: 8px;"><i class="fa-solid fa-cloud-arrow-up" style="color: #2c7da0;"></i> Upload Document</h4>
                <div class="file-upload-area" onclick="document.getElementById('fileUpload').click()" style="background: white; border: 2px dashed #e2e8f0; border-radius: 10px; padding: 30px; text-align: center; margin-bottom: 15px; cursor: pointer;">
                    <i class="fa-solid fa-cloud-upload-alt" style="font-size: 40px; color: #2c7da0; margin-bottom: 10px;"></i>
                    <p style="color: #1e293b; font-size: 14px;">Click to upload or drag and drop</p>
                    <small style="color: #64748b; font-size: 12px;">Supported: PDF, DOC, DOCX, JPG, PNG (Max 10MB)</small>
                    <div id="fileName" class="file-name" style="color: #2c7da0; font-size: 14px; margin-top: 10px;"></div>
                </div>
                <input type="file" id="fileUpload" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" onchange="updateFileName(this)" style="display: none;">
                
                <div style="margin-top: 20px;">
                    <label style="color: #1e293b; display: block; margin-bottom: 10px; font-weight: 600;"><i class="fa-regular fa-file" style="color: #2c7da0;"></i> Number of pages:</label>
                    <input type="number" id="pageCount" min="1" max="100" value="1" style="width: 100px; padding: 10px; border-radius: 8px; background: white; border: 1px solid #e2e8f0; color: #1e293b;">
                </div>
                
                <div id="printSummary" class="print-summary" style="display: none; background: #e2e8f0; border-radius: 10px; padding: 15px; margin-top: 20px; border: 1px solid #2c7da0;">
                    <p style="color: #1e293b; display: flex; align-items: center; gap: 10px; font-weight: 600;"><i class="fa-regular fa-circle-check" style="color: #2c7da0;"></i> <span id="printSummaryText"></span></p>
                </div>
                
                <div class="print-actions" style="display: flex; gap: 15px; justify-content: flex-end; margin-top: 20px;">
                    <button class="print-action-btn" id="printSubmitBtn" onclick="submitPrintJob()" disabled style="padding: 12px 30px; background: linear-gradient(135deg, #1e4a76, #2c7da0); border: none; border-radius: 10px; color: white; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px; opacity: 0.5;">
                        <i class="fa-regular fa-print"></i> Submit Print Job
                    </button>
                    <button class="cancel-btn" onclick="closePrintServicesModal()" style="padding: 12px 25px; background: white; border: 1px solid #e2e8f0; border-radius: 10px; color: #1e293b; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px;">
                        <i class="fa-regular fa-circle-xmark"></i> Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Personal Trainer Modal -->
<div id="personalTrainerModal" class="modal">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h2><i class="fa-solid fa-user-plus"></i> Book a Personal Trainer</h2>
            <span class="close-btn" onclick="closeTrainerModal()">&times;</span>
        </div>
        <div class="modal-body">
            <div style="margin-bottom: 20px;">
                <p style="color: #64748b; font-size: 14px; margin-bottom: 15px;">
                    <i class="fa-solid fa-info-circle" style="color: #2c7da0;"></i> Get personalized workout plans and guidance
                </p>
                <div style="background: #f8fafc; border-radius: 12px; padding: 15px; margin-bottom: 20px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span><i class="fa-solid fa-user"></i> John Smith</span>
                        <span style="color: #2c7da0; font-weight: 600;">Strength Training</span>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span><i class="fa-solid fa-user"></i> Sarah Johnson</span>
                        <span style="color: #2c7da0; font-weight: 600;">Yoga & Flexibility</span>
                    </div>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-bottom: 20px;">
                <div>
                    <label style="display: block; margin-bottom: 8px;">Select Trainer</label>
                    <select id="trainerSelect" style="width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 10px;">
                        <option value="John Smith">John Smith - Strength Training</option>
                        <option value="Sarah Johnson">Sarah Johnson - Yoga & Flexibility</option>
                    </select>
                </div>
                <div>
                    <label style="display: block; margin-bottom: 8px;">Session Duration</label>
                    <select id="sessionSelect" style="width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 10px;">
                        <option value="30">30 min - 30 points</option>
                        <option value="60">60 min - 50 points</option>
                        <option value="90">90 min - 70 points</option>
                    </select>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                <div>
                    <label style="display: block; margin-bottom: 8px;">Date</label>
                    <input type="date" id="trainerDate" min="<?php echo date('Y-m-d'); ?>" style="width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 10px;">
                </div>
                <div>
                    <label style="display: block; margin-bottom: 8px;">Time</label>
                    <select id="trainerTime" style="width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 10px;">
                        <option>9:00 AM</option><option>10:00 AM</option><option>11:00 AM</option>
                        <option>12:00 PM</option><option>1:00 PM</option><option>2:00 PM</option>
                        <option>3:00 PM</option><option>4:00 PM</option><option>5:00 PM</option>
                    </select>
                </div>
            </div>
            
            <div id="trainerSummary" style="display: none; background: #e0f2fe; border-radius: 12px; padding: 15px; margin-bottom: 20px;">
                <div style="display: flex; justify-content: space-between;">
                    <span>Trainer: <strong id="summaryTrainerName">-</strong></span>
                    <span>Cost: <strong id="summaryPoints">-</strong> points</span>
                </div>
            </div>
            
            <div style="display: flex; gap: 15px;">
                <button onclick="confirmTrainerBooking()" id="confirmTrainerBtn" disabled style="flex: 1; padding: 15px; background: linear-gradient(135deg, #1e4a76, #2c7da0); border: none; border-radius: 10px; color: white; font-weight: 600; cursor: pointer; opacity: 0.5;">Confirm Booking</button>
                <button onclick="closeTrainerModal()" style="flex: 1; padding: 15px; background: white; border: 1px solid #e2e8f0; border-radius: 10px; color: #1e293b; font-weight: 600; cursor: pointer;">Cancel</button>
            </div>
        </div>
    </div>
</div>

<!-- Locker Room Modal -->
<div id="lockerRoomModal" class="modal">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header">
            <h2><i class="fa-solid fa-locker"></i> Locker Room Access</h2>
            <span class="close-btn" onclick="closeLockerModal()">&times;</span>
        </div>
        <div class="modal-body">
            <div style="margin-bottom: 20px;">
                <p style="color: #64748b; font-size: 14px; margin-bottom: 15px;">
                    <i class="fa-solid fa-info-circle" style="color: #2c7da0;"></i> Secure lockers available for the entire day
                </p>
                <div style="background: #f8fafc; border-radius: 12px; padding: 15px; margin-bottom: 20px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span><i class="fa-solid fa-cube"></i> Small Locker</span>
                        <span style="color: #2c7da0; font-weight: 600;">5 points</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span><i class="fa-solid fa-cubes"></i> Medium Locker</span>
                        <span style="color: #2c7da0; font-weight: 600;">10 points</span>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span><i class="fa-solid fa-warehouse"></i> Large Locker</span>
                        <span style="color: #2c7da0; font-weight: 600;">15 points</span>
                    </div>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 20px;">
                <div onclick="selectLocker('Small Locker', 5)" id="lockerSmall" style="text-align: center; padding: 15px; background: #f8fafc; border: 2px solid #e2e8f0; border-radius: 10px; cursor: pointer;">
                    <i class="fa-solid fa-cube" style="font-size: 28px; color: #2c7da0;"></i>
                    <div style="font-weight: 600; margin-top: 5px;">Small</div>
                    <div style="color: #2c7da0;">5 pts</div>
                </div>
                <div onclick="selectLocker('Medium Locker', 10)" id="lockerMedium" style="text-align: center; padding: 15px; background: #f8fafc; border: 2px solid #e2e8f0; border-radius: 10px; cursor: pointer;">
                    <i class="fa-solid fa-cubes" style="font-size: 28px; color: #2c7da0;"></i>
                    <div style="font-weight: 600; margin-top: 5px;">Medium</div>
                    <div style="color: #2c7da0;">10 pts</div>
                </div>
                <div onclick="selectLocker('Large Locker', 15)" id="lockerLarge" style="text-align: center; padding: 15px; background: #f8fafc; border: 2px solid #e2e8f0; border-radius: 10px; cursor: pointer;">
                    <i class="fa-solid fa-warehouse" style="font-size: 28px; color: #2c7da0;"></i>
                    <div style="font-weight: 600; margin-top: 5px;">Large</div>
                    <div style="color: #2c7da0;">15 pts</div>
                </div>
            </div>
            
            <div id="lockerSummary" style="display: none; background: #e0f2fe; border-radius: 12px; padding: 15px; margin-bottom: 20px;">
                <div style="display: flex; justify-content: space-between;">
                    <span>Selected: <strong id="lockerNameDisplay"></strong></span>
                    <span>Cost: <strong id="lockerCostDisplay"></strong> points</span>
                </div>
            </div>
            
            <div style="display: flex; gap: 15px;">
                <button onclick="confirmLockerBooking()" id="confirmLockerBtn" disabled style="flex: 1; padding: 15px; background: linear-gradient(135deg, #1e4a76, #2c7da0); border: none; border-radius: 10px; color: white; font-weight: 600; cursor: pointer; opacity: 0.5;">Book Locker</button>
                <button onclick="closeLockerModal()" style="flex: 1; padding: 15px; background: white; border: 1px solid #e2e8f0; border-radius: 10px; color: #1e293b; font-weight: 600; cursor: pointer;">Cancel</button>
            </div>
        </div>
    </div>
</div>

<!-- Swim Lessons Modal -->
<div id="swimLessonsModal" class="modal">
    <div class="modal-content" style="max-width: 900px;">
        <div class="modal-header">
            <h2><i class="fa-solid fa-chalkboard-user"></i> Swimming Lessons</h2>
            <span class="close-btn" onclick="closeSwimLessonsModal()">&times;</span>
        </div>
        <div class="modal-body">
            <div style="background: linear-gradient(135deg, #e0f2fe, #bae6fd); border-radius: 15px; padding: 20px; margin-bottom: 25px; display: flex; align-items: center; gap: 15px;">
                <i class="fa-solid fa-circle-info" style="font-size: 40px; color: #2c7da0;"></i>
                <div>
                    <h3 style="color: #1e4a76; margin-bottom: 5px;">Professional Swimming Coaching</h3>
                    <p style="color: #475569;">Certified instructors with years of experience. Small class sizes for personalized attention.</p>
                </div>
            </div>
            
            <h3 style="color: #1e293b; font-size: 18px; margin-bottom: 15px; display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-chart-line" style="color: #2c7da0;"></i> Select Your Level
            </h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 15px; margin-bottom: 30px;">
                <div class="level-card" onclick="selectLevel('beginner', 200, '4 weeks', 8, 'Beginner')" id="levelBeginner" style="background: #f8fafc; border: 2px solid #e2e8f0; border-radius: 15px; padding: 20px; cursor: pointer; transition: all 0.3s;">
                    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                        <div style="width: 50px; height: 50px; background: linear-gradient(135deg, #10b981, #059669); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                            <i class="fa-solid fa-child" style="color: white; font-size: 22px;"></i>
                        </div>
                        <div>
                            <h4 style="color: #1e293b; font-size: 18px; font-weight: 700;">Beginner</h4>
                            <p style="color: #64748b; font-size: 12px;">Learn basic swimming skills</p>
                        </div>
                    </div>
                    <div style="margin-top: 10px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 6px;"><span style="color: #64748b;">Duration:</span><span style="color: #1e293b; font-weight: 500;">4 weeks</span></div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 6px;"><span style="color: #64748b;">Classes:</span><span style="color: #1e293b; font-weight: 500;">8 sessions</span></div>
                        <div style="display: flex; justify-content: space-between;"><span style="color: #64748b;">Points:</span><span style="color: #2c7da0; font-weight: 700;">200 points</span></div>
                    </div>
                </div>
                
                <div class="level-card" onclick="selectLevel('intermediate', 300, '6 weeks', 12, 'Intermediate')" id="levelIntermediate" style="background: #f8fafc; border: 2px solid #e2e8f0; border-radius: 15px; padding: 20px; cursor: pointer; transition: all 0.3s;">
                    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                        <div style="width: 50px; height: 50px; background: linear-gradient(135deg, #0284c7, #0ea5e9); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                            <i class="fa-solid fa-person-swimming" style="color: white; font-size: 22px;"></i>
                        </div>
                        <div>
                            <h4 style="color: #1e293b; font-size: 18px; font-weight: 700;">Intermediate</h4>
                            <p style="color: #64748b; font-size: 12px;">Improve stroke techniques</p>
                        </div>
                    </div>
                    <div style="margin-top: 10px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 6px;"><span style="color: #64748b;">Duration:</span><span style="color: #1e293b; font-weight: 500;">6 weeks</span></div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 6px;"><span style="color: #64748b;">Classes:</span><span style="color: #1e293b; font-weight: 500;">12 sessions</span></div>
                        <div style="display: flex; justify-content: space-between;"><span style="color: #64748b;">Points:</span><span style="color: #2c7da0; font-weight: 700;">300 points</span></div>
                    </div>
                </div>
                
                <div class="level-card" onclick="selectLevel('advanced', 400, '8 weeks', 16, 'Advanced')" id="levelAdvanced" style="background: #f8fafc; border: 2px solid #e2e8f0; border-radius: 15px; padding: 20px; cursor: pointer; transition: all 0.3s;">
                    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                        <div style="width: 50px; height: 50px; background: linear-gradient(135deg, #8b5cf6, #7c3aed); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                            <i class="fa-solid fa-medal" style="color: white; font-size: 22px;"></i>
                        </div>
                        <div>
                            <h4 style="color: #1e293b; font-size: 18px; font-weight: 700;">Advanced</h4>
                            <p style="color: #64748b; font-size: 12px;">Master competitive swimming</p>
                        </div>
                    </div>
                    <div style="margin-top: 10px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 6px;"><span style="color: #64748b;">Duration:</span><span style="color: #1e293b; font-weight: 500;">8 weeks</span></div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 6px;"><span style="color: #64748b;">Classes:</span><span style="color: #1e293b; font-weight: 500;">16 sessions</span></div>
                        <div style="display: flex; justify-content: space-between;"><span style="color: #64748b;">Points:</span><span style="color: #2c7da0; font-weight: 700;">400 points</span></div>
                    </div>
                </div>
            </div>
            
            <h3 style="color: #1e293b; font-size: 18px; margin-bottom: 15px; display: flex; align-items: center; gap: 8px;">
                <i class="fa-regular fa-calendar" style="color: #2c7da0;"></i> Select Schedule
            </h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 30px;">
                <div onclick="selectSchedule('morning', '09:00 AM - 10:00 AM')" id="scheduleMorning" style="background: #f8fafc; border: 2px solid #e2e8f0; border-radius: 12px; padding: 15px; text-align: center; cursor: pointer; transition: all 0.3s;">
                    <i class="fa-solid fa-sun" style="font-size: 28px; color: #f59e0b; margin-bottom: 8px;"></i>
                    <div style="font-weight: 600;">Morning Batch</div>
                    <div style="color: #64748b; font-size: 12px;">09:00 AM - 10:00 AM</div>
                </div>
                <div onclick="selectSchedule('afternoon', '02:00 PM - 03:00 PM')" id="scheduleAfternoon" style="background: #f8fafc; border: 2px solid #e2e8f0; border-radius: 12px; padding: 15px; text-align: center; cursor: pointer; transition: all 0.3s;">
                    <i class="fa-solid fa-cloud-sun" style="font-size: 28px; color: #2c7da0; margin-bottom: 8px;"></i>
                    <div style="font-weight: 600;">Afternoon Batch</div>
                    <div style="color: #64748b; font-size: 12px;">02:00 PM - 03:00 PM</div>
                </div>
                <div onclick="selectSchedule('evening', '05:00 PM - 06:00 PM')" id="scheduleEvening" style="background: #f8fafc; border: 2px solid #e2e8f0; border-radius: 12px; padding: 15px; text-align: center; cursor: pointer; transition: all 0.3s;">
                    <i class="fa-solid fa-moon" style="font-size: 28px; color: #8b5cf6; margin-bottom: 8px;"></i>
                    <div style="font-weight: 600;">Evening Batch</div>
                    <div style="color: #64748b; font-size: 12px;">05:00 PM - 06:00 PM</div>
                </div>
            </div>
            
            <input type="hidden" id="selectedLevel" value="">
            <input type="hidden" id="selectedPoints" value="">
            <input type="hidden" id="selectedDuration" value="">
            <input type="hidden" id="selectedSessions" value="">
            <input type="hidden" id="selectedLevelName" value="">
            <input type="hidden" id="selectedSchedule" value="">
            <input type="hidden" id="selectedScheduleTime" value="">
            
            <div id="lessonBookingSummary" style="display: none; background: #e0f2fe; border-radius: 15px; padding: 20px; margin-bottom: 25px;">
                <h4 style="color: #1e293b; margin-bottom: 15px;"><i class="fa-regular fa-rectangle-list" style="color: #2c7da0;"></i> Booking Summary</h4>
                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;"><span style="color: #64748b;">Level:</span><span id="summaryLevel" style="color: #1e4a76; font-weight: 600;">-</span></div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;"><span style="color: #64748b;">Duration:</span><span id="summaryDuration" style="color: #1e4a76; font-weight: 600;">-</span></div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;"><span style="color: #64748b;">Classes:</span><span id="summarySessions" style="color: #1e4a76; font-weight: 600;">-</span></div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;"><span style="color: #64748b;">Schedule:</span><span id="summarySchedule" style="color: #1e4a76; font-weight: 600;">-</span></div>
                <div style="display: flex; justify-content: space-between; padding-top: 10px; border-top: 1px solid #cbd5e1;"><span style="font-weight: 600;">Points Required:</span><span id="summaryPoints" style="color: #2c7da0; font-weight: 700; font-size: 18px;">-</span></div>
            </div>
            
            <div style="display: flex; gap: 15px;">
                <button onclick="confirmLessonBooking()" id="confirmLessonBtn" disabled style="flex: 1; padding: 15px; background: linear-gradient(135deg, #1e4a76, #2c7da0); border: none; border-radius: 10px; color: white; font-weight: 600; cursor: pointer; opacity: 0.5;">
                    <i class="fa-solid fa-check"></i> Enroll Now
                </button>
                <button onclick="closeSwimLessonsModal()" style="flex: 1; padding: 15px; background: white; border: 1px solid #e2e8f0; border-radius: 10px; color: #1e293b; font-weight: 600; cursor: pointer;">
                    <i class="fa-solid fa-times"></i> Cancel
                </button>
            </div>
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

// Transport Modal Functions
function openRoutesModal() { document.getElementById('routesModal').style.display = 'block'; }
function closeRoutesModal() { document.getElementById('routesModal').style.display = 'none'; }

function openPassesModal() { document.getElementById('passesModal').style.display = 'block'; }
function closePassesModal() { document.getElementById('passesModal').style.display = 'none'; }

function openTrackingModal() { document.getElementById('trackingModal').style.display = 'block'; }
function closeTrackingModal() { document.getElementById('trackingModal').style.display = 'none'; }

function openScheduleModal() { document.getElementById('scheduleModal').style.display = 'block'; }
function closeScheduleModal() { document.getElementById('scheduleModal').style.display = 'none'; }

// Request Pass function (instead of direct buy)
function requestPassFromModal(routeName, price) {
    if(confirm(`Request transport pass for ${routeName} (${price} points)?\n\nYour request will be sent to admin for approval. Points will be deducted only after approval.`)) {
        fetch('request_pass.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'route=' + encodeURIComponent(routeName) + '&price=' + price
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                alert('✅ Pass request submitted! You will be notified once approved.');
                location.reload();
            } else {
                alert('❌ Error: ' + data.message);
            }
        })
        .catch(error => {
            alert('❌ Error processing request');
        });
    }
}

// Keep the old buyPass function for backward compatibility if needed
function buyPass(routeName, price) {
    requestPassFromModal(routeName, price);
}

function updateLocationModal(routeId) {
    let newPlace = prompt("Where is the bus now? (e.g., Malabe, Colombo, etc.)");
    if (!newPlace) return;
    
    let time = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    
    fetch('save_location.php', {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: 'route=' + routeId + '&place=' + encodeURIComponent(newPlace) + '&time=' + time
    })
    .then(res => res.text())
    .then(data => {
        let statusEl = document.getElementById('modal-status-' + routeId);
        if (statusEl) {
            statusEl.innerHTML = '<strong>Last Location:</strong> ' + newPlace + ' <br> <strong>Updated at:</strong> ' + time;
        }
        alert("✅ Location saved!");
    })
    .catch(error => { alert("❌ Error saving location"); });
}

function viewLocationModal(routeId) {
    fetch('get_location.php?route=' + routeId)
    .then(res => res.json())
    .then(data => {
        if (!data || !data.location) {
            alert("No updates yet for this route");
            return;
        }
        
        let map = document.getElementById("modal-map-" + routeId);
        if (map) {
            map.src = "https://maps.google.com/maps?q=" + encodeURIComponent(data.location) + "&output=embed";
        }
        
        let statusEl = document.getElementById('modal-status-' + routeId);
        if (statusEl) {
            statusEl.innerHTML = '<strong>Last Location:</strong> ' + data.location + ' <br> <strong>Updated at:</strong> ' + data.updated_time;
        }
    })
    .catch(error => { alert("❌ Error fetching location"); });
}

// Library Functions
function openStudyRoomsModal() { document.getElementById('studyRoomsModal').style.display = 'block'; }
function closeStudyRoomsModal() { document.getElementById('studyRoomsModal').style.display = 'none'; cancelSelection(); }
function openPrintServicesModal() { document.getElementById('printServicesModal').style.display = 'block'; }
function closePrintServicesModal() { document.getElementById('printServicesModal').style.display = 'none'; resetPrintSelection(); }

// Gym Functions
function openTrainerModal() { document.getElementById('personalTrainerModal').style.display = 'block'; document.getElementById('trainerDate').value = new Date().toISOString().split('T')[0]; updateTrainerSummary(); }
function closeTrainerModal() { document.getElementById('personalTrainerModal').style.display = 'none'; }
function openLockerModal() { document.getElementById('lockerRoomModal').style.display = 'block'; resetLocker(); }
function closeLockerModal() { document.getElementById('lockerRoomModal').style.display = 'none'; resetLocker(); }

// Pool Functions
function openSwimLessonsModal() { document.getElementById('swimLessonsModal').style.display = 'block'; resetLessonSelections(); }
function closeSwimLessonsModal() { document.getElementById('swimLessonsModal').style.display = 'none'; resetLessonSelections(); }

function featureAction(feature) {
    alert(`🔧 "${feature}" feature is coming soon! We're working on it.`);
}

// Study Rooms Functions
let selectedRoom = null;
let selectedTime = null;
function selectRoom(roomId) {
    document.querySelectorAll('.room-card').forEach(card => { card.classList.remove('selected'); card.style.border = '1px solid #e2e8f0'; });
    document.getElementById(`room${roomId}`).style.border = '2px solid #2c7da0';
    selectedRoom = roomId;
}
function selectTimeSlot(roomId, time) {
    document.querySelectorAll(`#room${roomId}-slots .time-slot`).forEach(slot => { slot.classList.remove('selected'); slot.style.background = '#e2e8f0'; slot.style.color = '#2c7da0'; });
    event.target.classList.add('selected'); event.target.style.background = '#2c7da0'; event.target.style.color = 'white';
    selectedTime = time; selectedRoom = roomId;
    document.getElementById(`bookBtn${roomId}`).disabled = false; document.getElementById(`bookBtn${roomId}`).style.opacity = '1';
    showBookingSummary(roomId, time);
}
function showBookingSummary(roomId, time) {
    let roomName = roomId === 101 ? 'Room 101' : 'Room 102';
    let date = document.getElementById('bookingDate').value;
    let formattedDate = new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
    document.getElementById('summaryRoom').textContent = roomName;
    document.getElementById('summaryDate').textContent = formattedDate;
    document.getElementById('summaryTime').textContent = time;
    document.getElementById('bookingSummary').style.display = 'block';
}
function cancelSelection() {
    selectedRoom = null; selectedTime = null;
    document.querySelectorAll('.room-card').forEach(card => { card.classList.remove('selected'); card.style.border = '1px solid #e2e8f0'; });
    document.querySelectorAll('.time-slot').forEach(slot => { slot.classList.remove('selected'); slot.style.background = '#e2e8f0'; slot.style.color = '#2c7da0'; });
    document.querySelectorAll('[id^="bookBtn"]').forEach(btn => { btn.disabled = true; btn.style.opacity = '0.5'; });
    document.getElementById('bookingSummary').style.display = 'none';
}
function bookRoom(roomId) { if(selectedRoom === roomId && selectedTime) confirmBooking(); }
function confirmBooking() {
    if(selectedRoom && selectedTime) {
        let roomName = selectedRoom === 101 ? 'Room 101' : 'Room 102';
        let date = document.getElementById('bookingDate').value;
        let formattedDate = new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
        alert(`✅ Room ${roomName} booked successfully for ${formattedDate} at ${selectedTime}!`);
        cancelSelection(); closeStudyRoomsModal();
    }
}
document.getElementById('bookingDate')?.addEventListener('change', function() { alert('Showing availability for ' + this.value); cancelSelection(); });

// Print Services Functions
let selectedPrintType = null, selectedPrintPrice = 0;
function selectPrintType(type) {
    document.querySelectorAll('.print-card').forEach(card => { card.classList.remove('selected'); card.style.border = '1px solid #e2e8f0'; });
    document.getElementById(`print${type.toUpperCase()}`).classList.add('selected');
    document.getElementById(`print${type.toUpperCase()}`).style.border = '2px solid #2c7da0';
    selectedPrintType = type;
    switch(type) { case 'bw': selectedPrintPrice = 5; break; case 'color': selectedPrintPrice = 10; break; case 'copy': selectedPrintPrice = 3; break; case 'scan': selectedPrintPrice = 2; break; }
    updatePrintSummary();
}
function updateFileName(input) { if(input.files && input.files[0]) { document.getElementById('fileName').textContent = '📄 ' + input.files[0].name; updatePrintSummary(); } }
function updatePrintSummary() {
    let pages = document.getElementById('pageCount').value;
    let file = document.getElementById('fileUpload').files[0];
    if(selectedPrintType && file) {
        let totalPoints = selectedPrintPrice * pages;
        let typeName = selectedPrintType === 'bw' ? 'Black & White' : (selectedPrintType === 'color' ? 'Color' : (selectedPrintType === 'copy' ? 'Photocopy' : 'Scan'));
        document.getElementById('printSummaryText').innerHTML = `${typeName} • ${pages} page${pages > 1 ? 's' : ''} • ${totalPoints} points`;
        document.getElementById('printSummary').style.display = 'block';
        document.getElementById('printSubmitBtn').disabled = false; document.getElementById('printSubmitBtn').style.opacity = '1';
    } else { document.getElementById('printSummary').style.display = 'none'; document.getElementById('printSubmitBtn').disabled = true; document.getElementById('printSubmitBtn').style.opacity = '0.5'; }
}
function submitPrintJob() {
    let pages = document.getElementById('pageCount').value;
    let totalPoints = selectedPrintPrice * pages;
    if(confirm(`Submit print job? This will cost ${totalPoints} points.`)) { alert(`✅ Print job submitted successfully!`); closePrintServicesModal(); }
}
function resetPrintSelection() {
    selectedPrintType = null; selectedPrintPrice = 0;
    document.querySelectorAll('.print-card').forEach(card => { card.classList.remove('selected'); card.style.border = '1px solid #e2e8f0'; });
    document.getElementById('fileUpload').value = ''; document.getElementById('fileName').textContent = ''; document.getElementById('pageCount').value = '1';
    document.getElementById('printSummary').style.display = 'none'; document.getElementById('printSubmitBtn').disabled = true; document.getElementById('printSubmitBtn').style.opacity = '0.5';
}
document.getElementById('pageCount')?.addEventListener('input', updatePrintSummary);

// Personal Trainer Functions
function updateTrainerSummary() {
    let trainer = document.getElementById('trainerSelect').value;
    let session = document.getElementById('sessionSelect').value;
    let date = document.getElementById('trainerDate').value;
    let time = document.getElementById('trainerTime').value;
    let points = session == 30 ? 30 : (session == 60 ? 50 : 70);
    if(date && time) {
        document.getElementById('summaryTrainerName').textContent = trainer;
        document.getElementById('summaryPoints').textContent = points;
        document.getElementById('trainerSummary').style.display = 'block';
        document.getElementById('confirmTrainerBtn').disabled = false; document.getElementById('confirmTrainerBtn').style.opacity = '1';
    } else { document.getElementById('trainerSummary').style.display = 'none'; document.getElementById('confirmTrainerBtn').disabled = true; document.getElementById('confirmTrainerBtn').style.opacity = '0.5'; }
}
function confirmTrainerBooking() {
    let userPoints = <?php echo $user['PointsBalance']; ?>;
    let session = document.getElementById('sessionSelect').value;
    let points = session == 30 ? 30 : (session == 60 ? 50 : 70);
    if(userPoints < points) { alert('❌ Insufficient points! You need ' + points + ' points.'); return; }
    alert('✅ Personal Trainer booked successfully! ' + points + ' points deducted.');
    closeTrainerModal();
    let newPoints = userPoints - points;
    document.getElementById('pointsDisplay').textContent = newPoints; document.getElementById('currentPoints').textContent = newPoints;
}
document.getElementById('trainerSelect')?.addEventListener('change', updateTrainerSummary);
document.getElementById('sessionSelect')?.addEventListener('change', updateTrainerSummary);
document.getElementById('trainerDate')?.addEventListener('change', updateTrainerSummary);
document.getElementById('trainerTime')?.addEventListener('change', updateTrainerSummary);

// Locker Room Functions
let selectedLocker = null, selectedLockerCost = null;
function resetLocker() {
    document.querySelectorAll('[id^="locker"]').forEach(c => c.style.border = '2px solid #e2e8f0');
    selectedLocker = null; selectedLockerCost = null;
    document.getElementById('lockerSummary').style.display = 'none';
    document.getElementById('confirmLockerBtn').disabled = true; document.getElementById('confirmLockerBtn').style.opacity = '0.5';
}
function selectLocker(name, cost) {
    document.querySelectorAll('[id^="locker"]').forEach(c => c.style.border = '2px solid #e2e8f0');
    if(name === 'Small Locker') document.getElementById('lockerSmall').style.border = '2px solid #2c7da0';
    else if(name === 'Medium Locker') document.getElementById('lockerMedium').style.border = '2px solid #2c7da0';
    else if(name === 'Large Locker') document.getElementById('lockerLarge').style.border = '2px solid #2c7da0';
    selectedLocker = name; selectedLockerCost = cost;
    document.getElementById('lockerNameDisplay').textContent = name; document.getElementById('lockerCostDisplay').textContent = cost;
    document.getElementById('lockerSummary').style.display = 'block';
    document.getElementById('confirmLockerBtn').disabled = false; document.getElementById('confirmLockerBtn').style.opacity = '1';
}
function confirmLockerBooking() {
    let userPoints = <?php echo $user['PointsBalance']; ?>;
    if(userPoints < selectedLockerCost) { alert('❌ Insufficient points! You need ' + selectedLockerCost + ' points.'); return; }
    alert('✅ Locker booked successfully! ' + selectedLockerCost + ' points deducted.');
    closeLockerModal();
    let newPoints = userPoints - selectedLockerCost;
    document.getElementById('pointsDisplay').textContent = newPoints; document.getElementById('currentPoints').textContent = newPoints;
}

// Swim Lessons Functions
let selectedLevelVal = null, selectedPointsVal = null, selectedDurationVal = null, selectedSessionsVal = null, selectedLevelNameVal = null, selectedScheduleVal = null, selectedScheduleTimeVal = null;
function resetLessonSelections() {
    document.querySelectorAll('.level-card').forEach(card => { card.style.border = '2px solid #e2e8f0'; card.style.background = '#f8fafc'; });
    document.querySelectorAll('[id^="schedule"]').forEach(card => { card.style.border = '2px solid #e2e8f0'; card.style.background = '#f8fafc'; });
    selectedLevelVal = null; selectedPointsVal = null; selectedDurationVal = null; selectedSessionsVal = null; selectedLevelNameVal = null; selectedScheduleVal = null; selectedScheduleTimeVal = null;
    document.getElementById('lessonBookingSummary').style.display = 'none'; document.getElementById('confirmLessonBtn').disabled = true; document.getElementById('confirmLessonBtn').style.opacity = '0.5';
}
function selectLevel(level, points, duration, sessions, levelName) {
    document.querySelectorAll('.level-card').forEach(card => { card.style.border = '2px solid #e2e8f0'; card.style.background = '#f8fafc'; });
    if(level === 'beginner') { document.getElementById('levelBeginner').style.border = '2px solid #2c7da0'; document.getElementById('levelBeginner').style.background = '#e0f2fe'; }
    else if(level === 'intermediate') { document.getElementById('levelIntermediate').style.border = '2px solid #2c7da0'; document.getElementById('levelIntermediate').style.background = '#e0f2fe'; }
    else if(level === 'advanced') { document.getElementById('levelAdvanced').style.border = '2px solid #2c7da0'; document.getElementById('levelAdvanced').style.background = '#e0f2fe'; }
    selectedLevelVal = level; selectedPointsVal = points; selectedDurationVal = duration; selectedSessionsVal = sessions; selectedLevelNameVal = levelName;
    updateLessonSummary();
}
function selectSchedule(schedule, time) {
    document.querySelectorAll('[id^="schedule"]').forEach(card => { card.style.border = '2px solid #e2e8f0'; card.style.background = '#f8fafc'; });
    if(schedule === 'morning') { document.getElementById('scheduleMorning').style.border = '2px solid #2c7da0'; document.getElementById('scheduleMorning').style.background = '#e0f2fe'; selectedScheduleVal = 'Morning Batch'; }
    else if(schedule === 'afternoon') { document.getElementById('scheduleAfternoon').style.border = '2px solid #2c7da0'; document.getElementById('scheduleAfternoon').style.background = '#e0f2fe'; selectedScheduleVal = 'Afternoon Batch'; }
    else if(schedule === 'evening') { document.getElementById('scheduleEvening').style.border = '2px solid #2c7da0'; document.getElementById('scheduleEvening').style.background = '#e0f2fe'; selectedScheduleVal = 'Evening Batch'; }
    selectedScheduleTimeVal = time;
    updateLessonSummary();
}
function updateLessonSummary() {
    if(selectedLevelVal && selectedScheduleVal) {
        document.getElementById('summaryLevel').textContent = selectedLevelNameVal;
        document.getElementById('summaryDuration').textContent = selectedDurationVal;
        document.getElementById('summarySessions').textContent = selectedSessionsVal + ' sessions';
        document.getElementById('summarySchedule').textContent = selectedScheduleVal + ' (' + selectedScheduleTimeVal + ')';
        document.getElementById('summaryPoints').textContent = selectedPointsVal + ' points';
        document.getElementById('lessonBookingSummary').style.display = 'block';
        document.getElementById('confirmLessonBtn').disabled = false; document.getElementById('confirmLessonBtn').style.opacity = '1';
    } else { document.getElementById('lessonBookingSummary').style.display = 'none'; document.getElementById('confirmLessonBtn').disabled = true; document.getElementById('confirmLessonBtn').style.opacity = '0.5'; }
}
function confirmLessonBooking() {
    let userPoints = <?php echo $user['PointsBalance']; ?>;
    if(userPoints < selectedPointsVal) { alert('❌ Insufficient points! You need ' + selectedPointsVal + ' points.'); return; }
    alert('✅ Successfully enrolled in ' + selectedLevelNameVal + ' Swimming Lessons!\n\nPoints deducted: ' + selectedPointsVal);
    let newPoints = userPoints - selectedPointsVal;
    document.getElementById('pointsDisplay').textContent = newPoints; document.getElementById('currentPoints').textContent = newPoints;
    closeSwimLessonsModal();
}

// Close modals when clicking outside
window.onclick = function(event) {
    const routesModal = document.getElementById('routesModal');
    const passesModal = document.getElementById('passesModal');
    const trackingModal = document.getElementById('trackingModal');
    const scheduleModal = document.getElementById('scheduleModal');
    const studyModal = document.getElementById('studyRoomsModal');
    const printModal = document.getElementById('printServicesModal');
    const trainerModal = document.getElementById('personalTrainerModal');
    const lockerModal = document.getElementById('lockerRoomModal');
    const swimModal = document.getElementById('swimLessonsModal');
    
    if(event.target == routesModal) routesModal.style.display = 'none';
    if(event.target == passesModal) passesModal.style.display = 'none';
    if(event.target == trackingModal) trackingModal.style.display = 'none';
    if(event.target == scheduleModal) scheduleModal.style.display = 'none';
    if(event.target == studyModal) { studyModal.style.display = 'none'; cancelSelection(); }
    if(event.target == printModal) { printModal.style.display = 'none'; resetPrintSelection(); }
    if(event.target == trainerModal) trainerModal.style.display = 'none';
    if(event.target == lockerModal) lockerModal.style.display = 'none';
    if(event.target == swimModal) { swimModal.style.display = 'none'; resetLessonSelections(); }
}
</script>

</body>
</html>