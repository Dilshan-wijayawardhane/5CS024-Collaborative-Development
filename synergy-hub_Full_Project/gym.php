<?php
require_once 'config.php';
require_once 'functions.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$facility_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'dashboard';

// Get gym status
$gym_sql = "SELECT * FROM gym_status ORDER BY id DESC LIMIT 1";
$gym_result = mysqli_query($conn, $gym_sql);
$gym_status = mysqli_fetch_assoc($gym_result);

// Get today's operating hours
$today = date('l');
$hours_sql = "SELECT * FROM gym_operating_hours WHERE day_of_week = ?";
$hours_stmt = mysqli_prepare($conn, $hours_sql);
mysqli_stmt_bind_param($hours_stmt, "s", $today);
mysqli_stmt_execute($hours_stmt);
$hours_result = mysqli_stmt_get_result($hours_stmt);
$today_hours = mysqli_fetch_assoc($hours_result);

// Get active announcements
$announcements_sql = "SELECT * FROM gym_announcements 
                      WHERE is_active = 1 
                      AND (expires_at IS NULL OR expires_at > NOW())
                      ORDER BY created_at DESC";
$announcements_result = mysqli_query($conn, $announcements_sql);

// Get user badges
$badges_sql = "SELECT * FROM user_badges WHERE user_id = ? ORDER BY earned_at DESC";
$badges_stmt = mysqli_prepare($conn, $badges_sql);
mysqli_stmt_bind_param($badges_stmt, "i", $user_id);
mysqli_stmt_execute($badges_stmt);
$badges_result = mysqli_stmt_get_result($badges_stmt);

// Get user streak
$streak_sql = "SELECT * FROM user_streaks WHERE user_id = ? AND streak_type = 'attendance'";
$streak_stmt = mysqli_prepare($conn, $streak_sql);
mysqli_stmt_bind_param($streak_stmt, "i", $user_id);
mysqli_stmt_execute($streak_stmt);
$streak_result = mysqli_stmt_get_result($streak_stmt);
$user_streak = mysqli_fetch_assoc($streak_result);

// Get equipment with categories
$equip_sql = "SELECT e.*, c.category_name, c.icon as category_icon 
              FROM gym_equipment e
              LEFT JOIN equipment_categories c ON e.category_id = c.category_id
              ORDER BY c.display_order, e.name";
$equip_result = mysqli_query($conn, $equip_sql);

// Get equipment categories for filter
$categories_sql = "SELECT * FROM equipment_categories ORDER BY display_order";
$categories_result = mysqli_query($conn, $categories_sql);

// Get classes with waitlist info
$classes_sql = "SELECT fc.*, 
                (SELECT COUNT(*) FROM class_waitlist WHERE class_id = fc.class_id AND status = 'waiting') as waitlist_count
                FROM fitness_classes fc 
                WHERE fc.time >= CURTIME() OR DATE(fc.created_at) = CURDATE()
                ORDER BY fc.time";
$classes_result = mysqli_query($conn, $classes_sql);

// Get user's class bookings with check-in info
$bookings_sql = "SELECT cb.*, fc.name as class_name, fc.time, fc.instructor, fc.location,
                 ca.check_in_time, ca.bonus_points_awarded,
                 cr.rating, cr.comment as review_comment
                 FROM class_bookings cb
                 JOIN fitness_classes fc ON cb.class_id = fc.class_id
                 LEFT JOIN class_attendance ca ON cb.booking_id = ca.booking_id
                 LEFT JOIN class_reviews cr ON cb.booking_id = cr.booking_id
                 WHERE cb.user_id = ? AND cb.status = 'booked' AND fc.time >= NOW()
                 ORDER BY fc.time ASC";
$bookings_stmt = mysqli_prepare($conn, $bookings_sql);
mysqli_stmt_bind_param($bookings_stmt, "i", $user_id);
mysqli_stmt_execute($bookings_stmt);
$bookings_result = mysqli_stmt_get_result($bookings_stmt);

// Get user points
$user_sql = "SELECT PointsBalance, Name FROM Users WHERE UserID = ?";
$user_stmt = mysqli_prepare($conn, $user_sql);
mysqli_stmt_bind_param($user_stmt, "i", $user_id);
mysqli_stmt_execute($user_stmt);
$user_result = mysqli_stmt_get_result($user_stmt);
$user = mysqli_fetch_assoc($user_result);

// Get gym settings
$settings_sql = "SELECT * FROM gym_settings";
$settings_result = mysqli_query($conn, $settings_sql);
$gym_settings = [];
while($setting = mysqli_fetch_assoc($settings_result)) {
    $gym_settings[$setting['setting_key']] = $setting['setting_value'];
}

// Get user's class count today
$today_classes_sql = "SELECT COUNT(*) as count FROM class_bookings cb
                      JOIN fitness_classes fc ON cb.class_id = fc.class_id
                      WHERE cb.user_id = ? AND DATE(fc.time) = CURDATE() AND cb.status = 'booked'";
$today_classes_stmt = mysqli_prepare($conn, $today_classes_sql);
mysqli_stmt_bind_param($today_classes_stmt, "i", $user_id);
mysqli_stmt_execute($today_classes_stmt);
$today_classes_result = mysqli_stmt_get_result($today_classes_stmt);
$today_classes_count = mysqli_fetch_assoc($today_classes_result)['count'];
$max_classes_per_day = $gym_settings['max_classes_per_day'] ?? 3;
$can_book_more = $today_classes_count < $max_classes_per_day;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gym Center - Synergy Hub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif; }
        body { min-height: 100vh; position: relative; background: #0f172a; }
        
        /* Glassmorphism Background */
        .bg { position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: -1; }
        .bg::before { content: ""; position: absolute; inset: 0; background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%); }
        
        /* Navbar */
        .navbar { display: flex; justify-content: space-between; align-items: center; padding: 16px 32px; background: rgba(0,0,0,0.3); backdrop-filter: blur(10px); position: sticky; top: 0; z-index: 100; }
        .logo { font-size: 24px; font-weight: 700; color: white; }
        .logo span { color: #22d3ee; }
        .icons { display: flex; gap: 20px; align-items: center; }
        .menu-btn { color: white; font-size: 24px; cursor: pointer; }
        .points { display: flex; align-items: center; gap: 6px; font-weight: 600; padding: 8px 15px; border-radius: 20px; background: rgba(255,255,255,0.15); backdrop-filter: blur(10px); color: white; }
        .home-link { color: white; font-size: 20px; text-decoration: none; }
        
        /* Sidebar */
        .sidebar { position: fixed; left: -280px; top: 0; width: 280px; height: 100%; background: linear-gradient(180deg, #1e2b3c 0%, #0d1a24 100%); backdrop-filter: blur(10px); transition: 0.4s; z-index: 9999; box-shadow: 4px 0 30px rgba(0,0,0,0.3); overflow-y: auto; }
        .sidebar.active { left: 0; }
        .sidebar-header { padding: 25px 20px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-header h2 { color: white; font-size: 24px; background: linear-gradient(135deg, #fff, #a5b4fc); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .sidebar-user { padding: 15px 20px; background: rgba(255,255,255,0.03); margin: 0 15px 20px; border-radius: 16px; display: flex; align-items: center; gap: 12px; }
        .sidebar-user-avatar { width: 45px; height: 45px; border-radius: 12px; background: linear-gradient(135deg, #667eea, #764ba2); display: flex; align-items: center; justify-content: center; font-size: 20px; color: white; }
        .sidebar-user-info h4 { color: white; font-size: 15px; }
        .sidebar-user-info p { color: #94a3b8; font-size: 12px; }
        .sidebar-nav { list-style: none; }
        .sidebar-nav-item { margin: 4px 12px; }
        .sidebar-nav-link { display: flex; align-items: center; padding: 12px 18px; color: #b8c7de; text-decoration: none; border-radius: 12px; transition: all 0.3s; gap: 12px; }
        .sidebar-nav-link:hover { background: rgba(168,192,255,0.1); color: white; transform: translateX(5px); }
        .sidebar-nav-link.active { background: linear-gradient(90deg, rgba(168,192,255,0.15), rgba(168,192,255,0.05)); color: white; border-left: 3px solid #a5b4fc; }
        .sidebar-badge { background: #ef4444; color: white; font-size: 10px; padding: 2px 6px; border-radius: 30px; margin-left: auto; }
        .sidebar-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9998; display: none; }
        .sidebar-overlay.active { display: block; }
        
        /* Main Container */
        .container { padding: 30px; max-width: 1400px; margin: 0 auto; }
        .page-title { color: white; font-size: 32px; margin-bottom: 30px; }
        
        /* Dashboard Stats Grid */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); border-radius: 20px; padding: 25px; border: 1px solid rgba(255,255,255,0.2); transition: all 0.3s; }
        .stat-card:hover { transform: translateY(-5px); background: rgba(255,255,255,0.15); }
        .stat-icon { font-size: 40px; color: #22d3ee; margin-bottom: 15px; }
        .stat-value { font-size: 32px; font-weight: 700; color: white; }
        .stat-label { color: rgba(255,255,255,0.7); font-size: 14px; margin-top: 5px; }
        
        /* Announcements */
        .announcement-card { background: linear-gradient(135deg, #667eea20, #764ba220); border-left: 4px solid #22d3ee; border-radius: 12px; padding: 15px 20px; margin-bottom: 15px; }
        .announcement-title { color: white; font-weight: 600; margin-bottom: 5px; }
        .announcement-message { color: rgba(255,255,255,0.8); font-size: 14px; }
        
        /* Operating Hours */
        .hours-card { background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); border-radius: 16px; padding: 20px; text-align: center; }
        .hours-status { display: inline-block; padding: 5px 15px; border-radius: 30px; font-size: 14px; font-weight: 600; margin-top: 10px; }
        .status-open { background: #10b981; color: white; }
        .status-closed { background: #ef4444; color: white; }
        
        /* Tabs */
        .gym-tabs { display: flex; gap: 10px; margin-bottom: 30px; border-bottom: 2px solid rgba(255,255,255,0.1); padding-bottom: 10px; flex-wrap: wrap; }
        .gym-tab { padding: 12px 24px; background: none; border: none; color: #94a3b8; cursor: pointer; font-size: 16px; font-weight: 500; border-radius: 10px; transition: all 0.3s; text-decoration: none; display: inline-block; }
        .gym-tab:hover { background: rgba(255,255,255,0.1); color: white; }
        .gym-tab.active { background: linear-gradient(135deg, #667eea, #764ba2); color: white; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        
        /* Equipment Grid */
        .equipment-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 25px; }
        .equipment-card { background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); border-radius: 20px; padding: 20px; border: 1px solid rgba(255,255,255,0.2); transition: all 0.3s; }
        .equipment-card:hover { transform: translateY(-5px); background: rgba(255,255,255,0.15); }
        .equipment-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .equipment-icon { font-size: 48px; color: #22d3ee; }
        .availability-badge { padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .available { background: #10b981; color: white; }
        .maintenance { background: #f59e0b; color: white; }
        .unavailable { background: #ef4444; color: white; }
        .equipment-name { color: white; font-size: 18px; font-weight: 600; margin-bottom: 10px; }
        .equipment-category { color: #22d3ee; font-size: 13px; margin-bottom: 10px; }
        .equipment-stats { display: flex; justify-content: space-between; color: rgba(255,255,255,0.7); font-size: 13px; margin: 15px 0; }
        .report-btn { background: rgba(255,255,255,0.1); border: none; color: white; padding: 8px; border-radius: 8px; cursor: pointer; width: 100%; transition: all 0.3s; }
        .report-btn:hover { background: #ef4444; }
        
        /* Class Cards */
        .class-card { background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); border-radius: 20px; padding: 25px; margin-bottom: 20px; border: 1px solid rgba(255,255,255,0.2); transition: all 0.3s; }
        .class-card:hover { transform: translateX(5px); background: rgba(255,255,255,0.15); }
        .class-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-wrap: wrap; gap: 10px; }
        .class-name { font-size: 20px; font-weight: 600; color: white; }
        .class-time { background: #22d3ee20; padding: 5px 12px; border-radius: 20px; color: #22d3ee; font-size: 14px; }
        .class-instructor { color: rgba(255,255,255,0.7); margin: 10px 0; display: flex; align-items: center; gap: 10px; }
        .class-stats { display: flex; gap: 20px; margin: 15px 0; color: rgba(255,255,255,0.7); font-size: 14px; }
        .spots-left { color: #22d3ee; font-weight: 600; }
        .waitlist-badge { background: #f59e0b; color: white; padding: 2px 8px; border-radius: 20px; font-size: 11px; }
        .join-btn { padding: 12px 25px; background: linear-gradient(135deg, #667eea, #764ba2); border: none; border-radius: 12px; color: white; font-weight: 600; cursor: pointer; transition: all 0.3s; }
        .join-btn:hover:not(:disabled) { transform: scale(1.02); }
        .join-btn:disabled { opacity: 0.5; cursor: not-allowed; }
        .waitlist-btn { background: #f59e0b; }
        
        /* Ratings */
        .stars { color: #fbbf24; font-size: 14px; margin-top: 10px; }
        .review-input { width: 100%; padding: 10px; border-radius: 8px; border: none; margin-top: 10px; background: rgba(255,255,255,0.1); color: white; }
        
        /* Badges */
        .badges-container { display: flex; gap: 15px; flex-wrap: wrap; margin-top: 20px; }
        .badge-item { background: rgba(255,255,255,0.1); border-radius: 50px; padding: 8px 20px; display: flex; align-items: center; gap: 10px; color: white; font-size: 14px; }
        .badge-item i { color: #fbbf24; font-size: 18px; }
        
        /* QR Code Modal */
        .modal { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 10000; align-items: center; justify-content: center; }
        .modal.show { display: flex; }
        .modal-content { background: white; border-radius: 20px; padding: 30px; max-width: 400px; width: 90%; text-align: center; }
        .qr-code { padding: 20px; background: white; border-radius: 10px; margin: 20px 0; }
        
        .back-btn { display: inline-block; margin-top: 30px; color: white; text-decoration: none; padding: 10px 20px; background: rgba(255,255,255,0.1); border-radius: 30px; }
        
        @media (max-width: 768px) {
            .stats-grid { grid-template-columns: 1fr; }
            .class-header { flex-direction: column; align-items: flex-start; }
            .gym-tabs { flex-direction: column; }
            .equipment-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<div class="bg"></div>

<!-- Sidebar -->
<div id="sidebar" class="sidebar">
    <div class="sidebar-header">
        <h2>Synergy Hub</h2>
        <p><i class="fa-solid fa-circle"></i> Connect · Collaborate · Create</p>
    </div>
    <div class="sidebar-user">
        <div class="sidebar-user-avatar"><i class="fa-solid fa-user"></i></div>
        <div class="sidebar-user-info">
            <h4><?php echo htmlspecialchars($user['Name']); ?></h4>
            <p><i class="fa-solid fa-star"></i> <?php echo $user['PointsBalance']; ?> points</p>
        </div>
    </div>
    <ul class="sidebar-nav">
        <li class="sidebar-nav-item"><a href="index.php" class="sidebar-nav-link"><i class="fa-solid fa-home"></i><span>Home</span></a></li>
        <li class="sidebar-nav-item"><a href="facilities.php" class="sidebar-nav-link"><i class="fa-solid fa-building"></i><span>Facilities</span></a></li>
        <li class="sidebar-nav-item"><a href="gym.php" class="sidebar-nav-link active"><i class="fa-solid fa-dumbbell"></i><span>Gym</span></a></li>
        <li class="sidebar-nav-item"><a href="clubs.php" class="sidebar-nav-link"><i class="fa-solid fa-users"></i><span>Club Hub</span></a></li>
    </ul>
    <div class="sidebar-footer">
        <div class="sidebar-footer-links"><a href="#"><i class="fa-regular fa-circle-question"></i> Help</a></div>
        <div class="sidebar-copyright">© 2025 Synergy Hub</div>
    </div>
</div>
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<!-- Navbar -->
<header class="navbar">
    <div class="menu-btn" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></div>
    <h1 class="logo">Synergy <span>Hub</span> - Gym Center</h1>
    <div class="icons">
        <div class="points"><i class="fa-solid fa-star"></i><span><?php echo $user['PointsBalance']; ?></span></div>
        <a href="facilities.php" class="home-link"><i class="fa-solid fa-arrow-left"></i></a>
    </div>
</header>

<div class="container">
    <!-- Dashboard Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon"><i class="fa-solid fa-dumbbell"></i></div>
            <div class="stat-value"><?php echo $gym_status['status'] ?? 'Open'; ?></div>
            <div class="stat-label">Gym Status</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fa-regular fa-clock"></i></div>
            <div class="stat-value"><?php echo $today_hours ? date('h:i A', strtotime($today_hours['open_time'])) . ' - ' . date('h:i A', strtotime($today_hours['close_time'])) : 'Check Schedule'; ?></div>
            <div class="stat-label">Today's Hours (<?php echo $today; ?>)</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fa-solid fa-star"></i></div>
            <div class="stat-value"><?php echo $user['PointsBalance']; ?></div>
            <div class="stat-label">Your Points</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fa-solid fa-fire"></i></div>
            <div class="stat-value"><?php echo $user_streak['current_streak'] ?? 0; ?></div>
            <div class="stat-label">Day Streak 🔥</div>
        </div>
    </div>
    
    <!-- Announcements -->
    <?php if(mysqli_num_rows($announcements_result) > 0): ?>
    <div style="margin-bottom: 30px;">
        <?php while($announcement = mysqli_fetch_assoc($announcements_result)): ?>
        <div class="announcement-card">
            <div class="announcement-title"><i class="fa-solid fa-bullhorn"></i> <?php echo htmlspecialchars($announcement['title']); ?></div>
            <div class="announcement-message"><?php echo nl2br(htmlspecialchars($announcement['message'])); ?></div>
        </div>
        <?php endwhile; ?>
    </div>
    <?php endif; ?>
    
    <!-- User Badges -->
    <?php if(mysqli_num_rows($badges_result) > 0): ?>
    <div class="badges-container">
        <?php while($badge = mysqli_fetch_assoc($badges_result)): ?>
        <div class="badge-item">
            <i class="fa-solid <?php echo $badge['badge_icon']; ?>"></i>
            <span><?php echo htmlspecialchars($badge['badge_name']); ?></span>
        </div>
        <?php endwhile; ?>
    </div>
    <?php endif; ?>
    
    <!-- Tabs -->
    <div class="gym-tabs">
        <a href="?tab=dashboard" class="gym-tab <?php echo $active_tab == 'dashboard' ? 'active' : ''; ?>"><i class="fa-solid fa-chart-line"></i> Dashboard</a>
        <a href="?tab=equipment" class="gym-tab <?php echo $active_tab == 'equipment' ? 'active' : ''; ?>"><i class="fa-solid fa-dumbbell"></i> Equipment</a>
        <a href="?tab=classes" class="gym-tab <?php echo $active_tab == 'classes' ? 'active' : ''; ?>"><i class="fa-solid fa-people-group"></i> Classes</a>
        <a href="?tab=bookings" class="gym-tab <?php echo $active_tab == 'bookings' ? 'active' : ''; ?>"><i class="fa-regular fa-calendar-check"></i> My Bookings</a>
    </div>
    
    <!-- Dashboard Tab -->
    <div id="tab-dashboard" class="tab-content <?php echo $active_tab == 'dashboard' ? 'active' : ''; ?>">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fa-solid fa-chart-simple"></i></div>
                <div class="stat-value" id="activeUsers">Loading...</div>
                <div class="stat-label">Currently Active</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fa-solid fa-calendar-week"></i></div>
                <div class="stat-value" id="todayClasses">0</div>
                <div class="stat-label">Classes Today</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fa-solid fa-trophy"></i></div>
                <div class="stat-value" id="pointsLeader">-</div>
                <div class="stat-label">Points Leader</div>
            </div>
        </div>
        
        <!-- Peak Hours Chart -->
        <div class="stat-card" style="margin-top: 20px;">
            <h3 style="color: white; margin-bottom: 20px;"><i class="fa-solid fa-chart-line"></i> Peak Gym Hours</h3>
            <canvas id="peakHoursChart" height="200"></canvas>
        </div>
    </div>
    
    <!-- Equipment Tab -->
    <div id="tab-equipment" class="tab-content <?php echo $active_tab == 'equipment' ? 'active' : ''; ?>">
        <!-- Category Filters -->
        <div class="gym-tabs" style="margin-bottom: 20px;">
            <button class="gym-tab active" onclick="filterEquipment('all')">All</button>
            <?php 
            mysqli_data_seek($categories_result, 0);
            while($cat = mysqli_fetch_assoc($categories_result)): 
            ?>
            <button class="gym-tab" onclick="filterEquipment('<?php echo $cat['category_id']; ?>')"><?php echo htmlspecialchars($cat['category_name']); ?></button>
            <?php endwhile; ?>
        </div>
        
        <div class="equipment-grid" id="equipmentGrid">
            <?php while($item = mysqli_fetch_assoc($equip_result)): 
                $is_available = ($item['available'] > 0 && !$item['maintenance_mode']);
            ?>
            <div class="equipment-card" data-category="<?php echo $item['category_id']; ?>">
                <div class="equipment-header">
                    <div class="equipment-icon"><i class="fa-solid <?php echo $item['image_icon']; ?>"></i></div>
                    <span class="availability-badge <?php echo $is_available ? 'available' : ($item['maintenance_mode'] ? 'maintenance' : 'unavailable'); ?>">
                        <?php echo $is_available ? 'Available' : ($item['maintenance_mode'] ? 'Maintenance' : 'Out of Stock'); ?>
                    </span>
                </div>
                <div class="equipment-name"><?php echo htmlspecialchars($item['name']); ?></div>
                <div class="equipment-category"><i class="fa-solid <?php echo $item['category_icon'] ?? 'fa-tag'; ?>"></i> <?php echo htmlspecialchars($item['category_name'] ?? 'Uncategorized'); ?></div>
                <div class="equipment-stats">
                    <span><i class="fa-solid fa-cubes"></i> Total: <?php echo $item['quantity']; ?></span>
                    <span><i class="fa-solid fa-check-circle"></i> Available: <?php echo $item['available']; ?></span>
                </div>
                <button class="report-btn" onclick="reportIssue(<?php echo $item['equipment_id']; ?>, '<?php echo htmlspecialchars($item['name']); ?>')">
                    <i class="fa-solid fa-flag"></i> Report Issue
                </button>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
    
    <!-- Classes Tab -->
    <div id="tab-classes" class="tab-content <?php echo $active_tab == 'classes' ? 'active' : ''; ?>">
        <?php if(!$can_book_more): ?>
        <div class="announcement-card" style="background: #f59e0b20; border-left-color: #f59e0b;">
            <div class="announcement-title"><i class="fa-solid fa-info-circle"></i> Daily Limit Reached</div>
            <div class="announcement-message">You've reached the maximum of <?php echo $max_classes_per_day; ?> classes per day.</div>
        </div>
        <?php endif; ?>
        
        <?php while($class = mysqli_fetch_assoc($classes_result)): 
            $spots = $class['capacity'] - $class['booked'];
            $has_waitlist = $class['waitlist_count'] > 0;
            $is_full = $spots <= 0;
            $check_joined_sql = "SELECT * FROM class_bookings WHERE user_id = ? AND class_id = ? AND status = 'booked'";
            $check_joined_stmt = mysqli_prepare($conn, $check_joined_sql);
            mysqli_stmt_bind_param($check_joined_stmt, "ii", $user_id, $class['class_id']);
            mysqli_stmt_execute($check_joined_stmt);
            $already_joined = mysqli_num_rows(mysqli_stmt_get_result($check_joined_stmt)) > 0;
        ?>
        <div class="class-card" id="class-<?php echo $class['class_id']; ?>">
            <div class="class-header">
                <span class="class-name"><?php echo htmlspecialchars($class['name']); ?></span>
                <span class="class-time"><i class="fa-regular fa-clock"></i> <?php echo $class['time']; ?></span>
            </div>
            <div class="class-instructor">
                <i class="fa-solid fa-user"></i> <?php echo htmlspecialchars($class['instructor']); ?>
                <?php if($class['location']): ?> • <i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($class['location']); ?><?php endif; ?>
            </div>
            <div class="class-stats">
                <span><i class="fa-solid fa-users"></i> <?php echo $class['booked']; ?>/<?php echo $class['capacity']; ?> booked</span>
                <span class="spots-left"><i class="fa-solid fa-chair"></i> <?php echo $spots; ?> spots left</span>
                <?php if($has_waitlist): ?>
                <span class="waitlist-badge"><i class="fa-solid fa-clock"></i> <?php echo $class['waitlist_count']; ?> on waitlist</span>
                <?php endif; ?>
            </div>
            <?php if($already_joined): ?>
                <button class="join-btn joined" disabled style="background: #10b981;"><i class="fa-solid fa-check"></i> Already Booked</button>
            <?php elseif($is_full): ?>
                <button class="join-btn waitlist-btn" onclick="joinWaitlist(<?php echo $class['class_id']; ?>)">
                    <i class="fa-solid fa-clock"></i> Join Waitlist
                </button>
            <?php elseif(!$can_book_more): ?>
                <button class="join-btn" disabled><i class="fa-solid fa-ban"></i> Daily Limit Reached</button>
            <?php else: ?>
                <button class="join-btn" onclick="joinClass(<?php echo $class['class_id']; ?>)">
                    <i class="fa-solid fa-plus"></i> Book Class
                </button>
            <?php endif; ?>
        </div>
        <?php endwhile; ?>
    </div>
    
    <!-- Bookings Tab -->
    <div id="tab-bookings" class="tab-content <?php echo $active_tab == 'bookings' ? 'active' : ''; ?>">
        <?php if(mysqli_num_rows($bookings_result) > 0): ?>
            <?php while($booking = mysqli_fetch_assoc($bookings_result)): 
                $can_cancel = strtotime($booking['time']) > (time() + ($gym_settings['cancellation_window_hours'] ?? 2) * 3600);
                $has_checked_in = !is_null($booking['check_in_time']);
                $has_reviewed = !is_null($booking['rating']);
            ?>
            <div class="class-card" id="booking-<?php echo $booking['booking_id']; ?>">
                <div class="class-header">
                    <span class="class-name"><?php echo htmlspecialchars($booking['class_name']); ?></span>
                    <span class="class-time"><i class="fa-regular fa-clock"></i> <?php echo $booking['time']; ?></span>
                </div>
                <div class="class-instructor">
                    <i class="fa-solid fa-user"></i> <?php echo htmlspecialchars($booking['instructor']); ?>
                    <?php if($booking['location']): ?> • <i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($booking['location']); ?><?php endif; ?>
                </div>
                <?php if($has_checked_in): ?>
                <div style="background: #10b98120; padding: 10px; border-radius: 10px; margin: 10px 0;">
                    <i class="fa-solid fa-check-circle"></i> Checked in at <?php echo date('h:i A', strtotime($booking['check_in_time'])); ?>
                    <?php if($booking['bonus_points_awarded'] > 0): ?>
                    • +<?php echo $booking['bonus_points_awarded']; ?> bonus points
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div style="margin: 15px 0;">
                    <button class="join-btn" style="background: #22d3ee;" onclick="showQRCode(<?php echo $booking['booking_id']; ?>)">
                        <i class="fa-solid fa-qrcode"></i> Show Check-in QR
                    </button>
                </div>
                <?php endif; ?>
                
                <?php if($has_reviewed): ?>
                <div class="stars">
                    <?php for($i = 1; $i <= 5; $i++): ?>
                        <i class="fa-solid fa-star<?php echo $i <= $booking['rating'] ? '' : '-o'; ?>"></i>
                    <?php endfor; ?>
                    <?php if($booking['review_comment']): ?>
                    <div style="color: rgba(255,255,255,0.7); font-size: 13px; margin-top: 5px;"><?php echo htmlspecialchars($booking['review_comment']); ?></div>
                    <?php endif; ?>
                </div>
                <?php elseif($has_checked_in && !$has_reviewed): ?>
                <div class="review-section" data-booking-id="<?php echo $booking['booking_id']; ?>">
                    <div class="stars" style="margin-bottom: 10px;">
                        <i class="fa-regular fa-star" data-rating="1" onclick="setRating(this, 1)"></i>
                        <i class="fa-regular fa-star" data-rating="2" onclick="setRating(this, 2)"></i>
                        <i class="fa-regular fa-star" data-rating="3" onclick="setRating(this, 3)"></i>
                        <i class="fa-regular fa-star" data-rating="4" onclick="setRating(this, 4)"></i>
                        <i class="fa-regular fa-star" data-rating="5" onclick="setRating(this, 5)"></i>
                    </div>
                    <textarea class="review-input" placeholder="Share your experience..." rows="2"></textarea>
                    <button class="join-btn" style="margin-top: 10px; padding: 8px 20px;" onclick="submitReview(<?php echo $booking['booking_id']; ?>, this)">Submit Review</button>
                </div>
                <?php endif; ?>
                
                <div style="display: flex; gap: 10px; margin-top: 15px;">
                    <?php if($can_cancel && !$has_checked_in): ?>
                    <button class="cancel-btn" onclick="cancelBooking(<?php echo $booking['booking_id']; ?>)">
                        <i class="fa-solid fa-xmark"></i> Cancel Booking
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="no-bookings"><i class="fa-regular fa-calendar-xmark"></i><br>No upcoming bookings</div>
        <?php endif; ?>
    </div>
    
    <a href="facilities.php" class="back-btn"><i class="fa-solid fa-arrow-left"></i> Back to Facilities</a>
</div>

<!-- QR Code Modal -->
<div class="modal" id="qrModal">
    <div class="modal-content">
        <h3><i class="fa-solid fa-qrcode"></i> Check-in QR Code</h3>
        <div class="qr-code" id="qrCodeContainer"></div>
        <p>Show this QR code to the gym staff to check in</p>
        <button class="join-btn" onclick="closeModal()">Close</button>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
// ==================== SIDEBAR ====================
function toggleSidebar() {
    const sidebar = document.querySelector(".sidebar");
    const overlay = document.getElementById("sidebarOverlay");
    if(sidebar.style.left === "0px") {
        sidebar.style.left = "-280px";
        overlay.classList.remove("active");
    } else {
        sidebar.style.left = "0px";
        overlay.classList.add("active");
    }
}

// ==================== EQUIPMENT FILTER ====================
function filterEquipment(categoryId) {
    const cards = document.querySelectorAll('#equipmentGrid .equipment-card');
    cards.forEach(card => {
        if(categoryId === 'all' || card.dataset.category == categoryId) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
    
    // Update active tab styling
    document.querySelectorAll('#tab-equipment .gym-tab').forEach(btn => btn.classList.remove('active'));
    event.currentTarget.classList.add('active');
}

// ==================== REPORT ISSUE ====================
function reportIssue(equipmentId, equipmentName) {
    const issue = prompt(`Report issue with ${equipmentName}:\n\nDescribe the problem:`);
    if(issue && issue.trim()) {
        fetch('report_equipment_issue.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `equipment_id=${equipmentId}&issue=${encodeURIComponent(issue)}`
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                alert('✅ Issue reported successfully! Staff will address it soon.');
            } else {
                alert('❌ Error: ' + data.message);
            }
        });
    }
}

// ==================== JOIN CLASS ====================
function joinClass(classId) {
    fetch('join_class.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'class_id=' + classId
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            alert('✅ Successfully booked the class!');
            location.reload();
        } else {
            alert('❌ Error: ' + data.message);
        }
    });
}

// ==================== JOIN WAITLIST ====================
function joinWaitlist(classId) {
    if(confirm('Class is full. Join waitlist? You will be notified when a spot opens.')) {
        fetch('join_waitlist.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'class_id=' + classId
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                alert('✅ Added to waitlist! You will be notified when a spot opens.');
                location.reload();
            } else {
                alert('❌ Error: ' + data.message);
            }
        });
    }
}

// ==================== CANCEL BOOKING ====================
function cancelBooking(bookingId) {
    if(confirm('Cancel this booking?')) {
        fetch('cancel_booking.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'booking_id=' + bookingId
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                alert('✅ Booking cancelled');
                location.reload();
            } else {
                alert('❌ Error: ' + data.message);
            }
        });
    }
}

// ==================== QR CODE CHECK-IN ====================
let qrCode = null;

function showQRCode(bookingId) {
    fetch('generate_qr.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'booking_id=' + bookingId
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            document.getElementById('qrModal').classList.add('show');
            document.getElementById('qrCodeContainer').innerHTML = '';
            qrCode = new QRCode(document.getElementById('qrCodeContainer'), {
                text: data.qr_data,
                width: 200,
                height: 200
            });
        } else {
            alert('Error generating QR code');
        }
    });
}

// ==================== SUBMIT REVIEW ====================
let selectedRating = 0;

function setRating(element, rating) {
    selectedRating = rating;
    const stars = element.parentElement.querySelectorAll('.fa-star');
    stars.forEach((star, index) => {
        if(index < rating) {
            star.className = 'fa-solid fa-star';
        } else {
            star.className = 'fa-regular fa-star';
        }
    });
}

function submitReview(bookingId, button) {
    const reviewDiv = button.closest('.review-section');
    const comment = reviewDiv.querySelector('.review-input').value;
    
    if(selectedRating === 0) {
        alert('Please select a rating');
        return;
    }
    
    fetch('submit_review.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `booking_id=${bookingId}&rating=${selectedRating}&comment=${encodeURIComponent(comment)}`
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            alert('Thank you for your review!');
            location.reload();
        } else {
            alert('Error submitting review');
        }
    });
}

// ==================== MODAL ====================
function closeModal() {
    document.getElementById('qrModal').classList.remove('show');
}

// ==================== DASHBOARD STATS ====================
function loadDashboardStats() {
    fetch('get_gym_stats.php')
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                document.getElementById('activeUsers').textContent = data.active_users;
                document.getElementById('todayClasses').textContent = data.today_classes;
                document.getElementById('pointsLeader').textContent = data.points_leader;
                
                // Peak Hours Chart
                const ctx = document.getElementById('peakHoursChart')?.getContext('2d');
                if(ctx) {
                    new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: data.peak_hours.labels,
                            datasets: [{
                                label: 'Average Attendance',
                                data: data.peak_hours.values,
                                backgroundColor: '#667eea',
                                borderRadius: 8
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: true,
                            plugins: { legend: { labels: { color: 'white' } } },
                            scales: { y: { ticks: { color: 'white' } }, x: { ticks: { color: 'white' } } }
                        }
                    });
                }
            }
        });
}

// Close modals when clicking outside
window.onclick = function(event) {
    if(event.target.classList.contains('modal')) {
        event.target.classList.remove('show');
    }
}

loadDashboardStats();
</script>
</body>
</html>