<?php
/**
 * Central collection of reusable helper functions for the Synergy Hub application.
 * 
 * All functions are designed to be safe and reusable across pages and APIs.
 * 
 * Security Notes:
 *  - CSRF tokens are generated and verified using secure random values
 *  - Prepared statements are used where database interaction occurs
 *  - Sensitive operations (e.g. points) shuld always be validated server-side
 */

// Escapes HTML-special characters to prevent XSS
function escape($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

// Checks if the current user has admin privileges
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'Admin';
}

function isLoggedIn() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

// Retrieves the current user's points balance from the database
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

// Generates or returns the current CSRF token for the session
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verifies that the provided CSRF token matches the one stored in the session
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>