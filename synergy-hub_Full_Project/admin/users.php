<?php
require_once 'middleware.php';
require_once 'config.php';
checkAdminAuth();

$admin = getAdminInfo($conn);
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Handle filters
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$role_filter = isset($_GET['role']) ? mysqli_real_escape_string($conn, $_GET['role']) : '';
$status_filter = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Build WHERE clause
$where_conditions = ["1=1"];
if ($search) {
    $where_conditions[] = "(Name LIKE '%$search%' OR Email LIKE '%$search%' OR StudentID LIKE '%$search%')";
}
if ($role_filter) {
    $where_conditions[] = "Role = '$role_filter'";
}
if ($status_filter) {
    $where_conditions[] = "MembershipStatus = '$status_filter'";
}
if ($date_from) {
    $where_conditions[] = "DATE(CreatedAt) >= '$date_from'";
}
if ($date_to) {
    $where_conditions[] = "DATE(CreatedAt) <= '$date_to'";
}
$where_sql = implode(" AND ", $where_conditions);

// Get total users count
$count_sql = "SELECT COUNT(*) as total FROM Users WHERE $where_sql";
$count_result = mysqli_query($conn, $count_sql);
$total_users = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_users / $limit);

// Get users for current page
$users_sql = "SELECT * FROM Users WHERE $where_sql ORDER BY CreatedAt DESC LIMIT $offset, $limit";
$users_result = mysqli_query($conn, $users_sql);

// Get role counts for stats
$stats_sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN Role = 'Admin' THEN 1 ELSE 0 END) as admins,
    SUM(CASE WHEN Role = 'User' THEN 1 ELSE 0 END) as regular_users,
    SUM(CASE WHEN MembershipStatus = 'Active' THEN 1 ELSE 0 END) as active_users,
    SUM(CASE WHEN MembershipStatus = 'Inactive' THEN 1 ELSE 0 END) as inactive_users
    FROM Users";
$stats_result = mysqli_query($conn, $stats_sql);
$stats = mysqli_fetch_assoc($stats_result);

// Handle bulk actions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['bulk_action'])) {
    $selected_users = $_POST['selected_users'] ?? [];
    $action = $_POST['bulk_action'];
    
    if (!empty($selected_users)) {
        $ids = implode(',', array_map('intval', $selected_users));
        
        if ($action == 'activate') {
            mysqli_query($conn, "UPDATE Users SET MembershipStatus = 'Active' WHERE UserID IN ($ids)");
            $_SESSION['success'] = "Selected users activated successfully!";
        } elseif ($action == 'deactivate') {
            mysqli_query($conn, "UPDATE Users SET MembershipStatus = 'Inactive' WHERE UserID IN ($ids)");
            $_SESSION['success'] = "Selected users deactivated successfully!";
        } elseif ($action == 'delete') {
            // Don't allow deleting admins
            mysqli_query($conn, "DELETE FROM Users WHERE UserID IN ($ids) AND Role != 'Admin'");
            $_SESSION['success'] = "Selected users deleted successfully!";
        }
        
        header("Location: users.php?" . $_SERVER['QUERY_STRING']);
        exit();
    }
}

// Get success/error messages
$success_message = $_SESSION['success'] ?? '';
$error_message = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Synergy Hub Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="admin.css">
    <style>
        /* Additional styles specific to user management */
        .activity-summary {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .activity-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
        }

        .activity-dot.active {
            background: #10b981;
            box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2);
        }

        .activity-dot.inactive {
            background: #94a3b8;
        }

        .activity-count {
            background: #f1f5f9;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            color: #475569;
        }

        .filters-section {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .filter-group label {
            font-size: 12px;
            color: #64748b;
            font-weight: 500;
        }
        
        .filter-group input,
        .filter-group select {
            padding: 8px 12px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
        }
        
        .filter-actions {
            display: flex;
            gap: 10px;
            align-items: flex-end;
        }
        
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-mini-card {
            background: white;
            border-radius: 10px;
            padding: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border-left: 4px solid #667eea;
        }
        
        .stat-mini-card .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: #1e293b;
            display: block;
        }
        
        .stat-mini-card .stat-label {
            font-size: 12px;
            color: #64748b;
        }
        
        .bulk-actions {
            background: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .bulk-actions select {
            padding: 8px 12px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            min-width: 150px;
        }
        
        .user-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 14px;
        }
        
        .role-badge {
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
        }
        
        .role-admin {
            background: #fee;
            color: #ef4444;
        }
        
        .role-user {
            background: #e0f2fe;
            color: #0284c7;
        }
        
        .status-badge {
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
        }
        
        .status-active {
            background: #dcfce7;
            color: #16a34a;
        }
        
        .status-inactive {
            background: #f1f5f9;
            color: #64748b;
        }
        
        .points-badge {
            background: #fef9c3;
            color: #ca8a04;
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 3px;
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        
        .btn-icon {
            width: 32px;
            height: 32px;
            border-radius: 6px;
            border: none;
            background: transparent;
            color: #64748b;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-icon:hover {
            background: #f1f5f9;
            color: #1e293b;
        }
        
        .btn-icon.edit:hover {
            background: #e0f2fe;
            color: #0284c7;
        }
        
        .btn-icon.delete:hover {
            background: #fee;
            color: #ef4444;
        }
        
        .btn-icon.points:hover {
            background: #fef9c3;
            color: #ca8a04;
        }
        
        .table-container {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            overflow-x: auto;
        }
        
        .user-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .user-table th {
            text-align: left;
            padding: 15px 10px;
            color: #64748b;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .user-table td {
            padding: 15px 10px;
            border-bottom: 1px solid #e2e8f0;
            color: #1e293b;
        }
        
        .user-table tr:hover td {
            background: #f8fafc;
        }
        
        .checkbox-col {
            width: 30px;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
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
        
        .import-section {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            border: 2px dashed #e2e8f0;
            text-align: center;
        }
        
        .import-section:hover {
            border-color: #667eea;
        }
        
        .import-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.show {
            display: flex;
        }
        
        .modal-content {
            background: white;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .modal-header {
            padding: 20px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-body {
            padding: 20px;
        }
        
        .modal-footer {
            padding: 20px;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        
        .activity-log {
            max-height: 300px;
            overflow-y: auto;
        }
        
        .activity-item {
            padding: 10px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 13px;
        }
        
        .activity-time {
            color: #64748b;
            font-size: 11px;
        }
        
        .tab-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .tab-btn {
            padding: 10px 20px;
            background: none;
            border: none;
            border-bottom: 2px solid transparent;
            cursor: pointer;
            color: #64748b;
            font-weight: 500;
        }
        
        .tab-btn.active {
            color: #667eea;
            border-bottom-color: #667eea;
        }
        
        .tab-pane {
            display: none;
        }
        
        .tab-pane.active {
            display: block;
        }
        
        .sample-csv {
            margin-top: 10px;
            font-size: 12px;
            color: #64748b;
        }
        
        .sample-csv a {
            color: #667eea;
            text-decoration: none;
        }

        .header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .profile-link {
            color: #667eea;
            text-decoration: none;
            margin-right: 10px;
        }

        .profile-link:hover {
            text-decoration: underline;
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
                <div class="header-actions">
                    <h1 class="page-title" style="margin-bottom: 0;">
                        <i class="fa-solid fa-users"></i> User Management
                    </h1>
                    <button class="btn btn-primary" onclick="openAddUserModal()">
                        <i class="fa-solid fa-user-plus"></i> Add New User
                    </button>
                </div>
                
                <!-- Stats Cards -->
                <div class="stats-cards">
                    <div class="stat-mini-card">
                        <span class="stat-value"><?php echo $stats['total']; ?></span>
                        <span class="stat-label">Total Users</span>
                    </div>
                    <div class="stat-mini-card">
                        <span class="stat-value"><?php echo $stats['active_users']; ?></span>
                        <span class="stat-label">Active Users</span>
                    </div>
                    <div class="stat-mini-card">
                        <span class="stat-value"><?php echo $stats['admins']; ?></span>
                        <span class="stat-label">Admins</span>
                    </div>
                    <div class="stat-mini-card">
                        <span class="stat-value"><?php echo $stats['regular_users']; ?></span>
                        <span class="stat-label">Regular Users</span>
                    </div>
                </div>
                
                <!-- Success/Error Messages -->
                <?php if ($success_message): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
                <?php endif; ?>
                <?php if ($error_message): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
                <?php endif; ?>
                
                <!-- Tab Buttons -->
                <div class="tab-buttons">
                    <button class="tab-btn active" onclick="switchTab('users')">
                        <i class="fa-solid fa-list"></i> All Users
                    </button>
                    <button class="tab-btn" onclick="switchTab('import')">
                        <i class="fa-solid fa-file-import"></i> Import Users
                    </button>
                </div>
                
                <!-- All Users Tab -->
                <div class="tab-pane active" id="tab-users">
                    <!-- Filters -->
                    <div class="filters-section">
                        <form method="GET" action="">
                            <div class="filters-grid">
                                <div class="filter-group">
                                    <label>Search</label>
                                    <input type="text" name="search" placeholder="Name, Email, Student ID" 
                                           value="<?php echo htmlspecialchars($search); ?>">
                                </div>
                                <div class="filter-group">
                                    <label>Role</label>
                                    <select name="role">
                                        <option value="">All Roles</option>
                                        <option value="Admin" <?php echo $role_filter == 'Admin' ? 'selected' : ''; ?>>Admin</option>
                                        <option value="User" <?php echo $role_filter == 'User' ? 'selected' : ''; ?>>User</option>
                                    </select>
                                </div>
                                <div class="filter-group">
                                    <label>Status</label>
                                    <select name="status">
                                        <option value="">All Status</option>
                                        <option value="Active" <?php echo $status_filter == 'Active' ? 'selected' : ''; ?>>Active</option>
                                        <option value="Inactive" <?php echo $status_filter == 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                                    </select>
                                </div>
                                <div class="filter-group">
                                    <label>From Date</label>
                                    <input type="date" name="date_from" value="<?php echo $date_from; ?>">
                                </div>
                                <div class="filter-group">
                                    <label>To Date</label>
                                    <input type="date" name="date_to" value="<?php echo $date_to; ?>">
                                </div>
                                <div class="filter-actions">
                                    <button type="submit" class="btn btn-primary btn-sm">
                                        <i class="fa-solid fa-filter"></i> Apply Filters
                                    </button>
                                    <a href="users.php" class="btn btn-secondary btn-sm">
                                        <i class="fa-solid fa-rotate"></i> Reset
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Bulk Actions -->
                    <form method="POST" id="bulkActionForm">
                        <div class="bulk-actions">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <input type="checkbox" id="selectAll" onclick="toggleSelectAll()">
                                <span>Select All</span>
                            </div>
                            <select name="bulk_action" required>
                                <option value="">Bulk Actions</option>
                                <option value="activate">Activate Selected</option>
                                <option value="deactivate">Deactivate Selected</option>
                                <option value="delete" onclick="return confirm('Are you sure?')">Delete Selected</option>
                            </select>
                            <button type="submit" class="btn btn-primary btn-sm">Apply</button>
                        </div>
                        
                        <!-- Users Table -->
                        <div class="table-container">
                            <table class="user-table">
                                <thead>
                                    <tr>
                                        <th class="checkbox-col"></th>
                                        <th>User</th>
                                        <th>Student ID</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        <th>Points</th>
                                        <th>Activity</th>
                                        <th>Joined</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (mysqli_num_rows($users_result) > 0): ?>
                                        <?php while($user = mysqli_fetch_assoc($users_result)): 
                                            // Check if user was active today
                                            $active_today_sql = "SELECT COUNT(*) as count FROM ActivityLogs WHERE UserID = ? AND DATE(Timestamp) = CURDATE()";
                                            $active_today_stmt = mysqli_prepare($conn, $active_today_sql);
                                            mysqli_stmt_bind_param($active_today_stmt, "i", $user['UserID']);
                                            mysqli_stmt_execute($active_today_stmt);
                                            $active_today_result = mysqli_stmt_get_result($active_today_stmt);
                                            $active_today = mysqli_fetch_assoc($active_today_result)['count'] > 0;
                                            
                                            // Get total activity count
                                            $total_activity_sql = "SELECT COUNT(*) as count FROM ActivityLogs WHERE UserID = ?";
                                            $total_activity_stmt = mysqli_prepare($conn, $total_activity_sql);
                                            mysqli_stmt_bind_param($total_activity_stmt, "i", $user['UserID']);
                                            mysqli_stmt_execute($total_activity_stmt);
                                            $total_activity_result = mysqli_stmt_get_result($total_activity_stmt);
                                            $total_activity = mysqli_fetch_assoc($total_activity_result)['count'] ?: 0;
                                        ?>
                                        <tr>
                                            <td>
                                                <input type="checkbox" name="selected_users[]" value="<?php echo $user['UserID']; ?>" 
                                                       <?php echo $user['Role'] == 'Admin' ? 'disabled' : ''; ?>>
                                            </td>
                                            <td>
                                                <div class="user-info">
                                                    <div class="user-avatar">
                                                        <?php echo strtoupper(substr($user['Name'], 0, 2)); ?>
                                                    </div>
                                                    <span><?php echo htmlspecialchars($user['Name']); ?></span>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($user['StudentID'] ?: 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($user['Email']); ?></td>
                                            <td>
                                                <span class="role-badge role-<?php echo strtolower($user['Role']); ?>">
                                                    <?php echo $user['Role']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="status-badge status-<?php echo strtolower($user['MembershipStatus']); ?>">
                                                    <?php echo $user['MembershipStatus']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="points-badge">
                                                    <i class="fa-solid fa-star"></i> <?php echo $user['PointsBalance']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="activity-summary">
                                                    <div class="activity-dot <?php echo $active_today ? 'active' : 'inactive'; ?>" 
                                                         title="<?php echo $active_today ? 'Active today' : 'No activity today'; ?>">
                                                    </div>
                                                    <span class="activity-count" title="Total activities: <?php echo $total_activity; ?>">
                                                        <?php echo $total_activity; ?>
                                                    </span>
                                                </div>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($user['CreatedAt'])); ?></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="user_profile.php?id=<?php echo $user['UserID']; ?>" class="btn-icon" title="View Profile">
                                                        <i class="fa-regular fa-user"></i>
                                                    </a>
                                                    <button class="btn-icon edit" onclick="editUser(<?php echo $user['UserID']; ?>)" title="Edit User">
                                                        <i class="fa-regular fa-pen-to-square"></i>
                                                    </button>
                                                    <button class="btn-icon points" onclick="managePoints(<?php echo $user['UserID']; ?>, '<?php echo addslashes($user['Name']); ?>', <?php echo $user['PointsBalance']; ?>)" title="Manage Points">
                                                        <i class="fa-solid fa-star"></i>
                                                    </button>
                                                    <button class="btn-icon" onclick="viewActivity(<?php echo $user['UserID']; ?>)" title="View Activity">
                                                        <i class="fa-regular fa-clock"></i>
                                                    </button>
                                                    <?php if($user['Role'] != 'Admin'): ?>
                                                    <button class="btn-icon delete" onclick="deleteUser(<?php echo $user['UserID']; ?>)" title="Delete User">
                                                        <i class="fa-regular fa-trash-can"></i>
                                                    </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="10" style="text-align: center; padding: 40px; color: #64748b;">
                                                <i class="fa-regular fa-face-frown" style="font-size: 40px; margin-bottom: 10px; display: block;"></i>
                                                No users found
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </form>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo $role_filter; ?>&status=<?php echo $status_filter; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>">
                            <i class="fa-solid fa-chevron-left"></i>
                        </a>
                        <?php endif; ?>
                        
                        <?php for($i = 1; $i <= $total_pages; $i++): ?>
                            <?php if ($i == $page): ?>
                                <span class="active"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo $role_filter; ?>&status=<?php echo $status_filter; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo $role_filter; ?>&status=<?php echo $status_filter; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>">
                            <i class="fa-solid fa-chevron-right"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Import Users Tab -->
                <div class="tab-pane" id="tab-import">
                    <div class="import-section">
                        <h3 style="margin-bottom: 20px; color: #1e293b;">
                            <i class="fa-solid fa-cloud-arrow-up"></i> Import Users from CSV
                        </h3>
                        
                        <form action="import_users.php" method="POST" enctype="multipart/form-data">
                            <div style="margin-bottom: 20px;">
                                <input type="file" name="csv_file" accept=".csv" required style="display: none;" id="csvFile">
                                <button type="button" class="import-btn" onclick="document.getElementById('csvFile').click()">
                                    <i class="fa-solid fa-file-csv"></i> Choose CSV File
                                </button>
                                <span id="fileName" style="margin-left: 10px; color: #64748b;"></span>
                            </div>
                            
                            <div style="text-align: left; background: #f8fafc; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                                <h4 style="margin-bottom: 10px; color: #1e293b;">CSV Format Requirements:</h4>
                                <p style="color: #64748b; font-size: 13px; margin-bottom: 5px;">
                                    Your CSV file should have these columns:
                                </p>
                                <code style="background: #1e293b; color: #fff; padding: 10px; display: block; border-radius: 5px; margin: 10px 0;">
                                    StudentID,Name,Email,Password,Role,MembershipStatus,PointsBalance
                                </code>
                                <p style="color: #64748b; font-size: 12px; margin-top: 10px;">
                                    <i class="fa-solid fa-circle-info"></i> Password will be automatically hashed. Leave PointsBalance as 0 for new users.
                                </p>
                            </div>
                            
                            <div class="sample-csv">
                                <a href="sample_users.csv" download>
                                    <i class="fa-solid fa-download"></i> Download Sample CSV Template
                                </a>
                            </div>
                            
                            <button type="submit" class="btn btn-primary" style="margin-top: 20px;">
                                <i class="fa-solid fa-upload"></i> Import Users
                            </button>
                        </form>
                    </div>
                    
                    <!-- Import History -->
                    <div style="background: white; border-radius: 12px; padding: 20px; margin-top: 20px;">
                        <h4 style="margin-bottom: 15px; color: #1e293b;">Recent Import History</h4>
                        <?php
                        // Fixed: Removed non-existent import_type column
                        $import_sql = "SELECT * FROM ImportHistory ORDER BY ImportedAt DESC LIMIT 5";
                        $import_result = mysqli_query($conn, $import_sql);
                        ?>
                        <?php if($import_result && mysqli_num_rows($import_result) > 0): ?>
                        <table class="user-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>File Name</th>
                                    <th>Records Imported</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($import = mysqli_fetch_assoc($import_result)): ?>
                                <tr>
                                    <td><?php echo date('M d, Y h:i A', strtotime($import['ImportedAt'])); ?></td>
                                    <td><?php echo htmlspecialchars($import['FileName']); ?></td>
                                    <td><?php echo $import['RecordsImported']; ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower($import['Status']); ?>">
                                            <?php echo $import['Status']; ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                        <?php else: ?>
                        <p style="color: #64748b; text-align: center; padding: 20px;">No import history</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Edit User Modal -->
    <div class="modal" id="editUserModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fa-regular fa-pen-to-square"></i> Edit User</h3>
                <button class="modal-close" onclick="closeModal('editUserModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form id="editUserForm" method="POST" action="update_user.php">
                    <input type="hidden" name="user_id" id="edit_user_id">
                    
                    <div class="form-group">
                        <label>Name</label>
                        <input type="text" name="name" id="edit_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" id="edit_email" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Student ID</label>
                        <input type="text" name="student_id" id="edit_student_id">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Role</label>
                            <select name="role" id="edit_role">
                                <option value="User">User</option>
                                <option value="Admin">Admin</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" id="edit_status">
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Reset Password</label>
                        <input type="password" name="new_password" placeholder="Leave blank to keep current password">
                        <small style="color: #64748b;">Enter new password only if you want to change it</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('editUserModal')">Cancel</button>
                <button class="btn btn-primary" onclick="document.getElementById('editUserForm').submit()">Save Changes</button>
            </div>
        </div>
    </div>
    
    <!-- Manage Points Modal -->
    <div class="modal" id="pointsModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fa-solid fa-star"></i> Manage Points</h3>
                <button class="modal-close" onclick="closeModal('pointsModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form id="pointsForm" method="POST" action="update_points.php">
                    <input type="hidden" name="user_id" id="points_user_id">
                    
                    <div class="form-group">
                        <label>User</label>
                        <input type="text" id="points_user_name" readonly style="background: #f1f5f9;">
                    </div>
                    
                    <div class="form-group">
                        <label>Current Points</label>
                        <input type="text" id="current_points" readonly style="background: #f1f5f9;">
                    </div>
                    
                    <div class="form-group">
                        <label>Action</label>
                        <select name="action" id="points_action" onchange="togglePointsInput()">
                            <option value="add">Add Points</option>
                            <option value="deduct">Deduct Points</option>
                            <option value="set">Set Exact Value</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Points</label>
                        <input type="number" name="points" id="points_amount" min="1" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Reason (Optional)</label>
                        <textarea name="reason" rows="2" placeholder="e.g., Activity reward, purchase, etc."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('pointsModal')">Cancel</button>
                <button class="btn btn-primary" onclick="document.getElementById('pointsForm').submit()">Update Points</button>
            </div>
        </div>
    </div>
    
    <!-- Activity Log Modal -->
    <div class="modal" id="activityModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fa-regular fa-clock"></i> User Activity</h3>
                <button class="modal-close" onclick="closeModal('activityModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="activity-log" id="activityLog">
                    <div class="loading-spinner">Loading...</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add User Modal (NEW) -->
    <div class="modal" id="addUserModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fa-solid fa-user-plus"></i> Add New User</h3>
                <button class="modal-close" onclick="closeModal('addUserModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form id="addUserForm" method="POST" action="add_user.php">
                    <div class="form-group">
                        <label>Full Name <span style="color: #ef4444;">*</span></label>
                        <input type="text" name="name" id="add_name" required placeholder="Enter full name">
                    </div>
                    
                    <div class="form-group">
                        <label>Email Address <span style="color: #ef4444;">*</span></label>
                        <input type="email" name="email" id="add_email" required placeholder="user@example.com">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Student ID</label>
                            <input type="text" name="student_id" id="add_student_id" placeholder="e.g., STU001">
                        </div>
                        
                        <div class="form-group">
                            <label>Password <span style="color: #ef4444;">*</span></label>
                            <input type="password" name="password" id="add_password" required placeholder="••••••••">
                            <small style="color: #64748b;">Min. 6 characters</small>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Role</label>
                            <select name="role" id="add_role">
                                <option value="User" selected>User</option>
                                <option value="Admin">Admin</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" id="add_status">
                                <option value="Active" selected>Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Initial Points</label>
                        <input type="number" name="points" id="add_points" value="0" min="0">
                    </div>
                    
                    <div class="form-group">
                        <label>Send Welcome Email</label>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <input type="checkbox" name="send_email" id="send_email" checked>
                            <label for="send_email" style="margin: 0;">Send login credentials to user's email</label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('addUserModal')">Cancel</button>
                <button class="btn btn-primary" onclick="document.getElementById('addUserForm').submit()">
                    <i class="fa-solid fa-save"></i> Create User
                </button>
            </div>
        </div>
    </div>
    
    <script>
        // Tab switching
        function switchTab(tab) {
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.tab-pane').forEach(pane => pane.classList.remove('active'));
            
            if (tab === 'users') {
                document.querySelector('.tab-btn').classList.add('active');
                document.getElementById('tab-users').classList.add('active');
            } else {
                document.querySelectorAll('.tab-btn')[1].classList.add('active');
                document.getElementById('tab-import').classList.add('active');
            }
        }
        
        // Select all checkbox
        function toggleSelectAll() {
            const checkboxes = document.querySelectorAll('input[name="selected_users[]"]:not(:disabled)');
            const selectAll = document.getElementById('selectAll');
            checkboxes.forEach(cb => cb.checked = selectAll.checked);
        }
        
        // Modal functions
        function openModal(modalId) {
            document.getElementById(modalId).classList.add('show');
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('show');
        }
        
        // Open Add User Modal
        function openAddUserModal() {
            document.getElementById('addUserModal').classList.add('show');
            document.getElementById('addUserForm').reset();
            document.getElementById('add_points').value = '0';
            document.getElementById('send_email').checked = true;
        }
        
        // Edit user
        function editUser(userId) {
            fetch('get_user.php?id=' + userId)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('edit_user_id').value = data.UserID;
                    document.getElementById('edit_name').value = data.Name;
                    document.getElementById('edit_email').value = data.Email;
                    document.getElementById('edit_student_id').value = data.StudentID || '';
                    document.getElementById('edit_role').value = data.Role;
                    document.getElementById('edit_status').value = data.MembershipStatus;
                    openModal('editUserModal');
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading user data');
                });
        }
        
        // Manage points
        function managePoints(userId, userName, currentPoints) {
            document.getElementById('points_user_id').value = userId;
            document.getElementById('points_user_name').value = userName;
            document.getElementById('current_points').value = currentPoints + ' points';
            document.getElementById('points_amount').value = '';
            openModal('pointsModal');
        }
        
        function togglePointsInput() {
            const action = document.getElementById('points_action').value;
            const input = document.getElementById('points_amount');
            if (action === 'set') {
                input.placeholder = 'New point balance';
            } else {
                input.placeholder = 'Amount to ' + action;
            }
        }
        
        // View activity
        function viewActivity(userId) {
            openModal('activityModal');
            document.getElementById('activityLog').innerHTML = '<div style="text-align: center; padding: 20px;"><i class="fa-solid fa-spinner fa-spin"></i> Loading...</div>';
            
            fetch('get_user_activity.php?user_id=' + userId)
                .then(response => response.json())
                .then(data => {
                    let html = '';
                    if (data.activities && data.activities.length > 0) {
                        data.activities.forEach(activity => {
                            html += `
                                <div class="activity-item">
                                    <div><strong>${activity.Action}</strong></div>
                                    <div>${activity.Details || ''}</div>
                                    <div class="activity-time">
                                        <i class="fa-regular fa-clock"></i> ${activity.Timestamp}
                                    </div>
                                </div>
                            `;
                        });
                    } else {
                        html = '<div style="text-align: center; padding: 20px; color: #64748b;">No activity found</div>';
                    }
                    document.getElementById('activityLog').innerHTML = html;
                })
                .catch(error => {
                    document.getElementById('activityLog').innerHTML = '<div style="text-align: center; padding: 20px; color: #ef4444;">Error loading activity</div>';
                });
        }
        
        // Delete user
        function deleteUser(userId) {
            if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
                window.location.href = 'delete_user.php?id=' + userId;
            }
        }
        
        // File name display
        document.getElementById('csvFile').addEventListener('change', function(e) {
            document.getElementById('fileName').textContent = e.target.files[0].name;
        });
        
        // Close modals when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('show');
            }
        }
    </script>
</body>
</html>