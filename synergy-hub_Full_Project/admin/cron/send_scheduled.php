<?php
require_once dirname(__DIR__) . '/../config.php';

// Get all scheduled notifications that are due
$sql = "SELECT * FROM NotificationLog 
        WHERE Status = 'scheduled' 
        AND ScheduledFor <= NOW() 
        LIMIT 10";
$result = mysqli_query($conn, $sql);

while($notification = mysqli_fetch_assoc($result)) {
    // Mark as sending
    $update_sql = "UPDATE NotificationLog SET Status = 'sending' WHERE LogID = ?";
    $update_stmt = mysqli_prepare($conn, $update_sql);
    mysqli_stmt_bind_param($update_stmt, "i", $notification['LogID']);
    mysqli_stmt_execute($update_stmt);
    
    // Get target users based on target_type
    $user_ids = [];
    
    switch($notification['TargetType']) {
        case 'all':
            $user_sql = "SELECT UserID FROM Users WHERE Role = 'User'";
            $user_result = mysqli_query($conn, $user_sql);
            while($user = mysqli_fetch_assoc($user_result)) {
                $user_ids[] = $user['UserID'];
            }
            break;
            
        case 'user_group':
            $group_sql = "SELECT UserID FROM UserGroupMembers WHERE GroupID = ?";
            $group_stmt = mysqli_prepare($conn, $group_sql);
            mysqli_stmt_bind_param($group_stmt, "i", $notification['TargetValue']);
            mysqli_stmt_execute($group_stmt);
            $group_result = mysqli_stmt_get_result($group_stmt);
            while($user = mysqli_fetch_assoc($group_result)) {
                $user_ids[] = $user['UserID'];
            }
            break;
            
        case 'location':
            $loc_sql = "SELECT DISTINCT UserID FROM CheckIns WHERE FacilityID = ?";
            $loc_stmt = mysqli_prepare($conn, $loc_sql);
            mysqli_stmt_bind_param($loc_stmt, "i", $notification['TargetValue']);
            mysqli_stmt_execute($loc_stmt);
            $loc_result = mysqli_stmt_get_result($loc_stmt);
            while($user = mysqli_fetch_assoc($loc_result)) {
                $user_ids[] = $user['UserID'];
            }
            break;
            
        case 'tier':
            $points = intval($notification['TargetValue']);
            $tier_sql = "SELECT UserID FROM Users WHERE PointsBalance >= ?";
            $tier_stmt = mysqli_prepare($conn, $tier_sql);
            mysqli_stmt_bind_param($tier_stmt, "i", $points);
            mysqli_stmt_execute($tier_stmt);
            $tier_result = mysqli_stmt_get_result($tier_stmt);
            while($user = mysqli_fetch_assoc($tier_result)) {
                $user_ids[] = $user['UserID'];
            }
            break;
    }
    
    $sent_count = 0;
    $failed_count = 0;
    
    // Determine types for both tables
    $type_upper = match($notification['Type']) {
        'gym' => 'Announcement',
        'event' => 'Event',
        'transport' => 'Transport',
        'emergency' => 'Announcement',
        default => 'Announcement'
    };
    
    foreach($user_ids as $uid) {
        // Insert into uppercase table
        $insert_upper_sql = "INSERT INTO Notifications (UserID, Message, Type, Timestamp, Status) 
                           VALUES (?, ?, ?, NOW(), 'Unread')";
        $insert_upper_stmt = mysqli_prepare($conn, $insert_upper_sql);
        mysqli_stmt_bind_param($insert_upper_stmt, "iss", $uid, $notification['Message'], $type_upper);
        
        // Insert into lowercase table
        $insert_lower_sql = "INSERT INTO notifications (user_id, title, message, type, created_at, is_read) 
                           VALUES (?, ?, ?, ?, NOW(), FALSE)";
        $insert_lower_stmt = mysqli_prepare($conn, $insert_lower_sql);
        mysqli_stmt_bind_param($insert_lower_stmt, "isss", $uid, $notification['Title'], $notification['Message'], $notification['Type']);
        
        if (mysqli_stmt_execute($insert_upper_stmt) && mysqli_stmt_execute($insert_lower_stmt)) {
            $sent_count++;
        } else {
            $failed_count++;
        }
    }
    
    // Update log
    $final_sql = "UPDATE NotificationLog SET 
                  Status = 'sent', 
                  SentAt = NOW(), 
                  SentCount = ?, 
                  FailedCount = ? 
                  WHERE LogID = ?";
    $final_stmt = mysqli_prepare($conn, $final_sql);
    mysqli_stmt_bind_param($final_stmt, "iii", $sent_count, $failed_count, $notification['LogID']);
    mysqli_stmt_execute($final_stmt);
}

echo "Scheduled notifications processed.";
?>