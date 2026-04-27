<?php
require_once 'middleware.php';
require_once 'config.php';
checkAdminAuth();

$admin = getAdminInfo($conn);
$message = '';
$error = '';

// Handle game field operations
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_booking_rules'])) {
        $booking_interval = $_POST['booking_interval'];
        $peak_price = intval($_POST['peak_price']);
        $off_peak_price = intval($_POST['off_peak_price']);
        $cancellation_fee = intval($_POST['cancellation_fee']);
        $max_booking_days = intval($_POST['max_booking_days']);
        
        $_SESSION['game_rules'] = [
            'booking_interval' => $booking_interval,
            'peak_price' => $peak_price,
            'off_peak_price' => $off_peak_price,
            'cancellation_fee' => $cancellation_fee,
            'max_booking_days' => $max_booking_days
        ];
        
        $message = "Booking rules updated successfully!";
        logAdminActivity($conn, 'UPDATE_GAME_RULES', "Updated game field booking rules");
    }
    
    if (isset($_POST['add_equipment'])) {
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $quantity = intval($_POST['quantity']);
        $available = intval($_POST['available']);
        $condition = mysqli_real_escape_string($conn, $_POST['condition']);
        $borrow_limit = intval($_POST['borrow_limit']);
        $borrow_duration = intval($_POST['borrow_duration']);
        
        $sql = "INSERT INTO game_equipment (name, quantity, available, equipment_condition, borrow_limit, borrow_duration) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "siisii", $name, $quantity, $available, $condition, $borrow_limit, $borrow_duration);
        
        if (mysqli_stmt_execute($stmt)) {
            $message = "Equipment added successfully!";
            logAdminActivity($conn, 'ADD_EQUIPMENT', "Added equipment: $name");
        } else {
            $error = "Error adding equipment: " . mysqli_error($conn);
        }
    }
    
    if (isset($_POST['update_equipment'])) {
        $equipment_id = intval($_POST['equipment_id']);
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $quantity = intval($_POST['quantity']);
        $available = intval($_POST['available']);
        $condition = mysqli_real_escape_string($conn, $_POST['condition']);
        $borrow_limit = intval($_POST['borrow_limit']);
        $borrow_duration = intval($_POST['borrow_duration']);
        
        $sql = "UPDATE game_equipment SET name=?, quantity=?, available=?, equipment_condition=?, borrow_limit=?, borrow_duration=? WHERE equipment_id=?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "siisiii", $name, $quantity, $available, $condition, $borrow_limit, $borrow_duration, $equipment_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $message = "Equipment updated successfully!";
            logAdminActivity($conn, 'UPDATE_EQUIPMENT', "Updated equipment: $name");
        } else {
            $error = "Error updating equipment: " . mysqli_error($conn);
        }
    }
    
    if (isset($_POST['delete_equipment'])) {
        $equipment_id = intval($_POST['equipment_id']);
        
        $delete_sql = "DELETE FROM game_equipment WHERE equipment_id = ?";
        $delete_stmt = mysqli_prepare($conn, $delete_sql);
        mysqli_stmt_bind_param($delete_stmt, "i", $equipment_id);
        
        if (mysqli_stmt_execute($delete_stmt)) {
            $message = "Equipment deleted successfully!";
            logAdminActivity($conn, 'DELETE_EQUIPMENT', "Deleted equipment ID: $equipment_id");
        } else {
            $error = "Error deleting equipment: " . mysqli_error($conn);
        }
    }
}

// Get game fields (Facilities with type GameField)
$game_fields_sql = "SELECT * FROM Facilities WHERE Type = 'GameField'";
$game_fields_result = mysqli_query($conn, $game_fields_sql);

// Get equipment (create table if not exists)
$check_equipment = mysqli_query($conn, "SHOW TABLES LIKE 'game_equipment'");
if (mysqli_num_rows($check_equipment) == 0) {
    mysqli_query($conn, "CREATE TABLE game_equipment (
        equipment_id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        quantity INT DEFAULT 1,
        available INT DEFAULT 1,
        equipment_condition ENUM('New', 'Good', 'Fair', 'Poor') DEFAULT 'Good',
        borrow_limit INT DEFAULT 1,
        borrow_duration INT DEFAULT 2,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
}
$equipment_sql = "SELECT * FROM game_equipment ORDER BY name";
$equipment_result = mysqli_query($conn, $equipment_sql);

// Get field bookings
$bookings_sql = "SELECT fb.*, u.Name as user_name, u.StudentID 
                 FROM field_bookings fb
                 JOIN Users u ON fb.user_id = u.UserID
                 WHERE fb.booking_date >= CURDATE()
                 ORDER BY fb.booking_date, fb.time_slot";
$bookings_result = mysqli_query($conn, $bookings_sql);

// Get rules from session
$game_rules = $_SESSION['game_rules'] ?? [
    'booking_interval' => '1',
    'peak_price' => 50,
    'off_peak_price' => 30,
    'cancellation_fee' => 10,
    'max_booking_days' => 14
];

// Equipment conditions
$conditions = ['New', 'Good', 'Fair', 'Poor'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Game Field Management - Synergy Hub Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="admin.css">
    <style>
        .game-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 10px;
            flex-wrap: wrap;
        }
        
        .game-tab {
            padding: 10px 20px;
            background: none;
            border: none;
            color: #64748b;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .game-tab:hover {
            background: #f1f5f9;
            color: #1e293b;
        }
        
        .game-tab.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .rules-panel {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .price-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
        }
        
        .price-value {
            font-size: 36px;
            font-weight: 700;
        }
        
        .price-label {
            opacity: 0.9;
            font-size: 14px;
        }
        
        .equipment-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .equipment-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border-left: 4px solid;
        }
        
        .condition-New { border-left-color: #10b981; }
        .condition-Good { border-left-color: #3b82f6; }
        .condition-Fair { border-left-color: #f59e0b; }
        .condition-Poor { border-left-color: #ef4444; }
        
        .equipment-name {
            font-size: 18px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 5px;
        }
        
        .equipment-stats {
            display: flex;
            justify-content: space-between;
            margin: 10px 0;
            padding: 10px;
            background: #f8fafc;
            border-radius: 8px;
        }
        
        .stat {
            text-align: center;
        }
        
        .stat-value {
            font-size: 20px;
            font-weight: 600;
            color: #667eea;
        }
        
        .stat-label {
            font-size: 11px;
            color: #64748b;
        }
        
        .condition-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .condition-New-badge {
            background: #dcfce7;
            color: #16a34a;
        }
        
        .condition-Good-badge {
            background: #dbeafe;
            color: #2563eb;
        }
        
        .condition-Fair-badge {
            background: #fff7ed;
            color: #ea580c;
        }
        
        .condition-Poor-badge {
            background: #fee;
            color: #ef4444;
        }
        
        .availability-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 5px;
        }
        
        .available {
            background: #10b981;
        }
        
        .low-stock {
            background: #f59e0b;
        }
        
        .out-stock {
            background: #ef4444;
        }
        
        .booking-item {
            background: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .booking-info h4 {
            font-size: 16px;
            color: #1e293b;
            margin-bottom: 5px;
        }
        
        .booking-details {
            font-size: 13px;
            color: #64748b;
        }
        
        .booking-details i {
            color: #667eea;
            width: 20px;
        }
        
        .booking-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-booked {
            background: #dbeafe;
            color: #2563eb;
        }
        
        .status-cancelled {
            background: #fee;
            color: #ef4444;
        }
        
        .status-completed {
            background: #dcfce7;
            color: #16a34a;
        }
        
        .peak-hours {
            background: #f8fafc;
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
        }
        
        .time-slot {
            display: flex;
            justify-content: space-between;
            padding: 8px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .time-slot:last-child {
            border-bottom: none;
        }
        
        .time-label {
            font-weight: 500;
        }
        
        .time-price {
            color: #667eea;
            font-weight: 600;
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
                    <i class="fa-solid fa-futbol"></i> Game Field Management
                </h1>
                
                <?php if($message): ?>
                    <div class="alert alert-success"><?php echo $message; ?></div>
                <?php endif; ?>
                <?php if($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <!-- Tabs -->
                <div class="game-tabs">
                    <button class="game-tab active" onclick="showTab('fields')">
                        <i class="fa-solid fa-map"></i> Game Fields
                    </button>
                    <button class="game-tab" onclick="showTab('rules')">
                        <i class="fa-solid fa-ruler"></i> Booking Rules
                    </button>
                    <button class="game-tab" onclick="showTab('equipment')">
                        <i class="fa-solid fa-baseball"></i> Equipment
                    </button>
                    <button class="game-tab" onclick="showTab('bookings')">
                        <i class="fa-solid fa-calendar-check"></i> Bookings
                    </button>
                </div>
                
                <!-- Tab: Game Fields -->
                <div id="tab-fields" class="tab-content active">
                    <div class="stats-grid" style="grid-template-columns: repeat(3, 1fr);">
                        <?php 
                        $total_fields = mysqli_num_rows($game_fields_result);
                        $total_bookings_today = 0;
                        mysqli_data_seek($bookings_result, 0);
                        while($booking = mysqli_fetch_assoc($bookings_result)) {
                            if($booking['booking_date'] == date('Y-m-d')) $total_bookings_today++;
                        }
                        ?>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $total_fields; ?></div>
                            <div class="stat-label">Total Fields</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $total_bookings_today; ?></div>
                            <div class="stat-label">Bookings Today</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo mysqli_num_rows($equipment_result); ?></div>
                            <div class="stat-label">Equipment Items</div>
                        </div>
                    </div>
                    
                    <h3>Game Fields</h3>
                    <div class="books-grid">
                        <?php while($field = mysqli_fetch_assoc($game_fields_result)): 
                            $extra = json_decode($field['ExtraInfo'], true) ?? [];
                        ?>
                        <div class="book-card">
                            <div class="book-title"><?php echo htmlspecialchars($field['Name']); ?></div>
                            <div class="book-author">Game Field</div>
                            
                            <div class="book-meta">
                                <div><i class="fa-solid fa-users"></i> Capacity: <?php echo $field['Capacity'] ?: 'N/A'; ?></div>
                                <?php if(isset($extra['field_type'])): ?>
                                <div><i class="fa-solid fa-tag"></i> <?php echo $extra['field_type']; ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <?php if(isset($extra['floodlights']) && $extra['floodlights']): ?>
                            <div style="margin: 10px 0;">
                                <span class="feature-tag"><i class="fa-solid fa-lightbulb"></i> Floodlights</span>
                            </div>
                            <?php endif; ?>
                            
                            <div class="facility-status status-<?php echo $field['Status']; ?>" style="margin: 10px 0;">
                                <?php echo $field['Status']; ?>
                            </div>
                            
                            <div class="book-actions">
                                <button class="btn btn-primary btn-sm" onclick="editField(<?php echo $field['FacilityID']; ?>)">
                                    <i class="fa-regular fa-pen-to-square"></i> Edit
                                </button>
                                <button class="btn btn-secondary btn-sm" onclick="viewFieldBookings(<?php echo $field['FacilityID']; ?>)">
                                    <i class="fa-regular fa-calendar"></i> Bookings
                                </button>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>
                
                <!-- Tab: Booking Rules -->
                <div id="tab-rules" class="tab-content">
                    <div class="rules-panel">
                        <h3>Field Booking Rules</h3>
                        
                        <form method="POST">
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Booking Interval</label>
                                    <select name="booking_interval" class="form-control">
                                        <option value="0.5" <?php echo $game_rules['booking_interval'] == '0.5' ? 'selected' : ''; ?>>30 minutes</option>
                                        <option value="1" <?php echo $game_rules['booking_interval'] == '1' ? 'selected' : ''; ?>>1 hour</option>
                                        <option value="1.5" <?php echo $game_rules['booking_interval'] == '1.5' ? 'selected' : ''; ?>>1.5 hours</option>
                                        <option value="2" <?php echo $game_rules['booking_interval'] == '2' ? 'selected' : ''; ?>>2 hours</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label>Maximum Booking Days in Advance</label>
                                    <input type="number" name="max_booking_days" value="<?php echo $game_rules['max_booking_days']; ?>" min="1" max="30">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Peak Hours Price (points)</label>
                                    <input type="number" name="peak_price" value="<?php echo $game_rules['peak_price']; ?>" min="0">
                                </div>
                                
                                <div class="form-group">
                                    <label>Off-Peak Price (points)</label>
                                    <input type="number" name="off_peak_price" value="<?php echo $game_rules['off_peak_price']; ?>" min="0">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Cancellation Fee (points)</label>
                                <input type="number" name="cancellation_fee" value="<?php echo $game_rules['cancellation_fee']; ?>" min="0">
                            </div>
                            
                            <div class="peak-hours">
                                <h4>Peak Hours Configuration</h4>
                                <div class="time-slot">
                                    <span class="time-label">Weekdays (Mon-Fri) 17:00 - 21:00</span>
                                    <span class="time-price">Peak Price: <?php echo $game_rules['peak_price']; ?> points</span>
                                </div>
                                <div class="time-slot">
                                    <span class="time-label">Weekends (Sat-Sun) 09:00 - 21:00</span>
                                    <span class="time-price">Peak Price: <?php echo $game_rules['peak_price']; ?> points</span>
                                </div>
                                <div class="time-slot">
                                    <span class="time-label">All other times</span>
                                    <span class="time-price">Off-Peak: <?php echo $game_rules['off_peak_price']; ?> points</span>
                                </div>
                            </div>
                            
                            <button type="submit" name="update_booking_rules" class="btn btn-primary">
                                <i class="fa-solid fa-save"></i> Save Rules
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Tab: Equipment -->
                <div id="tab-equipment" class="tab-content">
                    <div style="margin-bottom: 20px;">
                        <button class="btn btn-primary" onclick="showAddEquipment()">
                            <i class="fa-solid fa-plus"></i> Add Equipment
                        </button>
                    </div>
                    
                    <div class="equipment-grid">
                        <?php while($equip = mysqli_fetch_assoc($equipment_result)): 
                            $available_percent = ($equip['available'] / $equip['quantity']) * 100;
                            $stock_class = 'available';
                            if ($equip['available'] == 0) $stock_class = 'out-stock';
                            else if ($equip['available'] <= 3) $stock_class = 'low-stock';
                        ?>
                        <div class="equipment-card condition-<?php echo $equip['equipment_condition']; ?>">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                <div>
                                    <div class="equipment-name"><?php echo htmlspecialchars($equip['name']); ?></div>
                                    <span class="condition-badge condition-<?php echo $equip['equipment_condition']; ?>-badge">
                                        <?php echo $equip['equipment_condition']; ?>
                                    </span>
                                </div>
                                <div>
                                    <span class="availability-indicator <?php echo $stock_class; ?>"></span>
                                </div>
                            </div>
                            
                            <div class="equipment-stats">
                                <div class="stat">
                                    <div class="stat-value"><?php echo $equip['quantity']; ?></div>
                                    <div class="stat-label">Total</div>
                                </div>
                                <div class="stat">
                                    <div class="stat-value"><?php echo $equip['available']; ?></div>
                                    <div class="stat-label">Available</div>
                                </div>
                                <div class="stat">
                                    <div class="stat-value"><?php echo $equip['borrow_limit']; ?></div>
                                    <div class="stat-label">Max Borrow</div>
                                </div>
                            </div>
                            
                            <div style="margin: 10px 0;">
                                <div class="availability-bar">
                                    <div class="crowd-fill" style="width: <?php echo $available_percent; ?>%;"></div>
                                </div>
                            </div>
                            
                            <div style="font-size: 13px; color: #64748b; margin-bottom: 10px;">
                                <i class="fa-regular fa-clock"></i> Max borrow duration: <?php echo $equip['borrow_duration']; ?> hours
                            </div>
                            
                            <div class="book-actions">
                                <button class="btn btn-primary btn-sm" onclick="editEquipment(<?php echo $equip['equipment_id']; ?>)">
                                    <i class="fa-regular fa-pen-to-square"></i> Edit
                                </button>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this equipment?')">
                                    <input type="hidden" name="equipment_id" value="<?php echo $equip['equipment_id']; ?>">
                                    <button type="submit" name="delete_equipment" class="btn btn-danger btn-sm">
                                        <i class="fa-regular fa-trash-can"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                    
                    <!-- Equipment Modal -->
                    <div class="modal" id="equipmentModal">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h3 id="equipmentModalTitle">Add Equipment</h3>
                                <button class="modal-close" onclick="closeEquipmentModal()">&times;</button>
                            </div>
                            <form method="POST" id="equipmentForm">
                                <div class="modal-body">
                                    <input type="hidden" name="equipment_id" id="equipment_id">
                                    
                                    <div class="form-group">
                                        <label>Equipment Name</label>
                                        <input type="text" name="name" id="equipment_name" required>
                                    </div>
                                    
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label>Total Quantity</label>
                                            <input type="number" name="quantity" id="equipment_quantity" min="1" value="1" required>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>Available</label>
                                            <input type="number" name="available" id="equipment_available" min="0" value="1" required>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Condition</label>
                                        <select name="condition" id="equipment_condition">
                                            <?php foreach($conditions as $cond): ?>
                                            <option value="<?php echo $cond; ?>"><?php echo $cond; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label>Borrow Limit (per user)</label>
                                            <input type="number" name="borrow_limit" id="equipment_limit" min="1" value="1">
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>Borrow Duration (hours)</label>
                                            <input type="number" name="borrow_duration" id="equipment_duration" min="1" value="2">
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" onclick="closeEquipmentModal()">Cancel</button>
                                    <button type="submit" name="add_equipment" class="btn btn-primary">Save Equipment</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Tab: Bookings -->
                <div id="tab-bookings" class="tab-content">
                    <h3>Upcoming Field Bookings</h3>
                    
                    <?php if(mysqli_num_rows($bookings_result) > 0): ?>
                        <?php while($booking = mysqli_fetch_assoc($bookings_result)): ?>
                        <div class="booking-item">
                            <div class="booking-info">
                                <h4><?php echo htmlspecialchars($booking['field_name']); ?></h4>
                                <div class="booking-details">
                                    <div><i class="fa-regular fa-user"></i> <?php echo htmlspecialchars($booking['user_name']); ?> (<?php echo $booking['StudentID']; ?>)</div>
                                    <div><i class="fa-regular fa-calendar"></i> <?php echo date('M d, Y', strtotime($booking['booking_date'])); ?> • <?php echo $booking['time_slot']; ?></div>
                                </div>
                            </div>
                            <div>
                                <span class="booking-status status-<?php echo $booking['status']; ?>">
                                    <?php echo ucfirst($booking['status']); ?>
                                </span>
                                <?php if($booking['status'] == 'booked'): ?>
                                <button class="btn btn-danger btn-sm" onclick="cancelFieldBooking(<?php echo $booking['booking_id']; ?>)">
                                    Cancel
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p>No upcoming bookings.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Tab switching
        function showTab(tab) {
            document.querySelectorAll('.game-tab').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            
            event.currentTarget.classList.add('active');
            document.getElementById('tab-' + tab).classList.add('active');
        }
        
        // Equipment modal functions
        function showAddEquipment() {
            document.getElementById('equipmentModalTitle').textContent = 'Add Equipment';
            document.getElementById('equipmentForm').reset();
            document.getElementById('equipment_id').value = '0';
            document.getElementById('equipmentModal').classList.add('show');
        }
        
        function closeEquipmentModal() {
            document.getElementById('equipmentModal').classList.remove('show');
        }
        
        function editEquipment(id) {
            fetch('get_equipment.php?id=' + id)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('equipmentModalTitle').textContent = 'Edit Equipment';
                    document.getElementById('equipment_id').value = data.equipment_id;
                    document.getElementById('equipment_name').value = data.name;
                    document.getElementById('equipment_quantity').value = data.quantity;
                    document.getElementById('equipment_available').value = data.available;
                    document.getElementById('equipment_condition').value = data.equipment_condition;
                    document.getElementById('equipment_limit').value = data.borrow_limit;
                    document.getElementById('equipment_duration').value = data.borrow_duration;
                    document.getElementById('equipmentModal').classList.add('show');
                });
        }
        
        // Cancel booking
        function cancelFieldBooking(bookingId) {
            if (confirm('Cancel this booking?')) {
                fetch('cancel_field_booking.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'booking_id=' + bookingId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Booking cancelled!');
                        location.reload();
                    }
                });
            }
        }
        
        // Close modals when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('show');
            }
        }
    </script>
</body>
</html>