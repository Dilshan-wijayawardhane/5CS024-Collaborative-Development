<?php

function escape($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'Admin';
}

function isLoggedIn() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

function getUserPoints($conn, $user_id) {
    $sql = "SELECT PointsBalance FROM Users WHERE UserID = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    return $user ? $user['PointsBalance'] : 0;
}

function logActivity($conn, $user_id, $action, $table_name = null, $record_id = null) {
    $check = mysqli_query($conn, "SHOW TABLES LIKE 'AuditLogs'");
    if (mysqli_num_rows($check) == 0) {
        return;
    }
    
    $ip = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    
    $sql = "INSERT INTO AuditLogs (UserID, Action, TableName, RecordID, IPAddress, UserAgent) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ississ", $user_id, $action, $table_name, $record_id, $ip, $user_agent);
    mysqli_stmt_execute($stmt);
}

function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function redirectBasedOnRole() {
    if (isset($_SESSION['user_role'])) {
        if ($_SESSION['user_role'] === 'Admin') {
            header("Location: admin/index.php");
        } else {
            header("Location: index.php");
        }
        exit();
    }
}

// FIXED: Add notification to the correct table
function addNotification($conn, $user_id, $title, $message, $type = 'general') {
    // Check if notifications table exists
    $table_check = mysqli_query($conn, "SHOW TABLES LIKE 'notifications'");
    if (mysqli_num_rows($table_check) == 0) {
        return false;
    }
    
    $sql = "INSERT INTO notifications (user_id, title, message, type, is_read, created_at) 
            VALUES (?, ?, ?, ?, 0, NOW())";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "isss", $user_id, $title, $message, $type);
    return mysqli_stmt_execute($stmt);
}

// Add sample notifications for new users
function addSampleNotifications($conn, $user_id) {
    // Check if user already has notifications
    $check_sql = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ?";
    $check_stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($check_stmt, "i", $user_id);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    $count = mysqli_fetch_assoc($check_result)['count'];
    
    if ($count == 0) {
        $sample_notifications = [
            ['Welcome to Synergy Hub!', 'Thank you for joining Synergy Hub. Explore our facilities and earn points!', 'general'],
            ['Complete Your Profile', 'Add your details to get started and earn 50 bonus points!', 'general'],
            ['Gym Open Now', 'The gym is open from 6:00 AM to 10:00 PM. Come work out!', 'gym'],
            ['Transport Available', 'Book your transport pass to travel between campuses.', 'transport'],
            ['Upcoming Events', 'Check out the events section for upcoming activities!', 'event']
        ];
        
        foreach ($sample_notifications as $notif) {
            addNotification($conn, $user_id, $notif[0], $notif[1], $notif[2]);
        }
        return true;
    }
    return false;
}

?>