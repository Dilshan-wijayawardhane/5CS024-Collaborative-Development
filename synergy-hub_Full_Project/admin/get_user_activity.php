<?php
require_once 'middleware.php';
require_once 'config.php';
checkAdminAuth();

if (!isset($_GET['user_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'User ID required']);
    exit();
}

$user_id = (int)$_GET['user_id'];

// Check if ActivityLogs table exists
$check_table = mysqli_query($conn, "SHOW TABLES LIKE 'ActivityLogs'");
if (mysqli_num_rows($check_table) == 0) {
    echo json_encode(['activities' => []]);
    exit();
}

$sql = "SELECT al.*, u.Name 
        FROM ActivityLogs al
        JOIN Users u ON al.UserID = u.UserID
        WHERE al.UserID = ? 
        ORDER BY al.Timestamp DESC 
        LIMIT 20";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$activities = [];
while ($row = mysqli_fetch_assoc($result)) {
    $activities[] = [
        'Action' => $row['Action'],
        'Details' => $row['Details'] ?? '',
        'Timestamp' => date('M d, Y h:i A', strtotime($row['Timestamp']))
    ];
}

header('Content-Type: application/json');
echo json_encode(['activities' => $activities]);
?>