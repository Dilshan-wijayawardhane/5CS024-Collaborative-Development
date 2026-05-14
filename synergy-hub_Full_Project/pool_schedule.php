<?php
// pool_schedule.php - COMPLETE FULL VERSION with white/blue theme
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
$bookings_sql = "SELECT pb.*, u.Name as user_name 
                 FROM pool_bookings pb
                 LEFT JOIN Users u ON pb.user_id = u.UserID
                 WHERE pb.facility_id = ? AND pb.booking_date = ?
                 AND pb.status IN ('confirmed', 'pending')
                 ORDER BY pb.time_slot, pb.lane_number";
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

// Get user points
$user_sql = "SELECT PointsBalance, Name FROM Users WHERE UserID = ?";
$user_stmt = mysqli_prepare($conn, $user_sql);
mysqli_stmt_bind_param($user_stmt, "i", $user_id);
mysqli_stmt_execute($user_stmt);
$user_result = mysqli_stmt_get_result($user_stmt);
$user = mysqli_fetch_assoc($user_result);

// Get total bookings count for selected date
$total_bookings_sql = "SELECT COUNT(*) as total FROM pool_bookings 
                       WHERE facility_id = ? AND booking_date = ? 
                       AND status IN ('confirmed', 'pending')";
$total_stmt = mysqli_prepare($conn, $total_bookings_sql);
mysqli_stmt_bind_param($total_stmt, "is", $facility_id, $selected_date);
mysqli_stmt_execute($total_stmt);
$total_result = mysqli_stmt_get_result($total_stmt);
$total_bookings = mysqli_fetch_assoc($total_result)['total'];

// Calculate available slots
$total_slots = count($time_slots) * $total_lanes;
$available_slots = $total_slots - $total_bookings;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pool Schedule - Synergy Hub</title>
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
            padding: 25px 20px;
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
            margin: 15px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .sidebar-user-avatar {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, #1e4a76 0%, #2c7da0 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .sidebar-user-info h4 {
            color: #1e293b;
            font-size: 15px;
        }

        .sidebar-user-info p {
            color: #64748b;
            font-size: 12px;
        }

        .sidebar-nav {
            list-style: none;
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
            gap: 12px;
            transition: all 0.3s;
        }

        .sidebar-nav-link:hover {
            background: #e0f2fe;
            color: #1e4a76;
        }

        .sidebar-nav-link.active {
            background: #e0f2fe;
            color: #1e4a76;
            border-left: 3px solid #2c7da0;
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
        
        .page-title {
            color: #1e4a76;
            font-size: 32px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .facility-name {
            color: #2c7da0;
            font-size: 18px;
            margin-bottom: 20px;
        }
        
        /* Stats Bar */
        .stats-bar {
            display: flex;
            gap: 20px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }
        
        .stat-box {
            background: white;
            border-radius: 15px;
            padding: 15px 25px;
            display: flex;
            align-items: center;
            gap: 15px;
            border: 1px solid #e2e8f0;
            flex: 1;
            min-width: 150px;
        }
        
        .stat-box i {
            font-size: 32px;
            color: #2c7da0;
        }
        
        .stat-box-info h4 {
            color: #64748b;
            font-size: 12px;
            margin-bottom: 5px;
        }
        
        .stat-box-info .number {
            color: #1e4a76;
            font-size: 24px;
            font-weight: 700;
        }
        
        .date-selector {
            background: white;
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 20px;
            border: 1px solid #e2e8f0;
        }
        
        .date-input-group {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .date-input {
            padding: 10px 20px;
            border-radius: 30px;
            border: 1px solid #e2e8f0;
            background: white;
            font-size: 16px;
            color: #1e293b;
        }
        
        .view-btn {
            padding: 10px 30px;
            background: linear-gradient(135deg, #1e4a76 0%, #2c7da0 100%);
            border: none;
            border-radius: 30px;
            color: white;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .view-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(30, 74, 118, 0.3);
        }
        
        .legend {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            flex-wrap: wrap;
            background: white;
            padding: 15px 20px;
            border-radius: 15px;
            border: 1px solid #e2e8f0;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #475569;
            font-size: 14px;
        }
        
        .legend-color {
            width: 20px;
            height: 20px;
            border-radius: 5px;
        }
        
        .color-available {
            background: #d1fae5;
            border: 2px solid #10b981;
        }
        
        .color-booked {
            background: #fee2e2;
            border: 2px solid #ef4444;
        }
        
        .color-your {
            background: #e0f2fe;
            border: 2px solid #2c7da0;
        }
        
        /* Schedule Table */
        .schedule-container {
            overflow-x: auto;
            background: white;
            border-radius: 20px;
            padding: 20px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
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
            background: linear-gradient(135deg, #1e4a76 0%, #2c7da0 100%);
            font-weight: 600;
        }
        
        .schedule-table td {
            padding: 12px;
            text-align: center;
            border: 1px solid #e2e8f0;
            color: #1e293b;
            transition: all 0.3s;
        }
        
        .time-column {
            background: #f8fafc;
            font-weight: 600;
            color: #1e4a76;
            position: sticky;
            left: 0;
            background-color: #f8fafc;
        }
        
        .available-slot {
            background: #d1fae5;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .available-slot:hover {
            background: #a7f3d0;
            transform: scale(1.02);
        }
        
        .booked-slot {
            background: #fee2e2;
            cursor: not-allowed;
        }
        
        .your-booking {
            background: #e0f2fe;
            border: 2px solid #2c7da0;
            position: relative;
        }
        
        .slot-content {
            display: flex;
            flex-direction: column;
            gap: 5px;
            align-items: center;
        }
        
        .book-now-btn {
            background: #10b981;
            color: white;
            border: none;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 11px;
            cursor: pointer;
            margin-top: 5px;
            transition: all 0.3s;
        }
        
        .book-now-btn:hover {
            background: #059669;
            transform: scale(1.05);
        }
        
        .booking-user {
            font-size: 10px;
            color: #64748b;
            margin-top: 3px;
        }
        
        /* Download Section */
        .download-section {
            margin-top: 30px;
            display: flex;
            justify-content: flex-end;
        }
        
        .download-btn {
            background: #f1f5f9;
            color: #1e4a76;
            border: 1px solid #e2e8f0;
            padding: 10px 25px;
            border-radius: 30px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .download-btn:hover {
            background: #1e4a76;
            color: white;
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
            .date-selector {
                flex-direction: column;
                align-items: stretch;
            }
            
            .date-input-group {
                justify-content: center;
            }
            
            .stats-bar {
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
        <li class="sidebar-nav-item"><a href="index.php" class="sidebar-nav-link"><i class="fa-solid fa-home"></i> Home</a></li>
        <li class="sidebar-nav-item"><a href="facilities.php" class="sidebar-nav-link"><i class="fa-solid fa-building"></i> Facilities</a></li>
        <li class="sidebar-nav-item"><a href="transport.php" class="sidebar-nav-link"><i class="fa-solid fa-bus"></i> Transport</a></li>
        <li class="sidebar-nav-item"><a href="game.php" class="sidebar-nav-link"><i class="fa-solid fa-futbol"></i> Game Field</a></li>
        <li class="sidebar-nav-item"><a href="clubs.php" class="sidebar-nav-link"><i class="fa-solid fa-users"></i> Club Hub</a></li>
        <li class="sidebar-nav-item"><a href="qr.html" class="sidebar-nav-link"><i class="fa-solid fa-qrcode"></i> QR Scanner</a></li>
    </ul>
</div>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<header class="navbar">
    <div class="menu-btn" onclick="toggleSidebar()">
        <i class="fa-solid fa-bars"></i>
    </div>
    
    <h1 class="logo">Synergy <span>Hub</span> - Pool Schedule</h1>
    
    <div class="icons">
        <div class="points">
            <i class="fa-solid fa-star"></i>
            <span><?php echo $user['PointsBalance']; ?></span>
        </div>
        <a href="pool.php?id=<?php echo $facility_id; ?>" class="home-link">
            <i class="fa-solid fa-arrow-left"></i> Back to Pool
        </a>
    </div>
</header>

<div class="container">
    
    <h1 class="page-title">
        <i class="fa-regular fa-calendar"></i> Pool Schedule
    </h1>
    <div class="facility-name"><?php echo htmlspecialchars($facility['Name']); ?> • <?php echo $total_lanes; ?> Lanes</div>
    
    <!-- Stats Bar -->
    <div class="stats-bar">
        <div class="stat-box">
            <i class="fa-solid fa-calendar-day"></i>
            <div class="stat-box-info">
                <h4>Selected Date</h4>
                <div class="number"><?php echo date('M d, Y', strtotime($selected_date)); ?></div>
            </div>
        </div>
        <div class="stat-box">
            <i class="fa-solid fa-chair"></i>
            <div class="stat-box-info">
                <h4>Total Slots</h4>
                <div class="number"><?php echo $total_slots; ?></div>
            </div>
        </div>
        <div class="stat-box">
            <i class="fa-solid fa-check-circle"></i>
            <div class="stat-box-info">
                <h4>Available</h4>
                <div class="number"><?php echo $available_slots; ?></div>
            </div>
        </div>
        <div class="stat-box">
            <i class="fa-solid fa-bookmark"></i>
            <div class="stat-box-info">
                <h4>Booked</h4>
                <div class="number"><?php echo $total_bookings; ?></div>
            </div>
        </div>
    </div>
    
    <!-- Date Selector -->
    <div class="date-selector">
        <form method="GET" action="" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <input type="hidden" name="id" value="<?php echo $facility_id; ?>">
            <div class="date-input-group">
                <i class="fa-regular fa-calendar" style="color: #2c7da0;"></i>
                <input type="date" name="date" value="<?php echo $selected_date; ?>" class="date-input" min="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d', strtotime('+30 days')); ?>">
            </div>
            <button type="submit" class="view-btn"><i class="fa-solid fa-eye"></i> View Schedule</button>
        </form>
        
        <div>
            <a href="?id=<?php echo $facility_id; ?>&date=<?php echo date('Y-m-d'); ?>" class="view-btn" style="background: #f1f5f9; color: #1e4a76; margin-left: 0;">Today</a>
            <a href="?id=<?php echo $facility_id; ?>&date=<?php echo date('Y-m-d', strtotime('tomorrow')); ?>" class="view-btn" style="background: #f1f5f9; color: #1e4a76;">Tomorrow</a>
        </div>
    </div>
    
    <!-- Legend -->
    <div class="legend">
        <div class="legend-item">
            <div class="legend-color color-available"></div>
            <span><i class="fa-regular fa-clock"></i> Available - Click to book</span>
        </div>
        <div class="legend-item">
            <div class="legend-color color-booked"></div>
            <span><i class="fa-solid fa-circle"></i> Booked by someone else</span>
        </div>
        <div class="legend-item">
            <div class="legend-color color-your"></div>
            <span><i class="fa-solid fa-user-check"></i> Your Booking</span>
        </div>
    </div>
    
    <!-- Schedule Table -->
    <div class="schedule-container">
        <table class="schedule-table">
            <thead>
                <tr>
                    <th>Time Slot</th>
                    <?php for($i = 1; $i <= $total_lanes; $i++): ?>
                    <th>Lane <?php echo $i; ?><br><span style="font-size: 10px; opacity: 0.8;"><?php echo $i % 2 === 0 ? 'Fast' : 'Medium'; ?></span></th>
                    <?php endfor; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach($time_slots as $slot): 
                    $slot_time = explode('-', $slot);
                ?>
                <tr>
                    <td class="time-column">
                        <i class="fa-regular fa-clock"></i> <?php echo $slot; ?>
                    </td>
                    <?php for($lane = 1; $lane <= $total_lanes; $lane++): 
                        $is_booked = isset($availability[$slot][$lane]);
                        $booking = $is_booked ? $availability[$slot][$lane] : null;
                        $is_yours = $is_booked && $booking['user_id'] == $user_id;
                        
                        if ($is_yours):
                    ?>
                    <td class="your-booking">
                        <div class="slot-content">
                            <span><i class="fa-solid fa-check-circle"></i> Your Lane</span>
                            <span style="font-size: 11px;">Booking #<?php echo $booking['booking_id']; ?></span>
                        </div>
                    </td>
                    <?php elseif ($is_booked): ?>
                    <td class="booked-slot">
                        <div class="slot-content">
                            <span><i class="fa-solid fa-user"></i> Booked</span>
                            <div class="booking-user">by <?php echo htmlspecialchars(substr($booking['user_name'] ?? 'User', 0, 10)); ?></div>
                        </div>
                    </td>
                    <?php else: ?>
                    <td class="available-slot" onclick="bookNow(<?php echo $lane; ?>, '<?php echo $slot; ?>')">
                        <div class="slot-content">
                            <span><i class="fa-regular fa-clock"></i> Available</span>
                            <button class="book-now-btn"><i class="fa-solid fa-bookmark"></i> Book Now</button>
                        </div>
                    </td>
                    <?php endif; endfor; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Download Options -->
    <div class="download-section">
        <button class="download-btn" onclick="exportSchedule()">
            <i class="fa-solid fa-download"></i> Export Schedule (CSV)
        </button>
    </div>
    
    <a href="pool.php?id=<?php echo $facility_id; ?>" class="back-btn">
        <i class="fa-solid fa-arrow-left"></i> Back to Pool
    </a>
</div>

<script>
function bookNow(lane, timeSlot) {
    window.location.href = 'pool_booking.php?id=<?php echo $facility_id; ?>&lane=' + lane + '&time=' + encodeURIComponent(timeSlot) + '&date=<?php echo $selected_date; ?>';
}

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

function exportSchedule() {
    // Simple CSV export functionality
    let csvContent = "Time Slot";
    <?php for($i = 1; $i <= $total_lanes; $i++): ?>
    csvContent += ",Lane <?php echo $i; ?>";
    <?php endfor; ?>
    csvContent += "\n";
    
    <?php foreach($time_slots as $slot): ?>
    csvContent += "<?php echo $slot; ?>";
    <?php for($lane = 1; $lane <= $total_lanes; $lane++): 
        $is_booked = isset($availability[$slot][$lane]);
        $is_yours = $is_booked && $availability[$slot][$lane]['user_id'] == $user_id;
        if($is_yours):
    ?>
    csvContent += ",Your Booking";
    <?php elseif($is_booked): ?>
    csvContent += ",Booked";
    <?php else: ?>
    csvContent += ",Available";
    <?php endif; endfor; ?>
    csvContent += "\n";
    <?php endforeach; ?>
    
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'pool_schedule_<?php echo $selected_date; ?>.csv';
    a.click();
    URL.revokeObjectURL(url);
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
</script>

</body>
</html>