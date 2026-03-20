<?php
/**
 * API Endpoint: Cancel a table reservation for the logged-in user.
 * 
 * Security / Design notes:
 *  - Only allows cancelation of own reservations
 *  - Soft update (status = 'cancelled') to keep history
 *  - Uses prepared statements to prevent SQL injection
 *  - No point refund logic here, as it can be complex (depends on cancellation policy, time of cancellation, etc.)
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
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reservation_id'])) {
    $user_id = $_SESSION['user_id'];
    $reservation_id = intval($_POST['reservation_id']);
    
    // Cancel reservation (soft delete by updating status)
    $sql = "UPDATE table_reservations SET status = 'cancelled' 
            WHERE reservation_id = ? AND user_id = ? AND status IN ('pending', 'confirmed')";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $reservation_id, $user_id);
    
    if (mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0) {
        logActivity($conn, $user_id, 'CANCEL_RESERVATION', 'table_reservations', $reservation_id);
        echo json_encode(['success' => true, 'message' => 'Reservation cancelled']);
    } else {
        // No rows affected means either reservation not found, not owned by user, or already cancelled
        echo json_encode(['success' => false, 'message' => 'No reservation found']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>