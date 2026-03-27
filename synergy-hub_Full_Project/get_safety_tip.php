<?php


require_once 'config.php';
require_once 'functions.php';

header('Content-Type: application/json');




session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

$tip_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$tip_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid tip ID']);
    exit();
}




$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'SafetyTips'");
if (mysqli_num_rows($table_check) == 0) {
    echo json_encode(['success' => false, 'message' => 'Safety tips table not found']);
    exit();
}




$sql = "SELECT * FROM SafetyTips WHERE id = ? AND is_active = 1";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $tip_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($row = mysqli_fetch_assoc($result)) {
    $update_sql = "UPDATE SafetyTips SET views = views + 1 WHERE id = ?";
    $update_stmt = mysqli_prepare($conn, $update_sql);
    mysqli_stmt_bind_param($update_stmt, "i", $tip_id);
    mysqli_stmt_execute($update_stmt);
    
    

    echo json_encode([
        'success' => true,
        'tip' => [
            'id' => $row['id'],
            'title' => $row['title'],
            'content' => $row['content'],
            'category' => $row['category'],
            'priority' => $row['priority'],
            'tags' => $row['tags'],
            'views' => $row['views'] + 1    
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Tip not found']);
}
?>