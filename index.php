<?php
// Start the session
session_start();

// Include the database configuration file
require_once 'user_db_config.php';

// Handle logout request
if (isset($_POST['logout'])) {
    session_destroy();
    header('Location: ./login and regestration/login.php');
    exit();
}

// Function to get random deals from the database
function getRandomDeals($conn, $limit = 3) {
    try {
        $stmt = $conn->prepare("SELECT * FROM items ORDER BY RAND() LIMIT :limit");
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Error fetching random deals: " . $e->getMessage());
        return [];
    }
}

// Function to get new arrivals from the database
function getNewArrivals($conn, $limit = 3) {
    try {
        $stmt = $conn->prepare("SELECT * FROM items ORDER BY created_at DESC LIMIT :limit");
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Error fetching new arrivals: " . $e->getMessage());
        return [];
    }
}

// Fetch random deals and new arrivals
$deals = getRandomDeals($conn);
$newArrivals = getNewArrivals($conn);

// Prepare slides for the slideshow
$slides = array_map(function($item) {
    $priceDisplay = '';
    if (!empty($item['discount']) && $item['discount'] > 0) {
        $discountedPrice = $item['price'] - $item['discount'];
        $priceDisplay = sprintf(
            '<span style="text-decoration: line-through; color: #999;">$%s</span><br>' .
            '<span style="color: #ff4444; font-weight: bold;">$%s</span><br>',
            number_format($item['price'], 2),
            number_format($discountedPrice, 2)
        );
    } else {
        $priceDisplay = '$' . number_format($item['price'], 2);
    }

    return [
        'title' => $item['name'],
        'description' => $item['description'] ?: 'Check out this amazing product!',
        'price_display' => $priceDisplay,
        'image' => isset($item['image_path']) ? './admin dashboard/' . $item['image_path'] : '/api/placeholder/1200/500',
        'link' => '#'
    ];
}, $newArrivals);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Our Store</title>
    <link rel="stylesheet" href="main_landing_page_styles.css">
</head>
<body>
    <nav class="navbar" style="font-family: Arial, sans-serif;">
        <a href="index.php" class="nav-brand">Nova market</a>
        <div class="nav-links">
            <a href="./unprivelaged user/market.php" class="nav-link">Market</a>
            <a href="#about" class="nav-link">About Us</a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" style="display: inline;">
                    <button type="submit" name="logout" class="signin-btn">Sign Out</button>
                </form>
            <?php else: ?>
                <a href="./login and regestration/login.php" class="signin-btn">Sign In</a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="slideshow-container">
        <?php foreach ($slides as $index => $slide): ?>
            <div class="slide <?php echo $index === 0 ? 'active' : ''; ?>" style="background-image: url('<?php echo htmlspecialchars($slide['image']); ?>')">
                <div class="slide-content">
                    <h2 class="slide-title"><?php echo htmlspecialchars($slide['title']); ?></h2>
                    <p class="slide-description"><?php echo htmlspecialchars($slide['description']); ?></p>
                    <?php if ($slide['price_display']): ?>
                        <p class="slide-description"><?php echo $slide['price_display']; ?></p>
                    <?php endif; ?>
                    <div class="slide-buttons">
                        <a href="<?php echo htmlspecialchars($slide['link']); ?>" class="slide-btn">Learn More</a>
                        <a href="./unprivelaged user/market.php" class="slide-btn" style="margin-left: 10px;">Shop Now</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <section class="section" id="market">
        <h2 class="section-title">Best Deals</h2>
        <div class="deals-grid">
            <?php foreach ($deals as $deal): ?>
                <div class="deal-card">
                    <img src="<?php echo htmlspecialchars(
                        !empty($deal['image_path']) 
                            ? './admin dashboard/' . $deal['image_path'] 
                            : '/api/placeholder/400/300'
                    ); ?>" 
                         alt="<?php echo htmlspecialchars($deal['name']); ?>" 
                         class="deal-image">
                    <div class="deal-content">
                        <h3 class="deal-title"><?php echo htmlspecialchars($deal['name']); ?></h3>
                        <?php if (!empty($deal['discount']) && $deal['discount'] > 0): ?>
                            <p class="deal-price">
                                <span style="text-decoration: line-through; color: #999;">
                                    $<?php echo number_format($deal['price'], 2); ?>
                                </span>
                                <br>
                                <span style="color: #ff4444; font-weight: bold;">
                                    $<?php echo number_format($deal['price'] - $deal['discount'], 2); ?>
                                </span>
                                <span style="font-size: 0.9em; color: #ff4444;">
                                    (Save $<?php echo number_format($deal['discount'], 2); ?>)
                                </span>
                            </p>
                        <?php else: ?>
                            <p class="deal-price">$<?php echo number_format($deal['price'], 2); ?></p>
                        <?php endif; ?>
                        <p><?php echo htmlspecialchars($deal['description'] ?: 'Check out this amazing deal!'); ?></p>
                        <a href="#" class="deal-btn">View Deal</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="section">
        <h2 class="section-title">New Arrivals</h2>
        <div class="deals-grid">
            <?php foreach ($newArrivals as $item): ?>
                <div class="deal-card">
                    <img src="<?php echo htmlspecialchars('./admin dashboard/' . $item['image_path'] ?: '/api/placeholder/400/300'); ?>" 
                         alt="<?php echo htmlspecialchars($item['name']); ?>" 
                         class="deal-image">
                    <div class="deal-content">
                        <h3 class="deal-title"><?php echo htmlspecialchars($item['name']); ?></h3>
                        <p class="deal-price">$<?php echo number_format($item['price'], 2); ?></p>
                        <p><?php echo htmlspecialchars($item['description'] ?: 'New arrival! Check it out!'); ?></p>
                        <a href="#" class="deal-btn">View Item</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="section about-section" id="about">
        <h2 class="section-title">About Us</h2>
        <div class="about-content">
            <p>Welcome to our store! We are dedicated to providing the best products and services to our customers. With years of experience in the market, we pride ourselves on our quality selection and exceptional customer service.</p>
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const slides = document.querySelectorAll('.slide');
            let currentSlide = 0;

            function showSlide(index) {
                slides.forEach(slide => slide.classList.remove('active'));
                slides[index].classList.add('active');
            }

            function nextSlide() {
                currentSlide = (currentSlide + 1) % slides.length;
                showSlide(currentSlide);
            }

            setInterval(nextSlide, 5000);
        });
    </script>
</body>
</html>
