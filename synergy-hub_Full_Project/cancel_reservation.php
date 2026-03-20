<?php

require_once 'config.php';
require_once 'functions.php';

header('Content-Type: application/json');


if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reservation_id'])) {
    $user_id = $_SESSION['user_id'];
    $reservation_id = intval($_POST['reservation_id']);
    
    
    $sql = "UPDATE table_reservations SET status = 'cancelled' 
            WHERE reservation_id = ? AND user_id = ? AND status IN ('pending', 'confirmed')";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $reservation_id, $user_id);
    
    if (mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0) {
        logActivity($conn, $user_id, 'CANCEL_RESERVATION', 'table_reservations', $reservation_id);
        echo json_encode(['success' => true, 'message' => 'Reservation cancelled']);
    } else {
        
        echo json_encode(['success' => false, 'message' => 'No reservation found']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>