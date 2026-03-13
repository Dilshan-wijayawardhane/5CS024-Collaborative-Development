<?php
require_once 'config.php';
require_once 'functions.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$facility_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get menu from database
$menu_sql = "SELECT * FROM cafe_menu WHERE available = TRUE ORDER BY category, name";
$menu_result = mysqli_query($conn, $menu_sql);

// Get user points (just for display)
$user_sql = "SELECT PointsBalance FROM Users WHERE UserID = ?";
$user_stmt = mysqli_prepare($conn, $user_sql);
mysqli_stmt_bind_param($user_stmt, "i", $user_id);
mysqli_stmt_execute($user_stmt);
$user_result = mysqli_stmt_get_result($user_stmt);
$user = mysqli_fetch_assoc($user_result);

// Organize menu by category
$menu_by_category = [];
while($item = mysqli_fetch_assoc($menu_result)) {
    $menu_by_category[$item['category']][] = $item;
}

// Food Images Array
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
        
        .order-now-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
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
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
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
        
        .menu-category {
            color: white;
            font-size: 28px;
            margin: 40px 0 20px;
            border-left: 5px solid #22d3ee;
            padding-left: 15px;
        }
        
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
        }
        
        .menu-item {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 20px;
            border: 1px solid rgba(255,255,255,0.2);
            transition: all 0.3s;
        }
        
        .menu-item:hover {
            transform: translateY(-5px);
            background: rgba(255,255,255,0.15);
        }
        
        .food-image {
            width: 100%;
            height: 160px;
            border-radius: 15px;
            overflow: hidden;
            margin-bottom: 15px;
            position: relative;
        }
        
        .food-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        /* Stock Badge */
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
        
        .item-name {
            color: white;
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        /* Availability Bar */
        .availability-section {
            margin: 15px 0;
        }
        
        .availability-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: 13px;
            color: rgba(255,255,255,0.8);
        }
        
        .availability-bar {
            width: 100%;
            height: 8px;
            background: rgba(255,255,255,0.1);
            border-radius: 10px;
            overflow: hidden;
        }
        
        .availability-fill {
            height: 100%;
            background: linear-gradient(90deg, #22d3ee, #667eea);
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
            background: rgba(0,0,0,0.2);
            border-radius: 10px;
        }
        
        .price-tag {
            text-align: center;
            flex: 1;
        }
        
        .price-tag small {
            color: rgba(255,255,255,0.7);
            font-size: 12px;
        }
        
        .price-tag div {
            color: #22d3ee;
            font-size: 18px;
            font-weight: 600;
        }
        
        .order-btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            color: white;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
        }
        
        .order-btn:hover {
            transform: scale(1.02);
        }
        
        .order-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
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
        
        /* Order Now Floating Button */
        .floating-order-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.3);
            z-index: 1000;
            transition: all 0.3s;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .floating-order-btn:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }
        
        .floating-order-btn i {
            font-size: 20px;
        }
        
        @media (max-width: 768px) {
            .floating-order-btn {
                bottom: 80px;
                right: 15px;
                padding: 12px 20px;
                font-size: 14px;
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

<!-- MAIN CONTENT -->
<div class="container">
    
    <div class="points-badge">
        <i class="fa-solid fa-star"></i> Your Points: <?php echo $user['PointsBalance']; ?>
    </div>
    
    <h1 class="page-title">☕ Campus Café Menu</h1>
    
    <!-- MENU BY CATEGORY -->
    <?php foreach($menu_by_category as $category => $items): ?>
    <h2 class="menu-category"><?php echo $category; ?></h2>
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
            
            <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
            
            <!-- Availability Bar -->
            <div class="availability-section">
                <div class="availability-header">
                    <span>Availability</span>
                    <span><?php echo $stock; ?>/20 left</span>
                </div>
                <div class="availability-bar">
                    <div class="availability-fill <?php echo $stock <= 3 ? 'low' : ''; ?>" 
                         style="width: <?php echo $stock_percent; ?>%;"></div>
                </div>
            </div>
            
            <!-- Prices -->
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
        <?php endforeach; ?>
    </div>
    <?php endforeach; ?>
    
    <a href="facility_details.php?id=<?php echo $facility_id; ?>" class="back-btn">
        <i class="fa-solid fa-arrow-left"></i> Back to Facility
    </a>
</div>

<!-- Floating Order Button -->
<a href="cafe_order.php?id=<?php echo $facility_id; ?>" class="floating-order-btn">
    <i class="fa-solid fa-cart-shopping"></i> Order Food
</a>

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
</script>

</body>
</html>