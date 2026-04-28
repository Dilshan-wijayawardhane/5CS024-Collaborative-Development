<?php
require_once 'config.php';
checkAdminAuth();

$admin = getAdminInfo($conn);
$message = '';
$error = '';
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'general';

// Handle general settings update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_general'])) {
    $site_name = mysqli_real_escape_string($conn, $_POST['site_name']);
    $site_description = mysqli_real_escape_string($conn, $_POST['site_description']);
    $contact_email = mysqli_real_escape_string($conn, $_POST['contact_email']);
    $contact_phone = mysqli_real_escape_string($conn, $_POST['contact_phone']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    
    // Store in a settings table or create one
    $check_table = mysqli_query($conn, "SHOW TABLES LIKE 'system_settings'");
    if (mysqli_num_rows($check_table) == 0) {
        mysqli_query($conn, "CREATE TABLE IF NOT EXISTS system_settings (
            setting_id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) UNIQUE NOT NULL,
            setting_value TEXT,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
    
    $settings = [
        'site_name' => $site_name,
        'site_description' => $site_description,
        'contact_email' => $contact_email,
        'contact_phone' => $contact_phone,
        'address' => $address
    ];
    
    foreach ($settings as $key => $value) {
        $insert_sql = "INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?) 
                       ON DUPLICATE KEY UPDATE setting_value = ?";
        $stmt = mysqli_prepare($conn, $insert_sql);
        mysqli_stmt_bind_param($stmt, "sss", $key, $value, $value);
        mysqli_stmt_execute($stmt);
    }
    
    $message = "General settings updated successfully!";
    logAdminActivity($conn, 'UPDATE_SETTINGS', 'Updated general settings');
}

// Handle security settings
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_security'])) {
    $session_timeout = intval($_POST['session_timeout']);
    $max_login_attempts = intval($_POST['max_login_attempts']);
    $enable_2fa = isset($_POST['enable_2fa']) ? 1 : 0;
    $password_expiry_days = intval($_POST['password_expiry_days']);
    
    $settings = [
        'session_timeout' => $session_timeout,
        'max_login_attempts' => $max_login_attempts,
        'enable_2fa' => $enable_2fa,
        'password_expiry_days' => $password_expiry_days
    ];
    
    foreach ($settings as $key => $value) {
        $insert_sql = "INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?) 
                       ON DUPLICATE KEY UPDATE setting_value = ?";
        $stmt = mysqli_prepare($conn, $insert_sql);
        mysqli_stmt_bind_param($stmt, "sss", $key, $value, $value);
        mysqli_stmt_execute($stmt);
    }
    
    $message = "Security settings updated successfully!";
    logAdminActivity($conn, 'UPDATE_SETTINGS', 'Updated security settings');
}

// Handle email settings
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_email'])) {
    $smtp_host = mysqli_real_escape_string($conn, $_POST['smtp_host']);
    $smtp_port = intval($_POST['smtp_port']);
    $smtp_user = mysqli_real_escape_string($conn, $_POST['smtp_user']);
    $smtp_pass = mysqli_real_escape_string($conn, $_POST['smtp_pass']);
    $smtp_encryption = mysqli_real_escape_string($conn, $_POST['smtp_encryption']);
    
    $settings = [
        'smtp_host' => $smtp_host,
        'smtp_port' => $smtp_port,
        'smtp_user' => $smtp_user,
        'smtp_pass' => $smtp_pass,
        'smtp_encryption' => $smtp_encryption
    ];
    
    foreach ($settings as $key => $value) {
        $insert_sql = "INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?) 
                       ON DUPLICATE KEY UPDATE setting_value = ?";
        $stmt = mysqli_prepare($conn, $insert_sql);
        mysqli_stmt_bind_param($stmt, "sss", $key, $value, $value);
        mysqli_stmt_execute($stmt);
    }
    
    $message = "Email settings updated successfully!";
    logAdminActivity($conn, 'UPDATE_SETTINGS', 'Updated email settings');
}

// Handle appearance settings
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_appearance'])) {
    $theme = mysqli_real_escape_string($conn, $_POST['theme']);
    $primary_color = mysqli_real_escape_string($conn, $_POST['primary_color']);
    $sidebar_color = mysqli_real_escape_string($conn, $_POST['sidebar_color']);
    $logo_path = mysqli_real_escape_string($conn, $_POST['logo_path']);
    
    $settings = [
        'theme' => $theme,
        'primary_color' => $primary_color,
        'sidebar_color' => $sidebar_color,
        'logo_path' => $logo_path
    ];
    
    foreach ($settings as $key => $value) {
        $insert_sql = "INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?) 
                       ON DUPLICATE KEY UPDATE setting_value = ?";
        $stmt = mysqli_prepare($conn, $insert_sql);
        mysqli_stmt_bind_param($stmt, "sss", $key, $value, $value);
        mysqli_stmt_execute($stmt);
    }
    
    $message = "Appearance settings updated successfully!";
    logAdminActivity($conn, 'UPDATE_SETTINGS', 'Updated appearance settings');
}

// Handle backup
if (isset($_POST['create_backup'])) {
    $backup_file = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
    $backup_path = '../backups/' . $backup_file;
    
    if (!is_dir('../backups')) {
        mkdir('../backups', 0777, true);
    }
    
    // Get all tables
    $tables = [];
    $result = mysqli_query($conn, "SHOW TABLES");
    while ($row = mysqli_fetch_array($result)) {
        $tables[] = $row[0];
    }
    
    $backup_content = "-- Synergy Hub Database Backup\n";
    $backup_content .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
    
    foreach ($tables as $table) {
        $result = mysqli_query($conn, "SELECT * FROM $table");
        $num_fields = mysqli_num_fields($result);
        
        $backup_content .= "DROP TABLE IF EXISTS `$table`;\n";
        
        $row2 = mysqli_fetch_row(mysqli_query($conn, "SHOW CREATE TABLE $table"));
        $backup_content .= $row2[1] . ";\n\n";
        
        while ($row = mysqli_fetch_row($result)) {
            $backup_content .= "INSERT INTO `$table` VALUES(";
            for ($j = 0; $j < $num_fields; $j++) {
                $row[$j] = addslashes($row[$j]);
                $backup_content .= '"' . $row[$j] . '"';
                if ($j < ($num_fields - 1)) {
                    $backup_content .= ',';
                }
            }
            $backup_content .= ");\n";
        }
        $backup_content .= "\n\n";
    }
    
    file_put_contents($backup_path, $backup_content);
    $message = "Database backup created successfully! File: " . $backup_file;
    logAdminActivity($conn, 'CREATE_BACKUP', 'Created database backup');
}

// Handle clear cache
if (isset($_POST['clear_cache'])) {
    // Clear session cache
    $cache_dirs = ['../cache', '../temp', '../uploads/temp'];
    foreach ($cache_dirs as $dir) {
        if (is_dir($dir)) {
            $files = glob($dir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
    }
    $message = "Cache cleared successfully!";
    logAdminActivity($conn, 'CLEAR_CACHE', 'Cleared system cache');
}

// Get current settings
$settings = [];
$settings_sql = "SELECT setting_key, setting_value FROM system_settings";
$settings_result = mysqli_query($conn, $settings_sql);
while ($row = mysqli_fetch_assoc($settings_result)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Default values if not set
$defaults = [
    'site_name' => 'Synergy Hub',
    'site_description' => 'Campus Collaboration Platform',
    'contact_email' => 'admin@synergyhub.com',
    'contact_phone' => '+94 11 234 5678',
    'address' => 'CINEC Campus, Malabe, Sri Lanka',
    'session_timeout' => 30,
    'max_login_attempts' => 5,
    'enable_2fa' => 0,
    'password_expiry_days' => 90,
    'theme' => 'dark',
    'primary_color' => '#667eea',
    'sidebar_color' => '#1e293b'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - Synergy Hub Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="admin.css">
    <style>
        .settings-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 10px;
            flex-wrap: wrap;
        }
        
        .settings-tab {
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
        
        .settings-tab:hover {
            background: #f1f5f9;
            color: #1e293b;
        }
        
        .settings-tab.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .settings-card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .settings-card h3 {
            color: #1e293b;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .settings-card h3 i {
            color: #667eea;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            color: #475569;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 8px;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: #f1f5f9;
            color: #475569;
        }
        
        .btn-secondary:hover {
            background: #e2e8f0;
        }
        
        .btn-danger {
            background: #ef4444;
            color: white;
        }
        
        .btn-danger:hover {
            background: #dc2626;
        }
        
        .btn-success {
            background: #10b981;
            color: white;
        }
        
        .btn-success:hover {
            background: #059669;
        }
        
        .backup-section {
            background: #f8fafc;
            border-radius: 12px;
            padding: 20px;
            margin-top: 20px;
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
        
        .color-preview {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            margin-left: 10px;
            border: 2px solid #e2e8f0;
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
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .settings-tabs {
                flex-direction: column;
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
                    <i class="fa-solid fa-gear"></i> System Settings
                </h1>
                
                <?php if($message): ?>
                    <div class="alert alert-success"><?php echo $message; ?></div>
                <?php endif; ?>
                <?php if($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <!-- Tabs -->
                <div class="settings-tabs">
                    <a href="?tab=general" class="settings-tab <?php echo $active_tab == 'general' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-globe"></i> General
                    </a>
                    <a href="?tab=security" class="settings-tab <?php echo $active_tab == 'security' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-shield-halved"></i> Security
                    </a>
                    <a href="?tab=email" class="settings-tab <?php echo $active_tab == 'email' ? 'active' : ''; ?>">
                        <i class="fa-regular fa-envelope"></i> Email
                    </a>
                    <a href="?tab=appearance" class="settings-tab <?php echo $active_tab == 'appearance' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-palette"></i> Appearance
                    </a>
                    <a href="?tab=maintenance" class="settings-tab <?php echo $active_tab == 'maintenance' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-database"></i> Maintenance
                    </a>
                </div>
                
                <!-- Tab: General Settings -->
                <div id="tab-general" class="tab-content <?php echo $active_tab == 'general' ? 'active' : ''; ?>">
                    <div class="settings-card">
                        <h3><i class="fa-solid fa-globe"></i> General Settings</h3>
                        <form method="POST">
                            <div class="form-group">
                                <label>Site Name</label>
                                <input type="text" name="site_name" value="<?php echo htmlspecialchars($settings['site_name'] ?? $defaults['site_name']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Site Description</label>
                                <textarea name="site_description" rows="3"><?php echo htmlspecialchars($settings['site_description'] ?? $defaults['site_description']); ?></textarea>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Contact Email</label>
                                    <input type="email" name="contact_email" value="<?php echo htmlspecialchars($settings['contact_email'] ?? $defaults['contact_email']); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Contact Phone</label>
                                    <input type="text" name="contact_phone" value="<?php echo htmlspecialchars($settings['contact_phone'] ?? $defaults['contact_phone']); ?>">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Address</label>
                                <textarea name="address" rows="2"><?php echo htmlspecialchars($settings['address'] ?? $defaults['address']); ?></textarea>
                            </div>
                            
                            <button type="submit" name="update_general" class="btn btn-primary">
                                <i class="fa-solid fa-save"></i> Save Changes
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Tab: Security Settings -->
                <div id="tab-security" class="tab-content <?php echo $active_tab == 'security' ? 'active' : ''; ?>">
                    <div class="settings-card">
                        <h3><i class="fa-solid fa-shield-halved"></i> Security Settings</h3>
                        <form method="POST">
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Session Timeout (minutes)</label>
                                    <input type="number" name="session_timeout" value="<?php echo $settings['session_timeout'] ?? $defaults['session_timeout']; ?>" min="5" max="480">
                                    <small>User session will expire after this many minutes of inactivity</small>
                                </div>
                                <div class="form-group">
                                    <label>Max Login Attempts</label>
                                    <input type="number" name="max_login_attempts" value="<?php echo $settings['max_login_attempts'] ?? $defaults['max_login_attempts']; ?>" min="3" max="10">
                                    <small>Number of failed login attempts before temporary lockout</small>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Password Expiry (days)</label>
                                    <input type="number" name="password_expiry_days" value="<?php echo $settings['password_expiry_days'] ?? $defaults['password_expiry_days']; ?>" min="30" max="365">
                                    <small>Users must change password after this many days</small>
                                </div>
                                <div class="form-group">
                                    <label>Two-Factor Authentication</label>
                                    <label class="toggle-switch">
                                        <input type="checkbox" name="enable_2fa" <?php echo ($settings['enable_2fa'] ?? $defaults['enable_2fa']) ? 'checked' : ''; ?>>
                                        <span class="toggle-slider"></span>
                                    </label>
                                    <small>Require 2FA for all admin accounts</small>
                                </div>
                            </div>
                            
                            <button type="submit" name="update_security" class="btn btn-primary">
                                <i class="fa-solid fa-save"></i> Save Security Settings
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Tab: Email Settings -->
                <div id="tab-email" class="tab-content <?php echo $active_tab == 'email' ? 'active' : ''; ?>">
                    <div class="settings-card">
                        <h3><i class="fa-regular fa-envelope"></i> Email Settings</h3>
                        <form method="POST">
                            <div class="form-row">
                                <div class="form-group">
                                    <label>SMTP Host</label>
                                    <input type="text" name="smtp_host" value="<?php echo htmlspecialchars($settings['smtp_host'] ?? 'smtp.gmail.com'); ?>" placeholder="smtp.gmail.com">
                                </div>
                                <div class="form-group">
                                    <label>SMTP Port</label>
                                    <input type="number" name="smtp_port" value="<?php echo $settings['smtp_port'] ?? 587; ?>" placeholder="587">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>SMTP Username</label>
                                    <input type="email" name="smtp_user" value="<?php echo htmlspecialchars($settings['smtp_user'] ?? ''); ?>" placeholder="your-email@gmail.com">
                                </div>
                                <div class="form-group">
                                    <label>SMTP Password</label>
                                    <input type="password" name="smtp_pass" value="<?php echo htmlspecialchars($settings['smtp_pass'] ?? ''); ?>" placeholder="••••••••">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Encryption</label>
                                <select name="smtp_encryption">
                                    <option value="tls" <?php echo ($settings['smtp_encryption'] ?? 'tls') == 'tls' ? 'selected' : ''; ?>>TLS</option>
                                    <option value="ssl" <?php echo ($settings['smtp_encryption'] ?? '') == 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                    <option value="none" <?php echo ($settings['smtp_encryption'] ?? '') == 'none' ? 'selected' : ''; ?>>None</option>
                                </select>
                            </div>
                            
                            <button type="submit" name="update_email" class="btn btn-primary">
                                <i class="fa-solid fa-save"></i> Save Email Settings
                            </button>
                        </form>
                    </div>
                    
                    <div class="settings-card">
                        <h3><i class="fa-regular fa-paper-plane"></i> Test Email</h3>
                        <div class="form-group">
                            <label>Send Test Email To</label>
                            <input type="email" id="test_email" placeholder="admin@example.com">
                        </div>
                        <button class="btn btn-secondary" onclick="sendTestEmail()">
                            <i class="fa-regular fa-paper-plane"></i> Send Test Email
                        </button>
                    </div>
                </div>
                
                <!-- Tab: Appearance -->
                <div id="tab-appearance" class="tab-content <?php echo $active_tab == 'appearance' ? 'active' : ''; ?>">
                    <div class="settings-card">
                        <h3><i class="fa-solid fa-palette"></i> Appearance Settings</h3>
                        <form method="POST">
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Theme</label>
                                    <select name="theme">
                                        <option value="dark" <?php echo ($settings['theme'] ?? 'dark') == 'dark' ? 'selected' : ''; ?>>Dark</option>
                                        <option value="light" <?php echo ($settings['theme'] ?? '') == 'light' ? 'selected' : ''; ?>>Light</option>
                                        <option value="auto" <?php echo ($settings['theme'] ?? '') == 'auto' ? 'selected' : ''; ?>>Auto (System)</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Primary Color</label>
                                    <div style="display: flex; align-items: center;">
                                        <input type="color" name="primary_color" id="primary_color" value="<?php echo $settings['primary_color'] ?? $defaults['primary_color']; ?>">
                                        <div class="color-preview" style="background-color: <?php echo $settings['primary_color'] ?? $defaults['primary_color']; ?>;"></div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Sidebar Color</label>
                                    <div style="display: flex; align-items: center;">
                                        <input type="color" name="sidebar_color" id="sidebar_color" value="<?php echo $settings['sidebar_color'] ?? $defaults['sidebar_color']; ?>">
                                        <div class="color-preview" style="background-color: <?php echo $settings['sidebar_color'] ?? $defaults['sidebar_color']; ?>;"></div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Logo URL</label>
                                    <input type="text" name="logo_path" value="<?php echo htmlspecialchars($settings['logo_path'] ?? ''); ?>" placeholder="/images/logo.png">
                                </div>
                            </div>
                            
                            <button type="submit" name="update_appearance" class="btn btn-primary">
                                <i class="fa-solid fa-save"></i> Save Appearance
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Tab: Maintenance -->
                <div id="tab-maintenance" class="tab-content <?php echo $active_tab == 'maintenance' ? 'active' : ''; ?>">
                    <div class="settings-card">
                        <h3><i class="fa-solid fa-database"></i> Database Backup</h3>
                        <p>Create a full backup of your database including all tables, users, orders, and settings.</p>
                        <form method="POST" style="margin-top: 20px;">
                            <button type="submit" name="create_backup" class="btn btn-success">
                                <i class="fa-solid fa-download"></i> Create Database Backup
                            </button>
                        </form>
                    </div>
                    
                    <div class="settings-card">
                        <h3><i class="fa-solid fa-trash-can"></i> Clear Cache</h3>
                        <p>Clear temporary files, cached data, and session files to free up space.</p>
                        <form method="POST" style="margin-top: 20px;" onsubmit="return confirm('Clear all system cache? This may log out all users.');">
                            <button type="submit" name="clear_cache" class="btn btn-warning">
                                <i class="fa-solid fa-broom"></i> Clear Cache
                            </button>
                        </form>
                    </div>
                    
                    <div class="settings-card">
                        <h3><i class="fa-solid fa-chart-simple"></i> System Information</h3>
                        <div class="backup-section">
                            <p><strong>PHP Version:</strong> <?php echo phpversion(); ?></p>
                            <p><strong>MySQL Version:</strong> <?php echo mysqli_get_server_info($conn); ?></p>
                            <p><strong>Server Software:</strong> <?php echo $_SERVER['SERVER_SOFTWARE']; ?></p>
                            <p><strong>Upload Max Size:</strong> <?php echo ini_get('upload_max_filesize'); ?></p>
                            <p><strong>Memory Limit:</strong> <?php echo ini_get('memory_limit'); ?></p>
                            <p><strong>Max Execution Time:</strong> <?php echo ini_get('max_execution_time'); ?> seconds</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Color preview
        document.getElementById('primary_color')?.addEventListener('input', function(e) {
            document.querySelector('.color-preview').style.backgroundColor = e.target.value;
        });
        
        // Test email function
        function sendTestEmail() {
            const email = document.getElementById('test_email').value;
            if (!email) {
                alert('Please enter an email address');
                return;
            }
            
            fetch('test_email.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'email=' + encodeURIComponent(email)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Test email sent successfully to ' + email);
                } else {
                    alert('Error sending test email: ' + data.message);
                }
            })
            .catch(error => {
                alert('Error: ' + error);
            });
        }
    </script>
</body>
</html>