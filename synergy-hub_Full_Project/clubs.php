<?php
require_once 'config.php';
require_once 'functions.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get all clubs
$clubs_sql = "SELECT c.*, u.Name as LeaderName 
              FROM Clubs c 
              LEFT JOIN Users u ON c.LeaderID = u.UserID 
              WHERE c.Status = 'Active'
              ORDER BY c.Name";
$clubs_result = mysqli_query($conn, $clubs_sql);

// Get user's club memberships
$my_clubs_sql = "SELECT c.*, cm.Role 
                 FROM ClubMemberships cm
                 JOIN Clubs c ON cm.ClubID = c.ClubID
                 WHERE cm.UserID = ? AND cm.Status = 'Active'";
$my_clubs_stmt = mysqli_prepare($conn, $my_clubs_sql);
mysqli_stmt_bind_param($my_clubs_stmt, "i", $user_id);
mysqli_stmt_execute($my_clubs_stmt);
$my_clubs_result = mysqli_stmt_get_result($my_clubs_stmt);

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
    <title>Club Hub - Synergy Hub</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .club-container {
            padding: 30px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .clubs-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }
        
        .club-card {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 20px;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .club-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .club-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: white;
        }
        
        .club-name {
            font-size: 20px;
            font-weight: 600;
            color: white;
        }
        
        .club-category {
            background: rgba(255,255,255,0.2);
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 12px;
            color: white;
            display: inline-block;
            margin-top: 5px;
        }
        
        .club-description {
            color: rgba(255,255,255,0.9);
            margin: 15px 0;
            line-height: 1.5;
        }
        
        .club-meta {
            display: flex;
            justify-content: space-between;
            color: rgba(255,255,255,0.6);
            font-size: 14px;
            margin: 10px 0;
        }
        
        .club-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .join-btn {
            flex: 1;
            padding: 10px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            color: white;
            cursor: pointer;
        }
        
        .join-btn:hover {
            opacity: 0.9;
        }
        
        .leave-btn {
            flex: 1;
            padding: 10px;
            background: #ef4444;
            border: none;
            border-radius: 10px;
            color: white;
            cursor: pointer;
        }
        
        .my-clubs-section {
            margin-bottom: 40px;
        }
        
        .role-badge {
            background: #22d3ee;
            color: #0f172a;
            padding: 2px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 10px;
        }
        
        .points-badge {
            background: #22d3ee;
            color: #0f172a;
            padding: 10px 20px;
            border-radius: 30px;
            display: inline-block;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

<div class="bg"></div>

<!-- NAVBAR -->
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
        <a href="index.php" style="color: white; text-decoration: none;">
            <i class="fa-solid fa-home"></i>
        </a>
    </div>
</header>

<!-- SIDEBAR -->
<div id="sidebar" class="sidebar">
    <a href="index.php">Home</a>
    <a href="facilities.php">Facilities</a>
    <a href="transport.php">Transport</a>
    <a href="game.php">Game Field</a>
    <a href="clubs.php">Club Hub</a>
    <a href="qr.html">QR Scanner</a>
</div>

<div class="club-container">
    
    <div class="points-badge">
        <i class="fa-solid fa-star"></i> Your Points: <?php echo $user['PointsBalance']; ?>
    </div>
    
    <!-- MY CLUBS -->
    <?php if(mysqli_num_rows($my_clubs_result) > 0): ?>
    <div class="my-clubs-section">
        <h2 class="section-title">My Clubs</h2>
        <div class="clubs-grid">
            <?php while($club = mysqli_fetch_assoc($my_clubs_result)): ?>
            <div class="club-card">
                <div class="club-header">
                    <div class="club-icon">
                        <i class="fa-solid fa-users"></i>
                    </div>
                    <div>
                        <div class="club-name">
                            <?php echo escape($club['Name']); ?>
                            <span class="role-badge"><?php echo escape($club['Role']); ?></span>
                        </div>
                        <div class="club-category"><?php echo escape($club['Category']); ?></div>
                    </div>
                </div>
                <div class="club-description">
                    <?php echo escape(substr($club['Description'], 0, 100)) . '...'; ?>
                </div>
                <button class="leave-btn" onclick="leaveClub(<?php echo $club['ClubID']; ?>)">
                    Leave Club
                </button>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- ALL CLUBS -->
    <h2 class="section-title">All Clubs</h2>
    <div class="clubs-grid">
        <?php while($club = mysqli_fetch_assoc($clubs_result)): 
            // Check if user is already a member
            $check_sql = "SELECT * FROM ClubMemberships WHERE ClubID = ? AND UserID = ? AND Status = 'Active'";
            $check_stmt = mysqli_prepare($conn, $check_sql);
            mysqli_stmt_bind_param($check_stmt, "ii", $club['ClubID'], $user_id);
            mysqli_stmt_execute($check_stmt);
            $check_result = mysqli_stmt_get_result($check_stmt);
            $is_member = mysqli_num_rows($check_result) > 0;
        ?>
        <div class="club-card">
            <div class="club-header">
                <div class="club-icon">
                    <?php
                    $icon = 'fa-users';
                    if(strpos($club['Name'], 'Coding') !== false) $icon = 'fa-code';
                    else if(strpos($club['Name'], 'Cyber') !== false) $icon = 'fa-shield';
                    else if(strpos($club['Name'], 'IEEE') !== false) $icon = 'fa-microchip';
                    else if(strpos($club['Name'], 'Robotics') !== false) $icon = 'fa-robot';
                    ?>
                    <i class="fa-solid <?php echo $icon; ?>"></i>
                </div>
                <div>
                    <div class="club-name"><?php echo escape($club['Name']); ?></div>
                    <div class="club-category"><?php echo escape($club['Category']); ?></div>
                </div>
            </div>
            <div class="club-description">
                <?php echo escape(substr($club['Description'], 0, 150)) . '...'; ?>
            </div>
            <div class="club-meta">
                <span><i class="fa-regular fa-calendar"></i> Since <?php echo date('Y', strtotime($club['CreatedAt'])); ?></span>
                <span><i class="fa-solid fa-crown"></i> Leader: <?php echo escape($club['LeaderName'] ?: 'TBD'); ?></span>
            </div>
            <?php if($is_member): ?>
                <button class="join-btn" disabled style="opacity: 0.5;">Already Joined</button>
            <?php else: ?>
                <button class="join-btn" onclick="joinClub(<?php echo $club['ClubID']; ?>)">
                    Join Club
                </button>
            <?php endif; ?>
        </div>
        <?php endwhile; ?>
    </div>
</div>

<script>
function toggleSidebar() {
    const sidebar = document.querySelector(".sidebar");
    sidebar.style.left = sidebar.style.left === "0px" ? "-260px" : "0px";
}

function joinClub(clubId) {
    fetch('join_club.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'club_id=' + clubId
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            alert('Successfully joined the club!');
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    });
}

function leaveClub(clubId) {
    if(confirm('Are you sure you want to leave this club?')) {
        fetch('leave_club.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'club_id=' + clubId
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                alert('Left the club');
                location.reload();
            }
        });
    }
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