<?php
require_once 'config.php';
require_once 'functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['club_id'])) {
    $club_id = intval($_POST['club_id']);
    
    $delete_sql = "DELETE FROM JoinRequests WHERE ClubID = ? AND UserID = ? AND Status = 'Pending'";
    $delete_stmt = mysqli_prepare($conn, $delete_sql);
    mysqli_stmt_bind_param($delete_stmt, "ii", $club_id, $user_id);
    
    if (mysqli_stmt_execute($delete_stmt)) {
        echo json_encode(['success' => true, 'message' => 'Request cancelled']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>