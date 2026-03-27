<?php



require_once 'config.php';
require_once 'functions.php';

header('Content-Type: application/json');




if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}




$sql = "SELECT * FROM EmergencyAlerts WHERE (expires_at IS NULL OR expires_at > NOW()) AND is_active = 1 ORDER BY 
        CASE severity 
            WHEN 'critical' THEN 1 
            WHEN 'warning' THEN 2 
            WHEN 'info' THEN 3 
        END, created_at DESC";

$result = mysqli_query($conn, $sql);

if (!$result) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit();
}




$alerts = [];
while ($row = mysqli_fetch_assoc($result)) {
    $alerts[] = [
        'id' => $row['id'],
        'severity' => $row['severity'],
        'title' => $row['title'],
        'message' => $row['message'],
        'time' => date('g:i A', strtotime($row['created_at'])),
        'date' => date('M d', strtotime($row['created_at'])),
        'expires' => $row['expires_at'] ? date('g:i A', strtotime($row['expires_at'])) : null
    ];
}

echo json_encode(['success' => true, 'alerts' => $alerts, 'count' => count($alerts)]);
?>