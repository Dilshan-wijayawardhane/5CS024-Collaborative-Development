<?php
/**
 * Allow ADMIN users to create and send notifications to specific users
 * 
 * Security Notes:
 * - Uses prepared statements to prevent SQL injection
 * - Access control: Only users with 'Admin' role can access this page
 */

require_once 'config.php';      //Database connection and constants
require_once 'functions.php';   //Contains isLoggedIn() and other helper functions

//Redirect to login if user is not authenticated
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

//Only admin can create notifications
if ($_SESSION['user_role'] != 'Admin') {
    header("Location: index.php");
    exit();
}

$message = '';      //Feedback message to show on page

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    //Get and sanitize input
    $user_id = intval($_POST['user_id']);
    $title = $_POST['title'];
    $message_text = $_POST['message'];
    $type = $_POST['type'];
    
    //Prepare INSERT query with placeholders
    $sql = "INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "isss", $user_id, $title, $message_text, $type);
    // "isss" means: integer (user_id), string (title), string (message), string (type)

    if (mysqli_stmt_execute($stmt)) {
        $message = '<div class="success">Notification added successfully!</div>';
    } else {
        $message = '<div class="error">Error adding notification</div>';
    }
}


$users_sql = "SELECT UserID, Name FROM Users";
$users_result = mysqli_query($conn, $users_sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Notification</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        .container { max-width: 500px; margin: 0 auto; }
        input, select, textarea { width: 100%; padding: 10px; margin: 10px 0; }
        button { padding: 10px 20px; background: #667eea; color: white; border: none; cursor: pointer; }
        .success { background: #d4edda; color: #155724; padding: 10px; margin: 10px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Add Notification</h1>
        <?php echo $message; ?>
        <form method="POST">
            <select name="user_id" required>
                <option value="">Select User</option>
                <?php while($user = mysqli_fetch_assoc($users_result)): ?>
                    <option value="<?php echo $user['UserID']; ?>">
                        <?php echo $user['Name']; ?> (ID: <?php echo $user['UserID']; ?>)
                    </option>
                <?php endwhile; ?>
            </select>
            
            <input type="text" name="title" placeholder="Notification Title" required>
            <textarea name="message" placeholder="Notification Message" rows="3" required></textarea>
            
            <select name="type" required>
                <option value="general">General</option>
                <option value="gym">Gym</option>
                <option value="event">Event</option>
                <option value="transport">Transport</option>
            </select>
            
            <button type="submit">Add Notification</button>
        </form>
        <p><a href="index.php">Back to Dashboard</a></p>
    </div>
</body>
</html>