<?php

/**
 * Features:
 *  - Marks a borrowed book as 'returned'
 *  - Updates book availability count
 *  - Awards 2 points to the user for returning the book
 *  - Logs the return activity
 * 
 * Security Notes:
 *  - Requires user to be logged in
 *  - Checks that the book belong to the current user and is still 'borrowed'
 */

require_once 'config.php';
require_once 'functions.php';

header('Content-Type: application/json');

// Authentication
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

// Validate request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['borrow_id']) && isset($_POST['book_id'])) {
    $user_id = $_SESSION['user_id'];
    $borrow_id = intval($_POST['borrow_id']);
    $book_id = intval($_POST['book_id']);
    
   // Update borrowed_books record to 'returned'
    $return_sql = "UPDATE borrowed_books SET return_date = CURDATE(), status = 'returned' 
                    WHERE borrow_id = ? AND user_id = ? AND status = 'borrowed'";
    $return_stmt = mysqli_prepare($conn, $return_sql);
    mysqli_stmt_bind_param($return_stmt, "ii", $borrow_id, $user_id);
    
    if (mysqli_stmt_execute($return_stmt) && mysqli_stmt_affected_rows($return_stmt) > 0) {
        
        // Increase book availability
        $update_sql = "UPDATE books SET available = available + 1 WHERE book_id = ?";
        $update_stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($update_stmt, "i", $book_id);
        mysqli_stmt_execute($update_stmt);
        
        // Award 2 points for returning the book
        $points = 2;
        $points_sql = "UPDATE Users SET PointsBalance = PointsBalance + ? WHERE UserID = ?";
        $points_stmt = mysqli_prepare($conn, $points_sql);
        mysqli_stmt_bind_param($points_stmt, "ii", $points, $user_id);
        mysqli_stmt_execute($points_stmt);
        
       // Log activity
        logActivity($conn, $user_id, 'RETURN_BOOK', 'books', $book_id);
        
        echo json_encode(['success' => true, 'message' => 'Book returned']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No borrowed book found']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>