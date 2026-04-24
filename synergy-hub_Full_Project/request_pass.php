<?php
require_once 'config.php';
require_once 'functions.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['route']) && isset($_POST['price'])) {
    $route_name = mysqli_real_escape_string($conn, $_POST['route']);
    $price = intval($_POST['price']);
    
    // Check if user has enough points
    $user_sql = "SELECT PointsBalance FROM Users WHERE UserID = ?";
    $user_stmt = mysqli_prepare($conn, $user_sql);
    mysqli_stmt_bind_param($user_stmt, "i", $user_id);
    mysqli_stmt_execute($user_stmt);
    $user_result = mysqli_stmt_get_result($user_stmt);
    $user = mysqli_fetch_assoc($user_result);
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit();
    }
    
    if ($user['PointsBalance'] < $price) {
        echo json_encode(['success' => false, 'message' => 'Insufficient points']);
        exit();
    }
    
    // Check if user already has a pending request for this route
    $check_sql = "SELECT COUNT(*) as count FROM TransportPasses WHERE UserID = ? AND RouteName = ? AND Status = 'Pending'";
    $check_stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($check_stmt, "is", $user_id, $route_name);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    $check = mysqli_fetch_assoc($check_result);
    
    if ($check['count'] > 0) {
        echo json_encode(['success' => false, 'message' => 'You already have a pending request for this route']);
        exit();
    }
    
    // Check if user already has an active pass for this route
    $active_sql = "SELECT COUNT(*) as count FROM TransportPasses WHERE UserID = ? AND RouteName = ? AND Status = 'Active'";
    $active_stmt = mysqli_prepare($conn, $active_sql);
    mysqli_stmt_bind_param($active_stmt, "is", $user_id, $route_name);
    mysqli_stmt_execute($active_stmt);
    $active_result = mysqli_stmt_get_result($active_stmt);
    $active = mysqli_fetch_assoc($active_result);
    
    if ($active['count'] > 0) {
        echo json_encode(['success' => false, 'message' => 'You already have an active pass for this route']);
        exit();
    }
    
    // Create pending pass request (points NOT deducted yet)
    $valid_until = date('Y-m-d', strtotime('+30 days')); // Temporary placeholder, admin will set actual
    $insert_sql = "INSERT INTO TransportPasses (UserID, RouteName, ValidUntil, Status, points_spent) VALUES (?, ?, ?, 'Pending', ?)";
    $insert_stmt = mysqli_prepare($conn, $insert_sql);
    mysqli_stmt_bind_param($insert_stmt, "issi", $user_id, $route_name, $valid_until, $price);
    
    if (mysqli_stmt_execute($insert_stmt)) {
        $pass_id = mysqli_insert_id($conn);
        
        // Log the activity
        logActivity($conn, $user_id, 'REQUEST_PASS', 'TransportPasses', $pass_id);
        
        echo json_encode(['success' => true, 'message' => 'Pass request submitted successfully. Awaiting admin approval!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request. Missing route or price.']);
}
?>