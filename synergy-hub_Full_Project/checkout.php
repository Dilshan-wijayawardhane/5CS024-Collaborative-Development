<?php

require_once 'config.php';
require_once 'functions.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$facility_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header("Location: cafe_menu.php?id=" . $facility_id);
    exit();
}

$user_sql = "SELECT UserID, Name, Email, PointsBalance FROM Users WHERE UserID = ?";
$user_stmt = mysqli_prepare($conn, $user_sql);
mysqli_stmt_bind_param($user_stmt, "i", $user_id);
mysqli_stmt_execute($user_stmt);
$user_result = mysqli_stmt_get_result($user_stmt);
$user = mysqli_fetch_assoc($user_result);

$cart_items = $_SESSION['cart'];

$subtotal = 0;
$total_points = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
    $total_points += $item['points_price'] * $item['quantity'];
}
$tax = $subtotal * 0.1;     
$total = $subtotal + $tax;  
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Synergy Hub</title>
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
        
        /* CHECKOUT NAV */
        .checkout-nav {
            background: linear-gradient(135deg, #1e4a76 0%, #2c7da0 100%);
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
        }
        
        .checkout-nav a {
            color: white;
            text-decoration: none;
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .checkout-nav a:hover {
            opacity: 0.9;
        }
        
        .checkout-title {
            font-size: 24px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .points-badge {
            background: rgba(255,255,255,0.2);
            padding: 8px 20px;
            border-radius: 30px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 20px;
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 30px;
        }
        
        .checkout-form {
            background: white;
            border-radius: 20px;
            padding: 30px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .section-title {
            color: #1e4a76;
            font-size: 22px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .section-title i {
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
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .payment-methods {
            margin: 20px 0;
        }
        
        .payment-option {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .payment-option:hover {
            background: #e0f2fe;
            border-color: #2c7da0;
        }
        
        .payment-option.selected {
            border-color: #2c7da0;
            background: #e0f2fe;
        }
        
        .payment-option input[type="radio"] {
            width: 18px;
            height: 18px;
            accent-color: #2c7da0;
        }
        
        .payment-option .method-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: #e0f2fe;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: #2c7da0;
        }
        
        .payment-option .method-details {
            flex: 1;
        }
        
        .payment-option .method-name {
            color: #1e293b;
            font-weight: 600;
            margin-bottom: 3px;
        }
        
        .payment-option .method-desc {
            color: #64748b;
            font-size: 12px;
        }
        
        .payment-option .points-balance {
            font-size: 12px;
            color: #2c7da0;
        }
        
        .payment-option .insufficient {
            color: #ef4444;
        }
        
        .card-details {
            background: #f8fafc;
            border-radius: 15px;
            padding: 20px;
            margin-top: 15px;
            display: none;
        }
        
        .card-details.show {
            display: block;
        }
        
        .card-display {
            background: linear-gradient(135deg, #1e4a76 0%, #2c7da0 100%);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            color: white;
            position: relative;
            min-height: 160px;
        }
        
        .card-chip {
            width: 40px;
            height: 30px;
            background: linear-gradient(135deg, #ffd700, #ffa500);
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .card-number-display {
            font-size: 18px;
            letter-spacing: 2px;
            margin-bottom: 20px;
            font-family: monospace;
        }
        
        .card-details-row {
            display: flex;
            justify-content: space-between;
        }
        
        .card-logo {
            position: absolute;
            bottom: 20px;
            right: 20px;
            font-size: 30px;
            opacity: 0.5;
        }
        
        .card-input-row {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            gap: 10px;
            margin-bottom: 10px;
        }
        
        .card-input {
            width: 100%;
            padding: 12px;
            border-radius: 8px;
            border: 2px solid #e2e8f0;
            background: white;
            color: #1e293b;
        }
        
        .card-input:focus {
            outline: none;
            border-color: #2c7da0;
        }
        
        .card-validation {
            font-size: 12px;
            color: #ef4444;
            min-height: 20px;
        }
        
        .card-validation.valid {
            color: #10b981;
        }
        
        .order-summary {
            background: white;
            border-radius: 20px;
            padding: 25px;
            border: 1px solid #e2e8f0;
            height: fit-content;
            position: sticky;
            top: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .summary-header {
            color: #1e4a76;
            font-size: 20px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .order-items {
            max-height: 300px;
            overflow-y: auto;
            margin-bottom: 20px;
        }
        
        .order-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e2e8f0;
            color: #1e293b;
        }
        
        .order-item-name {
            flex: 2;
        }
        
        .order-item-qty {
            flex: 1;
            text-align: center;
            color: #2c7da0;
        }
        
        .order-item-price {
            flex: 1;
            text-align: right;
        }
        
        .order-totals {
            margin: 20px 0;
            padding: 15px 0;
            border-top: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            color: #64748b;
            margin-bottom: 8px;
        }
        
        .grand-total {
            font-size: 20px;
            font-weight: 600;
            color: #1e293b;
            margin-top: 15px;
        }
        
        .grand-total span:last-child {
            color: #2c7da0;
        }
        
        .place-order-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #1e4a76 0%, #2c7da0 100%);
            border: none;
            border-radius: 10px;
            color: white;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s;
            margin: 15px 0;
        }
        
        .place-order-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(30, 74, 118, 0.3);
        }
        
        .place-order-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .secure-notice {
            text-align: center;
            color: #64748b;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }
        
        .error-message {
            background: #fee2e2;
            border: 1px solid #ef4444;
            color: #b91c1c;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 15px;
            text-align: center;
        }
        
        .success-message {
            background: #dcfce7;
            border: 1px solid #10b981;
            color: #166534;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 15px;
            text-align: center;
        }
        
        .back-btn {
            display: inline-block;
            margin-top: 30px;
            color: #1e4a76;
            text-decoration: none;
            font-size: 16px;
            padding: 10px 20px;
            background: white;
            border-radius: 30px;
            border: 1px solid #e2e8f0;
            transition: all 0.3s;
        }
        
        .back-btn:hover {
            background: #1e4a76;
            color: white;
            border-color: #1e4a76;
        }
        
        @media (max-width: 768px) {
            .container {
                grid-template-columns: 1fr;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .card-input-row {
                grid-template-columns: 1fr;
            }
            
            .checkout-nav {
                flex-direction: column;
                gap: 15px;
                text-align: center;
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
    <h1 class="logo">Synergy <span>Hub</span> - Checkout</h1>
    <div class="icons">
        <div class="points"><i class="fa-solid fa-star"></i> <span><?php echo $user['PointsBalance']; ?></span></div>
        <a href="cafe_order.php?id=<?php echo $facility_id; ?>" class="home-link"><i class="fa-solid fa-arrow-left"></i> Back</a>
    </div>
</header>

<div class="checkout-nav">
    <a href="cafe_order.php?id=<?php echo $facility_id; ?>">
        <i class="fa-solid fa-arrow-left"></i> Back to Cart
    </a>
    <div class="checkout-title">
        <i class="fa-solid fa-lock"></i> Secure Checkout
    </div>
    <div class="points-badge">
        <i class="fa-solid fa-star"></i> <?php echo $user['PointsBalance']; ?> points
    </div>
</div>

<div class="container">
    <div class="checkout-form">
        <h3 class="section-title"><i class="fa-regular fa-circle-check"></i> 1. Pickup Details</h3>
        
        <div class="form-row">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" id="fullName" value="<?php echo htmlspecialchars($user['Name']); ?>" placeholder="Your full name">
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" id="email" value="<?php echo htmlspecialchars($user['Email']); ?>" placeholder="Your email">
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>Phone Number</label>
                <input type="tel" id="phone" placeholder="07X XXX XXXX">
            </div>
            <div class="form-group">
                <label>Pickup Time</label>
                <select id="pickupTime">
                    <option value="As soon as ready">As soon as ready</option>
                    <option value="In 15 minutes">In 15 minutes</option>
                    <option value="In 30 minutes">In 30 minutes</option>
                    <option value="In 45 minutes">In 45 minutes</option>
                    <option value="In 1 hour">In 1 hour</option>
                </select>
            </div>
        </div>
        
        <div class="form-group">
            <label>Special Instructions (Optional)</label>
            <textarea id="instructions" rows="3" placeholder="Any allergies, preferences, or special requests..."></textarea>
        </div>
        
        <h3 class="section-title" style="margin-top: 30px;"><i class="fa-regular fa-credit-card"></i> 2. Payment Method</h3>
        
        <div class="payment-methods">
            <label class="payment-option" onclick="selectPayment('card')">
                <input type="radio" name="payment" value="card" checked>
                <div class="method-icon"><i class="fa-regular fa-credit-card"></i></div>
                <div class="method-details">
                    <div class="method-name">Credit / Debit Card</div>
                    <div class="method-desc">Pay securely with your card</div>
                </div>
            </label>
            
            <label class="payment-option" onclick="selectPayment('points')">
                <input type="radio" name="payment" value="points">
                <div class="method-icon"><i class="fa-solid fa-star"></i></div>
                <div class="method-details">
                    <div class="method-name">Pay with Points</div>
                    <div class="method-desc">Use your earned points</div>
                    <div class="points-balance <?php echo ($user['PointsBalance'] < $total_points) ? 'insufficient' : ''; ?>">
                        You have <?php echo $user['PointsBalance']; ?> points • Need <?php echo $total_points; ?> points
                    </div>
                </div>
            </label>
            
            <label class="payment-option" onclick="selectPayment('cash')">
                <input type="radio" name="payment" value="cash">
                <div class="method-icon"><i class="fa-solid fa-money-bill"></i></div>
                <div class="method-details">
                    <div class="method-name">Cash on Pickup</div>
                    <div class="method-desc">Pay when you collect your order</div>
                </div>
            </label>
        </div>
        
        <div class="card-details show" id="cardDetails">
            <div class="card-display">
                <div class="card-chip"></div>
                <div class="card-number-display" id="displayCardNumber">**** **** **** ****</div>
                <div class="card-details-row">
                    <span id="displayCardHolder">CARD HOLDER</span>
                    <span id="displayCardExpiry">MM/YY</span>
                </div>
                <div class="card-logo" id="cardLogo"><i class="fa-regular fa-credit-card"></i></div>
            </div>
            
            <div class="card-input-row">
                <div><input type="text" class="card-input" id="cardNumber" placeholder="Card Number" maxlength="19" oninput="formatCardNumber(this)"><div class="card-validation" id="cardNumberMsg"></div></div>
                <div><input type="text" class="card-input" id="cardExpiry" placeholder="MM/YY" maxlength="5" oninput="formatCardExpiry(this)"><div class="card-validation" id="cardExpiryMsg"></div></div>
                <div><input type="text" class="card-input" id="cardCvv" placeholder="CVV" maxlength="4" oninput="validateCardCvv(this)"><div class="card-validation" id="cardCvvMsg"></div></div>
            </div>
            <div><input type="text" class="card-input" id="cardHolder" placeholder="Card Holder Name" oninput="updateCardHolder(this)"><div class="card-validation" id="cardHolderMsg"></div></div>
        </div>
    </div>
    
    <div class="order-summary">
        <div class="summary-header"><i class="fa-solid fa-cart-shopping"></i> Your Order</div>
        <div class="order-items">
            <?php foreach($cart_items as $item): ?>
            <div class="order-item">
                <span class="order-item-name"><?php echo htmlspecialchars($item['name']); ?></span>
                <span class="order-item-qty">x<?php echo $item['quantity']; ?></span>
                <span class="order-item-price">Rs. <?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="order-totals">
            <div class="total-row"><span>Subtotal</span><span>Rs. <?php echo number_format($subtotal, 2); ?></span></div>
            <div class="total-row"><span>Tax (10%)</span><span>Rs. <?php echo number_format($tax, 2); ?></span></div>
            <div class="grand-total"><span>Total</span><span>Rs. <?php echo number_format($total, 2); ?></span></div>
            <div class="total-row" style="margin-top: 10px;"><span>Points Value</span><span><?php echo $total_points; ?> ⭐</span></div>
        </div>
        
        <div id="errorMsg" class="error-message" style="display: none;"></div>
        <div id="successMsg" class="success-message" style="display: none;"></div>
        
        <button class="place-order-btn" onclick="placeOrder()" id="placeOrderBtn"><i class="fa-solid fa-lock"></i> Place Order</button>
        <div class="secure-notice"><i class="fa-solid fa-shield-halved"></i><span>Your payment information is secure</span></div>
    </div>
</div>

<a href="cafe_order.php?id=<?php echo $facility_id; ?>" class="back-btn" style="margin-left: 20px;"><i class="fa-solid fa-arrow-left"></i> Back to Cart</a>

<script>
let cart = <?php echo json_encode($cart_items); ?>;
let userPoints = <?php echo $user['PointsBalance']; ?>;
let facilityId = <?php echo $facility_id; ?>;
let totalPoints = <?php echo $total_points; ?>;

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
    if(sidebar && btn && overlay && !sidebar.contains(e.target) && !btn.contains(e.target) && sidebar.classList.contains("active")) {
        sidebar.classList.remove("active");
        overlay.classList.remove("active");
        btn.classList.remove("active");
    }
});

function selectPayment(method) {
    document.querySelectorAll('input[name="payment"]').forEach(radio => { radio.checked = radio.value === method; });
    const cardDetails = document.getElementById('cardDetails');
    if (method === 'card') { cardDetails.classList.add('show'); } 
    else { cardDetails.classList.remove('show'); }
    if (method === 'points' && userPoints < totalPoints) {
        document.getElementById('placeOrderBtn').disabled = true;
        showError('Insufficient points! You need ' + totalPoints + ' points.');
    } else {
        document.getElementById('placeOrderBtn').disabled = false;
        hideError();
    }
}

function formatCardNumber(input) {
    let value = input.value.replace(/\s/g, '');
    let formatted = '';
    for (let i = 0; i < value.length; i++) {
        if (i > 0 && i % 4 === 0) formatted += ' ';
        formatted += value[i];
    }
    input.value = formatted;
    document.getElementById('displayCardNumber').textContent = formatted || '**** **** **** ****';
    validateCardNumber();
}

function validateCardNumber() {
    const cardNumber = document.getElementById('cardNumber').value.replace(/\s/g, '');
    const msg = document.getElementById('cardNumberMsg');
    if (cardNumber.length === 16 && /^\d+$/.test(cardNumber)) {
        msg.textContent = '✓ Valid';
        msg.classList.add('valid');
        detectCardType(cardNumber);
        return true;
    } else {
        msg.textContent = cardNumber.length === 0 ? '' : 'Invalid card number';
        msg.classList.remove('valid');
        return false;
    }
}

function detectCardType(number) {
    const logo = document.getElementById('cardLogo');
    if (number.startsWith('4')) logo.innerHTML = '<i class="fa-brands fa-cc-visa"></i>';
    else if (number.startsWith('5')) logo.innerHTML = '<i class="fa-brands fa-cc-mastercard"></i>';
    else if (number.startsWith('3')) logo.innerHTML = '<i class="fa-brands fa-cc-amex"></i>';
    else logo.innerHTML = '<i class="fa-regular fa-credit-card"></i>';
}

function formatCardExpiry(input) {
    let value = input.value.replace(/\//g, '');
    if (value.length >= 2) value = value.substring(0, 2) + '/' + value.substring(2, 4);
    input.value = value;
    document.getElementById('displayCardExpiry').textContent = value || 'MM/YY';
    validateCardExpiry();
}

function validateCardExpiry() {
    const expiry = document.getElementById('cardExpiry').value;
    const msg = document.getElementById('cardExpiryMsg');
    if (expiry.length === 5) {
        const [month, year] = expiry.split('/');
        const currentYear = new Date().getFullYear() % 100;
        const currentMonth = new Date().getMonth() + 1;
        if (month >= 1 && month <= 12 && year >= currentYear && (year > currentYear || month >= currentMonth)) {
            msg.textContent = '✓ Valid';
            msg.classList.add('valid');
            return true;
        }
    }
    msg.textContent = expiry.length === 0 ? '' : 'Invalid expiry';
    msg.classList.remove('valid');
    return false;
}

function validateCardCvv(input) {
    const value = input.value;
    const msg = document.getElementById('cardCvvMsg');
    if (value.length >= 3 && /^\d+$/.test(value)) {
        msg.textContent = '✓ Valid';
        msg.classList.add('valid');
        return true;
    } else {
        msg.textContent = value.length === 0 ? '' : 'Invalid CVV';
        msg.classList.remove('valid');
        return false;
    }
}

function updateCardHolder(input) {
    const value = input.value.toUpperCase();
    document.getElementById('displayCardHolder').textContent = value || 'CARD HOLDER';
}

function showError(message) {
    const errorDiv = document.getElementById('errorMsg');
    errorDiv.textContent = message;
    errorDiv.style.display = 'block';
    setTimeout(() => { errorDiv.style.display = 'none'; }, 5000);
}

function hideError() { document.getElementById('errorMsg').style.display = 'none'; }

function showSuccess(message) {
    const successDiv = document.getElementById('successMsg');
    successDiv.textContent = message;
    successDiv.style.display = 'block';
}

function placeOrder() {
    const paymentMethod = document.querySelector('input[name="payment"]:checked').value;
    const fullName = document.getElementById('fullName').value.trim();
    const email = document.getElementById('email').value.trim();
    const phone = document.getElementById('phone').value.trim();
    
    if (!fullName || !email || !phone) { showError('Please fill in all required fields'); return; }
    if (!email.includes('@') || !email.includes('.')) { showError('Please enter a valid email address'); return; }
    if (phone.length < 10) { showError('Please enter a valid phone number'); return; }
    
    // Only validate card details if payment method is 'card'
    if (paymentMethod === 'card') {
        if (!validateCardNumber() || !validateCardExpiry() || !validateCardCvv(document.getElementById('cardCvv'))) {
            showError('Please enter valid card details');
            return;
        }
    }
    
    if (paymentMethod === 'points' && userPoints < totalPoints) { showError('Insufficient points!'); return; }
    
    const orderData = {
        facility_id: facilityId,
        payment_method: paymentMethod,
        full_name: fullName,
        email: email,
        phone: phone,
        pickup_time: document.getElementById('pickupTime').value,
        instructions: document.getElementById('instructions').value,
        items: <?php echo json_encode($cart_items); ?>,
        total_amount: <?php echo $total; ?>,
        points_used: paymentMethod === 'points' ? totalPoints : 0
    };
    
    const btn = document.getElementById('placeOrderBtn');
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Processing...';
    btn.disabled = true;
    
    fetch('process_checkout.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(orderData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccess('Order placed successfully!');
            setTimeout(() => { window.location.href = 'order_confirmation.php?order_id=' + data.order_ids.join(','); }, 1500);
        } else {
            showError(data.message || 'Error placing order');
            btn.innerHTML = '<i class="fa-solid fa-lock"></i> Place Order';
            btn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('An error occurred. Please try again.');
        btn.innerHTML = '<i class="fa-solid fa-lock"></i> Place Order';
        btn.disabled = false;
    });
}
</script>

</body>
</html>