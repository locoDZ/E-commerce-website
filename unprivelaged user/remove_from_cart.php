<?php


require_once '../user_db_config.php';

//  response array  
$response = array(
    'success' => false,
    'cart_items' => array(),
    'total' => 0,
    'total_items' => 0
);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'User not logged in';
    echo json_encode($response);
    exit();
}

// Check if item_id was provided
if (!isset($_POST['item_id'])) {
    $response['message'] = 'No item ID provided';
    echo json_encode($response);
    exit();
}

$item_id = (int)$_POST['item_id'];

// Get current cart from cookie
function getCart() {
    $user_id = $_SESSION['user_id'];
    $cookie_name = 'shopping_cart_' . $user_id;
    if (isset($_COOKIE[$cookie_name])) {
        return json_decode($_COOKIE[$cookie_name], true);
    }
    return array();
}

function updateCart($cart) {
    $user_id = $_SESSION['user_id'];
    $cookie_name = 'shopping_cart_' . $user_id;
    setcookie($cookie_name, json_encode($cart), time() + (30 * 24 * 60 * 60), '/');
}

try {
    // Get current cart
    $cart = getCart();
    
    // Remove item if it exists in cart
    if (isset($cart[$item_id])) {
        unset($cart[$item_id]);
        updateCart($cart);
        
        // Get updated cart details
        if (!empty($cart)) {
            // Get items details from database
            $item_ids = array_keys($cart);
            $placeholders = str_repeat('?,', count($item_ids) - 1) . '?';
            
            $stmt = $conn->prepare("SELECT id, name, price, discount, image_path FROM items WHERE id IN ($placeholders)");
            $stmt->execute($item_ids);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $total = 0;
            $total_items = 0;
            
            foreach ($items as $item) {
                $quantity = $cart[$item['id']];
                $price = $item['price'] - $item['discount'];
                $subtotal = $price * $quantity;
                $total += $subtotal;
                $total_items += $quantity;
                
                $response['cart_items'][] = array(
                    'id' => $item['id'],
                    'name' => $item['name'],
                    'price' => $price,
                    'quantity' => $quantity,
                    'subtotal' => $subtotal,
                    'image_path' => !empty($item['image_path']) ? '../admin dashboard/' . $item['image_path'] : '/api/placeholder/50/50'
                );
            }
            
            $response['total'] = $total;
            $response['total_items'] = $total_items;
        }
        
        $response['success'] = true;
        $response['message'] = 'Item removed successfully';
    } else {
        $response['message'] = 'Item not found in cart';
    }
    
} catch(PDOException $e) {
    $response['message'] = 'Database error occurred';
    error_log("Database Error: " . $e->getMessage());
}

// Send JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>