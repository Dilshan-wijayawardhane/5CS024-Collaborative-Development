<?php
/**
 * API Endpoint: Claim a special offer for the logged-in user using points.
 * 
 * Security / Design notes:
 *  - Requires user to be logged in
 *  - No transaction needed
 *  - Does not check if offer is valid or already claimed (can be added later)
 *  - Does not generate/store a redemption code, just logs the claim activity
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
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['offer_id'])) {
    $user_id = $_SESSION['user_id'];
    $offer_id = intval($_POST['offer_id']);
    $points = isset($_POST['points']) ? intval($_POST['points']) : 0;
    
    // Handle points deduction
    if ($points > 0) {
        // Check current balance
        $check_sql = "SELECT PointsBalance FROM Users WHERE UserID = ?";
        $check_stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($check_stmt, "i", $user_id);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);
        $user = mysqli_fetch_assoc($check_result);
        
        if ($user['PointsBalance'] < $points) {
            echo json_encode(['success' => false, 'message' => 'Not enough points']);
            exit();
        }
        
        // Deduct points
        $deduct_sql = "UPDATE Users SET PointsBalance = PointsBalance - ? WHERE UserID = ?";
        $deduct_stmt = mysqli_prepare($conn, $deduct_sql);
        mysqli_stmt_bind_param($deduct_stmt, "ii", $points, $user_id);
        mysqli_stmt_execute($deduct_stmt);
    }
    // Log activity
    logActivity($conn, $user_id, 'CLAIM_OFFER', 'special_offers', $offer_id);
    
    // Success response
    echo json_encode(['success' => true, 'message' => 'Offer claimed']);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>