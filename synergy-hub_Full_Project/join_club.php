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
    
    

    $check_sql = "SELECT * FROM ClubMemberships WHERE ClubID = ? AND UserID = ? AND Status = 'Active'";
    $check_stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($check_stmt, "ii", $club_id, $user_id);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    
    if (mysqli_num_rows($check_result) > 0) {
        echo json_encode(['success' => false, 'message' => 'Already a member']);
        exit();
    }
    
    

    $join_sql = "INSERT INTO ClubMemberships (ClubID, UserID, Role, Status) VALUES (?, ?, 'Member', 'Active')";
    $join_stmt = mysqli_prepare($conn, $join_sql);
    mysqli_stmt_bind_param($join_stmt, "ii", $club_id, $user_id);
    
    

    if (mysqli_stmt_execute($join_stmt)) {
        $points = 20;
        $update_sql = "UPDATE Users SET PointsBalance = PointsBalance + ? WHERE UserID = ?";
        $update_stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($update_stmt, "ii", $points, $user_id);
        mysqli_stmt_execute($update_stmt);
        
        
        logActivity($conn, $user_id, 'JOIN_CLUB', 'Clubs', $club_id);
        

        
        echo json_encode(['success' => true, 'message' => 'Joined club successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>