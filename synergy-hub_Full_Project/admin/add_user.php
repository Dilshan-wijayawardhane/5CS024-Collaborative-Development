<?php
require_once 'middleware.php';
require_once 'config.php';
checkAdminAuth();

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header("Location: users.php");
    exit();
}

$name = mysqli_real_escape_string($conn, $_POST['name']);
$email = mysqli_real_escape_string($conn, $_POST['email']);
$student_id = mysqli_real_escape_string($conn, $_POST['student_id']);
$password = $_POST['password'];
$role = mysqli_real_escape_string($conn, $_POST['role']);
$status = mysqli_real_escape_string($conn, $_POST['status']);
$points = intval($_POST['points']);
$send_email = isset($_POST['send_email']);

// Validate inputs
$errors = [];

if (empty($name)) {
    $errors[] = "Name is required";
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Valid email is required";
}

if (empty($password) || strlen($password) < 6) {
    $errors[] = "Password must be at least 6 characters";
}

// Check if email already exists
$check_sql = "SELECT UserID FROM Users WHERE Email = ?";
$check_stmt = mysqli_prepare($conn, $check_sql);
mysqli_stmt_bind_param($check_stmt, "s", $email);
mysqli_stmt_execute($check_stmt);
$check_result = mysqli_stmt_get_result($check_stmt);

if (mysqli_num_rows($check_result) > 0) {
    $errors[] = "Email already exists";
}

// Check if student ID already exists (if provided)
if (!empty($student_id)) {
    $check_sql = "SELECT UserID FROM Users WHERE StudentID = ?";
    $check_stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($check_stmt, "s", $student_id);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    
    if (mysqli_num_rows($check_result) > 0) {
        $errors[] = "Student ID already exists";
    }
}

if (!empty($errors)) {
    $_SESSION['error'] = implode("<br>", $errors);
    header("Location: users.php");
    exit();
}

// Hash password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Insert user
$insert_sql = "INSERT INTO Users (StudentID, Name, Email, PasswordHash, Role, MembershipStatus, PointsBalance, CreatedAt) 
               VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
$insert_stmt = mysqli_prepare($conn, $insert_sql);
mysqli_stmt_bind_param($insert_stmt, "ssssssi", $student_id, $name, $email, $hashed_password, $role, $status, $points);

if (mysqli_stmt_execute($insert_stmt)) {
    $new_user_id = mysqli_insert_id($conn);
    
    // Log activity
    logAdminActivity($conn, 'ADD_USER', "Added new user: $name (ID: $new_user_id)");
    
    // Send welcome email if requested
    if ($send_email) {
        $to = $email;
        $subject = "Welcome to Synergy Hub - Your Account Details";
        
        $message = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f9f9f9; }
                .credentials { background: white; padding: 15px; border-radius: 8px; margin: 20px 0; }
                .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Welcome to Synergy Hub!</h2>
                </div>
                <div class='content'>
                    <p>Hello <strong>$name</strong>,</p>
                    <p>Your account has been created by an administrator. Here are your login details:</p>
                    
                    <div class='credentials'>
                        <p><strong>Email:</strong> $email</p>
                        <p><strong>Password:</strong> $password</p>
                        <p><strong>Role:</strong> $role</p>
                    </div>
                    
                    <p>You can login at: <a href='http://" . $_SERVER['HTTP_HOST'] . "/login.php'>" . $_SERVER['HTTP_HOST'] . "/login.php</a></p>
                    
                    <p>For security reasons, please change your password after your first login.</p>
                </div>
                <div class='footer'>
                    &copy; " . date('Y') . " Synergy Hub. All rights reserved.
                </div>
            </div>
        </body>
        </html>
        ";
        
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: noreply@synergyhub.com" . "\r\n";
        
        mail($to, $subject, $message, $headers);
    }
    
    $_SESSION['success'] = "User created successfully!";
} else {
    $_SESSION['error'] = "Error creating user: " . mysqli_error($conn);
}

header("Location: users.php");
exit();
?>