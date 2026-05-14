<?php
require_once 'config.php';
require_once 'functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];
$facility_id = isset($_POST['facility_id']) ? intval($_POST['facility_id']) : 0;
$facility_name = isset($_POST['facility_name']) ? mysqli_real_escape_string($conn, $_POST['facility_name']) : '';
$user_lat = isset($_POST['latitude']) ? floatval($_POST['latitude']) : 0;
$user_lng = isset($_POST['longitude']) ? floatval($_POST['longitude']) : 0;

if ($facility_id == 0 || empty($facility_name)) {
    echo json_encode(['success' => false, 'message' => 'Invalid facility']);
    exit();
}

$today = date('Y-m-d');

// ============ 1. Get Facility Coordinates from Database ============
// In a real implementation, you would have lat/lng columns in facilities table
// For this example, we'll use approximate coordinates for CINEC campus
$facility_coordinates = [
    1 => ['lat' => 6.905800, 'lng' => 79.968300, 'name' => 'Main Library'],      // Library
    2 => ['lat' => 6.905700, 'lng' => 79.968200, 'name' => 'University Gym'],     // Gym
    3 => ['lat' => 6.905720, 'lng' => 79.968180, 'name' => 'Campus Café'],        // Café
    5 => ['lat' => 6.905900, 'lng' => 79.968400, 'name' => 'Sports Field'],       // Sports Field
    6 => ['lat' => 6.905650, 'lng' => 79.968100, 'name' => 'Olympic Swimming Pool'] // Pool
];

// Check if facility exists in coordinates
if (!isset($facility_coordinates[$facility_id])) {
    echo json_encode(['success' => false, 'message' => 'Facility coordinates not found']);
    exit();
}

$facility_lat = $facility_coordinates[$facility_id]['lat'];
$facility_lng = $facility_coordinates[$facility_id]['lng'];

// ============ 2. Calculate Distance ============
function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $R = 6371e3; // Earth's radius in meters
    $φ1 = $lat1 * M_PI / 180;
    $φ2 = $lat2 * M_PI / 180;
    $Δφ = ($lat2 - $lat1) * M_PI / 180;
    $Δλ = ($lon2 - $lon1) * M_PI / 180;

    $a = sin($Δφ / 2) * sin($Δφ / 2) +
          cos($φ1) * cos($φ2) *
          sin($Δλ / 2) * sin($Δλ / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

    return round($R * $c, 1);
}

$distance = calculateDistance($user_lat, $user_lng, $facility_lat, $facility_lng);
$max_distance = 100; // 100 meters maximum for valid check-in

if ($distance > $max_distance) {
    echo json_encode([
        'success' => false,
        'too_far' => true,
        'distance_meters' => $distance,
        'message' => "You are {$distance}m away. Must be within {$max_distance}m to check in."
    ]);
    exit();
}

// ============ 3. Check if already checked in today ============
$check_sql = "SELECT checkin_id FROM gps_checkin_history 
              WHERE user_id = ? AND facility_id = ? AND checkin_date = ?";
$check_stmt = mysqli_prepare($conn, $check_sql);
mysqli_stmt_bind_param($check_stmt, "iis", $user_id, $facility_id, $today);
mysqli_stmt_execute($check_stmt);
$check_result = mysqli_stmt_get_result($check_stmt);

if (mysqli_num_rows($check_result) > 0) {
    echo json_encode([
        'success' => false,
        'already_checked_in_today' => true,
        'message' => 'Already checked in to this facility today'
    ]);
    mysqli_stmt_close($check_stmt);
    exit();
}
mysqli_stmt_close($check_stmt);

// ============ 4. Get Points from pointsconfig table ============
$action_type = 'FACILITY_VISIT';
$points_sql = "SELECT Points, MaxPerDay FROM pointsconfig WHERE ActionType = ?";
$points_stmt = mysqli_prepare($conn, $points_sql);
mysqli_stmt_bind_param($points_stmt, "s", $action_type);
mysqli_stmt_execute($points_stmt);
$points_result = mysqli_stmt_get_result($points_stmt);
$points_config = mysqli_fetch_assoc($points_result);

if (!$points_config) {
    echo json_encode(['success' => false, 'message' => 'Points configuration not found']);
    exit();
}

$base_points = $points_config['Points'];
$max_per_day = $points_config['MaxPerDay'];

// ============ 5. Check Daily Limit ============
if ($max_per_day && $max_per_day > 0) {
    $daily_count_sql = "SELECT COUNT(*) as count FROM gps_checkin_history 
                        WHERE user_id = ? AND checkin_date = ?";
    $daily_stmt = mysqli_prepare($conn, $daily_count_sql);
    mysqli_stmt_bind_param($daily_stmt, "is", $user_id, $today);
    mysqli_stmt_execute($daily_stmt);
    $daily_result = mysqli_stmt_get_result($daily_stmt);
    $daily_data = mysqli_fetch_assoc($daily_result);
    
    if ($daily_data['count'] >= $max_per_day) {
        echo json_encode([
            'success' => false,
            'daily_limit_reached' => true,
            'max_per_day' => $max_per_day,
            'message' => "Daily check-in limit reached ($max_per_day times per day)"
        ]);
        mysqli_stmt_close($daily_stmt);
        exit();
    }
    mysqli_stmt_close($daily_stmt);
}

// ============ 6. Get User's Tier Multiplier ============
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

// ============ 7. Calculate Final Points ============
$final_points = round($base_points * $tier_multiplier);

// ============ 8. Get Current Points ============
$current_sql = "SELECT PointsBalance FROM Users WHERE UserID = ?";
$current_stmt = mysqli_prepare($conn, $current_sql);
mysqli_stmt_bind_param($current_stmt, "i", $user_id);
mysqli_stmt_execute($current_stmt);
$current_result = mysqli_stmt_get_result($current_stmt);
$current_data = mysqli_fetch_assoc($current_result);
$old_balance = $current_data['PointsBalance'];
mysqli_stmt_close($current_stmt);

// ============ 9. Update User Points ============
$update_sql = "UPDATE Users SET PointsBalance = PointsBalance + ? WHERE UserID = ?";
$update_stmt = mysqli_prepare($conn, $update_sql);
mysqli_stmt_bind_param($update_stmt, "ii", $final_points, $user_id);
mysqli_stmt_execute($update_stmt);

if (mysqli_stmt_affected_rows($update_stmt) > 0) {
    $new_balance = $old_balance + $final_points;
    
    // ============ 10. Insert into pointsHistory ============
    $history_sql = "INSERT INTO pointsHistory (UserID, PointsChange, ActionType, Description, CreatedAt) 
                    VALUES (?, ?, ?, ?, NOW())";
    $history_stmt = mysqli_prepare($conn, $history_sql);
    $history_desc = "GPS Check-in: $facility_name (Distance: {$distance}m, Tier: {$tier_multiplier}x)";
    mysqli_stmt_bind_param($history_stmt, "iiss", $user_id, $final_points, $action_type, $history_desc);
    mysqli_stmt_execute($history_stmt);
    mysqli_stmt_close($history_stmt);
    
    // ============ 11. Insert into gps_checkin_history ============
    $gps_insert_sql = "INSERT INTO gps_checkin_history (user_id, facility_id, facility_name, latitude, longitude, points_earned, checkin_date, checkin_time) 
                       VALUES (?, ?, ?, ?, ?, ?, ?, CURTIME())";
    $gps_insert_stmt = mysqli_prepare($conn, $gps_insert_sql);
    mysqli_stmt_bind_param($gps_insert_stmt, "iisddi", $user_id, $facility_id, $facility_name, $user_lat, $user_lng, $final_points, $today);
    mysqli_stmt_execute($gps_insert_stmt);
    mysqli_stmt_close($gps_insert_stmt);
    
    // ============ 12. Log Activity ============
    logActivity($conn, $user_id, "Earned $final_points points from GPS check-in at $facility_name (Distance: {$distance}m)", 'GPS Check-in', 0);
    
    echo json_encode([
        'success' => true,
        'new_balance' => $new_balance,
        'points_earned' => $final_points,
        'base_points' => $base_points,
        'tier_multiplier' => $tier_multiplier,
        'facility_name' => $facility_name,
        'distance_meters' => $distance,
        'message' => 'GPS check-in successful'
    ]);
    mysqli_stmt_close($update_stmt);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to add points']);
}

mysqli_stmt_close($points_stmt);
?>