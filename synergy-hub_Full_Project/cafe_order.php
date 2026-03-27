<?php


require_once 'config.php';
require_once 'functions.php';


if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$facility_id = isset($_GET['id']) ? intval($_GET['id']) : 0;


if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}



if (isset($_POST['action']) && $_POST['action'] == 'add_to_cart') {
    header('Content-Type: application/json');
    
    $item_id = intval($_POST['item_id']);
    $item_name = $_POST['item_name'];
    $price = floatval($_POST['price']);
    $points_price = intval($_POST['points_price']);
    $quantity = intval($_POST['quantity']);
    $category = $_POST['category'] ?? 'Food';
    
    $found = false;
    foreach ($_SESSION['cart'] as &$item) {
        if ($item['item_id'] == $item_id) {
            $item['quantity'] += $quantity;
            $found = true;
            break;
        }
    }
    
    if (!$found) {
        $_SESSION['cart'][] = [
            'item_id' => $item_id,
            'name' => $item_name,
            'price' => $price,
            'points_price' => $points_price,
            'quantity' => $quantity,
            'category' => $category
        ];
    }
    
    
    $cart_count = 0;
    $cart_total = 0;
    foreach ($_SESSION['cart'] as $item) {
        $cart_count += $item['quantity'];
        $cart_total += $item['price'] * $item['quantity'];
    }
    
    echo json_encode([
        'success' => true, 
        'cart_count' => $cart_count,
        'cart_total' => $cart_total,
        'message' => 'Added to cart!'
    ]);
    exit();
}


if (isset($_GET['action']) && $_GET['action'] == 'get_cart') {
    header('Content-Type: application/json');
    
    $cart_items = [];
    $cart_count = 0;
    $cart_total = 0;
    
    foreach ($_SESSION['cart'] as $item) {
        $cart_items[] = $item;
        $cart_count += $item['quantity'];
        $cart_total += $item['price'] * $item['quantity'];
    }
    
    echo json_encode([
        'success' => true,
        'items' => $cart_items,
        'count' => $cart_count,
        'total' => $cart_total
    ]);
    exit();
}


if (isset($_POST['action']) && $_POST['action'] == 'remove_from_cart') {
    header('Content-Type: application/json');
    
    $item_id = intval($_POST['item_id']);
    
    foreach ($_SESSION['cart'] as $key => $item) {
        if ($item['item_id'] == $item_id) {
            unset($_SESSION['cart'][$key]);
            break;
        }
    }
    
    $_SESSION['cart'] = array_values($_SESSION['cart']);
    
    echo json_encode(['success' => true]);
    exit();
}


if (isset($_POST['action']) && $_POST['action'] == 'update_cart') {
    header('Content-Type: application/json');
    
    $item_id = intval($_POST['item_id']);
    $quantity = intval($_POST['quantity']);
    
    foreach ($_SESSION['cart'] as &$item) {
        if ($item['item_id'] == $item_id) {
            if ($quantity <= 0) {
                $item = null;
            } else {
                $item['quantity'] = $quantity;
            }
            break;
        }
    }
    
    $_SESSION['cart'] = array_filter($_SESSION['cart']);
    $_SESSION['cart'] = array_values($_SESSION['cart']);
    
    echo json_encode(['success' => true]);
    exit();
}


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

$cart_items = $_SESSION['cart'];
$cart_count = 0;
foreach ($cart_items as $item) {
    $cart_count += $item['quantity'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Food - Synergy Hub</title>
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
        
        
        .sidebar {
            position: fixed;
            left: -280px;
            top: 0;
            width: 280px;
            height: 100%;
            background: linear-gradient(180deg, #1e2b3c 0%, #0d1a24 100%);
            backdrop-filter: blur(10px);
            transition: 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 9999;
            box-shadow: 4px 0 30px rgba(0, 0, 0, 0.3);
            border-right: 1px solid rgba(255, 255, 255, 0.1);
            overflow-y: auto;
        }

        .sidebar.active {
            left: 0;
        }

        .sidebar-header {
            padding: 25px 20px 20px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 15px;
            position: relative;
            overflow: hidden;
        }

        .sidebar-header::after {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, rgba(100, 108, 255, 0.15) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        .sidebar-header h2 {
            color: white;
            font-size: 24px;
            font-weight: 700;
            margin: 0 0 5px 0;
            letter-spacing: -0.5px;
            background: linear-gradient(135deg, #ffffff 0%, #a5b4fc 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .sidebar-header p {
            color: #94a3b8;
            font-size: 13px;
            margin: 0;
            font-weight: 400;
        }

        .sidebar-header p i {
            color: #22d3ee;
            margin-right: 5px;
            font-size: 10px;
        }

        .sidebar-user {
            padding: 15px 20px;
            background: rgba(255, 255, 255, 0.03);
            margin: 0 15px 20px 15px;
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.05);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .sidebar-user-avatar {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.2);
        }

        .sidebar-user-info h4 {
            color: white;
            font-size: 15px;
            margin: 0 0 3px 0;
            font-weight: 600;
        }

        .sidebar-user-info p {
            color: #94a3b8;
            font-size: 12px;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .sidebar-user-info p i {
            color: #fbbf24;
            font-size: 10px;
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
            color: #b8c7de;
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
            color: #5f7d9e;
            transition: all 0.3s ease;
            text-align: center;
        }

        .sidebar-nav-link:hover {
            background: rgba(168, 192, 255, 0.1);
            color: white;
            transform: translateX(5px);
        }

        .sidebar-nav-link:hover i {
            color: #a5b4fc;
        }

        .sidebar-nav-link.active {
            background: linear-gradient(90deg, rgba(168, 192, 255, 0.15) 0%, rgba(168, 192, 255, 0.05) 100%);
            color: white;
            border-left: 3px solid #a5b4fc;
        }

        .sidebar-nav-link.active i {
            color: #a5b4fc;
        }

        .sidebar-badge {
            background: #ef4444;
            color: white;
            font-size: 10px;
            font-weight: 600;
            padding: 2px 6px;
            border-radius: 30px;
            margin-left: auto;
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        .sidebar-divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            margin: 20px 20px;
        }

        .sidebar-section-title {
            padding: 0 20px;
            margin: 25px 0 10px 0;
            color: #94a3b8;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .sidebar-club-preview {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 16px;
            padding: 15px;
            margin: 0 15px 20px 15px;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .sidebar-club-preview h4 {
            color: white;
            font-size: 13px;
            margin: 0 0 12px 0;
            display: flex;
            align-items: center;
            gap: 6px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            opacity: 0.8;
        }

        .sidebar-club-preview h4 i {
            color: #fbbf24;
        }

        .sidebar-club-item {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 12px;
            padding: 12px;
            margin-bottom: 10px;
            border: 1px solid rgba(255, 255, 255, 0.03);
            transition: transform 0.2s;
        }

        .sidebar-club-item:hover {
            transform: translateX(5px);
            background: rgba(0, 0, 0, 0.3);
        }

        .sidebar-club-item h5 {
            color: white;
            font-size: 14px;
            margin: 0 0 4px 0;
            font-weight: 600;
        }

        .sidebar-club-item p {
            color: #94a3b8;
            font-size: 11px;
            margin: 0 0 6px 0;
            line-height: 1.4;
        }

        .sidebar-club-tag {
            background: #2d4c6e;
            color: white;
            font-size: 9px;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 30px;
            display: inline-block;
            text-transform: uppercase;
        }

        .sidebar-stats {
            display: flex;
            justify-content: space-around;
            padding: 15px 10px;
            margin: 0 15px;
            background: rgba(255, 255, 255, 0.02);
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.03);
        }

        .sidebar-stat-item {
            text-align: center;
        }

        .sidebar-stat-value {
            color: white;
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 3px;
            background: linear-gradient(135deg, #fff, #a5b4fc);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .sidebar-stat-label {
            color: #94a3b8;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
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
            color: #94a3b8;
            text-decoration: none;
            font-size: 11px;
            transition: color 0.2s;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .sidebar-footer-links a:hover {
            color: white;
        }

        .sidebar-footer-links a i {
            font-size: 10px;
        }

        .sidebar-copyright {
            color: #64748b;
            font-size: 10px;
            text-align: center;
        }

        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(3px);
            z-index: 9998;
            display: none;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .sidebar-overlay.active {
            display: block;
            opacity: 1;
        }

        .sidebar::-webkit-scrollbar {
            width: 4px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: transparent;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 20px;
        }

        .sidebar::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.3);
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
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
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
        
        .stock-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 12px;
        }
        
        .item-name {
            color: white;
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 10px;
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
            font-size: 16px;
            font-weight: 600;
        }
        
        .quantity-selector {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            margin: 15px 0;
        }
        
        .qty-btn {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            border: none;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-size: 18px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .qty-btn:hover {
            transform: scale(1.1);
        }
        
        .qty-value {
            color: white;
            font-size: 16px;
            font-weight: 600;
            min-width: 30px;
            text-align: center;
        }
        
        .add-to-cart-btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            color: white;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s;
        }
        
        .add-to-cart-btn:hover {
            transform: scale(1.02);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        /* Cart Sidebar */
        .cart-sidebar {
            position: fixed;
            right: -400px;
            top: 0;
            width: 380px;
            height: 100%;
            background: white;
            box-shadow: -5px 0 20px rgba(0,0,0,0.2);
            transition: right 0.3s ease;
            z-index: 10000;
            display: flex;
            flex-direction: column;
        }
        
        .cart-sidebar.open {
            right: 0;
        }
        
        .cart-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .cart-header h3 {
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .close-cart {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            font-size: 18px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .close-cart:hover {
            background: rgba(255,255,255,0.3);
            transform: rotate(90deg);
        }
        
        .cart-items {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
        }
        
        .cart-item {
            display: flex;
            gap: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 12px;
            margin-bottom: 15px;
            position: relative;
        }
        
        .cart-item-img {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            object-fit: cover;
        }
        
        .cart-item-details {
            flex: 1;
        }
        
        .cart-item-name {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .cart-item-price {
            color: #667eea;
            font-weight: 600;
        }
        
        .cart-item-controls {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 8px;
        }
        
        .cart-qty-btn {
            width: 28px;
            height: 28px;
            border-radius: 6px;
            border: 1px solid #ddd;
            background: white;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s;
        }
        
        .cart-qty-btn:hover {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        
        .remove-item {
            position: absolute;
            top: 10px;
            right: 10px;
            color: #ef4444;
            cursor: pointer;
            opacity: 0.5;
            transition: opacity 0.2s;
        }
        
        .remove-item:hover {
            opacity: 1;
        }
        
        .cart-footer {
            padding: 20px;
            border-top: 2px solid #eee;
            background: #f8f9fa;
        }
        
        .cart-total {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            font-size: 18px;
            font-weight: 700;
        }
        
        .checkout-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .checkout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        
        .empty-cart {
            text-align: center;
            padding: 40px 20px;
            color: #999;
        }
        
        .empty-cart i {
            font-size: 48px;
            margin-bottom: 15px;
            color: #ccc;
        }
        
        .cart-toggle {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            width: 65px;
            height: 65px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            cursor: pointer;
            box-shadow: 0 5px 20px rgba(0,0,0,0.3);
            z-index: 9999;
            transition: all 0.3s;
            border: 2px solid rgba(255,255,255,0.2);
        }
        
        .cart-toggle:hover {
            transform: scale(1.1);
        }
        
        .cart-count {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #ef4444;
            color: white;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            border: 2px solid white;
        }
        
        .toast {
            position: fixed;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%) translateY(100px);
            background: #10b981;
            color: white;
            padding: 12px 24px;
            border-radius: 50px;
            font-weight: 600;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
            transition: transform 0.3s;
            z-index: 10001;
        }
        
        .toast.show {
            transform: translateX(-50%) translateY(0);
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
            .cart-sidebar {
                width: 100%;
                right: -100%;
            }
        }
    </style>
</head>
<body>

<div class="bg"></div>

<!-- SIDEBAR -->
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

<!-- NAVBAR -->
<header class="navbar">
    <div class="menu-btn" onclick="toggleSidebar()">
        <i class="fa-solid fa-bars"></i>
    </div>
    
    <h1 class="logo">Synergy <span>Hub</span> - Order Food</h1>
    
    <div class="icons">
        <div class="points">
            <i class="fa-solid fa-star"></i>
            <span id="pointsDisplay"><?php echo $user['PointsBalance']; ?></span>
        </div>
        <a href="cafe_menu.php?id=<?php echo $facility_id; ?>" class="home-link">
            <i class="fa-solid fa-arrow-left"></i> Back to Menu
        </a>
    </div>
</header>

<div class="cart-toggle" onclick="toggleCart()">
    <i class="fa-solid fa-cart-shopping"></i>
    <span class="cart-count" id="cartCount"><?php echo $cart_count; ?></span>
</div>

<div class="cart-sidebar" id="cartSidebar">
    <div class="cart-header">
        <h3><i class="fa-solid fa-cart-shopping"></i> Your Cart</h3>
        <button class="close-cart" onclick="toggleCart()"><i class="fa-solid fa-times"></i></button>
    </div>
    
    <div class="cart-items" id="cartItems">
        <div class="empty-cart">
            <i class="fa-solid fa-cart-plus"></i>
            <p>Your cart is empty</p>
        </div>
    </div>
    
    <div class="cart-footer" id="cartFooter" style="display: none;">
        <div class="cart-total">
            <span>Total:</span>
            <span id="cartTotal">Rs. 0</span>
        </div>
        <button class="checkout-btn" onclick="goToCheckout()">
            <i class="fa-solid fa-lock"></i> Proceed to Checkout
        </button>
    </div>
</div>

<div class="container">
    
    <div class="points-badge">
        <i class="fa-solid fa-star"></i> Your Points: <span id="currentPoints"><?php echo $user['PointsBalance']; ?></span>
    </div>
    
    <h1 class="page-title">🛒 Order Food</h1>
    
    <?php foreach($menu_by_category as $category => $items): ?>
    <h2 class="menu-category"><?php echo $category; ?></h2>
    <div class="menu-grid">
        <?php foreach($items as $item): 
            $image_url = isset($food_images[$item['name']]) ? $food_images[$item['name']] : 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=300&h=200&fit=crop';
            $stock = $item['stock'] ?? 10;
        ?>
        <div class="menu-item" id="item-<?php echo $item['item_id']; ?>" data-stock="<?php echo $stock; ?>">
            <div class="food-image">
                <img src="<?php echo $image_url; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                <?php if($stock < 5): ?>
                <span class="stock-badge">Only <?php echo $stock; ?> left!</span>
                <?php endif; ?>
            </div>
            
            <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
            
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
            
            <div class="quantity-selector">
                <button class="qty-btn" onclick="updateItemQty(<?php echo $item['item_id']; ?>, 'dec', <?php echo $stock; ?>)">-</button>
                <span class="qty-value" id="qty-<?php echo $item['item_id']; ?>">1</span>
                <button class="qty-btn" onclick="updateItemQty(<?php echo $item['item_id']; ?>, 'inc', <?php echo $stock; ?>)">+</button>
            </div>
            
            <button class="add-to-cart-btn" onclick="addToCart(<?php echo $item['item_id']; ?>, '<?php echo addslashes($item['name']); ?>', <?php echo $item['price']; ?>, <?php echo $item['points_price']; ?>, <?php echo $stock; ?>, '<?php echo $item['category']; ?>')">
                <i class="fa-solid fa-cart-plus"></i> Add to Cart
            </button>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endforeach; ?>
    
    <a href="cafe_menu.php?id=<?php echo $facility_id; ?>" class="back-btn">
        <i class="fa-solid fa-arrow-left"></i> Back to Menu
    </a>
</div>


<div class="toast" id="toast">Item added to cart!</div>

<script>
function toggleSidebar() {
    const sidebar = document.querySelector(".sidebar");
    const overlay = document.getElementById("sidebarOverlay");
    const menuBtn = document.querySelector(".menu-btn");
    
    if(sidebar.style.left === "0px") {
        sidebar.style.left = "-280px";
        overlay.classList.remove("active");
        menuBtn.classList.remove("active");
    } else {
        sidebar.style.left = "0px";
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
       sidebar.style.left === "0px") {
        sidebar.style.left = "-280px";
        overlay.classList.remove("active");
        btn.classList.remove("active");
    }
});

let itemQuantities = {};

document.querySelectorAll('[id^="qty-"]').forEach(el => {
    let id = el.id.replace('qty-', '');
    itemQuantities[id] = 1;
});

function updateItemQty(itemId, action, stock) {
    let qtyEl = document.getElementById('qty-' + itemId);
    let currentQty = parseInt(qtyEl.innerText);
    
    if (action === 'inc' && currentQty < stock) {
        itemQuantities[itemId] = currentQty + 1;
    } else if (action === 'dec' && currentQty > 1) {
        itemQuantities[itemId] = currentQty - 1;
    } else if (action === 'inc' && currentQty >= stock) {
        showToast('Only ' + stock + ' items available!');
        return;
    }
    
    qtyEl.innerText = itemQuantities[itemId];
}

function addToCart(itemId, name, price, points, stock, category) {
    let qty = itemQuantities[itemId];
    
    let btn = event.currentTarget;
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Adding...';
    
    fetch('cafe_order.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=add_to_cart&item_id=' + itemId + 
              '&item_name=' + encodeURIComponent(name) + 
              '&price=' + price + 
              '&points_price=' + points + 
              '&quantity=' + qty + 
              '&category=' + encodeURIComponent(category)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            itemQuantities[itemId] = 1;
            document.getElementById('qty-' + itemId).innerText = '1';
            
            document.getElementById('cartCount').innerText = data.cart_count;
            
            showToast('✅ ' + name + ' added to cart!');
            
            loadCart();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Error adding to cart');
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-cart-plus"></i> Add to Cart';
    });
}

function loadCart() {
    fetch('cafe_order.php?action=get_cart')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateCartDisplay(data);
            }
        });
}


function updateCartDisplay(data) {
    let cartItems = document.getElementById('cartItems');
    let cartFooter = document.getElementById('cartFooter');
    let cartCount = document.getElementById('cartCount');
    
    cartCount.innerText = data.count;
    
    if (data.count === 0) {
        cartItems.innerHTML = '<div class="empty-cart"><i class="fa-solid fa-cart-plus"></i><p>Your cart is empty</p></div>';
        cartFooter.style.display = 'none';
        return;
    }
    
    let html = '';
    data.items.forEach(item => {
        html += `
            <div class="cart-item" data-id="${item.item_id}">
                <div class="cart-item-details">
                    <div class="cart-item-name">${item.name}</div>
                    <div class="cart-item-price">Rs. ${item.price} / ${item.points_price} ⭐</div>
                    <div class="cart-item-controls">
                        <button class="cart-qty-btn" onclick="updateCartItem(${item.item_id}, ${item.quantity - 1})">-</button>
                        <span>${item.quantity}</span>
                        <button class="cart-qty-btn" onclick="updateCartItem(${item.item_id}, ${item.quantity + 1})">+</button>
                    </div>
                </div>
                <i class="fa-solid fa-trash remove-item" onclick="removeFromCart(${item.item_id})"></i>
            </div>
        `;
    });
    
    cartItems.innerHTML = html;
    document.getElementById('cartTotal').innerText = 'Rs. ' + data.total.toFixed(2);
    cartFooter.style.display = 'block';
}


function updateCartItem(itemId, newQty) {
    if (newQty < 1) {
        removeFromCart(itemId);
        return;
    }
    
    fetch('cafe_order.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=update_cart&item_id=' + itemId + '&quantity=' + newQty
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadCart();
        }
    });
}


function removeFromCart(itemId) {
    if (confirm('Remove this item from cart?')) {
        fetch('cafe_order.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=remove_from_cart&item_id=' + itemId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadCart();
                showToast('Item removed from cart');
            }
        });
    }
}


function toggleCart() {
    document.getElementById('cartSidebar').classList.toggle('open');
    loadCart();
}


function goToCheckout() {
    window.location.href = 'checkout.php?id=<?php echo $facility_id; ?>';
}


function showToast(message) {
    let toast = document.getElementById('toast');
    toast.textContent = message;
    toast.classList.add('show');
    setTimeout(() => {
        toast.classList.remove('show');
    }, 2000);
}


document.addEventListener("click", function(e) {
    const cart = document.getElementById('cartSidebar');
    const cartBtn = document.querySelector('.cart-toggle');
    if (cart && cartBtn && !cart.contains(e.target) && !cartBtn.contains(e.target) && cart.classList.contains('open')) {
        cart.classList.remove('open');
    }
});


document.addEventListener('DOMContentLoaded', function() {
    loadCart();
});
</script>

</body>
</html>