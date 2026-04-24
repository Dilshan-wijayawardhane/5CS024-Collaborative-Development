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
        $message = '<div class="error-msg">❌ This lane is already booked for this time slot</div>';
    } else {
        // Insert booking
        $insert_sql = "INSERT INTO pool_bookings (user_id, facility_id, lane_number, booking_date, time_slot, medical_report_id) 
                       VALUES (?, ?, ?, ?, ?, ?)";
        $insert_stmt = mysqli_prepare($conn, $insert_sql);
        $medical_id = $medical['report_id'] ?? null;
        mysqli_stmt_bind_param($insert_stmt, "iiissi", $user_id, $facility_id, $lane_number, $booking_date, $time_slot, $medical_id);
        
        if (mysqli_stmt_execute($insert_stmt)) {
            $message = '<div class="success-msg">✅ Lane booked successfully! +20 points will be added after your visit.</div>';
        } else {
            $message = '<div class="error-msg">❌ Error booking lane: ' . mysqli_error($conn) . '</div>';
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
            margin-bottom: 30px;
        }
        
        .medical-status {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 50px;
            padding: 15px 25px;
            margin-bottom: 30px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: white;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .medical-status.valid {
            background: rgba(16, 185, 129, 0.2);
            border-color: #10b981;
        }
        
        .medical-status.invalid {
            background: rgba(239, 68, 68, 0.2);
            border-color: #ef4444;
        }
        
        .booking-container {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 30px;
            margin-top: 20px;
        }
        
        /* Booking Form */
        .booking-form {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .form-section {
            margin-bottom: 30px;
        }
        
        .form-section h3 {
            color: white;
            font-size: 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-section h3 i {
            color: #22d3ee;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            color: rgba(255,255,255,0.8);
            margin-bottom: 8px;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border-radius: 10px;
            border: 1px solid rgba(255,255,255,0.2);
            background: rgba(255,255,255,0.05);
            color: white;
            font-size: 14px;
            outline: none;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            border-color: #22d3ee;
        }
        
        .form-group input[type="date"] {
            color-scheme: dark;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        /* Lane Grid */
        .lane-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 10px;
            margin: 20px 0;
        }
        
        .lane-option {
            background: rgba(255,255,255,0.05);
            border: 2px solid rgba(255,255,255,0.2);
            border-radius: 10px;
            padding: 15px 5px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            color: white;
        }
        
        .lane-option:hover {
            border-color: #22d3ee;
            transform: translateY(-2px);
        }
        
        .lane-option.selected {
            border-color: #22d3ee;
            background: rgba(34, 211, 238, 0.1);
        }
        
        .lane-option .lane-number {
            font-size: 18px;
            font-weight: 700;
            color: #22d3ee;
        }
        
        .lane-option .lane-type {
            font-size: 11px;
            color: rgba(255,255,255,0.6);
        }
        
        .lane-option.disabled {
            opacity: 0.3;
            cursor: not-allowed;
            pointer-events: none;
        }
        
        /* Time Slots */
        .time-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 10px;
            margin: 20px 0;
        }
        
        .time-slot {
            background: rgba(255,255,255,0.05);
            border: 2px solid rgba(255,255,255,0.2);
            border-radius: 10px;
            padding: 12px 5px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            color: white;
        }
        
        .time-slot:hover {
            border-color: #22d3ee;
            transform: translateY(-2px);
        }
        
        .time-slot.selected {
            border-color: #22d3ee;
            background: rgba(34, 211, 238, 0.1);
        }
        
        .time-slot.disabled {
            opacity: 0.3;
            cursor: not-allowed;
            pointer-events: none;
        }
        
        .submit-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #0284c7, #0ea5e9);
            border: none;
            border-radius: 10px;
            color: white;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 20px;
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(2, 132, 199, 0.4);
        }
        
        .submit-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        /* My Bookings */
        .my-bookings {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 25px;
            border: 1px solid rgba(255,255,255,0.2);
            height: fit-content;
        }
        
        .my-bookings h3 {
            color: white;
            font-size: 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .booking-card {
            background: rgba(255,255,255,0.05);
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 15px;
            border-left: 4px solid #22d3ee;
        }
        
        .booking-card .date {
            color: #22d3ee;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .booking-card .details {
            color: rgba(255,255,255,0.8);
            font-size: 14px;
            margin-bottom: 8px;
        }
        
        .booking-card .status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-confirmed {
            background: #10b981;
            color: white;
        }
        
        .status-pending {
            background: #f59e0b;
            color: white;
        }
        
        .status-cancelled {
            background: #ef4444;
            color: white;
        }
        
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
        
        .no-bookings {
            color: rgba(255,255,255,0.5);
            text-align: center;
            padding: 30px 0;
        }
        
        .success-msg {
            background: rgba(16, 185, 129, 0.2);
            border: 1px solid #10b981;
            color: #10b981;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .error-msg {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid #ef4444;
            color: #ef4444;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
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
            .booking-container {
                grid-template-columns: 1fr;
            }
            
            .form-row {
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
    
    <h1 class="logo">Synergy <span>Hub</span> - Book Pool Lane</h1>
    
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

<!-- MAIN CONTENT -->
<div class="container">
    
    <h1 class="page-title">
        <i class="fa-solid fa-person-swimming"></i> Book a Lane
    </h1>
    <div class="facility-name">at <?php echo htmlspecialchars($facility['Name']); ?></div>
    
    <!-- Medical Status -->
    <div class="medical-status <?php echo $has_medical ? 'valid' : 'invalid'; ?>">
        <?php if($has_medical): ?>
            <i class="fa-solid fa-check-circle"></i>
            Medical Report Verified (Valid until <?php echo date('M d, Y', strtotime($medical['expiry_date'] ?? '+1 year')); ?>)
        <?php else: ?>
            <i class="fa-solid fa-exclamation-triangle"></i>
            Medical Report Required - <a href="pool_medical.php?id=<?php echo $facility_id; ?>" style="color: #22d3ee;">Upload Now</a>
        <?php endif; ?>
    </div>
    
    <?php echo $message; ?>
    
    <div class="booking-container">
        <!-- Booking Form -->
        <div class="booking-form">
            <form method="POST" action="" id="bookingForm">
                <div class="form-section">
                    <h3><i class="fa-regular fa-calendar"></i> Select Date</h3>
                    <div class="form-group">
                        <input type="date" name="booking_date" id="bookingDate" 
                               min="<?php echo date('Y-m-d'); ?>" 
                               value="<?php echo date('Y-m-d'); ?>" 
                               required onchange="loadAvailability()">
                    </div>
                </div>
                
                <div class="form-section">
                    <h3><i class="fa-solid fa-road"></i> Select Lane</h3>
                    <div class="lane-grid" id="laneGrid">
                        <?php for($i = 1; $i <= $total_lanes; $i++): 
                            $lane_type = $i % 2 === 0 ? 'Fast Lane' : 'Medium Lane';
                        ?>
                        <div class="lane-option" onclick="selectLane(<?php echo $i; ?>)">
                            <div class="lane-number">Lane <?php echo $i; ?></div>
                            <div class="lane-type"><?php echo $lane_type; ?></div>
                        </div>
                        <?php endfor; ?>
                    </div>
                    <input type="hidden" name="lane_number" id="selectedLane" required>
                </div>
                
                <div class="form-section">
                    <h3><i class="fa-regular fa-clock"></i> Select Time</h3>
                    <div class="time-grid" id="timeGrid">
                        <?php foreach($time_slots as $slot): ?>
                        <div class="time-slot" onclick="selectTime('<?php echo $slot; ?>')">
                            <?php echo $slot; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <input type="hidden" name="time_slot" id="selectedTime" required>
                </div>
                
                <button type="submit" name="book_lane" class="submit-btn" id="submitBtn" <?php echo !$has_medical ? 'disabled' : ''; ?>>
                    <i class="fa-solid fa-check"></i> Confirm Booking
                </button>
            </form>
        </div>
        
        <!-- My Bookings -->
        <div class="my-bookings">
            <h3><i class="fa-solid fa-list"></i> My Upcoming Bookings</h3>
            
            <?php if(mysqli_num_rows($bookings_result) > 0): ?>
                <?php while($booking = mysqli_fetch_assoc($bookings_result)): ?>
                <div class="booking-card">
                    <div class="date">
                        <i class="fa-regular fa-calendar"></i> 
                        <?php echo date('M d, Y', strtotime($booking['booking_date'])); ?>
                    </div>
                    <div class="details">
                        Lane <?php echo $booking['lane_number']; ?> • <?php echo $booking['time_slot']; ?>
                    </div>
                    <div>
                        <span class="status status-<?php echo $booking['status']; ?>">
                            <?php echo ucfirst($booking['status']); ?>
                        </span>
                        <?php if($booking['status'] != 'cancelled' && strtotime($booking['booking_date']) >= strtotime(date('Y-m-d'))): ?>
                        <button class="cancel-btn" onclick="cancelBooking(<?php echo $booking['booking_id']; ?>)">
                            Cancel
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-bookings">
                    <i class="fa-regular fa-calendar-xmark" style="font-size: 40px; margin-bottom: 10px;"></i>
                    <p>No upcoming bookings</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <a href="pool.php?id=<?php echo $facility_id; ?>" class="back-btn">
        <i class="fa-solid fa-arrow-left"></i> Back to Pool
    </a>
</div>

<script>
let selectedLane = null;
let selectedTime = null;

function selectLane(lane) {
    document.querySelectorAll('.lane-option').forEach(el => {
        el.classList.remove('selected');
    });
    event.currentTarget.classList.add('selected');
    selectedLane = lane;
    document.getElementById('selectedLane').value = lane;
    updateSubmitButton();
}

function selectTime(time) {
    document.querySelectorAll('.time-slot').forEach(el => {
        el.classList.remove('selected');
    });
    event.currentTarget.classList.add('selected');
    selectedTime = time;
    document.getElementById('selectedTime').value = time;
    updateSubmitButton();
}

function updateSubmitButton() {
    let btn = document.getElementById('submitBtn');
    if (selectedLane && selectedTime) {
        btn.disabled = false;
    } else {
        btn.disabled = true;
    }
}

function loadAvailability() {
    // In a real app, you would fetch availability from server
    let date = document.getElementById('bookingDate').value;
    console.log('Loading availability for:', date);
}

function cancelBooking(bookingId) {
    if (confirm('Cancel this booking?')) {
        fetch('cancel_booking.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'booking_id=' + bookingId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✅ Booking cancelled');
                location.reload();
            } else {
                alert('❌ Error: ' + data.message);
            }
        });
    }
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