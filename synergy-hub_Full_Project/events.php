<?php
require_once 'config.php';
require_once 'functions.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle filters
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'date';
$event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;

// Build query
$where_conditions = ["Status IN ('Upcoming', 'Ongoing')"];
if ($category_filter) {
    $where_conditions[] = "Category = '$category_filter'";
}
if ($search) {
    $where_conditions[] = "(Title LIKE '%$search%' OR Description LIKE '%$search%' OR Location LIKE '%$search%')";
}
$where_sql = implode(" AND ", $where_conditions);

$order_by = "StartTime ASC";
if ($sort == 'popular') {
    $order_by = "like_count DESC, StartTime ASC";
} elseif ($sort == 'price_asc') {
    $order_by = "ticket_price ASC, StartTime ASC";
} elseif ($sort == 'price_desc') {
    $order_by = "ticket_price DESC, StartTime ASC";
}

// Get events
$events_sql = "SELECT e.*, 
               (SELECT COUNT(*) FROM EventBookings WHERE EventID = e.EventID AND Status IN ('Confirmed', 'Used')) as booked_count,
               (SELECT COUNT(*) FROM EventLikes WHERE EventID = e.EventID) as like_count
               FROM Events e 
               WHERE $where_sql 
               ORDER BY $order_by";
$events_result = mysqli_query($conn, $events_sql);

// Get user's liked events
$user_likes = [];
$likes_sql = "SELECT EventID FROM EventLikes WHERE UserID = ?";
$likes_stmt = mysqli_prepare($conn, $likes_sql);
mysqli_stmt_bind_param($likes_stmt, "i", $user_id);
mysqli_stmt_execute($likes_stmt);
$likes_result = mysqli_stmt_get_result($likes_stmt);
while($like = mysqli_fetch_assoc($likes_result)) {
    $user_likes[] = $like['EventID'];
}

// Get user points and name
$user_sql = "SELECT PointsBalance, Name FROM Users WHERE UserID = ?";
$user_stmt = mysqli_prepare($conn, $user_sql);
mysqli_stmt_bind_param($user_stmt, "i", $user_id);
mysqli_stmt_execute($user_stmt);
$user_result = mysqli_stmt_get_result($user_stmt);
$user = mysqli_fetch_assoc($user_result);

// Get facilities count
$facilities_count_sql = "SELECT COUNT(*) as count FROM Facilities WHERE Status = 'Open'";
$facilities_count_result = mysqli_query($conn, $facilities_count_sql);
$facilities_count = mysqli_fetch_assoc($facilities_count_result)['count'];

// Get specific event if ID is provided (for modal view)
$selected_event = null;
if ($event_id > 0) {
    $event_sql = "SELECT e.*, 
                  (SELECT COUNT(*) FROM EventBookings WHERE EventID = e.EventID AND Status IN ('Confirmed', 'Used')) as booked_count,
                  (SELECT COUNT(*) FROM EventLikes WHERE EventID = e.EventID) as like_count
                  FROM Events e WHERE e.EventID = ?";
    $event_stmt = mysqli_prepare($conn, $event_sql);
    mysqli_stmt_bind_param($event_stmt, "i", $event_id);
    mysqli_stmt_execute($event_stmt);
    $event_result = mysqli_stmt_get_result($event_stmt);
    $selected_event = mysqli_fetch_assoc($event_result);
}

// Local images mapping for events (ඔයාගේ download කරපු images)
$event_local_images = [
    'SU Meeting' => 'images/su-meeting.jpg',
    'Workshop' => 'images/workshop.jpg', 
    'Hackathon' => 'images/hackathon.jpg',
    'Robotics' => 'images/robotics-event.jpg',
    'default' => 'images/event-default.jpg'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events - Synergy Hub</title>
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
        }
        
        /* NAVBAR */
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 32px;
            background: white;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.08);
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
        }
        
        /* SIDEBAR */
        .sidebar {
            position: fixed;
            left: -280px;
            top: 0;
            width: 280px;
            height: 100%;
            background: white;
            transition: 0.4s;
            z-index: 9999;
            box-shadow: 4px 0 30px rgba(0, 0, 0, 0.1);
            overflow-y: auto;
        }
        
        .sidebar.active { left: 0; }
        
        .sidebar-header {
            padding: 25px 20px;
            background: linear-gradient(135deg, #1e4a76 0%, #2c7da0 100%);
        }
        
        .sidebar-header h2 { color: white; font-size: 24px; }
        .sidebar-header p { color: rgba(255,255,255,0.8); font-size: 13px; }
        
        .sidebar-user {
            padding: 15px 20px;
            background: #f8fafc;
            margin: 15px;
            border-radius: 16px;
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
            color: white;
        }
        
        .sidebar-user-info h4 { color: #1e293b; font-size: 15px; }
        .sidebar-user-info p { color: #64748b; font-size: 12px; }
        
        .sidebar-nav { list-style: none; padding: 0; }
        .sidebar-nav-item { margin: 4px 12px; }
        
        .sidebar-nav-link {
            display: flex;
            align-items: center;
            padding: 12px 18px;
            color: #475569;
            text-decoration: none;
            border-radius: 12px;
            gap: 12px;
        }
        
        .sidebar-nav-link:hover { background: #e0f2fe; color: #1e4a76; }
        .sidebar-nav-link.active { background: #e0f2fe; color: #1e4a76; }
        
        .sidebar-badge {
            background: #ef4444;
            color: white;
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 30px;
            margin-left: auto;
        }
        
        .sidebar-footer {
            padding: 20px;
            border-top: 1px solid #e2e8f0;
            margin-top: 20px;
        }
        
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.4);
            z-index: 9998;
            display: none;
        }
        
        .sidebar-overlay.active { display: block; }
        
        /* Main Content */
        .events-container {
            padding: 30px;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .page-title {
            color: #1e4a76;
            font-size: 32px;
            margin-bottom: 20px;
        }
        
        /* Filter Bar */
        .filter-bar {
            background: white;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 30px;
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: flex-end;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .filter-group label {
            color: #64748b;
            font-size: 12px;
            font-weight: 500;
        }
        
        .filter-group input, .filter-group select {
            padding: 10px 15px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            min-width: 150px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #1e4a76 0%, #2c7da0 100%);
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
        }
        
        .btn-secondary {
            background: #f1f5f9;
            color: #475569;
            border: none;
            padding: 10px 25px;
            border-radius: 10px;
            cursor: pointer;
        }
        
        /* Events Grid */
        .events-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
        }
        
        .event-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.3s;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
            cursor: pointer;
        }
        
        .event-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        }
        
        .event-image {
            height: 180px;
            background-size: cover;
            background-position: center;
            position: relative;
        }
        
        .event-category {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .trending-badge {
            position: absolute;
            top: 15px;
            left: 15px;
            background: #f59e0b;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .live-badge {
            position: absolute;
            top: 15px;
            left: 15px;
            background: #ef4444;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            animation: pulse 1.5s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.6; }
        }
        
        .event-content { padding: 20px; }
        .event-title { font-size: 18px; font-weight: 700; color: #1e293b; margin-bottom: 10px; }
        .event-datetime { color: #2c7da0; font-size: 13px; margin-bottom: 8px; }
        .event-location { color: #64748b; font-size: 13px; margin-bottom: 12px; }
        .event-description { color: #475569; font-size: 14px; line-height: 1.5; margin-bottom: 15px; }
        
        .event-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 15px;
            border-top: 1px solid #e2e8f0;
        }
        
        .event-price { font-size: 18px; font-weight: 700; color: #1e4a76; }
        .spots-left { font-size: 11px; color: #64748b; margin-top: 3px; }
        
        .event-actions { display: flex; gap: 10px; align-items: center; }
        
        .like-btn {
            background: none;
            border: none;
            color: #94a3b8;
            font-size: 16px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 8px 12px;
            border-radius: 30px;
        }
        
        .like-btn.liked { color: #ef4444; }
        .book-btn {
            background: linear-gradient(135deg, #1e4a76 0%, #2c7da0 100%);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 30px;
            cursor: pointer;
            font-weight: 600;
        }
        
        .sold-out { color: #ef4444; font-weight: 600; font-size: 14px; }
        
        .back-to-clubs {
            display: inline-block;
            margin-top: 30px;
            color: #2c7da0;
            text-decoration: none;
        }
        
        @media (max-width: 768px) {
            .events-grid { grid-template-columns: 1fr; }
            .filter-bar { flex-direction: column; }
            .filter-group input, .filter-group select { width: 100%; }
        }
    </style>
</head>
<body>

<div id="sidebar" class="sidebar">
    <div class="sidebar-header">
        <h2>Synergy Hub</h2>
        <p>Explore Events</p>
    </div>
    
    <div class="sidebar-user">
        <div class="sidebar-user-avatar"><i class="fa-solid fa-user"></i></div>
        <div class="sidebar-user-info">
            <h4><?php echo htmlspecialchars($user['Name']); ?></h4>
            <p><i class="fa-solid fa-star"></i> <?php echo $user['PointsBalance']; ?> points</p>
        </div>
    </div>
    
    <ul class="sidebar-nav">
        <li class="sidebar-nav-item"><a href="index.php" class="sidebar-nav-link"><i class="fa-solid fa-home"></i> Home</a></li>
        <li class="sidebar-nav-item"><a href="facilities.php" class="sidebar-nav-link"><i class="fa-solid fa-building"></i> Facilities</a></li>
        <li class="sidebar-nav-item"><a href="transport.php" class="sidebar-nav-link"><i class="fa-solid fa-bus"></i> Transport</a></li>
        <li class="sidebar-nav-item"><a href="game.php" class="sidebar-nav-link"><i class="fa-solid fa-futbol"></i> Game Field</a></li>
        <li class="sidebar-nav-item"><a href="clubs.php" class="sidebar-nav-link"><i class="fa-solid fa-users"></i> Club Hub</a></li>
        <li class="sidebar-nav-item"><a href="events.php" class="sidebar-nav-link active"><i class="fa-regular fa-calendar-alt"></i> Events</a></li>
    </ul>
    
    <div class="sidebar-footer">
        <a href="clubs.php" style="color: #2c7da0; text-decoration: none;">← Back to Clubs</a>
    </div>
</div>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<header class="navbar">
    <div class="menu-btn" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></div>
    <h1 class="logo">Synergy <span>Hub</span> - Events</h1>
    <div class="icons">
        <div class="points"><i class="fa-solid fa-star"></i> <?php echo $user['PointsBalance']; ?></div>
        <a href="clubs.php" class="home-link"><i class="fa-solid fa-arrow-left"></i></a>
    </div>
</header>

<div class="events-container">
    <h1 class="page-title"><i class="fa-regular fa-calendar-alt"></i> Campus Events</h1>
    
    <!-- Filter Bar -->
    <div class="filter-bar">
        <div class="filter-group">
            <label>Category</label>
            <select id="categoryFilter">
                <option value="">All</option>
                <option value="SU" <?php echo $category_filter == 'SU' ? 'selected' : ''; ?>>SU Events</option>
                <option value="Club" <?php echo $category_filter == 'Club' ? 'selected' : ''; ?>>Club Events</option>
                <option value="Workshop" <?php echo $category_filter == 'Workshop' ? 'selected' : ''; ?>>Workshops</option>
            </select>
        </div>
        <div class="filter-group">
            <label>Search</label>
            <input type="text" id="searchInput" placeholder="Search events..." value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <div class="filter-group">
            <label>Sort By</label>
            <select id="sortBy">
                <option value="date" <?php echo $sort == 'date' ? 'selected' : ''; ?>>Date (Soonest)</option>
                <option value="popular" <?php echo $sort == 'popular' ? 'selected' : ''; ?>>Most Popular</option>
                <option value="price_asc" <?php echo $sort == 'price_asc' ? 'selected' : ''; ?>>Price: Low to High</option>
                <option value="price_desc" <?php echo $sort == 'price_desc' ? 'selected' : ''; ?>>Price: High to Low</option>
            </select>
        </div>
        <div class="filter-actions">
            <button class="btn-primary" onclick="applyFilters()">Apply</button>
            <button class="btn-secondary" onclick="resetFilters()">Reset</button>
        </div>
    </div>
    
    <!-- Events Grid -->
    <div class="events-grid">
        <?php if(mysqli_num_rows($events_result) > 0): ?>
            <?php while($event = mysqli_fetch_assoc($events_result)): 
                $available_spots = $event['max_capacity'] - $event['booked_count'];
                $is_sold_out = $available_spots <= 0;
                $is_liked = in_array($event['EventID'], $user_likes);
                $is_trending = $event['like_count'] >= 10;
                
                // Event title එක අනුව local image එක තෝරා ගැනීම
                $event_title = $event['Title'];
                $image_url = '';
                
                if(stripos($event_title, 'SU Meeting') !== false || stripos($event_title, 'Student Union') !== false) {
                    $image_url = $event_local_images['SU Meeting'];
                } elseif(stripos($event_title, 'Workshop') !== false || stripos($event_title, 'Web Development') !== false) {
                    $image_url = $event_local_images['Workshop'];
                } elseif(stripos($event_title, 'Hackathon') !== false) {
                    $image_url = $event_local_images['Hackathon'];
                } elseif(stripos($event_title, 'Robotics') !== false) {
                    $image_url = $event_local_images['Robotics'];
                } else {
                    // Database එකේ event_image තියෙනවා නම් ඒක පාවිච්චි කරන්න
                    if(!empty($event['event_image'])) {
                        $image_url = $event['event_image'];
                    } else {
                        $image_url = $event_local_images['default'];
                    }
                }
            ?>
                <div class="event-card" onclick="window.location.href='event_details.php?id=<?php echo $event['EventID']; ?>'">
                    <div class="event-image" style="background-image: url('<?php echo $image_url; ?>'); background-size: cover; background-position: center;">
                        <span class="event-category"><?php echo $event['Category']; ?></span>
                        <?php if($is_trending): ?>
                            <span class="trending-badge"><i class="fa-solid fa-fire"></i> Trending</span>
                        <?php endif; ?>
                        <?php if($event['Status'] == 'Ongoing'): ?>
                            <span class="live-badge"><i class="fa-solid fa-circle"></i> LIVE</span>
                        <?php endif; ?>
                    </div>
                    <div class="event-content">
                        <h3 class="event-title"><?php echo htmlspecialchars($event['Title']); ?></h3>
                        <div class="event-datetime">
                            <i class="fa-regular fa-calendar"></i> <?php echo date('M d, Y', strtotime($event['StartTime'])); ?>
                            <i class="fa-regular fa-clock"></i> <?php echo date('h:i A', strtotime($event['StartTime'])); ?>
                        </div>
                        <div class="event-location">
                            <i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($event['Location']); ?>
                        </div>
                        <div class="event-description">
                            <?php echo htmlspecialchars(substr($event['Description'], 0, 100)) . '...'; ?>
                        </div>
                        <div class="event-footer">
                            <div>
                                <span class="event-price"><?php echo $event['ticket_price'] > 0 ? 'Rs. ' . number_format($event['ticket_price'], 2) : 'FREE'; ?></span>
                                <div class="spots-left"><?php echo $available_spots; ?> spots left</div>
                            </div>
                            <div class="event-actions">
                                <button class="like-btn <?php echo $is_liked ? 'liked' : ''; ?>" onclick="event.stopPropagation(); toggleLike(<?php echo $event['EventID']; ?>, this)">
                                    <i class="fa-<?php echo $is_liked ? 'solid' : 'regular'; ?> fa-heart"></i>
                                    <span><?php echo $event['like_count']; ?></span>
                                </button>
                                <?php if($is_sold_out): ?>
                                    <span class="sold-out">Sold Out</span>
                                <?php else: ?>
                                    <button class="book-btn" onclick="event.stopPropagation(); bookEvent(<?php echo $event['EventID']; ?>, '<?php echo addslashes($event['Title']); ?>', <?php echo $event['ticket_price']; ?>)">Book Now</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div style="text-align: center; padding: 60px; background: white; border-radius: 20px;">
                <i class="fa-regular fa-calendar-xmark" style="font-size: 48px; color: #94a3b8;"></i>
                <p style="color: #64748b; margin-top: 15px;">No upcoming events found</p>
            </div>
        <?php endif; ?>
    </div>
    
    <a href="clubs.php" class="back-to-clubs"><i class="fa-solid fa-arrow-left"></i> Back to Club Hub</a>
</div>

<script>
function toggleSidebar() {
    document.querySelector('.sidebar').classList.toggle('active');
    document.getElementById('sidebarOverlay').classList.toggle('active');
}

function applyFilters() {
    const category = document.getElementById('categoryFilter').value;
    const search = document.getElementById('searchInput').value;
    const sort = document.getElementById('sortBy').value;
    window.location.href = `events.php?category=${category}&search=${encodeURIComponent(search)}&sort=${sort}`;
}

function resetFilters() {
    window.location.href = 'events.php';
}

function toggleLike(eventId, btn) {
    fetch('toggle_event_like.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `event_id=${eventId}`
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            const icon = btn.querySelector('i');
            const countSpan = btn.querySelector('span');
            let currentCount = parseInt(countSpan.textContent);
            
            if(data.liked) {
                icon.classList.remove('fa-regular');
                icon.classList.add('fa-solid');
                btn.classList.add('liked');
                countSpan.textContent = currentCount + 1;
            } else {
                icon.classList.remove('fa-solid');
                icon.classList.add('fa-regular');
                btn.classList.remove('liked');
                countSpan.textContent = currentCount - 1;
            }
        }
    });
}

function bookEvent(eventId, title, price) {
    if(confirm(`Book ticket for "${title}"? ${price > 0 ? 'Price: Rs. ' + price.toFixed(2) : 'FREE'}`)) {
        fetch('book_event.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `event_id=${eventId}`
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                alert('✅ Booking confirmed! Check your tickets.');
                window.location.href = 'my_tickets.php';
            } else {
                alert('❌ ' + data.message);
            }
        });
    }
}
</script>

</body>
</html>