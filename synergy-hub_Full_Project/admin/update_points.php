<?php
require_once 'middleware.php';
require_once 'config.php';
checkAdminAuth();

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
$points = isset($_POST['points']) ? intval($_POST['points']) : 0;
$action = isset($_POST['action']) ? mysqli_real_escape_string($conn, $_POST['action']) : '';
$reason = isset($_POST['reason']) ? mysqli_real_escape_string($conn, $_POST['reason']) : '';

if ($user_id <= 0 || $points == 0 || empty($action)) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

// Get current user points
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

$current_points = $user['PointsBalance'];
$new_points = $current_points + $points;

if ($new_points < 0) {
    echo json_encode(['success' => false, 'message' => 'Insufficient points']);
    exit();
}

// Start transaction
mysqli_begin_transaction($conn);

try {
    // Update user points
    $update_sql = "UPDATE Users SET PointsBalance = PointsBalance + ? WHERE UserID = ?";
    $update_stmt = mysqli_prepare($conn, $update_sql);
    mysqli_stmt_bind_param($update_stmt, "ii", $points, $user_id);
    
    if (!mysqli_stmt_execute($update_stmt)) {
        throw new Exception('Failed to update user points');
    }
    
    // Insert into PointsHistory
    $history_sql = "INSERT INTO PointsHistory (UserID, PointsChange, ActionType, Description) 
                    VALUES (?, ?, ?, ?)";
    $history_stmt = mysqli_prepare($conn, $history_sql);
    $description = $action . " - " . $reason;
    mysqli_stmt_bind_param($history_stmt, "iiss", $user_id, $points, $action, $description);
    
    if (!mysqli_stmt_execute($history_stmt)) {
        throw new Exception('Failed to record points history');
    }
    
    // Log admin activity
    $log_sql = "INSERT INTO ActivityLogs (UserID, Action, Details, Timestamp) VALUES (?, 'ADMIN_POINTS_ADJUST', ?, NOW())";
    $log_stmt = mysqli_prepare($conn, $log_sql);
    $log_details = "User ID: $user_id, Points: $points, Reason: $reason";
    mysqli_stmt_bind_param($log_stmt, "is", $_SESSION['user_id'], $log_details);
    mysqli_stmt_execute($log_stmt);
    
    mysqli_commit($conn);
    
    echo json_encode([
        'success' => true,
        'message' => 'Points updated successfully',
        'new_balance' => $new_points
    ]);
    
} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>