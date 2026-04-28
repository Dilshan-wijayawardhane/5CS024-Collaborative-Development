<?php
require_once 'config.php';
checkAdminAuth();

$admin = getAdminInfo($conn);
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'dashboard';
$message = '';
$error = '';

// Handle Equipment Add/Edit/Delete
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_equipment'])) {
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $category_id = intval($_POST['category_id']);
        $quantity = intval($_POST['quantity']);
        $available = intval($_POST['available']);
        $image_icon = mysqli_real_escape_string($conn, $_POST['image_icon']);
        
        $sql = "INSERT INTO gym_equipment (name, category_id, quantity, available, image_icon) VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "siiis", $name, $category_id, $quantity, $available, $image_icon);
        
        if (mysqli_stmt_execute($stmt)) {
            $message = "Equipment added successfully!";
            logAdminActivity($conn, 'ADD_EQUIPMENT', "Added equipment: $name");
        } else {
            $error = "Error: " . mysqli_error($conn);
        }
    }
    
    if (isset($_POST['toggle_maintenance'])) {
        $equipment_id = intval($_POST['equipment_id']);
        $maintenance_mode = isset($_POST['maintenance_mode']) ? 1 : 0;
        
        $sql = "UPDATE gym_equipment SET maintenance_mode = ?, last_maintenance = ? WHERE equipment_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        $last_maintenance = $maintenance_mode ? date('Y-m-d') : null;
        mysqli_stmt_bind_param($stmt, "isi", $maintenance_mode, $last_maintenance, $equipment_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $message = "Equipment status updated!";
        }
    }
    
    if (isset($_POST['delete_equipment'])) {
        $equipment_id = intval($_POST['equipment_id']);
        $sql = "DELETE FROM gym_equipment WHERE equipment_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $equipment_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $message = "Equipment deleted!";
        }
    }
    
    // Handle Class Add/Edit/Delete
    if (isset($_POST['add_class'])) {
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $time = mysqli_real_escape_string($conn, $_POST['time']);
        $instructor = mysqli_real_escape_string($conn, $_POST['instructor']);
        $capacity = intval($_POST['capacity']);
        $location = mysqli_real_escape_string($conn, $_POST['location']);
        $description = mysqli_real_escape_string($conn, $_POST['description']);
        
        $sql = "INSERT INTO fitness_classes (name, time, instructor, capacity, location, description) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "sssiss", $name, $time, $instructor, $capacity, $location, $description);
        
        if (mysqli_stmt_execute($stmt)) {
            $message = "Class added successfully!";
        }
    }
    
    if (isset($_POST['update_class'])) {
        $class_id = intval($_POST['class_id']);
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $time = mysqli_real_escape_string($conn, $_POST['time']);
        $instructor = mysqli_real_escape_string($conn, $_POST['instructor']);
        $capacity = intval($_POST['capacity']);
        $location = mysqli_real_escape_string($conn, $_POST['location']);
        
        $sql = "UPDATE fitness_classes SET name=?, time=?, instructor=?, capacity=?, location=? WHERE class_id=?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "sssisi", $name, $time, $instructor, $capacity, $location, $class_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $message = "Class updated!";
        }
    }
    
    if (isset($_POST['delete_class'])) {
        $class_id = intval($_POST['class_id']);
        $sql = "DELETE FROM fitness_classes WHERE class_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $class_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $message = "Class deleted!";
        }
    }
    
    // Handle Settings Update
    if (isset($_POST['update_settings'])) {
        foreach ($_POST['settings'] as $key => $value) {
            $sql = "UPDATE gym_settings SET setting_value = ? WHERE setting_key = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ss", $value, $key);
            mysqli_stmt_execute($stmt);
        }
        $message = "Settings updated!";
    }
    
    // Handle Announcement
    if (isset($_POST['add_announcement'])) {
        $title = mysqli_real_escape_string($conn, $_POST['title']);
        $message_text = mysqli_real_escape_string($conn, $_POST['message']);
        $expires_at = $_POST['expires_at'] ?: null;
        
        $sql = "INSERT INTO gym_announcements (title, message, created_by, expires_at) VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssis", $title, $message_text, $_SESSION['user_id'], $expires_at);
        
        if (mysqli_stmt_execute($stmt)) {
            $message = "Announcement published!";
        }
    }
}

// Get data for display
$equipment_sql = "SELECT e.*, c.category_name FROM gym_equipment e LEFT JOIN equipment_categories c ON e.category_id = c.category_id ORDER BY c.display_order, e.name";
$equipment_result = mysqli_query($conn, $equipment_sql);

$classes_sql = "SELECT * FROM fitness_classes ORDER BY time";
$classes_result = mysqli_query($conn, $classes_sql);

$categories_sql = "SELECT * FROM equipment_categories ORDER BY display_order";
$categories_result = mysqli_query($conn, $categories_sql);

$settings_sql = "SELECT * FROM gym_settings";
$settings_result = mysqli_query($conn, $settings_sql);
$gym_settings = [];
while($row = mysqli_fetch_assoc($settings_result)) {
    $gym_settings[$row['setting_key']] = $row['setting_value'];
}

$announcements_sql = "SELECT a.*, u.Name as creator_name FROM gym_announcements a LEFT JOIN Users u ON a.created_by = u.UserID ORDER BY a.created_at DESC";
$announcements_result = mysqli_query($conn, $announcements_sql);

// Dashboard stats
$stats_sql = "SELECT 
                (SELECT COUNT(*) FROM gym_equipment WHERE maintenance_mode = 0) as available_equipment,
                (SELECT COUNT(*) FROM fitness_classes WHERE time >= CURTIME()) as today_classes,
                (SELECT COUNT(*) FROM class_bookings WHERE status = 'booked') as active_bookings,
                (SELECT SUM(points_earned) FROM class_bookings) as total_points_awarded";
$stats_result = mysqli_query($conn, $stats_sql);
$stats = mysqli_fetch_assoc($stats_result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gym Management - Synergy Hub Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="admin.css">
    <style>
        .gym-tabs { display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px; flex-wrap: wrap; }
        .gym-tab { padding: 10px 20px; background: none; border: none; color: #64748b; cursor: pointer; font-size: 14px; font-weight: 500; border-radius: 8px; text-decoration: none; display: inline-block; }
        .gym-tab:hover { background: #f1f5f9; color: #1e293b; }
        .gym-tab.active { background: linear-gradient(135deg, #667eea, #764ba2); color: white; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 15px; }
        .stat-icon { width: 50px; height: 50px; background: linear-gradient(135deg, #667eea, #764ba2); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white; font-size: 20px; }
        .stat-info h3 { font-size: 24px; color: #1e293b; margin: 0; }
        .stat-info p { color: #64748b; margin: 5px 0 0; font-size: 14px; }
        
        .equipment-card, .class-card { background: white; border-radius: 12px; padding: 20px; margin-bottom: 15px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); border-left: 4px solid #667eea; }
        .equipment-header, .class-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-wrap: wrap; }
        .equipment-name, .class-name { font-size: 18px; font-weight: 600; color: #1e293b; }
        .maintenance-badge { background: #f59e0b; color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px; }
        .available-badge { background: #10b981; color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px; }
        .form-container { background: white; border-radius: 12px; padding: 25px; max-width: 600px; margin: 0 auto; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; color: #475569; font-size: 14px; font-weight: 500; margin-bottom: 5px; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 14px; }
        .setting-item { display: flex; justify-content: space-between; align-items: center; padding: 15px; border-bottom: 1px solid #e2e8f0; }
        .setting-label { font-weight: 500; color: #1e293b; }
        .setting-value input { width: 150px; padding: 8px; border: 1px solid #e2e8f0; border-radius: 6px; }
        .setting-value select { width: 150px; padding: 8px; }
        .btn-sm { padding: 6px 12px; font-size: 12px; }
        .action-buttons { display: flex; gap: 10px; margin-top: 15px; }
        .modal { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 2000; align-items: center; justify-content: center; }
        .modal.show { display: flex; }
        .modal-content { background: white; border-radius: 12px; width: 90%; max-width: 500px; max-height: 80vh; overflow-y: auto; }
        .modal-header { padding: 20px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
        .modal-body { padding: 20px; }
        .modal-footer { padding: 20px; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 10px; }
    </style>
</head>
<body>
    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include 'includes/topbar.php'; ?>
            
            <div class="content">
                <h1 class="page-title"><i class="fa-solid fa-dumbbell"></i> Gym Management</h1>
                
                <?php if($message): ?>
                    <div class="alert alert-success"><?php echo $message; ?></div>
                <?php endif; ?>
                <?php if($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <!-- Stats -->
                <div class="stats-grid">
                    <div class="stat-card"><div class="stat-icon"><i class="fa-solid fa-dumbbell"></i></div><div class="stat-info"><h3><?php echo $stats['available_equipment']; ?></h3><p>Available Equipment</p></div></div>
                    <div class="stat-card"><div class="stat-icon"><i class="fa-solid fa-people-group"></i></div><div class="stat-info"><h3><?php echo $stats['today_classes']; ?></h3><p>Classes Today</p></div></div>
                    <div class="stat-card"><div class="stat-icon"><i class="fa-regular fa-calendar-check"></i></div><div class="stat-info"><h3><?php echo $stats['active_bookings']; ?></h3><p>Active Bookings</p></div></div>
                    <div class="stat-card"><div class="stat-icon"><i class="fa-solid fa-star"></i></div><div class="stat-info"><h3><?php echo number_format($stats['total_points_awarded']); ?></h3><p>Points Awarded</p></div></div>
                </div>
                
                <!-- Tabs -->
                <div class="gym-tabs">
                    <a href="?tab=dashboard" class="gym-tab <?php echo $active_tab == 'dashboard' ? 'active' : ''; ?>"><i class="fa-solid fa-chart-line"></i> Dashboard</a>
                    <a href="?tab=equipment" class="gym-tab <?php echo $active_tab == 'equipment' ? 'active' : ''; ?>"><i class="fa-solid fa-dumbbell"></i> Equipment</a>
                    <a href="?tab=classes" class="gym-tab <?php echo $active_tab == 'classes' ? 'active' : ''; ?>"><i class="fa-solid fa-people-group"></i> Classes</a>
                    <a href="?tab=settings" class="gym-tab <?php echo $active_tab == 'settings' ? 'active' : ''; ?>"><i class="fa-solid fa-gear"></i> Settings</a>
                    <a href="?tab=announcements" class="gym-tab <?php echo $active_tab == 'announcements' ? 'active' : ''; ?>"><i class="fa-solid fa-bullhorn"></i> Announcements</a>
                </div>
                
                <!-- Dashboard Tab -->
                <div id="tab-dashboard" class="tab-content <?php echo $active_tab == 'dashboard' ? 'active' : ''; ?>">
                    <div class="stats-grid">
                        <div class="stat-card"><div class="stat-icon"><i class="fa-solid fa-chart-line"></i></div><div class="stat-info"><h3 id="popularClass">-</h3><p>Most Popular Class</p></div></div>
                        <div class="stat-card"><div class="stat-icon"><i class="fa-solid fa-user-plus"></i></div><div class="stat-info"><h3 id="newUsers">-</h3><p>New Users (7d)</p></div></div>
                        <div class="stat-card"><div class="stat-icon"><i class="fa-solid fa-percent"></i></div><div class="stat-info"><h3 id="attendanceRate">-</h3><p>Attendance Rate</p></div></div>
                    </div>
                    <canvas id="attendanceChart" height="200"></canvas>
                </div>
                
                <!-- Equipment Tab -->
                <div id="tab-equipment" class="tab-content <?php echo $active_tab == 'equipment' ? 'active' : ''; ?>">
                    <div style="margin-bottom: 20px;">
                        <button class="btn btn-primary" onclick="openAddEquipmentModal()"><i class="fa-solid fa-plus"></i> Add Equipment</button>
                    </div>
                    
                    <?php while($item = mysqli_fetch_assoc($equipment_result)): ?>
                    <div class="equipment-card">
                        <div class="equipment-header">
                            <span class="equipment-name"><i class="fa-solid <?php echo $item['image_icon']; ?>"></i> <?php echo htmlspecialchars($item['name']); ?></span>
                            <span class="<?php echo $item['maintenance_mode'] ? 'maintenance-badge' : 'available-badge'; ?>">
                                <?php echo $item['maintenance_mode'] ? 'Maintenance' : 'Available'; ?>
                            </span>
                        </div>
                        <div class="equipment-details" style="margin: 10px 0; color: #64748b;">
                            <span><i class="fa-solid fa-tag"></i> <?php echo htmlspecialchars($item['category_name'] ?? 'Uncategorized'); ?></span>
                            <span style="margin-left: 20px;"><i class="fa-solid fa-cubes"></i> Total: <?php echo $item['quantity']; ?></span>
                            <span style="margin-left: 20px;"><i class="fa-solid fa-check-circle"></i> Available: <?php echo $item['available']; ?></span>
                        </div>
                        <div class="action-buttons">
                            <button class="btn btn-primary btn-sm" onclick="editEquipment(<?php echo $item['equipment_id']; ?>)">Edit</button>
                            <button class="btn btn-warning btn-sm" onclick="toggleMaintenance(<?php echo $item['equipment_id']; ?>, <?php echo $item['maintenance_mode'] ? '0' : '1'; ?>)">
                                <?php echo $item['maintenance_mode'] ? 'Remove Maintenance' : 'Set Maintenance'; ?>
                            </button>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this equipment?')">
                                <input type="hidden" name="equipment_id" value="<?php echo $item['equipment_id']; ?>">
                                <button type="submit" name="delete_equipment" class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
                
                <!-- Classes Tab -->
                <div id="tab-classes" class="tab-content <?php echo $active_tab == 'classes' ? 'active' : ''; ?>">
                    <div style="margin-bottom: 20px;">
                        <button class="btn btn-primary" onclick="openAddClassModal()"><i class="fa-solid fa-plus"></i> Add Class</button>
                    </div>
                    
                    <?php while($class = mysqli_fetch_assoc($classes_result)): 
                        $bookings_count_sql = "SELECT COUNT(*) as count FROM class_bookings WHERE class_id = ? AND status = 'booked'";
                        $bookings_stmt = mysqli_prepare($conn, $bookings_count_sql);
                        mysqli_stmt_bind_param($bookings_stmt, "i", $class['class_id']);
                        mysqli_stmt_execute($bookings_stmt);
                        $bookings_result_count = mysqli_stmt_get_result($bookings_stmt);
                        $booked_count = mysqli_fetch_assoc($bookings_result_count)['count'];
                    ?>
                    <div class="class-card">
                        <div class="class-header">
                            <span class="class-name"><?php echo htmlspecialchars($class['name']); ?></span>
                            <span class="class-time"><i class="fa-regular fa-clock"></i> <?php echo $class['time']; ?></span>
                        </div>
                        <div class="class-details" style="margin: 10px 0; color: #64748b;">
                            <span><i class="fa-solid fa-user"></i> Instructor: <?php echo htmlspecialchars($class['instructor']); ?></span>
                            <span style="margin-left: 20px;"><i class="fa-solid fa-location-dot"></i> <?php echo $class['location'] ?? 'Main Gym'; ?></span>
                            <span style="margin-left: 20px;"><i class="fa-solid fa-users"></i> Booked: <?php echo $booked_count; ?>/<?php echo $class['capacity']; ?></span>
                        </div>
                        <div class="action-buttons">
                            <button class="btn btn-primary btn-sm" onclick="editClass(<?php echo $class['class_id']; ?>)">Edit</button>
                            <button class="btn btn-secondary btn-sm" onclick="viewClassBookings(<?php echo $class['class_id']; ?>)">View Bookings</button>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this class?')">
                                <input type="hidden" name="class_id" value="<?php echo $class['class_id']; ?>">
                                <button type="submit" name="delete_class" class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
                
                <!-- Settings Tab -->
                <div id="tab-settings" class="tab-content <?php echo $active_tab == 'settings' ? 'active' : ''; ?>">
                    <div class="form-container" style="max-width: 100%;">
                        <h3>Gym Settings</h3>
                        <form method="POST">
                            <?php foreach($gym_settings as $key => $value): ?>
                            <div class="setting-item">
                                <span class="setting-label"><?php echo ucwords(str_replace('_', ' ', $key)); ?></span>
                                <span class="setting-value">
                                    <?php if($key == 'waitlist_enabled'): ?>
                                        <select name="settings[<?php echo $key; ?>]">
                                            <option value="1" <?php echo $value == '1' ? 'selected' : ''; ?>>Enabled</option>
                                            <option value="0" <?php echo $value == '0' ? 'selected' : ''; ?>>Disabled</option>
                                        </select>
                                    <?php else: ?>
                                        <input type="number" name="settings[<?php echo $key; ?>]" value="<?php echo $value; ?>">
                                    <?php endif; ?>
                                </span>
                            </div>
                            <?php endforeach; ?>
                            <button type="submit" name="update_settings" class="btn btn-primary" style="margin-top: 20px;">Save Settings</button>
                        </form>
                    </div>
                </div>
                
                <!-- Announcements Tab -->
                <div id="tab-announcements" class="tab-content <?php echo $active_tab == 'announcements' ? 'active' : ''; ?>">
                    <div class="form-container" style="margin-bottom: 30px;">
                        <h3>Create Announcement</h3>
                        <form method="POST">
                            <div class="form-group"><label>Title</label><input type="text" name="title" required></div>
                            <div class="form-group"><label>Message</label><textarea name="message" rows="3" required></textarea></div>
                            <div class="form-group"><label>Expires At (Optional)</label><input type="datetime-local" name="expires_at"></div>
                            <button type="submit" name="add_announcement" class="btn btn-primary">Publish</button>
                        </form>
                    </div>
                    
                    <h3>Existing Announcements</h3>
                    <?php while($ann = mysqli_fetch_assoc($announcements_result)): ?>
                    <div class="equipment-card">
                        <div class="equipment-header">
                            <span class="equipment-name"><?php echo htmlspecialchars($ann['title']); ?></span>
                            <span class="<?php echo $ann['is_active'] ? 'available-badge' : 'maintenance-badge'; ?>">
                                <?php echo $ann['is_active'] ? 'Active' : 'Expired'; ?>
                            </span>
                        </div>
                        <div class="equipment-details" style="margin: 10px 0; color: #64748b;">
                            <?php echo nl2br(htmlspecialchars($ann['message'])); ?>
                        </div>
                        <div style="font-size: 12px; color: #94a3b8;">
                            Created by <?php echo htmlspecialchars($ann['creator_name']); ?> on <?php echo date('M d, Y', strtotime($ann['created_at'])); ?>
                            <?php if($ann['expires_at']): ?> • Expires: <?php echo date('M d, Y', strtotime($ann['expires_at'])); ?><?php endif; ?>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add/Edit Equipment Modal -->
    <div class="modal" id="equipmentModal">
        <div class="modal-content">
            <div class="modal-header"><h3 id="equipmentModalTitle">Add Equipment</h3><button class="modal-close" onclick="closeModal('equipmentModal')">&times;</button></div>
            <form method="POST" id="equipmentForm">
                <div class="modal-body">
                    <input type="hidden" name="equipment_id" id="equipment_id">
                    <div class="form-group"><label>Name</label><input type="text" name="name" id="equipment_name" required></div>
                    <div class="form-group"><label>Category</label>
                        <select name="category_id" id="equipment_category">
                            <option value="">Select Category</option>
                            <?php 
                            mysqli_data_seek($categories_result, 0);
                            while($cat = mysqli_fetch_assoc($categories_result)): ?>
                            <option value="<?php echo $cat['category_id']; ?>"><?php echo htmlspecialchars($cat['category_name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>Total Quantity</label><input type="number" name="quantity" id="equipment_quantity" min="1" required></div>
                        <div class="form-group"><label>Available</label><input type="number" name="available" id="equipment_available" min="0" required></div>
                    </div>
                    <div class="form-group"><label>Icon (Font Awesome class)</label><input type="text" name="image_icon" id="equipment_icon" placeholder="fa-dumbbell"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('equipmentModal')">Cancel</button>
                    <button type="submit" name="add_equipment" class="btn btn-primary" id="equipmentSubmitBtn">Add Equipment</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Add/Edit Class Modal -->
    <div class="modal" id="classModal">
        <div class="modal-content">
            <div class="modal-header"><h3 id="classModalTitle">Add Class</h3><button class="modal-close" onclick="closeModal('classModal')">&times;</button></div>
            <form method="POST" id="classForm">
                <div class="modal-body">
                    <input type="hidden" name="class_id" id="class_id">
                    <div class="form-group"><label>Class Name</label><input type="text" name="name" id="class_name" required></div>
                    <div class="form-row">
                        <div class="form-group"><label>Time</label><input type="time" name="time" id="class_time" required></div>
                        <div class="form-group"><label>Capacity</label><input type="number" name="capacity" id="class_capacity" min="1" required></div>
                    </div>
                    <div class="form-group"><label>Instructor</label><input type="text" name="instructor" id="class_instructor" required></div>
                    <div class="form-group"><label>Location</label><input type="text" name="location" id="class_location"></div>
                    <div class="form-group"><label>Description</label><textarea name="description" id="class_description" rows="3"></textarea></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('classModal')">Cancel</button>
                    <button type="submit" name="add_class" class="btn btn-primary" id="classSubmitBtn">Add Class</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function openAddEquipmentModal() {
            document.getElementById('equipmentModalTitle').textContent = 'Add Equipment';
            document.getElementById('equipmentForm').reset();
            document.getElementById('equipment_id').value = '0';
            document.getElementById('equipmentSubmitBtn').name = 'add_equipment';
            document.getElementById('equipmentModal').classList.add('show');
        }
        
        function editEquipment(id) {
            fetch('get_equipment.php?id=' + id)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('equipmentModalTitle').textContent = 'Edit Equipment';
                    document.getElementById('equipment_id').value = data.equipment_id;
                    document.getElementById('equipment_name').value = data.name;
                    document.getElementById('equipment_category').value = data.category_id;
                    document.getElementById('equipment_quantity').value = data.quantity;
                    document.getElementById('equipment_available').value = data.available;
                    document.getElementById('equipment_icon').value = data.image_icon;
                    document.getElementById('equipmentSubmitBtn').name = 'add_equipment';
                    document.getElementById('equipmentModal').classList.add('show');
                });
        }
        
        function toggleMaintenance(id, mode) {
            let form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `<input type="hidden" name="equipment_id" value="${id}">
                              <input type="hidden" name="maintenance_mode" value="${mode}">
                              <input type="hidden" name="toggle_maintenance" value="1">`;
            document.body.appendChild(form);
            form.submit();
        }
        
        function openAddClassModal() {
            document.getElementById('classModalTitle').textContent = 'Add Class';
            document.getElementById('classForm').reset();
            document.getElementById('class_id').value = '0';
            document.getElementById('classSubmitBtn').name = 'add_class';
            document.getElementById('classModal').classList.add('show');
        }
        
        function editClass(id) {
            fetch('get_class.php?id=' + id)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('classModalTitle').textContent = 'Edit Class';
                    document.getElementById('class_id').value = data.class_id;
                    document.getElementById('class_name').value = data.name;
                    document.getElementById('class_time').value = data.time;
                    document.getElementById('class_capacity').value = data.capacity;
                    document.getElementById('class_instructor').value = data.instructor;
                    document.getElementById('class_location').value = data.location || '';
                    document.getElementById('class_description').value = data.description || '';
                    document.getElementById('classSubmitBtn').name = 'update_class';
                    document.getElementById('classModal').classList.add('show');
                });
        }
        
        function viewClassBookings(classId) {
            window.open('class_bookings.php?class_id=' + classId, '_blank');
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('show');
        }
        
        window.onclick = function(event) {
            if(event.target.classList.contains('modal')) {
                event.target.classList.remove('show');
            }
        }
        
        // Load dashboard charts
        fetch('get_gym_admin_stats.php')
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    document.getElementById('popularClass').textContent = data.popular_class;
                    document.getElementById('newUsers').textContent = data.new_users;
                    document.getElementById('attendanceRate').textContent = data.attendance_rate + '%';
                    
                    new Chart(document.getElementById('attendanceChart'), {
                        type: 'line',
                        data: {
                            labels: data.attendance_labels,
                            datasets: [{
                                label: 'Daily Attendance',
                                data: data.attendance_values,
                                borderColor: '#667eea',
                                backgroundColor: 'rgba(102, 126, 234, 0.1)',
                                tension: 0.4,
                                fill: true
                            }]
                        },
                        options: { responsive: true, maintainAspectRatio: true }
                    });
                }
            });
    </script>
</body>
</html>