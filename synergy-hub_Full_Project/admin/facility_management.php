<?php
require_once 'middleware.php';
require_once 'config.php';
checkAdminAuth();

$admin = getAdminInfo($conn);
$message = '';
$error = '';

// Handle facility operations
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_facility']) || isset($_POST['edit_facility'])) {
        $facility_id = isset($_POST['facility_id']) ? intval($_POST['facility_id']) : 0;
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $type = mysqli_real_escape_string($conn, $_POST['type']);
        $status = mysqli_real_escape_string($conn, $_POST['status']);
        $capacity = intval($_POST['capacity']);
        $opening_time = $_POST['opening_time'];
        $closing_time = $_POST['closing_time'];
        $description = mysqli_real_escape_string($conn, $_POST['description']);
        
        // Build ExtraInfo JSON
        $extra_info = [
            'opening_time' => $opening_time,
            'closing_time' => $closing_time,
            'description' => $description
        ];
        
        // Add type-specific fields
        switch($type) {
            case 'Pool':
                $extra_info['lanes'] = intval($_POST['lanes'] ?? 8);
                $extra_info['waterTemp'] = intval($_POST['water_temp'] ?? 27);
                $extra_info['depth'] = $_POST['depth'] ?? '1.2m - 2.5m';
                $extra_info['lifeguards'] = intval($_POST['lifeguards'] ?? 4);
                $extra_info['medicalRequired'] = isset($_POST['medical_required']);
                $extra_info['amenities'] = isset($_POST['amenities']) ? explode(',', $_POST['amenities']) : [];
                break;
            case 'Gym':
                $extra_info['equipment_count'] = intval($_POST['equipment_count'] ?? 50);
                $extra_info['personal_trainers'] = isset($_POST['personal_trainers']);
                $extra_info['locker_rooms'] = isset($_POST['locker_rooms']);
                $extra_info['showers'] = isset($_POST['showers']);
                break;
            case 'Café':
                $extra_info['seating_capacity'] = intval($_POST['seating_capacity'] ?? 80);
                $extra_info['cuisine_type'] = $_POST['cuisine_type'] ?? 'International, Sri Lankan';
                $extra_info['takeaway'] = isset($_POST['takeaway']);
                $extra_info['outdoor_seating'] = isset($_POST['outdoor_seating']);
                break;
            case 'Library':
                $extra_info['study_rooms'] = intval($_POST['study_rooms'] ?? 10);
                $extra_info['computers'] = intval($_POST['computers'] ?? 30);
                $extra_info['printers'] = isset($_POST['printers']);
                $extra_info['wifi'] = isset($_POST['wifi']);
                break;
            case 'GameField':
                $extra_info['field_type'] = $_POST['field_type'] ?? 'Multi-purpose';
                $extra_info['floodlights'] = isset($_POST['floodlights']);
                $extra_info['seating'] = intval($_POST['seating'] ?? 500);
                break;
            case 'Transport':
                $extra_info['bus_stops'] = intval($_POST['bus_stops'] ?? 5);
                $extra_info['routes'] = isset($_POST['routes']) ? explode(',', $_POST['routes']) : [];
                $extra_info['bike_parking'] = isset($_POST['bike_parking']);
                break;
        }
        
        $extra_info_json = json_encode($extra_info);
        
        // Handle image upload
        $image_path = '';
        if (isset($_FILES['facility_image']) && $_FILES['facility_image']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['facility_image']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed)) {
                $upload_dir = 'uploads/facilities/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                $new_filename = 'facility_' . time() . '_' . uniqid() . '.' . $ext;
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['facility_image']['tmp_name'], $upload_path)) {
                    $image_path = $upload_path;
                    $extra_info['image'] = $image_path;
                    $extra_info_json = json_encode($extra_info);
                }
            }
        }
        
        if ($facility_id > 0) {
            // Update existing facility
            $sql = "UPDATE Facilities SET Name=?, Type=?, Status=?, Capacity=?, ExtraInfo=? WHERE FacilityID=?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "sssis", $name, $type, $status, $capacity, $extra_info_json, $facility_id);
            
            if (mysqli_stmt_execute($stmt)) {
                $message = "Facility updated successfully!";
                logAdminActivity($conn, 'UPDATE_FACILITY', "Updated facility: $name");
            } else {
                $error = "Error updating facility: " . mysqli_error($conn);
            }
        } else {
            // Insert new facility
            $sql = "INSERT INTO Facilities (Name, Type, Status, Capacity, ExtraInfo) VALUES (?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "sssis", $name, $type, $status, $capacity, $extra_info_json);
            
            if (mysqli_stmt_execute($stmt)) {
                $facility_id = mysqli_insert_id($conn);
                $message = "Facility added successfully!";
                logAdminActivity($conn, 'ADD_FACILITY', "Added facility: $name");
            } else {
                $error = "Error adding facility: " . mysqli_error($conn);
            }
        }
    }
    
    // Delete facility
    if (isset($_POST['delete_facility'])) {
        $facility_id = intval($_POST['facility_id']);
        
        // Check if facility has check-ins
        $check_sql = "SELECT COUNT(*) as count FROM CheckIns WHERE FacilityID = ?";
        $check_stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($check_stmt, "i", $facility_id);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);
        $check = mysqli_fetch_assoc($check_result);
        
        if ($check['count'] > 0) {
            $error = "Cannot delete facility - it has check-in history";
        } else {
            $delete_sql = "DELETE FROM Facilities WHERE FacilityID = ?";
            $delete_stmt = mysqli_prepare($conn, $delete_sql);
            mysqli_stmt_bind_param($delete_stmt, "i", $facility_id);
            
            if (mysqli_stmt_execute($delete_stmt)) {
                $message = "Facility deleted successfully!";
                logAdminActivity($conn, 'DELETE_FACILITY', "Deleted facility ID: $facility_id");
            } else {
                $error = "Error deleting facility: " . mysqli_error($conn);
            }
        }
    }
    
    // Update crowd data
    if (isset($_POST['update_crowd'])) {
        $facility_id = intval($_POST['facility_id']);
        $current_crowd = intval($_POST['current_crowd']);
        $total_capacity = intval($_POST['total_capacity']);
        
        // Store crowd data in session
        if (!isset($_SESSION['crowd_data'])) {
            $_SESSION['crowd_data'] = [];
        }
        $_SESSION['crowd_data'][$facility_id] = [
            'current' => $current_crowd,
            'total' => $total_capacity,
            'updated_at' => time()
        ];
        
        $message = "Crowd data updated for facility!";
        logAdminActivity($conn, 'UPDATE_CROWD', "Facility ID: $facility_id, Crowd: $current_crowd/$total_capacity");
    }
}

// Get all facilities
$facilities_sql = "SELECT * FROM Facilities ORDER BY Type, Name";
$facilities_result = mysqli_query($conn, $facilities_sql);

// Get facility types for filter
$types = ['Gym', 'Library', 'Café', 'Transport', 'GameField', 'Pool'];

// Get crowd data from session
$crowd_data = $_SESSION['crowd_data'] ?? [];

// Handle tab parameter from URL
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'list';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facility Management - Synergy Hub Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="admin.css">
    <!-- Include Quill.js for rich text editor -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <style>
        .facility-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 10px;
            flex-wrap: wrap;
        }
        
        .facility-tab {
            padding: 10px 20px;
            background: none;
            border: none;
            color: #64748b;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            border-radius: 8px;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .facility-tab:hover {
            background: #f1f5f9;
            color: #1e293b;
        }
        
        .facility-tab.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .facility-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .facility-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border-left: 4px solid #667eea;
            transition: all 0.3s;
        }
        
        .facility-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }
        
        .facility-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        
        .facility-name {
            font-size: 18px;
            font-weight: 600;
            color: #1e293b;
        }
        
        .facility-type {
            font-size: 12px;
            color: #667eea;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .facility-status {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .status-Open {
            background: #dcfce7;
            color: #16a34a;
        }
        
        .status-Closed {
            background: #fee;
            color: #ef4444;
        }
        
        .status-Maintenance {
            background: #fff7ed;
            color: #ea580c;
        }
        
        .facility-details {
            margin: 15px 0;
            font-size: 13px;
            color: #475569;
        }
        
        .facility-details i {
            color: #667eea;
            width: 20px;
        }
        
        .crowd-section {
            background: #f8fafc;
            border-radius: 10px;
            padding: 15px;
            margin: 15px 0;
        }
        
        .crowd-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 13px;
        }
        
        .crowd-bar {
            width: 100%;
            height: 8px;
            background: #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .crowd-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea, #764ba2);
            border-radius: 10px;
            transition: width 0.3s;
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
            margin-top: 15px;
            flex-wrap: wrap;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }
        
        .form-container {
            background: white;
            border-radius: 12px;
            padding: 25px;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .form-section {
            background: #f8fafc;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .form-section h4 {
            color: #1e293b;
            margin-bottom: 15px;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            color: #475569;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 5px;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .time-picker {
            display: flex;
            gap: 10px;
        }
        
        .time-picker input {
            flex: 1;
        }
        
        .image-preview {
            width: 100%;
            height: 200px;
            border: 2px dashed #e2e8f0;
            border-radius: 8px;
            margin-top: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        
        .image-preview img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        
        .checkbox-group {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin: 10px 0;
        }
        
        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .json-editor {
            font-family: monospace;
            min-height: 100px;
        }
        
        .crowd-control {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-top: 20px;
        }
        
        .dual-range {
            display: flex;
            gap: 20px;
            align-items: center;
            margin: 20px 0;
        }
        
        .range-input {
            flex: 1;
        }
        
        .range-values {
            display: flex;
            gap: 10px;
            font-size: 14px;
            color: #64748b;
        }
        
        .range-values span {
            font-weight: 600;
            color: #667eea;
        }
        
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }
        
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }
        
        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        
        input:checked + .toggle-slider {
            background-color: #667eea;
        }
        
        input:checked + .toggle-slider:before {
            transform: translateX(26px);
        }
        
        .chart-container {
            height: 300px;
            margin: 20px 0;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #86efac;
        }
        
        .alert-danger {
            background: #fee;
            color: #991b1b;
            border: 1px solid #fecaca;
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
                    <i class="fa-solid fa-building"></i> Facility Management
                </h1>
                
                <?php if($message): ?>
                    <div class="alert alert-success"><?php echo $message; ?></div>
                <?php endif; ?>
                <?php if($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <!-- Tabs with URL parameters -->
                <div class="facility-tabs">
                    <a href="facility_management.php?tab=list" class="facility-tab <?php echo $active_tab == 'list' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-list"></i> All Facilities
                    </a>
                    <a href="facility_management.php?tab=add" class="facility-tab <?php echo $active_tab == 'add' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-plus"></i> Add Facility
                    </a>
                    <a href="facility_management.php?tab=crowd" class="facility-tab <?php echo $active_tab == 'crowd' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-users"></i> Crowd Management
                    </a>
                </div>
                
                <!-- Tab: Facility List -->
                <div id="tab-list" class="tab-content <?php echo $active_tab == 'list' ? 'active' : ''; ?>">
                    <div class="facility-grid">
                        <?php if(mysqli_num_rows($facilities_result) > 0): ?>
                            <?php while($facility = mysqli_fetch_assoc($facilities_result)): 
                                $extra = json_decode($facility['ExtraInfo'], true) ?? [];
                                $crowd = $crowd_data[$facility['FacilityID']] ?? ['current' => rand(20, 80), 'total' => $facility['Capacity'] ?: 100];
                                $crowd_percent = ($crowd['current'] / $crowd['total']) * 100;
                            ?>
                            <div class="facility-card">
                                <div class="facility-header">
                                    <div>
                                        <div class="facility-name"><?php echo htmlspecialchars($facility['Name']); ?></div>
                                        <div class="facility-type"><?php echo $facility['Type']; ?></div>
                                    </div>
                                    <div>
                                        <span class="facility-status status-<?php echo $facility['Status']; ?>">
                                            <?php echo $facility['Status']; ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="facility-details">
                                    <?php if(isset($extra['opening_time'])): ?>
                                    <div><i class="fa-regular fa-clock"></i> <?php echo $extra['opening_time']; ?> - <?php echo $extra['closing_time']; ?></div>
                                    <?php endif; ?>
                                    <?php if($facility['Capacity']): ?>
                                    <div><i class="fa-solid fa-users"></i> Capacity: <?php echo $facility['Capacity']; ?></div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Crowd Display -->
                                <div class="crowd-section">
                                    <div class="crowd-header">
                                        <span>Current Crowd</span>
                                        <span><?php echo $crowd['current']; ?>/<?php echo $crowd['total']; ?></span>
                                    </div>
                                    <div class="crowd-bar">
                                        <div class="crowd-fill" style="width: <?php echo $crowd_percent; ?>%;"></div>
                                    </div>
                                </div>
                                
                                <!-- Type-specific info -->
                                <?php if($facility['Type'] == 'Pool' && isset($extra['lanes'])): ?>
                                <div class="facility-details">
                                    <i class="fa-solid fa-road"></i> Lanes: <?php echo $extra['lanes']; ?> • 
                                    <i class="fa-solid fa-droplet"></i> Temp: <?php echo $extra['waterTemp']; ?>°C
                                </div>
                                <?php endif; ?>
                                
                                <div class="action-buttons">
                                    <button class="btn btn-primary btn-sm" onclick="editFacility(<?php echo $facility['FacilityID']; ?>)">
                                        <i class="fa-regular fa-pen-to-square"></i> Edit
                                    </button>
                                    <button class="btn btn-secondary btn-sm" onclick="manageCrowd(<?php echo $facility['FacilityID']; ?>, '<?php echo htmlspecialchars($facility['Name']); ?>', <?php echo $crowd['current']; ?>, <?php echo $crowd['total']; ?>)">
                                        <i class="fa-solid fa-users"></i> Crowd
                                    </button>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this facility?')">
                                        <input type="hidden" name="facility_id" value="<?php echo $facility['FacilityID']; ?>">
                                        <button type="submit" name="delete_facility" class="btn btn-danger btn-sm">
                                            <i class="fa-regular fa-trash-can"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div style="grid-column: 1/-1; text-align: center; padding: 50px; color: #64748b;">
                                <i class="fa-solid fa-building" style="font-size: 48px; margin-bottom: 20px;"></i>
                                <h3>No facilities found</h3>
                                <p>Click "Add Facility" to create your first facility.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Tab: Add/Edit Facility -->
                <div id="tab-add" class="tab-content <?php echo $active_tab == 'add' ? 'active' : ''; ?>">
                    <div class="form-container">
                        <h3 style="margin-bottom: 20px;">Add New Facility</h3>
                        
                        <form method="POST" enctype="multipart/form-data" id="facilityForm">
                            <input type="hidden" name="facility_id" id="facility_id" value="0">
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Facility Name</label>
                                    <input type="text" name="name" id="facility_name" required>
                                </div>
                                
                                <div class="form-group">
                                    <label>Facility Type</label>
                                    <select name="type" id="facility_type" onchange="toggleTypeFields()" required>
                                        <option value="">Select Type</option>
                                        <?php foreach($types as $type): ?>
                                        <option value="<?php echo $type; ?>"><?php echo $type; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Status</label>
                                    <select name="status" required>
                                        <option value="Open">Open</option>
                                        <option value="Closed">Closed</option>
                                        <option value="Maintenance">Maintenance</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label>Capacity</label>
                                    <input type="number" name="capacity" min="0" value="100">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Operating Hours</label>
                                <div class="time-picker">
                                    <input type="time" name="opening_time" id="opening_time" value="09:00">
                                    <input type="time" name="closing_time" id="closing_time" value="17:00">
                                </div>
                            </div>
                            
                            <div class="form-section">
                                <h4><i class="fa-regular fa-file-lines"></i> Description (Rich Text)</h4>
                                <div id="editor" style="height: 200px; background: white;"></div>
                                <input type="hidden" name="description" id="description">
                            </div>
                            
                            <div class="form-group">
                                <label>Facility Image</label>
                                <input type="file" name="facility_image" accept="image/*" onchange="previewImage(this)">
                                <div class="image-preview" id="imagePreview">
                                    <i class="fa-regular fa-image" style="font-size: 48px; color: #94a3b8;"></i>
                                </div>
                            </div>
                            
                            <!-- Type-specific fields container -->
                            <div id="typeFields"></div>
                            
                            <div class="form-section">
                                <h4><i class="fa-solid fa-code"></i> ExtraInfo JSON Editor</h4>
                                <textarea name="extra_info_json" id="extraInfoJson" class="json-editor" rows="5" placeholder='{"key": "value"}'></textarea>
                            </div>
                            
                            <div style="display: flex; gap: 10px;">
                                <button type="submit" name="add_facility" class="btn btn-primary">
                                    <i class="fa-solid fa-save"></i> Save Facility
                                </button>
                                <button type="button" class="btn btn-secondary" onclick="resetForm()">
                                    <i class="fa-solid fa-rotate"></i> Reset
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Tab: Crowd Management -->
                <div id="tab-crowd" class="tab-content <?php echo $active_tab == 'crowd' ? 'active' : ''; ?>">
                    <div class="crowd-control">
                        <h3 style="margin-bottom: 20px;">Manual Crowd Adjustment</h3>
                        
                        <form method="POST" id="crowdForm">
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Select Facility</label>
                                    <select name="facility_id" id="crowd_facility" required>
                                        <option value="">Choose a facility</option>
                                        <?php 
                                        mysqli_data_seek($facilities_result, 0);
                                        while($facility = mysqli_fetch_assoc($facilities_result)): 
                                        ?>
                                        <option value="<?php echo $facility['FacilityID']; ?>">
                                            <?php echo $facility['Name']; ?> (<?php echo $facility['Type']; ?>)
                                        </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="dual-range">
                                <div class="range-input">
                                    <label>Current Crowd: <span id="currentDisplay">0</span></label>
                                    <input type="range" name="current_crowd" id="currentCrowd" min="0" max="500" value="0" oninput="updateCrowdDisplay()">
                                </div>
                                <div class="range-input">
                                    <label>Total Capacity: <span id="totalDisplay">100</span></label>
                                    <input type="range" name="total_capacity" id="totalCapacity" min="1" max="500" value="100" oninput="updateCrowdDisplay()">
                                </div>
                            </div>
                            
                            <div class="crowd-section">
                                <div class="crowd-header">
                                    <span>Live Preview</span>
                                    <span id="crowdPercent">0%</span>
                                </div>
                                <div class="crowd-bar">
                                    <div class="crowd-fill" id="crowdBarFill" style="width: 0%;"></div>
                                </div>
                            </div>
                            
                            <div class="form-section">
                                <h4><i class="fa-solid fa-robot"></i> Automation Rules</h4>
                                <div class="checkbox-group">
                                    <div class="checkbox-item">
                                        <input type="checkbox" name="auto_checkins" id="auto_checkins">
                                        <label for="auto_checkins">Update crowd based on check-ins</label>
                                    </div>
                                    <div class="checkbox-item">
                                        <input type="checkbox" name="auto_bookings" id="auto_bookings">
                                        <label for="auto_bookings">Calculate from booking data</label>
                                    </div>
                                    <div class="checkbox-item">
                                        <input type="checkbox" name="auto_schedule" id="auto_schedule">
                                        <label for="auto_schedule">Use scheduled peak hours</label>
                                    </div>
                                </div>
                            </div>
                            
                            <button type="submit" name="update_crowd" class="btn btn-primary">
                                <i class="fa-solid fa-check"></i> Update Crowd Data
                            </button>
                        </form>
                    </div>
                    
                    <!-- Historical Data Graphs -->
                    <div style="margin-top: 30px;">
                        <h3>Historical Crowd Trends</h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Date Range</label>
                                <select id="chartRange" onchange="loadChartData()">
                                    <option value="hourly">Last 24 Hours</option>
                                    <option value="daily">Last 7 Days</option>
                                    <option value="weekly">Last 4 Weeks</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Facility</label>
                                <select id="chartFacility" onchange="loadChartData()">
                                    <option value="all">All Facilities</option>
                                    <?php 
                                    mysqli_data_seek($facilities_result, 0);
                                    while($facility = mysqli_fetch_assoc($facilities_result)): 
                                    ?>
                                    <option value="<?php echo $facility['FacilityID']; ?>">
                                        <?php echo $facility['Name']; ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="chart-container">
                            <canvas id="crowdChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Include Chart.js and Quill -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
    
    <script>
        // Initialize Quill editor
        var quill = new Quill('#editor', {
            theme: 'snow',
            modules: {
                toolbar: [
                    ['bold', 'italic', 'underline', 'strike'],
                    ['blockquote', 'code-block'],
                    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                    [{ 'script': 'sub'}, { 'script': 'super' }],
                    [{ 'indent': '-1'}, { 'indent': '+1' }],
                    [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
                    [{ 'color': [] }, { 'background': [] }],
                    [{ 'font': [] }],
                    [{ 'align': [] }],
                    ['link', 'image'],
                    ['clean']
                ]
            }
        });
        
        // Type-specific fields
        function toggleTypeFields() {
            const type = document.getElementById('facility_type').value;
            const container = document.getElementById('typeFields');
            let html = '';
            
            if (type === 'Pool') {
                html = `
                    <div class="form-section">
                        <h4><i class="fa-solid fa-person-swimming"></i> Pool Details</h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Number of Lanes</label>
                                <input type="number" name="lanes" value="8" min="1">
                            </div>
                            <div class="form-group">
                                <label>Water Temperature (°C)</label>
                                <input type="number" name="water_temp" value="27" min="15" max="35" step="0.5">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Depth Range</label>
                                <input type="text" name="depth" value="1.2m - 2.5m">
                            </div>
                            <div class="form-group">
                                <label>Number of Lifeguards</label>
                                <input type="number" name="lifeguards" value="4" min="0">
                            </div>
                        </div>
                        <div class="checkbox-group">
                            <div class="checkbox-item">
                                <input type="checkbox" name="medical_required" id="medical_required" checked>
                                <label for="medical_required">Medical Report Required</label>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Amenities (comma separated)</label>
                            <input type="text" name="amenities" value="Changing Rooms, Showers, Lockers, Pool Equipment, First Aid Station">
                        </div>
                    </div>
                `;
            } else if (type === 'Gym') {
                html = `
                    <div class="form-section">
                        <h4><i class="fa-solid fa-dumbbell"></i> Gym Details</h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Equipment Count</label>
                                <input type="number" name="equipment_count" value="50" min="0">
                            </div>
                        </div>
                        <div class="checkbox-group">
                            <div class="checkbox-item">
                                <input type="checkbox" name="personal_trainers" id="personal_trainers" checked>
                                <label for="personal_trainers">Personal Trainers Available</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" name="locker_rooms" id="locker_rooms" checked>
                                <label for="locker_rooms">Locker Rooms</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" name="showers" id="showers" checked>
                                <label for="showers">Showers</label>
                            </div>
                        </div>
                    </div>
                `;
            } else if (type === 'Café') {
                html = `
                    <div class="form-section">
                        <h4><i class="fa-solid fa-mug-saucer"></i> Café Details</h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Seating Capacity</label>
                                <input type="number" name="seating_capacity" value="80" min="0">
                            </div>
                            <div class="form-group">
                                <label>Cuisine Type</label>
                                <input type="text" name="cuisine_type" value="International, Sri Lankan">
                            </div>
                        </div>
                        <div class="checkbox-group">
                            <div class="checkbox-item">
                                <input type="checkbox" name="takeaway" id="takeaway" checked>
                                <label for="takeaway">Takeaway Available</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" name="outdoor_seating" id="outdoor_seating">
                                <label for="outdoor_seating">Outdoor Seating</label>
                            </div>
                        </div>
                    </div>
                `;
            } else if (type === 'Library') {
                html = `
                    <div class="form-section">
                        <h4><i class="fa-solid fa-book"></i> Library Details</h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Number of Study Rooms</label>
                                <input type="number" name="study_rooms" value="10" min="0">
                            </div>
                            <div class="form-group">
                                <label>Computer Stations</label>
                                <input type="number" name="computers" value="30" min="0">
                            </div>
                        </div>
                        <div class="checkbox-group">
                            <div class="checkbox-item">
                                <input type="checkbox" name="printers" id="printers" checked>
                                <label for="printers">Printing Services</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" name="wifi" id="wifi" checked>
                                <label for="wifi">Free WiFi</label>
                            </div>
                        </div>
                    </div>
                `;
            } else if (type === 'GameField') {
                html = `
                    <div class="form-section">
                        <h4><i class="fa-solid fa-futbol"></i> Game Field Details</h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Field Type</label>
                                <select name="field_type">
                                    <option value="Football">Football</option>
                                    <option value="Cricket">Cricket</option>
                                    <option value="Rugby">Rugby</option>
                                    <option value="Multi-purpose">Multi-purpose</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Seating Capacity</label>
                                <input type="number" name="seating" value="500" min="0">
                            </div>
                        </div>
                        <div class="checkbox-group">
                            <div class="checkbox-item">
                                <input type="checkbox" name="floodlights" id="floodlights" checked>
                                <label for="floodlights">Floodlights</label>
                            </div>
                        </div>
                    </div>
                `;
            } else if (type === 'Transport') {
                html = `
                    <div class="form-section">
                        <h4><i class="fa-solid fa-bus"></i> Transport Details</h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Number of Bus Stops</label>
                                <input type="number" name="bus_stops" value="5" min="0">
                            </div>
                            <div class="form-group">
                                <label>Routes (comma separated)</label>
                                <input type="text" name="routes" value="City-Walsall, City-Telford">
                            </div>
                        </div>
                        <div class="checkbox-group">
                            <div class="checkbox-item">
                                <input type="checkbox" name="bike_parking" id="bike_parking" checked>
                                <label for="bike_parking">Bike Parking</label>
                            </div>
                        </div>
                    </div>
                `;
            }
            
            container.innerHTML = html;
        }
        
        // Image preview
        function previewImage(input) {
            const preview = document.getElementById('imagePreview');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        // Crowd display update
        function updateCrowdDisplay() {
            const current = document.getElementById('currentCrowd').value;
            const total = document.getElementById('totalCapacity').value;
            const percent = (current / total) * 100;
            
            document.getElementById('currentDisplay').textContent = current;
            document.getElementById('totalDisplay').textContent = total;
            document.getElementById('crowdPercent').textContent = Math.round(percent) + '%';
            document.getElementById('crowdBarFill').style.width = percent + '%';
        }
        
        // Manage crowd for specific facility
        function manageCrowd(id, name, current, total) {
            window.location.href = 'facility_management.php?tab=crowd&facility_id=' + id + '&current=' + current + '&total=' + total;
        }
        
        // Edit facility
        function editFacility(id) {
            fetch('get_facility.php?id=' + id)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('facility_id').value = data.FacilityID;
                    document.getElementById('facility_name').value = data.Name;
                    document.getElementById('facility_type').value = data.Type;
                    document.querySelector('select[name="status"]').value = data.Status;
                    document.querySelector('input[name="capacity"]').value = data.Capacity;
                    
                    const extra = JSON.parse(data.ExtraInfo || '{}');
                    if (extra.opening_time) {
                        document.getElementById('opening_time').value = extra.opening_time;
                        document.getElementById('closing_time').value = extra.closing_time;
                    }
                    if (extra.description) {
                        quill.root.innerHTML = extra.description;
                    }
                    
                    toggleTypeFields();
                    
                    // Fill type-specific fields
                    if (data.Type === 'Pool' && extra) {
                        const lanesInput = document.querySelector('input[name="lanes"]');
                        const tempInput = document.querySelector('input[name="water_temp"]');
                        const depthInput = document.querySelector('input[name="depth"]');
                        const lifeguardsInput = document.querySelector('input[name="lifeguards"]');
                        const medicalCheckbox = document.getElementById('medical_required');
                        const amenitiesInput = document.querySelector('input[name="amenities"]');
                        
                        if (lanesInput) lanesInput.value = extra.lanes || 8;
                        if (tempInput) tempInput.value = extra.waterTemp || 27;
                        if (depthInput) depthInput.value = extra.depth || '1.2m - 2.5m';
                        if (lifeguardsInput) lifeguardsInput.value = extra.lifeguards || 4;
                        if (medicalCheckbox) medicalCheckbox.checked = extra.medicalRequired || false;
                        if (amenitiesInput && extra.amenities) amenitiesInput.value = extra.amenities.join(', ');
                    }
                    
                    // Switch to add tab
                    window.location.href = 'facility_management.php?tab=add';
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading facility data');
                });
        }
        
        // Reset form
        function resetForm() {
            document.getElementById('facilityForm').reset();
            document.getElementById('facility_id').value = '0';
            quill.root.innerHTML = '';
            document.getElementById('imagePreview').innerHTML = '<i class="fa-regular fa-image" style="font-size: 48px; color: #94a3b8;"></i>';
            document.getElementById('typeFields').innerHTML = '';
        }
        
        // Submit form with Quill content
        document.getElementById('facilityForm').addEventListener('submit', function() {
            document.getElementById('description').value = quill.root.innerHTML;
        });
        
        // Chart initialization
        let chart;
        function loadChartData() {
            const range = document.getElementById('chartRange').value;
            const facility = document.getElementById('chartFacility').value;
            
            // Generate sample data
            const labels = range === 'hourly' ? 
                Array.from({length: 24}, (_, i) => i + ':00') :
                range === 'daily' ?
                ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] :
                ['Week 1', 'Week 2', 'Week 3', 'Week 4'];
            
            const data = labels.map(() => Math.floor(Math.random() * 80) + 20);
            
            if (chart) chart.destroy();
            
            const ctx = document.getElementById('crowdChart').getContext('2d');
            chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Crowd Level',
                        data: data,
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
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100
                        }
                    }
                }
            });
        }
        
        // Load chart on page load if crowd tab is active
        document.addEventListener('DOMContentLoaded', function() {
            if (document.getElementById('tab-crowd').classList.contains('active')) {
                loadChartData();
            }
            
            // Check if we have facility ID in URL for crowd management
            const urlParams = new URLSearchParams(window.location.search);
            const facilityId = urlParams.get('facility_id');
            const current = urlParams.get('current');
            const total = urlParams.get('total');
            
            if (facilityId && current && total) {
                document.getElementById('crowd_facility').value = facilityId;
                document.getElementById('currentCrowd').value = current;
                document.getElementById('totalCapacity').value = total;
                updateCrowdDisplay();
            }
        });
    </script>
</body>
</html>