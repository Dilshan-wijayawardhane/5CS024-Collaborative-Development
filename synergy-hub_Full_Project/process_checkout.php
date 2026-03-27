<?php


require_once 'config.php';
require_once 'functions.php';

header('Content-Type: application/json');




error_reporting(E_ALL);
ini_set('display_errors', 1);




if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];




$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid request data']);
    exit();
}

if (!isset($data['items']) || empty($data['items']) || !isset($data['payment_method'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}




mysqli_begin_transaction($conn);

try {
    $order_ids = [];
    
    

    foreach ($data['items'] as $item) {
        for ($i = 0; $i < $item['quantity']; $i++) {
            $order_sql = "INSERT INTO Orders (UserID, ItemName, Category, Price, Quantity, Status) 
                          VALUES (?, ?, ?, ?, 1, 'Pending')";
            $order_stmt = mysqli_prepare($conn, $order_sql);
            
            $category = $item['category'] ?? 'Food';
            
            mysqli_stmt_bind_param($order_stmt, "issd", 
                $user_id, 
                $item['name'], 
                $category, 
                $item['price']
            );
            
            if (!mysqli_stmt_execute($order_stmt)) {
                throw new Exception('Failed to insert order: ' . mysqli_error($conn));
            }
            
            $order_ids[] = mysqli_insert_id($conn);
        }
    }
    
    


    if ($data['payment_method'] === 'points' && $data['points_used'] > 0) {
        


        $check_sql = "SELECT PointsBalance FROM Users WHERE UserID = ?";
        $check_stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($check_stmt, "i", $user_id);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);
        $user = mysqli_fetch_assoc($check_result);
        
        if ($user['PointsBalance'] < $data['points_used']) {
            throw new Exception('Insufficient points');
        }
        
        $points_sql = "UPDATE Users SET PointsBalance = PointsBalance - ? WHERE UserID = ?";
        $points_stmt = mysqli_prepare($conn, $points_sql);
        mysqli_stmt_bind_param($points_stmt, "ii", $data['points_used'], $user_id);
        
        if (!mysqli_stmt_execute($points_stmt)) {
            throw new Exception('Failed to deduct points');
        }
    }
    
    logActivity($conn, $user_id, 'ORDER_PLACED', 'Orders', $order_ids[0] ?? 0);
    
    
    mysqli_commit($conn);
    
    unset($_SESSION['cart']);
    
    echo json_encode([
        'success' => true,
        'message' => 'Order placed successfully',
        'order_ids' => $order_ids,
        'count' => count($order_ids)
    ]);
    
} catch (Exception $e) {
    


    mysqli_rollback($conn);
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>