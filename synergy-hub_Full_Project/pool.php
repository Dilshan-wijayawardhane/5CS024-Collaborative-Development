<?php
// pool.php - Rewritten to match facilities.php white/blue theme
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
$user_sql = "SELECT PointsBalance, Name FROM Users WHERE UserID = ?";
$user_stmt = mysqli_prepare($conn, $user_sql);
mysqli_stmt_bind_param($user_stmt, "i", $user_id);
mysqli_stmt_execute($user_stmt);
$user_result = mysqli_stmt_get_result($user_stmt);
$user = mysqli_fetch_assoc($user_result);

// Check if user has valid medical report
$medical_sql = "SELECT * FROM medical_reports 
                WHERE user_id = ? AND is_valid = TRUE 
                AND (expiry_date IS NULL OR expiry_date >= CURDATE())
                ORDER BY upload_date DESC LIMIT 1";
$medical_stmt = mysqli_prepare($conn, $medical_sql);
mysqli_stmt_bind_param($medical_stmt, "i", $user_id);
mysqli_stmt_execute($medical_stmt);
$medical_result = mysqli_stmt_get_result($medical_stmt);
$has_medical = mysqli_num_rows($medical_result) > 0;

// Generate lane availability
$lane_availability = [];
for ($i = 1; $i <= $lanes; $i++) {
    $lane_availability[] = [
        'number' => $i,
        'status' => rand(0, 10) > 3 ? 'available' : 'busy',
        'type' => $i % 2 === 0 ? 'Fast Lane' : 'Medium Lane'
    ];
}

$available_lanes = 0;
foreach ($lane_availability as $lane) {
    if ($lane['status'] === 'available') $available_lanes++;
}

// Sessions
$sessions = [
    ['time' => '06:00 - 08:00', 'name' => 'Morning Swim', 'type' => 'morning', 'capacity' => 30, 'booked' => rand(10, 25)],
    ['time' => '08:00 - 12:00', 'name' => 'Lap Swimming', 'type' => 'morning', 'capacity' => 40, 'booked' => rand(20, 35)],
    ['time' => '12:00 - 16:00', 'name' => 'Public Swim', 'type' => 'afternoon', 'capacity' => 50, 'booked' => rand(15, 40)],
    ['time' => '16:00 - 20:00', 'name' => 'Evening Swim', 'type' => 'evening', 'capacity' => 45, 'booked' => rand(25, 40)],
    ['time' => '20:00 - 22:00', 'name' => 'Ladies Only', 'type' => 'evening', 'capacity' => 25, 'booked' => rand(5, 15)]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pool['Name']); ?> - Synergy Hub</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
        }
        
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            position: relative;
        }
        
        /* NAVBAR */
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 32px;
            background: white;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.08);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .logo {
            font-size: 24px;
            font-weight: 700;
            color: #1e4a76;
        }
        
        .logo span {
            color: #2c7da0;
        }
        
        .icons {
            display: flex;
            gap: 20px;
            align-items: center;
        }
        
        .menu-btn {
            color: #1e4a76;
            font-size: 24px;
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        
        .menu-btn.active {
            transform: rotate(90deg);
        }
        
        .points {
            display: flex;
            align-items: center;
            gap: 6px;
            font-weight: 600;
            padding: 8px 15px;
            border-radius: 20px;
            background: linear-gradient(135deg, #1e4a76 0%, #2c7da0 100%);
            color: white;
        }
        
        .home-link {
            color: #1e4a76;
            font-size: 20px;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .home-link:hover {
            color: #2c7da0;
        }
        
        /* SIDEBAR */
        .sidebar {
            position: fixed;
            left: -280px;
            top: 0;
            width: 280px;
            height: 100%;
            background: white;
            transition: 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 9999;
            box-shadow: 4px 0 30px rgba(0, 0, 0, 0.1);
            border-right: 1px solid rgba(0, 0, 0, 0.08);
            overflow-y: auto;
        }

        .sidebar.active {
            left: 0;
        }

        .sidebar-header {
            padding: 25px 20px 20px 20px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.08);
            margin-bottom: 15px;
            background: linear-gradient(135deg, #1e4a76 0%, #2c7da0 100%);
        }

        .sidebar-header h2 {
            color: white;
            font-size: 24px;
            font-weight: 700;
            margin: 0 0 5px 0;
        }

        .sidebar-header p {
            color: rgba(255, 255, 255, 0.8);
            font-size: 13px;
        }

        .sidebar-user {
            padding: 15px 20px;
            background: #f8fafc;
            margin: 0 15px 20px 15px;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .sidebar-user-avatar {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            background: linear-gradient(135deg, #1e4a76 0%, #2c7da0 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: white;
        }

        .sidebar-user-info h4 {
            color: #1e293b;
            font-size: 15px;
            margin: 0 0 3px 0;
            font-weight: 600;
        }

        .sidebar-user-info p {
            color: #64748b;
            font-size: 12px;
        }

        .sidebar-user-info p i {
            color: #fbbf24;
        }

        .sidebar-nav {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar-nav-item {
            margin: 4px 12px;
        }

        .sidebar-nav-link {
            display: flex;
            align-items: center;
            padding: 12px 18px;
            color: #475569;
            text-decoration: none;
            border-radius: 12px;
            transition: all 0.3s ease;
            gap: 12px;
            font-weight: 500;
            font-size: 15px;
        }

        .sidebar-nav-link i {
            width: 22px;
            color: #94a3b8;
        }

        .sidebar-nav-link:hover {
            background: #e0f2fe;
            color: #1e4a76;
        }

        .sidebar-nav-link:hover i {
            color: #2c7da0;
        }

        .sidebar-nav-link.active {
            background: #e0f2fe;
            color: #1e4a76;
            border-left: 3px solid #2c7da0;
        }

        .sidebar-nav-link.active i {
            color: #2c7da0;
        }

        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(3px);
            z-index: 9998;
            display: none;
        }

        .sidebar-overlay.active {
            display: block;
        }

        /* Container */
        .container {
            padding: 30px;
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Card Base */
        .card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            margin-bottom: 25px;
        }

        /* Facility Header */
        .facility-header {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 30px;
            border: 1px solid #e2e8f0;
        }

        .facility-icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #1e4a76 0%, #2c7da0 100%);
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
            color: #0f172a;
            margin-bottom: 10px;
        }

        .facility-type {
            color: #2c7da0;
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
            margin-bottom: 30px;
        }

        .stat-card {
            background: #f8fafc;
            padding: 15px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 15px;
            border: 1px solid #e2e8f0;
        }

        .stat-card i {
            font-size: 2rem;
            color: #2c7da0;
        }

        .stat-label {
            font-size: 0.8rem;
            color: #64748b;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #0f172a;
        }

        /* Check-in Section */
        .checkin-section {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            border: 1px solid #e2e8f0;
            text-align: center;
        }

        .points-info {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 30px;
            flex-wrap: wrap;
        }

        .points-badge {
            background: #f1f5f9;
            color: #0f172a;
            padding: 15px 30px;
            border-radius: 50px;
            font-size: 20px;
            font-weight: 600;
            border: 1px solid #e2e8f0;
        }

        .points-badge i {
            color: #fbbf24;
            margin-right: 8px;
        }

        .checkin-btn {
            padding: 15px 40px;
            background: linear-gradient(135deg, #1e4a76 0%, #2c7da0 100%);
            border: none;
            border-radius: 50px;
            color: white;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .checkin-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(30, 74, 118, 0.4);
        }

        .checkin-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        .checkin-message {
            margin-top: 15px;
            color: #2c7da0;
            font-size: 16px;
        }

        /* Features Grid */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
        }

        .feature-card {
            background: #f8fafc;
            border-radius: 20px;
            padding: 30px;
            border: 1px solid #e2e8f0;
            transition: all 0.3s;
            cursor: pointer;
            text-decoration: none;
            color: #0f172a;
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
            background: white;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border-color: #2c7da0;
        }

        .feature-icon {
            font-size: 48px;
            color: #2c7da0;
            margin-bottom: 20px;
        }

        .feature-title {
            font-size: 22px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .feature-description {
            color: #64748b;
            font-size: 14px;
            line-height: 1.5;
        }

        /* Lane Section */
        .lane-section {
            background: white;
            border-radius: 20px;
            padding: 25px;
            margin: 25px 0;
            border: 1px solid #e2e8f0;
        }

        .section-title {
            color: #0f172a;
            font-size: 24px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            color: #2c7da0;
        }

        .lane-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }

        .lane-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 15px;
            text-align: center;
            color: #0f172a;
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
            color: #1e4a76;
            margin-bottom: 5px;
        }

        .lane-type {
            font-size: 12px;
            color: #64748b;
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
            background: #f8fafc;
            border-radius: 12px;
            margin-bottom: 10px;
            border-left: 4px solid;
            color: #0f172a;
        }

        .session-item.morning { border-left-color: #f59e0b; }
        .session-item.afternoon { border-left-color: #2c7da0; }
        .session-item.evening { border-left-color: #8b5cf6; }

        .session-time {
            font-weight: 600;
        }

        .session-name {
            color: #64748b;
            font-size: 14px;
        }

        .session-capacity {
            background: #e2e8f0;
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
            background: #f1f5f9;
            padding: 8px 16px;
            border-radius: 30px;
            font-size: 14px;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .amenity-tag i {
            color: #2c7da0;
        }

        /* Rules */
        .rules-list {
            list-style: none;
            margin: 20px 0;
        }

        .rules-list li {
            color: #475569;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .rules-list li i {
            color: #2c7da0;
            width: 20px;
        }

        .back-btn {
            display: inline-block;
            margin-top: 30px;
            color: #1e4a76;
            text-decoration: none;
            font-size: 16px;
            padding: 10px 20px;
            background: #f1f5f9;
            border-radius: 30px;
            transition: all 0.3s;
        }

        .back-btn:hover {
            background: #1e4a76;
            color: white;
        }

        @media (max-width: 768px) {
            .facility-header {
                flex-direction: column;
                text-align: center;
            }
            .points-info {
                flex-direction: column;
            }
            .navbar {
                flex-direction: column;
                gap: 10px;
            }
            .icons {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>

<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <h2>Synergy Hub</h2>
        <p><i class="fa-solid fa-circle"></i> Connect · Collaborate · Create</p>
    </div>
    
    <div class="sidebar-user">
        <div class="sidebar-user-avatar">
            <i class="fa-solid fa-user"></i>
        </div>
        <div class="sidebar-user-info">
            <h4><?php echo htmlspecialchars($user['Name'] ?? 'User'); ?></h4>
            <p><i class="fa-solid fa-star"></i> <?php echo $user['PointsBalance']; ?> points</p>
        </div>
    </div>

    <ul class="sidebar-nav">
        <li class="sidebar-nav-item"><a href="index.php" class="sidebar-nav-link"><i class="fa-solid fa-home"></i><span>Home</span></a></li>
        <li class="sidebar-nav-item"><a href="facilities.php" class="sidebar-nav-link"><i class="fa-solid fa-building"></i><span>Facilities</span></a></li>
        <li class="sidebar-nav-item"><a href="transport.php" class="sidebar-nav-link"><i class="fa-solid fa-bus"></i><span>Transport</span></a></li>
        <li class="sidebar-nav-item"><a href="game.php" class="sidebar-nav-link"><i class="fa-solid fa-futbol"></i><span>Game Field</span></a></li>
        <li class="sidebar-nav-item"><a href="clubs.php" class="sidebar-nav-link"><i class="fa-solid fa-users"></i><span>Club Hub</span></a></li>
        <li class="sidebar-nav-item"><a href="qr.html" class="sidebar-nav-link"><i class="fa-solid fa-qrcode"></i><span>QR Scanner</span></a></li>
    </ul>
    
    <div class="sidebar-footer" style="padding: 20px 20px 30px 20px;">
        <div class="sidebar-footer-links" style="display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 15px;">
            <a href="#" style="color: #64748b; text-decoration: none; font-size: 11px;"><i class="fa-regular fa-circle-question"></i> Help</a>
            <a href="#" style="color: #64748b; text-decoration: none; font-size: 11px;"><i class="fa-regular fa-gear"></i> Settings</a>
            <a href="#" style="color: #64748b; text-decoration: none; font-size: 11px;"><i class="fa-regular fa-message"></i> Feedback</a>
        </div>
        <div class="sidebar-copyright" style="color: #94a3b8; font-size: 10px; text-align: center;">© 2025 Synergy Hub</div>
    </div>
</div>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

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
        <div class="stat-card"><i class="fa-solid fa-water"></i><div><div class="stat-label">Water Temp</div><div class="stat-value"><?php echo $water_temp; ?>°C</div></div></div>
        <div class="stat-card"><i class="fa-solid fa-arrows-up-down"></i><div><div class="stat-label">Depth</div><div class="stat-value"><?php echo $depth; ?></div></div></div>
        <div class="stat-card"><i class="fa-solid fa-people-group"></i><div><div class="stat-label">Lifeguards</div><div class="stat-value"><?php echo $lifeguards; ?></div></div></div>
        <div class="stat-card"><i class="fa-solid fa-road"></i><div><div class="stat-label">Available Lanes</div><div class="stat-value"><?php echo $available_lanes; ?>/<?php echo $lanes; ?></div></div></div>
    </div>
    
    <!-- CHECK-IN SECTION -->
    <div class="checkin-section">
        <div class="points-info">
            <div class="points-badge"><i class="fa-solid fa-star"></i> Your Points: <span id="currentPoints"><?php echo $user['PointsBalance']; ?></span></div>
            <button class="checkin-btn" id="checkinBtn" onclick="checkIn(<?php echo $facility_id; ?>)" <?php echo ($already_checked_in || $pool['Status'] != 'Open') ? 'disabled' : ''; ?>>
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
    <div class="lane-section">
        <h2 class="section-title"><i class="fa-solid fa-person-swimming"></i> Pool Features</h2>
        <div class="features-grid">
            <a href="pool_booking.php?id=<?php echo $facility_id; ?>" class="feature-card <?php echo ($already_checked_in && $has_medical) ? 'active' : ''; ?>">
                <div class="feature-icon"><i class="fa-solid fa-calendar-check"></i></div>
                <div class="feature-title">Book a Lane</div>
                <div class="feature-description">Reserve a swimming lane for your workout. Choose lane type and time slot.</div>
            </a>
            
            <a href="pool_schedule.php?id=<?php echo $facility_id; ?>" class="feature-card <?php echo $already_checked_in ? 'active' : ''; ?>">
                <div class="feature-icon"><i class="fa-regular fa-clock"></i></div>
                <div class="feature-title">View Schedule</div>
                <div class="feature-description">Check daily sessions, peak hours, and lane availability in real-time.</div>
            </a>
            
            <a href="pool_medical.php?id=<?php echo $facility_id; ?>" class="feature-card active">
                <div class="feature-icon"><i class="fa-solid fa-notes-medical"></i></div>
                <div class="feature-title">Medical Report</div>
                <div class="feature-description">Upload or update your medical report. Required for all swimmers.</div>
            </a>
        </div>
        <?php if(!$has_medical): ?>
        <div style="margin-top: 15px; padding: 12px; background: #fef3c7; border-radius: 10px; color: #92400e;">
            <i class="fa-solid fa-exclamation-triangle"></i> Please upload your medical report before booking a lane.
        </div>
        <?php endif; ?>
    </div>
    
    <!-- LANE AVAILABILITY -->
    <div class="lane-section">
        <h2 class="section-title"><i class="fa-solid fa-road"></i> Lane Availability (Live)</h2>
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
        <h2 class="section-title"><i class="fa-regular fa-clock"></i> Daily Sessions</h2>
        <div class="session-list">
            <?php foreach($sessions as $session): ?>
            <div class="session-item <?php echo $session['type']; ?>">
                <div><div class="session-time"><?php echo $session['time']; ?></div><div class="session-name"><?php echo $session['name']; ?></div></div>
                <div class="session-capacity"><?php echo $session['booked']; ?>/<?php echo $session['capacity']; ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- AMENITIES -->
    <div class="lane-section">
        <h2 class="section-title"><i class="fa-solid fa-star"></i> Amenities</h2>
        <div class="amenities-list">
            <?php foreach($amenities as $amenity): ?>
            <span class="amenity-tag"><i class="fa-solid fa-check-circle"></i> <?php echo htmlspecialchars($amenity); ?></span>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- POOL RULES -->
    <div class="lane-section">
        <h2 class="section-title"><i class="fa-solid fa-clipboard-list"></i> Pool Rules</h2>
        <ul class="rules-list">
            <li><i class="fa-solid fa-circle-check"></i> Shower before entering pool</li>
            <li><i class="fa-solid fa-circle-check"></i> Swim cap required</li>
            <li><i class="fa-solid fa-circle-check"></i> No diving in shallow end</li>
            <li><i class="fa-solid fa-circle-check"></i> Children under 12 must be accompanied</li>
            <li><i class="fa-solid fa-circle-check"></i> Medical report required for all swimmers</li>
        </ul>
    </div>
    
    <a href="facilities.php" class="back-btn"><i class="fa-solid fa-arrow-left"></i> Back to Facilities</a>
</div>

<script>
function toggleSidebar() {
    const sidebar = document.querySelector("#sidebar");
    const overlay = document.getElementById("sidebarOverlay");
    const menuBtn = document.querySelector(".menu-btn");
    
    if(sidebar.classList.contains("active")) {
        sidebar.classList.remove("active");
        overlay.classList.remove("active");
        menuBtn.classList.remove("active");
    } else {
        sidebar.classList.add("active");
        overlay.classList.add("active");
        menuBtn.classList.add("active");
    }
}

document.addEventListener("click", function(e) {
    const sidebar = document.querySelector("#sidebar");
    const btn = document.querySelector(".menu-btn");
    const overlay = document.getElementById("sidebarOverlay");
    
    if(sidebar && btn && overlay && 
       !sidebar.contains(e.target) && 
       !btn.contains(e.target) && 
       sidebar.classList.contains("active")) {
        sidebar.classList.remove("active");
        overlay.classList.remove("active");
        btn.classList.remove("active");
    }
});

function checkIn(facilityId) {
    fetch('checkin.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'facility_id=' + facilityId
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            document.getElementById('pointsDisplay').textContent = data.new_points;
            document.getElementById('currentPoints').textContent = data.new_points;
            document.getElementById('checkinBtn').disabled = true;
            document.getElementById('checkinMessage').innerHTML = '✅ Check-in successful! +10 points added. You can now book lanes.';
            document.querySelectorAll('.feature-card').forEach(card => { card.classList.add('active'); });
        } else {
            alert('Error: ' + data.message);
        }
    });
}
</script>
</body>
</html>