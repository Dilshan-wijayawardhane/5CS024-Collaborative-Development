<?php

require_once 'config.php';
require_once 'functions.php';

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

$user_sql = "SELECT PointsBalance FROM Users WHERE UserID = ?";
$user_stmt = mysqli_prepare($conn, $user_sql);
mysqli_stmt_bind_param($user_stmt, "i", $user_id);
mysqli_stmt_execute($user_stmt);
$user_result = mysqli_stmt_get_result($user_stmt);
$user = mysqli_fetch_assoc($user_result);

// Fields with associated images (from uploads/game_fields folder)
$fields = [
    ['name' => 'Football Field', 'image' => 'uploads/game_fields/football.jpg', 'time' => '9:00 AM - 6:00 PM', 'price' => 50],
    ['name' => 'Basketball Court', 'image' => 'uploads/game_fields/basketball.jpg', 'time' => '8:00 AM - 8:00 PM', 'price' => 40],
    ['name' => 'Tennis Court', 'image' => 'uploads/game_fields/tennis.jpg', 'time' => '7:00 AM - 7:00 PM', 'price' => 45],
    ['name' => 'Volleyball Court', 'image' => 'uploads/game_fields/volleyball.jpg', 'time' => '8:00 AM - 6:00 PM', 'price' => 35],
    ['name' => 'Cricket Ground', 'image' => 'uploads/game_fields/cricket.jpg', 'time' => '9:00 AM - 5:00 PM', 'price' => 60],
    ['name' => 'Badminton Court', 'image' => 'uploads/game_fields/badminton.jpg', 'time' => '7:00 AM - 9:00 PM', 'price' => 30],
];

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Game Field - Synergy Hub</title>
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
        
        /* NAVBAR - White/Blue theme */
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
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .home-link:hover {
            color: #2c7da0;
        }
        
        /* SIDEBAR - White/Blue theme */
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
            margin: 0;
        }

        .sidebar-header p i {
            color: #22d3ee;
            margin-right: 5px;
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
            margin: 0;
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
            font-size: 1.1rem;
            color: #94a3b8;
            transition: all 0.3s ease;
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

        .sidebar-badge {
            background: #ef4444;
            color: white;
            font-size: 10px;
            font-weight: 600;
            padding: 2px 6px;
            border-radius: 30px;
            margin-left: auto;
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
        
        .container {
            padding: 30px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .page-title {
            color: #1e4a76;
            font-size: 32px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .points-badge {
            background: linear-gradient(135deg, #1e4a76 0%, #2c7da0 100%);
            color: white;
            padding: 12px 25px;
            border-radius: 50px;
            display: inline-block;
            margin-bottom: 30px;
            font-weight: 600;
            font-size: 16px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .points-badge i {
            color: #fbbf24;
            margin-right: 8px;
        }
        
        .tabs {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        
        .tab-btn {
            padding: 15px 30px;
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 15px;
            color: #475569;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .tab-btn:hover {
            background: #e0f2fe;
            border-color: #2c7da0;
        }
        
        .tab-btn.active {
            background: linear-gradient(135deg, #1e4a76 0%, #2c7da0 100%);
            color: white;
            border-color: transparent;
        }
        
        .fields-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }
        
        /* FIELD CARD WITH IMAGE */
        .field-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.3s;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
        }
        
        .field-card:hover {
            transform: translateY(-5px);
            border-color: #2c7da0;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }
        
        /* Card Image Section */
        .card-image {
            width: 100%;
            height: 180px;
            background-size: cover;
            background-position: center;
            position: relative;
        }
        
        .card-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(to top, rgba(0,0,0,0.7), transparent);
            padding: 15px;
        }
        
        .field-name-card {
            color: white;
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        /* Card Content */
        .card-content {
            padding: 20px;
        }
        
        .field-time {
            color: #64748b;
            font-size: 14px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .field-price {
            color: #2c7da0;
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
        }
        
        .date-selector {
            margin: 15px 0;
        }
        
        .date-selector input {
            width: 100%;
            padding: 12px;
            border-radius: 10px;
            background: white;
            border: 2px solid #e2e8f0;
            color: #1e293b;
            border-radius: 10px;
        }
        
        .date-selector input:focus {
            outline: none;
            border-color: #2c7da0;
        }
        
        .time-selector {
            margin: 15px 0;
        }
        
        .time-selector select {
            width: 100%;
            padding: 12px;
            border-radius: 10px;
            background: white;
            border: 2px solid #e2e8f0;
            color: #1e293b;
            cursor: pointer;
        }
        
        .time-selector select:focus {
            outline: none;
            border-color: #2c7da0;
        }
        
        .book-btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #1e4a76 0%, #2c7da0 100%);
            border: none;
            border-radius: 10px;
            color: white;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .book-btn:hover {
            transform: scale(1.02);
            box-shadow: 0 5px 15px rgba(30, 74, 118, 0.3);
        }
        
        .book-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .bookings-list {
            background: white;
            border-radius: 20px;
            padding: 20px;
            margin-top: 20px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
        
        .booking-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .booking-item:last-child {
            border-bottom: none;
        }
        
        .booking-item strong {
            color: #1e4a76;
        }
        
        .booking-item small {
            color: #64748b;
            font-size: 13px;
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
            background: #64748b;
            color: white;
        }
        
        .cancel-btn {
            padding: 5px 15px;
            background: #ef4444;
            border: none;
            border-radius: 8px;
            color: white;
            cursor: pointer;
            transition: all 0.3s;
            margin-left: 10px;
        }
        
        .cancel-btn:hover {
            background: #dc2626;
        }
        
        .no-bookings {
            color: #64748b;
            text-align: center;
            padding: 40px;
        }
        
        .no-bookings i {
            font-size: 48px;
            color: #cbd5e1;
            margin-bottom: 15px;
        }
        
        .back-btn {
            display: inline-block;
            margin-top: 40px;
            color: #1e4a76;
            text-decoration: none;
            font-size: 16px;
            padding: 12px 25px;
            background: white;
            border-radius: 30px;
            border: 1px solid #e2e8f0;
            transition: all 0.3s;
            font-weight: 500;
        }
        
        .back-btn:hover {
            background: #1e4a76;
            color: white;
            border-color: #1e4a76;
        }
        
        .back-btn i {
            margin-right: 8px;
        }
        
        @media (max-width: 768px) {
            .fields-grid {
                grid-template-columns: 1fr;
            }
            
            .tabs {
                flex-direction: column;
                align-items: stretch;
            }
            
            .booking-item {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }
            
            .container {
                padding: 20px;
            }
        }
    </style>
</head>
<body>

<div id="sidebar" class="sidebar">
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
            <p><i class="fa-solid fa-star"></i> <?php echo $user['PointsBalance'] ?? 0; ?> points</p>
        </div>
    </div>
    
    <ul class="sidebar-nav">
        <li class="sidebar-nav-item"><a href="index.php" class="sidebar-nav-link"><i class="fa-solid fa-home"></i> Home</a></li>
        <li class="sidebar-nav-item"><a href="facilities.php" class="sidebar-nav-link"><i class="fa-solid fa-building"></i> Facilities</a></li>
        <li class="sidebar-nav-item"><a href="transport.php" class="sidebar-nav-link"><i class="fa-solid fa-bus"></i> Transport</a></li>
        <li class="sidebar-nav-item"><a href="game.php" class="sidebar-nav-link active"><i class="fa-solid fa-futbol"></i> Game Field</a></li>
        <li class="sidebar-nav-item"><a href="clubs.php" class="sidebar-nav-link"><i class="fa-solid fa-users"></i> Club Hub</a></li>
        <li class="sidebar-nav-item"><a href="qr.html" class="sidebar-nav-link"><i class="fa-solid fa-qrcode"></i> QR Scanner</a></li>
    </ul>
</div>
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

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
                <!-- TOP LAYER WITH IMAGE -->
                <div class="card-image" style="background-image: url('<?php echo $field['image']; ?>');">
                    <div class="card-overlay">
                        <div class="field-name-card"><?php echo $field['name']; ?></div>
                    </div>
                </div>
                
                <!-- CARD CONTENT -->
                <div class="card-content">
                    <div class="field-time">
                        <i class="fa-regular fa-clock"></i> <?php echo $field['time']; ?>
                    </div>
                    <div class="field-price">
                        <?php echo $field['price']; ?> points per slot
                    </div>
                    
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
                        <strong><?php echo htmlspecialchars($booking['field_name']); ?></strong><br>
                        <small><?php echo date('M d, Y', strtotime($booking['booking_date'])); ?> | <?php echo $booking['time_slot']; ?></small>
                    </div>
                    <div>
                        <span class="booking-status status-<?php echo $booking['status']; ?>">
                            <?php echo ucfirst($booking['status']); ?>
                        </span>
                        <?php if($booking['status'] == 'booked'): ?>
                        <button class="cancel-btn" onclick="cancelBooking(<?php echo $booking['booking_id']; ?>)">
                            <i class="fa-solid fa-xmark"></i> Cancel
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-bookings">
                    <i class="fa-regular fa-calendar-xmark"></i>
                    <p>No bookings yet. Book a field!</p>
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
    const sidebar = document.querySelector(".sidebar");
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
                alert('✅ Field booked successfully!');
                location.reload();
            } else {
                alert('❌ Error: ' + data.message);
            }
        });
    }
}

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
                alert('✅ Booking cancelled');
                location.reload();
            } else {
                alert('❌ Error: ' + data.message);
            }
        });
    }
}
</script>

</body>
</html>