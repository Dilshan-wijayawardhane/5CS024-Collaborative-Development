<?php
/**
 * Game Field Booking Page for Synergy Hub
 * 
 * This page allows users to view available game fields, book them for specific time slots, and manage their existing bookings.
 * 
 * Security Notes:
 *  - User authentication is required to access this page
 *  - Uses prepared statements for all database interactions to prevent SQL injection
 *  - Booking & cancellation handled via seperate API endpoints
 *  - Client-side confirmation before actions
 *  - Hardcoded field data for demo purposes, should be dynamic in production
 */

require_once 'config.php';
require_once 'functions.php';

// Authentication check
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$facility_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$bookings_sql = "SELECT * FROM field_bookings WHERE user_id = ? ORDER BY booking_date DESC";
$bookings_stmt = mysqli_prepare($conn, $bookings_sql);
mysqli_stmt_bind_param($bookings_stmt, "i", $user_id);
mysqli_stmt_execute($bookings_stmt);
$bookings_result = mysqli_stmt_get_result($bookings_stmt);

// Load users' current points balance
$user_sql = "SELECT PointsBalance FROM Users WHERE UserID = ?";
$user_stmt = mysqli_prepare($conn, $user_sql);
mysqli_stmt_bind_param($user_stmt, "i", $user_id);
mysqli_stmt_execute($user_stmt);
$user_result = mysqli_stmt_get_result($user_stmt);
$user = mysqli_fetch_assoc($user_result);

// Hardcoded sports fields
$fields = [
    ['name' => 'Football Field', 'icon' => 'fa-futbol', 'time' => '9:00 AM - 6:00 PM', 'price' => 50],
    ['name' => 'Basketball Court', 'icon' => 'fa-basketball', 'time' => '8:00 AM - 8:00 PM', 'price' => 40],
    ['name' => 'Tennis Court', 'icon' => 'fa-table-tennis-paddle-ball', 'time' => '7:00 AM - 7:00 PM', 'price' => 45],
    ['name' => 'Volleyball Court', 'icon' => 'fa-volleyball', 'time' => '8:00 AM - 6:00 PM', 'price' => 35],
    ['name' => 'Cricket Ground', 'icon' => 'fa-baseball', 'time' => '9:00 AM - 5:00 PM', 'price' => 60],
    ['name' => 'Badminton Court', 'icon' => 'fa-shuttlecock', 'time' => '7:00 AM - 9:00 PM', 'price' => 30],
];

// Standard time slots
$time_slots = [
    '9:00 AM - 11:00 AM',
    '11:00 AM - 1:00 PM',
    '1:00 PM - 3:00 PM',
    '3:00 PM - 5:00 PM',
    '5:00 PM - 7:00 PM',
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <meta charset="UTF-8">
    <title>Game Field - Synergy Hub</title>
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
            margin-bottom: 30px;
            text-align: center;
        }
        
        .points-badge {
            background: #22d3ee;
            color: #0f172a;
            padding: 15px 25px;
            border-radius: 50px;
            display: inline-block;
            margin-bottom: 30px;
            font-weight: 600;
            font-size: 18px;
        }
        
        .tabs {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .tab-btn {
            padding: 15px 30px;
            background: rgba(255,255,255,0.1);
            border: 2px solid rgba(255,255,255,0.2);
            border-radius: 15px;
            color: white;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .tab-btn:hover {
            background: rgba(255,255,255,0.2);
        }
        
        .tab-btn.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-color: transparent;
        }
        
        .fields-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }
        
        .field-card {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 25px;
            border: 1px solid rgba(255,255,255,0.2);
            transition: all 0.3s;
        }
        
        .field-card:hover {
            transform: translateY(-5px);
            background: rgba(255,255,255,0.15);
        }
        
        .field-icon {
            font-size: 48px;
            color: #22d3ee;
            margin-bottom: 15px;
            text-align: center;
        }
        
        .field-name {
            color: white;
            font-size: 22px;
            font-weight: 600;
            margin-bottom: 10px;
            text-align: center;
        }
        
        .field-time {
            color: rgba(255,255,255,0.7);
            text-align: center;
            margin-bottom: 10px;
        }
        
        .field-price {
            text-align: center;
            color: #22d3ee;
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
        }
        
        .book-btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            color: white;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .book-btn:hover {
            transform: scale(1.02);
        }
        
        .book-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .time-selector {
            margin: 15px 0;
        }
        
        .time-selector select {
            width: 100%;
            padding: 10px;
            border-radius: 10px;
            background: rgba(255,255,255,0.1);
            color: white;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .date-selector {
            margin: 15px 0;
        }
        
        .date-selector input {
            width: 100%;
            padding: 10px;
            border-radius: 10px;
            background: rgba(255,255,255,0.1);
            color: white;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .bookings-list {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 20px;
            margin-top: 20px;
        }
        
        .booking-item {
            display: flex;
            justify-content: space-between;
            padding: 15px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            color: white;
        }
        
        .booking-item:last-child {
            border-bottom: none;
        }
        
        .booking-status {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-booked {
            background: #10b981;
            color: white;
        }
        
        .status-cancelled {
            background: #ef4444;
            color: white;
        }
        
        .status-completed {
            background: #6b7280;
            color: white;
        }
        
        .cancel-btn {
            padding: 5px 15px;
            background: #ef4444;
            border: none;
            border-radius: 8px;
            color: white;
            cursor: pointer;
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
        }
        
        .back-btn:hover {
            background: rgba(255,255,255,0.2);
            color: #22d3ee;
        }
    </style>
</head>
<body>

<div class="bg"></div>

<div id="sidebar" class="sidebar">
    <a href="index.php">Home</a>
    <a href="facilities.php">Facilities</a>
    <a href="transport.php">Transport</a>
    <a href="game.php">Game Field</a>
    <a href="clubs.php">Club Hub</a>
    <a href="qr.html">QR Scanner</a>
</div>

<header class="navbar">
    <div class="menu-btn" onclick="toggleSidebar()">
        <i class="fa-solid fa-bars"></i>
    </div>
    
    <h1 class="logo">Synergy <span>Hub</span> - Game Field</h1>
    
    <div class="icons">
        <div class="points">
            <i class="fa-solid fa-star"></i>
            <span id="pointsDisplay"><?php echo $user['PointsBalance']; ?></span>
        </div>
        <a href="facility_details.php?id=<?php echo $facility_id; ?>" class="home-link">
            <i class="fa-solid fa-arrow-left"></i> Back
        </a>
    </div>
</header>

<div class="container">
    
    <div class="points-badge">
        <i class="fa-solid fa-star"></i> Your Points: <span id="currentPoints"><?php echo $user['PointsBalance']; ?></span>
    </div>
    
    <h1 class="page-title">⚽ Game Field Bookings</h1>
    
    <div class="tabs">
        <button class="tab-btn active" onclick="switchTab('fields')">Available Fields</button>
        <button class="tab-btn" onclick="switchTab('bookings')">My Bookings</button>
    </div>
    
    <div id="fieldsTab">
        <div class="fields-grid">
            <?php foreach($fields as $field): ?>
            <div class="field-card">
                <div class="field-icon">
                    <i class="fa-solid <?php echo $field['icon']; ?>"></i>
                </div>
                <div class="field-name"><?php echo $field['name']; ?></div>
                <div class="field-time"><i class="fa-regular fa-clock"></i> <?php echo $field['time']; ?></div>
                <div class="field-price"><?php echo $field['price']; ?> points per slot</div>
                
                <div class="date-selector">
                    <input type="date" id="date-<?php echo str_replace(' ', '', $field['name']); ?>" 
                           min="<?php echo date('Y-m-d'); ?>" 
                           value="<?php echo date('Y-m-d'); ?>">
                </div>
                
                <div class="time-selector">
                    <select id="time-<?php echo str_replace(' ', '', $field['name']); ?>">
                        <?php foreach($time_slots as $slot): ?>
                        <option value="<?php echo $slot; ?>"><?php echo $slot; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <button class="book-btn" onclick="bookField('<?php echo $field['name']; ?>', <?php echo $field['price']; ?>)"
                    <?php echo ($user['PointsBalance'] < $field['price']) ? 'disabled' : ''; ?>>
                    <i class="fa-solid fa-calendar-check"></i> Book Now
                </button>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <div id="bookingsTab" style="display: none;">
        <div class="bookings-list">
            <?php if(mysqli_num_rows($bookings_result) > 0): ?>
                <?php while($booking = mysqli_fetch_assoc($bookings_result)): ?>
                <div class="booking-item">
                    <div>
                        <strong><?php echo escape($booking['field_name']); ?></strong><br>
                        <small><?php echo date('M d, Y', strtotime($booking['booking_date'])); ?> | <?php echo $booking['time_slot']; ?></small>
                    </div>
                    <div>
                        <span class="booking-status status-<?php echo $booking['status']; ?>">
                            <?php echo $booking['status']; ?>
                        </span>
                        <?php if($booking['status'] == 'booked'): ?>
                        <button class="cancel-btn" onclick="cancelBooking(<?php echo $booking['booking_id']; ?>)">Cancel</button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-orders" style="color: rgba(255,255,255,0.7); text-align: center; padding: 20px;">
                    <i class="fa-solid fa-calendar-xmark"></i> No bookings yet. Book a field!
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <a href="facility_details.php?id=<?php echo $facility_id; ?>" class="back-btn">
        <i class="fa-solid fa-arrow-left"></i> Back to Facility
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

// Tab switching logic
function switchTab(tab) {
    const tabs = document.querySelectorAll('.tab-btn');
    const fieldsTab = document.getElementById('fieldsTab');
    const bookingsTab = document.getElementById('bookingsTab');
    
    tabs.forEach(t => t.classList.remove('active'));
    
    if(tab === 'fields') {
        tabs[0].classList.add('active');
        fieldsTab.style.display = 'block';
        bookingsTab.style.display = 'none';
    } else {
        tabs[1].classList.add('active');
        fieldsTab.style.display = 'none';
        bookingsTab.style.display = 'block';
    }
}

// Book field (AJAX to book_field.php)
function bookField(fieldName, price) {
    let fieldId = fieldName.replace(/\s/g, '');
    let selectedDate = document.getElementById('date-' + fieldId).value;
    let selectedTime = document.getElementById('time-' + fieldId).value;
    
    if(confirm(`Book ${fieldName} on ${selectedDate} at ${selectedTime} for ${price} points?`)) {
        fetch('book_field.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'field_name=' + encodeURIComponent(fieldName) + 
                  '&booking_date=' + selectedDate + 
                  '&time_slot=' + encodeURIComponent(selectedTime) + 
                  '&price=' + price
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                alert('Field booked successfully!');
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        });
    }
}

// Cancel booking (AJAX to cancel_field_booking.php)
function cancelBooking(bookingId) {
    if(confirm('Cancel this booking?')) {
        fetch('cancel_field_booking.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'booking_id=' + bookingId
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                alert('Booking cancelled');
                location.reload();
            }
        });
    }
}
</script>

</body>
</html>