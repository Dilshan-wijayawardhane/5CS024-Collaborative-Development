<?php
require_once 'config.php';
checkAdminAuth();
header('Content-Type: application/json');

// Most popular class
$popular_sql = "SELECT fc.name, COUNT(cb.booking_id) as count 
                FROM fitness_classes fc
                JOIN class_bookings cb ON fc.class_id = cb.class_id
                GROUP BY fc.class_id ORDER BY count DESC LIMIT 1";
$popular_result = mysqli_query($conn, $popular_sql);
$popular = mysqli_fetch_assoc($popular_result);

// New users in last 7 days
$new_users_sql = "SELECT COUNT(*) as count FROM Users WHERE CreatedAt >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
$new_users_result = mysqli_query($conn, $new_users_sql);
$new_users = mysqli_fetch_assoc($new_users_result)['count'];

// Attendance rate
$attendance_sql = "SELECT 
                    COUNT(CASE WHEN ca.check_in_time IS NOT NULL THEN 1 END) as attended,
                    COUNT(*) as total
                  FROM class_bookings cb
                  LEFT JOIN class_attendance ca ON cb.booking_id = ca.booking_id";
$attendance_result = mysqli_query($conn, $attendance_sql);
$attendance = mysqli_fetch_assoc($attendance_result);
$rate = $attendance['total'] > 0 ? round(($attendance['attended'] / $attendance['total']) * 100) : 0;

// Last 7 days attendance
$weekly_sql = "SELECT DATE(check_in_time) as date, COUNT(*) as count 
               FROM class_attendance 
               WHERE check_in_time >= DATE_SUB(NOW(), INTERVAL 7 DAY)
               GROUP BY DATE(check_in_time) ORDER BY date";
$weekly_result = mysqli_query($conn, $weekly_sql);
$labels = [];
$values = [];
while($row = mysqli_fetch_assoc($weekly_result)) {
    $labels[] = date('D', strtotime($row['date']));
    $values[] = $row['count'];
}

echo json_encode([
    'success' => true,
    'popular_class' => $popular['name'] ?? 'N/A',
    'new_users' => $new_users,
    'attendance_rate' => $rate,
    'attendance_labels' => $labels,
    'attendance_values' => $values
]);
?>