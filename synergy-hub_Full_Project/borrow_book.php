<?php
/**
 * API Endpoint: Borrow a book from the campus library system
 * 
 * Behavior:
 * - Awards 5 points to user for borrowing a book
 * - Decrements available count of the book
 * - Logs the activity in the activity log
 * - Sets due date = borrow date + 14 days
 */

require_once 'config.php';
require_once 'functions.php';

header('Content-Type: application/json');

// Authentication Check
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

// Validate request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['book_id'])) {
    $user_id = $_SESSION['user_id'];
    $book_id = intval($_POST['book_id']);
    
    //Check if book exists and has available copies
    $book_sql = "SELECT * FROM books WHERE book_id = ? AND available > 0";
    $book_stmt = mysqli_prepare($conn, $book_sql);
    mysqli_stmt_bind_param($book_stmt, "i", $book_id);
    mysqli_stmt_execute($book_stmt);
    $book_result = mysqli_stmt_get_result($book_stmt);
    
    if (mysqli_num_rows($book_result) == 0) {
        echo json_encode(['success' => false, 'message' => 'Book not available']);
        exit();
    }
    
    $book = mysqli_fetch_assoc($book_result);
    
    // Prevent borrowing the same book twice
    $check_sql = "SELECT * FROM borrowed_books WHERE user_id = ? AND book_id = ? AND status = 'borrowed'";
    $check_stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($check_stmt, "ii", $user_id, $book_id);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    
    if (mysqli_num_rows($check_result) > 0) {
        echo json_encode(['success' => false, 'message' => 'You already borrowed this book']);
        exit();
    }
    
    //Prepare borrow data
    $due_date = date('Y-m-d', strtotime('+14 days'));
    $borrow_date = date('Y-m-d');
    
    //Create borrow record
    $borrow_sql = "INSERT INTO borrowed_books (user_id, book_id, borrow_date, due_date, status) VALUES (?, ?, ?, ?, 'borrowed')";
    $borrow_stmt = mysqli_prepare($conn, $borrow_sql);
    mysqli_stmt_bind_param($borrow_stmt, "iiss", $user_id, $book_id, $borrow_date, $due_date);
    
    if (mysqli_stmt_execute($borrow_stmt)) {
        // Decrease available copies
        $update_sql = "UPDATE books SET available = available - 1 WHERE book_id = ?";
        $update_stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($update_stmt, "i", $book_id);
        mysqli_stmt_execute($update_stmt);
        
        // Award points for borrowing
        $points = 5;
        $points_sql = "UPDATE Users SET PointsBalance = PointsBalance + ? WHERE UserID = ?";
        $points_stmt = mysqli_prepare($conn, $points_sql);
        mysqli_stmt_bind_param($points_stmt, "ii", $points, $user_id);
        mysqli_stmt_execute($points_stmt);
        
        // Log activity
        logActivity($conn, $user_id, 'BORROW_BOOK', 'books', $book_id);
        
        // Success response
        echo json_encode([
            'success' => true,
            'message' => 'Book borrowed',
            'due_date' => date('M d, Y', strtotime($due_date))
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>