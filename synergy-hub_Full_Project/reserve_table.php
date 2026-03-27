<?php
require_once 'config.php';
require_once 'functions.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$facility_id = isset($_GET['id']) ? intval($_GET['id']) : 0;


$facility_sql = "SELECT Name FROM Facilities WHERE FacilityID = ?";
$facility_stmt = mysqli_prepare($conn, $facility_sql);
mysqli_stmt_bind_param($facility_stmt, "i", $facility_id);
mysqli_stmt_execute($facility_stmt);
$facility_result = mysqli_stmt_get_result($facility_stmt);
$facility = mysqli_fetch_assoc($facility_result);


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


$message = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $reservation_date = $_POST['date'];
    $reservation_time = $_POST['time'];
    $guest_count = intval($_POST['guests']);
    $special_requests = $_POST['requests'] ?? '';
    
    
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
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .page-title {
            color: white;
            font-size: 32px;
            margin-bottom: 10px;
            text-align: center;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
        
        .facility-name {
            color: #22d3ee;
            font-size: 18px;
            text-align: center;
            margin-bottom: 30px;
        }
        
        .points-badge {
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(10px);
            color: white;
            padding: 15px 25px;
            border-radius: 50px;
            display: inline-block;
            margin-bottom: 30px;
            font-weight: 600;
            font-size: 18px;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .reservation-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-top: 20px;
        }
        
        /* Reservation Form */
        .reservation-form {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .form-title {
            color: white;
            font-size: 22px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-title i {
            color: #22d3ee;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            color: rgba(255,255,255,0.8);
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
            border: 1px solid rgba(255,255,255,0.2);
            background: rgba(255,255,255,0.05);
            color: white;
            font-size: 14px;
            outline: none;
            transition: all 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #22d3ee;
            background: rgba(255,255,255,0.1);
        }
        
        .form-group input::placeholder,
        .form-group textarea::placeholder {
            color: rgba(255,255,255,0.3);
        }
        
        .form-group select {
            cursor: pointer;
            background: rgba(255,255,255,0.1);
        }
        
        .form-group select option {
            background: #1e293b;
            color: white;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        
        .time-slots {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-top: 10px;
        }
        
        .time-slot {
            padding: 12px 8px;
            text-align: center;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 10px;
            color: white;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 13px;
        }
        
        .time-slot:hover {
            background: rgba(255,255,255,0.15);
            border-color: #22d3ee;
        }
        
        .time-slot.selected {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-color: transparent;
        }
        
        
        .submit-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            color: white;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 20px;
            font-size: 16px;
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
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
        
        
        .my-reservations {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .reservation-item {
            background: rgba(255,255,255,0.05);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 15px;
            border-left: 4px solid #22d3ee;
        }
        
        .reservation-item:last-child {
            margin-bottom: 0;
        }
        
        .reservation-item .date {
            color: #22d3ee;
            font-weight: 600;
            font-size: 16px;
            margin-bottom: 8px;
        }
        
        .reservation-item .details {
            color: rgba(255,255,255,0.8);
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
            background: #6b7280;
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
            color: rgba(255,255,255,0.5);
            text-align: center;
            padding: 40px 20px;
        }
        
        .no-reservations i {
            font-size: 48px;
            margin-bottom: 10px;
            color: rgba(255,255,255,0.3);
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
            .reservation-container {
                grid-template-columns: 1fr;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .time-slots {
                grid-template-columns: repeat(2, 1fr);
            }
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
    
    <h1 class="logo">Synergy <span>Hub</span></h1>
    
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
    sidebar.style.left = sidebar.style.left === "0px" ? "-260px" : "0px";
}

document.addEventListener("click", function(e) {
    const sidebar = document.querySelector(".sidebar");
    const btn = document.querySelector(".menu-btn");
    if(sidebar && btn && !sidebar.contains(e.target) && !btn.contains(e.target)) {
        sidebar.style.left = "-260px";
    }
});


document.querySelectorAll('.time-slot').forEach(slot => {
    slot.addEventListener('click', function() {
        document.querySelectorAll('.time-slot').forEach(s => s.classList.remove('selected'));
        this.classList.add('selected');
        document.getElementById('reservationTime').value = this.dataset.time;
    });
});


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