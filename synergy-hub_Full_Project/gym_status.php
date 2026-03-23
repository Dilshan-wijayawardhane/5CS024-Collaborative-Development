<?php
/**
 * Simple gym status dashboard page that shows current status of the WLV gym, including closing time and pool availability.
 * 
 * Security/Design Notes:
 *  - Requires login to access
 *  - No user-specific data - public-like view afrter login
 *  - Uses diredct query
 */

require_once 'config.php';
require_once 'functions.php';

// Authentication
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

// Fetch the latest gym status record
$sql = "SELECT * FROM gym_status ORDER BY id DESC LIMIT 1";
$result = mysqli_query($conn, $sql);
$gym = mysqli_fetch_assoc($result);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Gym Status - Synergy Hub</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div style="padding: 20px; color: white;">
        <h1>🏋️ WLV Gym Status</h1>
        <p>Status: <?php echo $gym['status']; ?></p>
        <p>Closes: <?php echo $gym['closing_time']; ?></p>
        <p>Pool: <?php echo $gym['pool_available'] ? 'Available' : 'Not Available'; ?></p>
        <p>Last Updated: <?php echo $gym['last_updated']; ?></p>
    </div>
</body>
</html>