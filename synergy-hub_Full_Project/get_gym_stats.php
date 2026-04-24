<?php
require_once 'config.php';
header('Content-Type: application/json');

$active_users_sql = "SELECT COUNT(DISTINCT user_id) as count FROM class_attendance WHERE DATE(check_in_time) = CURDATE()";
$active_users_result = mysqli_query($conn, $active_users_sql);
$active_users = mysqli_fetch_assoc($active_users_result)['count'];

$today_classes_sql = "SELECT COUNT(*) as count FROM fitness_classes WHERE TIME(time) >= CURTIME()";
$today_classes_result = mysqli_query($conn, $today_classes_sql);
$today_classes = mysqli_fetch_assoc($today_classes_result)['count'];

$leader_sql = "SELECT u.Name, u.PointsBalance FROM Users u ORDER BY u.PointsBalance DESC LIMIT 1";
$leader_result = mysqli_query($conn, $leader_sql);
$leader = mysqli_fetch_assoc($leader_result);

// Peak hours data
$peak_sql = "SELECT HOUR(check_in_time) as hour, COUNT(*) as count FROM class_attendance GROUP BY HOUR(check_in_time) ORDER BY hour";
$peak_result = mysqli_query($conn, $peak_sql);
$peak_hours = ['labels' => [], 'values' => []];
while($row = mysqli_fetch_assoc($peak_result)) {
    $peak_hours['labels'][] = date('g A', mktime($row['hour'], 0, 0));
    $peak_hours['values'][] = $row['count'];
}

echo json_encode([
    'success' => true,
    'active_users' => $active_users,
    'today_classes' => $today_classes,
    'points_leader' => $leader['Name'] ?? 'N/A',
    'peak_hours' => $peak_hours
]);
?>