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
    
    // Record game
    $game_sql = "INSERT INTO GameField (UserID, GameType, PointsEarned) VALUES (?, ?, ?)";
    $game_stmt = mysqli_prepare($conn, $game_sql);
    mysqli_stmt_bind_param($game_stmt, "isi", $user_id, $game_type, $points_earned);
    
    if (mysqli_stmt_execute($game_stmt)) {
        // Update user points
        $update_sql = "UPDATE Users SET PointsBalance = PointsBalance + ? WHERE UserID = ?";
        $update_stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($update_stmt, "ii", $points_earned, $user_id);
        mysqli_stmt_execute($update_stmt);
        
        logActivity($conn, $user_id, 'PLAY_GAME', 'GameField', mysqli_insert_id($conn));
        
        echo json_encode(['success' => true, 'message' => 'Game recorded']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>