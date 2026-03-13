<?php
require_once 'config.php';
require_once 'functions.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$facility_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get facility details
$facility_sql = "SELECT * FROM Facilities WHERE FacilityID = ?";
$facility_stmt = mysqli_prepare($conn, $facility_sql);
mysqli_stmt_bind_param($facility_stmt, "i", $facility_id);
mysqli_stmt_execute($facility_stmt);
$facility_result = mysqli_stmt_get_result($facility_stmt);
$facility = mysqli_fetch_assoc($facility_result);

// Check if user already checked in today
$check_sql = "SELECT * FROM CheckIns WHERE UserID = ? AND FacilityID = ? AND DATE(Timestamp) = CURDATE()";
$check_stmt = mysqli_prepare($conn, $check_sql);
mysqli_stmt_bind_param($check_stmt, "ii", $user_id, $facility_id);
mysqli_stmt_execute($check_stmt);
$check_result = mysqli_stmt_get_result($check_stmt);
$already_checked_in = mysqli_num_rows($check_result) > 0;

// Get user points
$user_sql = "SELECT PointsBalance FROM Users WHERE UserID = ?";
$user_stmt = mysqli_prepare($conn, $user_sql);
mysqli_stmt_bind_param($user_stmt, "i", $user_id);
mysqli_stmt_execute($user_stmt);
$user_result = mysqli_stmt_get_result($user_stmt);
$user = mysqli_fetch_assoc($user_result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($facility['Name']); ?> - Synergy Hub</title>
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
            max-width: 1200px;
            margin: 0 auto;
        }
        
        /* FACILITY HEADER */
        .facility-header {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 30px;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .facility-icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            color: white;
        }
        
        .facility-info {
            flex: 1;
        }
        
        .facility-name {
            font-size: 36px;
            color: white;
            margin-bottom: 10px;
        }
        
        .facility-type {
            color: #22d3ee;
            font-size: 18px;
            margin-bottom: 10px;
        }
        
        .facility-status {
            display: inline-block;
            padding: 8px 20px;
            border-radius: 30px;
            font-size: 14px;
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
        
        /* CHECK-IN SECTION */
        .checkin-section {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            border: 1px solid rgba(255,255,255,0.2);
            text-align: center;
        }
        
        .points-info {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 30px;
            margin: 20px 0;
            flex-wrap: wrap;
        }
        
        .points-badge {
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(10px);
            color: white;
            padding: 15px 30px;
            border-radius: 50px;
            font-size: 20px;
            font-weight: 600;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .points-badge i {
            color: #22d3ee;
            margin-right: 8px;
        }
        
        .checkin-btn {
            padding: 15px 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 50px;
            color: white;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .checkin-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        
        .checkin-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }
        
        .checkin-message {
            margin-top: 15px;
            color: #22d3ee;
            font-size: 16px;
        }
        
        /* FEATURES GRID */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }
        
        .feature-card {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            border: 1px solid rgba(255,255,255,0.2);
            transition: all 0.3s;
            cursor: pointer;
            opacity: 0.5;
            pointer-events: none;
            text-align: center;
            text-decoration: none;
            color: white;
            display: block;
        }
        
        .feature-card.active {
            opacity: 1;
            pointer-events: all;
        }
        
        .feature-card.active:hover {
            transform: translateY(-5px);
            background: rgba(255,255,255,0.15);
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        
        .feature-icon {
            font-size: 48px;
            color: #22d3ee;
            margin-bottom: 20px;
        }
        
        .feature-title {
            color: white;
            font-size: 22px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .feature-description {
            color: rgba(255,255,255,0.7);
            font-size: 14px;
            line-height: 1.5;
        }
        
        .back-btn {
            display: inline-block;
            margin-top: 40px;
            color: white;
            text-decoration: none;
            font-size: 16px;
            padding: 10px 20px;
            background: rgba(255,255,255,0.1);
            border-radius: 30px;
            transition: all 0.3s;
        }
        
        .back-btn:hover {
            background: rgba(255,255,255,0.2);
            color: #22d3ee;
        }
        
        .back-btn i {
            margin-right: 8px;
        }
        
        @media (max-width: 768px) {
            .facility-header {
                flex-direction: column;
                text-align: center;
            }
            
            .points-info {
                flex-direction: column;
            }
            
            .features-grid {
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
    
    <h1 class="logo">Synergy <span>Hub</span></h1>
    
    <div class="icons">
        <div class="points">
            <i class="fa-solid fa-star"></i>
            <span id="pointsDisplay"><?php echo $user['PointsBalance']; ?></span>
        </div>
        <a href="facilities.php" class="home-link">
            <i class="fa-solid fa-arrow-left"></i> Back
        </a>
    </div>
</header>

<!-- MAIN CONTENT -->
<div class="container">
    
    <!-- FACILITY HEADER -->
    <div class="facility-header">
        <?php
        $icon = 'fa-building';
        if($facility['Type'] == 'Gym') $icon = 'fa-dumbbell';
        else if($facility['Type'] == 'Library') $icon = 'fa-book';
        else if($facility['Type'] == 'Café') $icon = 'fa-mug-saucer';
        else if($facility['Type'] == 'GameField') $icon = 'fa-futbol';
        else if($facility['Type'] == 'Transport') $icon = 'fa-bus';
        ?>
        <div class="facility-icon">
            <i class="fa-solid <?php echo $icon; ?>"></i>
        </div>
        <div class="facility-info">
            <h1 class="facility-name"><?php echo htmlspecialchars($facility['Name']); ?></h1>
            <div class="facility-type"><?php echo htmlspecialchars($facility['Type']); ?></div>
            <div class="facility-status status-<?php echo $facility['Status']; ?>">
                <?php echo $facility['Status']; ?>
            </div>
        </div>
    </div>
    
    <!-- CHECK-IN SECTION -->
    <div class="checkin-section">
        <div class="points-info">
            <div class="points-badge">
                <i class="fa-solid fa-star"></i> Your Points: <span id="currentPoints"><?php echo $user['PointsBalance']; ?></span>
            </div>
            <button class="checkin-btn" id="checkinBtn" onclick="checkIn(<?php echo $facility_id; ?>)"
                <?php echo ($already_checked_in || $facility['Status'] != 'Open') ? 'disabled' : ''; ?>>
                <i class="fa-solid fa-location-dot"></i> Check In (+10 points)
            </button>
        </div>
        <div class="checkin-message" id="checkinMessage">
            <?php if($already_checked_in): ?>
                ✅ You have already checked in today! Access all features below.
            <?php elseif($facility['Status'] != 'Open'): ?>
                ⚠️ This facility is currently closed.
            <?php else: ?>
                Check in to access facility features and earn 10 points!
            <?php endif; ?>
        </div>
    </div>
    
    <!-- FACILITY FEATURES -->
    <h2 style="color: white; margin: 40px 0 20px; font-size: 28px;">📍 Facility Features</h2>
    <div class="features-grid">
        
        <?php if($facility['Type'] == 'Café'): ?>
        <!-- ==================== CAFE FEATURES ==================== -->
        <a href="cafe_menu.php?id=<?php echo $facility_id; ?>" class="feature-card <?php echo $already_checked_in ? 'active' : ''; ?>">
            <div class="feature-icon">
                <i class="fa-solid fa-mug-saucer"></i>
            </div>
            <div class="feature-title">View Menu</div>
            <div class="feature-description">
                Browse our full menu with prices, photos, and availability. See what's fresh today!
            </div>
        </a>
        
        <a href="cafe_order.php?id=<?php echo $facility_id; ?>" class="feature-card <?php echo $already_checked_in ? 'active' : ''; ?>">
            <div class="feature-icon">
                <i class="fa-solid fa-cart-shopping"></i>
            </div>
            <div class="feature-title">Order Food</div>
            <div class="feature-description">
                Place orders for pickup. Add items to cart, choose quantity, and pay with cash or points!
            </div>
        </a>
        
        <a href="special_offers.php?id=<?php echo $facility_id; ?>" class="feature-card <?php echo $already_checked_in ? 'active' : ''; ?>">
            <div class="feature-icon">
                <i class="fa-solid fa-tag"></i>
            </div>
            <div class="feature-title">Special Offers</div>
            <div class="feature-description">
                Check daily specials and combo offers. Save points on selected items!
            </div>
        </a>
        
        <a href="reserve_table.php?id=<?php echo $facility_id; ?>" class="feature-card <?php echo $already_checked_in ? 'active' : ''; ?>">
            <div class="feature-icon">
                <i class="fa-solid fa-chair"></i>
            </div>
            <div class="feature-title">Reserve Table</div>
            <div class="feature-description">
                Reserve a table for you and your friends during peak hours.
            </div>
        </a>
        
        <?php elseif($facility['Type'] == 'Library'): ?>
        <!-- ==================== LIBRARY FEATURES ==================== -->
        <a href="library_books.php?id=<?php echo $facility_id; ?>" class="feature-card <?php echo $already_checked_in ? 'active' : ''; ?>">
            <div class="feature-icon">
                <i class="fa-solid fa-book-open"></i>
            </div>
            <div class="feature-title">Browse Books</div>
            <div class="feature-description">
                Search and browse available books in the library catalog. View book details, authors, and availability.
            </div>
        </a>
        
        <a href="library_books.php?id=<?php echo $facility_id; ?>" class="feature-card <?php echo $already_checked_in ? 'active' : ''; ?>">
            <div class="feature-icon">
                <i class="fa-solid fa-book"></i>
            </div>
            <div class="feature-title">Borrow Books</div>
            <div class="feature-description">
                Borrow books from the library. 14-day lending period. Earn 5 points when you borrow!
            </div>
        </a>
        
        <div class="feature-card <?php echo $already_checked_in ? 'active' : ''; ?>" onclick="featureAction('Study Rooms')">
            <div class="feature-icon">
                <i class="fa-solid fa-door-open"></i>
            </div>
            <div class="feature-title">Study Rooms</div>
            <div class="feature-description">
                Reserve study rooms for group work. Free WiFi, whiteboards, and projectors available.
            </div>
        </div>
        
        <div class="feature-card <?php echo $already_checked_in ? 'active' : ''; ?>" onclick="featureAction('Print Services')">
            <div class="feature-icon">
                <i class="fa-solid fa-print"></i>
            </div>
            <div class="feature-title">Print Services</div>
            <div class="feature-description">
                Print, scan, and photocopy documents. Pay with points or cash.
            </div>
        </div>
        
        <?php elseif($facility['Type'] == 'Gym'): ?>
        <!-- ==================== GYM FEATURES ==================== -->
        <a href="gym_equipment.php?id=<?php echo $facility_id; ?>" class="feature-card <?php echo $already_checked_in ? 'active' : ''; ?>">
            <div class="feature-icon">
                <i class="fa-solid fa-dumbbell"></i>
            </div>
            <div class="feature-title">Gym Equipment</div>
            <div class="feature-description">
                View all available gym equipment. Treadmills, dumbbells, bench press, and more.
            </div>
        </a>
        
        <a href="gym_equipment.php?id=<?php echo $facility_id; ?>" class="feature-card <?php echo $already_checked_in ? 'active' : ''; ?>">
            <div class="feature-icon">
                <i class="fa-solid fa-people-group"></i>
            </div>
            <div class="feature-title">Fitness Classes</div>
            <div class="feature-description">
                Join yoga, zumba, HIIT, and strength training classes. Limited spots available!
            </div>
        </a>
        
        <div class="feature-card <?php echo $already_checked_in ? 'active' : ''; ?>" onclick="featureAction('Personal Trainer')">
            <div class="feature-icon">
                <i class="fa-solid fa-user"></i>
            </div>
            <div class="feature-title">Personal Trainer</div>
            <div class="feature-description">
                Book a personal trainer session. Get personalized workout plans and guidance.
            </div>
        </div>
        
        <div class="feature-card <?php echo $already_checked_in ? 'active' : ''; ?>" onclick="featureAction('Locker Room')">
            <div class="feature-icon">
                <i class="fa-solid fa-locker"></i>
            </div>
            <div class="feature-title">Locker Room</div>
            <div class="feature-description">
                Access locker rooms with showers and changing facilities.
            </div>
        </div>
        
        <?php elseif($facility['Type'] == 'GameField'): ?>
        <!-- ==================== GAME FIELD FEATURES ==================== -->
        <a href="game_field.php?id=<?php echo $facility_id; ?>" class="feature-card <?php echo $already_checked_in ? 'active' : ''; ?>">
            <div class="feature-icon">
                <i class="fa-solid fa-calendar-check"></i>
            </div>
            <div class="feature-title">Book Field</div>
            <div class="feature-description">
                Reserve sports fields for matches and practice sessions.
            </div>
        </a>
        
        <div class="feature-card <?php echo $already_checked_in ? 'active' : ''; ?>" onclick="featureAction('Sports Equipment')">
            <div class="feature-icon">
                <i class="fa-solid fa-futbol"></i>
            </div>
            <div class="feature-title">Sports Equipment</div>
            <div class="feature-description">
                Borrow sports equipment: balls, nets, rackets, and more.
            </div>
        </div>
        
        <div class="feature-card <?php echo $already_checked_in ? 'active' : ''; ?>" onclick="featureAction('Tournaments')">
            <div class="feature-icon">
                <i class="fa-solid fa-trophy"></i>
            </div>
            <div class="feature-title">Tournaments</div>
            <div class="feature-description">
                Join upcoming tournaments and win points and prizes!
            </div>
        </div>
        
        <div class="feature-card <?php echo $already_checked_in ? 'active' : ''; ?>" onclick="featureAction('Score Board')">
            <div class="feature-icon">
                <i class="fa-solid fa-chart-simple"></i>
            </div>
            <div class="feature-title">Score Board</div>
            <div class="feature-description">
                View live scores and match schedules.
            </div>
        </div>
        
        <?php elseif($facility['Type'] == 'Transport'): ?>
        <!-- ==================== TRANSPORT FEATURES ==================== -->
        <a href="transport.php" class="feature-card <?php echo $already_checked_in ? 'active' : ''; ?>">
            <div class="feature-icon">
                <i class="fa-solid fa-bus"></i>
            </div>
            <div class="feature-title">View Routes</div>
            <div class="feature-description">
                Check all bus routes, schedules, and live bus tracking.
            </div>
        </a>
        
        <a href="transport.php" class="feature-card <?php echo $already_checked_in ? 'active' : ''; ?>">
            <div class="feature-icon">
                <i class="fa-solid fa-ticket"></i>
            </div>
            <div class="feature-title">Buy Passes</div>
            <div class="feature-description">
                Purchase transport passes using your points. Valid for 30 days!
            </div>
        </a>
        
        <div class="feature-card <?php echo $already_checked_in ? 'active' : ''; ?>" onclick="featureAction('Live Tracking')">
            <div class="feature-icon">
                <i class="fa-solid fa-location-dot"></i>
            </div>
            <div class="feature-title">Live Tracking</div>
            <div class="feature-description">
                Track buses in real-time on the map. See exactly where your bus is!
            </div>
        </div>
        
        <div class="feature-card <?php echo $already_checked_in ? 'active' : ''; ?>" onclick="featureAction('Schedule')">
            <div class="feature-icon">
                <i class="fa-regular fa-clock"></i>
            </div>
            <div class="feature-title">Time Table</div>
            <div class="feature-description">
                View complete bus schedule for all routes.
            </div>
        </div>
        
        <?php endif; ?>
    </div>
    
    <a href="facilities.php" class="back-btn">
        <i class="fa-solid fa-arrow-left"></i> Back to Facilities
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

// Check-in function
function checkIn(facilityId) {
    fetch('checkin.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'facility_id=' + facilityId
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            // Update points display
            let pointsSpan = document.getElementById('pointsDisplay');
            let currentPoints = parseInt(pointsSpan.textContent);
            pointsSpan.textContent = data.new_points;
            
            document.getElementById('currentPoints').textContent = data.new_points;
            
            // Animate points
            document.querySelector('.points').classList.add('active');
            setTimeout(() => {
                document.querySelector('.points').classList.remove('active');
            }, 500);
            
            // Update UI
            document.getElementById('checkinBtn').disabled = true;
            document.getElementById('checkinMessage').innerHTML = '✅ Check-in successful! +10 points added. You can now access all features.';
            
            // Activate all feature cards
            document.querySelectorAll('.feature-card').forEach(card => {
                card.classList.add('active');
            });
            
        } else {
            alert('Error: ' + data.message);
        }
    });
}

// Feature action for placeholder features
function featureAction(feature) {
    alert(`🔧 "${feature}" feature is coming soon! We're working on it.`);
}
</script>

</body>
</html>