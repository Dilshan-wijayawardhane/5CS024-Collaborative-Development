<?php


require_once 'config.php';
require_once 'functions.php';

header('Content-Type: application/json');


if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['booking_id'])) {
    $user_id = $_SESSION['user_id'];
    $booking_id = intval($_POST['booking_id']);
    
    $get_sql = "SELECT class_id FROM class_bookings WHERE booking_id = ? AND user_id = ?";
    $get_stmt = mysqli_prepare($conn, $get_sql);
    mysqli_stmt_bind_param($get_stmt, "ii", $booking_id, $user_id);
    mysqli_stmt_execute($get_stmt);
    $get_result = mysqli_stmt_get_result($get_stmt);
    $booking = mysqli_fetch_assoc($get_result);
    
    if (!$booking) {
        echo json_encode(['success' => false, 'message' => 'Booking not found']);
        exit();
    }
    
    
    $delete_sql = "DELETE FROM class_bookings WHERE booking_id = ? AND user_id = ?";
    $delete_stmt = mysqli_prepare($conn, $delete_sql);
    mysqli_stmt_bind_param($delete_stmt, "ii", $booking_id, $user_id);
    
    if (mysqli_stmt_execute($delete_stmt) && mysqli_stmt_affected_rows($delete_stmt) > 0) {
        
        $update_sql = "UPDATE fitness_classes SET booked = booked - 1 WHERE class_id = ?";
        $update_stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($update_stmt, "i", $booking['class_id']);
        mysqli_stmt_execute($update_stmt);
        
        logActivity($conn, $user_id, 'CANCEL_CLASS', 'fitness_classes', $booking['class_id']);
        
        echo json_encode(['success' => true, 'message' => 'Booking cancelled']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>