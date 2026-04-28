<?php
require_once 'middleware.php';
require_once 'config.php';
checkAdminAuth();

$admin = getAdminInfo($conn);
$search_results = [];
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_by = isset($_GET['search_by']) ? $_GET['search_by'] : 'name';

if (!empty($search_term)) {
    // Prepare search query based on search type
    if ($search_by == 'student_id') {
        $search_sql = "SELECT * FROM Users WHERE StudentID LIKE ?";
        $search_param = "%$search_term%";
    } elseif ($search_by == 'email') {
        $search_sql = "SELECT * FROM Users WHERE Email LIKE ?";
        $search_param = "%$search_term%";
    } else {
        $search_sql = "SELECT * FROM Users WHERE Name LIKE ? OR StudentID LIKE ? OR Email LIKE ?";
        $search_param = "%$search_term%";
        $search_sql = "SELECT * FROM Users WHERE Name LIKE ? OR StudentID LIKE ? OR Email LIKE ?";
    }
    
    $stmt = mysqli_prepare($conn, $search_sql);
    if ($search_by == 'name') {
        mysqli_stmt_bind_param($stmt, "sss", $search_param, $search_param, $search_param);
    } else {
        mysqli_stmt_bind_param($stmt, "s", $search_param);
    }
    mysqli_stmt_execute($stmt);
    $search_results = mysqli_stmt_get_result($stmt);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Search - Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="admin.css">
    <style>
        .search-section {
            background: white;
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
        }
        
        .search-filters {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .filter-group {
            flex: 1;
            min-width: 150px;
        }
        
        .filter-group label {
            display: block;
            color: #475569;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .filter-group select,
        .filter-group input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .filter-group select:focus,
        .filter-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .user-card {
            background: white;
            border-radius: 16px;
            margin-bottom: 25px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            transition: all 0.3s;
        }
        
        .user-card:hover {
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        
        .user-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-avatar {
            width: 60px;
            height: 60px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
        }
        
        .user-details h3 {
            font-size: 20px;
            margin-bottom: 5px;
        }
        
        .user-details p {
            font-size: 12px;
            opacity: 0.9;
        }
        
        .user-badge {
            background: rgba(255,255,255,0.2);
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .user-body {
            padding: 20px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .info-section {
            background: #f8fafc;
            border-radius: 12px;
            padding: 15px;
            border: 1px solid #e2e8f0;
        }
        
        .info-section h4 {
            color: #1e293b;
            font-size: 16px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            color: #64748b;
            font-weight: 500;
            font-size: 13px;
        }
        
        .info-value {
            color: #1e293b;
            font-weight: 600;
            font-size: 13px;
        }
        
        .membership-list,
        .order-list,
        .pass-list {
            max-height: 300px;
            overflow-y: auto;
        }
        
        .membership-item,
        .order-item-mini,
        .pass-item {
            padding: 10px;
            margin-bottom: 8px;
            background: white;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }
        
        .membership-name {
            font-weight: 600;
            color: #667eea;
        }
        
        .order-item-mini {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .order-name {
            font-weight: 600;
        }
        
        .order-price {
            color: #16a34a;
            font-weight: 600;
        }
        
        .status-badge {
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .status-active {
            background: #dcfce7;
            color: #16a34a;
        }
        
        .status-expired {
            background: #fee;
            color: #ef4444;
        }
        
        .status-pending {
            background: #fff7ed;
            color: #ea580c;
        }
        
        .no-results {
            text-align: center;
            padding: 60px;
            background: white;
            border-radius: 16px;
            color: #64748b;
        }
        
        .highlight {
            background-color: #fef3c7;
            padding: 2px 4px;
            border-radius: 4px;
            color: #92400e;
            font-weight: bold;
        }
        
        @media (max-width: 768px) {
            .user-header {
                flex-direction: column;
                text-align: center;
            }
            
            .user-info {
                flex-direction: column;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Top Bar -->
            <?php include 'includes/topbar.php'; ?>
            
            <!-- Content -->
            <div class="content">
                <h1 class="page-title">
                    <i class="fa-solid fa-magnifying-glass"></i> User Search
                </h1>
                
                <!-- Search Section -->
                <div class="search-section">
                    <form method="GET" action="">
                        <div class="search-filters">
                            <div class="filter-group">
                                <label><i class="fa-regular fa-search"></i> Search By</label>
                                <select name="search_by">
                                    <option value="name" <?php echo $search_by == 'name' ? 'selected' : ''; ?>>Name</option>
                                    <option value="student_id" <?php echo $search_by == 'student_id' ? 'selected' : ''; ?>>Student ID</option>
                                    <option value="email" <?php echo $search_by == 'email' ? 'selected' : ''; ?>>Email</option>
                                </select>
                            </div>
                            <div class="filter-group" style="flex: 2;">
                                <label><i class="fa-regular fa-keyboard"></i> Search Term</label>
                                <input type="text" name="search" placeholder="Enter name, student ID, or email..." value="<?php echo htmlspecialchars($search_term); ?>">
                            </div>
                            <div class="filter-group" style="flex: 0.5;">
                                <label>&nbsp;</label>
                                <button type="submit" class="btn btn-primary" style="width: 100%;">
                                    <i class="fa-solid fa-magnifying-glass"></i> Search
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- Search Results -->
                <?php if (!empty($search_term)): ?>
                    <?php if ($search_results && mysqli_num_rows($search_results) > 0): ?>
                        <div class="search-results-info" style="margin-bottom: 20px; padding: 12px; background: #f1f5f9; border-radius: 8px;">
                            <i class="fa-solid fa-chart-simple"></i> Found <?php echo mysqli_num_rows($search_results); ?> user(s) matching "<strong><?php echo htmlspecialchars($search_term); ?></strong>"
                        </div>
                        
                        <?php while($user = mysqli_fetch_assoc($search_results)): 
                            // Get user details from all tables
                            $user_id = $user['UserID'];
                            
                            // Get club memberships
                            $clubs_sql = "SELECT c.*, cm.Role, cm.JoinDate 
                                         FROM ClubMemberships cm 
                                         JOIN Clubs c ON cm.ClubID = c.ClubID 
                                         WHERE cm.UserID = ? AND cm.Status = 'Active'";
                            $stmt = mysqli_prepare($conn, $clubs_sql);
                            mysqli_stmt_bind_param($stmt, "i", $user_id);
                            mysqli_stmt_execute($stmt);
                            $clubs = mysqli_stmt_get_result($stmt);
                            
                            // Get transport passes
                            $passes_sql = "SELECT * FROM TransportPasses WHERE UserID = ? ORDER BY ValidUntil DESC";
                            $stmt = mysqli_prepare($conn, $passes_sql);
                            mysqli_stmt_bind_param($stmt, "i", $user_id);
                            mysqli_stmt_execute($stmt);
                            $passes = mysqli_stmt_get_result($stmt);
                            
                            // Get orders
                            $orders_sql = "SELECT * FROM Orders WHERE UserID = ? ORDER BY Timestamp DESC LIMIT 5";
                            $stmt = mysqli_prepare($conn, $orders_sql);
                            mysqli_stmt_bind_param($stmt, "i", $user_id);
                            mysqli_stmt_execute($stmt);
                            $orders = mysqli_stmt_get_result($stmt);
                            
                            // Get check-ins
                            $checkins_sql = "SELECT c.*, f.Name as FacilityName 
                                            FROM CheckIns c 
                                            JOIN Facilities f ON c.FacilityID = f.FacilityID 
                                            WHERE c.UserID = ? 
                                            ORDER BY c.Timestamp DESC LIMIT 5";
                            $stmt = mysqli_prepare($conn, $checkins_sql);
                            mysqli_stmt_bind_param($stmt, "i", $user_id);
                            mysqli_stmt_execute($stmt);
                            $checkins = mysqli_stmt_get_result($stmt);
                            
                            // Get borrowed books
                            $books_sql = "SELECT bb.*, b.title, b.author 
                                         FROM borrowed_books bb 
                                         JOIN books b ON bb.book_id = b.book_id 
                                         WHERE bb.user_id = ? AND bb.status = 'borrowed'";
                            $stmt = mysqli_prepare($conn, $books_sql);
                            mysqli_stmt_bind_param($stmt, "i", $user_id);
                            mysqli_stmt_execute($stmt);
                            $books = mysqli_stmt_get_result($stmt);
                            
                            // Get game activity
                            $games_sql = "SELECT * FROM GameField WHERE UserID = ? ORDER BY Timestamp DESC LIMIT 5";
                            $stmt = mysqli_prepare($conn, $games_sql);
                            mysqli_stmt_bind_param($stmt, "i", $user_id);
                            mysqli_stmt_execute($stmt);
                            $games = mysqli_stmt_get_result($stmt);
                            
                            // Highlight search term
                            $highlighted_name = htmlspecialchars($user['Name']);
                            $highlighted_student_id = htmlspecialchars($user['StudentID']);
                            $highlighted_email = htmlspecialchars($user['Email']);
                            
                            if (!empty($search_term)) {
                                $highlighted_name = str_ireplace($search_term, "<span class='highlight'>$search_term</span>", $highlighted_name);
                                $highlighted_student_id = str_ireplace($search_term, "<span class='highlight'>$search_term</span>", $highlighted_student_id);
                                $highlighted_email = str_ireplace($search_term, "<span class='highlight'>$search_term</span>", $highlighted_email);
                            }
                        ?>
                        
                        <div class="user-card">
                            <div class="user-header">
                                <div class="user-info">
                                    <div class="user-avatar">
                                        <i class="fa-solid fa-user"></i>
                                    </div>
                                    <div class="user-details">
                                        <h3><?php echo $highlighted_name; ?></h3>
                                        <p><i class="fa-regular fa-id-card"></i> ID: <?php echo $highlighted_student_id; ?></p>
                                        <p><i class="fa-regular fa-envelope"></i> <?php echo $highlighted_email; ?></p>
                                    </div>
                                </div>
                                <div class="user-badge">
                                    <i class="fa-solid fa-crown"></i> <?php echo $user['Role']; ?>
                                </div>
                            </div>
                            
                            <div class="user-body">
                                <div class="info-grid">
                                    <!-- Basic Information -->
                                    <div class="info-section">
                                        <h4><i class="fa-solid fa-address-card"></i> Basic Information</h4>
                                        <div class="info-row">
                                            <span class="info-label">Points Balance:</span>
                                            <span class="info-value"><i class="fa-solid fa-star" style="color: #facc15;"></i> <?php echo number_format($user['PointsBalance']); ?> points</span>
                                        </div>
                                        <div class="info-row">
                                            <span class="info-label">Membership Status:</span>
                                            <span class="info-value">
                                                <span class="status-badge status-<?php echo strtolower($user['MembershipStatus']); ?>">
                                                    <?php echo $user['MembershipStatus']; ?>
                                                </span>
                                            </span>
                                        </div>
                                        <div class="info-row">
                                            <span class="info-label">Joined:</span>
                                            <span class="info-value"><?php echo date('F j, Y', strtotime($user['CreatedAt'])); ?></span>
                                        </div>
                                    </div>
                                    
                                    <!-- Club Memberships -->
                                    <div class="info-section">
                                        <h4><i class="fa-solid fa-users"></i> Club Memberships</h4>
                                        <div class="membership-list">
                                            <?php if(mysqli_num_rows($clubs) > 0): ?>
                                                <?php while($club = mysqli_fetch_assoc($clubs)): ?>
                                                    <div class="membership-item">
                                                        <div class="membership-name"><?php echo htmlspecialchars($club['Name']); ?></div>
                                                        <div class="info-row" style="margin-top: 5px;">
                                                            <span class="info-label">Role:</span>
                                                            <span class="info-value"><?php echo $club['Role']; ?></span>
                                                        </div>
                                                        <div class="info-row">
                                                            <span class="info-label">Joined:</span>
                                                            <span class="info-value"><?php echo date('M d, Y', strtotime($club['JoinDate'])); ?></span>
                                                        </div>
                                                    </div>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <p style="color: #94a3b8; text-align: center; padding: 20px;">
                                                    <i class="fa-regular fa-face-frown"></i> No club memberships
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Transport Passes -->
                                    <div class="info-section">
                                        <h4><i class="fa-solid fa-bus"></i> Transport Passes</h4>
                                        <div class="pass-list">
                                            <?php if(mysqli_num_rows($passes) > 0): ?>
                                                <?php while($pass = mysqli_fetch_assoc($passes)): ?>
                                                    <div class="pass-item">
                                                        <div class="info-row">
                                                            <span class="info-label">Route:</span>
                                                            <span class="info-value"><?php echo htmlspecialchars($pass['RouteName']); ?></span>
                                                        </div>
                                                        <div class="info-row">
                                                            <span class="info-label">Valid Until:</span>
                                                            <span class="info-value"><?php echo date('M d, Y', strtotime($pass['ValidUntil'])); ?></span>
                                                        </div>
                                                        <div class="info-row">
                                                            <span class="info-label">Status:</span>
                                                            <span class="info-value">
                                                                <span class="status-badge status-<?php echo strtolower($pass['Status']); ?>">
                                                                    <?php echo $pass['Status']; ?>
                                                                </span>
                                                            </span>
                                                        </div>
                                                    </div>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <p style="color: #94a3b8; text-align: center; padding: 20px;">
                                                    <i class="fa-regular fa-face-frown"></i> No transport passes
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Recent Orders -->
                                    <div class="info-section">
                                        <h4><i class="fa-solid fa-cart-shopping"></i> Recent Orders</h4>
                                        <div class="order-list">
                                            <?php if(mysqli_num_rows($orders) > 0): ?>
                                                <?php while($order = mysqli_fetch_assoc($orders)): ?>
                                                    <div class="order-item-mini">
                                                        <div>
                                                            <div class="order-name"><?php echo htmlspecialchars($order['ItemName']); ?></div>
                                                            <div style="font-size: 11px; color: #64748b;">x<?php echo $order['Quantity']; ?></div>
                                                        </div>
                                                        <div style="text-align: right;">
                                                            <div class="order-price">LKR <?php echo number_format($order['Price'] * $order['Quantity']); ?></div>
                                                            <div class="status-badge status-<?php echo strtolower($order['Status']); ?>" style="margin-top: 4px;">
                                                                <?php echo $order['Status']; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <p style="color: #94a3b8; text-align: center; padding: 20px;">
                                                    <i class="fa-regular fa-face-frown"></i> No orders yet
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Recent Check-ins -->
                                    <div class="info-section">
                                        <h4><i class="fa-solid fa-location-dot"></i> Recent Check-ins</h4>
                                        <div class="order-list">
                                            <?php if(mysqli_num_rows($checkins) > 0): ?>
                                                <?php while($checkin = mysqli_fetch_assoc($checkins)): ?>
                                                    <div class="pass-item">
                                                        <div class="info-row">
                                                            <span class="info-label">Facility:</span>
                                                            <span class="info-value"><?php echo htmlspecialchars($checkin['FacilityName']); ?></span>
                                                        </div>
                                                        <div class="info-row">
                                                            <span class="info-label">Time:</span>
                                                            <span class="info-value"><?php echo date('M d, h:i A', strtotime($checkin['Timestamp'])); ?></span>
                                                        </div>
                                                        <div class="info-row">
                                                            <span class="info-label">Points Earned:</span>
                                                            <span class="info-value">+<?php echo $checkin['PointsAwarded']; ?> pts</span>
                                                        </div>
                                                    </div>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <p style="color: #94a3b8; text-align: center; padding: 20px;">
                                                    <i class="fa-regular fa-face-frown"></i> No check-ins yet
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Borrowed Books -->
                                    <div class="info-section">
                                        <h4><i class="fa-solid fa-book"></i> Borrowed Books</h4>
                                        <div class="order-list">
                                            <?php if(mysqli_num_rows($books) > 0): ?>
                                                <?php while($book = mysqli_fetch_assoc($books)): ?>
                                                    <div class="pass-item">
                                                        <div class="info-row">
                                                            <span class="info-label">Title:</span>
                                                            <span class="info-value"><?php echo htmlspecialchars($book['title']); ?></span>
                                                        </div>
                                                        <div class="info-row">
                                                            <span class="info-label">Author:</span>
                                                            <span class="info-value"><?php echo htmlspecialchars($book['author']); ?></span>
                                                        </div>
                                                        <div class="info-row">
                                                            <span class="info-label">Due Date:</span>
                                                            <span class="info-value"><?php echo date('M d, Y', strtotime($book['due_date'])); ?></span>
                                                        </div>
                                                    </div>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <p style="color: #94a3b8; text-align: center; padding: 20px;">
                                                    <i class="fa-regular fa-face-frown"></i> No borrowed books
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Game Activity -->
                                    <div class="info-section">
                                        <h4><i class="fa-solid fa-gamepad"></i> Game Activity</h4>
                                        <div class="order-list">
                                            <?php if(mysqli_num_rows($games) > 0): ?>
                                                <?php while($game = mysqli_fetch_assoc($games)): ?>
                                                    <div class="pass-item">
                                                        <div class="info-row">
                                                            <span class="info-label">Game:</span>
                                                            <span class="info-value"><?php echo htmlspecialchars($game['GameType']); ?></span>
                                                        </div>
                                                        <div class="info-row">
                                                            <span class="info-label">Points Earned:</span>
                                                            <span class="info-value">+<?php echo $game['PointsEarned']; ?> pts</span>
                                                        </div>
                                                        <div class="info-row">
                                                            <span class="info-label">When:</span>
                                                            <span class="info-value"><?php echo date('M d, h:i A', strtotime($game['Timestamp'])); ?></span>
                                                        </div>
                                                    </div>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <p style="color: #94a3b8; text-align: center; padding: 20px;">
                                                    <i class="fa-regular fa-face-frown"></i> No game activity
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <?php endwhile; ?>
                        
                    <?php else: ?>
                        <div class="no-results">
                            <i class="fa-solid fa-user-slash" style="font-size: 60px; margin-bottom: 20px; color: #94a3b8;"></i>
                            <h3>No users found</h3>
                            <p>We couldn't find any users matching "<strong><?php echo htmlspecialchars($search_term); ?></strong>"</p>
                            <p style="margin-top: 15px;">Try searching by name, student ID, or email</p>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="no-results">
                        <i class="fa-solid fa-magnifying-glass" style="font-size: 60px; margin-bottom: 20px; color: #94a3b8;"></i>
                        <h3>Search for users</h3>
                        <p>Enter a name, student ID, or email to view detailed user information including:</p>
                        <div style="display: flex; justify-content: center; gap: 30px; margin-top: 20px; flex-wrap: wrap;">
                            <div><i class="fa-solid fa-users"></i> Club Memberships</div>
                            <div><i class="fa-solid fa-bus"></i> Transport Passes</div>
                            <div><i class="fa-solid fa-cart-shopping"></i> Orders</div>
                            <div><i class="fa-solid fa-book"></i> Borrowed Books</div>
                            <div><i class="fa-solid fa-gamepad"></i> Game Activity</div>
                            <div><i class="fa-solid fa-location-dot"></i> Check-ins</div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
    function toggleSidebar() {
        document.querySelector('.sidebar').classList.toggle('show');
    }
    </script>
</body>
</html>