<?php
// admin/config.php - Admin Panel Configuration
require_once dirname(__DIR__) . '/config.php';

// Admin session check function (now uses the same session)
function checkAdminAuth() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
        header("Location: ../login.php");
        exit();
    }
    
    if ($_SESSION['user_role'] !== 'Admin') {
        header("Location: ../index.php");
        exit();
    }
}

// Admin login check (for pages that don't require redirect)
function isAdmin() {
    return (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'Admin');
}

// Get admin info
function getAdminInfo($conn) {
    $admin_id = $_SESSION['user_id'];
    $sql = "SELECT UserID, Name, Email, StudentID, PointsBalance, Role 
            FROM Users WHERE UserID = ? AND Role = 'Admin'";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $admin_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($result);
}

// Get counts for dashboard
function getDashboardCounts($conn) {
    $counts = [];
    
    // Total users
    $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM Users WHERE Role = 'User'");
    $counts['total_users'] = $result ? mysqli_fetch_assoc($result)['count'] : 0;
    
    // Active users today - Check if ActivityLogs table exists
    $check_activity = mysqli_query($conn, "SHOW TABLES LIKE 'ActivityLogs'");
    if (mysqli_num_rows($check_activity) > 0) {
        $result = mysqli_query($conn, "SELECT COUNT(DISTINCT UserID) as count FROM ActivityLogs WHERE DATE(Timestamp) = CURDATE()");
        $counts['active_today'] = $result ? mysqli_fetch_assoc($result)['count'] : 0;
    } else {
        $counts['active_today'] = 0;
    }
    
    // Total facilities
    $check_facilities = mysqli_query($conn, "SHOW TABLES LIKE 'Facilities'");
    if (mysqli_num_rows($check_facilities) > 0) {
        $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM Facilities");
        $counts['total_facilities'] = $result ? mysqli_fetch_assoc($result)['count'] : 0;
        
        $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM Facilities WHERE Status = 'Open'");
        $counts['open_facilities'] = $result ? mysqli_fetch_assoc($result)['count'] : 0;
    } else {
        $counts['total_facilities'] = 0;
        $counts['open_facilities'] = 0;
    }
    
    // Total events
    $check_events = mysqli_query($conn, "SHOW TABLES LIKE 'Events'");
    if (mysqli_num_rows($check_events) > 0) {
        $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM Events");
        $counts['total_events'] = $result ? mysqli_fetch_assoc($result)['count'] : 0;
        
        $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM Events WHERE Status = 'Upcoming'");
        $counts['upcoming_events'] = $result ? mysqli_fetch_assoc($result)['count'] : 0;
    } else {
        $counts['total_events'] = 0;
        $counts['upcoming_events'] = 0;
    }
    
    // Total orders
    $check_orders = mysqli_query($conn, "SHOW TABLES LIKE 'Orders'");
    if (mysqli_num_rows($check_orders) > 0) {
        $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM Orders WHERE DATE(Timestamp) = CURDATE()");
        $counts['orders_today'] = $result ? mysqli_fetch_assoc($result)['count'] : 0;
        
        $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM Orders WHERE Status = 'Pending'");
        $counts['pending_orders'] = $result ? mysqli_fetch_assoc($result)['count'] : 0;
    } else {
        $counts['orders_today'] = 0;
        $counts['pending_orders'] = 0;
    }
    
    // Total points
    $result = mysqli_query($conn, "SELECT SUM(PointsBalance) as total FROM Users");
    $counts['total_points'] = $result ? (mysqli_fetch_assoc($result)['total'] ?? 0) : 0;
    
    return $counts;
}

// Log admin activity
function logAdminActivity($conn, $action, $details = '') {
    if (isset($_SESSION['user_id'])) {
        $sql = "INSERT INTO ActivityLogs (UserID, Action, Details, Timestamp) VALUES (?, ?, ?, NOW())";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "iss", $_SESSION['user_id'], $action, $details);
        mysqli_stmt_execute($stmt);
    }
}
?>