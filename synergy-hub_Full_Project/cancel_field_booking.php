<?php
/**
 * API Endpoint: Cancel a field/sports booking for the logged-in user.
 * 
 * Security / Design notes:
 *  - Only allows cancelation of own bookings
 *  - Prevents cancelling already cancelled booking
 *  - Uses prepared statements to prevent SQL injection
 *  - Does not refund points here
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
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['booking_id'])) {
    $user_id = $_SESSION['user_id'];
    $booking_id = intval($_POST['booking_id']);
    
    // Cancel booking (soft delete by updating status)
    $cancel_sql = "UPDATE field_bookings SET status = 'cancelled' WHERE booking_id = ? AND user_id = ? AND status = 'booked'";
    $cancel_stmt = mysqli_prepare($conn, $cancel_sql);
    mysqli_stmt_bind_param($cancel_stmt, "ii", $booking_id, $user_id);
    
    if (mysqli_stmt_execute($cancel_stmt) && mysqli_stmt_affected_rows($cancel_stmt) > 0) {
        // Log activity
        logActivity($conn, $user_id, 'CANCEL_BOOKING', 'field_bookings', $booking_id);
        echo json_encode(['success' => true, 'message' => 'Booking cancelled']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No booking found']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>