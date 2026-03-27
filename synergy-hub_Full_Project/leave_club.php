<?php


require_once 'config.php';
require_once 'functions.php';

header('Content-Type: application/json');



if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}



if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['club_id'])) {
    $user_id = $_SESSION['user_id'];
    $club_id = intval($_POST['club_id']);
    
    

    $check_sql = "SELECT Role FROM ClubMemberships WHERE ClubID = ? AND UserID = ? AND Status = 'Active'";
    $check_stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($check_stmt, "ii", $club_id, $user_id);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    $membership = mysqli_fetch_assoc($check_result);
    
    if ($membership['Role'] == 'Leader') {
        echo json_encode(['success' => false, 'message' => 'Leaders cannot leave. Transfer leadership first.']);
        exit();
    }
    
    
    
    $leave_sql = "UPDATE ClubMemberships SET Status = 'Inactive' WHERE ClubID = ? AND UserID = ?";
    $leave_stmt = mysqli_prepare($conn, $leave_sql);
    mysqli_stmt_bind_param($leave_stmt, "ii", $club_id, $user_id);
    
    if (mysqli_stmt_execute($leave_stmt)) {
        

        logActivity($conn, $user_id, 'LEAVE_CLUB', 'Clubs', $club_id);
        
        
        echo json_encode(['success' => true, 'message' => 'Left club']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>