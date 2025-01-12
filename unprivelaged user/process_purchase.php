<?php
require_once '../user_db_config.php';

// Function to get cart items
function getCart() {
    $user_id = $_SESSION['user_id'];
    $cookie_name = 'shopping_cart_' . $user_id;
    if (isset($_COOKIE[$cookie_name])) {
        return json_decode($_COOKIE[$cookie_name], true);
    }
    return array();
}

// Clear the cart cookie
function clearCart() {
    $user_id = $_SESSION['user_id'];
    $cookie_name = 'shopping_cart_' . $user_id;
    setcookie($cookie_name, '', time() - 3600, '/');
}

// Start the transaction
try {
    $conn->beginTransaction();
    
    $cart = getCart();
    $success = true;
    $errors = array();
    
    if (empty($cart)) {
        throw new Exception("Cart is empty");
    }
    
    // Process each item in the cart
    foreach ($cart as $item_id => $quantity) {
        // Get current item quantity from database
        $stmt = $conn->prepare("SELECT quantity FROM items WHERE id = ?");
        $stmt->execute([$item_id]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$item) {
            throw new Exception("Item with ID $item_id not found");
        }
        
        $new_quantity = $item['quantity'] - $quantity;
        
        // Check if we have enough stock
        if ($new_quantity < 0) {
            throw new Exception("Insufficient stock for item ID $item_id");
        }
        
        // Update the item quantity
        $update_stmt = $conn->prepare("UPDATE items SET quantity = ? WHERE id = ?");
        $update_stmt->execute([$new_quantity, $item_id]);
    }
    
    // If we get here, all updates were successful
    $conn->commit();
    
    // Clear the cart
    clearCart();
    
    // Send success response
    echo json_encode([
        'success' => true,
        'message' => 'Purchase completed successfully'
    ]);
    
} catch (Exception $e) {
    // Rollback the transaction if any error occurred
    $conn->rollBack();
    
    // Send error response
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}