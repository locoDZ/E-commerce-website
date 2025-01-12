<?php
// market.php

require_once '../user_db_config.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login and regestration/login.php");
    exit();
}

if (isset($_POST['logout'])) {
    session_start();
    session_destroy();
    header("Location: ../login and regestration/login.php");
    exit();
}

// Initialize variables
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$items_per_page = 9;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$show_discounted = isset($_GET['discounted']) ? true : false;
$offset = ($page - 1) * $items_per_page;

// Build the base query
$query = "SELECT * FROM items WHERE 1=1";
$count_query = "SELECT COUNT(*) as total FROM items WHERE 1=1";
$params = array();

// Add search condition if search term exists
if (!empty($search)) {
    $query .= " AND (name LIKE :search OR description LIKE :search)";
    $count_query .= " AND (name LIKE :search OR description LIKE :search)";
    $params[':search'] = "%$search%";
}

// Add discount filter if selected
if ($show_discounted) {
    $query .= " AND discount > 0";
    $count_query .= " AND discount > 0";
}

// Add pagination
$query .= " LIMIT :offset, :items_per_page";

// Function to get the cart
function getCart() {
    $user_id = $_SESSION['user_id'];
    $cookie_name = 'shopping_cart_' . $user_id;
    if (isset($_COOKIE[$cookie_name])) {
        return json_decode($_COOKIE[$cookie_name], true);
    }
    return array();
}

// Function to update the cart
function updateCart($cart) {
    $user_id = $_SESSION['user_id'];
    $cookie_name = 'shopping_cart_' . $user_id;
    setcookie($cookie_name, json_encode($cart), time() + (30 * 24 * 60 * 60), '/');
}

// Function to get cart items details
function getCartItemsDetails($conn) {
    $cart = getCart();
    $cart_items = array();
    $total = 0;
    
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
    return array('items' => $cart_items, 'total' => $total);
}

try {
    // Get total items count
    $stmt = $conn->prepare($count_query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $total_items = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Calculate total pages
    $total_pages = ceil($total_items / $items_per_page);
    
    // Get items for current page
    $stmt = $conn->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':items_per_page', $items_per_page, PDO::PARAM_INT);
    $stmt->execute();
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}

$cart_data = getCartItemsDetails($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Market</title>
    <link rel="stylesheet" href="market_styles.css">
    <script src="marketScript.js"></script>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <a href="../index.php" class="nav-brand">Nova market</a>
        <div class="nav-links">
            <a href="../index.php" class="nav-link">Home</a>
            <a href="#about" class="nav-link">About Us</a>
            <div class="cart-dropdown">
                <button class="cart-icon-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="cart-icon">
                        <circle cx="9" cy="21" r="1"></circle>
                        <circle cx="20" cy="21" r="1"></circle>
                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                    </svg>
                    <span class="cart-count"><?php echo array_sum(getCart()); ?></span>
                </button>
                <div class="cart-dropdown-content">
                    <?php if (empty($cart_data['items'])): ?>
                        <p class="empty-cart-message">Your cart is empty</p>
                    <?php else: ?>
                        <div class="cart-items-container">
                            <?php foreach ($cart_data['items'] as $item): ?>
                                <div class="cart-dropdown-item" data-item-id="<?php echo $item['id']; ?>">
                                    <img src="<?php echo htmlspecialchars(!empty($item['image_path']) ? '../admin dashboard/' . $item['image_path'] : '/api/placeholder/50/50'); ?>" 
                                         alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                         class="cart-item-thumbnail">
                                    <div class="cart-item-info">
                                        <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                                        <p>$<?php echo number_format($item['price'], 2); ?> x <?php echo $item['quantity']; ?></p>
                                    </div>
                                    <button class="remove-item-btn" data-item-id="<?php echo $item['id']; ?>">&times;</button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="cart-dropdown-footer">
                            <div class="cart-total">
                                Total: $<?php echo number_format($cart_data['total'], 2); ?>
                            </div>
                            <button class="purchase-btn">Purchase</button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <form method="POST" style="display: inline;">
                <button type="submit" name="logout" class="signin-btn">Sign Out</button>
            </form>
        </div>
    </nav>

    <!-- Search and Filter Section -->
    <div class="search-section">
        <form method="GET" class="search-form">
            <input type="text" name="search" placeholder="Search items..." 
                   value="<?php echo htmlspecialchars($search); ?>" class="search-input">
            <div class="filter-option">
                <input type="checkbox" id="discounted" name="discounted" 
                       <?php echo $show_discounted ? 'checked' : ''; ?>>
                <label for="discounted">Show only discounted items</label>
            </div>
            <button type="submit" class="search-btn">Search</button>
        </form>
    </div>

    <!-- Items Grid -->
    <div class="items-grid">
        <?php foreach ($items as $item): ?>
            <div class="item-card" data-item-id="<?php echo $item['id']; ?>">
                <img src="<?php echo htmlspecialchars(
                    !empty($item['image_path']) 
                        ? '../admin dashboard/' . $item['image_path'] 
                        : '/api/placeholder/400/300'
                ); ?>" 
                     alt="<?php echo htmlspecialchars($item['name']); ?>" 
                     class="item-image">
                <div class="item-content">
                    <h3 class="item-title"><?php echo htmlspecialchars($item['name']); ?></h3>
                    <?php if (!empty($item['discount']) && $item['discount'] > 0): ?>
                        <p class="item-price">
                            <span class="original-price">
                                $<?php echo number_format($item['price'], 2); ?>
                            </span>
                            <span class="discounted-price">
                                $<?php echo number_format($item['price'] - $item['discount'], 2); ?>
                            </span>
                            <span class="savings">
                                Save $<?php echo number_format($item['discount'], 2); ?>
                            </span>
                        </p>
                    <?php else: ?>
                        <p class="item-price">$<?php echo number_format($item['price'], 2); ?></p>
                    <?php endif; ?>
                    <p class="item-description">
                        <?php echo htmlspecialchars($item['description'] ?: 'No description available.'); ?>
                    </p>
                    <button class="add-to-cart-btn">Add to Cart</button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo ($page - 1); ?>&search=<?php echo urlencode($search); ?>&<?php echo $show_discounted ? 'discounted=on' : ''; ?>" 
                   class="page-link">Previous</a>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&<?php echo $show_discounted ? 'discounted=on' : ''; ?>" 
                   class="page-link <?php echo $i === $page ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo ($page + 1); ?>&search=<?php echo urlencode($search); ?>&<?php echo $show_discounted ? 'discounted=on' : ''; ?>" 
                   class="page-link">Next</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Purchase Modal -->
    <div id="purchaseModal" class="modal">
        <div class="modal-content">
            <h2>Purchase Successful!</h2>
            <p>Thank you for your purchase.</p>
            <button class="modal-close-btn">Close</button>
        </div>
    </div>
</body>
</html>