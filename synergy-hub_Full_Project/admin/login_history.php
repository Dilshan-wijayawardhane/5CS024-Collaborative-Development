<?php
require_once 'middleware.php';
require_once 'config.php';
checkAdminAuth();

$admin = getAdminInfo($conn);
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 50;
$offset = ($page - 1) * $limit;

// Get user info if specific user
$user_name = '';
if ($user_id > 0) {
    $user_sql = "SELECT Name FROM Users WHERE UserID = ?";
    $user_stmt = mysqli_prepare($conn, $user_sql);
    mysqli_stmt_bind_param($user_stmt, "i", $user_id);
    mysqli_stmt_execute($user_stmt);
    $user_result = mysqli_stmt_get_result($user_stmt);
    $user = mysqli_fetch_assoc($user_result);
    $user_name = $user ? $user['Name'] : '';
}

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM AuditLogs WHERE Action = 'LOGIN'";
$count_params = [];
$count_types = '';

if ($user_id > 0) {
    $count_sql .= " AND UserID = ?";
    $count_params[] = $user_id;
    $count_types .= 'i';
}

$count_stmt = mysqli_prepare($conn, $count_sql);
if (!empty($count_params)) {
    mysqli_stmt_bind_param($count_stmt, $count_types, ...$count_params);
}
mysqli_stmt_execute($count_stmt);
$count_result = mysqli_stmt_get_result($count_stmt);
$total_logins = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_logins / $limit);

// Get login history
$sql = "SELECT al.*, u.Name as UserName, u.Email 
        FROM AuditLogs al
        JOIN Users u ON al.UserID = u.UserID
        WHERE al.Action = 'LOGIN'";
$params = [];
$types = '';

if ($user_id > 0) {
    $sql .= " AND al.UserID = ?";
    $params[] = $user_id;
    $types .= 'i';
}

$sql .= " ORDER BY al.Timestamp DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Get login statistics
$stats_sql = "SELECT 
                COUNT(DISTINCT UserID) as unique_users,
                COUNT(*) as total_logins,
                COUNT(CASE WHEN DATE(Timestamp) = CURDATE() THEN 1 END) as today_logins,
                COUNT(CASE WHEN DATE(Timestamp) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as week_logins
              FROM AuditLogs 
              WHERE Action = 'LOGIN'";
$stats_result = mysqli_query($conn, $stats_sql);
$stats = mysqli_fetch_assoc($stats_result);

// Get top browsers/devices
$device_sql = "SELECT 
                CASE 
                    WHEN UserAgent LIKE '%Chrome%' THEN 'Chrome'
                    WHEN UserAgent LIKE '%Firefox%' THEN 'Firefox'
                    WHEN UserAgent LIKE '%Safari%' AND UserAgent NOT LIKE '%Chrome%' THEN 'Safari'
                    WHEN UserAgent LIKE '%Edge%' THEN 'Edge'
                    ELSE 'Other'
                END as browser,
                COUNT(*) as count
              FROM AuditLogs 
              WHERE Action = 'LOGIN' AND UserAgent IS NOT NULL
              GROUP BY browser
              ORDER BY count DESC";
$device_result = mysqli_query($conn, $device_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login History - Synergy Hub Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="admin.css">
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
        }
        
        .stat-info h3 {
            font-size: 24px;
            color: #1e293b;
            margin: 0;
        }
        
        .stat-info p {
            color: #64748b;
            margin: 5px 0 0;
            font-size: 14px;
        }
        
        .filter-bar {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .filter-bar select,
        .filter-bar input {
            padding: 10px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            min-width: 200px;
        }
        
        .login-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 12px;
            overflow: hidden;
        }
        
        .login-table th {
            background: #f8fafc;
            padding: 15px;
            text-align: left;
            font-size: 12px;
            color: #64748b;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .login-table td {
            padding: 15px;
            border-bottom: 1px solid #e2e8f0;
            color: #1e293b;
        }
        
        .login-table tr:hover td {
            background: #f8fafc;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .user-avatar {
            width: 35px;
            height: 35px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }
        
        .ip-address {
            font-family: monospace;
            background: #f1f5f9;
            padding: 4px 8px;
            border-radius: 4px;
        }
        
        .device-badge {
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .device-desktop { background: #e0f2fe; color: #0284c7; }
        .device-mobile { background: #dcfce7; color: #16a34a; }
        .device-tablet { background: #f3e8ff; color: #9333ea; }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 5px;
            margin-top: 20px;
        }
        
        .pagination a,
        .pagination span {
            padding: 8px 12px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            color: #475569;
            text-decoration: none;
        }
        
        .pagination a:hover {
            background: #f1f5f9;
        }
        
        .pagination .active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        
        .device-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
        }
        
        .device-stat {
            background: white;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
        }
        
        .device-stat .browser {
            font-weight: 600;
            color: #1e293b;
        }
        
        .device-stat .count {
            font-size: 20px;
            color: #667eea;
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
                    <i class="fa-solid fa-clock-rotate-left"></i> Login History
                    <?php if($user_name): ?>
                    <span style="font-size: 18px; color: #64748b;"> - <?php echo htmlspecialchars($user_name); ?></span>
                    <?php endif; ?>
                </h1>
                
                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fa-solid fa-users"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo number_format($stats['unique_users']); ?></h3>
                            <p>Unique Users</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fa-solid fa-arrow-right-to-bracket"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo number_format($stats['total_logins']); ?></h3>
                            <p>Total Logins</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fa-regular fa-calendar"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo number_format($stats['today_logins']); ?></h3>
                            <p>Today</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fa-regular fa-calendar-check"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo number_format($stats['week_logins']); ?></h3>
                            <p>Last 7 Days</p>
                        </div>
                    </div>
                </div>
                
                <!-- Filter Bar -->
                <div class="filter-bar">
                    <select id="userFilter" onchange="filterByUser()">
                        <option value="">All Users</option>
                        <?php
                        $users_sql = "SELECT UserID, Name FROM Users ORDER BY Name";
                        $users_result = mysqli_query($conn, $users_sql);
                        while($u = mysqli_fetch_assoc($users_result)) {
                            $selected = ($user_id == $u['UserID']) ? 'selected' : '';
                            echo "<option value='{$u['UserID']}' $selected>" . htmlspecialchars($u['Name']) . "</option>";
                        }
                        ?>
                    </select>
                    
                    <input type="date" id="dateFrom" placeholder="From Date">
                    <input type="date" id="dateTo" placeholder="To Date">
                    
                    <button class="btn btn-primary btn-sm" onclick="applyFilters()">
                        <i class="fa-solid fa-filter"></i> Apply Filters
                    </button>
                    <a href="login_history.php" class="btn btn-secondary btn-sm">
                        <i class="fa-solid fa-rotate"></i> Reset
                    </a>
                </div>
                
                <!-- Device Stats -->
                <div style="margin-bottom: 20px;">
                    <h3>Browser Distribution</h3>
                    <div class="device-stats">
                        <?php while($device = mysqli_fetch_assoc($device_result)): ?>
                        <div class="device-stat">
                            <div class="browser"><?php echo $device['browser']; ?></div>
                            <div class="count"><?php echo $device['count']; ?></div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>
                
                <!-- Login History Table -->
                <div class="table-container">
                    <table class="login-table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Date & Time</th>
                                <th>IP Address</th>
                                <th>Device</th>
                                <th>Location</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(mysqli_num_rows($result) > 0): ?>
                                <?php while($log = mysqli_fetch_assoc($result)): 
                                    // Parse user agent for device info
                                    $user_agent = $log['UserAgent'] ?? '';
                                    $device_type = 'desktop';
                                    if (preg_match('/(mobile|android|iphone)/i', $user_agent)) {
                                        $device_type = 'mobile';
                                    } elseif (preg_match('/(tablet|ipad)/i', $user_agent)) {
                                        $device_type = 'tablet';
                                    }
                                    
                                    // Detect browser
                                    $browser = 'Other';
                                    if (strpos($user_agent, 'Chrome') !== false) $browser = 'Chrome';
                                    elseif (strpos($user_agent, 'Firefox') !== false) $browser = 'Firefox';
                                    elseif (strpos($user_agent, 'Safari') !== false) $browser = 'Safari';
                                    elseif (strpos($user_agent, 'Edge') !== false) $browser = 'Edge';
                                ?>
                                <tr>
                                    <td>
                                        <div class="user-info">
                                            <div class="user-avatar">
                                                <?php echo strtoupper(substr($log['UserName'], 0, 2)); ?>
                                            </div>
                                            <div>
                                                <strong><?php echo htmlspecialchars($log['UserName']); ?></strong>
                                                <div style="font-size: 12px; color: #64748b;"><?php echo $log['Email']; ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php echo date('M d, Y', strtotime($log['Timestamp'])); ?>
                                        <div style="font-size: 12px; color: #64748b;"><?php echo date('h:i A', strtotime($log['Timestamp'])); ?></div>
                                    </td>
                                    <td>
                                        <span class="ip-address"><?php echo $log['IPAddress'] ?: 'N/A'; ?></span>
                                    </td>
                                    <td>
                                        <span class="device-badge device-<?php echo $device_type; ?>">
                                            <i class="fa-solid <?php 
                                                echo $device_type == 'mobile' ? 'fa-mobile' : 
                                                    ($device_type == 'tablet' ? 'fa-tablet' : 'fa-desktop'); 
                                            ?>"></i>
                                            <?php echo ucfirst($device_type); ?>
                                        </span>
                                        <div style="font-size: 11px; color: #64748b; margin-top: 5px;"><?php echo $browser; ?></div>
                                    </td>
                                    <td>
                                        <?php 
                                        // You would integrate with a geolocation API here
                                        echo '<span class="badge badge-secondary">Unknown</span>';
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                        // Check if the key exists in the array first
                                        if (isset($log['Details']) && $log['Details']): 
                                        ?>
                                            <span title="<?php echo htmlspecialchars($log['Details']); ?>">
                                                <i class="fa-regular fa-message"></i>
                                            </span>
                                        <?php else: ?>
                                            <span style="color: #cbd5e1; font-size: 12px;">None</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 40px; color: #64748b;">
                                        <i class="fa-regular fa-clock" style="font-size: 48px; margin-bottom: 10px;"></i>
                                        <p>No login history found</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page-1; ?><?php echo $user_id ? '&user_id='.$user_id : ''; ?>">
                        <i class="fa-solid fa-chevron-left"></i>
                    </a>
                    <?php endif; ?>
                    
                    <?php for($i = 1; $i <= $total_pages; $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="active"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?page=<?php echo $i; ?><?php echo $user_id ? '&user_id='.$user_id : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page+1; ?><?php echo $user_id ? '&user_id='.$user_id : ''; ?>">
                        <i class="fa-solid fa-chevron-right"></i>
                    </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        function filterByUser() {
            const userId = document.getElementById('userFilter').value;
            if (userId) {
                window.location.href = 'login_history.php?user_id=' + userId;
            }
        }
        
        function applyFilters() {
            const userId = document.getElementById('userFilter').value;
            const dateFrom = document.getElementById('dateFrom').value;
            const dateTo = document.getElementById('dateTo').value;
            
            let url = 'login_history.php?';
            if (userId) url += 'user_id=' + userId + '&';
            if (dateFrom) url += 'from=' + dateFrom + '&';
            if (dateTo) url += 'to=' + dateTo;
            
            window.location.href = url;
        }
    </script>
</body>
</html>