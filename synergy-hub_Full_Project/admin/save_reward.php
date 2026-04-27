<?php
require_once 'middleware.php';
require_once 'config.php';
checkAdminAuth();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $reward_id = isset($_POST['reward_id']) ? intval($_POST['reward_id']) : 0;
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $points_required = intval($_POST['points_required']);
    $availability = mysqli_real_escape_string($conn, $_POST['availability']);
    $quantity = !empty($_POST['quantity']) ? intval($_POST['quantity']) : 'NULL';
    
    if ($reward_id > 0) {
        $sql = "UPDATE Rewards SET 
                Name = ?, 
                Description = ?, 
                PointsRequired = ?, 
                Availability = ?, 
                Quantity = ? 
                WHERE RewardID = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssisii", $name, $description, $points_required, $availability, $quantity, $reward_id);
    } else {
        $sql = "INSERT INTO Rewards (Name, Description, PointsRequired, Availability, Quantity) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssisi", $name, $description, $points_required, $availability, $quantity);
    }
    
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['success_message'] = "Reward saved successfully!";
        logAdminActivity($conn, 'SAVE_REWARD', "Reward: $name, Points: $points_required");
    } else {
        $_SESSION['error_message'] = "Error saving reward: " . mysqli_error($conn);
    }
    
    header("Location: points.php#rewards");
    exit();
}
?>