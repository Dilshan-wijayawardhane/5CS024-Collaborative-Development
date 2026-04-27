<?php
require_once 'middleware.php';
require_once 'config.php';
checkAdminAuth();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $tier_name = mysqli_real_escape_string($conn, $_POST['tier_name']);
    $min_points = intval($_POST['min_points']);
    $max_points = !empty($_POST['max_points']) ? intval($_POST['max_points']) : 'NULL';
    $multiplier = floatval($_POST['multiplier']);
    $color = mysqli_real_escape_string($conn, $_POST['color'] ?? '#667eea');
    $icon = mysqli_real_escape_string($conn, $_POST['icon'] ?? 'fa-star');
    $benefits = json_encode(['points_multiplier' => $multiplier]);
    
    $sql = "INSERT INTO MembershipTiers (TierName, MinPoints, MaxPoints, Multiplier, Benefits, Color, Icon) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "siidsss", $tier_name, $min_points, $max_points, $multiplier, $benefits, $color, $icon);
    
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['success_message'] = "Tier added successfully!";
        logAdminActivity($conn, 'ADD_TIER', "Tier: $tier_name");
    } else {
        $_SESSION['error_message'] = "Error adding tier: " . mysqli_error($conn);
    }
    
    header("Location: points.php#tiers");
    exit();
}
?>