<?php
require_once 'config.php';
require_once 'functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];
$qr_code = isset($_POST['qr_code']) ? mysqli_real_escape_string($conn, $_POST['qr_code']) : '';
$location_type = isset($_POST['location_type']) ? mysqli_real_escape_string($conn, $_POST['location_type']) : '';

if (empty($qr_code)) {
    echo json_encode(['success' => false, 'message' => 'Invalid QR code']);
    exit();
}

$today = date('Y-m-d');

// ============ 1. Determine Action Type from QR Code ============
$action_type = 'FACILITY_VISIT';

if (strpos($qr_code, 'GYM') !== false) {
    $action_type = 'FACILITY_VISIT';
    $location_type = 'Gym';
} elseif (strpos($qr_code, 'LIB') !== false) {
    $action_type = 'BOOK_BORROW';
    $location_type = 'Library';
} elseif (strpos($qr_code, 'CAFE') !== false) {
    $action_type = 'FACILITY_VISIT';
    $location_type = 'Café';
} elseif (strpos($qr_code, 'EVENT') !== false) {
    $action_type = 'EVENT_ATTENDANCE';
    $location_type = 'Event';
} elseif (strpos($qr_code, 'GAME') !== false) {
    $action_type = 'GAME_PLAY';
    $location_type = 'Game';
}

// ============ 2. Get Points from pointsconfig table ============
$points_sql = "SELECT Points, MaxPerDay FROM pointsconfig WHERE ActionType = ?";
$points_stmt = mysqli_prepare($conn, $points_sql);
mysqli_stmt_bind_param($points_stmt, "s", $action_type);
mysqli_stmt_execute($points_stmt);
$points_result = mysqli_stmt_get_result($points_stmt);
$points_config = mysqli_fetch_assoc($points_result);

if (!$points_config) {
    echo json_encode(['success' => false, 'message' => 'Invalid action type']);
    exit();
}

$base_points = $points_config['Points'];
$max_per_day = $points_config['MaxPerDay'];

// ============ 3. Check Daily Limit ============
if ($max_per_day && $max_per_day > 0) {
    $daily_count_sql = "SELECT COUNT(*) as count FROM pointsHistory 
                        WHERE UserID = ? AND ActionType = ? AND DATE(CreatedAt) = ?";
    $daily_stmt = mysqli_prepare($conn, $daily_count_sql);
    mysqli_stmt_bind_param($daily_stmt, "iss", $user_id, $action_type, $today);
    mysqli_stmt_execute($daily_stmt);
    $daily_result = mysqli_stmt_get_result($daily_stmt);
    $daily_data = mysqli_fetch_assoc($daily_result);
    
    if ($daily_data['count'] >= $max_per_day) {
        echo json_encode([
            'success' => false,
            'daily_limit_reached' => true,
            'max_per_day' => $max_per_day,
            'message' => "Daily limit reached ($max_per_day times per day)"
        ]);
        mysqli_stmt_close($daily_stmt);
        exit();
    }
    mysqli_stmt_close($daily_stmt);
}

// ============ 4. Check if already scanned this QR today ============
$check_sql = "SELECT scan_id FROM qr_scan_history 
              WHERE user_id = ? AND qr_code = ? AND scan_date = ?";
$check_stmt = mysqli_prepare($conn, $check_sql);
mysqli_stmt_bind_param($check_stmt, "iss", $user_id, $qr_code, $today);
mysqli_stmt_execute($check_stmt);
$check_result = mysqli_stmt_get_result($check_stmt);

if (mysqli_num_rows($check_result) > 0) {
    echo json_encode([
        'success' => false,
        'already_scanned_today' => true,
        'message' => 'Already scanned this QR code today'
    ]);
    mysqli_stmt_close($check_stmt);
    exit();
}
mysqli_stmt_close($check_stmt);

// ============ 5. Get User's Tier Multiplier ============
$tier_multiplier = 1.00;
$tier_sql = "SELECT mt.Multiplier 
             FROM users u
             JOIN membershiptiers mt ON u.PointsBalance BETWEEN mt.MinPoints AND COALESCE(mt.MaxPoints, 999999)
             WHERE u.UserID = ?";
$tier_stmt = mysqli_prepare($conn, $tier_sql);
mysqli_stmt_bind_param($tier_stmt, "i", $user_id);
mysqli_stmt_execute($tier_stmt);
$tier_result = mysqli_stmt_get_result($tier_stmt);
$tier_data = mysqli_fetch_assoc($tier_result);
if ($tier_data) {
    $tier_multiplier = floatval($tier_data['Multiplier']);
}
mysqli_stmt_close($tier_stmt);

// ============ 6. Calculate Final Points ============
$final_points = round($base_points * $tier_multiplier);

// ============ 7. Get Current Points ============
$current_sql = "SELECT PointsBalance FROM Users WHERE UserID = ?";
$current_stmt = mysqli_prepare($conn, $current_sql);
mysqli_stmt_bind_param($current_stmt, "i", $user_id);
mysqli_stmt_execute($current_stmt);
$current_result = mysqli_stmt_get_result($current_stmt);
$current_data = mysqli_fetch_assoc($current_result);
$old_balance = $current_data['PointsBalance'];
mysqli_stmt_close($current_stmt);

// ============ 8. Update User Points ============
$update_sql = "UPDATE Users SET PointsBalance = PointsBalance + ? WHERE UserID = ?";
$update_stmt = mysqli_prepare($conn, $update_sql);
mysqli_stmt_bind_param($update_stmt, "ii", $final_points, $user_id);
mysqli_stmt_execute($update_stmt);

if (mysqli_stmt_affected_rows($update_stmt) > 0) {
    $new_balance = $old_balance + $final_points;
    
    // ============ 9. Insert into pointsHistory ============
    $history_sql = "INSERT INTO pointsHistory (UserID, PointsChange, ActionType, Description, CreatedAt) 
                    VALUES (?, ?, ?, ?, NOW())";
    $history_stmt = mysqli_prepare($conn, $history_sql);
    $history_desc = "QR Scan: $location_type (Tier Multiplier: {$tier_multiplier}x)";
    mysqli_stmt_bind_param($history_stmt, "iiss", $user_id, $final_points, $action_type, $history_desc);
    mysqli_stmt_execute($history_stmt);
    mysqli_stmt_close($history_stmt);
    
    // ============ 10. Insert into qr_scan_history ============
    $qr_insert_sql = "INSERT INTO qr_scan_history (user_id, qr_code, location_type, points_earned, scan_date, scan_time) 
                      VALUES (?, ?, ?, ?, ?, CURTIME())";
    $qr_insert_stmt = mysqli_prepare($conn, $qr_insert_sql);
    mysqli_stmt_bind_param($qr_insert_stmt, "issii", $user_id, $qr_code, $location_type, $final_points, $today);
    mysqli_stmt_execute($qr_insert_stmt);
    mysqli_stmt_close($qr_insert_stmt);
    
    // ============ 11. Log Activity ============
    logActivity($conn, $user_id, "Earned $final_points points from QR scan at $location_type", 'QRScan', 0);
    
    echo json_encode([
        'success' => true,
        'new_balance' => $new_balance,
        'points_earned' => $final_points,
        'base_points' => $base_points,
        'tier_multiplier' => $tier_multiplier,
        'action_type' => $action_type,
        'message' => 'Points added successfully'
    ]);
    mysqli_stmt_close($update_stmt);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to add points']);
}

mysqli_stmt_close($points_stmt);
?>