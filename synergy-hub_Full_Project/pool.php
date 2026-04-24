<?php
require_once 'config.php';
require_once 'functions.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$facility_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get pool facility details
$pool_sql = "SELECT * FROM Facilities WHERE FacilityID = ? AND Type = 'Pool'";
$pool_stmt = mysqli_prepare($conn, $pool_sql);
mysqli_stmt_bind_param($pool_stmt, "i", $facility_id);
mysqli_stmt_execute($pool_stmt);
$pool_result = mysqli_stmt_get_result($pool_stmt);
$pool = mysqli_fetch_assoc($pool_result);

if (!$pool) {
    header("Location: facilities.php");
    exit();
}

// Parse ExtraInfo JSON
$pool_info = json_decode($pool['ExtraInfo'], true) ?? [];
$lanes = $pool_info['lanes'] ?? 8;
$water_temp = $pool_info['waterTemp'] ?? 27;
$depth = $pool_info['depth'] ?? '1.2m - 2.5m';
$lifeguards = $pool_info['lifeguards'] ?? 4;
$amenities = $pool_info['amenities'] ?? ['Changing Rooms', 'Showers', 'Lockers'];

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

// Generate lane availability (in real app, this would come from database)
$lane_availability = [];
for ($i = 1; $i <= $lanes; $i++) {
    $lane_availability[] = [
        'number' => $i,
        'status' => rand(0, 10) > 3 ? 'available' : 'busy',
        'type' => $i % 2 === 0 ? 'Fast Lane' : 'Medium Lane'
    ];
}

// Generate sessions
$sessions = [
    ['time' => '06:00 - 08:00', 'name' => 'Morning Swim', 'type' => 'morning', 'capacity' => 30, 'booked' => rand(10, 25)],
    ['time' => '08:00 - 12:00', 'name' => 'Lap Swimming', 'type' => 'morning', 'capacity' => 40, 'booked' => rand(20, 35)],
    ['time' => '12:00 - 16:00', 'name' => 'Public Swim', 'type' => 'afternoon', 'capacity' => 50, 'booked' => rand(15, 40)],
    ['time' => '16:00 - 20:00', 'name' => 'Evening Swim', 'type' => 'evening', 'capacity' => 45, 'booked' => rand(25, 40)],
    ['time' => '20:00 - 22:00', 'name' => 'Ladies Only', 'type' => 'evening', 'capacity' => 25, 'booked' => rand(5, 15)]
];

// Calculate available lanes
$available_lanes = 0;
foreach ($lane_availability as $lane) {
    if ($lane['status'] === 'available') $available_lanes++;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pool['Name']); ?> - Synergy Hub</title>
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
            background: linear-gradient(135deg, #0284c7 0%, #0ea5e9 100%);
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
        
        /* Facility Header */
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
            background: linear-gradient(135deg, #0284c7 0%, #0ea5e9 100%);
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
        
        .medical-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 10px;
            background: #f97316;
            color: white;
            border-radius: 30px;
            font-size: 0.7rem;
            font-weight: 600;
            margin-left: 8px;
        }
        
        /* Quick Stats */
        .quick-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin: 20px 0 30px;
        }
        
        .stat-card {
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(10px);
            padding: 15px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 15px;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .stat-card i {
            font-size: 2rem;
            color: #22d3ee;
        }
        
        .stat-card div {
            display: flex;
            flex-direction: column;
        }
        
        .stat-label {
            font-size: 0.8rem;
            color: rgba(255,255,255,0.8);
        }
        
        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: white;
        }
        
        /* Check-in Section */
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
            color: gold;
            margin-right: 8px;
        }
        
        .checkin-btn {
            padding: 15px 40px;
            background: linear-gradient(135deg, #0284c7, #0ea5e9);
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
            box-shadow: 0 5px 20px rgba(2, 132, 199, 0.4);
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
        
        /* Pool Features Grid */
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
            text-decoration: none;
            color: white;
            display: block;
            opacity: 0.5;
            pointer-events: none;
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
        
        /* Lane Availability */
        .lane-section {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            margin: 30px 0;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .section-title {
            color: white;
            font-size: 24px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .section-title i {
            color: #22d3ee;
        }
        
        .lane-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        
        .lane-card {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 12px;
            padding: 15px;
            text-align: center;
            color: white;
        }
        
        .lane-card.available {
            border-left: 4px solid #10b981;
        }
        
        .lane-card.busy {
            border-left: 4px solid #f59e0b;
            opacity: 0.7;
        }
        
        .lane-number {
            font-size: 20px;
            font-weight: 700;
            color: #22d3ee;
            margin-bottom: 5px;
        }
        
        .lane-type {
            font-size: 12px;
            color: rgba(255,255,255,0.7);
        }
        
        /* Sessions */
        .session-list {
            margin: 20px 0;
        }
        
        .session-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background: rgba(255,255,255,0.05);
            border-radius: 12px;
            margin-bottom: 10px;
            border-left: 4px solid;
            color: white;
        }
        
        .session-item.morning { border-left-color: #f59e0b; }
        .session-item.afternoon { border-left-color: #0284c7; }
        .session-item.evening { border-left-color: #8b5cf6; }
        
        .session-time {
            font-weight: 600;
        }
        
        .session-name {
            color: rgba(255,255,255,0.7);
            font-size: 14px;
        }
        
        .session-capacity {
            background: rgba(255,255,255,0.1);
            padding: 5px 15px;
            border-radius: 30px;
            font-size: 14px;
        }
        
        /* Amenities */
        .amenities-list {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin: 20px 0;
        }
        
        .amenity-tag {
            background: rgba(255,255,255,0.1);
            padding: 8px 16px;
            border-radius: 30px;
            font-size: 14px;
            color: white;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .amenity-tag i {
            color: #22d3ee;
        }
        
        /* Rules */
        .rules-list {
            list-style: none;
            margin: 20px 0;
        }
        
        .rules-list li {
            color: rgba(255,255,255,0.8);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .rules-list li i {
            color: #22d3ee;
            width: 20px;
        }
        
        /* Back Button */
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
    
    <h1 class="logo">Synergy <span>Hub</span> - <?php echo htmlspecialchars($pool['Name']); ?></h1>
    
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
        <div class="facility-icon">
            <i class="fa-solid fa-person-swimming"></i>
        </div>
        <div class="facility-info">
            <h1 class="facility-name">
                <?php echo htmlspecialchars($pool['Name']); ?>
                <span class="medical-badge">
                    <i class="fa-solid fa-notes-medical"></i> Medical Required
                </span>
            </h1>
            <div class="facility-type"><?php echo $pool['Type']; ?> • <?php echo $lanes; ?> Lanes</div>
            <div class="facility-status status-<?php echo $pool['Status']; ?>">
                <?php echo $pool['Status']; ?>
            </div>
        </div>
    </div>
    
    <!-- QUICK STATS -->
    <div class="quick-stats">
        <div class="stat-card">
            <i class="fa-solid fa-water"></i>
            <div>
                <span class="stat-label">Water Temp</span>
                <span class="stat-value"><?php echo $water_temp; ?>°C</span>
            </div>
        </div>
        <div class="stat-card">
            <i class="fa-solid fa-arrows-up-down"></i>
            <div>
                <span class="stat-label">Depth</span>
                <span class="stat-value"><?php echo $depth; ?></span>
            </div>
        </div>
        <div class="stat-card">
            <i class="fa-solid fa-people-group"></i>
            <div>
                <span class="stat-label">Lifeguards</span>
                <span class="stat-value"><?php echo $lifeguards; ?></span>
            </div>
        </div>
        <div class="stat-card">
            <i class="fa-solid fa-road"></i>
            <div>
                <span class="stat-label">Available Lanes</span>
                <span class="stat-value"><?php echo $available_lanes; ?>/<?php echo $lanes; ?></span>
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
                <?php echo ($already_checked_in || $pool['Status'] != 'Open') ? 'disabled' : ''; ?>>
                <i class="fa-solid fa-location-dot"></i> Check In (+10 points)
            </button>
        </div>
        <div class="checkin-message" id="checkinMessage">
            <?php if($already_checked_in): ?>
                ✅ You have already checked in today! You can now book lanes.
            <?php elseif($pool['Status'] != 'Open'): ?>
                ⚠️ This pool is currently closed.
            <?php else: ?>
                Check in to book lanes and earn 10 points!
            <?php endif; ?>
        </div>
    </div>
    
    <!-- POOL FEATURES GRID -->
    <h2 class="section-title">
        <i class="fa-solid fa-person-swimming"></i> Pool Features
    </h2>
    <div class="features-grid">
        <a href="pool_booking.php?id=<?php echo $facility_id; ?>" class="feature-card <?php echo $already_checked_in ? 'active' : ''; ?>">
            <div class="feature-icon">
                <i class="fa-solid fa-calendar-check"></i>
            </div>
            <div class="feature-title">Book a Lane</div>
            <div class="feature-description">
                Reserve a swimming lane for your workout. Choose lane type and time slot.
            </div>
        </a>
        
        <a href="pool_schedule.php?id=<?php echo $facility_id; ?>" class="feature-card <?php echo $already_checked_in ? 'active' : ''; ?>">
            <div class="feature-icon">
                <i class="fa-regular fa-clock"></i>
            </div>
            <div class="feature-title">View Schedule</div>
            <div class="feature-description">
                Check daily sessions, peak hours, and lane availability in real-time.
            </div>
        </a>
        
        <a href="pool_medical.php?id=<?php echo $facility_id; ?>" class="feature-card <?php echo $already_checked_in ? 'active' : ''; ?>">
            <div class="feature-icon">
                <i class="fa-solid fa-notes-medical"></i>
            </div>
            <div class="feature-title">Medical Report</div>
            <div class="feature-description">
                Upload or update your medical report. Required for all swimmers.
            </div>
        </a>
        
        <div class="feature-card <?php echo $already_checked_in ? 'active' : ''; ?>" onclick="featureAction('Swim Lessons')">
            <div class="feature-icon">
                <i class="fa-solid fa-chalkboard-user"></i>
            </div>
            <div class="feature-title">Swim Lessons</div>
            <div class="feature-description">
                Join swimming lessons for beginners or advanced swimmers.
            </div>
        </div>
    </div>
    
    <!-- LANE AVAILABILITY -->
    <div class="lane-section">
        <h2 class="section-title">
            <i class="fa-solid fa-road"></i> Lane Availability
        </h2>
        <div class="lane-grid">
            <?php foreach($lane_availability as $lane): ?>
            <div class="lane-card <?php echo $lane['status']; ?>">
                <div class="lane-number">Lane <?php echo $lane['number']; ?></div>
                <div class="lane-type"><?php echo $lane['type']; ?></div>
                <div style="font-size: 12px; margin-top: 5px; color: <?php echo $lane['status'] === 'available' ? '#10b981' : '#f59e0b'; ?>">
                    <?php echo $lane['status'] === 'available' ? '✓ Available' : '✗ Busy'; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- DAILY SESSIONS -->
    <div class="lane-section">
        <h2 class="section-title">
            <i class="fa-regular fa-clock"></i> Daily Sessions
        </h2>
        <div class="session-list">
            <?php foreach($sessions as $session): 
                $percentage = ($session['booked'] / $session['capacity']) * 100;
            ?>
            <div class="session-item <?php echo $session['type']; ?>">
                <div>
                    <div class="session-time"><?php echo $session['time']; ?></div>
                    <div class="session-name"><?php echo $session['name']; ?></div>
                </div>
                <div class="session-capacity">
                    <?php echo $session['booked']; ?>/<?php echo $session['capacity']; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- AMENITIES -->
    <div class="lane-section">
        <h2 class="section-title">
            <i class="fa-solid fa-star"></i> Amenities
        </h2>
        <div class="amenities-list">
            <?php foreach($amenities as $amenity): ?>
            <span class="amenity-tag">
                <i class="fa-solid fa-check-circle"></i> <?php echo htmlspecialchars($amenity); ?>
            </span>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- POOL RULES -->
    <div class="lane-section">
        <h2 class="section-title">
            <i class="fa-solid fa-clipboard-list"></i> Pool Rules
        </h2>
        <ul class="rules-list">
            <li><i class="fa-solid fa-circle-check"></i> Shower before entering pool</li>
            <li><i class="fa-solid fa-circle-check"></i> Swim cap required</li>
            <li><i class="fa-solid fa-circle-check"></i> No diving in shallow end</li>
            <li><i class="fa-solid fa-circle-check"></i> Children under 12 must be accompanied</li>
            <li><i class="fa-solid fa-circle-check"></i> Medical report required for all swimmers</li>
        </ul>
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
            document.getElementById('checkinMessage').innerHTML = '✅ Check-in successful! +10 points added. You can now book lanes.';
            
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