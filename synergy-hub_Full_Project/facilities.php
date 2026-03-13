<?php
require_once 'config.php';
require_once 'functions.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get all facilities
$sql = "SELECT * FROM Facilities ORDER BY Type, Name";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Get user points
$user_sql = "SELECT PointsBalance FROM Users WHERE UserID = ?";
$user_stmt = mysqli_prepare($conn, $user_sql);
mysqli_stmt_bind_param($user_stmt, "i", $user_id);
mysqli_stmt_execute($user_stmt);
$user_result = mysqli_stmt_get_result($user_stmt);
$user = mysqli_fetch_assoc($user_result);

// Crowd data (you can make this dynamic from database)
$crowd_data = [
    'Gym' => ['current' => 82, 'total' => 150, 'hours' => '06:00 - 22:00'],
    'Library' => ['current' => 234, 'total' => 300, 'hours' => '08:00 - 23:59'],
    'Café' => ['current' => 45, 'total' => 80, 'hours' => '07:00 - 21:00'],
    'GameField' => ['current' => 28, 'total' => 100, 'hours' => '09:00 - 20:00'],
    'Transport' => ['current' => 12, 'total' => 50, 'hours' => '24/7'],
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <meta charset="UTF-8">
    <title>Facilities - Synergy Hub</title>
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
        
        .page-title {
            text-align: center;
            color: white;
            font-size: 36px;
            margin: 30px 0;
            text-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }
        
        /* FACILITIES GRID */
        .facility-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
            padding: 20px;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .facility-card {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 25px;
            border: 1px solid rgba(255,255,255,0.2);
            transition: all 0.3s;
            cursor: pointer;
            text-decoration: none;
            color: white;
            display: block;
        }
        
        .facility-card:hover {
            transform: translateY(-5px);
            background: rgba(255,255,255,0.15);
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        
        .facility-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .facility-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: white;
        }
        
        .facility-name {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .facility-type {
            color: #22d3ee;
            font-size: 14px;
        }
        
        .facility-status {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin: 10px 0;
        }
        
        .status-Open {
            background: #10b981;
            color: white;
        }
        
        .status-Closed {
            background: #ef4444;
            color: white;
        }
        
        .status-Maintenance {
            background: #f59e0b;
            color: white;
        }
        
        .facility-details {
            color: rgba(255,255,255,0.8);
            margin: 10px 0;
            line-height: 1.6;
        }
        
        .facility-details i {
            color: #22d3ee;
            width: 20px;
            margin-right: 5px;
        }
        
        /* NEW: Crowd Info with Progress Bar */
        .crowd-info {
            margin: 15px 0;
            padding: 10px;
            background: rgba(0,0,0,0.2);
            border-radius: 12px;
        }
        
        .crowd-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 13px;
            color: rgba(255,255,255,0.9);
        }
        
        .crowd-bar {
            width: 100%;
            height: 8px;
            background: rgba(255,255,255,0.2);
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 5px;
        }
        
        .crowd-fill {
            height: 100%;
            background: linear-gradient(90deg, #22d3ee, #667eea);
            border-radius: 10px;
            transition: width 0.3s;
        }
        
        .crowd-numbers {
            font-size: 13px;
            font-weight: 600;
            color: #22d3ee;
        }
        
        /* Hours */
        .hours {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 10px 0;
            color: rgba(255,255,255,0.7);
            font-size: 13px;
        }
        
        .hours i {
            color: #22d3ee;
        }
        
        /* Cafe Special */
        .cafe-special {
            margin: 10px 0;
            padding: 10px;
            background: rgba(0,0,0,0.2);
            border-radius: 12px;
        }
        
        .cuisine-tags {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 8px;
        }
        
        .cuisine-tag {
            background: rgba(255,255,255,0.1);
            padding: 4px 10px;
            border-radius: 30px;
            font-size: 11px;
            color: white;
        }
        
        .rating {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
        }
        
        .stars {
            color: #fbbf24;
        }
        
        .reviews {
            color: rgba(255,255,255,0.7);
            font-size: 12px;
        }
        
        .checkin-badge {
            margin-top: 15px;
            padding: 8px;
            background: rgba(255,255,255,0.05);
            border-radius: 10px;
            text-align: center;
            font-size: 14px;
            color: #22d3ee;
        }
        
        .checkin-badge i {
            margin-right: 5px;
        }
        
        @media (max-width: 768px) {
            .facility-grid {
                grid-template-columns: 1fr;
            }
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
</div>

<!-- NAVBAR -->
<header class="navbar">
    <div class="menu-btn" onclick="toggleSidebar()">
        <i class="fa-solid fa-bars"></i>
    </div>
    
    <h1 class="logo">Synergy <span>Hub</span> - Facilities</h1>
    
    <div class="icons">
        <div class="points">
            <i class="fa-solid fa-star"></i>
            <span><?php echo $user['PointsBalance']; ?></span>
        </div>
        <a href="index.php" class="home-link">
            <i class="fa-solid fa-home"></i>
        </a>
    </div>
</header>

<!-- PAGE TITLE -->
<h1 class="page-title">🏛️ Campus Facilities</h1>

<!-- FACILITIES GRID -->
<div class="facility-grid">
    <?php while($facility = mysqli_fetch_assoc($result)): 
        $type = $facility['Type'];
        $crowd = isset($crowd_data[$type]) ? $crowd_data[$type] : ['current' => 0, 'total' => 100, 'hours' => '09:00 - 17:00'];
        $crowd_percent = ($crowd['current'] / $crowd['total']) * 100;
        
        // Set icon based on type
        $icon = 'fa-building';
        if($type == 'Gym') $icon = 'fa-dumbbell';
        else if($type == 'Library') $icon = 'fa-book';
        else if($type == 'Café') $icon = 'fa-mug-saucer';
        else if($type == 'GameField') $icon = 'fa-futbol';
        else if($type == 'Transport') $icon = 'fa-bus';
    ?>
    <a href="facility_details.php?id=<?php echo $facility['FacilityID']; ?>" class="facility-card">
        <div class="facility-header">
            <div class="facility-icon">
                <i class="fa-solid <?php echo $icon; ?>"></i>
            </div>
            <div>
                <div class="facility-name"><?php echo htmlspecialchars($facility['Name']); ?></div>
                <div class="facility-type"><?php echo $type; ?></div>
            </div>
        </div>
        
        <div class="facility-status status-<?php echo $facility['Status']; ?>">
            <?php echo $facility['Status']; ?>
        </div>
        
        <!-- CROWD INFO WITH PROGRESS BAR (NEW) -->
        <div class="crowd-info">
            <div class="crowd-header">
                <span>Current Crowd</span>
                <span class="crowd-numbers"><?php echo $crowd['current']; ?>/<?php echo $crowd['total']; ?></span>
            </div>
            <div class="crowd-bar">
                <div class="crowd-fill" style="width: <?php echo $crowd_percent; ?>%;"></div>
            </div>
        </div>
        
        <!-- HOURS -->
        <div class="hours">
            <i class="fa-regular fa-calendar"></i>
            <span><?php echo $crowd['hours']; ?></span>
        </div>
        
        <!-- CAFE SPECIAL INFO (Only for Café) -->
        <?php if($type == 'Café'): ?>
        <div class="cafe-special">
            <div class="cuisine-tags">
                <span class="cuisine-tag">International</span>
                <span class="cuisine-tag">Sri Lankan</span>
                <span class="cuisine-tag">Fast Food</span>
            </div>
            <div class="rating">
                <div class="stars">
                    <i class="fa-solid fa-star"></i>
                    <i class="fa-solid fa-star"></i>
                    <i class="fa-solid fa-star"></i>
                    <i class="fa-solid fa-star"></i>
                    <i class="fa-solid fa-star-half-alt"></i>
                </div>
                <span class="reviews">4.5 (128 reviews)</span>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="checkin-badge">
            <i class="fa-solid fa-location-dot"></i> Click to Check In (+10 points)
        </div>
    </a>
    <?php endwhile; ?>
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
</script>

</body>
</html>