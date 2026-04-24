<?php
require_once 'config.php';
require_once 'functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['club_id'])) {
    $club_id = intval($_POST['club_id']);
    
    // Check if club exists and is active
    $club_sql = "SELECT ClubID, Name FROM Clubs WHERE ClubID = ? AND Status = 'Active'";
    $club_stmt = mysqli_prepare($conn, $club_sql);
    mysqli_stmt_bind_param($club_stmt, "i", $club_id);
    mysqli_stmt_execute($club_stmt);
    $club_result = mysqli_stmt_get_result($club_stmt);
    $club = mysqli_fetch_assoc($club_result);
    
    if (!$club) {
        echo json_encode(['success' => false, 'message' => 'Club not found or inactive']);
        exit();
    }
    
    // Check if already a member
    $member_sql = "SELECT MembershipID FROM ClubMemberships WHERE ClubID = ? AND UserID = ? AND Status = 'Active'";
    $member_stmt = mysqli_prepare($conn, $member_sql);
    mysqli_stmt_bind_param($member_stmt, "ii", $club_id, $user_id);
    mysqli_stmt_execute($member_stmt);
    $member_result = mysqli_stmt_get_result($member_stmt);
    
    if (mysqli_num_rows($member_result) > 0) {
        echo json_encode(['success' => false, 'message' => 'You are already a member of this club']);
        exit();
    }
    
    // Check if pending request exists
    $pending_sql = "SELECT RequestID FROM JoinRequests WHERE ClubID = ? AND UserID = ? AND Status = 'Pending'";
    $pending_stmt = mysqli_prepare($conn, $pending_sql);
    mysqli_stmt_bind_param($pending_stmt, "ii", $club_id, $user_id);
    mysqli_stmt_execute($pending_stmt);
    $pending_result = mysqli_stmt_get_result($pending_stmt);
    
    if (mysqli_num_rows($pending_result) > 0) {
        echo json_encode(['success' => false, 'message' => 'You already have a pending request for this club']);
        exit();
    }
    
    // Create join request
    $insert_sql = "INSERT INTO JoinRequests (ClubID, UserID, Status) VALUES (?, ?, 'Pending')";
    $insert_stmt = mysqli_prepare($conn, $insert_sql);
    mysqli_stmt_bind_param($insert_stmt, "ii", $club_id, $user_id);
    
    if (mysqli_stmt_execute($insert_stmt)) {
        // Log activity
        logActivity($conn, $user_id, 'JOIN_REQUEST', 'Clubs', $club_id);
        
        // Notify admin (insert into notifications table)
        $admin_sql = "SELECT UserID FROM Users WHERE Role = 'Admin'";
        $admin_result = mysqli_query($conn, $admin_sql);
        while($admin = mysqli_fetch_assoc($admin_result)) {
            $notif_sql = "INSERT INTO notifications (user_id, title, message, type, created_at) 
                         VALUES (?, 'New Club Join Request', 
                                CONCAT('User ', ?, ' has requested to join ', ?), 
                                'club', NOW())";
            $notif_stmt = mysqli_prepare($conn, $notif_sql);
            mysqli_stmt_bind_param($notif_stmt, "isss", $admin['UserID'], $_SESSION['user_name'], $club['Name']);
            mysqli_stmt_execute($notif_stmt);
        }
        
        echo json_encode(['success' => true, 'message' => 'Request submitted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>