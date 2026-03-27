<?php

require_once 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['route']) && isset($_POST['place']) && isset($_POST['time'])) {
    
    $route = $_POST['route'];
    $place = $_POST['place'];
    $time = $_POST['time'];
    
    
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