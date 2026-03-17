<?php
require_once 'config.php';
require_once 'functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

// Get user details
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM Users WHERE UserID = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

// Get facilities count
$facilities_count_sql = "SELECT COUNT(*) as count FROM Facilities WHERE Status = 'Open'";
$facilities_count_result = mysqli_query($conn, $facilities_count_sql);
$facilities_count = mysqli_fetch_assoc($facilities_count_result)['count'];

// Get upcoming events
$events_sql = "SELECT * FROM Events WHERE Status = 'Upcoming' AND StartTime > NOW() ORDER BY StartTime ASC LIMIT 3";
$events_stmt = mysqli_prepare($conn, $events_sql);
mysqli_stmt_execute($events_stmt);
$events_result = mysqli_stmt_get_result($events_stmt);

// Get SU Events (Students' Union)
$su_events_sql = "SELECT * FROM su_events WHERE event_time > NOW() ORDER BY event_time ASC LIMIT 2";
$su_events_result = mysqli_query($conn, $su_events_sql);

// Get Gym Status
$gym_sql = "SELECT * FROM gym_status ORDER BY id DESC LIMIT 1";
$gym_result = mysqli_query($conn, $gym_sql);
$gym = mysqli_fetch_assoc($gym_result);

// Get user points
$points = $user['PointsBalance'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Synergy Hub - Dashboard</title>
    <style>
        /* ========================================
           COMPLETE STYLES
           ======================================== */
        
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
        
        /* Background image */
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
        
        /* NAVBAR */
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 32px;
            background: rgba(0,0,0,0.2);
            backdrop-filter: blur(10px);
            position: relative;
            z-index: 1000;
        }
        
        .logo {
            font-size: 24px;
            font-weight: 700;
            color: white;
        }
        
        .logo span {
            color: #22d3ee;
        }
        
        .icons {
            display: flex;
            gap: 20px;
            align-items: center;
            position: relative;
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
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(10px);
            color: white;
            cursor: default;
            transition: all 0.3s;
        }
        
        .points.active {
            transform: scale(1.1);
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .points i {
            color: #22d3ee;
        }
        
        .home-link {
            color: white;
            font-size: 20px;
            text-decoration: none;
        }
        
        /* ========================================
           NOTIFICATION SYSTEM
           ======================================== */
        
        .notify {
            position: relative;
            font-size: 20px;
            color: white;
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
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            background: #f0f9ff;
        }
        
        .notification-item.unread:hover {
            background: #e0f2fe;
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
            color: #667eea;
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
        
        /* ========================================
           SEARCH BAR WITH LIVE SEARCH
           ======================================== */

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
            border: none;
            outline: none;
            box-shadow: 0 10px 30px rgba(0,0,0,.2);
            font-size: 16px;
            background: rgba(255,255,255,0.95);
            transition: all 0.3s;
        }

        .search-wrapper input:focus {
            background: white;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        }

        .search-wrapper input::placeholder {
            color: #94a3b8;
        }

        /* Search Results Dropdown */
        .search-results {
            position: absolute;
            top: 70px;
            left: 50%;
            transform: translateX(-50%);
            width: 55%;
            max-width: 600px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
        
        /* Profile Menu */
        .profile {
            position: relative;
            z-index: 9998;
        }
        
        .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            border: 2px solid #667eea;
        }
        
        .profile-menu {
            position: absolute;
            right: 0;
            top: 55px;
            background: #0f172a;
            border-radius: 12px;
            padding: 15px;
            width: 250px;
            opacity: 0;
            transform: translateY(-15px) scale(.95);
            pointer-events: none;
            transition: all .25s ease;
            z-index: 9998;
            border: 1px solid rgba(255,255,255,0.1);
        }
        
        .profile-menu.show {
            opacity: 1;
            transform: translateY(0) scale(1);
            pointer-events: auto;
        }
        
        .profile-info {
            text-align: center;
            padding: 10px;
        }
        
        .avatar-large {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin-bottom: 10px;
            border: 3px solid #667eea;
        }
        
        #userName {
            color: white;
            font-size: 18px;
            margin: 5px 0;
        }
        
        #userEmail {
            color: rgba(255,255,255,0.7);
            font-size: 14px;
            margin-bottom: 15px;
        }
        
        .edit-btn {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
        }
        
        .logout-btn {
            width: 100%;
            padding: 10px;
            background: #ff4757;
            color: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
        }
        
        /* ========================================
           SYNERGY HUB SIDEBAR - LAS SANATA
           ======================================== */

        .sidebar {
            position: fixed;
            left: -280px;
            top: 0;
            width: 280px;
            height: 100%;
            background: linear-gradient(180deg, #1e2b3c 0%, #0d1a24 100%);
            backdrop-filter: blur(10px);
            transition: 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 9999;
            box-shadow: 4px 0 30px rgba(0, 0, 0, 0.3);
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
            background: radial-gradient(circle, rgba(100, 108, 255, 0.15) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        .sidebar-header h2 {
            color: white;
            font-size: 24px;
            font-weight: 700;
            margin: 0 0 5px 0;
            letter-spacing: -0.5px;
            background: linear-gradient(135deg, #ffffff 0%, #a5b4fc 100%);
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            background: rgba(168, 192, 255, 0.1);
            color: white;
            transform: translateX(5px);
        }

        .sidebar-nav-link:hover i {
            color: #a5b4fc;
        }

        .sidebar-nav-link.active {
            background: linear-gradient(90deg, rgba(168, 192, 255, 0.15) 0%, rgba(168, 192, 255, 0.05) 100%);
            color: white;
            border-left: 3px solid #a5b4fc;
        }

        .sidebar-nav-link.active i {
            color: #a5b4fc;
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
            background: linear-gradient(135deg, #fff, #a5b4fc);
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
        
        /* MAIN LAYOUT */
        .layout {
            display: flex;
            gap: 24px;
            padding: 0 24px 24px 24px;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        /* GRID */
        .grid {
            flex: 3;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
        }
        
        /* CARD */
        .card {
            background: rgba(30, 144, 255, 0.18);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-radius: 18px;
            padding: 30px;
            color: white;
            border: 1px solid rgba(255,255,255,0.25);
            box-shadow: 0 8px 32px rgba(0,0,0,0.25);
            transition: 0.3s;
            text-align: center;
            cursor: pointer;
            text-decoration: none;
        }
        
        .card:hover {
            transform: translateY(-6px);
            background: rgba(30,144,255,0.28);
        }
        
        .icon {
            font-size: 38px;
            display: block;
            margin-bottom: 10px;
            color: #22d3ee;
        }
        
        .card h4 {
            margin: 10px 0 5px;
            font-size: 20px;
        }
        
        .card p {
            opacity: .8;
            font-size: 14px;
            margin: 0;
        }
        
        /* EVENTS PANEL */
        .events {
            flex: 1;
            background: rgba(30, 144, 255, 0.18);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            padding: 24px;
            border-radius: 18px;
            border: 1px solid rgba(255,255,255,0.25);
            box-shadow: 0 8px 32px rgba(0,0,0,0.25);
            color: white;
            height: fit-content;
            position: relative;
            z-index: 1;
        }
        
        .events h3 {
            margin-bottom: 20px;
            font-size: 22px;
            color: #22d3ee;
        }
        
        .events h4 {
            color: #22d3ee;
            margin: 15px 0 10px;
            font-size: 18px;
        }
        
        .events p {
            padding: 12px 0;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .events p:last-child {
            border-bottom: none;
        }
        
        .events strong {
            color: white;
            font-size: 16px;
        }
        
        .events small {
            color: rgba(255,255,255,0.7);
            font-size: 12px;
            display: block;
            margin-top: 4px;
        }
        
        .gym-status-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            color: white;
        }
        
        .gym-label {
            color: rgba(255,255,255,0.7);
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
        
        /* Bus Schedule Styles */
        .route-mini {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            color: white;
            font-size: 14px;
        }
        
        .route-mini:last-child {
            border-bottom: none;
        }
        
        .route-name {
            font-weight: 500;
            color: rgba(255,255,255,0.9);
        }
        
        .route-time {
            color: #fbbf24;
            font-weight: 600;
            background: rgba(251, 191, 36, 0.1);
            padding: 2px 10px;
            border-radius: 30px;
            font-size: 13px;
        }
        
        .route-time.evening {
            color: #22d3ee;
            background: rgba(34, 211, 238, 0.1);
            font-weight: 700;
        }
        
        .on-time {
            color: #10b981;
        }
        
        .bus-schedule-header {
            margin-top: 10px;
            border-top: 2px solid rgba(255,255,255,0.2);
            padding-top: 10px;
        }
        
        .bus-note {
            color: rgba(255,255,255,0.6);
            font-size: 12px;
            text-align: center;
            margin-top: 5px;
        }
        
        /* EDIT MODAL */
        .edit-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
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
            border-radius: 20px;
            width: 90%;
            max-width: 400px;
        }
        
        .edit-box h3 {
            color: #333;
            margin-bottom: 20px;
            font-size: 24px;
        }
        
        .edit-box input {
            width: 100%;
            padding: 12px 15px;
            margin-bottom: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 15px;
            transition: border-color 0.3s;
        }
        
        .edit-box input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .edit-box button {
            padding: 12px 25px;
            margin-right: 10px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            font-size: 15px;
            transition: opacity 0.3s;
        }
        
        .edit-box button:first-of-type {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .edit-box button:first-of-type:hover {
            opacity: 0.9;
        }
        
        .edit-box button:last-of-type {
            background: #e0e0e0;
            color: #333;
        }
        
        .edit-box button:last-of-type:hover {
            background: #d0d0d0;
        }
        
        /* CHAT */
        .chat-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            cursor: pointer;
            box-shadow: 0 5px 20px rgba(0,0,0,0.3);
            z-index: 9996;
            transition: transform 0.3s;
        }
        
        .chat-btn:hover {
            transform: scale(1.1);
        }
        
        .chat-box {
            position: fixed;
            bottom: 100px;
            right: 30px;
            width: 350px;
            height: 500px;
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 5px 30px rgba(0,0,0,0.3);
            display: none;
            flex-direction: column;
            z-index: 9997;
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .chat-box.show {
            display: flex;
        }
        
        .chat-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 600;
        }
        
        .chat-header span {
            cursor: pointer;
            font-size: 20px;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: background 0.3s;
        }
        
        .chat-header span:hover {
            background: rgba(255,255,255,0.2);
        }
        
        .chat-messages {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 10px;
            background: rgba(255,255,255,0.5);
        }
        
        .chat-messages p {
            max-width: 80%;
            padding: 12px 16px;
            border-radius: 18px;
            margin: 0;
            word-wrap: break-word;
            line-height: 1.4;
            font-size: 14px;
        }
        
        .chat-messages .bot {
            background: #f0f0f0;
            color: #333;
            align-self: flex-start;
            border-bottom-left-radius: 5px;
        }
        
        .chat-messages .user {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            align-self: flex-end;
            border-bottom-right-radius: 5px;
        }
        
        .chat-input {
            padding: 15px;
            border-top: 1px solid rgba(0,0,0,0.1);
            display: flex;
            gap: 10px;
            background: white;
        }
        
        .chat-input input {
            flex: 1;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 25px;
            outline: none;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .chat-input input:focus {
            border-color: #667eea;
        }
        
        .chat-input button {
            padding: 12px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 600;
            transition: transform 0.3s;
        }
        
        .chat-input button:hover {
            transform: scale(1.05);
        }
        
        #typingIndicator {
            font-style: italic;
            opacity: 0.7;
        }
        
        .footer {
            position: fixed;
            bottom: 15px;
            width: 100%;
            text-align: center;
            color: white;
            font-size: 13px;
            opacity: .8;
            z-index: 1;
        }
        
        @media (max-width: 1024px) {
            .layout {
                flex-direction: column;
            }
            
            .grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .grid {
                grid-template-columns: 1fr;
            }
            
            .search-wrapper input {
                width: 90%;
            }
            
            .search-results {
                width: 90%;
            }
            
            .notification-dropdown {
                width: 300px;
                right: -10px;
            }
            
            .chat-box {
                width: 300px;
                right: 15px;
            }
        }
    </style>
</head>
<body>

<div class="bg"></div>

<!-- SIDEBAR -->
<div id="sidebar" class="sidebar">
    <!-- Header -->
    <div class="sidebar-header">
        <h2>Synergy Hub</h2>
        <p><i class="fa-solid fa-circle"></i> Connect · Collaborate · Create</p>
    </div>
    
    <!-- User Info -->
    <div class="sidebar-user">
        <div class="sidebar-user-avatar">
            <i class="fa-solid fa-user"></i>
        </div>
        <div class="sidebar-user-info">
            <h4><?php echo htmlspecialchars($user['Name']); ?></h4>
            <p><i class="fa-solid fa-star"></i> <?php echo $points; ?> points</p>
        </div>
    </div>
    
    <!-- Navigation -->
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
    
    <!-- My Clubs Preview -->
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
    
    <!-- Quick Stats -->
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
    
    <!-- Footer -->
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

<!-- Sidebar Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<!-- NAVBAR -->
<header class="navbar">
    <div class="menu-btn" onclick="toggleSidebar()">
        <i class="fa-solid fa-bars"></i>
    </div>
    
    <h1 class="logo">Synergy <span>Hub</span></h1>
    
    <div class="icons">
        <!-- NOTIFICATION BELL -->
        <div class="notify" onclick="toggleNotifications()">
            <i class="fa-solid fa-bell"></i>
            <span class="badge" id="notificationBadge">0</span>
            
            <!-- Notification Dropdown -->
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
            <img id="avatar" src="https://i.pravatar.cc/40?u=<?php echo $user_id; ?>" class="avatar" onclick="toggleProfileMenu(event)">
            
            <div class="profile-menu" id="profileMenu">
                <div class="profile-info">
                    <img id="avatarLarge" src="https://i.pravatar.cc/80?u=<?php echo $user_id; ?>" class="avatar-large">
                    <h4 id="userName"><?php echo htmlspecialchars($user['Name']); ?></h4>
                    <p id="userEmail"><?php echo htmlspecialchars($user['Email']); ?></p>
                </div>
                
                <button class="edit-btn" onclick="openEditProfile()">Edit Profile</button> 
                <button class="logout-btn" onclick="logout()">Logout</button>
            </div>
        </div>
    </div>
</header>

<!-- SEARCH WITH LIVE RESULTS -->
<div class="search-wrapper">
    <input type="text" id="searchInput" placeholder="Search facilities, events, clubs, transport..." autocomplete="off">
    
    <!-- Search Results Dropdown -->
    <div class="search-results" id="searchResults">
        <div class="search-results-header">
            <i class="fa-solid fa-magnifying-glass"></i> Search Results
        </div>
        <div class="search-results-list" id="searchResultsList">
            <!-- Results will appear here -->
        </div>
    </div>
</div>

<!-- MAIN -->
<main class="layout">
    <!-- FEATURE GRID -->
    <section class="grid">
        <a href="qr.html" class="card">
            <i class="fa-solid fa-qrcode icon"></i>
            <h4>QR & GPS</h4>
            <p>Scan QR to navigate campus</p>
        </a>
        
        <a href="https://www.google.com/maps/place/CINEC+Campus+Malabe" target="_blank" class="card">
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
    
    <!-- EVENTS & INFO PANEL -->
    <aside class="events">
        <h3>Live Campus Updates</h3>
        
        <!-- WLV GYM STATUS -->
        <h4>🏋️ WLV Gym</h4>
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
        
        <!-- STUDENTS' UNION EVENTS -->
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
        
        <!-- ========== CINEC BUS SCHEDULE - UPDATED ========== -->
        <h4 style="margin-top: 20px;">🚌 CINEC Bus Schedule</h4>
        
        <!-- Morning Pickup Times -->
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
        
        <!-- Evening Departure -->
        <div class="route-mini bus-schedule-header">
            <span class="route-name" style="font-weight: 700;">🚍 Departure from CINEC</span>
            <span class="route-time evening">5:05 PM</span>
        </div>
        <div class="bus-note">
            <i class="fa-solid fa-clock"></i> All buses depart at 5:05 PM
        </div>
        
        <!-- CAMPUS EVENTS -->
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
        
        <!-- CAMPUS HOTLINE -->
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

<!-- EDIT PROFILE MODAL -->
<div class="edit-modal" id="editModal">
    <div class="edit-box">
        <h3>Edit Profile</h3>
        <input id="editName" placeholder="Name" value="<?php echo htmlspecialchars($user['Name']); ?>">
        <input id="editEmail" placeholder="Email" value="<?php echo htmlspecialchars($user['Email']); ?>">
        <input type="file" id="editPhoto" accept="image/*">
        <button onclick="saveProfile()">Save</button>
        <button onclick="closeEdit()">Cancel</button>
    </div>
</div>

<!-- CHAT BUTTON -->
<div class="chat-btn" onclick="toggleChat()">
    <i class="fa-solid fa-comment"></i>
</div>

<!-- CHAT BOX -->
<div class="chat-box" id="chatBox">
    <div class="chat-header">
        Campus Chat
        <span onclick="toggleChat()">✖</span>
    </div>
    <div class="chat-messages" id="chatMessages">
        <p class="bot">Hi <?php echo htmlspecialchars($user['Name']); ?>! 👋 I'm your campus assistant. How can I help you today?</p>
        <p class="bot">You can ask me about gym status, events, transport, clubs, or games!</p>
    </div>
    <div class="chat-input">
        <input id="chatInput" placeholder="Type message…" onkeypress="if(event.key==='Enter') sendMessage()">
        <button onclick="sendMessage()">Send</button>
    </div>
</div>

<footer class="footer">
    All rights reserved
</footer>

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

// ==================== PROFILE MENU ====================
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

// ==================== EDIT PROFILE ====================
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
    let photoFile = document.getElementById("editPhoto").files[0];
    
    document.getElementById("userName").innerText = name;
    document.getElementById("userEmail").innerText = email;
    
    if (photoFile) {
        let reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById("avatar").src = e.target.result;
            document.getElementById("avatarLarge").src = e.target.result;
        };
        reader.readAsDataURL(photoFile);
    }
    
    closeEdit();
    alert("Profile updated!");
}

// ==================== NOTIFICATION SYSTEM ====================

// Load notifications on page load
document.addEventListener('DOMContentLoaded', function() {
    loadNotifications();
    
    // Refresh notifications every 30 seconds
    setInterval(loadNotifications, 30000);
});

// Toggle notification dropdown
function toggleNotifications() {
    const dropdown = document.getElementById('notificationDropdown');
    dropdown.classList.toggle('show');
    
    // Load notifications when opened
    if (dropdown.classList.contains('show')) {
        loadNotifications();
    }
}

// Close notifications when clicking outside
document.addEventListener('click', function(e) {
    const notify = document.querySelector('.notify');
    const dropdown = document.getElementById('notificationDropdown');
    
    if (notify && dropdown && !notify.contains(e.target)) {
        dropdown.classList.remove('show');
    }
});

// Load notifications from server
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

// Update notification badge
function updateNotificationBadge(count) {
    const badge = document.getElementById('notificationBadge');
    if (count > 0) {
        badge.textContent = count;
        badge.style.display = 'inline';
    } else {
        badge.style.display = 'none';
    }
}

// Display notifications in dropdown
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
                    <div class="notification-title">${notif.title}</div>
                    <div class="notification-message">${notif.message}</div>
                    <div class="notification-time">
                        <i class="fa-regular fa-clock"></i> ${notif.time} • ${notif.date}
                    </div>
                </div>
            </div>
        `;
    });
    
    list.innerHTML = html;
}

// Get icon based on notification type
function getNotificationIcon(type) {
    switch(type) {
        case 'gym': return 'fa-dumbbell';
        case 'event': return 'fa-calendar';
        case 'transport': return 'fa-bus';
        default: return 'fa-bell';
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
        }
    });
}

// Mark all notifications as read
function markAllAsRead() {
    fetch('mark_notification_read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadNotifications();
        }
    });
}

// ==================== LIVE SEARCH ====================

const searchInput = document.getElementById('searchInput');
const searchResults = document.getElementById('searchResults');
const searchResultsList = document.getElementById('searchResultsList');
let searchTimeout;

// Search input event listener
if (searchInput) {
    searchInput.addEventListener('input', function() {
        const query = this.value.trim();
        
        // Clear previous timeout
        clearTimeout(searchTimeout);
        
        // Hide results if query is too short
        if (query.length < 2) {
            searchResults.classList.remove('show');
            return;
        }
        
        // Show loading
        searchResultsList.innerHTML = '<div class="search-loading"><i class="fa-solid fa-spinner"></i> Searching...</div>';
        searchResults.classList.add('show');
        
        // Debounce search
        searchTimeout = setTimeout(() => {
            performSearch(query);
        }, 300);
    });
}

// Perform search
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

// Display search results
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

// Get icon based on result type
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

// Get link based on result type
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

// Escape HTML
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Close search results when clicking outside
document.addEventListener('click', function(e) {
    const wrapper = document.querySelector('.search-wrapper');
    if (wrapper && !wrapper.contains(e.target)) {
        if (searchResults) {
            searchResults.classList.remove('show');
        }
    }
});

// Clear search results when pressing Escape
if (searchInput) {
    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            searchResults.classList.remove('show');
            this.blur();
        }
    });
}

// ==================== CHAT FUNCTIONS ====================
function toggleChat() {
    document.getElementById("chatBox").classList.toggle("show");
}

function sendMessage() {
    let input = document.getElementById("chatInput");
    let message = input.value.trim();
    if (!message) return;
    
    let messagesDiv = document.getElementById("chatMessages");
    
    let userMsg = document.createElement("p");
    userMsg.className = "user";
    userMsg.innerText = message;
    messagesDiv.appendChild(userMsg);
    
    input.value = "";
    messagesDiv.scrollTop = messagesDiv.scrollHeight;
    
    let typingIndicator = document.createElement("p");
    typingIndicator.className = "bot";
    typingIndicator.id = "typingIndicator";
    typingIndicator.innerText = "🤖 Typing...";
    messagesDiv.appendChild(typingIndicator);
    messagesDiv.scrollTop = messagesDiv.scrollHeight;
    
    setTimeout(() => {
        let indicator = document.getElementById("typingIndicator");
        if (indicator) indicator.remove();
        
        let botMsg = document.createElement("p");
        botMsg.className = "bot";
        
        let response = getBotResponse(message);
        botMsg.innerText = response;
        
        messagesDiv.appendChild(botMsg);
        messagesDiv.scrollTop = messagesDiv.scrollHeight;
    }, 1500);
}

function getBotResponse(userMessage) {
    let msg = userMessage.toLowerCase();
    
    if (msg.includes("hello") || msg.includes("hi") || msg.includes("hey")) {
        return "Hello! 👋 How can I help you today?";
    }
    else if (msg.includes("gym")) {
        return "🏋️ WLV Gym is currently Open. Closes at 10:00 PM. Pool is available.";
    }
    else if (msg.includes("event") || msg.includes("events")) {
        return "Check out our Students' Union events and campus events in the panel! 🎉";
    }
    else if (msg.includes("transport") || msg.includes("bus")) {
        return "🚌 CINEC bus schedule: Morning pickups from Negombo (6:15 AM), Gampaha (6:20 AM), Hendala (6:30 AM), Moratuwa (6:20 AM). All buses depart from CINEC at 5:05 PM.";
    }
    else if (msg.includes("club") || msg.includes("clubs")) {
        return "Join Coding Club, Cybersecurity Club, IEEE, or Robotics Club! Check Club Hub for more info. 👥";
    }
    else if (msg.includes("game") || msg.includes("play")) {
        return "Play Memory Game, Math Challenge, or Tic-Tac-Toe in the Game Field! Earn points! 🎮";
    }
    else if (msg.includes("point") || msg.includes("points")) {
        return "You earn points by:\n- Checking in to facilities (+10)\n- Playing games (10-50)\n- Joining clubs (+20)";
    }
    else {
        return "Thanks for your message! Try asking about gym, events, transport, clubs, or games.";
    }
}
</script>

</body>
</html>