<?php
require_once 'config.php';
require_once 'functions.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];
$equipment_id = intval($_POST['equipment_id']);
$issue = mysqli_real_escape_string($conn, $_POST['issue']);

$sql = "INSERT INTO equipment_issues (equipment_id, user_id, issue_description) VALUES (?, ?, ?)";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "iis", $equipment_id, $user_id, $issue);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(['success' => true, 'message' => 'Issue reported']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>