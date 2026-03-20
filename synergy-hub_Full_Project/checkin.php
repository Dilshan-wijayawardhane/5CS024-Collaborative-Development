<?php
/**
 * API Endpoint: Check-in to a facility and earn points for the logged-in user.
 * 
 * Security / Design notes:
 *  - Only allows check-in if not already checked in today (per facility)
 *  - Uses prepared statements to prevent SQL injection
 *  - Points awarded hardcoded to 10 for simplicity, but can be made dynamic based on facility or other factors
 */

require_once 'config.php';
require_once 'functions.php';

header('Content-Type: application/json');

// Authentication check
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

// Validate request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['facility_id'])) {
    $user_id = $_SESSION['user_id'];
    $facility_id = intval($_POST['facility_id']);
    
    // Check if already checked in today for this facility
    $check_sql = "SELECT * FROM CheckIns WHERE UserID = ? AND FacilityID = ? AND DATE(Timestamp) = CURDATE()";
    $check_stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($check_stmt, "ii", $user_id, $facility_id);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    
    if (mysqli_num_rows($check_result) > 0) {
        echo json_encode(['success' => false, 'message' => 'Already checked in today']);
        exit();
    }
    
    // Award points and record check-in
    $points = 10;   // Points awarded per check-in
    $insert_sql = "INSERT INTO CheckIns (UserID, FacilityID, PointsAwarded) VALUES (?, ?, ?)";
    $insert_stmt = mysqli_prepare($conn, $insert_sql);
    mysqli_stmt_bind_param($insert_stmt, "iii", $user_id, $facility_id, $points);
    
    if (mysqli_stmt_execute($insert_stmt)) {
        $update_sql = "UPDATE Users SET PointsBalance = PointsBalance + ? WHERE UserID = ?";
        $update_stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($update_stmt, "ii", $points, $user_id);
        mysqli_stmt_execute($update_stmt);
        
        $points_sql = "SELECT PointsBalance FROM Users WHERE UserID = ?";
        $points_stmt = mysqli_prepare($conn, $points_sql);
        mysqli_stmt_bind_param($points_stmt, "i", $user_id);
        mysqli_stmt_execute($points_stmt);
        $points_result = mysqli_stmt_get_result($points_stmt);
        $user = mysqli_fetch_assoc($points_result);
        
        logActivity($conn, $user_id, 'CHECKIN', 'Facilities', $facility_id);
        
        echo json_encode([
            'success' => true,
            'message' => 'Check-in successful',
            'new_points' => $user['PointsBalance']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>