<?php
require_once 'config.php';
require_once 'functions.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$facility_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get facility details
$facility_sql = "SELECT * FROM Facilities WHERE FacilityID = ?";
$facility_stmt = mysqli_prepare($conn, $facility_sql);
mysqli_stmt_bind_param($facility_stmt, "i", $facility_id);
mysqli_stmt_execute($facility_stmt);
$facility_result = mysqli_stmt_get_result($facility_stmt);
$facility = mysqli_fetch_assoc($facility_result);

// Get existing medical reports
$medical_sql = "SELECT * FROM medical_reports 
                WHERE user_id = ? 
                ORDER BY upload_date DESC";
$medical_stmt = mysqli_prepare($conn, $medical_sql);
mysqli_stmt_bind_param($medical_stmt, "i", $user_id);
mysqli_stmt_execute($medical_stmt);
$medical_result = mysqli_stmt_get_result($medical_stmt);

// Get latest valid report
$valid_sql = "SELECT * FROM medical_reports 
              WHERE user_id = ? AND is_valid = TRUE 
              AND (expiry_date IS NULL OR expiry_date >= CURDATE())
              ORDER BY upload_date DESC LIMIT 1";
$valid_stmt = mysqli_prepare($conn, $valid_sql);
mysqli_stmt_bind_param($valid_stmt, "i", $user_id);
mysqli_stmt_execute($valid_stmt);
$valid_result = mysqli_stmt_get_result($valid_stmt);
$has_valid = mysqli_num_rows($valid_result) > 0;
$valid_report = mysqli_fetch_assoc($valid_result);

// Handle form submission
$message = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $emergency_name = $_POST['emergency_name'];
    $emergency_phone = $_POST['emergency_phone'];
    $medical_conditions = $_POST['medical_conditions'];
    $expiry_date = $_POST['expiry_date'];
    
    // Handle file upload
    if (isset($_FILES['medical_file']) && $_FILES['medical_file']['error'] == 0) {
        $allowed = ['pdf', 'jpg', 'jpeg', 'png'];
        $filename = $_FILES['medical_file']['name'];
        $filesize = $_FILES['medical_file']['size'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (!in_array($ext, $allowed)) {
            $message = '<div class="error-msg">❌ Only PDF, JPG, PNG files are allowed</div>';
        } elseif ($filesize > 5 * 1024 * 1024) {
            $message = '<div class="error-msg">❌ File size must be less than 5MB</div>';
        } else {
            // Create upload directory if not exists
            $upload_dir = 'uploads/medical/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Generate unique filename
            $new_filename = 'medical_' . $user_id . '_' . time() . '.' . $ext;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['medical_file']['tmp_name'], $upload_path)) {
                // Insert into database
                $insert_sql = "INSERT INTO medical_reports (user_id, file_name, file_path, file_size, expiry_date, emergency_contact_name, emergency_contact_phone, medical_conditions) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $insert_stmt = mysqli_prepare($conn, $insert_sql);
                mysqli_stmt_bind_param($insert_stmt, "ississss", 
                    $user_id, 
                    $filename, 
                    $upload_path, 
                    $filesize, 
                    $expiry_date, 
                    $emergency_name, 
                    $emergency_phone, 
                    $medical_conditions
                );
                
                if (mysqli_stmt_execute($insert_stmt)) {
                    $message = '<div class="success-msg">✅ Medical report uploaded successfully!</div>';
                    
                    // Log activity
                    logActivity($conn, $user_id, 'MEDICAL_UPLOAD', 'medical_reports', mysqli_insert_id($conn));
                    
                    // Refresh the page
                    header("Refresh:2");
                } else {
                    $message = '<div class="error-msg">❌ Database error: ' . mysqli_error($conn) . '</div>';
                }
            } else {
                $message = '<div class="error-msg">❌ Failed to upload file</div>';
            }
        }
    } else {
        $message = '<div class="error-msg">❌ Please select a file to upload</div>';
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $report_id = intval($_GET['delete']);
    
    // Get file path
    $file_sql = "SELECT file_path FROM medical_reports WHERE report_id = ? AND user_id = ?";
    $file_stmt = mysqli_prepare($conn, $file_sql);
    mysqli_stmt_bind_param($file_stmt, "ii", $report_id, $user_id);
    mysqli_stmt_execute($file_stmt);
    $file_result = mysqli_stmt_get_result($file_stmt);
    $file = mysqli_fetch_assoc($file_result);
    
    if ($file) {
        // Delete file
        if (file_exists($file['file_path'])) {
            unlink($file['file_path']);
        }
        
        // Delete from database
        $delete_sql = "DELETE FROM medical_reports WHERE report_id = ? AND user_id = ?";
        $delete_stmt = mysqli_prepare($conn, $delete_sql);
        mysqli_stmt_bind_param($delete_stmt, "ii", $report_id, $user_id);
        
        if (mysqli_stmt_execute($delete_stmt)) {
            $message = '<div class="success-msg">✅ Report deleted successfully</div>';
            header("Refresh:2");
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Report - Synergy Hub</title>
    <link rel="stylesheet" href="style.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
        }
        
        body {
            min-height: 100vh;
            position: relative;
            background: linear-gradient(135deg, #0284c7 0%, #0ea5e9 100%);
        }
        
        .bg {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: -1;
        }
        
        .bg::before {
            content: "";
            position: absolute;
            inset: 0;
            background-image: url("campus.jpg");
            background-size: cover;
            background-position: center;
            filter: blur(4px) brightness(0.65);
            transform: scale(1.05);
            pointer-events: none;
        }
        
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 32px;
            background: rgba(0,0,0,0.2);
            backdrop-filter: blur(10px);
        }
        
        .logo {
            font-size: 24px;
            font-weight: 700;
            color: white;
        }
        
        .logo span {
            color: #22d3ee;
        }
        
        .icons {
            display: flex;
            gap: 20px;
            align-items: center;
        }
        
        .menu-btn {
            color: white;
            font-size: 24px;
            cursor: pointer;
        }
        
        .points {
            display: flex;
            align-items: center;
            gap: 6px;
            font-weight: 600;
            padding: 8px 15px;
            border-radius: 20px;
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(10px);
            color: white;
        }
        
        .home-link {
            color: white;
            font-size: 20px;
            text-decoration: none;
        }
        
        .sidebar {
            position: fixed;
            left: -260px;
            top: 0;
            width: 260px;
            height: 100%;
            background: #0f172a;
            padding-top: 70px;
            transition: .35s;
            z-index: 9999;
        }
        
        .sidebar a {
            display: block;
            padding: 15px 20px;
            color: white;
            text-decoration: none;
            opacity: .8;
            transition: all 0.3s;
        }
        
        .sidebar a:hover {
            opacity: 1;
            background: #1e293b;
            padding-left: 30px;
        }
        
        .container {
            padding: 30px;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .page-title {
            color: white;
            font-size: 32px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .facility-name {
            color: #22d3ee;
            font-size: 18px;
            margin-bottom: 30px;
        }
        
        /* Status Card */
        .status-card {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            border: 1px solid rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .status-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
        }
        
        .status-icon.valid {
            background: rgba(16, 185, 129, 0.2);
            color: #10b981;
        }
        
        .status-icon.invalid {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
        }
        
        .status-text {
            flex: 1;
            color: white;
        }
        
        .status-text h3 {
            margin-bottom: 5px;
        }
        
        .status-text p {
            color: rgba(255,255,255,0.7);
        }
        
        /* Upload Form */
        .upload-form {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .form-title {
            color: white;
            font-size: 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-title i {
            color: #22d3ee;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            color: rgba(255,255,255,0.8);
            margin-bottom: 8px;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border-radius: 10px;
            border: 1px solid rgba(255,255,255,0.2);
            background: rgba(255,255,255,0.05);
            color: white;
            font-size: 14px;
            outline: none;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #22d3ee;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .file-upload {
            border: 2px dashed rgba(255,255,255,0.2);
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            margin-bottom: 20px;
        }
        
        .file-upload:hover {
            border-color: #22d3ee;
            background: rgba(34, 211, 238, 0.05);
        }
        
        .file-upload i {
            font-size: 40px;
            color: #22d3ee;
            margin-bottom: 10px;
        }
        
        .file-upload p {
            color: rgba(255,255,255,0.7);
        }
        
        .file-info {
            color: rgba(255,255,255,0.5);
            font-size: 12px;
            margin-top: 10px;
        }
        
        .submit-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #0284c7, #0ea5e9);
            border: none;
            border-radius: 10px;
            color: white;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(2, 132, 199, 0.4);
        }
        
        /* Previous Reports */
        .reports-list {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .report-item {
            background: rgba(255,255,255,0.05);
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .report-icon {
            width: 40px;
            height: 40px;
            background: rgba(34, 211, 238, 0.1);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #22d3ee;
        }
        
        .report-details {
            flex: 1;
        }
        
        .report-name {
            color: white;
            font-weight: 600;
            margin-bottom: 3px;
        }
        
        .report-meta {
            color: rgba(255,255,255,0.5);
            font-size: 12px;
        }
        
        .report-status {
            padding: 4px 12px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-valid {
            background: #10b981;
            color: white;
        }
        
        .status-expired {
            background: #ef4444;
            color: white;
        }
        
        .delete-btn {
            background: #ef4444;
            color: white;
            border: none;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            cursor: pointer;
            margin-left: 10px;
        }
        
        .no-reports {
            text-align: center;
            color: rgba(255,255,255,0.5);
            padding: 30px 0;
        }
        
        .success-msg {
            background: rgba(16, 185, 129, 0.2);
            border: 1px solid #10b981;
            color: #10b981;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .error-msg {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid #ef4444;
            color: #ef4444;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .back-btn {
            display: inline-block;
            margin-top: 30px;
            color: white;
            text-decoration: none;
            font-size: 16px;
            padding: 10px 20px;
            background: rgba(255,255,255,0.1);
            border-radius: 30px;
            transition: all 0.3s;
        }
        
        .back-btn:hover {
            background: rgba(255,255,255,0.2);
            color: #22d3ee;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .status-card {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
</head>
<body>

<div class="bg"></div>

<!-- SIDEBAR -->
<div id="sidebar" class="sidebar">
    <a href="index.php">Home</a>
    <a href="facilities.php">Facilities</a>
    <a href="transport.php">Transport</a>
    <a href="game.php">Game Field</a>
    <a href="clubs.php">Club Hub</a>
    <a href="qr.html">QR Scanner</a>
</div>

<!-- NAVBAR -->
<header class="navbar">
    <div class="menu-btn" onclick="toggleSidebar()">
        <i class="fa-solid fa-bars"></i>
    </div>
    
    <h1 class="logo">Synergy <span>Hub</span> - Medical Report</h1>
    
    <div class="icons">
        <div class="points">
            <i class="fa-solid fa-star"></i>
            <span><?php
                $points_sql = "SELECT PointsBalance FROM Users WHERE UserID = ?";
                $points_stmt = mysqli_prepare($conn, $points_sql);
                mysqli_stmt_bind_param($points_stmt, "i", $user_id);
                mysqli_stmt_execute($points_stmt);
                $points_result = mysqli_stmt_get_result($points_stmt);
                $user_points = mysqli_fetch_assoc($points_result);
                echo $user_points['PointsBalance'];
            ?></span>
        </div>
        <a href="pool.php?id=<?php echo $facility_id; ?>" class="home-link">
            <i class="fa-solid fa-arrow-left"></i> Back to Pool
        </a>
    </div>
</header>

<!-- MAIN CONTENT -->
<div class="container">
    
    <h1 class="page-title">
        <i class="fa-solid fa-notes-medical"></i> Medical Report
    </h1>
    <div class="facility-name">for <?php echo htmlspecialchars($facility['Name']); ?></div>
    
    <?php echo $message; ?>
    
    <!-- Current Status -->
    <div class="status-card">
        <div class="status-icon <?php echo $has_valid ? 'valid' : 'invalid'; ?>">
            <i class="fa-solid <?php echo $has_valid ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?>"></i>
        </div>
        <div class="status-text">
            <h3><?php echo $has_valid ? 'Medical Report Verified' : 'No Valid Medical Report'; ?></h3>
            <?php if($has_valid): ?>
                <p>Valid until <?php echo date('F d, Y', strtotime($valid_report['expiry_date'] ?? '+1 year')); ?></p>
                <p>Emergency Contact: <?php echo htmlspecialchars($valid_report['emergency_contact_name']); ?> (<?php echo htmlspecialchars($valid_report['emergency_contact_phone']); ?>)</p>
            <?php else: ?>
                <p>Please upload a valid medical report to book pool lanes</p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Upload Form -->
    <div class="upload-form">
        <h3 class="form-title">
            <i class="fa-solid fa-cloud-upload-alt"></i> Upload New Medical Report
        </h3>
        
        <form method="POST" action="" enctype="multipart/form-data">
            <div class="file-upload" onclick="document.getElementById('medicalFile').click()">
                <i class="fa-solid fa-cloud-upload-alt"></i>
                <p>Click to upload or drag and drop</p>
                <p class="file-info">PDF, JPG, PNG (Max 5MB)</p>
                <input type="file" id="medicalFile" name="medical_file" style="display: none;" accept=".pdf,.jpg,.jpeg,.png" required>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Emergency Contact Name</label>
                    <input type="text" name="emergency_name" required>
                </div>
                <div class="form-group">
                    <label>Emergency Contact Phone</label>
                    <input type="tel" name="emergency_phone" required placeholder="07X XXX XXXX">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Expiry Date</label>
                    <input type="date" name="expiry_date" value="<?php echo date('Y-m-d', strtotime('+1 year')); ?>" required>
                </div>
                <div class="form-group">
                    <label>&nbsp;</label>
                    <div></div>
                </div>
            </div>
            
            <div class="form-group">
                <label>Medical Conditions (if any)</label>
                <textarea name="medical_conditions" rows="3" placeholder="List any medical conditions, allergies, or medications..."></textarea>
            </div>
            
            <button type="submit" class="submit-btn">
                <i class="fa-solid fa-upload"></i> Upload Report
            </button>
        </form>
    </div>
    
    <!-- Previous Reports -->
    <div class="reports-list">
        <h3 class="form-title">
            <i class="fa-solid fa-history"></i> Upload History
        </h3>
        
        <?php if(mysqli_num_rows($medical_result) > 0): ?>
            <?php while($report = mysqli_fetch_assoc($medical_result)): 
                $is_valid = $report['is_valid'] && (strtotime($report['expiry_date'] ?? '+1 year') >= time());
            ?>
            <div class="report-item">
                <div class="report-icon">
                    <i class="fa-solid fa-file-medical"></i>
                </div>
                <div class="report-details">
                    <div class="report-name"><?php echo htmlspecialchars($report['file_name']); ?></div>
                    <div class="report-meta">
                        Uploaded on <?php echo date('M d, Y', strtotime($report['upload_date'])); ?> • 
                        <?php echo round($report['file_size'] / 1024); ?> KB
                    </div>
                </div>
                <div>
                    <span class="report-status <?php echo $is_valid ? 'status-valid' : 'status-expired'; ?>">
                        <?php echo $is_valid ? 'Valid' : 'Expired'; ?>
                    </span>
                    <a href="?id=<?php echo $facility_id; ?>&delete=<?php echo $report['report_id']; ?>" 
                       class="delete-btn" 
                       onclick="return confirm('Delete this report?')">
                        Delete
                    </a>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="no-reports">
                <i class="fa-regular fa-file" style="font-size: 40px; margin-bottom: 10px;"></i>
                <p>No medical reports uploaded yet</p>
            </div>
        <?php endif; ?>
    </div>
    
    <a href="pool.php?id=<?php echo $facility_id; ?>" class="back-btn">
        <i class="fa-solid fa-arrow-left"></i> Back to Pool
    </a>
</div>

<script>
function toggleSidebar() {
    const sidebar = document.querySelector(".sidebar");
    sidebar.style.left = sidebar.style.left === "0px" ? "-260px" : "0px";
}

document.addEventListener("click", function(e) {
    const sidebar = document.querySelector(".sidebar");
    const btn = document.querySelector(".menu-btn");
    if(sidebar && btn && !sidebar.contains(e.target) && !btn.contains(e.target)) {
        sidebar.style.left = "-260px";
    }
});

// Show filename when selected
document.getElementById('medicalFile').addEventListener('change', function(e) {
    let fileName = e.target.files[0]?.name;
    if (fileName) {
        document.querySelector('.file-upload p').innerHTML = 'Selected: ' + fileName;
    }
});
</script>

</body>
</html>