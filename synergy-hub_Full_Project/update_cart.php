<?php

/**
 * Handles two actions in the cafe cart:
 *  - Update quantity of an existing item
 *  - Remove an item from the cart
 * 
 * Security Notes:
 *  - Requires login
 *  - Validates input data
 *  - Uses reference (&) for updating quantity
 *  - Re-indexes array after unset
 */

require_once 'config.php';
require_once 'functions.php';

header('Content-Type: application/json');

// Authentication
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get POST data with validation
    $item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 0;
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    // Process remove action
    if ($action == 'remove') {
        
        foreach ($_SESSION['cart'] as $key => $item) {
            if ($item['item_id'] == $item_id) {
                unset($_SESSION['cart'][$key]);
                $_SESSION['cart'] = array_values($_SESSION['cart']);
                break;
            }
        }
        // Process quntity update
    } else if ($quantity > 0) {
        
        foreach ($_SESSION['cart'] as &$item) {
            if ($item['item_id'] == $item_id) {
                $item['quantity'] = $quantity;
                break;
            }
        }
    }
    
    // Success response
    echo json_encode(['success' => true]);
    exit();
}
?>