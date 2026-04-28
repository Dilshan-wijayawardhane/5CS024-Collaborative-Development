<?php
require_once 'config.php';
checkAdminAuth();

// Handle Approve
if (isset($_POST['approve_request'])) {
    $request_id = intval($_POST['request_id']);
    $club_id = intval($_POST['club_id']);
    $user_id = intval($_POST['user_id']);
    $admin_notes = mysqli_real_escape_string($conn, $_POST['admin_notes'] ?? '');
    
    mysqli_begin_transaction($conn);
    
    try {
        // Update join request
        $update_sql = "UPDATE JoinRequests SET Status='Approved', ReviewedBy=?, ReviewedAt=NOW(), AdminNotes=? WHERE RequestID=?";
        $update_stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($update_stmt, "isi", $_SESSION['user_id'], $admin_notes, $request_id);
        mysqli_stmt_execute($update_stmt);
        
        // Add to club memberships
        $insert_sql = "INSERT INTO ClubMemberships (ClubID, UserID, Role, Status) VALUES (?, ?, 'Member', 'Active')";
        $insert_stmt = mysqli_prepare($conn, $insert_sql);
        mysqli_stmt_bind_param($insert_stmt, "ii", $club_id, $user_id);
        mysqli_stmt_execute($insert_stmt);
        
        // Add 20 points to user
        $points_sql = "UPDATE Users SET PointsBalance = PointsBalance + 20 WHERE UserID = ?";
        $points_stmt = mysqli_prepare($conn, $points_sql);
        mysqli_stmt_bind_param($points_stmt, "i", $user_id);
        mysqli_stmt_execute($points_stmt);
        
        // Add to points history
        $history_sql = "INSERT INTO PointsHistory (UserID, PointsChange, ActionType, Description) VALUES (?, 20, 'CLUB_JOIN', 'Joined club via approval')";
        $history_stmt = mysqli_prepare($conn, $history_sql);
        mysqli_stmt_bind_param($history_stmt, "i", $user_id);
        mysqli_stmt_execute($history_stmt);
        
        // Send notification
        $club_sql = "SELECT Name FROM Clubs WHERE ClubID = ?";
        $club_stmt = mysqli_prepare($conn, $club_sql);
        mysqli_stmt_bind_param($club_stmt, "i", $club_id);
        mysqli_stmt_execute($club_stmt);
        $club_result = mysqli_stmt_get_result($club_stmt);
        $club_name = mysqli_fetch_assoc($club_result)['Name'];
        
        $notif_sql = "INSERT INTO notifications (user_id, title, message, type, created_at) 
                     VALUES (?, 'Club Join Request Approved', 
                            CONCAT('Your request to join ', ?, ' has been approved! You received 20 points.'), 
                            'club', NOW())";
        $notif_stmt = mysqli_prepare($conn, $notif_sql);
        mysqli_stmt_bind_param($notif_stmt, "iss", $user_id, $club_name);
        mysqli_stmt_execute($notif_stmt);
        
        mysqli_commit($conn);
        $_SESSION['success'] = "Request approved! User added to club and received 20 points.";
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
    header("Location: club_management.php?tab=requests");
    exit();
}

// Handle Reject
if (isset($_POST['reject_request'])) {
    $request_id = intval($_POST['request_id']);
    $admin_notes = mysqli_real_escape_string($conn, $_POST['admin_notes'] ?? '');
    
    // Get user and club info
    $info_sql = "SELECT jr.UserID, jr.ClubID, c.Name as ClubName, u.Name as UserName 
                 FROM JoinRequests jr
                 JOIN Clubs c ON jr.ClubID = c.ClubID
                 JOIN Users u ON jr.UserID = u.UserID
                 WHERE jr.RequestID = ?";
    $info_stmt = mysqli_prepare($conn, $info_sql);
    mysqli_stmt_bind_param($info_stmt, "i", $request_id);
    mysqli_stmt_execute($info_stmt);
    $info_result = mysqli_stmt_get_result($info_stmt);
    $info = mysqli_fetch_assoc($info_result);
    
    if ($info) {
        $update_sql = "UPDATE JoinRequests SET Status='Rejected', ReviewedBy=?, ReviewedAt=NOW(), AdminNotes=? WHERE RequestID=?";
        $update_stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($update_stmt, "isi", $_SESSION['user_id'], $admin_notes, $request_id);
        mysqli_stmt_execute($update_stmt);
        
        $notif_sql = "INSERT INTO notifications (user_id, title, message, type, created_at) 
                     VALUES (?, 'Club Join Request Rejected', 
                            CONCAT('Your request to join ', ?, ' has been rejected. Reason: ', ?), 
                            'club', NOW())";
        $notif_stmt = mysqli_prepare($conn, $notif_sql);
        mysqli_stmt_bind_param($notif_stmt, "isss", $info['UserID'], $info['ClubName'], $admin_notes);
        mysqli_stmt_execute($notif_stmt);
        
        $_SESSION['success'] = "Request rejected.";
    }
    header("Location: club_management.php?tab=requests");
    exit();
}
?>