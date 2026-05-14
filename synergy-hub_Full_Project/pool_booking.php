<?php
// pool_booking.php - Rewritten to match facilities.php white/blue theme
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

// Parse ExtraInfo for pool data
$pool_info = json_decode($facility['ExtraInfo'], true) ?? [];
$total_lanes = $pool_info['lanes'] ?? 8;

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
$medical = mysqli_fetch_assoc($medical_result);

// Get user points
$user_sql = "SELECT PointsBalance FROM Users WHERE UserID = ?";
$user_stmt = mysqli_prepare($conn, $user_sql);
mysqli_stmt_bind_param($user_stmt, "i", $user_id);
mysqli_stmt_execute($user_stmt);
$user_result = mysqli_stmt_get_result($user_stmt);
$user = mysqli_fetch_assoc($user_result);

// Handle booking submission
$message = '';
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['book_lane'])) {
    $lane_number = intval($_POST['lane_number']);
    $booking_date = $_POST['booking_date'];
    $time_slot = $_POST['time_slot'];
    
    // Check if lane is available
    $check_sql = "SELECT * FROM pool_bookings 
                  WHERE facility_id = ? AND booking_date = ? 
                  AND lane_number = ? AND time_slot = ? 
                  AND status IN ('pending', 'confirmed')";
    $check_stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($check_stmt, "isis", $facility_id, $booking_date, $lane_number, $time_slot);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    
    if (mysqli_num_rows($check_result) > 0) {
        $message = '<div class="error-msg"><i class="fa-solid fa-circle-exclamation"></i> This lane is already booked for this time slot</div>';
    } else {
        // Insert booking
        $insert_sql = "INSERT INTO pool_bookings (user_id, facility_id, lane_number, booking_date, time_slot, medical_report_id) 
                       VALUES (?, ?, ?, ?, ?, ?)";
        $insert_stmt = mysqli_prepare($conn, $insert_sql);
        $medical_id = $medical['report_id'] ?? null;
        mysqli_stmt_bind_param($insert_stmt, "iiissi", $user_id, $facility_id, $lane_number, $booking_date, $time_slot, $medical_id);
        
        if (mysqli_stmt_execute($insert_stmt)) {
            $message = '<div class="success-msg"><i class="fa-solid fa-check-circle"></i> Lane booked successfully! +20 points will be added after your visit.</div>';
        } else {
            $message = '<div class="error-msg"><i class="fa-solid fa-circle-exclamation"></i> Error booking lane: ' . mysqli_error($conn) . '</div>';
        }
    }
}

// Get user's upcoming bookings
$bookings_sql = "SELECT * FROM pool_bookings 
                 WHERE user_id = ? AND facility_id = ? 
                 AND booking_date >= CURDATE() 
                 AND status != 'cancelled'
                 ORDER BY booking_date, time_slot
                 LIMIT 5";
$bookings_stmt = mysqli_prepare($conn, $bookings_sql);
mysqli_stmt_bind_param($bookings_stmt, "ii", $user_id, $facility_id);
mysqli_stmt_execute($bookings_stmt);
$bookings_result = mysqli_stmt_get_result($bookings_stmt);

// Generate time slots
$time_slots = [
    '06:00-07:30', '07:30-09:00', '09:00-10:30', '10:30-12:00',
    '12:00-13:30', '13:30-15:00', '15:00-16:30', '16:30-18:00',
    '18:00-19:30', '19:30-21:00'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Pool Lane - Synergy Hub</title>
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
            overflow-y: auto;
        }
        
        .sidebar.active { left: 0; }
        
        .sidebar-header {
            padding: 25px 20px;
            background: linear-gradient(135deg, #1e4a76 0%, #2c7da0 100%);
        }
        
        .sidebar-header h2 { color: white; font-size: 24px; }
        .sidebar-header p { color: rgba(255,255,255,0.8); font-size: 13px; }
        
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
            font-size: 20px;
        }
        
        .sidebar-user-info h4 { color: #1e293b; font-size: 15px; }
        .sidebar-user-info p { color: #64748b; font-size: 12px; }
        .sidebar-user-info p i { color: #fbbf24; }
        
        .sidebar-nav { list-style: none; padding: 0; margin: 0; }
        .sidebar-nav-item { margin: 4px 12px; }
        
        .sidebar-nav-link {
            display: flex;
            align-items: center;
            padding: 12px 18px;
            color: #475569;
            text-decoration: none;
            border-radius: 12px;
            gap: 12px;
        }
        
        .sidebar-nav-link:hover { background: #e0f2fe; color: #1e4a76; }
        .sidebar-nav-link.active { background: #e0f2fe; color: #1e4a76; border-left: 3px solid #2c7da0; }
        
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
        
        .sidebar-overlay.active { display: block; }
        
        /* Container */
        .container { padding: 30px; max-width: 1200px; margin: 0 auto; }
        
        /* Page Title */
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
            margin-bottom: 30px;
        }
        
        /* Medical Status */
        .medical-status {
            background: #f8fafc;
            border-radius: 50px;
            padding: 15px 25px;
            margin-bottom: 30px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            border: 1px solid #e2e8f0;
        }
        
        .medical-status.valid { background: #d1fae5; border-color: #10b981; color: #065f46; }
        .medical-status.invalid { background: #fee2e2; border-color: #ef4444; color: #991b1b; }
        
        /* Booking Container */
        .booking-container {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 30px;
            margin-top: 20px;
        }
        
        /* Booking Form */
        .booking-form {
            background: white;
            border-radius: 20px;
            padding: 30px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }
        
        .form-section { margin-bottom: 30px; }
        
        .form-section h3 {
            color: #1e4a76;
            font-size: 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-group { margin-bottom: 20px; }
        
        .form-group label {
            display: block;
            color: #475569;
            margin-bottom: 8px;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            background: white;
            font-size: 14px;
            outline: none;
        }
        
        .form-group input:focus,
        .form-group select:focus { border-color: #2c7da0; }
        
        /* Lane Grid */
        .lane-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 10px;
            margin: 20px 0;
        }
        
        .lane-option {
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 15px 5px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .lane-option:hover { border-color: #2c7da0; transform: translateY(-2px); }
        .lane-option.selected { border-color: #2c7da0; background: #e0f2fe; }
        
        .lane-option .lane-number {
            font-size: 18px;
            font-weight: 700;
            color: #1e4a76;
        }
        
        .lane-option .lane-type { font-size: 11px; color: #64748b; }
        
        /* Time Slots */
        .time-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 10px;
            margin: 20px 0;
        }
        
        .time-slot {
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 12px 5px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .time-slot:hover { border-color: #2c7da0; transform: translateY(-2px); }
        .time-slot.selected { border-color: #2c7da0; background: #e0f2fe; }
        
        .submit-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #1e4a76 0%, #2c7da0 100%);
            border: none;
            border-radius: 10px;
            color: white;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .submit-btn:hover { transform: translateY(-2px); box-shadow: 0 5px 20px rgba(30, 74, 118, 0.4); }
        .submit-btn:disabled { opacity: 0.5; cursor: not-allowed; }
        
        /* My Bookings */
        .my-bookings {
            background: white;
            border-radius: 20px;
            padding: 25px;
            border: 1px solid #e2e8f0;
            height: fit-content;
        }
        
        .my-bookings h3 {
            color: #1e4a76;
            font-size: 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .booking-card {
            background: #f8fafc;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 15px;
            border-left: 4px solid #2c7da0;
        }
        
        .booking-card .date { color: #2c7da0; font-weight: 600; margin-bottom: 5px; }
        .booking-card .details { color: #475569; font-size: 14px; margin-bottom: 8px; }
        
        .status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-confirmed { background: #10b981; color: white; }
        .status-pending { background: #f59e0b; color: white; }
        .status-cancelled { background: #ef4444; color: white; }
        
        .cancel-btn {
            background: #ef4444;
            color: white;
            border: none;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            cursor: pointer;
            margin-left: 10px;
        }
        
        .no-bookings { text-align: center; color: #94a3b8; padding: 30px 0; }
        
        .success-msg {
            background: #d1fae5;
            border: 1px solid #10b981;
            color: #065f46;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .error-msg {
            background: #fee2e2;
            border: 1px solid #ef4444;
            color: #991b1b;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .back-btn {
            display: inline-block;
            margin-top: 30px;
            color: #1e4a76;
            text-decoration: none;
            padding: 10px 20px;
            background: #f1f5f9;
            border-radius: 30px;
        }
        
        .back-btn:hover { background: #1e4a76; color: white; }
        
        @media (max-width: 768px) {
            .booking-container { grid-template-columns: 1fr; }
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
        <div class="sidebar-user-avatar"><i class="fa-solid fa-user"></i></div>
        <div class="sidebar-user-info">
            <h4><?php echo htmlspecialchars($_SESSION['name'] ?? 'User'); ?></h4>
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
    <div class="menu-btn" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></div>
    <h1 class="logo">Synergy <span>Hub</span> - Book Pool Lane</h1>
    <div class="icons">
        <div class="points"><i class="fa-solid fa-star"></i> <span><?php echo $user['PointsBalance']; ?></span></div>
        <a href="pool.php?id=<?php echo $facility_id; ?>" class="home-link"><i class="fa-solid fa-arrow-left"></i> Back to Pool</a>
    </div>
</header>

<div class="container">
    <h1 class="page-title"><i class="fa-solid fa-person-swimming"></i> Book a Lane</h1>
    <div class="facility-name">at <?php echo htmlspecialchars($facility['Name']); ?></div>
    
    <div class="medical-status <?php echo $has_medical ? 'valid' : 'invalid'; ?>">
        <?php if($has_medical): ?>
            <i class="fa-solid fa-check-circle"></i> Medical Report Verified (Valid until <?php echo date('M d, Y', strtotime($medical['expiry_date'] ?? '+1 year')); ?>)
        <?php else: ?>
            <i class="fa-solid fa-exclamation-triangle"></i> Medical Report Required - <a href="pool_medical.php?id=<?php echo $facility_id; ?>" style="color: #2c7da0;">Upload Now</a>
        <?php endif; ?>
    </div>
    
    <?php echo $message; ?>
    
    <div class="booking-container">
        <div class="booking-form">
            <form method="POST" action="" id="bookingForm">
                <div class="form-section">
                    <h3><i class="fa-regular fa-calendar"></i> Select Date</h3>
                    <div class="form-group">
                        <input type="date" name="booking_date" id="bookingDate" min="<?php echo date('Y-m-d'); ?>" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3><i class="fa-solid fa-road"></i> Select Lane</h3>
                    <div class="lane-grid" id="laneGrid">
                        <?php for($i = 1; $i <= $total_lanes; $i++): ?>
                        <div class="lane-option" onclick="selectLane(<?php echo $i; ?>, this)">
                            <div class="lane-number">Lane <?php echo $i; ?></div>
                            <div class="lane-type"><?php echo $i % 2 === 0 ? 'Fast Lane' : 'Medium Lane'; ?></div>
                        </div>
                        <?php endfor; ?>
                    </div>
                    <input type="hidden" name="lane_number" id="selectedLane" required>
                </div>
                
                <div class="form-section">
                    <h3><i class="fa-regular fa-clock"></i> Select Time</h3>
                    <div class="time-grid" id="timeGrid">
                        <?php foreach($time_slots as $slot): ?>
                        <div class="time-slot" onclick="selectTime('<?php echo $slot; ?>', this)"><?php echo $slot; ?></div>
                        <?php endforeach; ?>
                    </div>
                    <input type="hidden" name="time_slot" id="selectedTime" required>
                </div>
                
                <button type="submit" name="book_lane" class="submit-btn" id="submitBtn" <?php echo !$has_medical ? 'disabled' : ''; ?>>
                    <i class="fa-solid fa-check"></i> Confirm Booking
                </button>
            </form>
        </div>
        
        <div class="my-bookings">
            <h3><i class="fa-solid fa-list"></i> My Upcoming Bookings</h3>
            <?php if(mysqli_num_rows($bookings_result) > 0): ?>
                <?php while($booking = mysqli_fetch_assoc($bookings_result)): ?>
                <div class="booking-card">
                    <div class="date"><i class="fa-regular fa-calendar"></i> <?php echo date('M d, Y', strtotime($booking['booking_date'])); ?></div>
                    <div class="details">Lane <?php echo $booking['lane_number']; ?> • <?php echo $booking['time_slot']; ?></div>
                    <div>
                        <span class="status status-<?php echo $booking['status']; ?>"><?php echo ucfirst($booking['status']); ?></span>
                        <?php if($booking['status'] != 'cancelled' && strtotime($booking['booking_date']) >= strtotime(date('Y-m-d'))): ?>
                        <button class="cancel-btn" onclick="cancelBooking(<?php echo $booking['booking_id']; ?>)">Cancel</button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-bookings"><i class="fa-regular fa-calendar-xmark" style="font-size: 40px; margin-bottom: 10px;"></i><p>No upcoming bookings</p></div>
            <?php endif; ?>
        </div>
    </div>
    
    <a href="pool.php?id=<?php echo $facility_id; ?>" class="back-btn"><i class="fa-solid fa-arrow-left"></i> Back to Pool</a>
</div>

<script>
let selectedLaneInput = null;
let selectedTimeInput = null;

function selectLane(lane, element) {
    document.querySelectorAll('.lane-option').forEach(el => el.classList.remove('selected'));
    element.classList.add('selected');
    document.getElementById('selectedLane').value = lane;
    updateSubmitButton();
}

function selectTime(time, element) {
    document.querySelectorAll('.time-slot').forEach(el => el.classList.remove('selected'));
    element.classList.add('selected');
    document.getElementById('selectedTime').value = time;
    updateSubmitButton();
}

function updateSubmitButton() {
    let btn = document.getElementById('submitBtn');
    btn.disabled = !(document.getElementById('selectedLane').value && document.getElementById('selectedTime').value);
}

function cancelBooking(bookingId) {
    if (confirm('Cancel this booking?')) {
        fetch('cancel_pool_booking.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'booking_id=' + bookingId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) { alert('✅ Booking cancelled'); location.reload(); }
            else alert('❌ Error: ' + data.message);
        });
    }
}

function toggleSidebar() {
    document.querySelector("#sidebar").classList.toggle("active");
    document.getElementById("sidebarOverlay").classList.toggle("active");
}
</script>
</body>
</html>