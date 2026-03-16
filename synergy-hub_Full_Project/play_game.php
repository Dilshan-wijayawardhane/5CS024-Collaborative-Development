<?php
require_once 'config.php';
require_once 'functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['game_type']) && isset($_POST['points_earned'])) {
    $user_id = $_SESSION['user_id'];
    $game_type = $_POST['game_type'];
    $points_earned = intval($_POST['points_earned']);
    $points_used = isset($_POST['points_used']) ? intval($_POST['points_used']) : 0;
    
    // Record game
    $game_sql = "INSERT INTO GameField (UserID, GameType, PointsUsed, PointsEarned) VALUES (?, ?, ?, ?)";
    $game_stmt = mysqli_prepare($conn, $game_sql);
    mysqli_stmt_bind_param($game_stmt, "isii", $user_id, $game_type, $points_used, $points_earned);
    
    if (mysqli_stmt_execute($game_stmt)) {
        // Update user points
        $net_points = $points_earned - $points_used;
        if ($net_points != 0) {
            $update_sql = "UPDATE Users SET PointsBalance = PointsBalance + ? WHERE UserID = ?";
            $update_stmt = mysqli_prepare($conn, $update_sql);
            mysqli_stmt_bind_param($update_stmt, "ii", $net_points, $user_id);
            mysqli_stmt_execute($update_stmt);
        }
        
        logActivity($conn, $user_id, 'PLAY_GAME', 'GameField', mysqli_insert_id($conn));
        
        echo json_encode(['success' => true, 'message' => 'Game recorded']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>