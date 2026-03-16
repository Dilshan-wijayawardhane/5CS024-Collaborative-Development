<?php
require_once 'config.php';
require_once 'functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['field_name']) && isset($_POST['booking_date']) && isset($_POST['time_slot']) && isset($_POST['price'])) {
    
    $user_id = $_SESSION['user_id'];
    $field_name = $_POST['field_name'];
    $booking_date = $_POST['booking_date'];
    $time_slot = $_POST['time_slot'];
    $price = intval($_POST['price']);
    
    // Check points
    $check_sql = "SELECT PointsBalance FROM Users WHERE UserID = ?";
    $check_stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($check_stmt, "i", $user_id);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    $user = mysqli_fetch_assoc($check_result);
    
    if($user['PointsBalance'] < $price) {
        echo json_encode(['success' => false, 'message' => 'Not enough points']);
        exit();
    }
    
    // Insert booking
    $book_sql = "INSERT INTO field_bookings (user_id, field_name, booking_date, time_slot) VALUES (?, ?, ?, ?)";
    $book_stmt = mysqli_prepare($conn, $book_sql);
    mysqli_stmt_bind_param($book_stmt, "isss", $user_id, $field_name, $booking_date, $time_slot);
    
    if (mysqli_stmt_execute($book_stmt)) {
        // Deduct points
        $deduct_sql = "UPDATE Users SET PointsBalance = PointsBalance - ? WHERE UserID = ?";
        $deduct_stmt = mysqli_prepare($conn, $deduct_sql);
        mysqli_stmt_bind_param($deduct_stmt, "ii", $price, $user_id);
        mysqli_stmt_execute($deduct_stmt);
        
        logActivity($conn, $user_id, 'BOOK_FIELD', 'field_bookings', mysqli_insert_id($conn));
        echo json_encode(['success' => true, 'message' => 'Field booked']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>