<?php

/**
 * Features:
 *  - Fetches offers filtered by facility_id and validity
 *  - Shows image, discount type, prices, vlaidity
 *  - Claim button calls claim_offer.php
 * 
 * Security Notes:
 *  - Requires login
 *  - Uses prepared statements
 *  - Hardcoded fallback images
 *  - Points chaeck done is frontend + backend
 */

require_once 'config.php';
require_once 'functions.php';

//authentication
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$facility_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch active special offers
$offers_sql = "SELECT * FROM special_offers 
               WHERE facility_id = ? AND is_active = TRUE 
               AND (valid_until IS NULL OR valid_until >= CURDATE())
               ORDER BY valid_until ASC";
$offers_stmt = mysqli_prepare($conn, $offers_sql);
mysqli_stmt_bind_param($offers_stmt, "i", $facility_id);
mysqli_stmt_execute($offers_stmt);
$offers_result = mysqli_stmt_get_result($offers_stmt);

// User info and facility name
$user_sql = "SELECT PointsBalance, Name FROM Users WHERE UserID = ?";
$user_stmt = mysqli_prepare($conn, $user_sql);
mysqli_stmt_bind_param($user_stmt, "i", $user_id);
mysqli_stmt_execute($user_stmt);
$user_result = mysqli_stmt_get_result($user_stmt);
$user = mysqli_fetch_assoc($user_result);

$facilities_count_sql = "SELECT COUNT(*) as count FROM Facilities WHERE Status = 'Open'";
$facilities_count_result = mysqli_query($conn, $facilities_count_sql);
$facilities_count = mysqli_fetch_assoc($facilities_count_result)['count'];

$facility_sql = "SELECT Name FROM Facilities WHERE FacilityID = ?";
$facility_stmt = mysqli_prepare($conn, $facility_sql);
mysqli_stmt_bind_param($facility_stmt, "i", $facility_id);
mysqli_stmt_execute($facility_stmt);
$facility_result = mysqli_stmt_get_result($facility_stmt);
$facility = mysqli_fetch_assoc($facility_result);

// Offer images - online stock images (database changes නැතුව)
$offer_images = [
    'Happy Hour Special' => 'https://images.pexels.com/photos/312418/pexels-photo-312418.jpeg?auto=compress&cs=tinysrgb&w=600',
    'Combo Meal Deal' => 'https://images.pexels.com/photos/1640777/pexels-photo-1640777.jpeg?auto=compress&cs=tinysrgb&w=600',
    'Buy 1 Get 1 Free' => 'https://images.pexels.com/photos/1600727/pexels-photo-1600727.jpeg?auto=compress&cs=tinysrgb&w=600',
    'Student Discount' => 'https://images.pexels.com/photos/3184298/pexels-photo-3184298.jpeg?auto=compress&cs=tinysrgb&w=600',
    'Breakfast Special' => 'https://images.pexels.com/photos/1326946/pexels-photo-1326946.jpeg?auto=compress&cs=tinysrgb&w=600',
    'Lunch Deal' => 'https://images.pexels.com/photos/1279330/pexels-photo-1279330.jpeg?auto=compress&cs=tinysrgb&w=600',
    'Dinner Combo' => 'https://images.pexels.com/photos/406152/pexels-photo-406152.jpeg?auto=compress&cs=tinysrgb&w=600',
    'default' => 'https://images.pexels.com/photos/1640777/pexels-photo-1640777.jpeg?auto=compress&cs=tinysrgb&w=600'
];

$default_image = 'https://images.pexels.com/photos/1640777/pexels-photo-1640777.jpeg?auto=compress&cs=tinysrgb&w=600';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Special Offers - Synergy Hub</title>
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
            text-align: center;
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

        .sidebar-nav-link.active i {
            color: #2c7da0;
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

        .sidebar-divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(0, 0, 0, 0.1), transparent);
            margin: 20px 20px;
        }

        .sidebar-section-title {
            padding: 0 20px;
            margin: 25px 0 10px 0;
            color: #64748b;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .sidebar-club-preview {
            background: #f8fafc;
            border-radius: 16px;
            padding: 15px;
            margin: 0 15px 20px 15px;
            border: 1px solid #e2e8f0;
        }

        .sidebar-club-preview h4 {
            color: #1e4a76;
            font-size: 13px;
            margin: 0 0 12px 0;
        }

        .sidebar-club-preview h4 i {
            color: #fbbf24;
        }

        .sidebar-club-item {
            background: white;
            border-radius: 12px;
            padding: 12px;
            margin-bottom: 10px;
            border: 1px solid #e2e8f0;
            transition: transform 0.2s;
        }

        .sidebar-club-item:hover {
            transform: translateX(5px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .sidebar-club-item h5 {
            color: #1e293b;
            font-size: 14px;
            margin: 0 0 4px 0;
            font-weight: 600;
        }

        .sidebar-club-item p {
            color: #64748b;
            font-size: 11px;
            margin: 0 0 6px 0;
        }

        .sidebar-club-tag {
            background: #e0f2fe;
            color: #1e4a76;
            font-size: 9px;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 30px;
            display: inline-block;
        }

        .sidebar-stats {
            display: flex;
            justify-content: space-around;
            padding: 15px 10px;
            margin: 0 15px;
            background: #f8fafc;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
        }

        .sidebar-stat-value {
            color: #1e4a76;
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 3px;
        }

        .sidebar-stat-label {
            color: #64748b;
            font-size: 10px;
            text-transform: uppercase;
        }

        .sidebar-footer {
            padding: 20px 20px 30px 20px;
        }

        .sidebar-footer-links {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 15px;
        }

        .sidebar-footer-links a {
            color: #64748b;
            text-decoration: none;
            font-size: 11px;
            transition: color 0.2s;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .sidebar-footer-links a:hover {
            color: #1e4a76;
        }

        .sidebar-copyright {
            color: #94a3b8;
            font-size: 10px;
            text-align: center;
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

        .sidebar::-webkit-scrollbar {
            width: 4px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: transparent;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 20px;
        }
        
        /* MAIN CONTAINER */
        .container {
            padding: 30px;
            max-width: 1200px;
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
            margin-bottom: 20px;
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
        
        .offers-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }
        
        /* OFFER CARD */
        .offer-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            transition: all 0.3s;
            display: flex;
            flex-direction: column;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
        
        .offer-card:hover {
            transform: translateY(-5px);
            border-color: #2c7da0;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
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
            background: linear-gradient(135deg, #1e4a76 0%, #2c7da0 100%);
            color: white;
            padding: 8px 20px;
            border-radius: 30px;
            font-size: 14px;
            font-weight: 600;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            z-index: 2;
        }
        
        .offer-content {
            padding: 25px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .offer-title {
            color: #1e293b;
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .offer-description {
            color: #64748b;
            font-size: 14px;
            margin-bottom: 20px;
            line-height: 1.6;
        }
        
        .offer-details {
            background: #f8fafc;
            border-radius: 15px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            color: #1e293b;
        }
        
        .detail-label {
            color: #64748b;
        }
        
        .detail-value {
            font-weight: 600;
            color: #2c7da0;
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
            color: #2c7da0;
            font-weight: 600;
            font-size: 18px;
        }
        
        .validity {
            color: #94a3b8;
            font-size: 12px;
            margin-bottom: 15px;
        }
        
        .claim-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #1e4a76 0%, #2c7da0 100%);
            border: none;
            border-radius: 12px;
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
            box-shadow: 0 5px 20px rgba(30, 74, 118, 0.3);
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
            color: #64748b;
        }
        
        .no-offers i {
            font-size: 60px;
            color: #cbd5e1;
            margin-bottom: 20px;
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
            .offers-grid {
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
        <li class="sidebar-nav-item">
            <a href="index.php" class="sidebar-nav-link">
                <i class="fa-solid fa-home"></i> Home
            </a>
        </li>
        <li class="sidebar-nav-item">
            <a href="facilities.php" class="sidebar-nav-link active">
                <i class="fa-solid fa-building"></i> Facilities
                <span class="sidebar-badge"><?php echo $facilities_count; ?></span>
            </a>
        </li>
        <li class="sidebar-nav-item">
            <a href="transport.php" class="sidebar-nav-link">
                <i class="fa-solid fa-bus"></i> Transport
            </a>
        </li>
        <li class="sidebar-nav-item">
            <a href="game.php" class="sidebar-nav-link">
                <i class="fa-solid fa-futbol"></i> Game Field
            </a>
        </li>
        <li class="sidebar-nav-item">
            <a href="clubs.php" class="sidebar-nav-link">
                <i class="fa-solid fa-users"></i> Club Hub
            </a>
        </li>
        <li class="sidebar-nav-item">
            <a href="qr.html" class="sidebar-nav-link">
                <i class="fa-solid fa-qrcode"></i> QR Scanner
            </a>
        </li>
        <li class="sidebar-nav-item">
            <a href="notifications.php" class="sidebar-nav-link">
                <i class="fa-solid fa-bell"></i> Notifications
                <span class="sidebar-badge">3</span>
            </a>
        </li>
    </ul>
    
    <div class="sidebar-divider"></div>
    
    <div class="sidebar-section-title">MY CLUBS</div>
    
    <div class="sidebar-club-preview">
        <h4><i class="fa-regular fa-star"></i> Active Clubs</h4>
        <div class="sidebar-club-item">
            <h5>Coding Club</h5>
            <p>Programming and software development...</p>
            <span class="sidebar-club-tag">Academic</span>
        </div>
        <div class="sidebar-club-item">
            <h5>IEEE Student Branch</h5>
            <p>IEEE student chapter...</p>
            <span class="sidebar-club-tag">Academic</span>
        </div>
    </div>
    
    <div class="sidebar-stats">
        <div class="sidebar-stat-item">
            <div class="sidebar-stat-value">4</div>
            <div class="sidebar-stat-label">Clubs</div>
        </div>
        <div class="sidebar-stat-item">
            <div class="sidebar-stat-value">12</div>
            <div class="sidebar-stat-label">Events</div>
        </div>
        <div class="sidebar-stat-item">
            <div class="sidebar-stat-value"><?php echo $user['PointsBalance'] ?? 0; ?></div>
            <div class="sidebar-stat-label">Points</div>
        </div>
    </div>
    
    <div class="sidebar-footer">
        <div class="sidebar-footer-links">
            <a href="#"><i class="fa-regular fa-circle-question"></i> Help</a>
            <a href="#"><i class="fa-regular fa-gear"></i> Settings</a>
            <a href="#"><i class="fa-regular fa-message"></i> Feedback</a>
        </div>
        <div class="sidebar-copyright">© 2025 Synergy Hub</div>
    </div>
</div>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<header class="navbar">
    <div class="menu-btn" onclick="toggleSidebar()">
        <i class="fa-solid fa-bars"></i>
    </div>
    
    <h1 class="logo">Synergy <span>Hub</span> - Special Offers</h1>
    
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
        <i class="fa-solid fa-star"></i> Your Points: <?php echo $user['PointsBalance']; ?>
    </div>
    
    <h1 class="page-title">🎉 Special Offers</h1>
    <div class="facility-name">at <?php echo htmlspecialchars($facility['Name'] ?? 'Café'); ?></div>
    
    <div class="offers-grid">
        <?php if(mysqli_num_rows($offers_result) > 0): ?>
            <?php while($offer = mysqli_fetch_assoc($offers_result)): ?>
                <?php
                $days_left = '';
                if($offer['valid_until']) {
                    $valid_until = new DateTime($offer['valid_until']);
                    $today = new DateTime();
                    $interval = $today->diff($valid_until);
                    $days_left = $interval->days . ' days left';
                }
                
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