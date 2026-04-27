<?php

require_once 'middleware.php';
require_once 'config.php';
checkAdminAuth();

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="points_history_' . date('Y-m-d') . '.csv"');

// Create output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for Excel compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Add headers
fputcsv($output, ['Date', 'User', 'Student ID', 'Action', 'Points Change', 'Description']);

// Get data
$sql = "SELECT ph.*, u.Name as UserName, u.StudentID 
        FROM PointsHistory ph
        JOIN Users u ON ph.UserID = u.UserID
        ORDER BY ph.CreatedAt DESC";

$result = mysqli_query($conn, $sql);

while ($row = mysqli_fetch_assoc($result)) {
    fputcsv($output, [
        date('Y-m-d H:i:s', strtotime($row['CreatedAt'])),
        $row['UserName'],
        $row['StudentID'],
        $row['ActionType'],
        $row['PointsChange'],
        $row['Description']
    ]);
}

fclose($output);
exit();
?>