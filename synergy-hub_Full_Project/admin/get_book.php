<?php
require_once 'middleware.php';
require_once 'config.php';
checkAdminAuth();

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Book ID required']);
    exit();
}

$book_id = intval($_GET['id']);

$sql = "SELECT * FROM books WHERE book_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $book_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($book = mysqli_fetch_assoc($result)) {
    header('Content-Type: application/json');
    echo json_encode($book);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Book not found']);
}
?>