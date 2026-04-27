<?php
require_once 'middleware.php';
require_once 'config.php';
checkAdminAuth();

$admin = getAdminInfo($conn);
$message = '';
$error = '';

// Handle menu operations
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_item']) || isset($_POST['edit_item'])) {
        $item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $category = mysqli_real_escape_string($conn, $_POST['category']);
        $price = floatval($_POST['price']);
        $points_price = intval($_POST['points_price']);
        $available = isset($_POST['available']) ? 1 : 0;
        $stock = intval($_POST['stock']);
        
        // Handle image upload
        $image_icon = $_POST['image_icon'] ?? 'fa-utensils';
        
        if ($item_id > 0) {
            $sql = "UPDATE cafe_menu SET name=?, category=?, price=?, points_price=?, available=?, stock=? WHERE item_id=?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ssdiiii", $name, $category, $price, $points_price, $available, $stock, $item_id);
            
            if (mysqli_stmt_execute($stmt)) {
                $message = "Menu item updated successfully!";
                logAdminActivity($conn, 'UPDATE_MENU', "Updated menu item: $name");
            } else {
                $error = "Error updating item: " . mysqli_error($conn);
            }
        } else {
            $sql = "INSERT INTO cafe_menu (name, category, price, points_price, available, stock, image_icon) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ssdiiis", $name, $category, $price, $points_price, $available, $stock, $image_icon);
            
            if (mysqli_stmt_execute($stmt)) {
                $message = "Menu item added successfully!";
                logAdminActivity($conn, 'ADD_MENU', "Added menu item: $name");
            } else {
                $error = "Error adding item: " . mysqli_error($conn);
            }
        }
    }
    
    if (isset($_POST['delete_item'])) {
        $item_id = intval($_POST['item_id']);
        
        $delete_sql = "DELETE FROM cafe_menu WHERE item_id = ?";
        $delete_stmt = mysqli_prepare($conn, $delete_sql);
        mysqli_stmt_bind_param($delete_stmt, "i", $item_id);
        
        if (mysqli_stmt_execute($delete_stmt)) {
            $message = "Menu item deleted successfully!";
            logAdminActivity($conn, 'DELETE_MENU', "Deleted menu item ID: $item_id");
        } else {
            $error = "Error deleting item: " . mysqli_error($conn);
        }
    }
    
    if (isset($_POST['add_offer'])) {
        $facility_id = intval($_POST['facility_id']);
        $title = mysqli_real_escape_string($conn, $_POST['title']);
        $description = mysqli_real_escape_string($conn, $_POST['description']);
        $discount_type = mysqli_real_escape_string($conn, $_POST['discount_type']);
        $discount_value = intval($_POST['discount_value']);
        $original_price = floatval($_POST['original_price']);
        $offer_price = floatval($_POST['offer_price']);
        $points_required = intval($_POST['points_required']);
        $valid_from = $_POST['valid_from'];
        $valid_until = $_POST['valid_until'];
        
        $sql = "INSERT INTO special_offers (facility_id, title, description, discount_type, discount_value, original_price, offer_price, points_required, valid_from, valid_until) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "isssiddiss", $facility_id, $title, $description, $discount_type, $discount_value, $original_price, $offer_price, $points_required, $valid_from, $valid_until);
        
        if (mysqli_stmt_execute($stmt)) {
            $message = "Special offer added successfully!";
            logAdminActivity($conn, 'ADD_OFFER', "Added offer: $title");
        } else {
            $error = "Error adding offer: " . mysqli_error($conn);
        }
    }
}

// Get menu items
$menu_sql = "SELECT * FROM cafe_menu ORDER BY category, name";
$menu_result = mysqli_query($conn, $menu_sql);

// Get special offers
$offers_sql = "SELECT so.*, f.Name as facility_name 
               FROM special_offers so
               JOIN Facilities f ON so.facility_id = f.FacilityID
               ORDER BY so.valid_until ASC";
$offers_result = mysqli_query($conn, $offers_sql);

// Get facilities for offers dropdown
$facilities_sql = "SELECT FacilityID, Name FROM Facilities WHERE Type IN ('Café', 'Restaurant')";
$facilities_result = mysqli_query($conn, $facilities_sql);

// Group menu by category
$menu_by_category = [];
while($item = mysqli_fetch_assoc($menu_result)) {
    $menu_by_category[$item['category']][] = $item;
}
mysqli_data_seek($menu_result, 0);

// Available icons
$icons = [
    'fa-utensils' => 'Utensils',
    'fa-pizza-slice' => 'Pizza',
    'fa-hamburger' => 'Burger',
    'fa-coffee' => 'Coffee',
    'fa-mug-hot' => 'Hot Drink',
    'fa-wine-bottle' => 'Wine',
    'fa-cake-candles' => 'Cake',
    'fa-ice-cream' => 'Ice Cream',
    'fa-bowl-food' => 'Bowl',
    'fa-mug-saucer' => 'Mug'
];

// Handle tab parameter from URL
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'menu';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Café Menu Management - Synergy Hub Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="admin.css">
    <style>
        .menu-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 10px;
            flex-wrap: wrap;
        }
        
        .menu-tab {
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
        
        .menu-tab:hover {
            background: #f1f5f9;
            color: #1e293b;
        }
        
        .menu-tab.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .category-section {
            margin-bottom: 30px;
        }
        
        .category-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            background: #f8fafc;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        
        .category-title {
            font-size: 18px;
            font-weight: 600;
            color: #1e293b;
        }
        
        .item-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 15px;
        }
        
        .menu-item-card {
            background: white;
            border-radius: 12px;
            padding: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
            position: relative;
        }
        
        .menu-item-card.unavailable {
            opacity: 0.7;
            background: #f1f5f9;
        }
        
        .item-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
        }
        
        .item-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
        }
        
        .item-name {
            font-size: 16px;
            font-weight: 600;
            color: #1e293b;
        }
        
        .item-category {
            font-size: 12px;
            color: #667eea;
        }
        
        .item-prices {
            display: flex;
            gap: 15px;
            margin: 10px 0;
            padding: 10px;
            background: #f8fafc;
            border-radius: 8px;
        }
        
        .price-tag {
            flex: 1;
            text-align: center;
        }
        
        .price-tag small {
            display: block;
            font-size: 11px;
            color: #64748b;
        }
        
        .price-tag span {
            font-size: 16px;
            font-weight: 600;
            color: #667eea;
        }
        
        .stock-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .stock-low {
            background: #fff7ed;
            color: #ea580c;
        }
        
        .stock-out {
            background: #fee;
            color: #ef4444;
        }
        
        .stock-ok {
            background: #dcfce7;
            color: #16a34a;
        }
        
        .availability-toggle {
            margin: 10px 0;
            display: flex;
            align-items: center;
            gap: 10px;
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
        
        .drag-handle {
            cursor: move;
            color: #94a3b8;
            font-size: 16px;
        }
        
        .offer-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            border-left: 4px solid #667eea;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .offer-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .offer-title {
            font-size: 18px;
            font-weight: 600;
            color: #1e293b;
        }
        
        .offer-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-percentage {
            background: #e0f2fe;
            color: #0284c7;
        }
        
        .badge-points {
            background: #fef9c3;
            color: #ca8a04;
        }
        
        .badge-bogo {
            background: #dcfce7;
            color: #16a34a;
        }
        
        .offer-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
            margin: 10px 0;
        }
        
        .offer-detail {
            font-size: 13px;
            color: #475569;
        }
        
        .offer-detail i {
            color: #667eea;
            width: 20px;
        }
        
        .validity {
            font-size: 12px;
            color: #ef4444;
        }
        
        .preview-panel {
            background: #f8fafc;
            border-radius: 12px;
            padding: 20px;
            margin-top: 20px;
        }
        
        .preview-card {
            background: white;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            max-width: 300px;
        }
        
        .stock-control {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .stock-control button {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            border: none;
            background: #667eea;
            color: white;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
        }
        
        .stock-control button:hover {
            background: #764ba2;
        }
        
        .stock-control input {
            width: 60px;
            text-align: center;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 5px;
        }
        
        .form-container {
            background: white;
            border-radius: 12px;
            padding: 25px;
            max-width: 800px;
            margin: 0 auto;
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
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
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
        
        .checkbox-item input[type="checkbox"] {
            width: auto;
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
            margin-top: 10px;
            flex-wrap: wrap;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: #f1f5f9;
            color: #475569;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }
        
        .btn-secondary:hover {
            background: #e2e8f0;
        }
        
        .btn-danger {
            background: #ef4444;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }
        
        .btn-danger:hover {
            background: #dc2626;
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
        
        .category-item {
            background: #f8fafc;
            border-radius: 8px;
            padding: 10px;
            margin-bottom: 10px;
        }
        
        .sortable-categories .category-header {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .item-grid {
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
                    <i class="fa-solid fa-mug-saucer"></i> Café Menu Management
                </h1>
                
                <?php if($message): ?>
                    <div class="alert alert-success"><?php echo $message; ?></div>
                <?php endif; ?>
                <?php if($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <!-- Tabs with URL parameters -->
                <div class="menu-tabs">
                    <a href="cafe_menu_admin.php?tab=menu" class="menu-tab <?php echo $active_tab == 'menu' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-list"></i> Menu Items
                    </a>
                    <a href="cafe_menu_admin.php?tab=add" class="menu-tab <?php echo $active_tab == 'add' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-plus"></i> Add Item
                    </a>
                    <a href="cafe_menu_admin.php?tab=offers" class="menu-tab <?php echo $active_tab == 'offers' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-tag"></i> Special Offers
                    </a>
                    <a href="cafe_menu_admin.php?tab=categories" class="menu-tab <?php echo $active_tab == 'categories' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-layer-group"></i> Categories
                    </a>
                </div>
                
                <!-- Tab: Menu Items -->
                <div id="tab-menu" class="tab-content <?php echo $active_tab == 'menu' ? 'active' : ''; ?>">
                    <?php if(!empty($menu_by_category)): ?>
                        <?php foreach($menu_by_category as $category => $items): ?>
                        <div class="category-section" data-category="<?php echo $category; ?>">
                            <div class="category-header">
                                <h3 class="category-title"><?php echo $category; ?></h3>
                                <button class="btn btn-secondary btn-sm" onclick="editCategory('<?php echo $category; ?>')">
                                    <i class="fa-regular fa-pen-to-square"></i> Edit
                                </button>
                            </div>
                            
                            <div class="item-grid sortable-items">
                                <?php foreach($items as $item): ?>
                                <div class="menu-item-card <?php echo $item['available'] ? '' : 'unavailable'; ?>" data-id="<?php echo $item['item_id']; ?>">
                                    <div class="drag-handle">
                                        <i class="fa-solid fa-grip-vertical"></i>
                                    </div>
                                    
                                    <div class="item-header">
                                        <div class="item-icon">
                                            <i class="fa-solid <?php echo $item['image_icon'] ?? 'fa-utensils'; ?>"></i>
                                        </div>
                                        <div>
                                            <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                            <div class="item-category"><?php echo $item['category']; ?></div>
                                        </div>
                                    </div>
                                    
                                    <?php
                                    $stock = $item['stock'] ?? 20;
                                    $stock_class = 'stock-ok';
                                    if ($stock <= 0) $stock_class = 'stock-out';
                                    else if ($stock <= 5) $stock_class = 'stock-low';
                                    ?>
                                    <span class="stock-badge <?php echo $stock_class; ?>">
                                        Stock: <?php echo $stock; ?>
                                    </span>
                                    
                                    <div class="item-prices">
                                        <div class="price-tag">
                                            <small>Cash</small>
                                            <span>Rs. <?php echo number_format($item['price'], 2); ?></span>
                                        </div>
                                        <div class="price-tag">
                                            <small>Points</small>
                                            <span><?php echo $item['points_price']; ?> ⭐</span>
                                        </div>
                                    </div>
                                    
                                    <div class="availability-toggle">
                                        <label class="toggle-switch">
                                            <input type="checkbox" class="item-available" 
                                                   data-id="<?php echo $item['item_id']; ?>" 
                                                   <?php echo $item['available'] ? 'checked' : ''; ?>>
                                            <span class="toggle-slider"></span>
                                        </label>
                                        <span>Available Today</span>
                                    </div>
                                    
                                    <div class="action-buttons">
                                        <button class="btn btn-primary btn-sm" onclick="editMenuItem(<?php echo $item['item_id']; ?>)">
                                            <i class="fa-regular fa-pen-to-square"></i> Edit
                                        </button>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this item?')">
                                            <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
                                            <button type="submit" name="delete_item" class="btn btn-danger btn-sm">
                                                <i class="fa-regular fa-trash-can"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="text-align: center; padding: 50px; color: #64748b;">
                            <i class="fa-solid fa-utensils" style="font-size: 48px; margin-bottom: 20px;"></i>
                            <h3>No menu items found</h3>
                            <p>Click "Add Item" to create your first menu item.</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Tab: Add Item -->
                <div id="tab-add" class="tab-content <?php echo $active_tab == 'add' ? 'active' : ''; ?>">
                    <div class="form-container">
                        <h3>Add New Menu Item</h3>
                        
                        <form method="POST" id="menuForm">
                            <input type="hidden" name="item_id" id="item_id" value="0">
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Item Name</label>
                                    <input type="text" name="name" id="item_name" required>
                                </div>
                                
                                <div class="form-group">
                                    <label>Category</label>
                                    <select name="category" id="item_category" required>
                                        <option value="Food">Food</option>
                                        <option value="Beverage">Beverage</option>
                                        <option value="Dessert">Dessert</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Cash Price (Rs.)</label>
                                    <input type="number" name="price" id="item_price" step="0.01" min="0" required>
                                </div>
                                
                                <div class="form-group">
                                    <label>Points Price</label>
                                    <input type="number" name="points_price" id="item_points" min="0" value="0" required>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Stock Quantity</label>
                                    <div class="stock-control">
                                        <button type="button" onclick="adjustStock(-1)">-</button>
                                        <input type="number" name="stock" id="item_stock" value="20" min="0">
                                        <button type="button" onclick="adjustStock(1)">+</button>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label>Icon</label>
                                    <select name="image_icon" id="item_icon">
                                        <?php foreach($icons as $value => $label): ?>
                                        <option value="<?php echo $value; ?>" <?php echo $value == 'fa-utensils' ? 'selected' : ''; ?>>
                                            <?php echo $label; ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="checkbox-group">
                                <div class="checkbox-item">
                                    <input type="checkbox" name="available" id="item_available" checked>
                                    <label for="item_available">Available Today</label>
                                </div>
                            </div>
                            
                            <!-- Live Preview -->
                            <div class="preview-panel">
                                <h4>Live Preview</h4>
                                <div class="preview-card" id="previewCard">
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <div class="item-icon">
                                            <i class="fa-solid" id="previewIcon">fa-utensils</i>
                                        </div>
                                        <div>
                                            <div id="previewName">Item Name</div>
                                            <div id="previewCategory">Category</div>
                                        </div>
                                    </div>
                                    <div class="item-prices">
                                        <div class="price-tag">
                                            <small>Cash</small>
                                            <span id="previewPrice">Rs. 0.00</span>
                                        </div>
                                        <div class="price-tag">
                                            <small>Points</small>
                                            <span id="previewPoints">0 ⭐</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div style="display: flex; gap: 10px; margin-top: 20px;">
                                <button type="submit" name="add_item" class="btn btn-primary">
                                    <i class="fa-solid fa-save"></i> Save Item
                                </button>
                                <button type="button" class="btn btn-secondary" onclick="resetMenuForm()">
                                    <i class="fa-solid fa-rotate"></i> Reset
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Tab: Special Offers -->
                <div id="tab-offers" class="tab-content <?php echo $active_tab == 'offers' ? 'active' : ''; ?>">
                    <div class="form-container" style="margin-bottom: 30px;">
                        <h3>Create New Offer</h3>
                        
                        <form method="POST">
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Facility</label>
                                    <select name="facility_id" required>
                                        <option value="">Select Café</option>
                                        <?php 
                                        if(mysqli_num_rows($facilities_result) > 0) {
                                            mysqli_data_seek($facilities_result, 0);
                                            while($facility = mysqli_fetch_assoc($facilities_result)): 
                                        ?>
                                        <option value="<?php echo $facility['FacilityID']; ?>">
                                            <?php echo $facility['Name']; ?>
                                        </option>
                                        <?php 
                                            endwhile;
                                        } else {
                                        ?>
                                        <option value="" disabled>No cafés found</option>
                                        <?php } ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label>Offer Title</label>
                                    <input type="text" name="title" required oninput="updateOfferPreview()">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Description</label>
                                <textarea name="description" rows="2" required></textarea>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Discount Type</label>
                                    <select name="discount_type" id="discount_type" onchange="toggleDiscountFields()">
                                        <option value="percentage">Percentage Off</option>
                                        <option value="points">Points Required</option>
                                        <option value="bogo">Buy One Get One</option>
                                    </select>
                                </div>
                                
                                <div class="form-group" id="discount_value_field">
                                    <label>Discount Value (%)</label>
                                    <input type="number" name="discount_value" min="0" max="100">
                                </div>
                            </div>
                            
                            <div class="form-row" id="price_fields">
                                <div class="form-group">
                                    <label>Original Price (Rs.)</label>
                                    <input type="number" name="original_price" step="0.01" min="0">
                                </div>
                                <div class="form-group">
                                    <label>Offer Price (Rs.)</label>
                                    <input type="number" name="offer_price" step="0.01" min="0">
                                </div>
                            </div>
                            
                            <div class="form-row" id="points_field" style="display: none;">
                                <div class="form-group">
                                    <label>Points Required</label>
                                    <input type="number" name="points_required" min="0" value="100">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Valid From</label>
                                    <input type="date" name="valid_from" value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Valid Until</label>
                                    <input type="date" name="valid_until" value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>" required>
                                </div>
                            </div>
                            
                            <!-- Offer Preview -->
                            <div class="preview-panel">
                                <h4>Offer Preview</h4>
                                <div class="offer-card" id="offerPreview">
                                    <div class="offer-header">
                                        <span class="offer-title">New Offer</span>
                                        <span class="offer-badge badge-percentage">10% OFF</span>
                                    </div>
                                    <div class="offer-details">
                                        <div class="offer-detail">
                                            <i class="fa-regular fa-clock"></i> Valid until: Today
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <button type="submit" name="add_offer" class="btn btn-primary">
                                <i class="fa-solid fa-tag"></i> Create Offer
                            </button>
                        </form>
                    </div>
                    
                    <h3>Active Offers</h3>
                    <?php if(mysqli_num_rows($offers_result) > 0): ?>
                        <?php while($offer = mysqli_fetch_assoc($offers_result)): ?>
                        <div class="offer-card">
                            <div class="offer-header">
                                <div>
                                    <span class="offer-title"><?php echo htmlspecialchars($offer['title']); ?></span>
                                    <div style="font-size: 12px; color: #667eea;"><?php echo $offer['facility_name']; ?></div>
                                </div>
                                <span class="offer-badge badge-<?php echo $offer['discount_type']; ?>">
                                    <?php 
                                    if($offer['discount_type'] == 'percentage') echo $offer['discount_value'] . '% OFF';
                                    else if($offer['discount_type'] == 'points') echo $offer['points_required'] . ' Points';
                                    else echo 'BOGO';
                                    ?>
                                </span>
                            </div>
                            <p style="color: #475569; margin: 10px 0;"><?php echo htmlspecialchars($offer['description']); ?></p>
                            <div class="offer-details">
                                <?php if($offer['original_price']): ?>
                                <div class="offer-detail">
                                    <i class="fa-solid fa-tag"></i> Was: Rs. <?php echo number_format($offer['original_price'], 2); ?>
                                </div>
                                <?php endif; ?>
                                <?php if($offer['offer_price']): ?>
                                <div class="offer-detail">
                                    <i class="fa-solid fa-circle-dollar"></i> Now: Rs. <?php echo number_format($offer['offer_price'], 2); ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="validity">
                                <i class="fa-regular fa-calendar"></i> 
                                <?php echo date('M d, Y', strtotime($offer['valid_from'])); ?> - 
                                <?php echo date('M d, Y', strtotime($offer['valid_until'])); ?>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p style="text-align: center; color: #64748b; padding: 30px;">No special offers found.</p>
                    <?php endif; ?>
                </div>
                
                <!-- Tab: Categories -->
                <div id="tab-categories" class="tab-content <?php echo $active_tab == 'categories' ? 'active' : ''; ?>">
                    <div class="form-container">
                        <h3>Manage Categories</h3>
                        <p>Drag and drop to reorder categories and items</p>
                        
                        <div id="categoryList" class="sortable-categories">
                            <div class="category-item">
                                <div class="category-header">
                                    <i class="fa-solid fa-grip-vertical drag-handle"></i>
                                    <span>Food</span>
                                    <button class="btn btn-secondary btn-sm" onclick="editCategory('Food')">Edit</button>
                                </div>
                            </div>
                            <div class="category-item">
                                <div class="category-header">
                                    <i class="fa-solid fa-grip-vertical drag-handle"></i>
                                    <span>Beverage</span>
                                    <button class="btn btn-secondary btn-sm" onclick="editCategory('Beverage')">Edit</button>
                                </div>
                            </div>
                            <div class="category-item">
                                <div class="category-header">
                                    <i class="fa-solid fa-grip-vertical drag-handle"></i>
                                    <span>Dessert</span>
                                    <button class="btn btn-secondary btn-sm" onclick="editCategory('Dessert')">Edit</button>
                                </div>
                            </div>
                        </div>
                        
                        <div style="margin-top: 20px;">
                            <h4>Add New Category</h4>
                            <div class="form-row">
                                <div class="form-group">
                                    <input type="text" id="new_category_name" placeholder="Category Name">
                                </div>
                                <div>
                                    <button class="btn btn-primary" onclick="addCategory()">Add Category</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    
    <script>
        // Tab switching is handled by URL, but we need to initialize based on URL
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Sortable for items
            document.querySelectorAll('.sortable-items').forEach(el => {
                new Sortable(el, {
                    animation: 150,
                    handle: '.drag-handle',
                    onEnd: function() {
                        saveOrder();
                    }
                });
            });
            
            // Initialize Sortable for categories
            const categoryList = document.getElementById('categoryList');
            if (categoryList) {
                new Sortable(categoryList, {
                    animation: 150,
                    handle: '.drag-handle',
                    onEnd: function() {
                        saveCategoryOrder();
                    }
                });
            }
        });
        
        // Save item order to server
        function saveOrder() {
            const items = [];
            document.querySelectorAll('.menu-item-card').forEach((card, index) => {
                items.push({
                    id: card.dataset.id,
                    order: index
                });
            });
            
            fetch('save_menu_order.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({items: items})
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('Order saved');
                }
            })
            .catch(error => console.error('Error:', error));
        }
        
        // Save category order
        function saveCategoryOrder() {
            const categories = [];
            document.querySelectorAll('.category-item').forEach((item, index) => {
                const name = item.querySelector('span').textContent;
                categories.push({
                    name: name,
                    order: index
                });
            });
            
            console.log('Category order saved:', categories);
            // You can implement server-side saving here
        }
        
        // Stock adjustment
        function adjustStock(change) {
            const stockInput = document.getElementById('item_stock');
            let value = parseInt(stockInput.value) + change;
            if (value < 0) value = 0;
            stockInput.value = value;
            updatePreview();
        }
        
        // Update live preview
        function updatePreview() {
            document.getElementById('previewName').textContent = document.getElementById('item_name').value || 'Item Name';
            document.getElementById('previewCategory').textContent = document.getElementById('item_category').value;
            
            const price = parseFloat(document.getElementById('item_price').value) || 0;
            document.getElementById('previewPrice').textContent = 'Rs. ' + price.toFixed(2);
            
            const points = parseInt(document.getElementById('item_points').value) || 0;
            document.getElementById('previewPoints').textContent = points + ' ⭐';
            
            const icon = document.getElementById('item_icon').value;
            document.getElementById('previewIcon').className = 'fa-solid ' + icon;
        }
        
        // Toggle discount fields
        function toggleDiscountFields() {
            const type = document.getElementById('discount_type').value;
            const priceFields = document.getElementById('price_fields');
            const pointsField = document.getElementById('points_field');
            const discountField = document.getElementById('discount_value_field');
            
            if (type === 'points') {
                priceFields.style.display = 'none';
                pointsField.style.display = 'flex';
                discountField.style.display = 'none';
            } else if (type === 'bogo') {
                priceFields.style.display = 'none';
                pointsField.style.display = 'none';
                discountField.style.display = 'none';
            } else {
                priceFields.style.display = 'flex';
                pointsField.style.display = 'none';
                discountField.style.display = 'block';
            }
        }
        
        // Update offer preview
        function updateOfferPreview() {
            const title = document.querySelector('input[name="title"]').value || 'New Offer';
            const discountType = document.getElementById('discount_type').value;
            const discountValue = document.querySelector('input[name="discount_value"]').value || '10';
            const validUntil = document.querySelector('input[name="valid_until"]').value || 'Today';
            
            document.querySelector('#offerPreview .offer-title').textContent = title;
            
            let badge = document.querySelector('#offerPreview .offer-badge');
            if (discountType === 'percentage') {
                badge.className = 'offer-badge badge-percentage';
                badge.textContent = discountValue + '% OFF';
            } else if (discountType === 'points') {
                badge.className = 'offer-badge badge-points';
                badge.textContent = (document.querySelector('input[name="points_required"]').value || '100') + ' Points';
            } else {
                badge.className = 'offer-badge badge-bogo';
                badge.textContent = 'BOGO';
            }
            
            const dateText = validUntil ? new Date(validUntil).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : 'Today';
            document.querySelector('#offerPreview .offer-detail i.fa-clock').parentElement.innerHTML = 
                '<i class="fa-regular fa-clock"></i> Valid until: ' + dateText;
        }
        
        // Edit menu item
        function editMenuItem(id) {
            fetch('get_menu_item.php?id=' + id)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('item_id').value = data.item_id;
                    document.getElementById('item_name').value = data.name;
                    document.getElementById('item_category').value = data.category;
                    document.getElementById('item_price').value = data.price;
                    document.getElementById('item_points').value = data.points_price;
                    document.getElementById('item_stock').value = data.stock || 20;
                    document.getElementById('item_icon').value = data.image_icon || 'fa-utensils';
                    document.getElementById('item_available').checked = data.available == 1;
                    
                    updatePreview();
                    window.location.href = 'cafe_menu_admin.php?tab=add';
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading menu item');
                });
        }
        
        // Reset form
        function resetMenuForm() {
            document.getElementById('menuForm').reset();
            document.getElementById('item_id').value = '0';
            document.getElementById('item_stock').value = '20';
            document.getElementById('item_available').checked = true;
            updatePreview();
        }
        
        // Edit category
        function editCategory(name) {
            const newName = prompt('Edit category name:', name);
            if (newName && newName !== name) {
                // Update category name in UI
                alert('Category renamed to: ' + newName + ' (This would be saved to server in production)');
            }
        }
        
        // Add category
        function addCategory() {
            const input = document.getElementById('new_category_name');
            const name = input.value.trim();
            if (name) {
                // Add new category to list
                const list = document.getElementById('categoryList');
                const newItem = document.createElement('div');
                newItem.className = 'category-item';
                newItem.innerHTML = `
                    <div class="category-header">
                        <i class="fa-solid fa-grip-vertical drag-handle"></i>
                        <span>${name}</span>
                        <button class="btn btn-secondary btn-sm" onclick="editCategory('${name}')">Edit</button>
                    </div>
                `;
                list.appendChild(newItem);
                input.value = '';
                
                // Reinitialize Sortable for new items
                new Sortable(list, {
                    animation: 150,
                    handle: '.drag-handle',
                    onEnd: saveCategoryOrder
                });
                
                alert('Category added: ' + name + ' (This would be saved to server in production)');
            }
        }
        
        // Input listeners for preview
        document.getElementById('item_name')?.addEventListener('input', updatePreview);
        document.getElementById('item_category')?.addEventListener('change', updatePreview);
        document.getElementById('item_price')?.addEventListener('input', updatePreview);
        document.getElementById('item_points')?.addEventListener('input', updatePreview);
        document.getElementById('item_icon')?.addEventListener('change', updatePreview);
    </script>
</body>
</html>