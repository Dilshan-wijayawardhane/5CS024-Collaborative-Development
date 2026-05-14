<?php

/**
 * Features:
 *  - Displays all notifications for the logged-in user
 *  - Dynamic fallback data if the notification table doesn't exist or is empty
 *  - Icon and colour coding based on notification type
 *  - Dynamic sidebar with real user club memberships
 */

require_once 'config.php';
require_once 'functions.php';

// Authentication
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Load user points & name
$user_sql = "SELECT PointsBalance, Name, Email, StudentID FROM users WHERE UserID = ?";
$user_stmt = mysqli_prepare($conn, $user_sql);
mysqli_stmt_bind_param($user_stmt, "i", $user_id);
mysqli_stmt_execute($user_stmt);
$user_result = mysqli_stmt_get_result($user_stmt);
$user = mysqli_fetch_assoc($user_result);

// Count open facilities
$facilities_count_sql = "SELECT COUNT(*) as count FROM facilities WHERE Status = 'Open'";
$facilities_count_result = mysqli_query($conn, $facilities_count_sql);
$facilities_count = mysqli_fetch_assoc($facilities_count_result)['count'];

// Get user's club memberships for sidebar
$my_clubs_sql = "SELECT c.*, cm.Role 
                 FROM ClubMemberships cm
                 JOIN Clubs c ON cm.ClubID = c.ClubID
                 WHERE cm.UserID = ? AND cm.Status = 'Active'
                 LIMIT 3";
$my_clubs_stmt = mysqli_prepare($conn, $my_clubs_sql);
mysqli_stmt_bind_param($my_clubs_stmt, "i", $user_id);
mysqli_stmt_execute($my_clubs_stmt);
$my_clubs_result = mysqli_stmt_get_result($my_clubs_stmt);
$my_clubs_array = [];
while($row = mysqli_fetch_assoc($my_clubs_result)) {
    $my_clubs_array[] = $row;
}
$my_clubs_count = count($my_clubs_array);

// Get total clubs count
$total_clubs_sql = "SELECT COUNT(*) as count FROM Clubs WHERE Status = 'Active'";
$total_clubs_result = mysqli_query($conn, $total_clubs_sql);
$total_clubs = mysqli_fetch_assoc($total_clubs_result)['count'];

// Get total events count
$total_events_sql = "SELECT COUNT(*) as count FROM Events WHERE Status IN ('Upcoming', 'Ongoing')";
$total_events_result = mysqli_query($conn, $total_events_sql);
$total_events = mysqli_fetch_assoc($total_events_result)['count'];

// ========== Get notifications from unified table ==========
$unread_count = 0;
$notifications = [];

// Check if notifications table exists
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'notifications'");
$table_exists = mysqli_num_rows($table_check) > 0;

if ($table_exists) {
    // Check if user_id column exists
    $col_check = mysqli_query($conn, "SHOW COLUMNS FROM notifications LIKE 'user_id'");
    if(mysqli_num_rows($col_check) > 0) {
        // Get unread count
        $unread_sql = "SELECT COUNT(*) as unread FROM notifications WHERE user_id = ? AND is_read = 0";
        $unread_stmt = mysqli_prepare($conn, $unread_sql);
        mysqli_stmt_bind_param($unread_stmt, "i", $user_id);
        mysqli_stmt_execute($unread_stmt);
        $unread_result = mysqli_stmt_get_result($unread_stmt);
        $unread_count = mysqli_fetch_assoc($unread_result)['unread'];
        
        // Get all notifications
        $sql = "SELECT notification_id, title, message, type, is_read, created_at 
                FROM notifications 
                WHERE user_id = ? 
                ORDER BY created_at DESC";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        while($row = mysqli_fetch_assoc($result)) {
            $notifications[] = [
                'id' => $row['notification_id'],
                'title' => $row['title'],
                'message' => $row['message'],
                'type' => $row['type'],
                'is_read' => $row['is_read'],
                'created_at' => $row['created_at']
            ];
        }
    }
}

$points = $user['PointsBalance'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - Synergy Hub</title>
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
        }
        
        .home-link:hover {
            color: #2c7da0;
        }
        
        /* Notification Icon with Badge */
        .notify {
            position: relative;
            cursor: pointer;
        }
        
        .notify i {
            font-size: 22px;
            color: #1e4a76;
        }
        
        .badge {
            position: absolute;
            top: -8px;
            right: -12px;
            background: #ef4444;
            color: white;
            font-size: 10px;
            font-weight: 600;
            padding: 2px 6px;
            border-radius: 30px;
            min-width: 18px;
            text-align: center;
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
        }

        .sidebar-nav-link:hover {
            background: #e0f2fe;
            color: #1e4a76;
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
        }

        .sidebar-club-item:last-child {
            margin-bottom: 0;
        }

        .sidebar-club-item h5 {
            color: #1e293b;
            font-size: 14px;
            margin: 0 0 4px 0;
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
        
        /* Main Container */
        .container {
            padding: 30px;
            max-width: 900px;
            margin: 0 auto;
        }
        
        .page-title {
            color: #1e4a76;
            font-size: 28px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-left: 5px solid #2c7da0;
            padding-left: 15px;
        }
        
        .clear-btn {
            padding: 10px 20px;
            background: linear-gradient(135deg, #1e4a76 0%, #2c7da0 100%);
            border: none;
            border-radius: 30px;
            color: white;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .clear-btn:hover {
            transform: scale(1.02);
            box-shadow: 0 5px 15px rgba(30, 74, 118, 0.3);
        }
        
        .notifications-list {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
        }
        
        .notification-item-main {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            padding: 20px;
            border-bottom: 1px solid #e2e8f0;
            transition: background 0.3s;
            cursor: pointer;
        }
        
        .notification-item-main:hover {
            background: #f8fafc;
        }
        
        .notification-item-main:last-child {
            border-bottom: none;
        }
        
        .notification-item-main.unread {
            background: #e0f2fe;
        }
        
        .notification-item-main.unread:hover {
            background: #bae6fd;
        }
        
        .notification-icon-main {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            flex-shrink: 0;
        }
        
        .notification-icon-main.gym {
            background: #dbeafe;
            color: #1e4a76;
        }
        
        .notification-icon-main.event {
            background: #dcfce7;
            color: #16a34a;
        }
        
        .notification-icon-main.transport {
            background: #fed7aa;
            color: #ea580c;
        }
        
        .notification-icon-main.general {
            background: #e0f2fe;
            color: #2c7da0;
        }
        
        .notification-content-main {
            flex: 1;
        }
        
        .notification-title-main {
            font-weight: 700;
            color: #1e293b;
            font-size: 16px;
            margin-bottom: 5px;
        }
        
        .notification-message-main {
            color: #475569;
            font-size: 14px;
            margin-bottom: 8px;
            line-height: 1.5;
        }
        
        .notification-time-main {
            color: #64748b;
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .no-notifications {
            padding: 60px 20px;
            text-align: center;
            color: #64748b;
        }
        
        .no-notifications i {
            font-size: 48px;
            color: #94a3b8;
            margin-bottom: 15px;
        }
        
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 25px;
            color: #2c7da0;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: color 0.3s;
        }
        
        .back-btn:hover {
            color: #1e4a76;
        }
        
        /* Notification Dropdown */
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
            z-index: 1000;
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }
        
        .notification-dropdown.show {
            display: block;
        }
        
        .notification-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            background: linear-gradient(135deg, #1e4a76 0%, #2c7da0 100%);
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
            background: #e0f2fe;
        }
        
        .notification-item.unread:hover {
            background: #bae6fd;
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
            color: #1e4a76;
        }
        
        .notification-icon.event {
            background: #dcfce7;
            color: #16a34a;
        }
        
        .notification-icon.transport {
            background: #fed7aa;
            color: #ea580c;
        }
        
        .notification-icon.general {
            background: #e0f2fe;
            color: #2c7da0;
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
        
        .notification-footer {
            padding: 12px 20px;
            text-align: center;
            border-top: 1px solid #e5e7eb;
            background: #f9fafb;
        }
        
        .notification-footer a {
            color: #2c7da0;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
        }
        
        .notification-footer a:hover {
            color: #1e4a76;
            text-decoration: underline;
        }
        
        .loading-notifications {
            padding: 40px;
            text-align: center;
            color: #94a3b8;
        }
        
        /* Profile Menu */
        .profile {
            position: relative;
        }

        .avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            cursor: pointer;
            border: 2px solid #2c7da0;
            transition: all 0.3s ease;
            object-fit: cover;
        }

        .avatar:hover {
            transform: scale(1.08);
            box-shadow: 0 0 0 3px rgba(44, 125, 160, 0.2);
        }

        .profile-menu {
            position: absolute;
            right: 0;
            top: 55px;
            background: white;
            border-radius: 20px;
            width: 300px;
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
            background: linear-gradient(135deg, #1e4a76 0%, #2c7da0 100%);
            padding: 25px 20px;
            text-align: center;
        }

        .avatar-large {
            width: 85px;
            height: 85px;
            border-radius: 50%;
            border: 4px solid white;
            margin-bottom: 12px;
            object-fit: cover;
        }

        .profile-header h4 {
            color: white;
            font-size: 18px;
            margin: 5px 0;
        }

        .profile-header p {
            color: rgba(255, 255, 255, 0.85);
            font-size: 13px;
            margin: 0;
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
            color: #1e4a76;
            font-size: 22px;
            font-weight: 700;
        }

        .profile-stat-label {
            color: #64748b;
            font-size: 11px;
            text-transform: uppercase;
            margin-top: 4px;
        }

        .profile-actions {
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            background: white;
        }

        .edit-profile-btn, .logout-profile-btn {
            width: 100%;
            padding: 12px;
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

        .edit-profile-btn {
            background: linear-gradient(135deg, #1e4a76 0%, #2c7da0 100%);
            color: white;
        }

        .edit-profile-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(30, 74, 118, 0.3);
        }

        .logout-profile-btn {
            background: #ef4444;
            color: white;
        }

        .logout-profile-btn:hover {
            transform: translateY(-2px);
            background: #dc2626;
        }

        /* Edit Modal */
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
            color: #2c7da0;
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
            border: 3px solid #2c7da0;
            cursor: pointer;
        }

        .edit-box input {
            width: 100%;
            padding: 14px 16px;
            margin-bottom: 15px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 15px;
        }

        .edit-box input:focus {
            outline: none;
            border-color: #2c7da0;
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
        }

        .edit-box-buttons button:first-child {
            background: linear-gradient(135deg, #1e4a76 0%, #2c7da0 100%);
            color: white;
        }

        .edit-box-buttons button:last-child {
            background: #e2e8f0;
            color: #1e293b;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }
            
            .notification-dropdown {
                width: 320px;
                right: -80px;
            }
        }
    </style>
</head>
<body>

<!-- SIDEBAR -->
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
            <a href="index.php" class="sidebar-nav-link">
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
            <a href="qr.html" class="sidebar-nav-link">
                <i class="fa-solid fa-qrcode"></i>
                <span>QR Scanner</span>
            </a>
        </li>
        <li class="sidebar-nav-item">
            <a href="notifications.php" class="sidebar-nav-link active">
                <i class="fa-solid fa-bell"></i>
                <span>Notifications</span>
                <?php if($unread_count > 0): ?>
                <span class="sidebar-badge" id="sidebarNotificationBadge"><?php echo $unread_count; ?></span>
                <?php endif; ?>
            </a>
        </li>
    </ul>
    
    <div class="sidebar-divider"></div>
    
    <div class="sidebar-section-title">MY CLUBS</div>
    
    <div class="sidebar-club-preview">
        <h4><i class="fa-regular fa-star"></i> Active Clubs</h4>
        <?php if(!empty($my_clubs_array)): ?>
            <?php foreach($my_clubs_array as $club): ?>
                <div class="sidebar-club-item">
                    <h5><?php echo htmlspecialchars($club['Name']); ?></h5>
                    <p><?php echo htmlspecialchars(substr($club['Description'], 0, 30)) . '...'; ?></p>
                    <span class="sidebar-club-tag"><?php echo htmlspecialchars($club['Category']); ?></span>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="sidebar-club-item">
                <p style="color: #64748b; text-align: center;">No clubs joined yet</p>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="sidebar-stats">
        <div class="sidebar-stat-item">
            <div class="sidebar-stat-value"><?php echo $my_clubs_count; ?></div>
            <div class="sidebar-stat-label">My Clubs</div>
        </div>
        <div class="sidebar-stat-item">
            <div class="sidebar-stat-value"><?php echo $total_events; ?></div>
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
    
    <h1 class="logo">Synergy <span>Hub</span> - Notifications</h1>
    
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
                    <div class="loading-notifications"><i class="fa-solid fa-spinner fa-spin"></i> Loading...</div>
                </div>
                <div class="notification-footer">
                    <a href="notifications.php">View all notifications</a>
                </div>
            </div>
        </div>
        
        <div class="points">
            <i class="fa-solid fa-star"></i>
            <span><?php echo $points; ?></span>
        </div>
        
        <div class="profile">
            <img id="avatar" src="https://i.pravatar.cc/42?u=<?php echo $user_id; ?>" class="avatar" onclick="toggleProfileMenu(event)">
            
            <div class="profile-menu" id="profileMenu">
                <div class="profile-header">
                    <img id="avatarLarge" src="https://i.pravatar.cc/85?u=<?php echo $user_id; ?>" class="avatar-large">
                    <h4 id="userName"><?php echo htmlspecialchars($user['Name']); ?></h4>
                    <p id="userEmail"><?php echo htmlspecialchars($user['Email']); ?></p>
                </div>
                
                <div class="profile-stats">
                    <div class="profile-stat">
                        <div class="profile-stat-value"><?php echo $points; ?></div>
                        <div class="profile-stat-label">Points</div>
                    </div>
                    <div class="profile-stat">
                        <div class="profile-stat-value"><?php echo $my_clubs_count; ?></div>
                        <div class="profile-stat-label">Clubs</div>
                    </div>
                    <div class="profile-stat">
                        <div class="profile-stat-value"><?php echo $total_events; ?></div>
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

<div class="container">
    <div class="page-title">
        <span><i class="fa-regular fa-bell"></i> All Notifications</span>
        <button class="clear-btn" onclick="markAllAsRead()"><i class="fa-regular fa-circle-check"></i> Mark all as read</button>
    </div>
    
    <div class="notifications-list" id="notificationsContainer">
        <?php if(!empty($notifications)): ?>
            <?php foreach($notifications as $notif): ?>
                <?php
                $type = $notif['type'];
                $icon = 'fa-bell';
                $type_class = 'general';
                
                if($type == 'event') {
                    $icon = 'fa-calendar';
                    $type_class = 'event';
                } else if($type == 'transport') {
                    $icon = 'fa-bus';
                    $type_class = 'transport';
                } else if($type == 'gym') {
                    $icon = 'fa-dumbbell';
                    $type_class = 'gym';
                } else if($type == 'club') {
                    $icon = 'fa-users';
                    $type_class = 'general';
                } else if($type == 'announcement') {
                    $icon = 'fa-bullhorn';
                    $type_class = 'general';
                } else if($type == 'reminder') {
                    $icon = 'fa-clock';
                    $type_class = 'general';
                }
                
                $unread_class = $notif['is_read'] ? '' : 'unread';
                $time = date('g:i A', strtotime($notif['created_at']));
                $date = date('M d, Y', strtotime($notif['created_at']));
                ?>
                <div class="notification-item-main <?php echo $unread_class; ?>" onclick="markAsRead(<?php echo $notif['id']; ?>)">
                    <div class="notification-icon-main <?php echo $type_class; ?>">
                        <i class="fa-solid <?php echo $icon; ?>"></i>
                    </div>
                    <div class="notification-content-main">
                        <div class="notification-title-main"><?php echo htmlspecialchars($notif['title']); ?></div>
                        <div class="notification-message-main"><?php echo htmlspecialchars($notif['message']); ?></div>
                        <div class="notification-time-main">
                            <i class="fa-regular fa-clock"></i> 
                            <?php echo $time; ?> • <?php echo $date; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-notifications">
                <i class="fa-regular fa-bell-slash"></i>
                <p>No notifications yet</p>
                <p style="font-size: 13px; margin-top: 10px;">When you get notifications, they'll appear here</p>
            </div>
        <?php endif; ?>
    </div>
    
    <a href="index.php" class="back-btn">
        <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
    </a>
</div>

<!-- Edit Profile Modal -->
<div class="edit-modal" id="editModal">
    <div class="edit-box">
        <h3><i class="fa-regular fa-user"></i> Edit Profile</h3>
        
        <div class="edit-avatar-preview">
            <img id="editAvatarPreview" src="https://i.pravatar.cc/100?u=<?php echo $user_id; ?>" onclick="document.getElementById('editPhoto').click()">
        </div>
        
        <input type="text" id="editName" placeholder="Full Name" value="<?php echo htmlspecialchars($user['Name']); ?>">
        <input type="email" id="editEmail" placeholder="Email Address" value="<?php echo htmlspecialchars($user['Email']); ?>">
        
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

<script>
// Sidebar Functions
function toggleSidebar() {
    const sidebar = document.getElementById("sidebar");
    const overlay = document.getElementById("sidebarOverlay");
    const menuBtn = document.querySelector(".menu-btn");
    
    if(sidebar.style.left === "0px") {
        sidebar.style.left = "-280px";
        overlay.classList.remove("active");
        if(menuBtn) menuBtn.classList.remove("active");
    } else {
        sidebar.style.left = "0px";
        overlay.classList.add("active");
        if(menuBtn) menuBtn.classList.add("active");
    }
}

document.addEventListener("click", function(e) {
    const sidebar = document.getElementById("sidebar");
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

// Profile Functions
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
}

function closeEdit() {
    document.getElementById("editModal").classList.remove("show");
}

function saveProfile() {
    let name = document.getElementById("editName").value;
    let email = document.getElementById("editEmail").value;
    
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
    
    closeEdit();
    alert("Profile updated successfully!");
}

// ========== NOTIFICATION FUNCTIONS ==========

// Load notifications for dropdown
function loadNotifications() {
    fetch('get_notifications.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateNotificationBadge(data.unread_count);
                displayDropdownNotifications(data.notifications);
            }
        })
        .catch(error => console.error('Error loading notifications:', error));
}

// Update badge count
function updateNotificationBadge(count) {
    const badge = document.getElementById('notificationBadge');
    const sidebarBadge = document.getElementById('sidebarNotificationBadge');
    
    if (count > 0) {
        if (badge) {
            badge.textContent = count;
            badge.style.display = 'inline-flex';
            badge.style.alignItems = 'center';
            badge.style.justifyContent = 'center';
        }
        if (sidebarBadge) {
            sidebarBadge.textContent = count;
            sidebarBadge.style.display = 'inline-block';
        }
    } else {
        if (badge) {
            badge.textContent = '0';
            badge.style.display = 'inline-flex';
        }
        if (sidebarBadge) {
            sidebarBadge.style.display = 'none';
        }
    }
}

// Display notifications in dropdown
function displayDropdownNotifications(notifications) {
    const list = document.getElementById('notificationList');
    
    if (!list) return;
    
    if (!notifications || notifications.length === 0) {
        list.innerHTML = '<div class="no-notifications">No notifications</div>';
        return;
    }
    
    let html = '';
    notifications.forEach(notif => {
        const unreadClass = notif.is_read ? '' : 'unread';
        let iconClass = 'fa-bell';
        let typeClass = 'general';
        
        if (notif.type === 'event') {
            iconClass = 'fa-calendar';
            typeClass = 'event';
        } else if (notif.type === 'transport') {
            iconClass = 'fa-bus';
            typeClass = 'transport';
        } else if (notif.type === 'gym') {
            iconClass = 'fa-dumbbell';
            typeClass = 'gym';
        } else if (notif.type === 'club') {
            iconClass = 'fa-users';
            typeClass = 'general';
        } else if (notif.type === 'announcement') {
            iconClass = 'fa-bullhorn';
            typeClass = 'general';
        } else if (notif.type === 'reminder') {
            iconClass = 'fa-clock';
            typeClass = 'general';
        }
        
        html += `
            <div class="notification-item ${unreadClass}" onclick="markAsRead(${notif.id})">
                <div class="notification-icon ${typeClass}">
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

// Toggle notifications dropdown
function toggleNotifications() {
    const dropdown = document.getElementById('notificationDropdown');
    if (dropdown) {
        dropdown.classList.toggle('show');
        if (dropdown.classList.contains('show')) {
            loadNotifications();
        }
    }
}

// Mark single notification as read
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
            location.reload();
        }
    })
    .catch(error => {
        console.error('Error marking as read:', error);
        location.reload();
    });
}

// Mark all notifications as read
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
            location.reload();
        }
    })
    .catch(error => {
        console.error('Error marking all as read:', error);
        location.reload();
    });
}

// Escape HTML to prevent XSS
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Close dropdown when clicking outside
document.addEventListener('click', function(e) {
    const notify = document.querySelector('.notify');
    const dropdown = document.getElementById('notificationDropdown');
    
    if (notify && dropdown && !notify.contains(e.target)) {
        dropdown.classList.remove('show');
    }
});

// Load on page load
document.addEventListener('DOMContentLoaded', function() {
    loadNotifications();
    setInterval(loadNotifications, 30000);
});
</script>

</body>
</html>