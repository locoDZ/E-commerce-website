<?php
require_once '../user_db_config.php';

// Function to get the current cart from the user's cookies
function getCart() {
    $user_id = $_SESSION['user_id'];
    $cookie_name = 'shopping_cart_' . $user_id;
    if (isset($_COOKIE[$cookie_name])) {
        return json_decode($_COOKIE[$cookie_name], true);
    }
    return array();
}

// Function to update the cart in the user's cookies
function updateCart($cart) {
    $user_id = $_SESSION['user_id'];
    $cookie_name = 'shopping_cart_' . $user_id;
    setcookie($cookie_name, json_encode($cart), time() + (30 * 24 * 60 * 60), '/');
}

// Function to get detailed information about the items in the cart
function getCartItemsDetails($cart) {
    global $conn;
    $cart_items = array();
    $total = 0;
    $total_items = 0;

    if (!empty($cart)) {
        $item_ids = array_keys($cart);
        $placeholders = str_repeat('?,', count($item_ids) - 1) . '?';

        $stmt = $conn->prepare("SELECT * FROM items WHERE id IN ($placeholders)");
        $stmt->execute($item_ids);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($items as $item) {
            $quantity = $cart[$item['id']];
            $price = $item['price'] - $item['discount'];
            $subtotal = $price * $quantity;
            $total += $subtotal;
            $total_items += $quantity;

            $cart_items[] = array(
                'id' => $item['id'],
                'name' => $item['name'],
                'price' => $price,
                'quantity' => $quantity,
                'subtotal' => $subtotal,
                'image_path' => $item['image_path']
            );
        }
    }

    return array(
        'cart_items' => $cart_items,
        'total' => $total,
        'total_items' => $total_items
    );
}

// Handle the POST request to add an item to the cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['item_id'])) {
    $item_id = $_POST['item_id'];
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

    $cart = getCart();

    // Update the quantity if the item is already in the cart
    if (isset($cart[$item_id])) {
        $cart[$item_id] += $quantity;
    } else {
        // Add the item to the cart if it's not already there
        $cart[$item_id] = $quantity;
    }

    // Update the cart in the cookies
    updateCart($cart);

    // Get the updated cart details
    $cart_details = getCartItemsDetails($cart);

    // Return the updated cart details as JSON
    echo json_encode(array_merge(['success' => true], $cart_details));
    exit;
}
?>