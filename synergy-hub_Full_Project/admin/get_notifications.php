<?php
require_once 'config.php';
require_once 'functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Get unread notifications count from uppercase Notifications table
$count_sql = "SELECT COUNT(*) as unread_count FROM Notifications WHERE UserID = ? AND Status = 'Unread'";
$count_stmt = mysqli_prepare($conn, $count_sql);
mysqli_stmt_bind_param($count_stmt, "i", $user_id);
mysqli_stmt_execute($count_stmt);
$count_result = mysqli_stmt_get_result($count_stmt);
$count_data = mysqli_fetch_assoc($count_result);

// Get recent notifications (last 10)
$notif_sql = "SELECT * FROM Notifications WHERE UserID = ? ORDER BY Timestamp DESC LIMIT 10";
$notif_stmt = mysqli_prepare($conn, $notif_sql);
mysqli_stmt_bind_param($notif_stmt, "i", $user_id);
mysqli_stmt_execute($notif_stmt);
$notif_result = mysqli_stmt_get_result($notif_stmt);

$notifications = [];
while($row = mysqli_fetch_assoc($notif_result)) {
    // Map your types to icons
    $type = 'general';
    if ($row['Type'] == 'Event') $type = 'event';
    else if ($row['Type'] == 'Transport') $type = 'transport';
    else if ($row['Type'] == 'Reminder') $type = 'general';
    else if ($row['Type'] == 'Announcement') $type = 'general';
    
    $notifications[] = [
        'id' => $row['NotificationID'],
        'title' => $row['Type'] . ' Notification',
        'message' => htmlspecialchars($row['Message']),
        'type' => $type,
        'is_read' => ($row['Status'] == 'Read'),
        'time' => date('g:i A', strtotime($row['Timestamp'])),
        'date' => date('M d', strtotime($row['Timestamp']))
    ];
}

echo json_encode([
    'success' => true,
    'unread_count' => (int)$count_data['unread_count'],
    'notifications' => $notifications
]);
?>