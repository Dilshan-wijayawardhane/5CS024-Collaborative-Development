<?php
require_once 'config.php';
require_once 'functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['event_id'])) {
    $event_id = intval($_POST['event_id']);
    
    // Check if already liked
    $check_sql = "SELECT LikeID FROM EventLikes WHERE EventID = ? AND UserID = ?";
    $check_stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($check_stmt, "ii", $event_id, $user_id);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    
    if (mysqli_num_rows($check_result) > 0) {
        // Unlike
        $delete_sql = "DELETE FROM EventLikes WHERE EventID = ? AND UserID = ?";
        $delete_stmt = mysqli_prepare($conn, $delete_sql);
        mysqli_stmt_bind_param($delete_stmt, "ii", $event_id, $user_id);
        mysqli_stmt_execute($delete_stmt);
        
        $update_sql = "UPDATE Events SET like_count = like_count - 1 WHERE EventID = ?";
        $update_stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($update_stmt, "i", $event_id);
        mysqli_stmt_execute($update_stmt);
        
        echo json_encode(['success' => true, 'liked' => false]);
    } else {
        // Like
        $insert_sql = "INSERT INTO EventLikes (EventID, UserID) VALUES (?, ?)";
        $insert_stmt = mysqli_prepare($conn, $insert_sql);
        mysqli_stmt_bind_param($insert_stmt, "ii", $event_id, $user_id);
        mysqli_stmt_execute($insert_stmt);
        
        $update_sql = "UPDATE Events SET like_count = like_count + 1 WHERE EventID = ?";
        $update_stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($update_stmt, "i", $event_id);
        mysqli_stmt_execute($update_stmt);
        
        echo json_encode(['success' => true, 'liked' => true]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>