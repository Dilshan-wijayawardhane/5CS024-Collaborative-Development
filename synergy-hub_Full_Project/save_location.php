<?php

/**
 * Features:
 *  - Inserts a new bus location record
 *  - Uses ON DUPLICATE KEY UPDATE to update existing record for the same route
 *  - Simple response (plain text "saved" or "error")
 * 
 * Security Notes:
 *  - No authentication (should be protected by network or other means)
 *  - No input sanitization or validation
 *  - No activity logging
 */

require_once 'config.php';

// Validate Request Method and Required Fields
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['route']) && isset($_POST['place']) && isset($_POST['time'])) {
    
    $route = $_POST['route'];
    $place = $_POST['place'];
    $time = $_POST['time'];
    
    // Prepare and Execute UPSERT statement
    $sql = "INSERT INTO bus_routes (route_id, location, updated_time)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE
            location = VALUES(location), updated_time = VALUES(updated_time)";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sss", $route, $place, $time);
    
    if (mysqli_stmt_execute($stmt)) {
        echo "Saved";
    } else {
        echo "Error";
    }
} else {
    echo "Invalid request";
}
?>