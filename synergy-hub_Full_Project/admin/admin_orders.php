<?php
require_once 'config.php';
checkAdminAuth();

$admin = getAdminInfo($conn);
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Handle filters
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$status_filter = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : '';
$category_filter = isset($_GET['category']) ? mysqli_real_escape_string($conn, $_GET['category']) : '';
$date_filter = isset($_GET['date_filter']) ? $_GET['date_filter'] : 'today';
$custom_date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$custom_date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// Build date condition
$date_condition = "";
if ($date_filter == 'today') {
    $date_condition = "DATE(o.Timestamp) = CURDATE()";
} elseif ($date_filter == 'week') {
    $date_condition = "o.Timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
} elseif ($date_filter == 'month') {
    $date_condition = "o.Timestamp >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
} elseif ($date_filter == 'custom' && $custom_date_from && $custom_date_to) {
    $date_condition = "DATE(o.Timestamp) BETWEEN '$custom_date_from' AND '$custom_date_to'";
}

// Build WHERE clause
$where_conditions = ["1=1"];
if ($date_condition) {
    $where_conditions[] = $date_condition;
}
if ($search) {
    $where_conditions[] = "(o.OrderID LIKE '%$search%' OR u.Name LIKE '%$search%' OR o.ItemName LIKE '%$search%')";
}
if ($status_filter) {
    $where_conditions[] = "o.Status = '$status_filter'";
}
if ($category_filter) {
    $where_conditions[] = "o.Category = '$category_filter'";
}
$where_sql = implode(" AND ", $where_conditions);

// Build ORDER BY
$order_by = "o.Timestamp DESC";
if ($sort_by == 'price_asc') $order_by = "o.Price ASC";
elseif ($sort_by == 'price_desc') $order_by = "o.Price DESC";
elseif ($sort_by == 'status') $order_by = "FIELD(o.Status, 'Pending', 'Preparing', 'Ready', 'Completed', 'Cancelled')";

// Get total orders count
$count_sql = "SELECT COUNT(*) as total FROM Orders o JOIN Users u ON o.UserID = u.UserID WHERE $where_sql";
$count_result = mysqli_query($conn, $count_sql);
$total_orders = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_orders / $limit);

// Get orders for current page
$orders_sql = "SELECT o.*, u.Name as CustomerName, u.Email 
               FROM Orders o 
               JOIN Users u ON o.UserID = u.UserID 
               WHERE $where_sql 
               ORDER BY $order_by 
               LIMIT $offset, $limit";
$orders_result = mysqli_query($conn, $orders_sql);

// Get statistics
$stats_sql = "SELECT 
                COUNT(CASE WHEN DATE(Timestamp) = CURDATE() THEN 1 END) as total_today,
                COUNT(CASE WHEN Status = 'Pending' THEN 1 END) as pending_count,
                COUNT(CASE WHEN Status = 'Preparing' THEN 1 END) as preparing_count,
                COUNT(CASE WHEN Status = 'Ready' THEN 1 END) as ready_count,
                COUNT(CASE WHEN Status = 'Completed' AND DATE(Timestamp) = CURDATE() THEN 1 END) as completed_today,
                SUM(CASE WHEN Status = 'Completed' AND DATE(Timestamp) = CURDATE() THEN Price * Quantity ELSE 0 END) as revenue_today,
                AVG(Price * Quantity) as avg_order_value
              FROM Orders o
              WHERE DATE(Timestamp) = CURDATE()";
$stats_result = mysqli_query($conn, $stats_sql);
$stats = mysqli_fetch_assoc($stats_result);

// Get most popular item today
$popular_sql = "SELECT ItemName, SUM(Quantity) as total_qty 
                FROM Orders 
                WHERE DATE(Timestamp) = CURDATE() 
                GROUP BY ItemName 
                ORDER BY total_qty DESC 
                LIMIT 1";
$popular_result = mysqli_query($conn, $popular_sql);
$popular = mysqli_fetch_assoc($popular_result);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management - Synergy Hub Admin</title>
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
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 15px;
            transition: all 0.3s;
        }
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,0.1); }
        .stat-icon {
            width: 55px;
            height: 55px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }
        .stat-icon.blue { background: linear-gradient(135deg, #3b82f6, #1d4ed8); }
        .stat-icon.orange { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .stat-icon.cyan { background: linear-gradient(135deg, #06b6d4, #0891b2); }
        .stat-icon.green { background: linear-gradient(135deg, #10b981, #059669); }
        .stat-icon.purple { background: linear-gradient(135deg, #8b5cf6, #6d28d9); }
        .stat-icon.yellow { background: linear-gradient(135deg, #eab308, #ca8a04); }
        .stat-icon.pink { background: linear-gradient(135deg, #ec4899, #db2777); }
        .stat-info h3 { font-size: 28px; color: #1e293b; margin: 0; }
        .stat-info p { color: #64748b; margin: 5px 0 0; font-size: 14px; }
        .stat-change { font-size: 12px; margin-top: 5px; }
        .stat-change.positive { color: #10b981; }
        
        .filter-bar {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: flex-end;
        }
        .filter-group { display: flex; flex-direction: column; gap: 5px; }
        .filter-group label { font-size: 12px; color: #64748b; font-weight: 500; }
        .filter-group input, .filter-group select {
            padding: 8px 12px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            min-width: 150px;
        }
        .filter-actions { display: flex; gap: 10px; }
        
        .bulk-actions {
            background: white;
            border-radius: 12px;
            padding: 15px 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .order-table {
            width: 100%;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .order-table th {
            background: #f8fafc;
            padding: 15px;
            text-align: left;
            font-size: 12px;
            color: #64748b;
            font-weight: 600;
            text-transform: uppercase;
        }
        .order-table td {
            padding: 15px;
            border-bottom: 1px solid #e2e8f0;
            color: #1e293b;
        }
        .order-table tr:hover td { background: #f8fafc; }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        .status-Pending { background: #fff7ed; color: #ea580c; }
        .status-Preparing { background: #e0f2fe; color: #0284c7; }
        .status-Ready { background: #dcfce7; color: #16a34a; }
        .status-Completed { background: #f3e8ff; color: #9333ea; }
        .status-Cancelled { background: #fee; color: #ef4444; }
        
        .order-id {
            color: #667eea;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
        }
        .order-id:hover { text-decoration: underline; }
        
        .status-select {
            padding: 6px 10px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            background: white;
            font-size: 13px;
            cursor: pointer;
        }
        
        .btn-sm { padding: 6px 12px; font-size: 12px; }
        .btn-icon {
            width: 32px;
            height: 32px;
            border-radius: 6px;
            border: none;
            background: transparent;
            color: #64748b;
            cursor: pointer;
        }
        .btn-icon:hover { background: #f1f5f9; color: #1e293b; }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 5px;
            margin-top: 20px;
        }
        .pagination a, .pagination span {
            padding: 8px 12px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            color: #475569;
            text-decoration: none;
        }
        .pagination a:hover { background: #f1f5f9; }
        .pagination .active { background: #667eea; color: white; border-color: #667eea; }
        
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
        .modal.show { display: flex; }
        .modal-content {
            background: white;
            border-radius: 16px;
            width: 90%;
            max-width: 800px;
            max-height: 85vh;
            overflow-y: auto;
        }
        .modal-header {
            padding: 20px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-body { padding: 20px; }
        .modal-footer { padding: 20px; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 10px; }
        
        .timeline {
            position: relative;
            padding-left: 30px;
            margin: 20px 0;
        }
        .timeline::before {
            content: '';
            position: absolute;
            left: 10px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #e2e8f0;
        }
        .timeline-item {
            position: relative;
            padding-bottom: 20px;
        }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -24px;
            top: 5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: white;
            border: 2px solid #667eea;
        }
        .timeline-status { font-weight: 600; color: #1e293b; }
        .timeline-time { font-size: 12px; color: #64748b; }
        
        .auto-refresh {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: white;
            padding: 10px 15px;
            border-radius: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 10px;
            z-index: 100;
        }
        
        .checkbox-col { width: 30px; }
        
        @media (max-width: 768px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .filter-bar { flex-direction: column; }
            .filter-group input, .filter-group select { width: 100%; }
            .order-table { font-size: 12px; }
            .order-table th, .order-table td { padding: 10px; }
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
                    <i class="fa-solid fa-cart-shopping"></i> Order Management
                </h1>
                
                <!-- Statistics Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon blue"><i class="fa-solid fa-chart-line"></i></div>
                        <div class="stat-info">
                            <h3><?php echo number_format($stats['total_today']); ?></h3>
                            <p>Total Orders Today</p>
                            <div class="stat-change positive"><i class="fa-solid fa-arrow-up"></i> +<?php echo $stats['total_today']; ?> today</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon orange"><i class="fa-solid fa-hourglass-half"></i></div>
                        <div class="stat-info">
                            <h3><?php echo $stats['pending_count']; ?></h3>
                            <p>Pending Orders</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon cyan"><i class="fa-solid fa-gear"></i></div>
                        <div class="stat-info">
                            <h3><?php echo $stats['preparing_count']; ?></h3>
                            <p>Preparing</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon green"><i class="fa-solid fa-check-circle"></i></div>
                        <div class="stat-info">
                            <h3><?php echo $stats['ready_count']; ?></h3>
                            <p>Ready for Pickup</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon purple"><i class="fa-solid fa-circle-check"></i></div>
                        <div class="stat-info">
                            <h3><?php echo $stats['completed_today']; ?></h3>
                            <p>Completed Today</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon yellow"><i class="fa-solid fa-dollar-sign"></i></div>
                        <div class="stat-info">
                            <h3>Rs. <?php echo number_format($stats['revenue_today'], 2); ?></h3>
                            <p>Revenue Today</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon pink"><i class="fa-solid fa-chart-simple"></i></div>
                        <div class="stat-info">
                            <h3>Rs. <?php echo number_format($stats['avg_order_value'], 2); ?></h3>
                            <p>Avg Order Value</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon blue"><i class="fa-solid fa-fire"></i></div>
                        <div class="stat-info">
                            <h3><?php echo htmlspecialchars($popular['ItemName'] ?? 'N/A'); ?></h3>
                            <p>Most Popular Item</p>
                            <div class="stat-change"><?php echo $popular['total_qty'] ?? 0; ?> sold today</div>
                        </div>
                    </div>
                </div>
                
                <!-- Filter Bar -->
                <div class="filter-bar">
                    <div class="filter-group">
                        <label><i class="fa-solid fa-search"></i> Search</label>
                        <input type="text" id="searchInput" placeholder="Order ID, Customer, Item..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="filter-group">
                        <label><i class="fa-solid fa-filter"></i> Status</label>
                        <select id="statusFilter">
                            <option value="">All Status</option>
                            <option value="Pending" <?php echo $status_filter == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="Preparing" <?php echo $status_filter == 'Preparing' ? 'selected' : ''; ?>>Preparing</option>
                            <option value="Ready" <?php echo $status_filter == 'Ready' ? 'selected' : ''; ?>>Ready</option>
                            <option value="Completed" <?php echo $status_filter == 'Completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="Cancelled" <?php echo $status_filter == 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label><i class="fa-solid fa-tag"></i> Category</label>
                        <select id="categoryFilter">
                            <option value="">All Categories</option>
                            <option value="Food" <?php echo $category_filter == 'Food' ? 'selected' : ''; ?>>Food</option>
                            <option value="Beverage" <?php echo $category_filter == 'Beverage' ? 'selected' : ''; ?>>Beverage</option>
                            <option value="Dessert" <?php echo $category_filter == 'Dessert' ? 'selected' : ''; ?>>Dessert</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label><i class="fa-regular fa-calendar"></i> Date Range</label>
                        <select id="dateFilter">
                            <option value="today" <?php echo $date_filter == 'today' ? 'selected' : ''; ?>>Today</option>
                            <option value="week" <?php echo $date_filter == 'week' ? 'selected' : ''; ?>>Last 7 Days</option>
                            <option value="month" <?php echo $date_filter == 'month' ? 'selected' : ''; ?>>This Month</option>
                            <option value="custom" <?php echo $date_filter == 'custom' ? 'selected' : ''; ?>>Custom Range</option>
                        </select>
                    </div>
                    <div class="filter-group" id="customDateRange" style="display: none;">
                        <label>From</label>
                        <input type="date" id="dateFrom" value="<?php echo $custom_date_from; ?>">
                        <label>To</label>
                        <input type="date" id="dateTo" value="<?php echo $custom_date_to; ?>">
                    </div>
                    <div class="filter-group">
                        <label><i class="fa-solid fa-sort"></i> Sort By</label>
                        <select id="sortBy">
                            <option value="newest" <?php echo $sort_by == 'newest' ? 'selected' : ''; ?>>Newest First</option>
                            <option value="price_asc" <?php echo $sort_by == 'price_asc' ? 'selected' : ''; ?>>Price: Low to High</option>
                            <option value="price_desc" <?php echo $sort_by == 'price_desc' ? 'selected' : ''; ?>>Price: High to Low</option>
                            <option value="status" <?php echo $sort_by == 'status' ? 'selected' : ''; ?>>By Status</option>
                        </select>
                    </div>
                    <div class="filter-actions">
                        <button class="btn btn-primary btn-sm" onclick="applyFilters()"><i class="fa-solid fa-filter"></i> Apply</button>
                        <button class="btn btn-secondary btn-sm" onclick="resetFilters()"><i class="fa-solid fa-rotate"></i> Reset</button>
                        <button class="btn btn-secondary btn-sm" onclick="exportOrders()"><i class="fa-solid fa-download"></i> Export</button>
                    </div>
                </div>
                
                <!-- Bulk Actions -->
                <div class="bulk-actions">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <input type="checkbox" id="selectAll" onclick="toggleSelectAll()">
                        <span>Select All</span>
                    </div>
                    <select id="bulkStatus">
                        <option value="">Bulk Update Status</option>
                        <option value="Preparing">Mark as Preparing</option>
                        <option value="Ready">Mark as Ready</option>
                        <option value="Completed">Mark as Completed</option>
                        <option value="Cancelled">Mark as Cancelled</option>
                    </select>
                    <button class="btn btn-primary btn-sm" onclick="bulkUpdateStatus()">Apply</button>
                    <button class="btn btn-secondary btn-sm" onclick="exportSelected()"><i class="fa-solid fa-download"></i> Export Selected</button>
                    <div class="auto-refresh" style="position: static; margin-left: auto;">
                        <i class="fa-solid fa-rotate-right"></i>
                        <label class="toggle-switch">
                            <input type="checkbox" id="autoRefreshToggle">
                            <span class="toggle-slider"></span>
                        </label>
                        <span>Auto-refresh (30s)</span>
                    </div>
                </div>
                
                <!-- Orders Table -->
                <div class="table-container">
                    <table class="order-table">
                        <thead>
                            <tr>
                                <th class="checkbox-col"></th>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Item</th>
                                <th>Category</th>
                                <th>Qty</th>
                                <th>Price</th>
                                <th>Total</th>
                                <th>Time</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="ordersTableBody">
                            <?php if(mysqli_num_rows($orders_result) > 0): ?>
                                <?php while($order = mysqli_fetch_assoc($orders_result)): ?>
                                <tr>
                                    <td class="checkbox-col">
                                        <input type="checkbox" class="order-checkbox" value="<?php echo $order['OrderID']; ?>">
                                    </td>
                                    <td><a href="#" class="order-id" onclick="viewOrderDetails(<?php echo $order['OrderID']; ?>); return false;">#<?php echo $order['OrderID']; ?></a></td>
                                    <td><?php echo htmlspecialchars($order['CustomerName']); ?></td>
                                    <td><?php echo htmlspecialchars($order['ItemName']); ?></td>
                                    <td><span class="status-badge status-<?php echo $order['Category']; ?>"><?php echo $order['Category']; ?></span></td>
                                    <td><?php echo $order['Quantity']; ?></td>
                                    <td>Rs. <?php echo number_format($order['Price'], 2); ?></td>
                                    <td>Rs. <?php echo number_format($order['Price'] * $order['Quantity'], 2); ?></td>
                                    <td><?php echo date('h:i A', strtotime($order['Timestamp'])); ?></td>
                                    <td>
                                        <select class="status-select" data-order-id="<?php echo $order['OrderID']; ?>" onchange="updateOrderStatus(this)">
                                            <option value="Pending" <?php echo $order['Status'] == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="Preparing" <?php echo $order['Status'] == 'Preparing' ? 'selected' : ''; ?>>Preparing</option>
                                            <option value="Ready" <?php echo $order['Status'] == 'Ready' ? 'selected' : ''; ?>>Ready</option>
                                            <option value="Completed" <?php echo $order['Status'] == 'Completed' ? 'selected' : ''; ?>>Completed</option>
                                            <option value="Cancelled" <?php echo $order['Status'] == 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                        </select>
                                    </td>
                                    <td>
                                        <button class="btn-icon" onclick="viewOrderDetails(<?php echo $order['OrderID']; ?>)" title="View Details">
                                            <i class="fa-regular fa-eye"></i>
                                        </button>
                                        <button class="btn-icon" onclick="printOrder(<?php echo $order['OrderID']; ?>)" title="Print">
                                            <i class="fa-solid fa-print"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="11" style="text-align: center; padding: 40px; color: #64748b;">
                                        <i class="fa-regular fa-receipt" style="font-size: 40px; margin-bottom: 10px;"></i>
                                        <p>No orders found</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if($total_pages > 1): ?>
                <div class="pagination" id="pagination">
                    <?php if($page > 1): ?>
                    <a href="?page=<?php echo $page-1; ?>&status=<?php echo urlencode($status_filter); ?>&search=<?php echo urlencode($search); ?>">
                        <i class="fa-solid fa-chevron-left"></i>
                    </a>
                    <?php endif; ?>
                    <?php for($i = 1; $i <= $total_pages; $i++): ?>
                        <?php if($i == $page): ?>
                            <span class="active"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?page=<?php echo $i; ?>&status=<?php echo urlencode($status_filter); ?>&search=<?php echo urlencode($search); ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    <?php if($page < $total_pages): ?>
                    <a href="?page=<?php echo $page+1; ?>&status=<?php echo urlencode($status_filter); ?>&search=<?php echo urlencode($search); ?>">
                        <i class="fa-solid fa-chevron-right"></i>
                    </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Order Details Modal -->
    <div class="modal" id="orderModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fa-solid fa-receipt"></i> Order Details #<span id="modalOrderId"></span></h3>
                <button class="modal-close" onclick="closeModal('orderModal')">&times;</button>
            </div>
            <div class="modal-body" id="orderModalBody">
                <div class="loading-spinner">Loading...</div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="printOrderFromModal()"><i class="fa-solid fa-print"></i> Print Receipt</button>
                <button class="btn btn-primary" onclick="sendMessageToCustomer()"><i class="fa-regular fa-message"></i> Send Message</button>
                <button class="btn btn-secondary" onclick="closeModal('orderModal')">Close</button>
            </div>
        </div>
    </div>
    
    <script>
        // Auto-refresh
        let autoRefreshInterval = null;
        const autoRefreshToggle = document.getElementById('autoRefreshToggle');
        
        if(autoRefreshToggle) {
            autoRefreshToggle.addEventListener('change', function() {
                if(this.checked) {
                    autoRefreshInterval = setInterval(() => {
                        location.reload();
                    }, 30000);
                } else {
                    if(autoRefreshInterval) clearInterval(autoRefreshInterval);
                }
            });
        }
        
        // Apply filters
        function applyFilters() {
            const search = document.getElementById('searchInput').value;
            const status = document.getElementById('statusFilter').value;
            const category = document.getElementById('categoryFilter').value;
            const dateFilter = document.getElementById('dateFilter').value;
            const sort = document.getElementById('sortBy').value;
            let url = `admin_orders.php?page=1&search=${encodeURIComponent(search)}&status=${status}&category=${category}&date_filter=${dateFilter}&sort=${sort}`;
            
            if(dateFilter === 'custom') {
                const dateFrom = document.getElementById('dateFrom').value;
                const dateTo = document.getElementById('dateTo').value;
                if(dateFrom && dateTo) {
                    url += `&date_from=${dateFrom}&date_to=${dateTo}`;
                }
            }
            window.location.href = url;
        }
        
        function resetFilters() {
            window.location.href = 'admin_orders.php';
        }
        
        function toggleSelectAll() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.order-checkbox');
            checkboxes.forEach(cb => cb.checked = selectAll.checked);
        }
        
        function getSelectedOrders() {
            const checkboxes = document.querySelectorAll('.order-checkbox:checked');
            return Array.from(checkboxes).map(cb => cb.value);
        }
        
        function bulkUpdateStatus() {
            const selectedOrders = getSelectedOrders();
            const newStatus = document.getElementById('bulkStatus').value;
            
            if(selectedOrders.length === 0) {
                alert('Please select at least one order');
                return;
            }
            if(!newStatus) {
                alert('Please select a status');
                return;
            }
            
            if(confirm(`Update ${selectedOrders.length} order(s) to ${newStatus}?`)) {
                fetch('bulk_update_status.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `order_ids=${selectedOrders.join(',')}&status=${newStatus}`
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        alert(`Updated ${data.updated_count} orders`);
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
            }
        }
        
        function updateOrderStatus(select) {
            const orderId = select.dataset.orderId;
            const newStatus = select.value;
            
            if(confirm(`Change order #${orderId} status to ${newStatus}?`)) {
                fetch('update_order_status.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `order_id=${orderId}&status=${newStatus}`
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                        location.reload();
                    }
                });
            } else {
                location.reload();
            }
        }
        
        function viewOrderDetails(orderId) {
            document.getElementById('orderModal').classList.add('show');
            document.getElementById('modalOrderId').textContent = orderId;
            document.getElementById('orderModalBody').innerHTML = '<div style="text-align: center; padding: 40px;"><i class="fa-solid fa-spinner fa-spin"></i> Loading...</div>';
            
            fetch(`get_order_details.php?id=${orderId}`)
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        let timelineHtml = '';
                        if(data.timeline) {
                            data.timeline.forEach(item => {
                                timelineHtml += `
                                    <div class="timeline-item">
                                        <div class="timeline-status">${item.old_status || 'Order Placed'} → ${item.new_status}</div>
                                        <div class="timeline-time">
                                            <i class="fa-regular fa-clock"></i> ${item.changed_at}
                                            ${item.changed_by ? ` • by ${item.changed_by}` : ''}
                                        </div>
                                        ${item.notes ? `<div style="font-size: 12px; color: #64748b;">${item.notes}</div>` : ''}
                                    </div>
                                `;
                            });
                        }
                        
                        document.getElementById('orderModalBody').innerHTML = `
                            <div class="order-details-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                                <div>
                                    <h4>Order Information</h4>
                                    <div class="detail-item"><strong>Order ID:</strong> #${data.order.OrderID}</div>
                                    <div class="detail-item"><strong>Item:</strong> ${data.order.ItemName}</div>
                                    <div class="detail-item"><strong>Category:</strong> ${data.order.Category}</div>
                                    <div class="detail-item"><strong>Quantity:</strong> ${data.order.Quantity}</div>
                                    <div class="detail-item"><strong>Price:</strong> Rs. ${parseFloat(data.order.Price).toFixed(2)}</div>
                                    <div class="detail-item"><strong>Total:</strong> Rs. ${(data.order.Price * data.order.Quantity).toFixed(2)}</div>
                                    <div class="detail-item"><strong>Status:</strong> <span class="status-badge status-${data.order.Status}">${data.order.Status}</span></div>
                                    <div class="detail-item"><strong>Ordered at:</strong> ${data.order.Timestamp}</div>
                                </div>
                                <div>
                                    <h4>Customer Information</h4>
                                    <div class="detail-item"><strong>Name:</strong> ${data.customer.Name}</div>
                                    <div class="detail-item"><strong>Email:</strong> ${data.customer.Email}</div>
                                    <div class="detail-item"><strong>Total Orders:</strong> ${data.customer.total_orders}</div>
                                    <div class="detail-item"><strong>Total Spent:</strong> Rs. ${parseFloat(data.customer.total_spent).toFixed(2)}</div>
                                </div>
                            </div>
                            <div style="margin-top: 20px;">
                                <h4>Status Timeline</h4>
                                <div class="timeline">${timelineHtml || '<p>No status changes recorded</p>'}</div>
                            </div>
                            <div style="margin-top: 20px;">
                                <h4>Admin Notes</h4>
                                <textarea id="adminNote" rows="3" style="width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 8px;" placeholder="Add a note about this order..."></textarea>
                                <button class="btn btn-primary btn-sm" style="margin-top: 10px;" onclick="addOrderNote(${orderId})">Add Note</button>
                                <div id="existingNotes">
                                    ${data.notes ? data.notes.map(n => `<div style="background: #f8fafc; padding: 10px; border-radius: 8px; margin-top: 10px;"><strong>${n.created_at}</strong><br>${n.note}</div>`).join('') : ''}
                                </div>
                            </div>
                        `;
                    } else {
                        document.getElementById('orderModalBody').innerHTML = '<p style="color: red;">Error loading order details</p>';
                    }
                });
        }
        
        function addOrderNote(orderId) {
            const note = document.getElementById('adminNote').value;
            if(!note.trim()) {
                alert('Please enter a note');
                return;
            }
            
            fetch('add_order_note.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `order_id=${orderId}&note=${encodeURIComponent(note)}`
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    document.getElementById('adminNote').value = '';
                    viewOrderDetails(orderId);
                } else {
                    alert('Error adding note');
                }
            });
        }
        
        function printOrder(orderId) {
            window.open(`print_order.php?id=${orderId}`, '_blank');
        }
        
        function printOrderFromModal() {
            const orderId = document.getElementById('modalOrderId').textContent;
            printOrder(orderId);
        }
        
        function sendMessageToCustomer() {
            const orderId = document.getElementById('modalOrderId').textContent;
            const message = prompt('Enter message to send to customer:');
            if(message) {
                fetch('send_customer_message.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `order_id=${orderId}&message=${encodeURIComponent(message)}`
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        alert('Message sent successfully!');
                    } else {
                        alert('Error sending message');
                    }
                });
            }
        }
        
        function exportOrders() {
            const search = document.getElementById('searchInput').value;
            const status = document.getElementById('statusFilter').value;
            const category = document.getElementById('categoryFilter').value;
            const dateFilter = document.getElementById('dateFilter').value;
            let url = `export_orders.php?search=${encodeURIComponent(search)}&status=${status}&category=${category}&date_filter=${dateFilter}`;
            
            if(dateFilter === 'custom') {
                const dateFrom = document.getElementById('dateFrom').value;
                const dateTo = document.getElementById('dateTo').value;
                if(dateFrom && dateTo) {
                    url += `&date_from=${dateFrom}&date_to=${dateTo}`;
                }
            }
            window.location.href = url;
        }
        
        function exportSelected() {
            const selectedOrders = getSelectedOrders();
            if(selectedOrders.length === 0) {
                alert('Please select at least one order');
                return;
            }
            window.location.href = `export_orders.php?order_ids=${selectedOrders.join(',')}`;
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('show');
        }
        
        // Custom date range toggle
        document.getElementById('dateFilter')?.addEventListener('change', function() {
            const customRange = document.getElementById('customDateRange');
            if(customRange) {
                customRange.style.display = this.value === 'custom' ? 'flex' : 'none';
            }
        });
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            if(event.target.classList.contains('modal')) {
                event.target.classList.remove('show');
            }
        }
    </script>
</body>
</html>