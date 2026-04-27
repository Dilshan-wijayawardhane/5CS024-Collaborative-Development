<?php
require_once 'config.php';
checkAdminAuth();

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = intval($_POST['user_id']);
    $club_id = intval($_POST['club_id']);
    
    // First check if user is the only leader
    $leader_check_sql = "SELECT COUNT(*) as leader_count FROM ClubMemberships WHERE ClubID = ? AND Role = 'Leader' AND Status = 'Active'";
    $leader_check_stmt = mysqli_prepare($conn, $leader_check_sql);
    mysqli_stmt_bind_param($leader_check_stmt, "i", $club_id);
    mysqli_stmt_execute($leader_check_stmt);
    $leader_check_result = mysqli_stmt_get_result($leader_check_stmt);
    $leader_check = mysqli_fetch_assoc($leader_check_result);
    
    // Check if the user being removed is a leader
    $user_role_sql = "SELECT Role FROM ClubMemberships WHERE ClubID = ? AND UserID = ? AND Status = 'Active'";
    $user_role_stmt = mysqli_prepare($conn, $user_role_sql);
    mysqli_stmt_bind_param($user_role_stmt, "ii", $club_id, $user_id);
    mysqli_stmt_execute($user_role_stmt);
    $user_role_result = mysqli_stmt_get_result($user_role_stmt);
    $user_role = mysqli_fetch_assoc($user_role_result);
    
    if ($user_role && $user_role['Role'] == 'Leader' && $leader_check['leader_count'] <= 1) {
        echo json_encode(['success' => false, 'error' => 'Cannot remove the only leader of the club. Assign another leader first.']);
        exit();
    }
    
    $sql = "UPDATE ClubMemberships SET Status = 'Inactive' WHERE ClubID = ? AND UserID = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $club_id, $user_id);
    
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
    }
} else {
    echo json_encode(['success' => false]);
}
?>