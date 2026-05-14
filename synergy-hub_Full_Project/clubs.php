<?php
require_once 'config.php';
require_once 'functions.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user's club memberships
$my_clubs_sql = "SELECT c.*, cm.Role 
                 FROM ClubMemberships cm
                 JOIN Clubs c ON cm.ClubID = c.ClubID
                 WHERE cm.UserID = ? AND cm.Status = 'Active'";
$my_clubs_stmt = mysqli_prepare($conn, $my_clubs_sql);
mysqli_stmt_bind_param($my_clubs_stmt, "i", $user_id);
mysqli_stmt_execute($my_clubs_stmt);
$my_clubs_result = mysqli_stmt_get_result($my_clubs_stmt);

// Get user's pending/rejected requests
$requests_sql = "SELECT ClubID, Status, AdminNotes, RequestDate, ReviewedAt 
                 FROM JoinRequests 
                 WHERE UserID = ? AND Status IN ('Pending', 'Rejected')
                 ORDER BY RequestDate DESC";
$requests_stmt = mysqli_prepare($conn, $requests_sql);
mysqli_stmt_bind_param($requests_stmt, "i", $user_id);
mysqli_stmt_execute($requests_stmt);
$requests_result = mysqli_stmt_get_result($requests_stmt);
$user_requests = [];
while($row = mysqli_fetch_assoc($requests_result)) {
    $user_requests[$row['ClubID']] = $row;
}

// Get all active clubs
$clubs_sql = "SELECT c.*, u.Name as LeaderName 
              FROM Clubs c 
              LEFT JOIN Users u ON c.LeaderID = u.UserID 
              WHERE c.Status = 'Active'
              ORDER BY c.Name";
$clubs_result = mysqli_query($conn, $clubs_sql);

// Get user points and name
$user_sql = "SELECT PointsBalance, Name FROM Users WHERE UserID = ?";
$user_stmt = mysqli_prepare($conn, $user_sql);
mysqli_stmt_bind_param($user_stmt, "i", $user_id);
mysqli_stmt_execute($user_stmt);
$user_result = mysqli_stmt_get_result($user_stmt);
$user = mysqli_fetch_assoc($user_result);

// Get facilities count
$facilities_count_sql = "SELECT COUNT(*) as count FROM Facilities WHERE Status = 'Open'";
$facilities_count_result = mysqli_query($conn, $facilities_count_sql);
$facilities_count = mysqli_fetch_assoc($facilities_count_result)['count'];

$my_clubs_array = [];
while($row = mysqli_fetch_assoc($my_clubs_result)) {
    $my_clubs_array[$row['ClubID']] = $row;
}
$my_clubs_count = count($my_clubs_array);

$all_clubs_array = [];
while($row = mysqli_fetch_assoc($clubs_result)) {
    $all_clubs_array[] = $row;
}
$total_clubs_count = count($all_clubs_array);

// Helper function to get button state
function getClubButtonState($club_id, $my_clubs, $requests) {
    if (isset($my_clubs[$club_id])) {
        return ['type' => 'member', 'text' => 'Leave Club', 'disabled' => false, 'icon' => 'fa-sign-out-alt'];
    }
    if (isset($requests[$club_id])) {
        $request = $requests[$club_id];
        if ($request['Status'] == 'Pending') {
            return ['type' => 'pending', 'text' => 'Request Pending', 'disabled' => true, 'icon' => 'fa-clock'];
        } elseif ($request['Status'] == 'Rejected') {
            $rejected_date = strtotime($request['ReviewedAt']);
            $days_since = (time() - $rejected_date) / (60 * 60 * 24);
            if ($days_since >= 7) {
                return ['type' => 'request', 'text' => 'Request Again', 'disabled' => false, 'icon' => 'fa-paper-plane'];
            } else {
                $days_left = ceil(7 - $days_since);
                return ['type' => 'rejected', 'text' => "Rejected - Try after {$days_left} days", 'disabled' => true, 'icon' => 'fa-ban'];
            }
        }
    }
    return ['type' => 'request', 'text' => 'Request to Join', 'disabled' => false, 'icon' => 'fa-paper-plane'];
}

// Club images mapping
$club_images = [
    'Coding' => 'https://images.pexels.com/photos/1181263/pexels-photo-1181263.jpeg?auto=compress&cs=tinysrgb&w=600',
    'Cyber' => 'https://images.pexels.com/photos/5380642/pexels-photo-5380642.jpeg?auto=compress&cs=tinysrgb&w=600',
    'IEEE' => 'IEEE.jfif',
    'Robotics' => 'robo.jpg',
    'Music' => 'https://images.pexels.com/photos/167636/pexels-photo-167636.jpeg?auto=compress&cs=tinysrgb&w=600',
    'Dance' => 'https://images.pexels.com/photos/2661869/pexels-photo-2661869.jpeg?auto=compress&cs=tinysrgb&w=600',
    'Art' => 'https://images.pexels.com/photos/1779487/pexels-photo-1779487.jpeg?auto=compress&cs=tinysrgb&w=600',
    'Sports' => 'https://images.pexels.com/photos/248547/pexels-photo-248547.jpeg?auto=compress&cs=tinysrgb&w=600',
    'default' => 'https://images.pexels.com/photos/256490/pexels-photo-256490.jpeg?auto=compress&cs=tinysrgb&w=600'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Club Hub - Synergy Hub</title>
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
        
        /* CLUB CONTAINER */
        .club-container {
            padding: 30px;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .points-badge {
            background: white;
            color: #1e4a76;
            padding: 12px 25px;
            border-radius: 50px;
            display: inline-block;
            margin-bottom: 30px;
            font-weight: 600;
            font-size: 16px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }
        
        .points-badge i {
            color: #fbbf24;
            margin-right: 8px;
        }
        
        .section-title {
            color: #1e4a76;
            font-size: 28px;
            margin: 40px 0 20px;
            border-left: 5px solid #2c7da0;
            padding-left: 15px;
        }
        
        /* Requests Section */
        .my-requests-section {
            margin-bottom: 40px;
        }
        
        .request-card {
            background: white;
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 15px;
            border-left: 4px solid;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }
        
        .request-card.pending {
            border-left-color: #f59e0b;
        }
        
        .request-card.rejected {
            border-left-color: #ef4444;
        }
        
        .request-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            flex-wrap: wrap;
        }
        
        .request-club {
            font-size: 18px;
            font-weight: 600;
            color: #1e293b;
        }
        
        .request-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-pending {
            background: #f59e0b;
            color: white;
        }
        
        .status-rejected {
            background: #ef4444;
            color: white;
        }
        
        .request-date {
            color: #64748b;
            font-size: 12px;
            margin-top: 5px;
        }
        
        .request-notes {
            color: #475569;
            font-size: 13px;
            margin-top: 10px;
            padding: 10px;
            background: #f8fafc;
            border-radius: 8px;
        }
        
        .cancel-request-btn {
            background: #ef4444;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 12px;
            margin-top: 10px;
            transition: all 0.3s;
        }
        
        .cancel-request-btn:hover {
            background: #dc2626;
            transform: scale(1.02);
        }
        
        /* Clubs Grid */
        .clubs-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
        }
        
        .club-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.3s;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
        }
        
        .club-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            border-color: #2c7da0;
        }
        
        /* CARD IMAGE SECTION */
        .card-image {
            width: 100%;
            height: 180px;
            background-size: cover;
            background-position: center;
            position: relative;
        }
        
        .card-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
            padding: 20px;
        }
        
        .club-name-card {
            color: white;
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .club-category-card {
            color: #22d3ee;
            font-size: 13px;
            display: inline-block;
            background: rgba(0,0,0,0.5);
            padding: 2px 10px;
            border-radius: 20px;
        }
        
        .role-badge {
            background: #10b981;
            color: white;
            padding: 2px 12px;
            border-radius: 30px;
            font-size: 11px;
            font-weight: 600;
            margin-left: 10px;
            text-transform: uppercase;
            display: inline-block;
        }
        
        /* CARD CONTENT */
        .card-content {
            padding: 20px;
        }
        
        .club-description {
            color: #475569;
            margin: 0 0 15px 0;
            line-height: 1.6;
            font-size: 14px;
        }
        
        .club-meta {
            display: flex;
            justify-content: space-between;
            color: #64748b;
            font-size: 13px;
            margin: 15px 0;
            padding: 10px 0;
            border-top: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .club-meta i {
            color: #2c7da0;
            margin-right: 5px;
        }
        
        .join-btn, .request-btn, .leave-btn {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 12px;
            color: white;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .join-btn {
            background: linear-gradient(135deg, #1e4a76 0%, #2c7da0 100%);
        }
        
        .join-btn:hover:not(:disabled) {
            transform: scale(1.02);
            box-shadow: 0 5px 15px rgba(30, 74, 118, 0.3);
        }
        
        .join-btn:disabled, .request-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .leave-btn {
            background: #ef4444;
        }
        
        .leave-btn:hover {
            transform: scale(1.02);
            background: #dc2626;
        }
        
        .my-clubs-section {
            margin-bottom: 40px;
        }
        
        /* Toast Animation */
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        .toast-notification {
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 12px 24px;
            border-radius: 8px;
            color: white;
            z-index: 10000;
            animation: slideIn 0.3s ease;
        }
        
        /* ======================================== */
        /* EVENTS SECTION STYLES */
        /* ======================================== */
        .club-events-section {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 2px solid rgba(0, 0, 0, 0.08);
        }

        .events-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }

        .event-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.3s;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
        }

        .event-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            border-color: #2c7da0;
        }

        .event-image {
            height: 160px;
            background-size: cover;
            background-position: center;
            position: relative;
        }

        .event-category {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }

        .live-badge {
            position: absolute;
            top: 15px;
            left: 15px;
            background: #ef4444;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.6; }
        }

        .trending-badge {
            position: absolute;
            top: 15px;
            left: 15px;
            background: #f59e0b;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }

        .event-content {
            padding: 20px;
        }

        .event-title {
            font-size: 18px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 10px;
        }

        .event-datetime {
            color: #2c7da0;
            font-size: 13px;
            margin-bottom: 8px;
        }

        .event-datetime i {
            margin-right: 5px;
        }

        .event-location {
            color: #64748b;
            font-size: 13px;
            margin-bottom: 12px;
        }

        .event-location i {
            margin-right: 5px;
            color: #2c7da0;
        }

        .event-description {
            color: #475569;
            font-size: 14px;
            line-height: 1.5;
            margin-bottom: 15px;
        }

        .event-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 15px;
            border-top: 1px solid #e2e8f0;
        }

        .event-price {
            font-size: 18px;
            font-weight: 700;
            color: #1e4a76;
        }

        .spots-left {
            font-size: 11px;
            color: #64748b;
            margin-top: 3px;
        }

        .event-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .like-btn {
            background: none;
            border: none;
            color: #94a3b8;
            font-size: 16px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s;
            padding: 8px 12px;
            border-radius: 30px;
        }

        .like-btn:hover {
            background: #f1f5f9;
        }

        .like-btn.liked {
            color: #ef4444;
        }

        .like-count {
            font-size: 12px;
        }

        .details-btn {
            background: linear-gradient(135deg, #1e4a76 0%, #2c7da0 100%);
            color: white;
            padding: 8px 16px;
            border-radius: 30px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .details-btn:hover {
            transform: scale(1.02);
            box-shadow: 0 5px 15px rgba(30, 74, 118, 0.3);
        }

        .view-all-events {
            text-align: center;
            margin-top: 30px;
        }

        .view-all-btn {
            display: inline-block;
            background: transparent;
            border: 2px solid #2c7da0;
            color: #2c7da0;
            padding: 12px 30px;
            border-radius: 40px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }

        .view-all-btn:hover {
            background: #2c7da0;
            color: white;
            transform: translateY(-2px);
        }

        .no-events {
            text-align: center;
            padding: 60px;
            background: white;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
        }

        .no-events i {
            font-size: 48px;
            color: #94a3b8;
            margin-bottom: 15px;
        }

        .no-events p {
            color: #64748b;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .clubs-grid {
                grid-template-columns: 1fr;
            }
            
            .club-container {
                padding: 20px;
            }
            
            .navbar {
                flex-direction: column;
                gap: 10px;
            }
            
            .icons {
                width: 100%;
                justify-content: center;
            }
            
            .events-grid {
                grid-template-columns: 1fr;
            }
            
            .event-footer {
                flex-direction: column;
                gap: 15px;
            }
            
            .event-actions {
                width: 100%;
                justify-content: space-between;
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
            <h4><?php echo htmlspecialchars($user['Name']); ?></h4>
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
            <a href="clubs.php" class="sidebar-nav-link active">
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
        <?php 
        if(!empty($my_clubs_array)):
            $preview_count = 0;
            foreach($my_clubs_array as $preview_club):
                if($preview_count >= 2) break;
        ?>
        <div class="sidebar-club-item">
            <h5><?php echo htmlspecialchars($preview_club['Name']); ?></h5>
            <p><?php echo htmlspecialchars(substr($preview_club['Description'], 0, 30)) . '...'; ?></p>
            <span class="sidebar-club-tag"><?php echo htmlspecialchars($preview_club['Category']); ?></span>
        </div>
        <?php 
                $preview_count++;
            endforeach;
        else:
        ?>
        <div style="color: #64748b; text-align: center; padding: 10px; font-size: 12px;">
            No clubs joined yet
        </div>
        <?php endif; ?>
    </div>
    
    <div class="sidebar-stats">
        <div class="sidebar-stat-item">
            <div class="sidebar-stat-value"><?php echo $my_clubs_count; ?></div>
            <div class="sidebar-stat-label">My Clubs</div>
        </div>
        <div class="sidebar-stat-item">
            <div class="sidebar-stat-value"><?php echo $total_clubs_count; ?></div>
            <div class="sidebar-stat-label">Total Clubs</div>
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
    
    <h1 class="logo">Synergy <span>Hub</span> - Club Hub</h1>
    
    <div class="icons">
        <div class="points">
            <i class="fa-solid fa-star"></i>
            <span id="pointsDisplay"><?php echo $user['PointsBalance']; ?></span>
        </div>
        <a href="index.php" class="home-link">
            <i class="fa-solid fa-home"></i>
        </a>
    </div>
</header>

<div class="club-container">
    
    <div class="points-badge">
        <i class="fa-solid fa-star"></i> Your Points: <?php echo $user['PointsBalance']; ?> ⭐
    </div>
    
    <!-- MY REQUESTS SECTION -->
    <?php if(!empty($user_requests)): ?>
    <div class="my-requests-section">
        <h2 class="section-title"><i class="fa-regular fa-clock"></i> My Join Requests</h2>
        <?php 
        foreach($user_requests as $club_id => $request):
            $club_sql = "SELECT Name FROM Clubs WHERE ClubID = ?";
            $club_stmt = mysqli_prepare($conn, $club_sql);
            mysqli_stmt_bind_param($club_stmt, "i", $club_id);
            mysqli_stmt_execute($club_stmt);
            $club_result = mysqli_stmt_get_result($club_stmt);
            $club_name = mysqli_fetch_assoc($club_result)['Name'] ?? 'Unknown Club';
        ?>
        <div class="request-card <?php echo strtolower($request['Status']); ?>">
            <div class="request-header">
                <span class="request-club"><i class="fa-solid fa-users"></i> <?php echo htmlspecialchars($club_name); ?></span>
                <span class="request-status status-<?php echo strtolower($request['Status']); ?>">
                    <?php echo $request['Status']; ?>
                </span>
            </div>
            <div class="request-date">
                <i class="fa-regular fa-calendar"></i> Requested: <?php echo date('M d, Y', strtotime($request['RequestDate'])); ?>
            </div>
            <?php if($request['Status'] == 'Rejected' && !empty($request['AdminNotes'])): ?>
            <div class="request-notes">
                <i class="fa-regular fa-message"></i> Reason: <?php echo htmlspecialchars($request['AdminNotes']); ?>
            </div>
            <?php endif; ?>
            <?php if($request['Status'] == 'Pending'): ?>
            <button class="cancel-request-btn" onclick="cancelRequest(<?php echo $club_id; ?>)">
                <i class="fa-solid fa-times"></i> Cancel Request
            </button>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <!-- MY CLUBS SECTION -->
    <?php if(!empty($my_clubs_array)): ?>
    <div class="my-clubs-section">
        <h2 class="section-title"><i class="fa-solid fa-check-circle"></i> My Clubs</h2>
        <div class="clubs-grid">
            <?php foreach($my_clubs_array as $club):
                $image_key = 'default';
                $club_name = $club['Name'];
                if(strpos($club_name, 'Coding') !== false) $image_key = 'Coding';
                else if(strpos($club_name, 'Cyber') !== false) $image_key = 'Cyber';
                else if(strpos($club_name, 'IEEE') !== false) $image_key = 'IEEE';
                else if(strpos($club_name, 'Robotics') !== false) $image_key = 'Robotics';
                else if(strpos($club_name, 'Music') !== false) $image_key = 'Music';
                else if(strpos($club_name, 'Dance') !== false) $image_key = 'Dance';
                else if(strpos($club_name, 'Art') !== false) $image_key = 'Art';
                else if(strpos($club_name, 'Sports') !== false) $image_key = 'Sports';
                
                $image_url = isset($club_images[$image_key]) ? $club_images[$image_key] : $club_images['default'];
            ?>
            <div class="club-card">
                <div class="card-image" style="background-image: url('<?php echo $image_url; ?>');">
                    <div class="card-overlay">
                        <div class="club-name-card"><?php echo htmlspecialchars($club['Name']); ?></div>
                        <div>
                            <span class="club-category-card"><?php echo htmlspecialchars($club['Category']); ?></span>
                            <span class="role-badge"><?php echo htmlspecialchars($club['Role']); ?></span>
                        </div>
                    </div>
                </div>
                <div class="card-content">
                    <div class="club-description">
                        <?php echo htmlspecialchars(substr($club['Description'], 0, 100)) . '...'; ?>
                    </div>
                    <div class="club-meta">
                        <span><i class="fa-regular fa-calendar"></i> Since <?php echo date('Y', strtotime($club['CreatedAt'])); ?></span>
                        <span><i class="fa-solid fa-users"></i> Active Members</span>
                    </div>
                    <button class="leave-btn" onclick="leaveClub(<?php echo $club['ClubID']; ?>)">
                        <i class="fa-solid fa-sign-out-alt"></i> Leave Club
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- ALL CLUBS SECTION -->
    <h2 class="section-title"><i class="fa-solid fa-building"></i> All Clubs</h2>
    <div class="clubs-grid">
        <?php foreach($all_clubs_array as $club):
            
            $button_state = getClubButtonState($club['ClubID'], $my_clubs_array, $user_requests);
            
            $image_key = 'default';
            $club_name = $club['Name'];
            if(strpos($club_name, 'Coding') !== false) $image_key = 'Coding';
            else if(strpos($club_name, 'Cyber') !== false) $image_key = 'Cyber';
            else if(strpos($club_name, 'IEEE') !== false) $image_key = 'IEEE';
            else if(strpos($club_name, 'Robotics') !== false) $image_key = 'Robotics';
            else if(strpos($club_name, 'Music') !== false) $image_key = 'Music';
            else if(strpos($club_name, 'Dance') !== false) $image_key = 'Dance';
            else if(strpos($club_name, 'Art') !== false) $image_key = 'Art';
            else if(strpos($club_name, 'Sports') !== false) $image_key = 'Sports';
            
            $image_url = isset($club_images[$image_key]) ? $club_images[$image_key] : $club_images['default'];
        ?>
        <div class="club-card">
            <div class="card-image" style="background-image: url('<?php echo $image_url; ?>');">
                <div class="card-overlay">
                    <div class="club-name-card"><?php echo htmlspecialchars($club['Name']); ?></div>
                    <span class="club-category-card"><?php echo htmlspecialchars($club['Category']); ?></span>
                </div>
            </div>
            <div class="card-content">
                <div class="club-description">
                    <?php echo htmlspecialchars(substr($club['Description'], 0, 120)) . '...'; ?>
                </div>
                <div class="club-meta">
                    <span><i class="fa-regular fa-calendar"></i> Since <?php echo date('Y', strtotime($club['CreatedAt'])); ?></span>
                    <span><i class="fa-solid fa-crown"></i> <?php echo htmlspecialchars($club['LeaderName'] ?: 'TBD'); ?></span>
                </div>
                <?php if($button_state['type'] == 'member'): ?>
                    <button class="leave-btn" onclick="leaveClub(<?php echo $club['ClubID']; ?>)">
                        <i class="fa-solid fa-sign-out-alt"></i> <?php echo $button_state['text']; ?>
                    </button>
                <?php elseif($button_state['type'] == 'pending'): ?>
                    <button class="join-btn" disabled>
                        <i class="fa-regular fa-clock"></i> <?php echo $button_state['text']; ?>
                    </button>
                <?php elseif($button_state['type'] == 'rejected'): ?>
                    <button class="join-btn" disabled>
                        <i class="fa-solid fa-ban"></i> <?php echo $button_state['text']; ?>
                    </button>
                <?php else: ?>
                    <button class="join-btn" onclick="requestToJoin(<?php echo $club['ClubID']; ?>, '<?php echo htmlspecialchars($club['Name']); ?>')">
                        <i class="fa-solid fa-paper-plane"></i> <?php echo $button_state['text']; ?>
                    </button>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <!-- CLUB EVENTS SECTION - After All Clubs (MODIFIED WITH DOWNLOADED IMAGES) -->
    <div class="club-events-section">
        <h2 class="section-title"><i class="fa-regular fa-calendar-alt"></i> Club Events & Activities</h2>
        
        <?php
        // Get upcoming events from clubs
        $club_events_sql = "SELECT e.*, 
                            (SELECT COUNT(*) FROM EventBookings WHERE EventID = e.EventID AND Status IN ('Confirmed', 'Used')) as booked_count,
                            (SELECT COUNT(*) FROM EventLikes WHERE EventID = e.EventID) as like_count
                            FROM Events e 
                            WHERE e.Status IN ('Upcoming', 'Ongoing') AND e.Category IN ('Club', 'SU', 'Workshop')
                            ORDER BY e.StartTime ASC
                            LIMIT 6";
        $club_events_result = mysqli_query($conn, $club_events_sql);
        
        // Check if user has liked events
        $user_likes = [];
        $likes_sql = "SELECT EventID FROM EventLikes WHERE UserID = ?";
        $likes_stmt = mysqli_prepare($conn, $likes_sql);
        mysqli_stmt_bind_param($likes_stmt, "i", $user_id);
        mysqli_stmt_execute($likes_stmt);
        $likes_result = mysqli_stmt_get_result($likes_stmt);
        while($like = mysqli_fetch_assoc($likes_result)) {
            $user_likes[] = $like['EventID'];
        }
        
        // Event images mapping for downloaded images (ඔයාගේ local images)
        // මේ images 'images/' ෆෝල්ඩර් එකේ තියෙන්න ඕන
        $event_local_images = [
            'SU Meeting' => 'images/su-meeting.jpg',
            'Workshop' => 'images/workshop.jpg', 
            'Hackathon' => 'images/hackathon.jpg',
            'Robotics' => 'images/robotics-event.jpg',
            'default' => 'images/event-default.jpg'
        ];
        ?>
        
        <?php if(mysqli_num_rows($club_events_result) > 0): ?>
            <div class="events-grid">
                <?php while($event = mysqli_fetch_assoc($club_events_result)): 
                    $available_spots = $event['max_capacity'] - $event['booked_count'];
                    $is_sold_out = $available_spots <= 0;
                    $is_liked = in_array($event['EventID'], $user_likes);
                    $is_trending = $event['like_count'] >= 10;
                    
                    // Event title එක අනුව local image එක තෝරා ගැනීම
                    $event_title = $event['Title'];
                    $image_path = $event_local_images['default'];
                    
                    if(stripos($event_title, 'SU Meeting') !== false || stripos($event_title, 'Student Union') !== false) {
                        $image_path = $event_local_images['SU Meeting'];
                    } elseif(stripos($event_title, 'Workshop') !== false || stripos($event_title, 'Web Development') !== false) {
                        $image_path = $event_local_images['Workshop'];
                    } elseif(stripos($event_title, 'Hackathon') !== false) {
                        $image_path = $event_local_images['Hackathon'];
                    } elseif(stripos($event_title, 'Robotics') !== false) {
                        $image_path = $event_local_images['Robotics'];
                    }
                    
                    // Database එකේ event_image තියෙනවා නම් ඒක පාවිච්චි කරන්න
                    if(!empty($event['event_image'])) {
                        $image_path = $event['event_image'];
                    }
                ?>
                    <div class="event-card" data-event-id="<?php echo $event['EventID']; ?>">
                        <div class="event-image" style="background-image: url('<?php echo $image_path; ?>'); background-size: cover; background-position: center;">
                            <span class="event-category"><?php echo htmlspecialchars($event['Category']); ?></span>
                            <?php if($is_trending): ?>
                                <span class="trending-badge"><i class="fa-solid fa-fire"></i> Trending</span>
                            <?php endif; ?>
                            <?php if($event['Status'] == 'Ongoing'): ?>
                                <span class="live-badge"><i class="fa-solid fa-circle"></i> LIVE</span>
                            <?php endif; ?>
                        </div>
                        <div class="event-content">
                            <h3 class="event-title"><?php echo htmlspecialchars($event['Title']); ?></h3>
                            <div class="event-datetime">
                                <i class="fa-regular fa-calendar"></i> <?php echo date('M d, Y', strtotime($event['StartTime'])); ?>
                                @ <i class="fa-regular fa-clock"></i> <?php echo date('h:i A', strtotime($event['StartTime'])); ?>
                            </div>
                            <div class="event-location">
                                <i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($event['Location']); ?>
                            </div>
                            <div class="event-description">
                                <?php echo htmlspecialchars(substr($event['Description'], 0, 80)) . '...'; ?>
                            </div>
                            <div class="event-footer">
                                <div>
                                    <span class="event-price">
                                        <?php echo $event['ticket_price'] > 0 ? 'Rs. ' . number_format($event['ticket_price'], 2) : 'FREE'; ?>
                                    </span>
                                    <div class="spots-left">
                                        <i class="fa-regular fa-circle-user"></i> <?php echo max(0, $available_spots); ?> spots left
                                    </div>
                                </div>
                                <div class="event-actions">
                                    <button class="like-btn <?php echo $is_liked ? 'liked' : ''; ?>" onclick="toggleEventLike(<?php echo $event['EventID']; ?>, this)">
                                        <i class="fa-<?php echo $is_liked ? 'solid' : 'regular'; ?> fa-heart"></i>
                                        <span class="like-count"><?php echo $event['like_count']; ?></span>
                                    </button>
                                    <a href="events.php?event_id=<?php echo $event['EventID']; ?>" class="details-btn">
                                        View Details <i class="fa-solid fa-arrow-right"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
            
            <div class="view-all-events">
                <a href="events.php" class="view-all-btn">
                    View All Events <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>
            
        <?php else: ?>
            <div class="no-events">
                <i class="fa-regular fa-calendar-xmark"></i>
                <p>No upcoming events at the moment. Check back later!</p>
                <a href="events.php" class="view-all-btn">Browse All Events</a>
            </div>
        <?php endif; ?>
    </div>
    
</div>

<script>
// ==================== SIDEBAR ====================
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

// ==================== TOAST FUNCTION ====================
function showToast(message, type) {
    const toast = document.createElement('div');
    toast.className = 'toast-notification';
    toast.innerHTML = message;
    if(type === 'success') toast.style.backgroundColor = '#10b981';
    else if(type === 'error') toast.style.backgroundColor = '#ef4444';
    else toast.style.backgroundColor = '#3b82f6';
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
}

// ==================== CLUB FUNCTIONS ====================
function requestToJoin(clubId, clubName) {
    if(confirm(`Request to join "${clubName}"? Your request will be reviewed by admin.`)) {
        fetch('join_request.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'club_id=' + clubId
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                showToast('✅ Request sent! Awaiting admin approval.', 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showToast('❌ Error: ' + data.message, 'error');
            }
        })
        .catch(error => {
            showToast('❌ Error processing request', 'error');
        });
    }
}

function leaveClub(clubId) {
    if(confirm('Are you sure you want to leave this club? You will lose any club benefits.')) {
        fetch('leave_club.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'club_id=' + clubId
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                showToast('✅ Left the club successfully', 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showToast('❌ Error: ' + data.message, 'error');
            }
        })
        .catch(error => {
            showToast('❌ Error processing request', 'error');
        });
    }
}

function cancelRequest(clubId) {
    if(confirm('Cancel your join request for this club?')) {
        fetch('cancel_request.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'club_id=' + clubId
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                showToast('✅ Request cancelled', 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showToast('❌ Error: ' + data.message, 'error');
            }
        });
    }
}

// Toggle event like function
function toggleEventLike(eventId, btn) {
    fetch('toggle_event_like.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `event_id=${eventId}`
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            const icon = btn.querySelector('i');
            const countSpan = btn.querySelector('.like-count');
            let currentCount = parseInt(countSpan.textContent);
            
            if(data.liked) {
                icon.classList.remove('fa-regular');
                icon.classList.add('fa-solid');
                btn.classList.add('liked');
                countSpan.textContent = currentCount + 1;
            } else {
                icon.classList.remove('fa-solid');
                icon.classList.add('fa-regular');
                btn.classList.remove('liked');
                countSpan.textContent = currentCount - 1;
            }
        }
    })
    .catch(error => console.error('Error:', error));
}
</script>

</body>
</html>