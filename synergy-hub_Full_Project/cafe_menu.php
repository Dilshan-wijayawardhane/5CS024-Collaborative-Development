<?php

require_once 'config.php';
require_once 'functions.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$facility_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$menu_sql = "SELECT * FROM cafe_menu WHERE available = TRUE ORDER BY category, name";
$menu_result = mysqli_query($conn, $menu_sql);

$user_sql = "SELECT PointsBalance, Name FROM Users WHERE UserID = ?";
$user_stmt = mysqli_prepare($conn, $user_sql);
mysqli_stmt_bind_param($user_stmt, "i", $user_id);
mysqli_stmt_execute($user_stmt);
$user_result = mysqli_stmt_get_result($user_stmt);
$user = mysqli_fetch_assoc($user_result);

$facilities_count_sql = "SELECT COUNT(*) as count FROM Facilities WHERE Status = 'Open'";
$facilities_count_result = mysqli_query($conn, $facilities_count_sql);
$facilities_count = mysqli_fetch_assoc($facilities_count_result)['count'];

$menu_by_category = [];
while($item = mysqli_fetch_assoc($menu_result)) {
    $menu_by_category[$item['category']][] = $item;
}

// Food images - ඔබේ local images (වෙනස් කරන්නේ නැහැ)
$food_images = [
    'Chicken Rice' => 'chickenrice.jpg',
    'Chicken Sandwich' => 'Chicken Sandwich.jfif',
    'Pasta' => 'Pasta.jpg',
    'Veggie Wrap' => 'Veggie Wrap.jfif',
    'Coffee' => 'coffee.jpg',
    'Tea' => 'Tea.jpg',
    'Soft Drink' => 'Soft Drink.jpg',
    'Fresh Juice' => 'juice.jpg',
    'Chocolate Cake' => 'Chocolate Cake.jpg',
    'Ice Cream' => 'Ice Cream.jpg',
    'Fruit Salad' => 'Fruit Salad.jpg',
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Café Menu - Synergy Hub</title>
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
        
        .points.active {
            transform: scale(1.1);
        }
        
        .order-now-btn {
            background: linear-gradient(135deg, #1e4a76 0%, #2c7da0 100%);
            color: white;
            padding: 8px 20px;
            border-radius: 30px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }
        
        .order-now-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(30, 74, 118, 0.3);
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
        
        .menu-category {
            color: #1e4a76;
            font-size: 28px;
            margin: 40px 0 20px;
            border-left: 5px solid #2c7da0;
            padding-left: 15px;
        }
        
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
        }
        
        /* MENU ITEM CARD - White background */
        .menu-item {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.3s;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
        }
        
        .menu-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            border-color: #2c7da0;
        }
        
        /* Food Image Section - ඔබේ local images */
        .food-image {
            width: 100%;
            height: 180px;
            position: relative;
            overflow: hidden;
        }
        
        .food-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .stock-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(0,0,0,0.8);
            color: white;
            padding: 5px 12px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .stock-badge.low {
            background: #f59e0b;
        }
        
        .stock-badge.out {
            background: #ef4444;
        }
        
        /* Card Content - White/Blue theme */
        .item-content {
            padding: 20px;
        }
        
        .item-name {
            color: #1e293b;
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 15px;
        }
        
        .availability-section {
            margin: 15px 0;
        }
        
        .availability-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: 13px;
            color: #64748b;
        }
        
        .availability-bar {
            width: 100%;
            height: 8px;
            background: #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .availability-fill {
            height: 100%;
            background: linear-gradient(90deg, #2c7da0, #1e4a76);
            border-radius: 10px;
            transition: width 0.3s;
        }
        
        .availability-fill.low {
            background: #f59e0b;
        }
        
        .item-prices {
            display: flex;
            justify-content: space-between;
            margin: 15px 0;
            padding: 10px;
            background: #f8fafc;
            border-radius: 10px;
        }
        
        .price-tag {
            text-align: center;
            flex: 1;
        }
        
        .price-tag small {
            color: #64748b;
            font-size: 12px;
        }
        
        .price-tag div {
            color: #2c7da0;
            font-size: 18px;
            font-weight: 600;
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
        
        /* FLOATING ORDER BUTTON */
        .floating-order-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: linear-gradient(135deg, #1e4a76 0%, #2c7da0 100%);
            color: white;
            padding: 15px 30px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
            z-index: 1000;
            transition: all 0.3s;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .floating-order-btn:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(30, 74, 118, 0.4);
        }
        
        .floating-order-btn i {
            font-size: 20px;
        }
        
        @media (max-width: 768px) {
            .floating-order-btn {
                bottom: 20px;
                right: 20px;
                padding: 12px 20px;
                font-size: 14px;
            }
            
            .container {
                padding: 20px;
            }
            
            .menu-grid {
                grid-template-columns: 1fr;
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
    
    <h1 class="logo">Synergy <span>Hub</span> - Café Menu</h1>
    
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
    
    <h1 class="page-title">☕ Campus Café Menu</h1>
    
    <?php foreach($menu_by_category as $category => $items): ?>
    <h2 class="menu-category"><?php echo htmlspecialchars($category); ?></h2>
    <div class="menu-grid">
        <?php foreach($items as $item):
            $image_url = isset($food_images[$item['name']]) ? $food_images[$item['name']] : 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=300&h=200&fit=crop';
            $stock = $item['stock'] ?? 10;
            $stock_percent = ($stock / 20) * 100;
        ?>
        <div class="menu-item">
            <div class="food-image">
                <img src="<?php echo $image_url; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                <?php if($stock <= 3): ?>
                <div class="stock-badge low">Only <?php echo $stock; ?> left!</div>
                <?php elseif($stock <= 5): ?>
                <div class="stock-badge"><?php echo $stock; ?> left</div>
                <?php endif; ?>
            </div>
            
            <div class="item-content">
                <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                
                <div class="availability-section">
                    <div class="availability-header">
                        <span>Availability</span>
                        <span><?php echo $stock; ?>/20 left</span>
                    </div>
                    <div class="availability-bar">
                        <div class="availability-fill <?php echo $stock <= 3 ? 'low' : ''; ?>" 
                             style="width: <?php echo max(5, $stock_percent); ?>%;"></div>
                    </div>
                </div>
                
                <div class="item-prices">
                    <div class="price-tag">
                        <small>Cash</small>
                        <div>Rs. <?php echo number_format($item['price'], 2); ?></div>
                    </div>
                    <div class="price-tag">
                        <small>Points</small>
                        <div><?php echo $item['points_price']; ?> ⭐</div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endforeach; ?>
    
    <a href="facility_details.php?id=<?php echo $facility_id; ?>" class="back-btn">
        <i class="fa-solid fa-arrow-left"></i> Back to Facility
    </a>
</div>

<a href="cafe_order.php?id=<?php echo $facility_id; ?>" class="floating-order-btn">
    <i class="fa-solid fa-cart-shopping"></i> Order Food
</a>

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
</script>

</body>
</html>