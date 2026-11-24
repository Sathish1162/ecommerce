
<?php
session_start();

// Check whether user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: pages/login.php");
    exit;
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Database connection
require_once "includes/db.php";

// Fetch all products
try {
    $stmt = $conn->query("SELECT * FROM products ORDER BY id DESC");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Count cart items for the logged-in user
    $cartStmt = $conn->prepare(
        "SELECT COALESCE(SUM(quantity), 0) FROM cart WHERE user_id = ?"
    );
    $cartStmt->execute([$_SESSION['user_id']]);
    $cartCount = (int)$cartStmt->fetchColumn();

} catch (PDOException $e) {
    error_log($e->getMessage());
    $products = [];
    $cartCount = 0;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Store</title>

    <link rel="stylesheet" href="css/home.css">

</head>

<body>

    <!-- Navbar -->
    <nav class="navbar">

        <div class="logo">
            My Store
        </div>

        <div class="nav-links">

            <a href="index.php">Home</a>

            <a href="#products">Products</a>

            <a href="pages/add_product.php">Add Product</a>

            <a href="pages/cart.php">
                Cart (<?php echo $cartCount; ?>)
            </a>

            <a href="pages/logout.php" class="logout-btn">
                Logout
            </a>

        </div>

    </nav>


    <!-- Hero Section -->
    <section class="hero">

        <div class="hero-content">

            <h1>Welcome to My Store!</h1>

            <p>
                Hello,
                <?php echo htmlspecialchars($_SESSION['username']); ?>!

                Discover amazing products at great prices.
            </p>

            <a href="#products" class="shop-btn">
                Explore Products
            </a>

        </div>

    </section>


    <!-- Products Section -->
    <section class="products-section" id="products">

        <h2>Our Products</h2>

        <p class="section-description">
            Explore our latest collection.
        </p>


        <div class="product-container">

            <?php if (count($products) > 0): ?>

                <?php foreach ($products as $product): ?>

                    <div class="product-card">

                        <!-- Product Image -->
                        <?php if (!empty($product['image'])): ?>

                            <img
                                src="<?php echo htmlspecialchars($product['image']); ?>"
                                alt="<?php echo htmlspecialchars($product['name']); ?>"
                            >

                        <?php else: ?>

                            <div class="no-image">
                                No Image Available
                            </div>

                        <?php endif; ?>


                        <!-- Product Details -->
                        <div class="product-info">

                            <h3>
                                <?php echo htmlspecialchars($product['name']); ?>
                            </h3>

                            <p>
                                <?php echo htmlspecialchars($product['description'] ?? ''); ?>
                            </p>

                            <h4>
                                ₹<?php echo number_format((float)$product['price'], 2); ?>
                            </h4>


                            <!-- Add to Cart -->
                            <form
                                action="pages/add_to_cart.php"
                                method="POST"
                            >

                                <input
                                    type="hidden"
                                    name="product_id"
                                    value="<?php echo (int)$product['id']; ?>"
                                >

                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>"
                                >

                                <button
                                    type="submit"
                                    class="add-cart-btn"
                                >
                                    Add to Cart
                                </button>

                            </form>


                            <!-- Remove Product -->
                            <form
                                action="pages/delete_product.php"
                                method="POST"
                                onsubmit="return confirm('Are you sure you want to remove this product?');"
                            >

                                <input
                                    type="hidden"
                                    name="product_id"
                                    value="<?php echo (int)$product['id']; ?>"
                                >

                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>"
                                >

                                <button
                                    type="submit"
                                    class="delete-btn"
                                >
                                    Remove Product
                                </button>

                            </form>

                        </div>

                    </div>

                <?php endforeach; ?>

            <?php else: ?>

                <p class="empty-message">
                    No products available yet.
                    Please check back soon!
                </p>

            <?php endif; ?>

        </div>

    </section>


    <!-- Footer -->
    <footer>

        <p>
            &copy; <?php echo date("Y"); ?>
            My Store. All rights reserved.
        </p>

    </footer>

</body>

</html>
