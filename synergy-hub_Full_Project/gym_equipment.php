<?php
require_once 'config.php';
require_once 'functions.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$facility_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get equipment from database
$equip_sql = "SELECT * FROM gym_equipment ORDER BY category, name";
$equip_result = mysqli_query($conn, $equip_sql);

// Get classes from database
$classes_sql = "SELECT * FROM fitness_classes ORDER BY time";
$classes_result = mysqli_query($conn, $classes_sql);

// Get user's class bookings
$bookings_sql = "SELECT cb.*, fc.name as class_name, fc.time, fc.instructor 
                 FROM class_bookings cb
                 JOIN fitness_classes fc ON cb.class_id = fc.class_id
                 WHERE cb.user_id = ? AND cb.status = 'booked'";
$bookings_stmt = mysqli_prepare($conn, $bookings_sql);
mysqli_stmt_bind_param($bookings_stmt, "i", $user_id);
mysqli_stmt_execute($bookings_stmt);
$bookings_result = mysqli_stmt_get_result($bookings_stmt);

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
    <title>Gym Equipment - Synergy Hub</title>
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
        
        /* NAVBAR */
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
        
        .home-link:hover {
            color: #22d3ee;
        }
        
        /* SIDEBAR */
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
        
        /* MAIN CONTENT */
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
        
        .section-title {
            color: white;
            font-size: 24px;
            margin: 40px 0 20px;
            border-left: 5px solid #22d3ee;
            padding-left: 15px;
        }
        
        /* TABS */
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
        
        /* EQUIPMENT GRID */
        .equipment-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }
        
        .equipment-card {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 25px;
            border: 1px solid rgba(255,255,255,0.2);
            transition: all 0.3s;
        }
        
        .equipment-card:hover {
            transform: translateY(-5px);
            background: rgba(255,255,255,0.15);
        }
        
        .equipment-icon {
            font-size: 48px;
            color: #22d3ee;
            margin-bottom: 15px;
            text-align: center;
        }
        
        .equipment-name {
            color: white;
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 10px;
            text-align: center;
        }
        
        .equipment-category {
            text-align: center;
            color: rgba(255,255,255,0.6);
            font-size: 14px;
            margin-bottom: 15px;
        }
        
        .equipment-stats {
            display: flex;
            justify-content: space-between;
            margin: 15px 0;
            color: rgba(255,255,255,0.8);
        }
        
        /* CLASSES LIST */
        .classes-list {
            margin-top: 20px;
        }
        
        .class-item {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .class-info h3 {
            color: white;
            margin-bottom: 5px;
        }
        
        .class-info p {
            color: rgba(255,255,255,0.7);
            font-size: 14px;
            margin: 3px 0;
        }
        
        .class-info p i {
            color: #22d3ee;
            width: 20px;
            margin-right: 5px;
        }
        
        .class-status {
            text-align: right;
        }
        
        .spots-left {
            color: #22d3ee;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .join-btn {
            padding: 10px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            color: white;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .join-btn:hover {
            transform: scale(1.05);
        }
        
        .join-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .join-btn.joined {
            background: #10b981;
        }
        
        /* MY BOOKINGS */
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
        
        .no-bookings {
            color: rgba(255,255,255,0.7);
            text-align: center;
            padding: 20px;
            font-style: italic;
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
        
        @media (max-width: 768px) {
            .class-item {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }
            
            .class-status {
                text-align: center;
            }
            
            .tabs {
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
    
    <h1 class="logo">Synergy <span>Hub</span> - Gym</h1>
    
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

<!-- MAIN CONTENT -->
<div class="container">
    
    <div class="points-badge">
        <i class="fa-solid fa-star"></i> Your Points: <span id="currentPoints"><?php echo $user['PointsBalance']; ?></span>
    </div>
    
    <h1 class="page-title">💪 Gym Equipment & Classes</h1>
    
    <!-- TABS -->
    <div class="tabs">
        <button class="tab-btn active" onclick="switchTab('equipment')">Equipment</button>
        <button class="tab-btn" onclick="switchTab('classes')">Fitness Classes</button>
        <button class="tab-btn" onclick="switchTab('bookings')">My Bookings</button>
    </div>
    
    <!-- EQUIPMENT TAB -->
    <div id="equipmentTab">
        <h2 class="section-title">Available Equipment</h2>
        <div class="equipment-grid">
            <?php if(mysqli_num_rows($equip_result) > 0): ?>
                <?php while($item = mysqli_fetch_assoc($equip_result)): ?>
                <div class="equipment-card">
                    <div class="equipment-icon">
                        <i class="fa-solid <?php echo $item['image_icon']; ?>"></i>
                    </div>
                    <div class="equipment-name"><?php echo escape($item['name']); ?></div>
                    <div class="equipment-category"><?php echo escape($item['category']); ?></div>
                    <div class="equipment-stats">
                        <span>Total: <?php echo $item['quantity']; ?></span>
                        <span>Available: <?php echo $item['available']; ?></span>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="color: white;">No equipment found.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- CLASSES TAB -->
    <div id="classesTab" style="display: none;">
        <h2 class="section-title">Fitness Classes</h2>
        <div class="classes-list">
            <?php if(mysqli_num_rows($classes_result) > 0): ?>
                <?php while($class = mysqli_fetch_assoc($classes_result)): 
                    $spots = $class['capacity'] - $class['booked'];
                    
                    // Check if user already joined this class
                    $check_joined_sql = "SELECT * FROM class_bookings WHERE user_id = ? AND class_id = ? AND status = 'booked'";
                    $check_joined_stmt = mysqli_prepare($conn, $check_joined_sql);
                    mysqli_stmt_bind_param($check_joined_stmt, "ii", $user_id, $class['class_id']);
                    mysqli_stmt_execute($check_joined_stmt);
                    $check_joined_result = mysqli_stmt_get_result($check_joined_stmt);
                    $already_joined = mysqli_num_rows($check_joined_result) > 0;
                ?>
                <div class="class-item">
                    <div class="class-info">
                        <h3><?php echo escape($class['name']); ?></h3>
                        <p><i class="fa-regular fa-clock"></i> <?php echo $class['time']; ?></p>
                        <p><i class="fa-solid fa-user"></i> Instructor: <?php echo escape($class['instructor']); ?></p>
                    </div>
                    <div class="class-status">
                        <div class="spots-left"><?php echo $spots; ?> spots left</div>
                        <?php if($already_joined): ?>
                            <button class="join-btn joined" disabled>Already Joined</button>
                        <?php else: ?>
                            <button class="join-btn" onclick="joinClass(<?php echo $class['class_id']; ?>)"
                                <?php echo ($spots <= 0) ? 'disabled' : ''; ?>>
                                Join Class
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="color: white;">No classes available.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- MY BOOKINGS TAB -->
    <div id="bookingsTab" style="display: none;">
        <h2 class="section-title">My Class Bookings</h2>
        <div class="bookings-list">
            <?php if(mysqli_num_rows($bookings_result) > 0): ?>
                <?php while($booking = mysqli_fetch_assoc($bookings_result)): ?>
                <div class="booking-item">
                    <div>
                        <strong><?php echo escape($booking['class_name']); ?></strong><br>
                        <small><?php echo $booking['time']; ?> with <?php echo escape($booking['instructor']); ?></small>
                    </div>
                    <button class="cancel-btn" onclick="cancelBooking(<?php echo $booking['booking_id']; ?>)">Cancel</button>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-bookings">
                    <i class="fa-solid fa-calendar-xmark"></i> No class bookings yet. Join a class!
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

function switchTab(tab) {
    const tabs = document.querySelectorAll('.tab-btn');
    const equipmentTab = document.getElementById('equipmentTab');
    const classesTab = document.getElementById('classesTab');
    const bookingsTab = document.getElementById('bookingsTab');
    
    tabs.forEach(t => t.classList.remove('active'));
    
    if(tab === 'equipment') {
        tabs[0].classList.add('active');
        equipmentTab.style.display = 'block';
        classesTab.style.display = 'none';
        bookingsTab.style.display = 'none';
    } else if(tab === 'classes') {
        tabs[1].classList.add('active');
        equipmentTab.style.display = 'none';
        classesTab.style.display = 'block';
        bookingsTab.style.display = 'none';
    } else {
        tabs[2].classList.add('active');
        equipmentTab.style.display = 'none';
        classesTab.style.display = 'none';
        bookingsTab.style.display = 'block';
    }
}

function joinClass(classId) {
    fetch('join_class.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'class_id=' + classId
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            alert('✅ Successfully joined the class!');
            location.reload();
        } else {
            alert('❌ Error: ' + data.message);
        }
    });
}

function cancelBooking(bookingId) {
    if(confirm('Cancel this booking?')) {
        fetch('cancel_booking.php', {
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
            }
        });
    }
}
</script>

</body>
</html>