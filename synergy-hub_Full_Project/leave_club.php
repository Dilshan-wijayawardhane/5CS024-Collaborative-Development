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
    
    // Check if user is a member
    $check_sql = "SELECT MembershipID FROM ClubMemberships WHERE ClubID = ? AND UserID = ? AND Status = 'Active'";
    $check_stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($check_stmt, "ii", $club_id, $user_id);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    
    if (mysqli_num_rows($check_result) == 0) {
        echo json_encode(['success' => false, 'message' => 'You are not a member of this club']);
        exit();
    }
    
    // Remove membership (soft delete - set status to Inactive)
    $update_sql = "UPDATE ClubMemberships SET Status = 'Inactive' WHERE ClubID = ? AND UserID = ?";
    $update_stmt = mysqli_prepare($conn, $update_sql);
    mysqli_stmt_bind_param($update_stmt, "ii", $club_id, $user_id);
    
    if (mysqli_stmt_execute($update_stmt)) {
        logActivity($conn, $user_id, 'LEAVE_CLUB', 'Clubs', $club_id);
        echo json_encode(['success' => true, 'message' => 'Left club successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>