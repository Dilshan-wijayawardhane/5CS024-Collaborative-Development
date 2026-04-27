<?php
require_once 'middleware.php';
require_once 'config.php';
checkAdminAuth();

$admin = getAdminInfo($conn);

// Get date range from request
$range = isset($_GET['range']) ? $_GET['range'] : '30days';
$end_date = date('Y-m-d');
$start_date = date('Y-m-d', strtotime($range == '7days' ? '-7 days' : ($range == '90days' ? '-90 days' : '-30 days')));

// User registration statistics
$reg_sql = "SELECT 
                DATE(CreatedAt) as date,
                COUNT(*) as count
            FROM Users
            WHERE CreatedAt >= ? AND CreatedAt <= ?
            GROUP BY DATE(CreatedAt)
            ORDER BY date ASC";
$reg_stmt = mysqli_prepare($conn, $reg_sql);
mysqli_stmt_bind_param($reg_stmt, "ss", $start_date, $end_date);
mysqli_stmt_execute($reg_stmt);
$reg_result = mysqli_stmt_get_result($reg_stmt);

$reg_dates = [];
$reg_counts = [];
while($row = mysqli_fetch_assoc($reg_result)) {
    $reg_dates[] = $row['date'];
    $reg_counts[] = $row['count'];
}

// User role distribution
$role_sql = "SELECT 
                Role,
                COUNT(*) as count
            FROM Users
            GROUP BY Role";
$role_result = mysqli_query($conn, $role_sql);
$role_labels = [];
$role_counts = [];
while($row = mysqli_fetch_assoc($role_result)) {
    $role_labels[] = $row['Role'];
    $role_counts[] = $row['count'];
}

// User status distribution
$status_sql = "SELECT 
                MembershipStatus,
                COUNT(*) as count
            FROM Users
            GROUP BY MembershipStatus";
$status_result = mysqli_query($conn, $status_sql);
$status_labels = [];
$status_counts = [];
while($row = mysqli_fetch_assoc($status_result)) {
    $status_labels[] = $row['MembershipStatus'];
    $status_counts[] = $row['count'];
}

// Daily active users
$active_sql = "SELECT 
                DATE(Timestamp) as date,
                COUNT(DISTINCT UserID) as count
            FROM ActivityLogs
            WHERE Timestamp >= ? AND Timestamp <= ?
            GROUP BY DATE(Timestamp)
            ORDER BY date ASC";
$active_stmt = mysqli_prepare($conn, $active_sql);
mysqli_stmt_bind_param($active_stmt, "ss", $start_date, $end_date);
mysqli_stmt_execute($active_stmt);
$active_result = mysqli_stmt_get_result($active_stmt);

$active_dates = [];
$active_counts = [];
while($row = mysqli_fetch_assoc($active_result)) {
    $active_dates[] = $row['date'];
    $active_counts[] = $row['count'];
}

// Points distribution
$points_sql = "SELECT 
                CASE 
                    WHEN PointsBalance = 0 THEN '0'
                    WHEN PointsBalance BETWEEN 1 AND 100 THEN '1-100'
                    WHEN PointsBalance BETWEEN 101 AND 500 THEN '101-500'
                    WHEN PointsBalance BETWEEN 501 AND 1000 THEN '501-1000'
                    WHEN PointsBalance BETWEEN 1001 AND 5000 THEN '1001-5000'
                    ELSE '5000+'
                END as points_range,
                COUNT(*) as count
            FROM Users
            GROUP BY points_range
            ORDER BY points_range";
$points_result = mysqli_query($conn, $points_sql);
$points_labels = [];
$points_counts = [];
while($row = mysqli_fetch_assoc($points_result)) {
    $points_labels[] = $row['points_range'];
    $points_counts[] = $row['count'];
}

// Top 10 users by points
$top_users_sql = "SELECT Name, Email, PointsBalance 
                FROM Users 
                WHERE Role = 'User'
                ORDER BY PointsBalance DESC 
                LIMIT 10";
$top_users_result = mysqli_query($conn, $top_users_sql);

// Summary statistics
$summary_sql = "SELECT 
                COUNT(*) as total_users,
                SUM(CASE WHEN DATE(CreatedAt) = CURDATE() THEN 1 ELSE 0 END) as new_today,
                SUM(CASE WHEN DATE(CreatedAt) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as new_week,
                AVG(PointsBalance) as avg_points,
                MAX(PointsBalance) as max_points,
                SUM(CASE WHEN MembershipStatus = 'Active' THEN 1 ELSE 0 END) as active_users
            FROM Users";
$summary_result = mysqli_query($conn, $summary_sql);
$summary = mysqli_fetch_assoc($summary_result);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Statistics - Synergy Hub Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stats-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .range-selector {
            display: flex;
            gap: 10px;
        }
        
        .range-btn {
            padding: 8px 16px;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            color: #64748b;
            cursor: pointer;
            text-decoration: none;
        }
        
        .range-btn:hover {
            background: #f1f5f9;
        }
        
        .range-btn.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: transparent;
        }
        
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .summary-card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            position: relative;
            overflow: hidden;
        }
        
        .summary-card::before {
            content: '';
            position: absolute;
            top: -20px;
            right: -20px;
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea20 0%, #764ba220 100%);
            border-radius: 50%;
        }
        
        .summary-label {
            color: #64748b;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .summary-value {
            font-size: 36px;
            font-weight: 700;
            color: #1e293b;
        }
        
        .summary-trend {
            margin-top: 10px;
            font-size: 13px;
        }
        
        .trend-up { color: #10b981; }
        .trend-down { color: #ef4444; }
        
        .chart-row {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .chart-container {
            background: white;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        
        .chart-title {
            font-size: 18px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 20px;
        }
        
        .chart-wrapper {
            height: 300px;
        }
        
        .mini-charts {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .top-users {
            background: white;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        
        .top-users table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .top-users th {
            text-align: left;
            padding: 10px;
            color: #64748b;
            font-weight: 600;
            font-size: 12px;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .top-users td {
            padding: 10px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .rank-1 { color: #FFD700; font-weight: 700; }
        .rank-2 { color: #C0C0C0; font-weight: 600; }
        .rank-3 { color: #CD7F32; font-weight: 600; }
        
        @media (max-width: 1024px) {
            .chart-row {
                grid-template-columns: 1fr;
            }
            
            .mini-charts {
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
                <div class="stats-header">
                    <h1 class="page-title">
                        <i class="fa-solid fa-chart-line"></i> User Statistics
                    </h1>
                    
                    <div class="range-selector">
                        <a href="?range=7days" class="range-btn <?php echo $range == '7days' ? 'active' : ''; ?>">7 Days</a>
                        <a href="?range=30days" class="range-btn <?php echo $range == '30days' ? 'active' : ''; ?>">30 Days</a>
                        <a href="?range=90days" class="range-btn <?php echo $range == '90days' ? 'active' : ''; ?>">90 Days</a>
                    </div>
                </div>
                
                <!-- Summary Cards -->
                <div class="summary-grid">
                    <div class="summary-card">
                        <div class="summary-label">Total Users</div>
                        <div class="summary-value"><?php echo number_format($summary['total_users']); ?></div>
                        <div class="summary-trend trend-up">
                            <i class="fa-solid fa-arrow-up"></i> +<?php echo $summary['new_today']; ?> today
                        </div>
                    </div>
                    
                    <div class="summary-card">
                        <div class="summary-label">Active Users</div>
                        <div class="summary-value"><?php echo number_format($summary['active_users']); ?></div>
                        <div class="summary-trend">
                            <?php echo round(($summary['active_users'] / $summary['total_users']) * 100); ?>% of total
                        </div>
                    </div>
                    
                    <div class="summary-card">
                        <div class="summary-label">New Users (7d)</div>
                        <div class="summary-value"><?php echo number_format($summary['new_week']); ?></div>
                        <div class="summary-trend">
                            Avg. <?php echo round($summary['new_week'] / 7); ?> per day
                        </div>
                    </div>
                    
                    <div class="summary-card">
                        <div class="summary-label">Average Points</div>
                        <div class="summary-value"><?php echo number_format(round($summary['avg_points'])); ?></div>
                        <div class="summary-trend">
                            Max: <?php echo number_format($summary['max_points']); ?>
                        </div>
                    </div>
                </div>
                
                <!-- Main Charts -->
                <div class="chart-row">
                    <!-- User Registrations Chart -->
                    <div class="chart-container">
                        <h3 class="chart-title">User Registrations</h3>
                        <div class="chart-wrapper">
                            <canvas id="registrationChart"></canvas>
                        </div>
                    </div>
                    
                    <!-- Daily Active Users Chart -->
                    <div class="chart-container">
                        <h3 class="chart-title">Daily Active Users</h3>
                        <div class="chart-wrapper">
                            <canvas id="activeChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- Mini Charts -->
                <div class="mini-charts">
                    <!-- Role Distribution -->
                    <div class="chart-container">
                        <h3 class="chart-title">User Roles</h3>
                        <div class="chart-wrapper">
                            <canvas id="roleChart"></canvas>
                        </div>
                    </div>
                    
                    <!-- Status Distribution -->
                    <div class="chart-container">
                        <h3 class="chart-title">Membership Status</h3>
                        <div class="chart-wrapper">
                            <canvas id="statusChart"></canvas>
                        </div>
                    </div>
                    
                    <!-- Points Distribution -->
                    <div class="chart-container">
                        <h3 class="chart-title">Points Distribution</h3>
                        <div class="chart-wrapper">
                            <canvas id="pointsChart"></canvas>
                        </div>
                    </div>
                    
                    <!-- Top Users by Points -->
                    <div class="top-users">
                        <h3 class="chart-title">Top 10 Users by Points</h3>
                        <table>
                            <thead>
                                <tr>
                                    <th>Rank</th>
                                    <th>User</th>
                                    <th>Points</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $rank = 1;
                                while($user = mysqli_fetch_assoc($top_users_result)): 
                                ?>
                                <tr>
                                    <td>
                                        <span class="<?php echo $rank <= 3 ? 'rank-'.$rank : ''; ?>">
                                            <?php echo $rank == 1 ? '🥇' : ($rank == 2 ? '🥈' : ($rank == 3 ? '🥉' : '#'.$rank)); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($user['Name']); ?></strong>
                                        <div style="font-size: 11px; color: #64748b;"><?php echo $user['Email']; ?></div>
                                    </td>
                                    <td><span class="points-badge"><?php echo number_format($user['PointsBalance']); ?> ⭐</span></td>
                                </tr>
                                <?php 
                                $rank++;
                                endwhile; 
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Registration Chart
        new Chart(document.getElementById('registrationChart'), {
            type: 'line',
            data: {
                labels: <?php echo json_encode($reg_dates); ?>,
                datasets: [{
                    label: 'New Registrations',
                    data: <?php echo json_encode($reg_counts); ?>,
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 } }
                }
            }
        });
        
        // Active Users Chart
        new Chart(document.getElementById('activeChart'), {
            type: 'line',
            data: {
                labels: <?php echo json_encode($active_dates); ?>,
                datasets: [{
                    label: 'Daily Active Users',
                    data: <?php echo json_encode($active_counts); ?>,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 } }
                }
            }
        });
        
        // Role Chart
        new Chart(document.getElementById('roleChart'), {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($role_labels); ?>,
                datasets: [{
                    data: <?php echo json_encode($role_counts); ?>,
                    backgroundColor: ['#667eea', '#10b981', '#f59e0b'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
        
        // Status Chart
        new Chart(document.getElementById('statusChart'), {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($status_labels); ?>,
                datasets: [{
                    data: <?php echo json_encode($status_counts); ?>,
                    backgroundColor: ['#10b981', '#ef4444', '#f59e0b'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
        
        // Points Distribution Chart
        new Chart(document.getElementById('pointsChart'), {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($points_labels); ?>,
                datasets: [{
                    label: 'Users',
                    data: <?php echo json_encode($points_counts); ?>,
                    backgroundColor: '#667eea',
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 } }
                }
            }
        });
    </script>
</body>
</html>