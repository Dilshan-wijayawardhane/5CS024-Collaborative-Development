<?php
require_once 'config.php';
require_once 'functions.php';

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $student_id = $_POST['student_id'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    

    if ($password != $confirm_password) {
        $error = "Passwords do not match!";
    } else {
        

        $check_sql = "SELECT * FROM Users WHERE Email = ? OR StudentID = ?";
        $check_stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($check_stmt, "ss", $email, $student_id);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);
        
        if (mysqli_num_rows($check_result) > 0) {
            $error = "Email or Student ID already exists!";
        } else {
            

            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            

            $insert_sql = "INSERT INTO Users (StudentID, Name, Email, PasswordHash, Role, PointsBalance, MembershipStatus) 
                          VALUES (?, ?, ?, ?, 'User', 0, 'Active')";
            $insert_stmt = mysqli_prepare($conn, $insert_sql);
            mysqli_stmt_bind_param($insert_stmt, "ssss", $student_id, $name, $email, $hashed_password);
            
            if (mysqli_stmt_execute($insert_stmt)) {
                $success = "Registration successful! Please login.";
                
                
                $new_user_id = mysqli_insert_id($conn);
                
                
                logActivity($conn, $new_user_id, 'REGISTER');
            } else {
                $error = "Error: " . mysqli_error($conn);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register - Synergy Hub</title>
    <link rel="stylesheet" href="style.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
        }
        
        body.auth {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            position: relative;
        }
        
        
        .bg {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: -1;
        }
        
        .bg::before {
            content: "";
            position: absolute;
            inset: 0;
            background-image: url("campus.jpg");
            background-size: cover;
            background-position: center;
            filter: blur(4px) brightness(0.65);
            transform: scale(1.05);
            pointer-events: none;
        }
        
        .auth-container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            z-index: 1;
        }
        
        .auth-box {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 450px;
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.15); 
            border: 1px solid rgba(255, 255, 255, 0.25);
        }
        
        .auth-box h2 {
            color: white;
            margin-bottom: 30px;
            text-align: center;
            font-size: 28px;
            font-weight: 600;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
        
        .auth-box input {
            width: 100%;
            padding: 12px 15px;
            margin-bottom: 15px;
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 10px;
            font-size: 15px;
            transition: border-color 0.3s;
            background: rgba(255,255,255,0.9);
        }
        
        .auth-box input:focus {
            outline: none;
            border-color: #667eea;
            background: white;
        }
        
        .auth-box button {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.3s, box-shadow 0.3s;
            margin: 10px 0 15px;
        }
        
        .auth-box button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }
        
        .auth-box p {
            text-align: center;
            color: white;
            text-shadow: 0 1px 2px rgba(0,0,0,0.3);
        }
        
        .auth-box a {
            color: #22d3ee;
            text-decoration: none;
            font-weight: 500;
        }
        
        .auth-box a:hover {
            text-decoration: underline;
        }
        
        .footer {
            text-align: center;
            padding: 20px;
            color: rgba(255,255,255,0.8);
            font-size: 14px;
            position: relative;
            z-index: 1;
            text-shadow: 0 1px 2px rgba(0,0,0,0.3);
        }
        
        .error {
            background: rgba(255, 0, 0, 0.2);
            color: white;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 14px;
            border: 1px solid rgba(255, 0, 0, 0.3);
            backdrop-filter: blur(5px);
        }
        
        .success {
            background: rgba(0, 255, 0, 0.2);
            color: white;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 14px;
            border: 1px solid rgba(0, 255, 0, 0.3);
            backdrop-filter: blur(5px);
        }
    </style>
</head>
<body class="auth">

<div class="bg"></div>

<div class="auth-container">
    <div class="auth-box">
        <h2>Create Account</h2>
        
        <?php if($error): ?>
            <div class="error"><?php echo escape($error); ?></div>
        <?php endif; ?>
        
        <?php if($success): ?>
            <div class="success"><?php echo escape($success); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <input type="text" name="student_id" placeholder="Student ID" required>
            <input type="text" name="name" placeholder="Full Name" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <input type="password" name="confirm_password" placeholder="Confirm Password" required>
            <button type="submit">Register</button>
        </form>
        
        <p>Already have an account? <a href="login.php">Login</a></p>
    </div>
</div>

<footer class="footer">
    All rights reserved
</footer>

</body>
</html>