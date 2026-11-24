
<?php
session_start();
require_once "../includes/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch order details
$stmt = $conn->prepare("
    SELECT *
    FROM orders
    WHERE id = ? AND user_id = ?
");

$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    die("Order not found.");
}

// Fetch ordered products
$itemStmt = $conn->prepare("
    SELECT product_name, price, quantity
    FROM order_items
    WHERE order_id = ?
");

$itemStmt->execute([$order_id]);
$items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Successful | My Store</title>
    <link rel="stylesheet" href="../css/order_success.css">
</head>

<body>

<div class="success-container">

    <div class="success-icon">✓</div>

    <h1>Order Placed Successfully!</h1>

    <p>Thank you for shopping with My Store.</p>
    <p>Your order has been received successfully.</p>

    <div class="order-details">

        <h2>Order Details</h2>

        <p><strong>Order ID:</strong>
            <?php echo htmlspecialchars($order['id']); ?>
        </p>

        <p><strong>Customer:</strong>
            <?php echo htmlspecialchars($order['customer_name']); ?>
        </p>

        <p><strong>Phone:</strong>
            <?php echo htmlspecialchars($order['phone']); ?>
        </p>

        <p><strong>Address:</strong>
            <?php echo nl2br(htmlspecialchars($order['address'])); ?>
        </p>

        <p><strong>Status:</strong>
            <?php echo htmlspecialchars($order['status']); ?>
        </p>

    </div>

    <h2>Ordered Products</h2>

    <?php foreach ($items as $item): ?>

        <div class="product">

            <span>
                <?php echo htmlspecialchars($item['product_name']); ?>
                × <?php echo (int)$item['quantity']; ?>
            </span>

            <span>
                ₹<?php echo number_format(
                    $item['price'] * $item['quantity'], 2
                ); ?>
            </span>

        </div>

    <?php endforeach; ?>

    <h2 class="total">
        Total: ₹<?php echo number_format($order['total_amount'], 2); ?>
    </h2>

    <p><strong>Payment Method:</strong> Cash on Delivery</p>

    <a href="../index.php" class="continue-btn">
        Continue Shopping
    </a>

</div>

</body>
</html>
