<?php
require_once 'middleware.php';
require_once 'config.php';
checkAdminAuth();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $expiry_days = intval($_POST['expiry_days']);
    $last_earned_expire = isset($_POST['last_earned_expire']) ? 1 : 0;
    $notification_days = intval($_POST['notification_days']);
    $enabled = isset($_POST['enabled']) ? 1 : 0;
    
    $sql = "UPDATE PointsExpiration SET 
            ExpiryDays = ?, 
            LastEarnedPointsExpire = ?, 
            NotificationDays = ?, 
            Enabled = ? 
            WHERE ExpirationID = 1";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "iiii", $expiry_days, $last_earned_expire, $notification_days, $enabled);
    
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['success_message'] = "Expiration settings updated successfully!";
    } else {
        $_SESSION['error_message'] = "Error updating settings: " . mysqli_error($conn);
    }
    
    header("Location: points.php#expiration");
    exit();
}
?>