<?php
// admin/includes/sidebar.php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<div class="sidebar">
    <div class="sidebar-header">
        <h2>Synergy <span>Hub</span></h2>
        <p>Admin Panel</p>
    </div>
    
    <nav class="sidebar-nav">
        <!-- Dashboard -->
        <a href="index.php" class="<?php echo $current_page == 'index.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-gauge-high"></i>
            <span>Dashboard</span>
        </a>
        
        <!-- User Management -->
        <div class="nav-section">
            <div class="nav-section-title">User Management</div>
            <a href="users.php" class="<?php echo $current_page == 'users.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-users"></i>
                <span>All Users</span>
            </a>
            <a href="points.php" class="<?php echo $current_page == 'points.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-star"></i>
                <span>Points System</span>
            </a>

            <a href="user_stats.php" class="<?php echo $current_page == 'user_stats.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-chart-line"></i>
                <span>User Statistics</span>
            </a>
            <a href="login_history.php" class="<?php echo $current_page == 'login_history.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-clock-rotate-left"></i>
                <span>Login History</span>
            </a>

        </div>
        
        <!-- Facility Management -->
        <div class="nav-section">
            <div class="nav-section-title">Facility Management</div>
            <a href="facility_management.php" class="<?php echo $current_page == 'facility_management.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-building"></i>
                <span>All Facilities</span>
            </a>
            <a href="facility_management.php?tab=add" class="<?php echo $current_page == 'facility_management.php' && isset($_GET['tab']) && $_GET['tab'] == 'add' ? 'active' : ''; ?>">
                <i class="fa-solid fa-plus-circle"></i>
                <span>Add Facility</span>
            </a>
            <a href="facility_management.php?tab=crowd" class="<?php echo $current_page == 'facility_management.php' && isset($_GET['tab']) && $_GET['tab'] == 'crowd' ? 'active' : ''; ?>">
                <i class="fa-solid fa-users"></i>
                <span>Crowd Management</span>
            </a>
        </div>
        
        <!-- Café Management -->
        <div class="nav-section">
            <div class="nav-section-title">Café Management</div>
            <a href="cafe_menu_admin.php" class="<?php echo $current_page == 'cafe_menu_admin.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-mug-saucer"></i>
                <span>Menu Items</span>
            </a>
            <a href="cafe_menu_admin.php?tab=offers" class="<?php echo $current_page == 'cafe_menu_admin.php' && isset($_GET['tab']) && $_GET['tab'] == 'offers' ? 'active' : ''; ?>">
                <i class="fa-solid fa-tag"></i>
                <span>Special Offers</span>
            </a>
            <a href="cafe_menu_admin.php?tab=add" class="<?php echo $current_page == 'cafe_menu_admin.php' && isset($_GET['tab']) && $_GET['tab'] == 'add' ? 'active' : ''; ?>">
                <i class="fa-solid fa-plus"></i>
                <span>Add Item</span>
            </a>
        </div>
        
        <!-- Library Management -->
        <div class="nav-section">
            <div class="nav-section-title">Library Management</div>
            <a href="library_management.php" class="<?php echo $current_page == 'library_management.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-book"></i>
                <span>Books</span>
            </a>
            <a href="library_management.php?tab=rooms" class="<?php echo $current_page == 'library_management.php' && isset($_GET['tab']) && $_GET['tab'] == 'rooms' ? 'active' : ''; ?>">
                <i class="fa-solid fa-door-open"></i>
                <span>Study Rooms</span>
            </a>
            <a href="library_management.php?tab=borrowed" class="<?php echo $current_page == 'library_management.php' && isset($_GET['tab']) && $_GET['tab'] == 'borrowed' ? 'active' : ''; ?>">
                <i class="fa-solid fa-hand-holding"></i>
                <span>Borrowed Books</span>
            </a>
        </div>
        
        <!-- Pool Management -->
        <div class="nav-section">
            <div class="nav-section-title">Pool Management</div>
            <a href="pool_management.php" class="<?php echo $current_page == 'pool_management.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-person-swimming"></i>
                <span>Pool Dashboard</span>
            </a>
            <a href="pool_management.php?tab=medical" class="<?php echo $current_page == 'pool_management.php' && isset($_GET['tab']) && $_GET['tab'] == 'medical' ? 'active' : ''; ?>">
                <i class="fa-solid fa-notes-medical"></i>
                <span>Medical Reports</span>
            </a>
            <a href="pool_management.php?tab=schedule" class="<?php echo $current_page == 'pool_management.php' && isset($_GET['tab']) && $_GET['tab'] == 'schedule' ? 'active' : ''; ?>">
                <i class="fa-regular fa-calendar"></i>
                <span>Lifeguard Schedule</span>
            </a>
            <a href="pool_management.php?tab=bookings" class="<?php echo $current_page == 'pool_management.php' && isset($_GET['tab']) && $_GET['tab'] == 'bookings' ? 'active' : ''; ?>">
                <i class="fa-solid fa-clock"></i>
                <span>Lane Bookings</span>
            </a>
        </div>
        
        <!-- Gym Management -->
        <div class="nav-section">
            <div class="nav-section-title">Gym Management</div>
            <a href="gym_equipment_admin.php" class="<?php echo $current_page == 'gym_equipment_admin.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-dumbbell"></i>
                <span>Equipment</span>
            </a>
            <a href="fitness_classes.php" class="<?php echo $current_page == 'fitness_classes.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-people-group"></i>
                <span>Fitness Classes</span>
            </a>
        </div>
        
        <!-- Transport Management -->
        <div class="nav-section">
            <div class="nav-section-title">Transport</div>
            <a href="transport_admin.php" class="<?php echo $current_page == 'transport_admin.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-bus"></i>
                <span>Manage Transport</span>
            </a>
        </div>
        
        <!-- Game Field Management -->
        <div class="nav-section">
            <div class="nav-section-title">Game Field</div>
            <a href="game_field_admin.php" class="<?php echo $current_page == 'game_field_admin.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-futbol"></i>
                <span>Field Bookings</span>
            </a>
        </div>
        
        <!-- Orders -->
        <div class="nav-section">
            <div class="nav-section-title">Orders</div>
            <a href="orders_admin.php" class="<?php echo $current_page == 'orders_admin.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-cart-shopping"></i>
                <span>Manage Orders</span>
            </a>
        </div>
        
        <!-- Notifications -->
        <div class="nav-section">
            <div class="nav-section-title">Communications</div>
            <a href="notifications.php" class="<?php echo $current_page == 'notifications.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-bell"></i>
                <span>Notifications</span>
            </a>
        </div>
        
        <!-- System -->
        <div class="nav-section">
            <div class="nav-section-title">System</div>
            <a href="settings.php" class="<?php echo $current_page == 'settings.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-gear"></i>
                <span>Settings</span>
            </a>
        </div>
    </nav>
    
    <div class="sidebar-footer">
        <a href="../index.php" target="_blank">
            <i class="fa-solid fa-eye"></i>
            <span>View Site</span>
        </a>
        <a href="logout.php">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span>Logout</span>
        </a>
    </div>
</div>