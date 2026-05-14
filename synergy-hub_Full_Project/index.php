<?php

require_once 'config.php';
require_once 'functions.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM Users WHERE UserID = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

$facilities_count_sql = "SELECT COUNT(*) as count FROM Facilities WHERE Status = 'Open'";
$facilities_count_result = mysqli_query($conn, $facilities_count_sql);
$facilities_count = mysqli_fetch_assoc($facilities_count_result)['count'];

$events_sql = "SELECT * FROM Events WHERE Status = 'Upcoming' AND StartTime > NOW() ORDER BY StartTime ASC LIMIT 3";
$events_stmt = mysqli_prepare($conn, $events_sql);
mysqli_stmt_execute($events_stmt);
$events_result = mysqli_stmt_get_result($events_stmt);

$su_events_sql = "SELECT * FROM su_events WHERE event_time > NOW() ORDER BY event_time ASC LIMIT 2";
$su_events_result = mysqli_query($conn, $su_events_sql);

$gym_sql = "SELECT * FROM gym_status ORDER BY id DESC LIMIT 1";
$gym_result = mysqli_query($conn, $gym_sql);
$gym = mysqli_fetch_assoc($gym_result);

$points = $user['PointsBalance'];

// ========== TIER SYSTEM ==========
// Define tier thresholds
$tiers = [
    'platinum' => ['min' => 5000, 'name' => 'Platinum', 'color' => '#E5E4E2', 'icon' => 'fa-crown', 'multiplier' => 2.0],
    'gold' => ['min' => 2000, 'name' => 'Gold', 'color' => '#FFD700', 'icon' => 'fa-medal', 'multiplier' => 1.5],
    'silver' => ['min' => 500, 'name' => 'Silver', 'color' => '#C0C0C0', 'icon' => 'fa-medal', 'multiplier' => 1.2],
    'bronze' => ['min' => 0, 'name' => 'Bronze', 'color' => '#CD7F32', 'icon' => 'fa-medal', 'multiplier' => 1.0]
];

// Determine current tier
$current_tier = 'bronze';
foreach ($tiers as $key => $tier) {
    if ($points >= $tier['min']) {
        $current_tier = $key;
    }
}

// Calculate next tier
$next_tier = null;
$points_to_next = null;
$tier_order = ['bronze', 'silver', 'gold', 'platinum'];
$current_index = array_search($current_tier, $tier_order);
if ($current_index < count($tier_order) - 1) {
    $next_tier_key = $tier_order[$current_index + 1];
    $next_tier = $tiers[$next_tier_key];
    $points_to_next = $next_tier['min'] - $points;
}

// Calculate progress percentage
$progress_percentage = 0;
if ($next_tier) {
    $total_needed = $next_tier['min'];
    $progress_percentage = min(100, ($points / $total_needed) * 100);
} else {
    $progress_percentage = 100;
}

// Get tier benefits
$tier_benefits = [
    'bronze' => [
        'Points Multiplier: 1.0x',
        'Basic access to facilities'
    ],
    'silver' => [
        'Points Multiplier: 1.2x',
        '5% discount at campus café',
        'Early event registration'
    ],
    'gold' => [
        'Points Multiplier: 1.5x',
        '10% discount at campus café',
        'Priority booking for facilities',
        '1 free drink per month'
    ],
    'platinum' => [
        'Points Multiplier: 2.0x',
        '15% discount at campus café',
        'VIP event access',
        '2 free drinks per month',
        'Priority seating at events'
    ]
];

// Get unread notification count for badge
$unread_count = 0;
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'notifications'");
$table_exists = mysqli_num_rows($table_check) > 0;
if ($table_exists) {
    $col_check = mysqli_query($conn, "SHOW COLUMNS FROM notifications LIKE 'user_id'");
    if(mysqli_num_rows($col_check) > 0) {
        $unread_sql = "SELECT COUNT(*) as unread FROM notifications WHERE user_id = ? AND is_read = 0";
        $unread_stmt = mysqli_prepare($conn, $unread_sql);
        mysqli_stmt_bind_param($unread_stmt, "i", $user_id);
        mysqli_stmt_execute($unread_stmt);
        $unread_result = mysqli_stmt_get_result($unread_stmt);
        $unread_data = mysqli_fetch_assoc($unread_result);
        $unread_count = $unread_data ? $unread_data['unread'] : 0;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Synergy Hub - Dashboard</title>
   <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
        }
        
        body {
            min-height: 100vh;
            background: #3f3b7c;
            position: relative;
        }
        
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 32px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            position: relative;
            z-index: 1000;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .logo {
            font-size: 50px;
            font-weight: 700;
            color: #0f172a;
        }
        
        .logo span {
            color: #2563eb;
        }
        
        .icons {
            display: flex;
            gap: 20px;
            align-items: center;
            position: relative;
        }
        
        .menu-btn {
            color: #2563eb;
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
            background: rgba(37, 99, 235, 0.1);
            backdrop-filter: blur(10px);
            color: #2563eb;
            cursor: default;
            transition: all 0.3s;
        }
        
        .points.active {
            transform: scale(1.1);
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
            color: white;
        }
        
        .points i {
            color: #2563eb;
        }
        
        .points.active i {
            color: white;
        }
        
        .home-link {
            color: #2563eb;
            font-size: 20px;
            text-decoration: none;
        }
                
        .notify {
            position: relative;
            font-size: 20px;
            color: #2563eb;
            cursor: pointer;
            z-index: 10000;
        }
        
        .badge {
            position: absolute;
            top: -6px;
            right: -8px;
            background: #ef4444;
            color: white;
            font-size: 11px;
            font-weight: 600;
            padding: 2px 6px;
            border-radius: 50%;
            min-width: 18px;
            text-align: center;
            animation: pulse 1.5s infinite;
            z-index: 10001;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }
        
        .notification-dropdown {
            position: absolute;
            top: 45px;
            right: -20px;
            width: 380px;
            max-height: 500px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
            display: none;
            z-index: 999999 !important;
            overflow: hidden;
            border: 1px solid #e5e7eb;
        }
        
        .notification-dropdown.show {
            display: block;
        }
        
        .notification-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
            color: white;
        }
        
        .notification-header h3 {
            font-size: 16px;
            margin: 0;
        }
        
        .mark-read-btn {
            background: rgba(255,255,255,0.2);
            border: none;
            border-radius: 20px;
            padding: 5px 12px;
            color: white;
            font-size: 12px;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .mark-read-btn:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .notification-list {
            max-height: 350px;
            overflow-y: auto;
            background: white;
        }
        
        .notification-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 15px 20px;
            border-bottom: 1px solid #e5e7eb;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .notification-item:hover {
            background: #f9fafb;
        }
        
        .notification-item.unread {
            background: #eff6ff;
        }
        
        .notification-item.unread:hover {
            background: #dbeafe;
        }
        
        .notification-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }
        
        .notification-icon.gym {
            background: #dbeafe;
            color: #2563eb;
        }
        
        .notification-icon.event {
            background: #dcfce7;
            color: #16a34a;
        }
        
        .notification-icon.transport {
            background: #fff7ed;
            color: #ea580c;
        }
        
        .notification-icon.general {
            background: #f3e8ff;
            color: #9333ea;
        }
        
        .notification-content {
            flex: 1;
            min-width: 0;
        }
        
        .notification-title {
            font-weight: 600;
            color: #111827;
            margin-bottom: 3px;
            font-size: 14px;
        }
        
        .notification-message {
            color: #6b7280;
            font-size: 12px;
            margin-bottom: 5px;
            line-height: 1.4;
        }
        
        .notification-time {
            color: #9ca3af;
            font-size: 10px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .notification-time i {
            font-size: 8px;
        }
        
        .notification-footer {
            padding: 12px 20px;
            text-align: center;
            border-top: 1px solid #e5e7eb;
            background: #f9fafb;
        }
        
        .notification-footer a {
            color: #2563eb;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
        }
        
        .notification-footer a:hover {
            text-decoration: underline;
        }
        
        .loading-notifications,
        .no-notifications {
            padding: 30px;
            text-align: center;
            color: #6b7280;
            font-size: 14px;
        }
        
        .search-wrapper {
            position: relative;
            display: flex;
            justify-content: center;
            margin: 30px 20px;
            z-index: 100;
        }

        .search-wrapper input {
            width: 55%;
            max-width: 600px;
            padding: 16px 25px;
            border-radius: 40px;
            border: 2px solid #e2e8f0;
            outline: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            font-size: 16px;
            background: white;
            transition: all 0.3s;
        }

        .search-wrapper input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
            background: white;
        }

        .search-wrapper input::placeholder {
            color: #94a3b8;
        }

        .search-results {
            position: absolute;
            top: 70px;
            left: 50%;
            transform: translateX(-50%);
            width: 55%;
            max-width: 600px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
            display: none;
            z-index: 99999 !important;
            overflow: hidden;
            border: 1px solid #e5e7eb;
        }

        .search-results.show {
            display: block;
        }

        .search-results-header {
            padding: 12px 20px;
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
            color: white;
            font-size: 14px;
            font-weight: 600;
        }

        .search-results-list {
            max-height: 400px;
            overflow-y: auto;
        }

        .search-result-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px 20px;
            border-bottom: 1px solid #e5e7eb;
            cursor: pointer;
            transition: background 0.3s;
        }

        .search-result-item:hover {
            background: #f8fafc;
        }

        .search-result-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }

        .search-result-icon.facility {
            background: #dbeafe;
            color: #2563eb;
        }

        .search-result-icon.event {
            background: #dcfce7;
            color: #16a34a;
        }

        .search-result-icon.club {
            background: #fff7ed;
            color: #ea580c;
        }

        .search-result-icon.transport {
            background: #f3e8ff;
            color: #9333ea;
        }

        .search-result-icon.gym {
            background: #dbeafe;
            color: #2563eb;
        }

        .search-result-content {
            flex: 1;
            min-width: 0;
        }

        .search-result-title {
            font-weight: 600;
            color: #111827;
            margin-bottom: 4px;
            font-size: 15px;
        }

        .search-result-category {
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 2px;
        }

        .search-result-description {
            font-size: 12px;
            color: #9ca3af;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .search-no-results {
            padding: 30px;
            text-align: center;
            color: #6b7280;
        }

        .search-no-results i {
            font-size: 40px;
            color: #d1d5db;
            margin-bottom: 10px;
        }

        .search-loading {
            padding: 30px;
            text-align: center;
            color: #6b7280;
        }

        .search-loading i {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .profile {
            position: relative;
            z-index: 9998;
        }

        .avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            cursor: pointer;
            border: 2px solid #2563eb;
            transition: all 0.3s ease;
            object-fit: cover;
        }

        .avatar:hover {
            transform: scale(1.08);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.2);
            border-color: #60a5fa;
        }

        .profile-menu {
            position: absolute;
            right: 0;
            top: 55px;
            background: white;
            border-radius: 20px;
            width: 350px;
            opacity: 0;
            transform: translateY(-15px) scale(0.95);
            pointer-events: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 9998;
            box-shadow: 0 20px 35px -10px rgba(0, 0, 0, 0.2);
            border: 1px solid #e2e8f0;
            overflow: hidden;
        }

        .profile-menu.show {
            opacity: 1;
            transform: translateY(0) scale(1);
            pointer-events: auto;
        }

        .profile-header {
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
            padding: 25px 20px;
            text-align: center;
            position: relative;
        }

        .avatar-large {
            width: 85px;
            height: 85px;
            border-radius: 50%;
            border: 4px solid white;
            margin-bottom: 12px;
            object-fit: cover;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .profile-header h4 {
            color: white;
            font-size: 18px;
            margin: 5px 0;
            font-weight: 600;
        }

        .profile-header p {
            color: rgba(255, 255, 255, 0.85);
            font-size: 13px;
            margin: 0;
            word-break: break-all;
        }

        .profile-tier-card {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            margin: 15px;
            padding: 15px;
            border-radius: 16px;
            position: relative;
            overflow: hidden;
        }
        
        .profile-tier-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 150px;
            height: 150px;
            background: radial-gradient(circle, rgba(37, 99, 235, 0.15) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }
        
        .profile-tier-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .profile-tier-badge {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .profile-tier-badge i {
            font-size: 24px;
        }
        
        .profile-tier-name {
            font-size: 18px;
            font-weight: 700;
            color: white;
        }
        
        .profile-tier-points {
            font-size: 12px;
            color: #94a3b8;
        }
        
        .profile-tier-progress {
            margin: 12px 0;
        }
        
        .profile-progress-bar {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            height: 6px;
            overflow: hidden;
        }
        
        .profile-progress-fill {
            background: linear-gradient(90deg, #2563eb, #60a5fa);
            border-radius: 10px;
            height: 100%;
            transition: width 0.5s ease;
        }
        
        .profile-points-needed {
            font-size: 10px;
            color: #94a3b8;
            margin-top: 6px;
            text-align: right;
        }
        
        .profile-tier-benefits {
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .profile-tier-benefits h5 {
            font-size: 11px;
            color: #94a3b8;
            margin-bottom: 8px;
            letter-spacing: 0.5px;
        }
        
        .profile-tier-benefits ul {
            list-style: none;
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }
        
        .profile-tier-benefits li {
            font-size: 10px;
            background: rgba(255, 255, 255, 0.1);
            padding: 4px 10px;
            border-radius: 20px;
            color: white;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .profile-tier-benefits li i {
            font-size: 9px;
            color: #22d3ee;
        }
        
        .profile-tier-multiplier {
            position: absolute;
            bottom: 10px;
            right: 15px;
            background: rgba(37, 99, 235, 0.3);
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 9px;
            font-weight: 600;
            color: #93c5fd;
        }

        .profile-stats {
            display: flex;
            justify-content: space-around;
            padding: 18px 20px;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
        }

        .profile-stat {
            text-align: center;
        }

        .profile-stat-value {
            color: #2563eb;
            font-size: 22px;
            font-weight: 700;
        }

        .profile-stat-label {
            color: #64748b;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 4px;
        }

        .profile-actions {
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            background: white;
        }

        .edit-profile-btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
            color: white;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .edit-profile-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(37, 99, 235, 0.3);
        }

        .logout-profile-btn {
            width: 100%;
            padding: 12px;
            background: #ef4444;
            color: white;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .logout-profile-btn:hover {
            transform: translateY(-2px);
            background: #dc2626;
            box-shadow: 0 5px 15px rgba(239, 68, 68, 0.3);
        }

        .edit-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(5px);
            z-index: 10000;
            align-items: center;
            justify-content: center;
        }

        .edit-modal.show {
            display: flex;
        }

        .edit-box {
            background: white;
            padding: 30px;
            border-radius: 24px;
            width: 90%;
            max-width: 420px;
            animation: modalPop 0.3s ease;
        }

        @keyframes modalPop {
            from {
                opacity: 0;
                transform: scale(0.9);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .edit-box h3 {
            color: #1e293b;
            margin-bottom: 20px;
            font-size: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .edit-box h3 i {
            color: #2563eb;
        }

        .edit-avatar-preview {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }

        .edit-avatar-preview img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #2563eb;
            cursor: pointer;
            transition: opacity 0.3s;
        }

        .edit-avatar-preview img:hover {
            opacity: 0.8;
        }

        .edit-box input {
            width: 100%;
            padding: 14px 16px;
            margin-bottom: 15px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 15px;
            transition: all 0.3s;
        }

        .edit-box input:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .edit-file-input {
            position: relative;
            margin-bottom: 20px;
        }

        .edit-file-input label {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 16px;
            background: #f1f5f9;
            border-radius: 12px;
            cursor: pointer;
            font-size: 14px;
            color: #475569;
            transition: background 0.3s;
        }

        .edit-file-input label:hover {
            background: #e2e8f0;
        }

        .edit-file-input input {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }

        .edit-box-buttons {
            display: flex;
            gap: 12px;
            margin-top: 10px;
        }

        .edit-box-buttons button {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            font-size: 15px;
            transition: all 0.3s;
        }

        .edit-box-buttons button:first-child {
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
            color: white;
        }

        .edit-box-buttons button:first-child:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        .edit-box-buttons button:last-child {
            background: #e2e8f0;
            color: #1e293b;
        }

        .edit-box-buttons button:last-child:hover {
            background: #cbd5e1;
        }
        
        .sidebar {
            position: fixed;
            left: -280px;
            top: 0;
            width: 280px;
            height: 100%;
            background: linear-gradient(180deg, #0f2b3d 0%, #0a1a2a 100%);
            backdrop-filter: blur(10px);
            transition: 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 9999;
            box-shadow: 4px 0 30px rgba(0, 0, 0, 0.2);
            border-right: 1px solid rgba(255, 255, 255, 0.1);
            overflow-y: auto;
        }

        .sidebar.active {
            left: 0;
        }

        .sidebar-header {
            padding: 25px 20px 20px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 15px;
            position: relative;
            overflow: hidden;
        }

        .sidebar-header::after {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, rgba(37, 99, 235, 0.15) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        .sidebar-header h2 {
            color: white;
            font-size: 24px;
            font-weight: 700;
            margin: 0 0 5px 0;
            letter-spacing: -0.5px;
            background: linear-gradient(135deg, #ffffff 0%, #93c5fd 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .sidebar-header p {
            color: #94a3b8;
            font-size: 13px;
            margin: 0;
            font-weight: 400;
        }

        .sidebar-header p i {
            color: #22d3ee;
            margin-right: 5px;
            font-size: 10px;
        }

        .sidebar-user {
            padding: 15px 20px;
            background: rgba(255, 255, 255, 0.03);
            margin: 0 15px 20px 15px;
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.05);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .sidebar-user-avatar {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.2);
        }

        .sidebar-user-info h4 {
            color: white;
            font-size: 15px;
            margin: 0 0 3px 0;
            font-weight: 600;
        }

        .sidebar-user-info p {
            color: #94a3b8;
            font-size: 12px;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .sidebar-user-info p i {
            color: #fbbf24;
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
            color: #b8c7de;
            text-decoration: none;
            border-radius: 12px;
            transition: all 0.3s ease;
            gap: 12px;
            font-weight: 500;
            font-size: 15px;
            position: relative;
            overflow: hidden;
        }

        .sidebar-nav-link i {
            width: 22px;
            font-size: 1.1rem;
            color: #5f7d9e;
            transition: all 0.3s ease;
            text-align: center;
        }

        .sidebar-nav-link:hover {
            background: rgba(37, 99, 235, 0.2);
            color: white;
            transform: translateX(5px);
        }

        .sidebar-nav-link:hover i {
            color: #60a5fa;
        }

        .sidebar-nav-link.active {
            background: linear-gradient(90deg, rgba(37, 99, 235, 0.2) 0%, rgba(37, 99, 235, 0.05) 100%);
            color: white;
            border-left: 3px solid #3b82f6;
        }

        .sidebar-nav-link.active i {
            color: #60a5fa;
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

        .sidebar-divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
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
            background: rgba(255, 255, 255, 0.03);
            border-radius: 16px;
            padding: 15px;
            margin: 0 15px 20px 15px;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .sidebar-club-preview h4 {
            color: white;
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
            color: #fbbf24;
        }

        .sidebar-club-item {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 12px;
            padding: 12px;
            margin-bottom: 10px;
            border: 1px solid rgba(255, 255, 255, 0.03);
            transition: transform 0.2s;
        }

        .sidebar-club-item:hover {
            transform: translateX(5px);
            background: rgba(0, 0, 0, 0.3);
        }

        .sidebar-club-item h5 {
            color: white;
            font-size: 14px;
            margin: 0 0 4px 0;
            font-weight: 600;
        }

        .sidebar-club-item p {
            color: #94a3b8;
            font-size: 11px;
            margin: 0 0 6px 0;
            line-height: 1.4;
        }

        .sidebar-club-tag {
            background: #2d4c6e;
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
            background: rgba(255, 255, 255, 0.02);
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.03);
        }

        .sidebar-stat-item {
            text-align: center;
        }

        .sidebar-stat-value {
            color: white;
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 3px;
            background: linear-gradient(135deg, #fff, #93c5fd);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .sidebar-stat-label {
            color: #94a3b8;
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
            color: #94a3b8;
            text-decoration: none;
            font-size: 11px;
            transition: color 0.2s;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .sidebar-footer-links a:hover {
            color: white;
        }

        .sidebar-footer-links a i {
            font-size: 10px;
        }

        .sidebar-copyright {
            color: #64748b;
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
            background: rgba(255, 255, 255, 0.2);
            border-radius: 20px;
        }

        .sidebar::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.3);
        }
        
        .layout {
            display: flex;
            gap: 24px;
            padding: 0 24px 24px 24px;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .grid {
            flex: 3;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
        }
        
        .card {
            background: #ffffff;
            border-radius: 20px;
            padding: 20px;
            color: #1e293b;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            transition: all 0.25s ease;
            cursor: pointer;
            text-decoration: none;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }
        
        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.08);
            border-color: #bfdbfe;
        }
        
        .icon {
            font-size: 70px;
            display: block;
            margin-bottom: 12px;
            color: #2563eb;
        }
        
        .card h4 {
            margin: 4px 0 6px;
            font-size: 25px;
            font-weight: 600;
            color: #0f172a;
        }
        
        .card p {
            opacity: 0.8;
            font-size: 20px;
            margin: 0 0 8px 0;
            color: #475569;
        }
        
        .card .price {
            font-size: 22px;
            font-weight: 700;
            color: #2563eb;
            margin: 10px 0 12px;
        }
        
        .add-to-cart-btn {
            background: #eff6ff;
            border: none;
            border-radius: 40px;
            padding: 10px 16px;
            color: #2563eb;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.2s;
            margin-top: 8px;
            width: 100%;
            text-align: center;
        }
        
        .add-to-cart-btn:hover {
            background: #2563eb;
            color: white;
            transform: scale(0.98);
        }
        
        .events {
            flex: 1;
            background: #f8fafc;
            backdrop-filter: none;
            padding: 24px;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            color: #1e293b;
            height: fit-content;
            position: relative;
            z-index: 1;
        }
        
        .events h3 {
            margin-bottom: 20px;
            font-size: 22px;
            color: #2563eb;
            font-weight: 600;
        }
        
        .events h4 {
            color: #1e40af;
            margin: 15px 0 10px;
            font-size: 18px;
        }
        
        .events p {
            padding: 12px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .events p:last-child {
            border-bottom: none;
        }
        
        .events strong {
            color: #0f172a;
            font-size: 16px;
        }
        
        .events small {
            color: #64748b;
            font-size: 12px;
            display: block;
            margin-top: 4px;
        }
        
        .gym-status-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            color: #1e293b;
        }
        
        .gym-label {
            color: #475569;
        }
        
        .gym-value {
            font-weight: 600;
        }
        
        .pool-available {
            color: #10b981;
        }
        
        .pool-unavailable {
            color: #ef4444;
        }
        
        .route-mini {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e2e8f0;
            color: #1e293b;
            font-size: 14px;
        }
        
        .route-mini:last-child {
            border-bottom: none;
        }
        
        .route-name {
            font-weight: 500;
            color: #0f172a;
        }
        
        .route-time {
            color: #2563eb;
            font-weight: 600;
            background: #eff6ff;
            padding: 2px 10px;
            border-radius: 30px;
            font-size: 13px;
        }
        
        .route-time.evening {
            color: #2563eb;
            background: #eff6ff;
            font-weight: 700;
        }
        
        .on-time {
            color: #10b981;
        }
        
        .bus-schedule-header {
            margin-top: 10px;
            border-top: 2px solid #e2e8f0;
            padding-top: 10px;
        }
        
        .bus-note {
            color: #64748b;
            font-size: 12px;
            text-align: center;
            margin-top: 5px;
        }
        
        .emergency-btn {
            position: fixed;
            bottom: 110px;
            right: 30px;
            width: 65px;
            height: 65px;
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 26px;
            cursor: pointer;
            box-shadow: 0 5px 20px rgba(239, 68, 68, 0.4);
            z-index: 9996;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 3px solid rgba(255, 255, 255, 0.5);
            text-decoration: none;
            animation: emergencyPulse 2s infinite;
        }

        .emergency-btn:hover {
            transform: scale(1.1) rotate(5deg);
            box-shadow: 0 10px 30px rgba(239, 68, 68, 0.6);
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
        }

        .emergency-btn-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: white;
            color: #ef4444;
            font-size: 10px;
            font-weight: 800;
            min-width: 30px;
            height: 20px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 6px;
            border: 2px solid #ef4444;
            animation: pulse 1.5s infinite;
            letter-spacing: 0.5px;
        }

        @keyframes emergencyPulse {
            0% {
                box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7);
            }
            70% {
                box-shadow: 0 0 0 15px rgba(239, 68, 68, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(239, 68, 68, 0);
            }
        }

        .chat-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 65px;
            height: 65px;
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 26px;
            cursor: pointer;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.25);
            z-index: 9996;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 3px solid rgba(255, 255, 255, 0.3);
        }

        .chat-btn:hover {
            transform: scale(1.1) rotate(5deg);
            box-shadow: 0 10px 30px rgba(37, 99, 235, 0.4);
        }

        .chat-btn-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #ef4444;
            color: white;
            font-size: 12px;
            font-weight: 600;
            min-width: 22px;
            height: 22px;
            border-radius: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 6px;
            border: 2px solid white;
            animation: pulse 1.5s infinite;
        }

        @media (max-width: 768px) {
            .emergency-btn {
                width: 55px;
                height: 55px;
                font-size: 22px;
                right: 20px;
                bottom: 100px;
            }
            
            .chat-btn {
                width: 55px;
                height: 55px;
                font-size: 22px;
                right: 20px;
                bottom: 20px;
            }
            
            .grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .profile-menu {
                width: 320px;
                right: -50px;
            }
        }

        @media (max-width: 600px) {
            .grid {
                grid-template-columns: 1fr;
            }
            .layout {
                flex-direction: column;
            }
            
            .profile-menu {
                width: 300px;
                right: -20px;
            }
        }
        
        .chat-container {
            position: fixed;
            bottom: 100px;
            right: 30px;
            width: 380px;
            height: 600px;
            background: white;
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
            display: none;
            flex-direction: column;
            z-index: 9997;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .chat-container.show {
            display: flex;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .chat-header {
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: move;
        }

        .chat-header-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .chat-avatar {
            width: 45px;
            height: 45px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            border: 2px solid rgba(255, 255, 255, 0.5);
        }

        .chat-header-info h3 {
            font-size: 16px;
            margin: 0 0 4px 0;
            font-weight: 600;
        }

        .chat-header-info p {
            font-size: 12px;
            margin: 0;
            opacity: 0.9;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            background: #10b981;
            border-radius: 50%;
            display: inline-block;
            animation: pulse 1.5s infinite;
        }

        .chat-close {
            width: 32px;
            height: 32px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 16px;
        }

        .chat-close:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: rotate(90deg);
        }

        .chat-messages {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            background: #f8fafc;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .message-group {
            display: flex;
            flex-direction: column;
            max-width: 85%;
        }

        .message-group.bot {
            align-self: flex-start;
        }

        .message-group.user {
            align-self: flex-end;
        }

        .message-bubble {
            padding: 12px 16px;
            border-radius: 18px;
            word-wrap: break-word;
            line-height: 1.4;
            font-size: 14px;
            position: relative;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        .message-group.bot .message-bubble {
            background: white;
            color: #1e293b;
            border-bottom-left-radius: 5px;
            border: 1px solid #e2e8f0;
        }

        .message-group.user .message-bubble {
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
            color: white;
            border-bottom-right-radius: 5px;
        }

        .message-time {
            font-size: 10px;
            color: #94a3b8;
            margin-top: 4px;
            margin-left: 8px;
            margin-right: 8px;
        }

        .message-group.user .message-time {
            text-align: right;
        }

        .quick-replies {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 8px;
        }

        .quick-reply-btn {
            padding: 8px 14px;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 30px;
            font-size: 12px;
            color: #475569;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        .quick-reply-btn:hover {
            background: #2563eb;
            color: white;
            border-color: #2563eb;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(37, 99, 235, 0.2);
        }

        .typing-indicator {
            display: flex;
            gap: 4px;
            padding: 12px 16px;
            background: white;
            border-radius: 18px;
            border-bottom-left-radius: 5px;
            width: fit-content;
            border: 1px solid #e2e8f0;
        }

        .typing-indicator span {
            width: 8px;
            height: 8px;
            background: #94a3b8;
            border-radius: 50%;
            animation: typing 1s infinite ease-in-out;
        }

        .typing-indicator span:nth-child(1) { animation-delay: 0.1s; }
        .typing-indicator span:nth-child(2) { animation-delay: 0.2s; }
        .typing-indicator span:nth-child(3) { animation-delay: 0.3s; }

        @keyframes typing {
            0%, 60%, 100% { transform: translateY(0); }
            30% { transform: translateY(-8px); }
        }

        .chat-input-area {
            padding: 20px;
            background: white;
            border-top: 1px solid #e2e8f0;
        }

        .chat-input-wrapper {
            display: flex;
            align-items: center;
            gap: 12px;
            background: #f1f5f9;
            border-radius: 30px;
            padding: 6px;
            transition: all 0.3s;
        }

        .chat-input-wrapper:focus-within {
            background: white;
            box-shadow: 0 0 0 2px #2563eb;
        }

        .chat-input-wrapper input {
            flex: 1;
            padding: 12px 18px;
            border: none;
            background: transparent;
            outline: none;
            font-size: 14px;
            color: #1e293b;
        }

        .chat-input-wrapper input::placeholder {
            color: #94a3b8;
        }

        .chat-input-actions {
            display: flex;
            gap: 4px;
            padding-right: 6px;
        }

        .action-btn {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            border: none;
            background: transparent;
            color: #64748b;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
        }

        .action-btn:hover {
            background: #e2e8f0;
            color: #475569;
        }

        .action-btn.send {
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
            color: white;
        }

        .action-btn.send:hover {
            transform: scale(1.1);
            box-shadow: 0 5px 15px rgba(37, 99, 235, 0.3);
        }

        .chat-features {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 10px;
            padding: 0 8px;
        }

        .chat-feature-buttons {
            display: flex;
            gap: 12px;
        }

        .feature-btn {
            background: transparent;
            border: none;
            color: #64748b;
            font-size: 12px;
            cursor: pointer;
            transition: color 0.3s;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .feature-btn:hover {
            color: #2563eb;
        }

        .feature-btn i {
            font-size: 14px;
        }

        .chat-timestamp {
            font-size: 10px;
            color: #94a3b8;
        }

        .emoji-picker {
            position: absolute;
            bottom: 100px;
            right: 0;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            padding: 15px;
            display: none;
            grid-template-columns: repeat(6, 1fr);
            gap: 8px;
            z-index: 9998;
            border: 1px solid #e2e8f0;
        }

        .emoji-picker.show {
            display: grid;
        }

        .emoji-item {
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            cursor: pointer;
            border-radius: 8px;
            transition: background 0.3s;
        }

        .emoji-item:hover {
            background: #f1f5f9;
            transform: scale(1.1);
        }

        @keyframes messagePop {
            from {
                opacity: 0;
                transform: scale(0.8) translateY(10px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        .message-group {
            animation: messagePop 0.3s ease;
        }
        
        @media (max-width: 768px) {
            .chat-container {
                width: 320px;
                height: 500px;
                right: 15px;
                bottom: 85px;
            }
            
            .quick-replies {
                flex-wrap: wrap;
            }
            
            .quick-reply-btn {
                padding: 6px 12px;
                font-size: 11px;
            }
        }
        
        .hero-section {
            border-radius: 0 0 30px 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .hero-slide {
            background-image: url('library.jpg');
        }

        .hero-slide:nth-child(2) {
            background-image: url('maritime.jpg');
        }

        .hero-slide:nth-child(3) {
            background-image: url('convercation.jpg');
        }

        @media (max-width: 768px) {
            .hero-section {
                height: 50vh !important;
                min-height: 300px !important;
            }
            
            .hero-section h1 {
                font-size: 1.8rem !important;
            }
            
            .hero-section p {
                font-size: 0.9rem !important;
            }
        }
		
		/* ========== CUSTOM SCROLL BAR STYLES ========== */
		/* For Webkit browsers (Chrome, Safari, Edge) */
		::-webkit-scrollbar {
			width: 10px;
			height: 10px;
		}

		::-webkit-scrollbar-track {
			background: #f1f1f1;
			border-radius: 10px;
		}

		::-webkit-scrollbar-thumb {
			background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
			border-radius: 10px;
			transition: all 0.3s ease;
		}

		::-webkit-scrollbar-thumb:hover {
			background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%);
			transform: scale(1.05);
		}

		/* For Firefox */
		* {
			scrollbar-width: thin;
			scrollbar-color: #2563eb #f1f1f1;
		}

		/* Smooth scrolling for the whole page */
		html {
			scroll-behavior: smooth;
		}

		/* Body scroll styling */
		body {
			overflow-y: auto;
			overflow-x: hidden;
		}

		/* Custom scroll for the chat messages */
		.chat-messages::-webkit-scrollbar {
			width: 6px;
		}

		.chat-messages::-webkit-scrollbar-track {
			background: #e2e8f0;
			border-radius: 10px;
		}

		.chat-messages::-webkit-scrollbar-thumb {
			background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
			border-radius: 10px;
		}

		/* Custom scroll for sidebar */
		.sidebar::-webkit-scrollbar {
			width: 4px;
		}

		.sidebar::-webkit-scrollbar-track {
			background: rgba(255, 255, 255, 0.1);
			border-radius: 10px;
		}

		.sidebar::-webkit-scrollbar-thumb {
			background: rgba(255, 255, 255, 0.3);
			border-radius: 10px;
		}

		.sidebar::-webkit-scrollbar-thumb:hover {
			background: rgba(255, 255, 255, 0.5);
		}

		/* Custom scroll for notification dropdown */
		.notification-list::-webkit-scrollbar {
			width: 4px;
		}

		.notification-list::-webkit-scrollbar-track {
			background: #e2e8f0;
			border-radius: 10px;
		}

		.notification-list::-webkit-scrollbar-thumb {
			background: #2563eb;
			border-radius: 10px;
		}

		/* Custom scroll for search results */
		.search-results-list::-webkit-scrollbar {
			width: 6px;
		}

		.search-results-list::-webkit-scrollbar-track {
			background: #e2e8f0;
			border-radius: 10px;
		}

		.search-results-list::-webkit-scrollbar-thumb {
			background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
			border-radius: 10px;
		}
    </style>
</head>
<body>

<div class="bg"></div>

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
            <p><i class="fa-solid fa-star"></i> <?php echo $points; ?> points</p>
        </div>
    </div>

    <ul class="sidebar-nav">
        <li class="sidebar-nav-item">
            <a href="index.php" class="sidebar-nav-link active">
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
            <a href="qr.php" class="sidebar-nav-link">
                <i class="fa-solid fa-qrcode"></i>
                <span>QR & GPS</span>
            </a>
        </li>
        <li class="sidebar-nav-item">
            <a href="notifications.php" class="sidebar-nav-link">
                <i class="fa-solid fa-bell"></i>
                <span>Notifications</span>
                <span class="sidebar-badge" id="sidebarNotificationBadge"><?php echo $unread_count > 0 ? $unread_count : ''; ?></span>
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
            <div class="sidebar-stat-value"><?php echo $points; ?></div>
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
    
    <h1 class="logo">Synergy <span>Hub</span></h1>
    
    <div class="icons">
        <div class="notify" onclick="toggleNotifications()">
            <i class="fa-solid fa-bell"></i>
            <span class="badge" id="notificationBadge"><?php echo $unread_count > 0 ? $unread_count : '0'; ?></span>
            
            <div class="notification-dropdown" id="notificationDropdown">
                <div class="notification-header">
                    <h3>Notifications</h3>
                    <button onclick="markAllAsRead()" class="mark-read-btn">Mark all as read</button>
                </div>
                <div class="notification-list" id="notificationList">
                    <div class="loading-notifications">Loading...</div>
                </div>
                <div class="notification-footer">
                    <a href="notifications.php">View all notifications</a>
                </div>
            </div>
        </div>
        
        <div class="points" id="pointsBox">
            <i class="fa-solid fa-star"></i>
            <span id="pointsValue"><?php echo $points; ?></span>
        </div>
        
        <div class="profile">
            <img id="avatar" src="https://i.pravatar.cc/42?u=<?php echo $user_id; ?>" class="avatar" onclick="toggleProfileMenu(event)">
            
            <div class="profile-menu" id="profileMenu">
                <div class="profile-header">
                    <img id="avatarLarge" src="https://i.pravatar.cc/85?u=<?php echo $user_id; ?>" class="avatar-large">
                    <h4 id="userName"><?php echo htmlspecialchars($user['Name']); ?></h4>
                    <p id="userEmail"><?php echo htmlspecialchars($user['Email']); ?></p>
                </div>
                
                <!-- Tier Card inside Profile -->
                <div class="profile-tier-card">
                    <div class="profile-tier-header">
                        <div class="profile-tier-badge">
                            <i class="fa-solid <?php echo $tiers[$current_tier]['icon']; ?>" style="color: <?php echo $tiers[$current_tier]['color']; ?>"></i>
                            <span class="profile-tier-name"><?php echo $tiers[$current_tier]['name']; ?> Member</span>
                        </div>
                        <div class="profile-tier-points">
                            <?php echo number_format($points); ?> pts
                        </div>
                    </div>
                    
                    <div class="profile-tier-progress">
                        <div class="profile-progress-bar">
                            <div class="profile-progress-fill" style="width: <?php echo $progress_percentage; ?>%;"></div>
                        </div>
                        <?php if ($next_tier): ?>
                        <div class="profile-points-needed">
                            ⭐ <?php echo number_format($points_to_next); ?> more to <?php echo $next_tier['name']; ?>
                        </div>
                        <?php else: ?>
                        <div class="profile-points-needed">
                            🏆 Max Tier Reached!
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="profile-tier-benefits">
                        <h5><i class="fa-regular fa-star"></i> Benefits</h5>
                        <ul>
                            <?php foreach ($tier_benefits[$current_tier] as $benefit): ?>
                            <li><i class="fa-regular fa-circle-check"></i> <?php echo $benefit; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    
                    <div class="profile-tier-multiplier">
                        <i class="fa-solid fa-calculator"></i> <?php echo $tiers[$current_tier]['multiplier']; ?>x
                    </div>
                </div>
                
                <div class="profile-stats">
                    <div class="profile-stat">
                        <div class="profile-stat-value"><?php echo $points; ?></div>
                        <div class="profile-stat-label">Points</div>
                    </div>
                    <div class="profile-stat">
                        <div class="profile-stat-value">4</div>
                        <div class="profile-stat-label">Clubs</div>
                    </div>
                    <div class="profile-stat">
                        <div class="profile-stat-value">12</div>
                        <div class="profile-stat-label">Events</div>
                    </div>
                </div>
                
                <div class="profile-actions">
                    <button class="edit-profile-btn" onclick="openEditProfile()">
                        <i class="fa-regular fa-pen-to-square"></i> Edit Profile
                    </button>
                    <button class="logout-profile-btn" onclick="logout()">
                        <i class="fa-solid fa-right-from-bracket"></i> Logout
                    </button>
                </div>
            </div>
        </div>
    </div>
</header>

<div class="search-wrapper">
    <input type="text" id="searchInput" placeholder="Search facilities, events, clubs, transport..." autocomplete="off">
    
    <div class="search-results" id="searchResults">
        <div class="search-results-header">
            <i class="fa-solid fa-magnifying-glass"></i> Search Results
        </div>
        <div class="search-results-list" id="searchResultsList">
        </div>
    </div>
</div>

<!-- Hero Section with Animated Slider -->
<div class="hero-section" style="position: relative; width: 100%; height: 60vh; min-height: 400px; overflow: hidden; margin-bottom: 30px; border-radius: 0 0 30px 30px;">
    <div class="hero-slider" style="position: relative; width: 100%; height: 100%;">
        
        <!-- Slide 1 -->
        <div class="hero-slide active" style="position: absolute; width: 100%; height: 100%; background-image: url('library.jpg'); background-size: cover; background-position: center; transition: opacity 1s ease; opacity: 1;">
            <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(135deg, rgba(0,0,0,0.7) 0%, rgba(0,0,0,0.4) 100%);"></div>
            <div style="position: relative; height: 100%; display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center; color: white; padding: 0 20px;">
                <h1 style="font-size: 3rem; margin-bottom: 15px;">Welcome to <span style="color: #2563eb;">Synergy Hub</span></h1>
                <p style="font-size: 1.2rem; margin-bottom: 25px;">Your all-in-one campus companion</p>
                <button onclick="document.getElementById('searchInput').focus()" style="background: #2563eb; color: white; border: none; padding: 12px 35px; border-radius: 50px; font-size: 1rem; font-weight: 600; cursor: pointer;">Explore Now →</button>
            </div>
        </div>
        
        <!-- Slide 2 -->
        <div class="hero-slide" style="position: absolute; width: 100%; height: 100%; background-image: url('maritime.jpg'); background-size: cover; background-position: center; transition: opacity 1s ease; opacity: 0;">
            <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(135deg, rgba(0,0,0,0.7) 0%, rgba(0,0,0,0.4) 100%);"></div>
            <div style="position: relative; height: 100%; display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center; color: white; padding: 0 20px;">
                <h1 style="font-size: 3rem; margin-bottom: 15px;">Discover <span style="color: #2563eb;">Facilities</span></h1>
                <p style="font-size: 1.2rem; margin-bottom: 25px;">Gym, Pool, Sports grounds and more</p>
                <a href="facilities.php" style="background: #2563eb; color: white; text-decoration: none; padding: 12px 35px; border-radius: 50px; font-size: 1rem; font-weight: 600;">View Facilities →</a>
            </div>
        </div>
        
        <!-- Slide 3 -->
        <div class="hero-slide" style="position: absolute; width: 100%; height: 100%; background-image: url('convercation.jpg'); background-size: cover; background-position: center; transition: opacity 1s ease; opacity: 0;">
            <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(135deg, rgba(0,0,0,0.7) 0%, rgba(0,0,0,0.4) 100%);"></div>
            <div style="position: relative; height: 100%; display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center; color: white; padding: 0 20px;">
                <h1 style="font-size: 3rem; margin-bottom: 15px;">Join <span style="color: #2563eb;">Clubs</span> & <span style="color: #2563eb;">Events</span></h1>
                <p style="font-size: 1.2rem; margin-bottom: 25px;">Connect with like-minded students</p>
                <a href="clubs.php" style="background: #2563eb; color: white; text-decoration: none; padding: 12px 35px; border-radius: 50px; font-size: 1rem; font-weight: 600;">Explore Clubs →</a>
            </div>
        </div>
    </div>
    
    <!-- Slider Dots -->
    <div style="position: absolute; bottom: 20px; left: 50%; transform: translateX(-50%); display: flex; gap: 12px; z-index: 10;">
        <div class="hero-dot active" data-slide="0" style="width: 30px; height: 8px; border-radius: 10px; background: white; cursor: pointer;"></div>
        <div class="hero-dot" data-slide="1" style="width: 8px; height: 8px; border-radius: 50%; background: rgba(255,255,255,0.5); cursor: pointer;"></div>
        <div class="hero-dot" data-slide="2" style="width: 8px; height: 8px; border-radius: 50%; background: rgba(255,255,255,0.5); cursor: pointer;"></div>
    </div>
</div>

<main class="layout">
    <section class="grid">
        <a href="qr.php" class="card">
            <i class="fa-solid fa-qrcode icon"></i>
            <h4>QR & GPS</h4>
            <p>Scan QR codes Or use GPS to earn points</p>
        </a>
        
        <a href="map.html" target="_blank" class="card">
            <i class="fa-solid fa-earth-asia icon"></i>
            <h4>360° Map & Navigation</h4>
            <p>Explore CINEC campus and get directions</p>
        </a>
        
        <a href="facilities.php" class="card">
            <i class="fa-solid fa-dumbbell icon"></i>
            <h4>Facilities</h4>
            <p><?php echo $facilities_count; ?> facilities open now</p>
        </a>
        
        <a href="transport.php" class="card">
            <i class="fa-solid fa-bus icon"></i>
            <h4>Transport</h4>
            <p>Bus routes & schedules</p>
        </a>
        
        <a href="game.php" class="card">
            <i class="fa-solid fa-futbol icon"></i>
            <h4>Game Field</h4>
            <p>Play games & earn points</p>
        </a>
        
        <a href="clubs.php" class="card">
            <i class="fa-solid fa-building icon"></i>
            <h4>Club Hub</h4>
            <p>Join clubs & events</p>
        </a>
    </section>
    
    <aside class="events">
        <h3>Live Campus Updates</h3>
        
        <h4>🏋️  Gym</h4>
        <?php if($gym): ?>
        <div class="gym-status-item">
            <span class="gym-label">Status:</span>
            <span class="gym-value"><?php echo $gym['status']; ?></span>
        </div>
        <div class="gym-status-item">
            <span class="gym-label">Closes:</span>
            <span class="gym-value"><?php echo $gym['closing_time']; ?></span>
        </div>
        <div class="gym-status-item">
            <span class="gym-label">Pool:</span>
            <span class="gym-value <?php echo $gym['pool_available'] ? 'pool-available' : 'pool-unavailable'; ?>">
                <?php echo $gym['pool_available'] ? '✅ Available' : '❌ Not Available'; ?>
            </span>
        </div>
        <div class="gym-status-item">
            <span class="gym-label">Last Updated:</span>
            <span class="gym-value"><?php echo date('g:i A', strtotime($gym['last_updated'])); ?></span>
        </div>
        <?php else: ?>
        <p>Gym status unavailable</p>
        <?php endif; ?>
        
        <h4 style="margin-top: 20px;">🎓 Students' Union</h4>
        <?php if(mysqli_num_rows($su_events_result) > 0): ?>
            <?php while($su_event = mysqli_fetch_assoc($su_events_result)): ?>
            <p>
                <strong><?php echo htmlspecialchars($su_event['title']); ?></strong><br>
                <small><?php echo date('g:i A, M d', strtotime($su_event['event_time'])); ?> | <?php echo htmlspecialchars($su_event['location']); ?></small>
            </p>
            <?php endwhile; ?>
        <?php else: ?>
        <p>No SU events scheduled</p>
        <?php endif; ?>
        
        <h4 style="margin-top: 20px;">🚌 CINEC Bus Schedule</h4>
        
        <div class="route-mini">
            <span class="route-name">Negombo → CINEC</span>
            <span class="route-time">6:15 AM</span>
        </div>
        
        <div class="route-mini">
            <span class="route-name">Gampaha → CINEC</span>
            <span class="route-time">6:20 AM</span>
        </div>
        
        <div class="route-mini">
            <span class="route-name">Gampaha (via Oruthota) → CINEC</span>
            <span class="route-time">6:25 AM</span>
        </div>
        
        <div class="route-mini">
            <span class="route-name">Hendala → CINEC</span>
            <span class="route-time">6:30 AM</span>
        </div>
        
        <div class="route-mini">
            <span class="route-name">Moratuwa → CINEC</span>
            <span class="route-time">6:20 AM</span>
        </div>
        
        <div class="route-mini bus-schedule-header">
            <span class="route-name" style="font-weight: 700;">🚍 Departure from CINEC</span>
            <span class="route-time evening">5:05 PM</span>
        </div>
        <div class="bus-note">
            <i class="fa-solid fa-clock"></i> All buses depart at 5:05 PM
        </div>
        
        <h4 style="margin-top: 20px;">📅 Campus Events</h4>
        <?php if(mysqli_num_rows($events_result) > 0): ?>
            <?php while($event = mysqli_fetch_assoc($events_result)): ?>
            <p>
                <strong><?php echo htmlspecialchars($event['Title']); ?></strong><br>
                <small><?php echo date('g:i A', strtotime($event['StartTime'])); ?></small>
            </p>
            <?php endwhile; ?>
        <?php else: ?>
        <p>No upcoming events</p>
        <?php endif; ?>
        
        <h4 style="margin-top: 20px;">📞 Campus Hotline</h4>

        <div class="route-mini">
            <span class="route-name">Campus Security</span>
            <span class="route-time">
                <a href="tel:0112345678" style="color:#22d3ee; text-decoration:none;">
                    011-2345678
                </a>
            </span>
        </div>

        <div class="route-mini">
            <span class="route-name">Medical Center</span>
            <span class="route-time">
                <a href="tel:0118765432" style="color:#22d3ee; text-decoration:none;">
                    011-8765432
                </a>
            </span>
        </div>

        <div class="route-mini">
            <span class="route-name">IT Support</span>
            <span class="route-time">
                <a href="tel:0115678901" style="color:#22d3ee; text-decoration:none;">
                    011-5678901
                </a>
            </span>
        </div>

        <div class="route-mini">
            <span class="route-name">Transport Help</span>
            <span class="route-time">
                <a href="tel:0113456789" style="color:#22d3ee; text-decoration:none;">
                    011-3456789
                </a>
            </span>
        </div>
    </aside>
</main>

<div class="edit-modal" id="editModal">
    <div class="edit-box">
        <h3><i class="fa-regular fa-user"></i> Edit Profile</h3>
        
        <div class="edit-avatar-preview">
            <img id="editAvatarPreview" src="https://i.pravatar.cc/100?u=<?php echo $user_id; ?>" onclick="document.getElementById('editPhoto').click()">
        </div>
        
        <input id="editName" placeholder="Full Name" value="<?php echo htmlspecialchars($user['Name']); ?>">
        <input id="editEmail" placeholder="Email Address" type="email" value="<?php echo htmlspecialchars($user['Email']); ?>">
        
        <div class="edit-file-input">
            <label for="editPhoto">
                <i class="fa-solid fa-camera"></i> Change Profile Picture
            </label>
            <input type="file" id="editPhoto" accept="image/*" onchange="previewProfileImage(this)">
        </div>
        
        <div class="edit-box-buttons">
            <button onclick="saveProfile()">Save Changes</button>
            <button onclick="closeEdit()">Cancel</button>
        </div>
    </div>
</div>

<a href="emergency.php" class="emergency-btn" title="Emergency Response">
    <i class="fa-solid fa-triangle-exclamation"></i>
    <span class="emergency-btn-badge">SOS</span>
</a>

<div class="chat-btn" onclick="toggleChat()">
    <i class="fa-solid fa-comment"></i>
    <span class="chat-btn-badge" id="chatBadge" style="display: none;">1</span>
</div>

<div class="chat-container" id="chatContainer">
    <div class="chat-header" id="chatHeader">
        <div class="chat-header-left">
            <div class="chat-avatar">
                <i class="fa-solid fa-robot"></i>
            </div>
            <div class="chat-header-info">
                <h3>Campus Assistant</h3>
                <p>
                    <span class="status-dot"></span>
                    Online · AI Powered
                </p>
            </div>
        </div>
        <div class="chat-close" onclick="toggleChat()">
            <i class="fa-solid fa-times"></i>
        </div>
    </div>
    
    <div class="chat-messages" id="chatMessages">
        <div class="message-group bot">
            <div class="message-bubble">
                👋 Hi <?php echo htmlspecialchars($user['Name']); ?>! I'm your AI campus assistant. How can I help you today?
            </div>
            <div class="message-time">
                <i class="fa-regular fa-clock"></i> Just now
            </div>
        </div>
        
        <div class="message-group bot">
            <div class="message-bubble">
                Here's what I can help you with:
            </div>
            <div class="quick-replies">
                <button class="quick-reply-btn" onclick="quickReply('gym')">🏋️ Gym Status</button>
                <button class="quick-reply-btn" onclick="quickReply('events')">📅 Events</button>
                <button class="quick-reply-btn" onclick="quickReply('transport')">🚌 Transport</button>
                <button class="quick-reply-btn" onclick="quickReply('clubs')">👥 Clubs</button>
                <button class="quick-reply-btn" onclick="quickReply('games')">🎮 Games</button>
                <button class="quick-reply-btn" onclick="quickReply('points')">⭐ Points</button>
            </div>
            <div class="message-time">
                <i class="fa-regular fa-clock"></i> Just now
            </div>
        </div>
    </div>
    
    <div class="chat-input-area">
        <div class="chat-input-wrapper">
            <input type="text" id="chatInput" placeholder="Type your message..." autocomplete="off">
            <div class="chat-input-actions">
                <button class="action-btn" onclick="toggleEmojiPicker()" title="Add emoji">
                    <i class="fa-regular fa-face-smile"></i>
                </button>
                <button class="action-btn send" onclick="sendMessage()" title="Send message">
                    <i class="fa-regular fa-paper-plane"></i>
                </button>
            </div>
        </div>
        
        <div class="emoji-picker" id="emojiPicker">
            <div class="emoji-item" onclick="addEmoji('😊')">😊</div>
            <div class="emoji-item" onclick="addEmoji('😂')">😂</div>
            <div class="emoji-item" onclick="addEmoji('❤️')">❤️</div>
            <div class="emoji-item" onclick="addEmoji('👍')">👍</div>
            <div class="emoji-item" onclick="addEmoji('🎉')">🎉</div>
            <div class="emoji-item" onclick="addEmoji('🤔')">🤔</div>
            <div class="emoji-item" onclick="addEmoji('😎')">😎</div>
            <div class="emoji-item" onclick="addEmoji('👋')">👋</div>
            <div class="emoji-item" onclick="addEmoji('🔥')">🔥</div>
            <div class="emoji-item" onclick="addEmoji('💯')">💯</div>
            <div class="emoji-item" onclick="addEmoji('✅')">✅</div>
            <div class="emoji-item" onclick="addEmoji('❌')">❌</div>
        </div>
        
        <div class="chat-features">
            <div class="chat-feature-buttons">
                <button class="feature-btn" onclick="clearChat()" title="Clear chat">
                    <i class="fa-regular fa-trash-can"></i> Clear
                </button>
                <button class="feature-btn" onclick="exportChat()" title="Save chat">
                    <i class="fa-regular fa-download"></i> Save
                </button>
            </div>
            <div class="chat-timestamp">
                <i class="fa-regular fa-clock"></i> 
                <span id="chatTime"><?php echo date('h:i A'); ?></span>
            </div>
        </div>
    </div>
</div>

<footer class="footer">
    All rights reserved
</footer>

<script>
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

function toggleProfileMenu(e) {
    e.stopPropagation();
    document.getElementById("profileMenu").classList.toggle("show");
}

function logout() {
    window.location = "logout.php";
}

document.addEventListener("click", function() {
    document.getElementById("profileMenu").classList.remove("show");
});

function previewProfileImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById("editAvatarPreview").src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function openEditProfile() {
    document.getElementById("editModal").classList.add("show");
    document.getElementById("profileMenu").classList.remove("show");
    const currentAvatar = document.getElementById("avatarLarge").src;
    document.getElementById("editAvatarPreview").src = currentAvatar;
}

function closeEdit() {
    document.getElementById("editModal").classList.remove("show");
}

function saveProfile() {
    let name = document.getElementById("editName").value;
    let email = document.getElementById("editEmail").value;
    let photoFile = document.getElementById("editPhoto").files[0];
    
    if (name.trim() === "") {
        alert("Please enter your name");
        return;
    }
    
    if (email.trim() === "") {
        alert("Please enter your email");
        return;
    }
    
    // Update displayed name and email
    document.getElementById("userName").innerText = name;
    document.getElementById("userEmail").innerText = email;
    document.querySelector(".sidebar-user-info h4").innerText = name;
    
    if (photoFile) {
        let reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById("avatar").src = e.target.result;
            document.getElementById("avatarLarge").src = e.target.result;
            document.getElementById("editAvatarPreview").src = e.target.result;
        };
        reader.readAsDataURL(photoFile);
    }
    
    closeEdit();
    alert("Profile updated successfully!");
}

// ============= NOTIFICATION FUNCTIONS =============

function loadNotifications() {
    fetch('get_notifications.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateNotificationBadge(data.unread_count);
                displayNotifications(data.notifications);
            }
        })
        .catch(error => console.error('Error loading notifications:', error));
}

function updateNotificationBadge(count) {
    const badge = document.getElementById('notificationBadge');
    const sidebarBadge = document.getElementById('sidebarNotificationBadge');
    
    if (badge) {
        if (count > 0) {
            badge.textContent = count;
            badge.style.display = 'inline-flex';
        } else {
            badge.textContent = '0';
            badge.style.display = 'inline-flex';
        }
    }
    
    if (sidebarBadge) {
        if (count > 0) {
            sidebarBadge.textContent = count;
            sidebarBadge.style.display = 'inline-block';
        } else {
            sidebarBadge.textContent = '';
            sidebarBadge.style.display = 'none';
        }
    }
}

function displayNotifications(notifications) {
    const list = document.getElementById('notificationList');
    
    if (notifications.length === 0) {
        list.innerHTML = '<div class="no-notifications">No notifications</div>';
        return;
    }
    
    let html = '';
    notifications.forEach(notif => {
        const unreadClass = notif.is_read ? '' : 'unread';
        const iconClass = getNotificationIcon(notif.type);
        
        html += `
            <div class="notification-item ${unreadClass}" onclick="markAsRead(${notif.id})">
                <div class="notification-icon ${notif.type}">
                    <i class="fa-solid ${iconClass}"></i>
                </div>
                <div class="notification-content">
                    <div class="notification-title">${escapeHtml(notif.title)}</div>
                    <div class="notification-message">${escapeHtml(notif.message)}</div>
                    <div class="notification-time">
                        <i class="fa-regular fa-clock"></i> ${notif.time} • ${notif.date}
                    </div>
                </div>
            </div>
        `;
    });
    
    list.innerHTML = html;
}

function getNotificationIcon(type) {
    switch(type) {
        case 'gym': return 'fa-dumbbell';
        case 'event': return 'fa-calendar';
        case 'transport': return 'fa-bus';
        default: return 'fa-bell';
    }
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function toggleNotifications() {
    const dropdown = document.getElementById('notificationDropdown');
    if (dropdown) {
        dropdown.classList.toggle('show');
        if (dropdown.classList.contains('show')) {
            loadNotifications();
        }
    }
}

function markAsRead(notificationId) {
    fetch('mark_notification_read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'notification_id=' + notificationId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadNotifications();
        }
    });
}

function markAllAsRead() {
    fetch('mark_notification_read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'all=1'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadNotifications();
        }
    });
}

// Close dropdown when clicking outside
document.addEventListener('click', function(e) {
    const notify = document.querySelector('.notify');
    const dropdown = document.getElementById('notificationDropdown');
    
    if (notify && dropdown && !notify.contains(e.target)) {
        dropdown.classList.remove('show');
    }
});

// Load notifications on page load
document.addEventListener('DOMContentLoaded', function() {
    loadNotifications();
    setInterval(loadNotifications, 30000);
});

// Rest of the existing JavaScript code (search, chat, hero slider, etc.) continues here...
// (Keep all your existing code for search, chat, hero slider, etc. unchanged)

const searchInput = document.getElementById('searchInput');
const searchResults = document.getElementById('searchResults');
const searchResultsList = document.getElementById('searchResultsList');
let searchTimeout;


if (searchInput) {
    searchInput.addEventListener('input', function() {
        const query = this.value.trim();
        
        clearTimeout(searchTimeout);
        
        if (query.length < 2) {
            searchResults.classList.remove('show');
            return;
        }
        
        searchResultsList.innerHTML = '<div class="search-loading"><i class="fa-solid fa-spinner"></i> Searching...</div>';
        searchResults.classList.add('show');
        
        searchTimeout = setTimeout(() => {
            performSearch(query);
        }, 300);
    });
}

function performSearch(query) {
    fetch('search.php?q=' + encodeURIComponent(query))
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            displaySearchResults(data.results);
        })
        .catch(error => {
            console.error('Search error:', error);
            searchResultsList.innerHTML = '<div class="search-no-results">Error performing search</div>';
        });
}

function displaySearchResults(results) {
    if (!results || results.length === 0) {
        searchResultsList.innerHTML = `
            <div class="search-no-results">
                <i class="fa-regular fa-face-frown"></i>
                <p>No results found for "${searchInput.value}"</p>
            </div>
        `;
        return;
    }
    
    let html = '';
    results.forEach(item => {
        const icon = getSearchIcon(item.type);
        const link = getSearchLink(item);
        
        html += `
            <div class="search-result-item" onclick="window.location.href='${link}'">
                <div class="search-result-icon ${item.type}">
                    <i class="fa-solid ${icon}"></i>
                </div>
                <div class="search-result-content">
                    <div class="search-result-title">${escapeHtml(item.title)}</div>
                    <div class="search-result-category">${escapeHtml(item.category || item.type)}</div>
                    <div class="search-result-description">${escapeHtml(item.description || '')}</div>
                </div>
            </div>
        `;
    });
    
    searchResultsList.innerHTML = html;
}

function getSearchIcon(type) {
    switch(type) {
        case 'facility': return 'fa-building';
        case 'event': return 'fa-calendar';
        case 'club': return 'fa-users';
        case 'transport': return 'fa-bus';
        case 'gym': return 'fa-dumbbell';
        default: return 'fa-circle';
    }
}

function getSearchLink(item) {
    switch(item.type) {
        case 'facility': return 'facility_details.php?id=' + item.id;
        case 'event': return 'event_details.php?id=' + item.id;
        case 'club': return 'club_details.php?id=' + item.id;
        case 'transport': return 'transport.php';
        case 'gym': return 'facility_details.php?id=1';
        default: return '#';
    }
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

document.addEventListener('click', function(e) {
    const wrapper = document.querySelector('.search-wrapper');
    if (wrapper && !wrapper.contains(e.target)) {
        if (searchResults) {
            searchResults.classList.remove('show');
        }
    }
});

if (searchInput) {
    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            searchResults.classList.remove('show');
            this.blur();
        }
    });
}

let chatHistory = [];
let isTyping = false;
let unreadCount = 1;

document.addEventListener('DOMContentLoaded', function() {
    loadChatHistory();
    updateChatBadge();
    
    function loadChatHistory() {
        const saved = localStorage.getItem('chatHistory');
        if (saved) {
            chatHistory = JSON.parse(saved);
            displayChatHistory();
        }
    }
    document.getElementById('chatInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });
    makeChatDraggable();
});


function toggleChat() {
    const container = document.getElementById('chatContainer');
    container.classList.toggle('show');
    
    if (container.classList.contains('show')) {
        
        unreadCount = 0;
        updateChatBadge();
        
        scrollToBottom();
        
        setTimeout(() => {
            document.getElementById('chatInput').focus();
        }, 300);
    }
}

function sendMessage() {
    const input = document.getElementById('chatInput');
    const message = input.value.trim();
    
    if (!message) return;
    
    addMessage(message, 'user');
    
    input.value = '';
    
    showTypingIndicator();
    
    setTimeout(() => {
        removeTypingIndicator();
        const response = getAIResponse(message);
        addMessage(response, 'bot');
        
        if (shouldShowQuickReplies(message)) {
            addQuickReplies(message);
        }
    }, 1500 + Math.random() * 1000);
    
    saveToHistory(message, 'user');
}


function addMessage(text, sender) {
    const messagesDiv = document.getElementById('chatMessages');
    const messageGroup = document.createElement('div');
    messageGroup.className = `message-group ${sender}`;
    
    const bubble = document.createElement('div');
    bubble.className = 'message-bubble';
    bubble.textContent = text;
    
    const time = document.createElement('div');
    time.className = 'message-time';
    time.innerHTML = `<i class="fa-regular fa-clock"></i> ${getCurrentTime()}`;
    
    messageGroup.appendChild(bubble);
    messageGroup.appendChild(time);
    messagesDiv.appendChild(messageGroup);
    
    saveToHistory(text, sender, getCurrentTime());
    
    scrollToBottom();
    
    if (!document.getElementById('chatContainer').classList.contains('show') && sender === 'bot') {
        unreadCount++;
        updateChatBadge();
    }
}


function getAIResponse(message) {
    const msg = message.toLowerCase();
    
    if (msg.includes('gym') || msg.includes('workout') || msg.includes('exercise')) {
        const gymStatus = <?php echo json_encode($gym); ?>;
        if (gymStatus) {
            return `🏋️ **WLV Gym Status**\n\n` +
                   `• Status: ${gymStatus.status}\n` +
                   `• Closes: ${gymStatus.closing_time}\n` +
                   `• Pool: ${gymStatus.pool_available ? '✅ Available' : '❌ Not Available'}\n` +
                   `• Last Updated: ${new Date('<?php echo $gym['last_updated']; ?>').toLocaleTimeString()}\n\n` +
                   `Want to check-in to the gym? You can earn 10 points!`;
        }
        return "🏋️ The gym is currently open from 6:00 AM to 10:00 PM. Pool is available!";
    }
    
    else if (msg.includes('event') || msg.includes('calendar') || msg.includes('upcoming')) {
        return "📅 **Upcoming Events**\n\n" +
               "• Tech Workshop - Tomorrow 2:00 PM\n" +
               "• Career Fair - Friday 10:00 AM\n" +
               "• Sports Day - Saturday 8:00 AM\n\n" +
               "Check the Events panel for more details!";
    }
    
    else if (msg.includes('bus') || msg.includes('transport') || msg.includes('shuttle')) {
        return "🚌 **CINEC Bus Schedule**\n\n" +
               "**Morning Pickups:**\n" +
               "• Negombo → CINEC: 6:15 AM\n" +
               "• Gampaha → CINEC: 6:20 AM\n" +
               "• Hendala → CINEC: 6:30 AM\n" +
               "• Moratuwa → CINEC: 6:20 AM\n\n" +
               "**Evening Departure:**\n" +
               "• All buses depart CINEC at 5:05 PM";
    }
    
    else if (msg.includes('club') || msg.includes('society') || msg.includes('join')) {
        return "👥 **Active Clubs**\n\n" +
               "• Coding Club - Meets Wednesdays\n" +
               "• Robotics Club - Meets Tuesdays\n" +
               "• IEEE Student Branch\n" +
               "• Photography Club\n" +
               "• Drama Society\n\n" +
               "Want to join any club? I can help you with that!";
    }
    
    else if (msg.includes('game') || msg.includes('play') || msg.includes('fun')) {
        return "🎮 **Available Games**\n\n" +
               "• Memory Game - Test your memory\n" +
               "• Math Challenge - Solve problems\n" +
               "• Tic-Tac-Toe - Play with friends\n" +
               "• Quiz Battle - Compete with others\n\n" +
               "You can earn 10-50 points by playing games!";
    }
    
    else if (msg.includes('point') || msg.includes('score') || msg.includes('earn')) {
        return "⭐ **How to Earn Points**\n\n" +
               "• Check-in to facilities: +10 points\n" +
               "• Play games: 10-50 points\n" +
               "• Join clubs: +20 points\n" +
               "• Attend events: +15 points\n" +
               "• Refer friends: +30 points\n\n" +
               `Your current balance: ${<?php echo $points; ?>} points`;
    }
    
    else if (msg.includes('help') || msg.includes('support') || msg.includes('contact')) {
        return "🆘 **Need Help?**\n\n" +
               "• Campus Security: 011-2345678\n" +
               "• Medical Center: 011-8765432\n" +
               "• IT Support: 011-5678901\n" +
               "• Transport Help: 011-3456789\n\n" +
               "You can also visit the admin office in person.";
    }
    
    else if (msg.includes('hi') || msg.includes('hello') || msg.includes('hey')) {
        return `Hello ${<?php echo json_encode($user['Name']); ?>}! 👋 How can I assist you today?`;
    }
    
    else if (msg.includes('thank') || msg.includes('thanks')) {
        return "You're welcome! 😊 Let me know if you need anything else!";
    }
    
    else {
        return "I understand you're asking about " + message + ". Could you please be more specific? You can ask me about:\n\n" +
               "• Gym status 🏋️\n" +
               "• Events 📅\n" +
               "• Transport 🚌\n" +
               "• Clubs 👥\n" +
               "• Games 🎮\n" +
               "• Points ⭐\n" +
               "• Help 🆘";
    }
}

function addQuickReplies(originalMessage) {
    const messagesDiv = document.getElementById('chatMessages');
    const msg = originalMessage.toLowerCase();
    
    let replies = [];
    
    if (msg.includes('gym')) {
        replies = ['Check-in to Gym', 'Pool Status', 'Gym Timings', 'Book Court'];
    } else if (msg.includes('event')) {
        replies = ['Register for Event', 'Create Event', 'My Events', 'Event Calendar'];
    } else if (msg.includes('bus')) {
        replies = ['Track Bus', 'Bus Routes', 'Schedule PDF', 'Report Delay'];
    } else if (msg.includes('club')) {
        replies = ['Join Club', 'Club Events', 'My Clubs', 'Create Club'];
    } else {
        return;
    }
    
    const quickReplyGroup = document.createElement('div');
    quickReplyGroup.className = 'message-group bot';
    
    const quickReplyDiv = document.createElement('div');
    quickReplyDiv.className = 'quick-replies';
    
    replies.forEach(reply => {
        const btn = document.createElement('button');
        btn.className = 'quick-reply-btn';
        btn.textContent = reply;
        btn.onclick = () => quickReply(reply.toLowerCase());
        quickReplyDiv.appendChild(btn);
    });
    
    quickReplyGroup.appendChild(quickReplyDiv);
    messagesDiv.appendChild(quickReplyGroup);
    scrollToBottom();
}


function quickReply(action) {
    addMessage(action, 'user');
    
    showTypingIndicator();
    
    setTimeout(() => {
        removeTypingIndicator();
        const response = getAIResponse(action);
        addMessage(response, 'bot');
    }, 1000);
}

function showTypingIndicator() {
    if (isTyping) return;
    
    const messagesDiv = document.getElementById('chatMessages');
    const typingDiv = document.createElement('div');
    typingDiv.className = 'message-group bot';
    typingDiv.id = 'typingIndicator';
    
    const indicator = document.createElement('div');
    indicator.className = 'typing-indicator';
    indicator.innerHTML = '<span></span><span></span><span></span>';
    
    typingDiv.appendChild(indicator);
    messagesDiv.appendChild(typingDiv);
    
    isTyping = true;
    scrollToBottom();
}


function removeTypingIndicator() {
    const indicator = document.getElementById('typingIndicator');
    if (indicator) {
        indicator.remove();
    }
    isTyping = false;
}

function toggleEmojiPicker() {
    const picker = document.getElementById('emojiPicker');
    picker.classList.toggle('show');
}

function addEmoji(emoji) {
    const input = document.getElementById('chatInput');
    input.value += emoji;
    input.focus();
     
    document.getElementById('emojiPicker').classList.remove('show');
}

function clearChat() {
    if (confirm('Clear all messages?')) {
        const messagesDiv = document.getElementById('chatMessages');
        messagesDiv.innerHTML = '';
        
        addMessage(`👋 Hi ${<?php echo json_encode($user['Name']); ?>}! I'm your AI campus assistant. How can I help you today?`, 'bot');
        
        chatHistory = [];
        localStorage.removeItem('chatHistory');
    }
}

function exportChat() {
    const messages = document.querySelectorAll('.message-group');
    let chatText = "Campus Assistant Chat - " + new Date().toLocaleString() + "\n\n";
    
    messages.forEach(msg => {
        const sender = msg.classList.contains('user') ? 'You' : 'Assistant';
        const text = msg.querySelector('.message-bubble')?.textContent || '';
        const time = msg.querySelector('.message-time')?.textContent || '';
        chatText += `${sender} (${time}): ${text}\n`;
    });
    
    const blob = new Blob([chatText], { type: 'text/plain' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `chat-${new Date().toISOString().slice(0,10)}.txt`;
    a.click();
    URL.revokeObjectURL(url);
}

function saveToHistory(message, sender, time) {
    chatHistory.push({
        message: message,
        sender: sender,
        time: time || getCurrentTime(),
        date: new Date().toISOString()
    });
    
    if (chatHistory.length > 100) {
        chatHistory = chatHistory.slice(-100);
    }
    
    localStorage.setItem('chatHistory', JSON.stringify(chatHistory));
}

function displayChatHistory() {
    const messagesDiv = document.getElementById('chatMessages');
    messagesDiv.innerHTML = '';
    
    chatHistory.forEach(item => {
        const messageGroup = document.createElement('div');
        messageGroup.className = `message-group ${item.sender}`;
        
        const bubble = document.createElement('div');
        bubble.className = 'message-bubble';
        bubble.textContent = item.message;
        
        const time = document.createElement('div');
        time.className = 'message-time';
        time.innerHTML = `<i class="fa-regular fa-clock"></i> ${item.time}`;
        
        messageGroup.appendChild(bubble);
        messageGroup.appendChild(time);
        messagesDiv.appendChild(messageGroup);
    });
    
    scrollToBottom();
}

function getCurrentTime() {
    const now = new Date();
    let hours = now.getHours();
    const minutes = now.getMinutes().toString().padStart(2, '0');
    const ampm = hours >= 12 ? 'PM' : 'AM';
    hours = hours % 12;
    hours = hours ? hours : 12;
    return `${hours}:${minutes} ${ampm}`;
}

function scrollToBottom() {
    const messagesDiv = document.getElementById('chatMessages');
    messagesDiv.scrollTop = messagesDiv.scrollHeight;
}

function updateChatBadge() {
    const badge = document.getElementById('chatBadge');
    if (unreadCount > 0) {
        badge.textContent = unreadCount;
        badge.style.display = 'flex';
    } else {
        badge.style.display = 'none';
    }
}

function shouldShowQuickReplies(message) {
    const keywords = ['gym', 'event', 'bus', 'club', 'game', 'help'];
    return keywords.some(keyword => message.toLowerCase().includes(keyword));
}

function makeChatDraggable() {
    const chatContainer = document.getElementById('chatContainer');
    const chatHeader = document.getElementById('chatHeader');
    
    let isDragging = false;
    let startX, startY, startLeft, startTop;
    
    chatHeader.addEventListener('mousedown', startDrag);
    
    function startDrag(e) {
        isDragging = true;
        startX = e.clientX;
        startY = e.clientY;
        
        const rect = chatContainer.getBoundingClientRect();
        startLeft = rect.left;
        startTop = rect.top;
        
        document.addEventListener('mousemove', drag);
        document.addEventListener('mouseup', stopDrag);
        
        chatContainer.style.transition = 'none';
    }
    
    function drag(e) {
        if (!isDragging) return;
        
        e.preventDefault();
        
        const dx = e.clientX - startX;
        const dy = e.clientY - startY;
        
        let newLeft = startLeft + dx;
        let newTop = startTop + dy;
        
        const maxLeft = window.innerWidth - chatContainer.offsetWidth;
        const maxTop = window.innerHeight - chatContainer.offsetHeight;
        
        newLeft = Math.max(0, Math.min(newLeft, maxLeft));
        newTop = Math.max(0, Math.min(newTop, maxTop));
        
        chatContainer.style.left = newLeft + 'px';
        chatContainer.style.right = 'auto';
        chatContainer.style.bottom = 'auto';
    }
    
    function stopDrag() {
        isDragging = false;
        chatContainer.style.transition = 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
        document.removeEventListener('mousemove', drag);
        document.removeEventListener('mouseup', stopDrag);
    }
}

document.addEventListener('click', function(e) {
    const picker = document.getElementById('emojiPicker');
    const emojiBtn = document.querySelector('.action-btn:first-child');
    
    if (picker && emojiBtn && !picker.contains(e.target) && !emojiBtn.contains(e.target)) {
        picker.classList.remove('show');
    }
});

setInterval(() => {
    document.getElementById('chatTime').textContent = getCurrentTime();
}, 60000);

function checkEmergencyAlerts() {
    fetch('get_emergency_alerts.php')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.count > 0) {
                const badge = document.querySelector('.emergency-btn-badge');
                if (badge) {
                    badge.textContent = data.count > 9 ? '9+' : data.count;
                }
                
                const criticalAlerts = data.alerts.filter(a => a.severity === 'critical');
                if (criticalAlerts.length > 0 && !sessionStorage.getItem('alertShown')) {
                    const alert = criticalAlerts[0];
                    showEmergencyNotification(alert.title, alert.message);
                    sessionStorage.setItem('alertShown', 'true');
                    
                    setTimeout(() => {
                        sessionStorage.removeItem('alertShown');
                    }, 3600000);
                }
            }
        })
        .catch(error => console.error('Error checking alerts:', error));
}

function showEmergencyNotification(title, message) {
    if (Notification.permission === 'granted') {
        new Notification('🚨 EMERGENCY ALERT: ' + title, {
            body: message,
            icon: 'https://i.pravatar.cc/80?u=emergency',
            badge: 'https://i.pravatar.cc/80?u=emergency',
            vibrate: [200, 100, 200],
            requireInteraction: true
        });
    } else if (Notification.permission !== 'denied') {
        Notification.requestPermission().then(permission => {
            if (permission === 'granted') {
                showEmergencyNotification(title, message);
            }
        });
    }
    
    const toast = document.createElement('div');
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: #ef4444;
        color: white;
        padding: 15px 25px;
        border-radius: 10px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        z-index: 10001;
        animation: slideIn 0.3s ease;
        max-width: 350px;
        border-left: 5px solid white;
        cursor: pointer;
    `;
    toast.innerHTML = `
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 5px;">
            <i class="fa-solid fa-triangle-exclamation" style="font-size: 20px;"></i>
            <strong style="font-size: 16px;">${title}</strong>
        </div>
        <p style="margin: 0; font-size: 14px; opacity: 0.9;">${message}</p>
        <small style="display: block; margin-top: 8px; opacity: 0.7;">Click to view details</small>
    `;
    toast.onclick = () => {
        window.location.href = 'emergency.php';
    };
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.remove();
    }, 10000);
}

setInterval(checkEmergencyAlerts, 30000);

document.addEventListener('DOMContentLoaded', function() {
    checkEmergencyAlerts();

    if (Notification && Notification.permission === 'default') {
        Notification.requestPermission();
    }
});
// ========== HERO SLIDER FUNCTIONALITY ==========
let currentHeroSlide = 0;
let heroSlides = [];
let heroDots = [];
let heroInterval;

function initHeroSlider() {
    heroSlides = document.querySelectorAll('.hero-slide');
    heroDots = document.querySelectorAll('.hero-dot');
    
    if (heroSlides.length === 0) return;
    
    // Set all slides - initially hide all except first
    heroSlides.forEach((slide, index) => {
        if (index === 0) {
            slide.style.opacity = '1';
            slide.classList.add('active');
        } else {
            slide.style.opacity = '0';
            slide.classList.remove('active');
        }
    });
    
    // Set dots style
    if (heroDots.length) {
        heroDots.forEach((dot, index) => {
            if (index === 0) {
                dot.style.background = 'white';
                dot.style.width = '30px';
                dot.style.borderRadius = '10px';
            } else {
                dot.style.background = 'rgba(255,255,255,0.5)';
                dot.style.width = '8px';
                dot.style.borderRadius = '50%';
            }
        });
    }
    
    // Add click events to dots
    if (heroDots.length) {
        heroDots.forEach((dot, index) => {
            dot.removeEventListener('click', heroDotClickHandler);
            dot.addEventListener('click', () => heroDotClickHandler(index));
        });
    }
    
    // Start slider
    startHeroSlider();
    
    // Pause slider on hover
    const heroSection = document.querySelector('.hero-section');
    if (heroSection) {
        heroSection.removeEventListener('mouseenter', pauseHeroSlider);
        heroSection.removeEventListener('mouseleave', startHeroSlider);
        heroSection.addEventListener('mouseenter', pauseHeroSlider);
        heroSection.addEventListener('mouseleave', startHeroSlider);
    }
}

function heroDotClickHandler(index) {
    clearInterval(heroInterval);
    showHeroSlide(index);
    startHeroSlider();
}

function showHeroSlide(index) {
    if (!heroSlides.length) return;
    
    heroSlides.forEach((slide, i) => {
        slide.style.opacity = '0';
        slide.classList.remove('active');
        if (heroDots[i]) {
            heroDots[i].style.background = 'rgba(255,255,255,0.5)';
            heroDots[i].style.width = '8px';
            heroDots[i].style.borderRadius = '50%';
        }
    });
    
    heroSlides[index].style.opacity = '1';
    heroSlides[index].classList.add('active');
    if (heroDots[index]) {
        heroDots[index].style.background = 'white';
        heroDots[index].style.width = '30px';
        heroDots[index].style.borderRadius = '10px';
    }
    currentHeroSlide = index;
}

function nextHeroSlide() {
    if (!heroSlides.length) return;
    let next = currentHeroSlide + 1;
    if (next >= heroSlides.length) next = 0;
    showHeroSlide(next);
}

function startHeroSlider() {
    if (heroInterval) clearInterval(heroInterval);
    if (heroSlides.length > 1) {
        heroInterval = setInterval(nextHeroSlide, 3000); // 3 seconds
    }
}

function pauseHeroSlider() {
    if (heroInterval) clearInterval(heroInterval);
}

// Initialize hero slider when page loads
document.addEventListener('DOMContentLoaded', function() {
    initHeroSlider();
});

// ========== SCROLL FUNCTIONALITY ==========

// Smooth scroll to top button
function addScrollToTopButton() {
    // Check if button already exists
    if (document.getElementById('scrollToTop')) return;
    
    const scrollBtn = document.createElement('div');
    scrollBtn.id = 'scrollToTop';
    scrollBtn.innerHTML = '<i class="fa-solid fa-arrow-up"></i>';
    scrollBtn.style.cssText = `
        position: fixed;
        bottom: 110px;
        right: 120px;
        width: 45px;
        height: 45px;
        background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
        color: white;
        border-radius: 50%;
        display: none;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        z-index: 9995;
        box-shadow: 0 5px 20px rgba(37, 99, 235, 0.3);
        transition: all 0.3s ease;
        border: 2px solid rgba(255, 255, 255, 0.3);
        font-size: 20px;
    `;
    
    scrollBtn.onmouseenter = () => {
        scrollBtn.style.transform = 'scale(1.1)';
        scrollBtn.style.boxShadow = '0 8px 25px rgba(37, 99, 235, 0.4)';
    };
    
    scrollBtn.onmouseleave = () => {
        scrollBtn.style.transform = 'scale(1)';
        scrollBtn.style.boxShadow = '0 5px 20px rgba(37, 99, 235, 0.3)';
    };
    
    scrollBtn.onclick = () => {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    };
    
    document.body.appendChild(scrollBtn);
    
    // Show/hide button based on scroll position
    window.addEventListener('scroll', () => {
        if (window.scrollY > 300) {
            scrollBtn.style.display = 'flex';
        } else {
            scrollBtn.style.display = 'none';
        }
    });
}

// Function to scroll to specific section
function scrollToSection(sectionId) {
    const section = document.getElementById(sectionId);
    if (section) {
        section.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
    }
}

// Function to get current scroll position
function getScrollPosition() {
    return {
        x: window.scrollX,
        y: window.scrollY
    };
}

// Function to save scroll position
function saveScrollPosition() {
    sessionStorage.setItem('scrollPosition', window.scrollY);
}

// Function to restore scroll position
function restoreScrollPosition() {
    const savedPosition = sessionStorage.getItem('scrollPosition');
    if (savedPosition) {
        window.scrollTo({
            top: parseInt(savedPosition),
            behavior: 'auto'
        });
        sessionStorage.removeItem('scrollPosition');
    }
}

// Save scroll position before page reload or navigation
window.addEventListener('beforeunload', () => {
    saveScrollPosition();
});

// Restore scroll position on page load
document.addEventListener('DOMContentLoaded', () => {
    restoreScrollPosition();
    addScrollToTopButton();
});

// Calculate and display scroll percentage on page
function updateScrollPercentage() {
    const scrollTop = window.scrollY;
    const docHeight = document.documentElement.scrollHeight - window.innerHeight;
    const scrollPercent = (scrollTop / docHeight) * 100;
    
    // Update scroll percentage in console (optional)
    // console.log(`Scrolled: ${scrollPercent.toFixed(1)}%`);
    
    // You can add a visual indicator if needed
    let scrollIndicator = document.getElementById('scrollIndicator');
    if (!scrollIndicator && scrollPercent > 0) {
        scrollIndicator = document.createElement('div');
        scrollIndicator.id = 'scrollIndicator';
        scrollIndicator.style.cssText = `
            position: fixed;
            top: 50%;
            right: 15px;
            transform: translateY(-50%);
            width: 3px;
            height: 100px;
            background: rgba(37, 99, 235, 0.3);
            border-radius: 10px;
            z-index: 9995;
        `;
        
        const fill = document.createElement('div');
        fill.id = 'scrollFill';
        fill.style.cssText = `
            width: 100%;
            height: 0%;
            background: linear-gradient(180deg, #2563eb, #1e40af);
            border-radius: 10px;
            transition: height 0.3s ease;
        `;
        scrollIndicator.appendChild(fill);
        document.body.appendChild(scrollIndicator);
    }
    
    const scrollFill = document.getElementById('scrollFill');
    if (scrollFill) {
        scrollFill.style.height = scrollPercent + '%';
    }
}

window.addEventListener('scroll', () => {
    updateScrollPercentage();
});

// Keyboard navigation for scrolling
document.addEventListener('keydown', (e) => {
    // Page Up/Down keys
    if (e.key === 'PageUp') {
        e.preventDefault();
        window.scrollBy({
            top: -window.innerHeight / 2,
            behavior: 'smooth'
        });
    } else if (e.key === 'PageDown') {
        e.preventDefault();
        window.scrollBy({
            top: window.innerHeight / 2,
            behavior: 'smooth'
        });
    } else if (e.key === 'Home') {
        e.preventDefault();
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    } else if (e.key === 'End') {
        e.preventDefault();
        window.scrollTo({
            top: document.documentElement.scrollHeight,
            behavior: 'smooth'
        });
    }
});

// Add CSS for responsive scroll adjustments
const scrollResponsiveStyles = `
    @media (max-width: 768px) {
        #scrollToTop {
            bottom: 100px;
            right: 20px;
            width: 40px;
            height: 40px;
            font-size: 18px;
        }
        
        #scrollIndicator {
            right: 5px;
            height: 60px;
        }
    }
`;

const styleSheet = document.createElement('style');
styleSheet.textContent = scrollResponsiveStyles;
document.head.appendChild(styleSheet);

</script>

</body>
</html>