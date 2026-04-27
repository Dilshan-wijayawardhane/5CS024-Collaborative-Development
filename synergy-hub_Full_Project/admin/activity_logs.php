<?php
require_once 'middleware.php';
require_once 'config.php';
checkAdminAuth();

$admin = getAdminInfo($conn);

// Handle filters
$filter_user = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;
$filter_action = isset($_GET['action']) ? $_GET['action'] : null;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 50;
$offset = ($page - 1) * $limit;

// Get activities with filters
$activities = getUserActivities($conn, $filter_user, $filter_action, $limit, $offset);

// Get stats for dashboard
$stats = getActivityStats($conn);

// Get unique actions for filter dropdown
$actions_query = "SELECT DISTINCT Action FROM ActivityLogs ORDER BY Action";
$actions_result = mysqli_query($conn, $actions_query);

// Get users for filter dropdown
$users_query = "SELECT UserID, Name FROM Users ORDER BY Name";
$users_result = mysqli_query($conn, $users_query);

// Handle cleanup if requested
if (isset($_POST['cleanup'])) {
    $days = isset($_POST['days']) ? intval($_POST['days']) : 30;
    if (cleanupOldLogs($conn, $days)) {
        $success_msg = "Successfully cleaned up activity logs older than $days days.";
    } else {
        $error_msg = "Failed to cleanup activity logs.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Logs - Synergy Hub Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="admin.css">
    <style>
        .stats-banner {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            color: white;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .stat-box {
            text-align: center;
        }
        
        .stat-box .number {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stat-box .label {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .filters-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .filter-form {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: flex-end;
        }
        
        .filter-group {
            flex: 1;
            min-width: 150px;
        }
        
        .filter-group label {
            display: block;
            margin-bottom: 5px;
            font-size: 12px;
            color: #64748b;
            font-weight: 600;
        }
        
        .filter-group select, .filter-group input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .activity-table {
            background: white;
            border-radius: 10px;
            overflow-x: auto;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .activity-table table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .activity-table th,
        .activity-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .activity-table th {
            background: #f8fafc;
            font-weight: 600;
            color: #1e293b;
        }
        
        .activity-table tr:hover {
            background: #f8fafc;
        }
        
        .action-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .action-LOGIN { background: #10b98120; color: #10b981; }
        .action-ORDER { background: #f59e0b20; color: #f59e0b; }
        .action-CHECKIN { background: #3b82f620; color: #3b82f6; }
        .action-REGISTER { background: #8b5cf620; color: #8b5cf6; }
        .action-ADMIN_LOGIN { background: #ef444420; color: #ef4444; }
        
        .details-preview {
            max-width: 300px;
            font-size: 12px;
            color: #64748b;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
        }
        
        .pagination a {
            padding: 8px 12px;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            color: #4b5563;
            text-decoration: none;
        }
        
        .pagination a.active {
            background: #4f46e5;
            color: white;
            border-color: #4f46e5;
        }
        
        .cleanup-section {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
        }
        
        .btn-cleanup {
            background: #ef4444;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }
        
        .btn-cleanup:hover {
            background: #dc2626;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include 'includes/topbar.php'; ?>
            
            <div class="content">
                <h1 class="page-title">
                    <i class="fa-solid fa-clock-rotate-left"></i> Activity Logs
                </h1>
                
                <?php if (isset($success_msg)): ?>
                    <div class="alert alert-success"><?php echo $success_msg; ?></div>
                <?php endif; ?>
                
                <?php if (isset($error_msg)): ?>
                    <div class="alert alert-error"><?php echo $error_msg; ?></div>
                <?php endif; ?>
                
                <!-- Stats Banner -->
                <div class="stats-banner">
                    <div class="stat-box">
                        <div class="number"><?php echo $stats['today']; ?></div>
                        <div class="label">Activities Today</div>
                    </div>
                    <div class="stat-box">
                        <div class="number"><?php echo $stats['active_users']; ?></div>
                        <div class="label">Active Users Today</div>
                    </div>
                    <?php foreach($stats['by_action'] as $action => $count): ?>
                    <div class="stat-box">
                        <div class="number"><?php echo $count; ?></div>
                        <div class="label"><?php echo $action; ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Filters -->
                <div class="filters-section">
                    <form method="GET" class="filter-form">
                        <div class="filter-group">
                            <label>User</label>
                            <select name="user_id">
                                <option value="">All Users</option>
                                <?php while($user = mysqli_fetch_assoc($users_result)): ?>
                                <option value="<?php echo $user['UserID']; ?>" 
                                    <?php echo $filter_user == $user['UserID'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($user['Name']); ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label>Action</label>
                            <select name="action">
                                <option value="">All Actions</option>
                                <?php while($action = mysqli_fetch_assoc($actions_result)): ?>
                                <option value="<?php echo $action['Action']; ?>" 
                                    <?php echo $filter_action == $action['Action'] ? 'selected' : ''; ?>>
                                    <?php echo $action['Action']; ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn-primary">Apply Filters</button>
                            <a href="activity_logs.php" class="btn-secondary">Reset</a>
                        </div>
                    </form>
                </div>
                
                <!-- Activity Table -->
                <div class="activity-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>User</th>
                                <th>Action</th>
                                <th>Details</th>
                                <th>IP Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(mysqli_num_rows($activities) > 0): ?>
                                <?php while($activity = mysqli_fetch_assoc($activities)): ?>
                                <tr>
                                    <td><?php echo date('Y-m-d H:i:s', strtotime($activity['Timestamp'])); ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($activity['UserName']); ?>
                                        <br>
                                        <small style="color: #64748b;"><?php echo htmlspecialchars($activity['UserEmail']); ?></small>
                                    </td>
                                    <td>
                                        <span class="action-badge action-<?php echo $activity['Action']; ?>">
                                            <?php echo $activity['Action']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="details-preview">
                                            <?php 
                                            $details = $activity['Details'];
                                            if ($details) {
                                                $decoded = json_decode($details, true);
                                                if ($decoded && is_array($decoded)) {
                                                    echo implode(', ', array_map(function($k, $v) {
                                                        return "$k: $v";
                                                    }, array_keys($decoded), $decoded));
                                                } else {
                                                    echo htmlspecialchars($details);
                                                }
                                            } else {
                                                echo '-';
                                            }
                                            ?>
                                        </div>
                                    </td>
                                    <td><?php echo $activity['IPAddress'] ?? '-'; ?></td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 40px;">
                                        <i class="fa-regular fa-clock" style="font-size: 40px; margin-bottom: 10px;"></i>
                                        <p>No activity logs found</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Cleanup Section -->
                <div class="cleanup-section">
                    <form method="POST" style="display: inline-block;">
                        <input type="hidden" name="cleanup" value="1">
                        <select name="days" style="padding: 8px; margin-right: 10px;">
                            <option value="7">7 days</option>
                            <option value="30">30 days</option>
                            <option value="60">60 days</option>
                            <option value="90">90 days</option>
                        </select>
                        <button type="submit" class="btn-cleanup" onclick="return confirm('Are you sure you want to clean up old activity logs? This action cannot be undone.')">
                            <i class="fa-solid fa-trash"></i> Clean Old Logs
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>