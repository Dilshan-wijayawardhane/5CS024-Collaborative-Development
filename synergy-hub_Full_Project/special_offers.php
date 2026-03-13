<?php
require_once 'config.php';
require_once 'functions.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$facility_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get special offers
$offers_sql = "SELECT * FROM special_offers 
               WHERE facility_id = ? AND is_active = TRUE 
               AND (valid_until IS NULL OR valid_until >= CURDATE())
               ORDER BY valid_until ASC";
$offers_stmt = mysqli_prepare($conn, $offers_sql);
mysqli_stmt_bind_param($offers_stmt, "i", $facility_id);
mysqli_stmt_execute($offers_stmt);
$offers_result = mysqli_stmt_get_result($offers_stmt);

// Get user points
$user_sql = "SELECT PointsBalance FROM Users WHERE UserID = ?";
$user_stmt = mysqli_prepare($conn, $user_sql);
mysqli_stmt_bind_param($user_stmt, "i", $user_id);
mysqli_stmt_execute($user_stmt);
$user_result = mysqli_stmt_get_result($user_stmt);
$user = mysqli_fetch_assoc($user_result);

// Get facility name
$facility_sql = "SELECT Name FROM Facilities WHERE FacilityID = ?";
$facility_stmt = mysqli_prepare($conn, $facility_sql);
mysqli_stmt_bind_param($facility_stmt, "i", $facility_id);
mysqli_stmt_execute($facility_stmt);
$facility_result = mysqli_stmt_get_result($facility_stmt);
$facility = mysqli_fetch_assoc($facility_result);

// Food Images Array for Offers
$offer_images = [
    'Happy Hour Special' => 'beverages.jpg',
    'Combo Meal Deal' => 'Chicken Rice + Soft Drink.jpg',
    'Buy 1 Get 1 Free' => 'coffeeoffer.jpg',
    'Student Discount' => 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=400&h=250&fit=crop',
    'Breakfast Special' => 'https://images.unsplash.com/photo-1533089860892-a7c6f0a88666?w=400&h=250&fit=crop',
    'Lunch Deal' => 'https://images.unsplash.com/photo-1547496502-affa22d38842?w=400&h=250&fit=crop',
    'Dinner Combo' => 'https://images.unsplash.com/photo-1551218808-94e220e084d2?w=400&h=250&fit=crop',
];

// Default image if specific not found
$default_image = 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=400&h=250&fit=crop';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Special Offers - Synergy Hub</title>
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
            max-width: 1200px;
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
            margin-bottom: 20px;
        }
        
        .points-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 25px;
            border-radius: 50px;
            display: inline-block;
            margin-bottom: 30px;
            font-weight: 600;
            font-size: 18px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        
        .points-badge i {
            margin-right: 8px;
        }
        
        .offers-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }
        
        .offer-card {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.2);
            transition: all 0.3s;
            display: flex;
            flex-direction: column;
        }
        
        .offer-card:hover {
            transform: translateY(-5px);
            background: rgba(255,255,255,0.15);
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        
        .offer-image {
            width: 100%;
            height: 200px;
            overflow: hidden;
            position: relative;
        }
        
        .offer-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }
        
        .offer-card:hover .offer-image img {
            transform: scale(1.1);
        }
        
        .offer-tag {
            position: absolute;
            top: 15px;
            right: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 8px 20px;
            border-radius: 30px;
            font-size: 14px;
            font-weight: 600;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
            z-index: 2;
        }
        
        .offer-content {
            padding: 25px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .offer-title {
            color: white;
            font-size: 22px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .offer-description {
            color: rgba(255,255,255,0.8);
            font-size: 14px;
            margin-bottom: 20px;
            line-height: 1.6;
        }
        
        .offer-details {
            background: rgba(0,0,0,0.2);
            border-radius: 15px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            color: rgba(255,255,255,0.9);
        }
        
        .detail-label {
            color: rgba(255,255,255,0.6);
        }
        
        .detail-value {
            font-weight: 600;
            color: #22d3ee;
        }
        
        .original-price {
            text-decoration: line-through;
            color: #ef4444;
            margin-right: 10px;
        }
        
        .offer-price {
            color: #10b981;
            font-size: 20px;
            font-weight: 700;
        }
        
        .points-required {
            color: #22d3ee;
            font-weight: 600;
            font-size: 18px;
        }
        
        .validity {
            color: rgba(255,255,255,0.5);
            font-size: 12px;
            margin-bottom: 15px;
        }
        
        .claim-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            color: white;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: auto;
            font-size: 16px;
        }
        
        .claim-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        
        .claim-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }
        
        .no-offers {
            grid-column: 1 / -1;
            text-align: center;
            padding: 60px;
            color: rgba(255,255,255,0.7);
        }
        
        .no-offers i {
            font-size: 60px;
            color: rgba(255,255,255,0.3);
            margin-bottom: 20px;
        }
        
        .back-btn {
            display: inline-block;
            margin-top: 40px;
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
            .offers-grid {
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
    
    <h1 class="logo">Synergy <span>Hub</span></h1>
    
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
        <i class="fa-solid fa-star"></i> Your Points: <?php echo $user['PointsBalance']; ?>
    </div>
    
    <h1 class="page-title">🎉 Special Offers</h1>
    <div class="facility-name">at <?php echo htmlspecialchars($facility['Name']); ?></div>
    
    <div class="offers-grid">
        <?php if(mysqli_num_rows($offers_result) > 0): ?>
            <?php while($offer = mysqli_fetch_assoc($offers_result)): ?>
                <?php
                // Calculate days left
                $days_left = '';
                if($offer['valid_until']) {
                    $valid_until = new DateTime($offer['valid_until']);
                    $today = new DateTime();
                    $interval = $today->diff($valid_until);
                    $days_left = $interval->days . ' days left';
                }
                
                // Set offer tag and image
                $offer_tag = '';
                $image_url = isset($offer_images[$offer['title']]) ? $offer_images[$offer['title']] : $default_image;
                
                switch($offer['discount_type']) {
                    case 'percentage':
                        $offer_tag = $offer['discount_value'] . '% OFF';
                        break;
                    case 'points':
                        $offer_tag = $offer['points_required'] . ' Points';
                        break;
                    case 'bogo':
                        $offer_tag = 'BOGO';
                        break;
                    default:
                        $offer_tag = 'OFFER';
                }
                ?>
                <div class="offer-card">
                    <div class="offer-image">
                        <img src="<?php echo $image_url; ?>" alt="<?php echo htmlspecialchars($offer['title']); ?>">
                        <div class="offer-tag"><?php echo $offer_tag; ?></div>
                    </div>
                    
                    <div class="offer-content">
                        <h3 class="offer-title"><?php echo htmlspecialchars($offer['title']); ?></h3>
                        <p class="offer-description"><?php echo htmlspecialchars($offer['description']); ?></p>
                        
                        <div class="offer-details">
                            <?php if($offer['original_price']): ?>
                            <div class="detail-row">
                                <span class="detail-label">Original Price:</span>
                                <span class="detail-value original-price">Rs. <?php echo number_format($offer['original_price'], 2); ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <?php if($offer['offer_price']): ?>
                            <div class="detail-row">
                                <span class="detail-label">Offer Price:</span>
                                <span class="detail-value offer-price">Rs. <?php echo number_format($offer['offer_price'], 2); ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <?php if($offer['points_required'] > 0): ?>
                            <div class="detail-row">
                                <span class="detail-label">Points Required:</span>
                                <span class="detail-value points-required"><?php echo $offer['points_required']; ?> ⭐</span>
                            </div>
                            <?php endif; ?>
                            
                            <?php if($offer['discount_type'] == 'percentage'): ?>
                            <div class="detail-row">
                                <span class="detail-label">Discount:</span>
                                <span class="detail-value"><?php echo $offer['discount_value']; ?>% OFF</span>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if($offer['valid_until']): ?>
                        <div class="validity">
                            <i class="fa-regular fa-clock"></i> Valid until: <?php echo date('M d, Y', strtotime($offer['valid_until'])); ?> 
                            (<?php echo $days_left; ?>)
                        </div>
                        <?php endif; ?>
                        
                        <button class="claim-btn" onclick="claimOffer(<?php echo $offer['offer_id']; ?>, <?php echo $offer['points_required'] ?: 0; ?>)"
                            <?php echo ($offer['points_required'] > $user['PointsBalance']) ? 'disabled' : ''; ?>>
                            <i class="fa-solid fa-gift"></i> Claim Offer
                        </button>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="no-offers">
                <i class="fa-regular fa-face-frown"></i>
                <h3>No special offers available</h3>
                <p>Check back later for exciting deals!</p>
            </div>
        <?php endif; ?>
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

function claimOffer(offerId, pointsRequired) {
    if(pointsRequired > 0) {
        if(confirm(`Claim this offer for ${pointsRequired} points?`)) {
            fetch('claim_offer.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'offer_id=' + offerId + '&points=' + pointsRequired
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    alert('✅ Offer claimed successfully!');
                    location.reload();
                } else {
                    alert('❌ Error: ' + data.message);
                }
            });
        }
    } else {
        alert('✅ Offer claimed! Show this at the counter.');
    }
}
</script>

</body>
</html>