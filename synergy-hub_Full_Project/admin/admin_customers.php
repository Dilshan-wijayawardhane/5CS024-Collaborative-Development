<?php
require_once 'config.php';
checkAdminAuth();

$admin = getAdminInfo($conn);
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

// Get customers with order stats
$customers_sql = "SELECT u.UserID, u.Name, u.Email, u.PointsBalance,
                  COUNT(o.OrderID) as total_orders,
                  SUM(o.Price * o.Quantity) as total_spent,
                  MAX(o.Timestamp) as last_order_date,
                  (SELECT ItemName FROM Orders WHERE UserID = u.UserID GROUP BY ItemName ORDER BY SUM(Quantity) DESC LIMIT 1) as favorite_item
                  FROM Users u
                  LEFT JOIN Orders o ON u.UserID = o.UserID
                  WHERE u.Role = 'User'
                  GROUP BY u.UserID
                  ORDER BY total_spent DESC";
if($search) {
    $customers_sql = "SELECT u.UserID, u.Name, u.Email, u.PointsBalance,
                      COUNT(o.OrderID) as total_orders,
                      SUM(o.Price * o.Quantity) as total_spent,
                      MAX(o.Timestamp) as last_order_date,
                      (SELECT ItemName FROM Orders WHERE UserID = u.UserID GROUP BY ItemName ORDER BY SUM(Quantity) DESC LIMIT 1) as favorite_item
                      FROM Users u
                      LEFT JOIN Orders o ON u.UserID = o.UserID
                      WHERE u.Role = 'User' AND (u.Name LIKE '%$search%' OR u.Email LIKE '%$search%')
                      GROUP BY u.UserID
                      ORDER BY total_spent DESC";
}
$customers_result = mysqli_query($conn, $customers_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Management - Synergy Hub Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="admin.css">
    <style>
        .customer-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            transition: all 0.3s;
            cursor: pointer;
        }
        .customer-card:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,0.1); }
        .customer-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 15px;
        }
        .customer-name { font-size: 18px; font-weight: 600; color: #1e293b; }
        .customer-email { color: #64748b; font-size: 14px; }
        .customer-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin: 15px 0;
            padding: 15px;
            background: #f8fafc;
            border-radius: 12px;
        }
        .stat { text-align: center; }
        .stat-value { font-size: 24px; font-weight: 700; color: #667eea; }
        .stat-label { font-size: 12px; color: #64748b; margin-top: 5px; }
        
        .modal-lg { max-width: 900px; }
        .order-history-table { width: 100%; border-collapse: collapse; }
        .order-history-table th, .order-history-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        .order-history-table th { background: #f8fafc; color: #64748b; font-weight: 600; }
        
        .search-bar {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            align-items: center;
        }
        .search-bar input {
            flex: 1;
            padding: 12px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
        }
        
        .bulk-actions {
            background: white;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            align-items: center;
        }
        
        .customer-checkbox { margin-right: 15px; }
    </style>
</head>
<body>
    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include 'includes/topbar.php'; ?>
            
            <div class="content">
                <h1 class="page-title">
                    <i class="fa-solid fa-users"></i> Customer Management
                </h1>
                
                <div class="search-bar">
                    <i class="fa-solid fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Search by name or email..." value="<?php echo htmlspecialchars($search); ?>">
                    <button class="btn btn-primary" onclick="searchCustomers()">Search</button>
                    <button class="btn btn-secondary" onclick="resetSearch()">Reset</button>
                    <button class="btn btn-secondary" onclick="exportCustomers()"><i class="fa-solid fa-download"></i> Export CSV</button>
                </div>
                
                <div class="bulk-actions">
                    <div style="display: flex; align-items: center;">
                        <input type="checkbox" id="selectAll" onclick="toggleSelectAll()">
                        <label style="margin-left: 8px;">Select All</label>
                    </div>
                    <button class="btn btn-primary btn-sm" onclick="sendBulkMessage()">
                        <i class="fa-regular fa-message"></i> Send Message to Selected
                    </button>
                    <button class="btn btn-secondary btn-sm" onclick="exportSelected()">
                        <i class="fa-solid fa-download"></i> Export Selected
                    </button>
                </div>
                
                <div id="customersList">
                    <?php while($customer = mysqli_fetch_assoc($customers_result)): ?>
                    <div class="customer-card" data-user-id="<?php echo $customer['UserID']; ?>">
                        <div class="customer-header">
                            <div>
                                <input type="checkbox" class="customer-checkbox" value="<?php echo $customer['UserID']; ?>" onclick="event.stopPropagation()">
                                <span class="customer-name"><?php echo htmlspecialchars($customer['Name']); ?></span>
                                <div class="customer-email"><?php echo htmlspecialchars($customer['Email']); ?></div>
                            </div>
                            <div>
                                <span class="status-badge status-active"><?php echo $customer['total_orders']; ?> orders</span>
                            </div>
                        </div>
                        
                        <div class="customer-stats">
                            <div class="stat">
                                <div class="stat-value"><?php echo $customer['total_orders']; ?></div>
                                <div class="stat-label">Total Orders</div>
                            </div>
                            <div class="stat">
                                <div class="stat-value">Rs. <?php echo number_format($customer['total_spent'], 2); ?></div>
                                <div class="stat-label">Total Spent</div>
                            </div>
                            <div class="stat">
                                <div class="stat-value"><?php echo $customer['favorite_item'] ? htmlspecialchars($customer['favorite_item']) : 'N/A'; ?></div>
                                <div class="stat-label">Favorite Item</div>
                            </div>
                            <div class="stat">
                                <div class="stat-value"><?php echo $customer['PointsBalance']; ?></div>
                                <div class="stat-label">Points Balance</div>
                            </div>
                        </div>
                        
                        <div style="display: flex; gap: 10px;">
                            <button class="btn btn-primary btn-sm" onclick="viewCustomerOrders(<?php echo $customer['UserID']; ?>, '<?php echo htmlspecialchars($customer['Name']); ?>')">
                                <i class="fa-regular fa-clock"></i> View Orders
                            </button>
                            <button class="btn btn-secondary btn-sm" onclick="sendMessage(<?php echo $customer['UserID']; ?>, '<?php echo htmlspecialchars($customer['Name']); ?>')">
                                <i class="fa-regular fa-message"></i> Send Message
                            </button>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Customer Orders Modal -->
    <div class="modal" id="ordersModal">
        <div class="modal-content modal-lg">
            <div class="modal-header">
                <h3>Order History: <span id="customerName"></span></h3>
                <button class="modal-close" onclick="closeModal('ordersModal')">&times;</button>
            </div>
            <div class="modal-body" id="ordersModalBody">
                <div class="loading-spinner">Loading...</div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="exportCustomerOrders()"><i class="fa-solid fa-download"></i> Export CSV</button>
                <button class="btn btn-secondary" onclick="closeModal('ordersModal')">Close</button>
            </div>
        </div>
    </div>
    
    <script>
        let currentCustomerId = null;
        
        function searchCustomers() {
            const search = document.getElementById('searchInput').value;
            window.location.href = `admin_customers.php?search=${encodeURIComponent(search)}`;
        }
        
        function resetSearch() {
            window.location.href = 'admin_customers.php';
        }
        
        function toggleSelectAll() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.customer-checkbox');
            checkboxes.forEach(cb => cb.checked = selectAll.checked);
        }
        
        function getSelectedCustomers() {
            const checkboxes = document.querySelectorAll('.customer-checkbox:checked');
            return Array.from(checkboxes).map(cb => cb.value);
        }
        
        function sendBulkMessage() {
            const selected = getSelectedCustomers();
            if(selected.length === 0) {
                alert('Please select at least one customer');
                return;
            }
            const message = prompt('Enter message to send to selected customers:');
            if(message) {
                fetch('send_bulk_message.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `user_ids=${selected.join(',')}&message=${encodeURIComponent(message)}`
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        alert(`Message sent to ${data.sent_count} customers`);
                    } else {
                        alert('Error sending messages');
                    }
                });
            }
        }
        
        function sendMessage(userId, userName) {
            const message = prompt(`Enter message to send to ${userName}:`);
            if(message) {
                fetch('send_customer_message.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `user_id=${userId}&message=${encodeURIComponent(message)}`
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
        
        function viewCustomerOrders(userId, userName) {
            currentCustomerId = userId;
            document.getElementById('customerName').textContent = userName;
            document.getElementById('ordersModal').classList.add('show');
            document.getElementById('ordersModalBody').innerHTML = '<div style="text-align: center; padding: 40px;"><i class="fa-solid fa-spinner fa-spin"></i> Loading orders...</div>';
            
            fetch(`get_customer_orders.php?user_id=${userId}`)
                .then(response => response.json())
                .then(data => {
                    if(data.success && data.orders.length > 0) {
                        let html = '<table class="order-history-table"><thead><tr><th>Order ID</th><th>Item</th><th>Qty</th><th>Price</th><th>Total</th><th>Status</th><th>Date</th></tr></thead><tbody>';
                        data.orders.forEach(order => {
                            html += `<tr>
                                        <td>#${order.OrderID}</td>
                                        <td>${escapeHtml(order.ItemName)}</td>
                                        <td>${order.Quantity}</td>
                                        <td>Rs. ${parseFloat(order.Price).toFixed(2)}</td>
                                        <td>Rs. ${(order.Price * order.Quantity).toFixed(2)}</td>
                                        <td><span class="status-badge status-${order.Status}">${order.Status}</span></td>
                                        <td>${order.Timestamp}</td>
                                     </tr>`;
                        });
                        html += '</tbody></table>';
                        html += `<div style="margin-top: 20px; padding: 15px; background: #f8fafc; border-radius: 12px;">
                                    <strong>Summary:</strong> Total Orders: ${data.summary.total_orders} | Total Spent: Rs. ${parseFloat(data.summary.total_spent).toFixed(2)} | Avg Order Value: Rs. ${parseFloat(data.summary.avg_order_value).toFixed(2)}
                                </div>`;
                        document.getElementById('ordersModalBody').innerHTML = html;
                    } else {
                        document.getElementById('ordersModalBody').innerHTML = '<p style="text-align: center; padding: 40px;">No orders found for this customer</p>';
                    }
                });
        }
        
        function exportCustomers() {
            window.location.href = 'export_customers.php';
        }
        
        function exportSelected() {
            const selected = getSelectedCustomers();
            if(selected.length === 0) {
                alert('Please select at least one customer');
                return;
            }
            window.location.href = `export_customers.php?user_ids=${selected.join(',')}`;
        }
        
        function exportCustomerOrders() {
            if(currentCustomerId) {
                window.location.href = `export_orders.php?user_id=${currentCustomerId}`;
            }
        }
        
        function escapeHtml(text) {
            if(!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('show');
        }
        
        window.onclick = function(event) {
            if(event.target.classList.contains('modal')) {
                event.target.classList.remove('show');
            }
        }
    </script>
</body>
</html>