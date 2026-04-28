<?php
require_once 'config.php';
checkAdminAuth();

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = intval($_POST['user_id']);
    $club_id = intval($_POST['club_id']);
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    
    $sql = "UPDATE ClubMemberships SET Role = ? WHERE ClubID = ? AND UserID = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sii", $role, $club_id, $user_id);
    
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
    }
} else {
    echo json_encode(['success' => false]);
}
?>