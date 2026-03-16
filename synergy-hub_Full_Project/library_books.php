<?php
require_once 'config.php';
require_once 'functions.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$facility_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get the tab parameter from URL
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'browse';

// Get all available books
$books_sql = "SELECT * FROM books WHERE available > 0 ORDER BY category, title";
$books_result = mysqli_query($conn, $books_sql);

// Get user's borrowed books with days remaining calculation
$borrowed_sql = "SELECT b.*, bk.title, bk.author, bk.category,
                        DATEDIFF(b.due_date, CURDATE()) as days_remaining,
                        DATEDIFF(CURDATE(), b.due_date) as days_overdue
                 FROM borrowed_books b
                 JOIN books bk ON b.book_id = bk.book_id
                 WHERE b.user_id = ? AND b.status = 'borrowed'
                 ORDER BY b.due_date ASC";
$borrowed_stmt = mysqli_prepare($conn, $borrowed_sql);
mysqli_stmt_bind_param($borrowed_stmt, "i", $user_id);
mysqli_stmt_execute($borrowed_stmt);
$borrowed_result = mysqli_stmt_get_result($borrowed_stmt);

// Get user points and name
$user_sql = "SELECT PointsBalance, Name FROM Users WHERE UserID = ?";
$user_stmt = mysqli_prepare($conn, $user_sql);
mysqli_stmt_bind_param($user_stmt, "i", $user_id);
mysqli_stmt_execute($user_stmt);
$user_result = mysqli_stmt_get_result($user_stmt);
$user = mysqli_fetch_assoc($user_result);

// Get facilities count for badge
$facilities_count_sql = "SELECT COUNT(*) as count FROM Facilities WHERE Status = 'Open'";
$facilities_count_result = mysqli_query($conn, $facilities_count_sql);
$facilities_count = mysqli_fetch_assoc($facilities_count_result)['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Books - Synergy Hub</title>
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
            position: relative;
            z-index: 1000;
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
            transition: all 0.3s;
        }
        
        .points.active {
            transform: scale(1.1);
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .home-link {
            color: white;
            font-size: 20px;
            text-decoration: none;
        }
        
        .home-link:hover {
            color: #22d3ee;
        }
        
        /* ========================================
           SYNERGY HUB SIDEBAR - LAS SANATA
           ======================================== */

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
        
        /* MAIN CONTENT */
        .container {
            padding: 30px;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .page-title {
            color: white;
            font-size: 32px;
            margin-bottom: 30px;
            text-align: center;
            text-shadow: 0 2px 10px rgba(0,0,0,0.3);
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
        
        .points-badge i {
            color: #22d3ee;
            margin-right: 8px;
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
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .tab-btn:hover {
            background: rgba(255,255,255,0.2);
        }
        
        .tab-btn.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-color: transparent;
        }
        
        .tab-badge {
            background: #22d3ee;
            color: #0f172a;
            padding: 2px 8px;
            border-radius: 30px;
            font-size: 12px;
            margin-left: 8px;
        }
        
        /* BOOKS GRID */
        .books-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }
        
        .book-card {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 25px;
            border: 1px solid rgba(255,255,255,0.2);
            transition: all 0.3s;
        }
        
        .book-card:hover {
            transform: translateY(-5px);
            background: rgba(255,255,255,0.15);
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        
        .book-title {
            color: white;
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .book-author {
            color: #22d3ee;
            font-size: 16px;
            margin-bottom: 10px;
        }
        
        .book-category {
            display: inline-block;
            padding: 5px 15px;
            background: rgba(255,255,255,0.1);
            border-radius: 20px;
            color: rgba(255,255,255,0.8);
            font-size: 12px;
            margin-bottom: 15px;
        }
        
        .book-description {
            color: rgba(255,255,255,0.7);
            font-size: 14px;
            margin: 15px 0;
            line-height: 1.5;
        }
        
        .book-stats {
            display: flex;
            justify-content: space-between;
            margin: 15px 0;
            padding: 10px 0;
            border-top: 1px solid rgba(255,255,255,0.1);
            border-bottom: 1px solid rgba(255,255,255,0.1);
            color: rgba(255,255,255,0.8);
        }
        
        .borrow-btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            color: white;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .borrow-btn:hover {
            transform: scale(1.02);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        
        .borrow-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }
        
        /* ========== BORROWED BOOKS - WHITE COLOR UPDATED ========== */
        .borrowed-list {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 20px;
            margin-top: 20px;
        }
        
        .borrowed-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 25px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            background: rgba(255,255,255,0.08);
            border-radius: 15px;
            margin-bottom: 15px;
        }
        
        .borrowed-item:last-child {
            border-bottom: none;
        }
        
        .borrowed-info h4 {
            color: white;
            font-size: 18px;
            margin-bottom: 8px;
            font-weight: 600;
        }
        
        .borrowed-info p {
            color: white;
            font-size: 14px;
            margin: 3px 0;
            opacity: 0.9;
        }
        
        .borrowed-dates {
            margin-top: 10px;
            color: white;
            font-size: 13px;
            opacity: 0.8;
        }
        
        .borrowed-dates i {
            color: #22d3ee;
            margin-right: 5px;
        }
        
        .borrowed-status {
            text-align: right;
            min-width: 200px;
        }
        
        .due-date {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 5px;
            color: #22d3ee;
        }
        
        .due-date.overdue {
            color: #ef4444;
        }
        
        .days-left {
            color: #10b981;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .overdue-warning {
            color: #ef4444;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .return-btn {
            padding: 10px 25px;
            background: #10b981;
            border: none;
            border-radius: 10px;
            color: white;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .return-btn:hover {
            transform: scale(1.05);
            background: #0f9b6e;
        }
        
        .section-title {
            color: white;
            font-size: 24px;
            margin: 40px 0 20px;
            border-left: 5px solid #22d3ee;
            padding-left: 15px;
        }
        
        .no-books {
            color: white;
            text-align: center;
            padding: 60px 40px;
        }
        
        .no-books i {
            font-size: 60px;
            color: #22d3ee;
            margin-bottom: 20px;
        }
        
        .no-books h3 {
            color: white;
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        .no-books p {
            color: white;
            opacity: 0.8;
            margin-bottom: 20px;
        }
        
        .browse-btn {
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 30px;
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .browse-btn:hover {
            transform: scale(1.05);
        }
        
        .search-box {
            width: 100%;
            max-width: 500px;
            margin: 0 auto 30px;
        }
        
        .search-box input {
            width: 100%;
            padding: 15px 20px;
            border: none;
            border-radius: 30px;
            background: rgba(255,255,255,0.1);
            color: white;
            font-size: 16px;
            outline: none;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .search-box input::placeholder {
            color: rgba(255,255,255,0.5);
        }
        
        .search-box input:focus {
            border-color: #22d3ee;
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
        
        .back-btn i {
            margin-right: 8px;
        }
        
        @media (max-width: 768px) {
            .tabs {
                flex-direction: column;
                align-items: stretch;
            }
            
            .borrowed-item {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .borrowed-status {
                text-align: center;
            }
            
            .books-grid {
                grid-template-columns: 1fr;
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
    
    <h1 class="logo">Synergy <span>Hub</span> - Library</h1>
    
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
    
    <h1 class="page-title">📚 Library Books</h1>
    
    <!-- TABS -->
    <div class="tabs">
        <button class="tab-btn <?php echo $active_tab == 'browse' ? 'active' : ''; ?>" onclick="switchTab('browse')">
            <i class="fa-solid fa-magnifying-glass"></i> Browse Books
        </button>
        <button class="tab-btn <?php echo $active_tab == 'borrowed' ? 'active' : ''; ?>" onclick="switchTab('borrowed')">
            <i class="fa-solid fa-bookmark"></i> My Borrowed Books 
            <?php 
            $borrowed_count = mysqli_num_rows($borrowed_result);
            if($borrowed_count > 0): 
            ?>
            <span class="tab-badge"><?php echo $borrowed_count; ?></span>
            <?php endif; ?>
        </button>
    </div>
    
    <!-- SEARCH -->
    <div class="search-box" id="browseTab" style="display: <?php echo $active_tab == 'browse' ? 'block' : 'none'; ?>;">
        <input type="text" id="searchBooks" placeholder="Search by title, author, or category..." onkeyup="searchBooks()">
    </div>
    
    <!-- BOOKS GRID (Browse Tab) -->
    <div id="booksGrid" class="books-grid" style="display: <?php echo $active_tab == 'browse' ? 'grid' : 'none'; ?>;">
        <?php 
        // Reset pointer for books_result
        mysqli_data_seek($books_result, 0);
        while($book = mysqli_fetch_assoc($books_result)): 
        ?>
        <div class="book-card" data-title="<?php echo strtolower($book['title']); ?>" 
             data-author="<?php echo strtolower($book['author']); ?>" 
             data-category="<?php echo strtolower($book['category']); ?>">
            <div class="book-title"><?php echo htmlspecialchars($book['title']); ?></div>
            <div class="book-author">by <?php echo htmlspecialchars($book['author']); ?></div>
            <div class="book-category"><?php echo htmlspecialchars($book['category']); ?></div>
            <div class="book-description"><?php echo htmlspecialchars(substr($book['description'], 0, 100)) . '...'; ?></div>
            <div class="book-stats">
                <span><i class="fa-solid fa-hashtag"></i> ISBN: <?php echo htmlspecialchars($book['isbn'] ?: 'N/A'); ?></span>
                <span><i class="fa-solid fa-copy"></i> Available: <?php echo $book['available']; ?>/<?php echo $book['quantity']; ?></span>
            </div>
            <button class="borrow-btn" onclick="borrowBook(<?php echo $book['book_id']; ?>)"
                <?php echo ($book['available'] <= 0) ? 'disabled' : ''; ?>>
                <i class="fa-solid fa-book"></i> Borrow Book (+5 points)
            </button>
        </div>
        <?php endwhile; ?>
    </div>
    
    <!-- BORROWED BOOKS TAB - WHITE COLOR -->
    <div id="borrowedTab" style="display: <?php echo $active_tab == 'borrowed' ? 'block' : 'none'; ?>;">
        <h2 class="section-title">📖 Books You've Borrowed</h2>
        <div class="borrowed-list">
            <?php 
            // Reset pointer for borrowed_result
            mysqli_data_seek($borrowed_result, 0);
            if(mysqli_num_rows($borrowed_result) > 0): 
            ?>
                <?php while($borrowed = mysqli_fetch_assoc($borrowed_result)): 
                    $overdue = $borrowed['days_overdue'] > 0;
                    $days_left = $borrowed['days_remaining'];
                ?>
                <div class="borrowed-item">
                    <div class="borrowed-info">
                        <h4><?php echo htmlspecialchars($borrowed['title']); ?></h4>
                        <p>by <?php echo htmlspecialchars($borrowed['author']); ?></p>
                        <p><i class="fa-regular fa-folder"></i> <?php echo htmlspecialchars($borrowed['category']); ?></p>
                        <div class="borrowed-dates">
                            <span><i class="fa-regular fa-calendar-check"></i> Borrowed: <?php echo date('M d, Y', strtotime($borrowed['borrow_date'])); ?></span>
                        </div>
                    </div>
                    <div class="borrowed-status">
                        <div class="due-date <?php echo $overdue ? 'overdue' : ''; ?>">
                            <i class="fa-regular fa-calendar"></i> 
                            Due: <?php echo date('M d, Y', strtotime($borrowed['due_date'])); ?>
                        </div>
                        <?php if($overdue): ?>
                            <div class="overdue-warning">
                                <i class="fa-solid fa-exclamation-triangle"></i> 
                                <?php echo $borrowed['days_overdue']; ?> days overdue
                            </div>
                        <?php else: ?>
                            <div class="days-left">
                                <i class="fa-regular fa-clock"></i> 
                                <?php echo $days_left; ?> days left
                            </div>
                        <?php endif; ?>
                        <button class="return-btn" onclick="returnBook(<?php echo $borrowed['borrow_id']; ?>, <?php echo $borrowed['book_id']; ?>)">
                            <i class="fa-solid fa-rotate-left"></i> Return Book (+2 points)
                        </button>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-books">
                    <i class="fa-solid fa-book-open"></i>
                    <h3>No books borrowed yet</h3>
                    <p>Browse available books and borrow your first book today!</p>
                    <button class="browse-btn" onclick="switchTab('browse')">
                        <i class="fa-solid fa-magnifying-glass"></i> Browse Books
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <a href="facility_details.php?id=<?php echo $facility_id; ?>" class="back-btn">
        <i class="fa-solid fa-arrow-left"></i> Back to Facility
    </a>
</div>

<script>
// ==================== SIDEBAR ====================
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

// Tab switching
function switchTab(tab) {
    const tabs = document.querySelectorAll('.tab-btn');
    const browseTab = document.getElementById('browseTab');
    const booksGrid = document.getElementById('booksGrid');
    const borrowedTab = document.getElementById('borrowedTab');
    
    tabs.forEach(t => t.classList.remove('active'));
    
    if(tab === 'browse') {
        tabs[0].classList.add('active');
        browseTab.style.display = 'block';
        booksGrid.style.display = 'grid';
        borrowedTab.style.display = 'none';
        
        // URL update
        const url = new URL(window.location);
        url.searchParams.set('tab', 'browse');
        window.history.pushState({}, '', url);
    } else {
        tabs[1].classList.add('active');
        browseTab.style.display = 'none';
        booksGrid.style.display = 'none';
        borrowedTab.style.display = 'block';
        
        // URL update
        const url = new URL(window.location);
        url.searchParams.set('tab', 'borrowed');
        window.history.pushState({}, '', url);
    }
}

// Search books
function searchBooks() {
    let searchTerm = document.getElementById('searchBooks').value.toLowerCase();
    let books = document.querySelectorAll('.book-card');
    
    books.forEach(book => {
        let title = book.dataset.title;
        let author = book.dataset.author;
        let category = book.dataset.category;
        
        if(title.includes(searchTerm) || author.includes(searchTerm) || category.includes(searchTerm)) {
            book.style.display = 'block';
        } else {
            book.style.display = 'none';
        }
    });
}

// Borrow book
function borrowBook(bookId) {
    if(confirm('Borrow this book? You will get 5 points! (Due in 14 days)')) {
        fetch('borrow_book.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'book_id=' + bookId
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                alert('✅ Book borrowed successfully! Due date: ' + data.due_date + '. You earned 5 points!');
                
                // Update points display
                let pointsSpan = document.getElementById('pointsDisplay');
                let currentPoints = parseInt(pointsSpan.textContent);
                pointsSpan.textContent = currentPoints + 5;
                document.getElementById('currentPoints').textContent = pointsSpan.textContent;
                
                // Animate points
                document.querySelector('.points').classList.add('active');
                setTimeout(() => {
                    document.querySelector('.points').classList.remove('active');
                }, 500);
                
                location.reload();
            } else {
                alert('❌ Error: ' + data.message);
            }
        })
        .catch(error => {
            alert('❌ Error processing request');
        });
    }
}

// Return book
function returnBook(borrowId, bookId) {
    if(confirm('Return this book? You will get 2 points!')) {
        fetch('return_book.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'borrow_id=' + borrowId + '&book_id=' + bookId
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                alert('✅ Book returned successfully! You earned 2 points!');
                
                // Update points display
                let pointsSpan = document.getElementById('pointsDisplay');
                let currentPoints = parseInt(pointsSpan.textContent);
                pointsSpan.textContent = currentPoints + 2;
                document.getElementById('currentPoints').textContent = pointsSpan.textContent;
                
                // Animate points
                document.querySelector('.points').classList.add('active');
                setTimeout(() => {
                    document.querySelector('.points').classList.remove('active');
                }, 500);
                
                location.reload();
            } else {
                alert('❌ Error: ' + data.message);
            }
        })
        .catch(error => {
            alert('❌ Error processing request');
        });
    }
}
</script>

</body>
</html>