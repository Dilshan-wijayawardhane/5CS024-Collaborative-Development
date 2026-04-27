<?php
require_once 'middleware.php';
require_once 'config.php';
checkAdminAuth();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = (int)$_POST['user_id'];
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $student_id = mysqli_real_escape_string($conn, $_POST['student_id']);
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $new_password = $_POST['new_password'];
    
    // Check if email already exists for another user
    $check_sql = "SELECT UserID FROM Users WHERE Email = ? AND UserID != ?";
    $check_stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($check_stmt, "si", $email, $user_id);
    mysqli_stmt_execute($check_stmt);
    if (mysqli_stmt_get_result($check_stmt)->num_rows > 0) {
        $_SESSION['error'] = "Email already exists for another user!";
        header("Location: users.php");
        exit();
    }
    
    // Build update query
    $update_sql = "UPDATE Users SET Name = ?, Email = ?, StudentID = ?, Role = ?, MembershipStatus = ?";
    $params = [$name, $email, $student_id, $role, $status];
    $types = "sssss";
    
    if (!empty($new_password)) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $update_sql .= ", PasswordHash = ?";
        $params[] = $hashed_password;
        $types .= "s";
    }
    
    $update_sql .= " WHERE UserID = ?";
    $params[] = $user_id;
    $types .= "i";
    
    $update_stmt = mysqli_prepare($conn, $update_sql);
    mysqli_stmt_bind_param($update_stmt, $types, ...$params);
    
    if (mysqli_stmt_execute($update_stmt)) {
        logActivity($conn, $_SESSION['user_id'], 'UPDATE_USER', 'Users', $user_id);
        $_SESSION['success'] = "User updated successfully!";
    } else {
        $_SESSION['error'] = "Error updating user: " . mysqli_error($conn);
    }
    
    header("Location: users.php");
    exit();
}
?>