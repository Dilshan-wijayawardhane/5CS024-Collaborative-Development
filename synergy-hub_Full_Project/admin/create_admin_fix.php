<?php
require_once 'middleware.php';
require_once 'config.php';

// Set your admin details
$email = 'admin@synergyhub.com';
$password = 'admin123';
$name = 'Admin User';
$student_id = 'ADMIN001';

// Generate correct hash
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "<h2>Admin User Creation</h2>";
echo "Password: <strong>$password</strong><br>";
echo "Generated Hash: <strong>$hash</strong><br><br>";

// First, delete existing admin with this email
$delete_sql = "DELETE FROM Users WHERE Email = ?";
$delete_stmt = mysqli_prepare($conn, $delete_sql);
mysqli_stmt_bind_param($delete_stmt, "s", $email);
mysqli_stmt_execute($delete_stmt);
echo "✓ Removed existing user with email: $email<br>";

// Insert new admin
$insert_sql = "INSERT INTO Users (StudentID, Name, Email, PasswordHash, Role, PointsBalance, MembershipStatus, CreatedAt) 
               VALUES (?, ?, ?, ?, 'Admin', 0, 'Active', NOW())";
$insert_stmt = mysqli_prepare($conn, $insert_sql);
mysqli_stmt_bind_param($insert_stmt, "ssss", $student_id, $name, $email, $hash);

if (mysqli_stmt_execute($insert_stmt)) {
    echo "✓ Admin user created successfully!<br><br>";
    
    // Verify the password works
    $verify_sql = "SELECT * FROM Users WHERE Email = ?";
    $verify_stmt = mysqli_prepare($conn, $verify_sql);
    mysqli_stmt_bind_param($verify_stmt, "s", $email);
    mysqli_stmt_execute($verify_stmt);
    $result = mysqli_stmt_get_result($verify_stmt);
    $user = mysqli_fetch_assoc($result);
    
    if (password_verify($password, $user['PasswordHash'])) {
        echo "<span style='color: green; font-weight: bold;'>✅ Password verification successful!</span><br>";
    } else {
        echo "<span style='color: red; font-weight: bold;'>❌ Password verification failed!</span><br>";
    }
    
    echo "<br><br>";
    echo "<strong>Login Details:</strong><br>";
    echo "URL: <a href='admin/login.php'>" . $_SERVER['HTTP_HOST'] . "/admin/login.php</a><br>";
    echo "Email: $email<br>";
    echo "Password: $password<br>";
    
} else {
    echo "❌ Error: " . mysqli_error($conn);
}
?>