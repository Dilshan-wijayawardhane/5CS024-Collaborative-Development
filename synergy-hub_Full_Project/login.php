<?php


require_once 'config.php';
require_once 'functions.php';



$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    

    $sql = "SELECT * FROM Users WHERE Email = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        
        


        if (password_verify($password, $user['PasswordHash'])) {
            

            $_SESSION['user_id'] = $user['UserID'];
            $_SESSION['user_name'] = $user['Name'];
            $_SESSION['user_role'] = $user['Role'];
            $_SESSION['logged_in'] = true;
            
            

            session_regenerate_id(true);
            

            logActivity($conn, $user['UserID'], 'LOGIN');
            
            
            
            if ($user['Role'] === 'Admin') {
                header("Location: admin/index.php");
            } else {
                header("Location: index.php");
            }
            exit();
        } else {
            $error = "Invalid password!";
        }
    } else {
        $error = "Email not found!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Synergy Hub Login</title>
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
            background-image: url('loginimage.jpg');
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
            background: #ffffff;  /* සුදු පසුබිම */
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 400px;
            backdrop-filter: none;  /* blur ඉවත් කරන්න */
            border: 1px solid rgba(0, 0, 0, 0.1);
}
        
        .auth-box h2 {
            color: #1e4a76;  /* ගැඹුරු නිල් */
            margin-bottom: 30px;
            text-align: center;
            font-size: 28px;
            font-weight: 600;
            text-shadow: none;
}
        /* Profile Icon Container - Center */
.profile-icon-container {
    display: flex;
    justify-content: center;
    align-items: center;
    margin-bottom: 25px;
}

.profile-icon {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #e0f2fe 0%, #bae6fd 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
    border: 3px solid #1e4a76;
    box-shadow: 0 4px 15px rgba(30, 74, 118, 0.2);
}

.profile-icon:hover {
    transform: scale(1.05);
    box-shadow: 0 8px 25px rgba(30, 74, 118, 0.3);
    border-color: #2c7da0;
}

.profile-icon svg {
    transition: transform 0.3s ease;
}

.profile-icon:hover svg {
    transform: scale(1.1);
}
        
       .auth-box input {
          width: 100%;
          padding: 15px;
          margin-bottom: 15px;
          border: 2px solid #e2e8f0;
          border-radius: 10px;
          font-size: 16px;
          transition: border-color 0.3s;
          background: #ffffff;
          color: #1e293b;
}

       .auth-box input:focus {
        outline: none;
        border-color: #2c7da0;  /* නිල් focus border */
        background: white;
}
        
        .auth-box button {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #1e4a76 0%, #2c7da0 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.3s, box-shadow 0.3s;
            margin-bottom: 15px;
}
        
        .auth-box button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }
        
        .auth-box p {
             text-align: center;
             color: #475569;
             text-shadow: none;
}
        
        .auth-box a {
            color: #2c7da0;
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
            background: #fee2e2;
            color: #b91c1c;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 15px;
            text-align: center;
            font-size: 14px;
            border: 1px solid #fecaca;
}
    </style>
</head>
<body class="auth">

<div class="bg"></div>

<div class="auth-container">
    <div class="auth-box">
        <h2>Synergy Hub</h2>
        <div class="profile-icon-container">
        <div class="profile-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#1e4a76" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
            </svg>
        </div>
    </div>
        
        <?php if($error): ?>
            <div class="error"><?php echo escape($error); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>
        
        <p>First time? <a href="register.php">Register</a></p>
        <p style="margin-top: 10px; font-size: 12px;">Admin users will be redirected to admin panel</p>
    </div>
</div>

<footer class="footer">
    All rights reserved
</footer>

</body>
</html>