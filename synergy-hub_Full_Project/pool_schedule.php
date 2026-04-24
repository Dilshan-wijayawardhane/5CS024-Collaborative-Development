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

if (!$facility) {
    header("Location: facilities.php");
    exit();
}

// Parse ExtraInfo
$pool_info = json_decode($facility['ExtraInfo'], true) ?? [];
$total_lanes = $pool_info['lanes'] ?? 8;

// Get selected date (default to today)
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Get bookings for selected date
$bookings_sql = "SELECT * FROM pool_bookings 
                 WHERE facility_id = ? AND booking_date = ?
                 AND status IN ('confirmed', 'pending')
                 ORDER BY time_slot, lane_number";
$bookings_stmt = mysqli_prepare($conn, $bookings_sql);
mysqli_stmt_bind_param($bookings_stmt, "is", $facility_id, $selected_date);
mysqli_stmt_execute($bookings_stmt);
$bookings_result = mysqli_stmt_get_result($bookings_stmt);

// Create availability matrix
$time_slots = [
    '06:00-07:30', '07:30-09:00', '09:00-10:30', '10:30-12:00',
    '12:00-13:30', '13:30-15:00', '15:00-16:30', '16:30-18:00',
    '18:00-19:30', '19:30-21:00'
];

$availability = [];
while ($booking = mysqli_fetch_assoc($bookings_result)) {
    $availability[$booking['time_slot']][$booking['lane_number']] = $booking;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pool Schedule - Synergy Hub</title>
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
        
        .page-title {
            color: white;
            font-size: 32px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .facility-name {
            color: #22d3ee;
            font-size: 18px;
            margin-bottom: 20px;
        }
        
        .date-selector {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .date-input {
            padding: 10px 20px;
            border-radius: 30px;
            border: 1px solid rgba(255,255,255,0.2);
            background: rgba(255,255,255,0.05);
            color: white;
            font-size: 16px;
        }
        
        .view-btn {
            padding: 10px 30px;
            background: linear-gradient(135deg, #0284c7, #0ea5e9);
            border: none;
            border-radius: 30px;
            color: white;
            font-weight: 600;
            cursor: pointer;
        }
        
        .legend {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: rgba(255,255,255,0.8);
        }
        
        .legend-color {
            width: 20px;
            height: 20px;
            border-radius: 5px;
        }
        
        .color-available {
            background: rgba(16, 185, 129, 0.3);
            border: 2px solid #10b981;
        }
        
        .color-booked {
            background: rgba(239, 68, 68, 0.3);
            border: 2px solid #ef4444;
        }
        
        .color-your {
            background: rgba(34, 211, 238, 0.3);
            border: 2px solid #22d3ee;
        }
        
        /* Schedule Table */
        .schedule-container {
            overflow-x: auto;
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 20px;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .schedule-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }
        
        .schedule-table th {
            color: white;
            padding: 15px;
            text-align: center;
            background: rgba(34, 211, 238, 0.2);
            font-weight: 600;
        }
        
        .schedule-table td {
            padding: 12px;
            text-align: center;
            border: 1px solid rgba(255,255,255,0.1);
            color: white;
        }
        
        .time-column {
            background: rgba(0,0,0,0.2);
            font-weight: 600;
            color: #22d3ee;
        }
        
        .available-slot {
            background: rgba(16, 185, 129, 0.2);
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .available-slot:hover {
            background: rgba(16, 185, 129, 0.4);
            transform: scale(1.02);
        }
        
        .booked-slot {
            background: rgba(239, 68, 68, 0.2);
            cursor: not-allowed;
        }
        
        .your-booking {
            background: rgba(34, 211, 238, 0.3);
            border: 2px solid #22d3ee;
        }
        
        .slot-content {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .book-now-btn {
            background: #10b981;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            cursor: pointer;
            margin-top: 5px;
        }
        
        .back-btn {
            display: inline-block;
            margin-top: 30px;
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
        
        @media (max-width: 768px) {
            .date-selector {
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
    
    <h1 class="logo">Synergy <span>Hub</span> - Pool Schedule</h1>
    
    <div class="icons">
        <div class="points">
            <i class="fa-solid fa-star"></i>
            <span><?php
                $points_sql = "SELECT PointsBalance FROM Users WHERE UserID = ?";
                $points_stmt = mysqli_prepare($conn, $points_sql);
                mysqli_stmt_bind_param($points_stmt, "i", $user_id);
                mysqli_stmt_execute($points_stmt);
                $points_result = mysqli_stmt_get_result($points_stmt);
                $user_points = mysqli_fetch_assoc($points_result);
                echo $user_points['PointsBalance'];
            ?></span>
        </div>
        <a href="pool.php?id=<?php echo $facility_id; ?>" class="home-link">
            <i class="fa-solid fa-arrow-left"></i> Back to Pool
        </a>
    </div>
</header>

<!-- MAIN CONTENT -->
<div class="container">
    
    <h1 class="page-title">
        <i class="fa-regular fa-calendar"></i> Pool Schedule
    </h1>
    <div class="facility-name"><?php echo htmlspecialchars($facility['Name']); ?></div>
    
    <!-- Date Selector -->
    <div class="date-selector">
        <form method="GET" action="" style="display: flex; gap: 10px; align-items: center;">
            <input type="hidden" name="id" value="<?php echo $facility_id; ?>">
            <input type="date" name="date" value="<?php echo $selected_date; ?>" class="date-input">
            <button type="submit" class="view-btn">View Schedule</button>
        </form>
    </div>
    
    <!-- Legend -->
    <div class="legend">
        <div class="legend-item">
            <div class="legend-color color-available"></div>
            <span>Available</span>
        </div>
        <div class="legend-item">
            <div class="legend-color color-booked"></div>
            <span>Booked</span>
        </div>
        <div class="legend-item">
            <div class="legend-color color-your"></div>
            <span>Your Booking</span>
        </div>
    </div>
    
    <!-- Schedule Table -->
    <div class="schedule-container">
        <table class="schedule-table">
            <thead>
                <tr>
                    <th>Time</th>
                    <?php for($i = 1; $i <= $total_lanes; $i++): ?>
                    <th>Lane <?php echo $i; ?></th>
                    <?php endfor; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach($time_slots as $slot): ?>
                <tr>
                    <td class="time-column"><?php echo $slot; ?></td>
                    <?php for($lane = 1; $lane <= $total_lanes; $lane++): 
                        $is_booked = isset($availability[$slot][$lane]);
                        $booking = $is_booked ? $availability[$slot][$lane] : null;
                        $is_yours = $is_booked && $booking['user_id'] == $user_id;
                        
                        if ($is_yours):
                    ?>
                    <td class="your-booking">
                        <div class="slot-content">
                            <span><i class="fa-solid fa-check"></i> Your Lane</span>
                        </div>
                    </td>
                    <?php elseif ($is_booked): ?>
                    <td class="booked-slot">
                        <div class="slot-content">
                            <span><i class="fa-solid fa-circle"></i> Booked</span>
                        </div>
                    </td>
                    <?php else: ?>
                    <td class="available-slot" onclick="bookNow(<?php echo $lane; ?>, '<?php echo $slot; ?>')">
                        <div class="slot-content">
                            <span>Available</span>
                            <button class="book-now-btn">Book Now</button>
                        </div>
                    </td>
                    <?php endif; endfor; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <a href="pool.php?id=<?php echo $facility_id; ?>" class="back-btn">
        <i class="fa-solid fa-arrow-left"></i> Back to Pool
    </a>
</div>

<script>
function bookNow(lane, timeSlot) {
    window.location.href = 'pool_booking.php?id=<?php echo $facility_id; ?>&lane=' + lane + '&time=' + timeSlot + '&date=<?php echo $selected_date; ?>';
}

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