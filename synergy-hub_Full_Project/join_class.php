<?php
/**
 * API Endpoint: Join a fitness class
 * 
 * Security Notes:
 *  - Only authenticated users can access this endpoint
 *  - No points deduction
 */

require_once 'config.php';
require_once 'functions.php';

header('Content-Type: application/json');


// Authentication
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

// Validate input and process booking
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['class_id'])) {
    $user_id = $_SESSION['user_id'];
    $class_id = intval($_POST['class_id']);
    
    $class_sql = "SELECT * FROM fitness_classes WHERE class_id = ? AND booked < capacity";
    $class_stmt = mysqli_prepare($conn, $class_sql);
    mysqli_stmt_bind_param($class_stmt, "i", $class_id);
    mysqli_stmt_execute($class_stmt);
    $class_result = mysqli_stmt_get_result($class_stmt);
    
    if (mysqli_num_rows($class_result) == 0) {
        echo json_encode(['success' => false, 'message' => 'Class full or not available']);
        exit();
    }
    
    // Prevent duplicate booking
    $check_sql = "SELECT * FROM class_bookings WHERE user_id = ? AND class_id = ? AND status = 'booked'";
    $check_stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($check_stmt, "ii", $user_id, $class_id);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    
    if (mysqli_num_rows($check_result) > 0) {
        echo json_encode(['success' => false, 'message' => 'Already booked this class']);
        exit();
    }
    
    // Create booking record
    $book_sql = "INSERT INTO class_bookings (user_id, class_id) VALUES (?, ?)";
    $book_stmt = mysqli_prepare($conn, $book_sql);
    mysqli_stmt_bind_param($book_stmt, "ii", $user_id, $class_id);
    
    if (mysqli_stmt_execute($book_stmt)) {
        // Increment booked count for the class
        $update_sql = "UPDATE fitness_classes SET booked = booked + 1 WHERE class_id = ?";
        $update_stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($update_stmt, "i", $class_id);
        mysqli_stmt_execute($update_stmt);
        // Log activity
        logActivity($conn, $user_id, 'JOIN_CLASS', 'fitness_classes', $class_id);
        // Success response
        echo json_encode(['success' => true, 'message' => 'Class joined']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>