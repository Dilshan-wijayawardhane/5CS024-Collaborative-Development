<?php
require_once 'config.php';
require_once 'functions.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get all notifications for this user
$sql = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Mark all as read when viewing page
$mark_sql = "UPDATE notifications SET is_read = TRUE WHERE user_id = ?";
$mark_stmt = mysqli_prepare($conn, $mark_sql);
mysqli_stmt_bind_param($mark_stmt, "i", $user_id);
mysqli_stmt_execute($mark_stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - Synergy Hub</title>
    <link rel="stylesheet" href="style.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
        }
        
        body {
            min-height: 100vh;
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
        
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 32px;
            background: rgba(0,0,0,0.2);
            backdrop-filter: blur(10px);
        }
        
        .logo {
            font-size: 24px;
            font-weight: 700;
            color: white;
        }
        
        .logo span {
            color: #22d3ee;
        }
        
        .icons {
            display: flex;
            gap: 20px;
            align-items: center;
        }
        
        .menu-btn {
            color: white;
            font-size: 24px;
            cursor: pointer;
        }
        
        .points {
            display: flex;
            align-items: center;
            gap: 6px;
            font-weight: 600;
            padding: 8px 15px;
            border-radius: 20px;
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(10px);
            color: white;
        }
        
        .home-link {
            color: white;
            font-size: 20px;
            text-decoration: none;
        }
        
        .sidebar {
            position: fixed;
            left: -260px;
            top: 0;
            width: 260px;
            height: 100%;
            background: #0f172a;
            padding-top: 70px;
            transition: .35s;
            z-index: 9999;
        }
        
        .sidebar a {
            display: block;
            padding: 15px 20px;
            color: white;
            text-decoration: none;
            opacity: .8;
            transition: all 0.3s;
        }
        
        .sidebar a:hover {
            opacity: 1;
            background: #1e293b;
            padding-left: 30px;
        }
        
        .container {
            padding: 30px;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .page-title {
            color: white;
            font-size: 32px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .clear-btn {
            padding: 10px 20px;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 30px;
            color: white;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .clear-btn:hover {
            background: rgba(255,255,255,0.2);
        }
        
        .notifications-list {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .notification-item {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            padding: 20px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .notification-item:last-child {
            border-bottom: none;
        }
        
        .notification-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        
        .notification-icon.gym {
            background: #dbeafe;
            color: #2563eb;
        }
        
        .notification-icon.event {
            background: #dcfce7;
            color: #16a34a;
        }
        
        .notification-icon.transport {
            background: #fff7ed;
            color: #ea580c;
        }
        
        .notification-icon.general {
            background: #f3e8ff;
            color: #9333ea;
        }
        
        .notification-content {
            flex: 1;
        }
        
        .notification-title {
            font-weight: 600;
            color: #111827;
            font-size: 16px;
            margin-bottom: 5px;
        }
        
        .notification-message {
            color: #4b5563;
            font-size: 14px;
            margin-bottom: 8px;
        }
        
        .notification-time {
            color: #6b7280;
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .no-notifications {
            padding: 50px;
            text-align: center;
            color: #6b7280;
        }
        
        .no-notifications i {
            font-size: 48px;
            color: #9ca3af;
            margin-bottom: 15px;
        }
        
        .back-btn {
            display: inline-block;
            margin-top: 30px;
            color: white;
            text-decoration: none;
            font-size: 16px;
        }
        
        .back-btn:hover {
            color: #22d3ee;
        }
    </style>
</head>
<body>

<div class="bg"></div>

<!-- SIDEBAR -->
<div id="sidebar" class="sidebar">
    <a href="index.php">Home</a>
    <a href="facilities.php">Facilities</a>
    <a href="transport.php">Transport</a>
    <a href="game.php">Game Field</a>
    <a href="clubs.php">Club Hub</a>
    <a href="qr.html">QR Scanner</a>
    <a href="notifications.php">Notifications</a>
</div>

<!-- NAVBAR -->
<header class="navbar">
    <div class="menu-btn" onclick="toggleSidebar()">
        <i class="fa-solid fa-bars"></i>
    </div>
    
    <h1 class="logo">Synergy <span>Hub</span> - Notifications</h1>
    
    <div class="icons">
        <a href="index.php" class="home-link">
            <i class="fa-solid fa-home"></i>
        </a>
    </div>
</header>

<!-- MAIN CONTENT -->
<div class="container">
    <div class="page-title">
        <span>🔔 All Notifications</span>
        <button class="clear-btn" onclick="clearAll()">Clear All</button>
    </div>
    
    <div class="notifications-list">
        <?php if(mysqli_num_rows($result) > 0): ?>
            <?php while($notif = mysqli_fetch_assoc($result)): ?>
                <?php
                $icon = 'fa-bell';
                if($notif['type'] == 'gym') $icon = 'fa-dumbbell';
                else if($notif['type'] == 'event') $icon = 'fa-calendar';
                else if($notif['type'] == 'transport') $icon = 'fa-bus';
                ?>
                <div class="notification-item">
                    <div class="notification-icon <?php echo $notif['type']; ?>">
                        <i class="fa-solid <?php echo $icon; ?>"></i>
                    </div>
                    <div class="notification-content">
                        <div class="notification-title"><?php echo htmlspecialchars($notif['title']); ?></div>
                        <div class="notification-message"><?php echo htmlspecialchars($notif['message']); ?></div>
                        <div class="notification-time">
                            <i class="fa-regular fa-clock"></i> 
                            <?php echo date('g:i A • M d, Y', strtotime($notif['created_at'])); ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="no-notifications">
                <i class="fa-regular fa-bell-slash"></i>
                <p>No notifications yet</p>
            </div>
        <?php endif; ?>
    </div>
    
    <a href="index.php" class="back-btn">
        <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
    </a>
</div>

<script>
function toggleSidebar() {
    const sidebar = document.querySelector(".sidebar");
    sidebar.style.left = sidebar.style.left === "0px" ? "-260px" : "0px";
}

document.addEventListener("click", function(e) {
    const sidebar = document.querySelector(".sidebar");
    const btn = document.querySelector(".menu-btn");
    if(sidebar && btn && !sidebar.contains(e.target) && !btn.contains(e.target)) {
        sidebar.style.left = "-260px";
    }
});

function clearAll() {
    if(confirm('Clear all notifications?')) {
        fetch('mark_notification_read.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            }
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                location.reload();
            }
        });
    }
}
</script>

</body>
</html>