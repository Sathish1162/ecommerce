<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once "../includes/db.php";

/* Create CSRF token */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$userId = (int)$_SESSION['user_id'];
$error = "";
$success = "";

/* Update cart quantity */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_cart'])) {

    if (
        empty($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        die("Invalid request.");
    }

    $cartId = (int)($_POST['cart_id'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 0);

    if ($cartId > 0 && $quantity >= 1 && $quantity <= 99) {

        $stmt = $conn->prepare("
            UPDATE cart
            SET quantity = ?
            WHERE id = ? AND user_id = ?
        ");

        $stmt->execute([
            $quantity,
            $cartId,
            $userId
        ]);

        $success = "Cart updated successfully.";
    }
}

/* Remove item from cart */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['remove_item'])) {

    if (
        empty($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        die("Invalid request.");
    }

    $cartId = (int)($_POST['cart_id'] ?? 0);

    if ($cartId > 0) {

        $stmt = $conn->prepare("
            DELETE FROM cart
            WHERE id = ? AND user_id = ?
        ");

        $stmt->execute([
            $cartId,
            $userId
        ]);

        $success = "Item removed from cart.";
    }
}

/* Fetch cart items */
try {

    $stmt = $conn->prepare("
        SELECT 
            cart.id AS cart_id,
            cart.quantity,
            products.id AS product_id,
            products.name,
            products.price,
            products.image,
            products.description
        FROM cart
        INNER JOIN products
            ON cart.product_id = products.id
        WHERE cart.user_id = ?
        ORDER BY cart.id DESC
    ");

    $stmt->execute([$userId]);

    $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    error_log($e->getMessage());
    $cartItems = [];
    $error = "Unable to load your cart.";
}

/* Calculate total */
$total = 0;

foreach ($cartItems as $item) {
    $total += (float)$item['price'] * (int)$item['quantity'];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Cart - My Store</title>

    <link rel="stylesheet" href="../css/cart.css">

</head>

<body>

<!-- Navbar -->
<nav class="navbar">

    <div class="logo">
        My Store
    </div>

    <div class="nav-links">

        <a href="../index.php">Home</a>

        <a href="../index.php#products">Products</a>

        <a href="add_product.php">Add Product</a>

        <a href="cart.php" class="active">
            Cart
        </a>

        <a href="logout.php" class="logout-btn">
            Logout
        </a>

    </div>

</nav>


<!-- Cart Section -->
<section class="cart-section">

    <div class="cart-container">

        <h1>Shopping Cart</h1>

        <p class="cart-subtitle">
            Review your products before checkout.
        </p>


        <!-- Success Message -->
        <?php if (!empty($success)): ?>

            <div class="success-message">
                <?php echo htmlspecialchars($success); ?>
            </div>

        <?php endif; ?>


        <!-- Error Message -->
        <?php if (!empty($error)): ?>

            <div class="error-message">
                <?php echo htmlspecialchars($error); ?>
            </div>

        <?php endif; ?>


        <?php if (count($cartItems) > 0): ?>

            <!-- Cart Table -->
            <div class="cart-table-wrapper">

                <table class="cart-table">

                    <thead>

                        <tr>

                            <th>Product</th>

                            <th>Price</th>

                            <th>Quantity</th>

                            <th>Subtotal</th>

                            <th>Action</th>

                        </tr>

                    </thead>

                    <tbody>

                        <?php foreach ($cartItems as $item): ?>

                            <?php

                            $price = (float)$item['price'];

                            $quantity = (int)$item['quantity'];

                            $subtotal = $price * $quantity;

                            ?>

                            <tr>

                                <!-- Product -->
                                <td class="product-cell">

                                    <?php if (!empty($item['image'])): ?>

                                        <img
                                            src="../<?php echo htmlspecialchars($item['image']); ?>"
                                            alt="<?php echo htmlspecialchars($item['name']); ?>"
                                            class="cart-product-image"
                                        >

                                    <?php else: ?>

                                        <div class="no-image">
                                            No Image
                                        </div>

                                    <?php endif; ?>


                                    <div class="product-details">

                                        <h3>
                                            <?php echo htmlspecialchars($item['name']); ?>
                                        </h3>

                                        <?php if (!empty($item['description'])): ?>

                                            <p>
                                                <?php
                                                echo htmlspecialchars(
                                                    strlen($item['description']) > 80
                                                        ? substr($item['description'], 0, 80) . "..."
                                                        : $item['description']
                                                );
                                                ?>
                                            </p>

                                        <?php endif; ?>

                                    </div>

                                </td>


                                <!-- Price -->
                                <td>

                                    <span class="price">
                                        ₹<?php echo number_format($price, 2); ?>
                                    </span>

                                </td>


                                <!-- Quantity -->
                                <td>

                                    <form
                                        action="cart.php"
                                        method="POST"
                                        class="quantity-form"
                                    >

                                        <input
                                            type="hidden"
                                            name="cart_id"
                                            value="<?php echo (int)$item['cart_id']; ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>"
                                        >

                                        <input
                                            type="number"
                                            name="quantity"
                                            value="<?php echo $quantity; ?>"
                                            min="1"
                                            max="99"
                                            required
                                        >

                                        <button
                                            type="submit"
                                            name="update_cart"
                                            class="update-btn"
                                        >
                                            Update
                                        </button>

                                    </form>

                                </td>


                                <!-- Subtotal -->
                                <td>

                                    <span class="subtotal">
                                        ₹<?php echo number_format($subtotal, 2); ?>
                                    </span>

                                </td>


                                <!-- Remove -->
                                <td>

                                    <form
                                        action="cart.php"
                                        method="POST"
                                        onsubmit="return confirm('Remove this item from your cart?');"
                                    >

                                        <input
                                            type="hidden"
                                            name="cart_id"
                                            value="<?php echo (int)$item['cart_id']; ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>"
                                        >

                                        <button
                                            type="submit"
                                            name="remove_item"
                                            class="remove-btn"
                                        >
                                            Remove
                                        </button>

                                    </form>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>


            <!-- Cart Summary -->
            <div class="cart-summary">

                <div class="summary-row">

                    <span>
                        Total Items
                    </span>

                    <span>
                        <?php echo count($cartItems); ?>
                    </span>

                </div>


                <div class="summary-row total-row">

                    <span>
                        Total Amount
                    </span>

                    <strong>
                        ₹<?php echo number_format($total, 2); ?>
                    </strong>

                </div>


                <div class="cart-actions">

                    <a
                        href="../index.php#products"
                        class="continue-shopping-btn"
                    >
                        Continue Shopping
                    </a>


                    <a
                        href="checkout.php"
                        class="checkout-btn"
                    >
                        Proceed to Checkout
                    </a>

                </div>

            </div>

        <?php else: ?>

            <!-- Empty Cart -->
            <div class="empty-cart">

                <div class="empty-cart-icon">
                    🛒
                </div>

                <h2>
                    Your cart is empty
                </h2>

                <p>
                    You haven't added any products yet.
                </p>

                <a
                    href="../index.php#products"
                    class="shop-now-btn"
                >
                    Start Shopping
                </a>

            </div>

        <?php endif; ?>

    </div>

</section>


<!-- Footer -->
<footer>

    <p>
        &copy; <?php echo date("Y"); ?> My Store. All rights reserved.
    </p>

</footer>

</body>

</html>