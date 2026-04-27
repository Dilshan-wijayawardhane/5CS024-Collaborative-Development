<?php
require_once 'middleware.php';
require_once 'config.php';
checkAdminAuth();

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // Check if reward has been redeemed
    $check_sql = "SELECT COUNT(*) as count FROM RewardsRedemption WHERE RewardID = ? AND Status IN ('Pending', 'Approved')";
    $check_stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($check_stmt, "i", $id);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    $check = mysqli_fetch_assoc($check_result);
    
    if ($check['count'] > 0) {
        $_SESSION['error_message'] = "Cannot delete reward - it has pending redemptions";
    } else {
        $sql = "DELETE FROM Rewards WHERE RewardID = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success_message'] = "Reward deleted successfully!";
            logAdminActivity($conn, 'DELETE_REWARD', "Reward ID: $id");
        } else {
            $_SESSION['error_message'] = "Error deleting reward: " . mysqli_error($conn);
        }
    }
}

header("Location: points.php#rewards");
exit();
?>