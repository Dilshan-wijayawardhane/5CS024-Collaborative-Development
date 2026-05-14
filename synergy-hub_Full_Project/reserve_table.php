<?php
/**
 * Features:
 *  - Allows users to reserve a table with date, time, number of guests, and special request
 *  - Shows user's upcoming reservations for the same facility
 *  - Basic availability check
 *  - Cancel reservation functionality via AJAX
 *  - Dynamic form with today's date as minimum
 * 
 * Security Notes:
 *  - Prevents overbooking with a simple count check (max 5 per slot)
 */

require_once 'config.php';
require_once 'functions.php';

// Authentication
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
// Get facility ID from URL
$facility_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch facility name
$facility_sql = "SELECT Name FROM Facilities WHERE FacilityID = ?";
$facility_stmt = mysqli_prepare($conn, $facility_sql);
mysqli_stmt_bind_param($facility_stmt, "i", $facility_id);
mysqli_stmt_execute($facility_stmt);
$facility_result = mysqli_stmt_get_result($facility_stmt);
$facility = mysqli_fetch_assoc($facility_result);

// Fetch user's upcoming reservations for this facility
$reservations_sql = "SELECT * FROM table_reservations 
                     WHERE user_id = ? AND facility_id = ? 
                     AND reservation_date >= CURDATE() 
                     AND status != 'cancelled'
                     ORDER BY reservation_date, reservation_time
                     LIMIT 5";
$reservations_stmt = mysqli_prepare($conn, $reservations_sql);
mysqli_stmt_bind_param($reservations_stmt, "ii", $user_id, $facility_id);
mysqli_stmt_execute($reservations_stmt);
$reservations_result = mysqli_stmt_get_result($reservations_stmt);

$user_sql = "SELECT PointsBalance FROM Users WHERE UserID = ?";
$user_stmt = mysqli_prepare($conn, $user_sql);
mysqli_stmt_bind_param($user_stmt, "i", $user_id);
mysqli_stmt_execute($user_stmt);
$user_result = mysqli_stmt_get_result($user_stmt);
$user = mysqli_fetch_assoc($user_result);

// Handle reservation from submission
$message = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $reservation_date = $_POST['date'];
    $reservation_time = $_POST['time'];
    $guest_count = intval($_POST['guests']);
    $special_requests = $_POST['requests'] ?? '';
    
    // Check availability (max 5 tables per time slot)
    $check_sql = "SELECT COUNT(*) as count FROM table_reservations 
                  WHERE facility_id = ? AND reservation_date = ? AND reservation_time = ? 
                  AND status IN ('pending', 'confirmed')";
    $check_stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($check_stmt, "iss", $facility_id, $reservation_date, $reservation_time);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    $check = mysqli_fetch_assoc($check_result);
    
    if ($check['count'] >= 5) { 
        $message = '<div class="error-msg">❌ Sorry, no tables available at this time</div>';
    } else {
       // Insert new reservation
        $insert_sql = "INSERT INTO table_reservations (user_id, facility_id, reservation_date, reservation_time, guest_count, special_requests) 
                       VALUES (?, ?, ?, ?, ?, ?)";
        $insert_stmt = mysqli_prepare($conn, $insert_sql);
        mysqli_stmt_bind_param($insert_stmt, "iissis", $user_id, $facility_id, $reservation_date, $reservation_time, $guest_count, $special_requests);
        
        if (mysqli_stmt_execute($insert_stmt)) {
            $message = '<div class="success-msg">✅ Table reserved successfully!</div>';
        } else {
            $message = '<div class="error-msg">❌ Error making reservation</div>';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reserve Table - Synergy Hub</title>
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
            transition: all 0.3s;
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
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .page-title {
            color: #1e4a76;
            font-size: 32px;
            margin-bottom: 10px;
            text-align: center;
        }
        
        .facility-name {
            color: #2c7da0;
            font-size: 18px;
            text-align: center;
            margin-bottom: 30px;
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
        
        .reservation-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-top: 20px;
        }
        
        /* Reservation Form */
        .reservation-form {
            background: white;
            border-radius: 20px;
            padding: 30px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .form-title {
            color: #1e4a76;
            font-size: 22px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-title i {
            color: #2c7da0;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            color: #475569;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border-radius: 10px;
            border: 2px solid #e2e8f0;
            background: white;
            color: #1e293b;
            font-size: 14px;
            outline: none;
            transition: all 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #2c7da0;
            box-shadow: 0 0 0 3px rgba(44, 125, 160, 0.1);
        }
        
        .form-group input::placeholder,
        .form-group textarea::placeholder {
            color: #94a3b8;
        }
        
        .form-group select {
            cursor: pointer;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .submit-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #1e4a76 0%, #2c7da0 100%);
            border: none;
            border-radius: 12px;
            color: white;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 20px;
            font-size: 16px;
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(30, 74, 118, 0.3);
        }
        
        .success-msg {
            background: #dcfce7;
            border: 1px solid #10b981;
            color: #166534;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .error-msg {
            background: #fee2e2;
            border: 1px solid #ef4444;
            color: #b91c1c;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        /* My Reservations */
        .my-reservations {
            background: white;
            border-radius: 20px;
            padding: 30px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .reservation-item {
            background: #f8fafc;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 15px;
            border-left: 4px solid #2c7da0;
        }
        
        .reservation-item:last-child {
            margin-bottom: 0;
        }
        
        .reservation-item .date {
            color: #2c7da0;
            font-weight: 600;
            font-size: 16px;
            margin-bottom: 8px;
        }
        
        .reservation-item .details {
            color: #64748b;
            font-size: 14px;
            margin-bottom: 10px;
            line-height: 1.5;
        }
        
        .reservation-item .status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-pending {
            background: #f59e0b;
            color: white;
        }
        
        .status-confirmed {
            background: #10b981;
            color: white;
        }
        
        .status-completed {
            background: #64748b;
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
            transition: all 0.3s;
        }
        
        .cancel-btn:hover {
            background: #dc2626;
        }
        
        .no-reservations {
            color: #94a3b8;
            text-align: center;
            padding: 40px 20px;
        }
        
        .no-reservations i {
            font-size: 48px;
            margin-bottom: 10px;
            color: #cbd5e1;
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
            .reservation-container {
                grid-template-columns: 1fr;
            }
            
            .form-row {
                grid-template-columns: 1fr;
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
    <h1 class="logo">Synergy <span>Hub</span> - Reserve Table</h1>
    <div class="icons">
        <div class="points">
            <i class="fa-solid fa-star"></i>
            <span><?php echo $user['PointsBalance']; ?></span>
        </div>
        <a href="facility_details.php?id=<?php echo $facility_id; ?>" class="home-link">
            <i class="fa-solid fa-arrow-left"></i> Back
        </a>
    </div>
</header>

<div class="container">
    
    <div class="points-badge">
        <i class="fa-solid fa-star"></i> Your Points: <?php echo $user['PointsBalance']; ?>
    </div>
    
    <h1 class="page-title">🍽️ Reserve a Table</h1>
    <div class="facility-name">at <?php echo htmlspecialchars($facility['Name']); ?></div>
    
    <?php echo $message; ?>
    
    <div class="reservation-container">
        
        <div class="reservation-form">
            <h3 class="form-title">
                <i class="fa-solid fa-calendar-check"></i> Make a Reservation
            </h3>
            
            <form method="POST" action="" id="reservationForm">
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fa-regular fa-calendar"></i> Date</label>
                        <input type="date" name="date" id="reservationDate" 
                               min="<?php echo date('Y-m-d'); ?>" 
                               value="<?php echo date('Y-m-d'); ?>" 
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fa-regular fa-clock"></i> Time</label>
                        <select name="time" id="reservationTime" required>
                            <option value="">Select a time</option>
                            <option value="12:00:00">12:00 PM</option>
                            <option value="13:00:00">1:00 PM</option>
                            <option value="14:00:00">2:00 PM</option>
                            <option value="17:00:00">5:00 PM</option>
                            <option value="18:00:00">6:00 PM</option>
                            <option value="19:00:00">7:00 PM</option>
                            <option value="20:00:00">8:00 PM</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label><i class="fa-solid fa-users"></i> Number of Guests</label>
                    <select name="guests" required>
                        <option value="1">1 Person</option>
                        <option value="2" selected>2 People</option>
                        <option value="3">3 People</option>
                        <option value="4">4 People</option>
                        <option value="5">5 People</option>
                        <option value="6">6 People</option>
                        <option value="7">7 People</option>
                        <option value="8">8 People</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label><i class="fa-regular fa-message"></i> Special Requests (Optional)</label>
                    <textarea name="requests" rows="3" placeholder="Any special requirements? (e.g., birthday, allergies, preferred seating)"></textarea>
                </div>
                
                <button type="submit" class="submit-btn">
                    <i class="fa-solid fa-check"></i> Reserve Table
                </button>
            </form>
        </div>
        
        <div class="my-reservations">
            <h3 class="form-title">
                <i class="fa-solid fa-list"></i> My Reservations
            </h3>
            
            <?php if(mysqli_num_rows($reservations_result) > 0): ?>
                <?php while($res = mysqli_fetch_assoc($reservations_result)): ?>
                <div class="reservation-item" id="reservation-<?php echo $res['reservation_id']; ?>">
                    <div class="date">
                        <i class="fa-regular fa-calendar"></i> 
                        <?php echo date('M d, Y', strtotime($res['reservation_date'])); ?> • 
                        <?php echo date('g:i A', strtotime($res['reservation_time'])); ?>
                    </div>
                    <div class="details">
                        <i class="fa-solid fa-user-group"></i> <?php echo $res['guest_count']; ?> guests<br>
                        <?php if($res['special_requests']): ?>
                        <small><i class="fa-regular fa-note-sticky"></i> <?php echo htmlspecialchars($res['special_requests']); ?></small>
                        <?php endif; ?>
                    </div>
                    <div>
                        <span class="status status-<?php echo $res['status']; ?>">
                            <?php echo ucfirst($res['status']); ?>
                        </span>
                        <?php if($res['status'] == 'pending' || $res['status'] == 'confirmed'): ?>
                        <button class="cancel-btn" onclick="cancelReservation(<?php echo $res['reservation_id']; ?>)">
                            <i class="fa-solid fa-xmark"></i> Cancel
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-reservations">
                    <i class="fa-regular fa-calendar-xmark"></i>
                    <p>No upcoming reservations</p>
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

// Cancel reservation via AJAX
function cancelReservation(reservationId) {
    if(confirm('Cancel this reservation?')) {
        fetch('cancel_reservation.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'reservation_id=' + reservationId
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                alert('✅ Reservation cancelled');
                location.reload();
            } else {
                alert('❌ Error: ' + data.message);
            }
        });
    }
}

document.addEventListener('DOMContentLoaded', function() {
    let today = new Date().toISOString().split('T')[0];
    document.getElementById('reservationDate').setAttribute('min', today);
});
</script>

</body>
</html>