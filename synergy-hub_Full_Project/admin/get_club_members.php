<?php
require_once 'config.php';
checkAdminAuth();

header('Content-Type: application/json');

if (!isset($_GET['club_id'])) {
    echo json_encode([]);
    exit();
}

$club_id = intval($_GET['club_id']);

$sql = "SELECT cm.*, u.Name, u.Email, u.StudentID 
        FROM ClubMemberships cm
        JOIN Users u ON cm.UserID = u.UserID
        WHERE cm.ClubID = ? AND cm.Status = 'Active'";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $club_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$members = [];
while($row = mysqli_fetch_assoc($result)) {
    $members[] = [
        'UserID' => $row['UserID'],
        'ClubID' => $row['ClubID'],
        'Name' => $row['Name'],
        'Email' => $row['Email'],
        'StudentID' => $row['StudentID'],
        'Role' => $row['Role'],
        'JoinDate' => $row['JoinDate']
    ];
}
echo json_encode($members);
?>