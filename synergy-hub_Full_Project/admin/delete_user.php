<?php
require_once 'middleware.php';
require_once 'config.php';
checkAdminAuth();

if (!isset($_GET['id'])) {
    header("Location: users.php");
    exit();
}

$user_id = (int)$_GET['id'];

// Check if user is admin
$check_sql = "SELECT Role FROM Users WHERE UserID = ?";
$check_stmt = mysqli_prepare($conn, $check_sql);
mysqli_stmt_bind_param($check_stmt, "i", $user_id);
mysqli_stmt_execute($check_stmt);
$result = mysqli_stmt_get_result($check_stmt);
$user = mysqli_fetch_assoc($result);

if ($user['Role'] == 'Admin') {
    $_SESSION['error'] = "Cannot delete admin users!";
    header("Location: users.php");
    exit();
}

// Delete user (or you might want to soft delete)
$delete_sql = "DELETE FROM Users WHERE UserID = ? AND Role != 'Admin'";
$delete_stmt = mysqli_prepare($conn, $delete_sql);
mysqli_stmt_bind_param($delete_stmt, "i", $user_id);

if (mysqli_stmt_execute($delete_stmt) && mysqli_stmt_affected_rows($delete_stmt) > 0) {
    logActivity($conn, $_SESSION['user_id'], 'DELETE_USER', 'Users', $user_id);
    $_SESSION['success'] = "User deleted successfully!";
} else {
    $_SESSION['error'] = "Error deleting user!";
}

header("Location: users.php");
exit();
?>