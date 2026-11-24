
<?php
session_start();

require_once "../includes/db.php";

// Check whether user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error = "";

// Fetch cart products
$stmt = $conn->prepare("
    SELECT c.product_id, c.quantity, p.name, p.price
    FROM cart c
    INNER JOIN products p ON c.product_id = p.id
    WHERE c.user_id = ?
");

$stmt->execute([$user_id]);
$cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate total
$totalAmount = 0;

foreach ($cartItems as $item) {
    $totalAmount += $item['price'] * $item['quantity'];
}

// Handle order submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $customer_name = trim($_POST['customer_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    // Validate customer details
    if (
        $customer_name === "" ||
        $phone === "" ||
        $address === ""
    ) {
        $error = "Please fill in all delivery details.";
    }

    elseif (!preg_match('/^[0-9+\-\s]{10,15}$/', $phone)) {
        $error = "Please enter a valid phone number.";
    }

    elseif (empty($cartItems)) {
        $error = "Your cart is empty.";
    }

    else {

        try {

            // Start transaction
            $conn->beginTransaction();

            // Insert order details
            $orderStmt = $conn->prepare("
                INSERT INTO orders
                (
                    user_id,
                    customer_name,
                    phone,
                    address,
                    total_amount,
                    status
                )
                VALUES (?, ?, ?, ?, ?, 'Pending')
            ");

            $orderStmt->execute([
                $user_id,
                $customer_name,
                $phone,
                $address,
                $totalAmount
            ]);

            // Get generated Order ID
            $order_id = $conn->lastInsertId();

            // Insert ordered products
            $itemStmt = $conn->prepare("
                INSERT INTO order_items
                (
                    order_id,
                    product_id,
                    product_name,
                    price,
                    quantity
                )
                VALUES (?, ?, ?, ?, ?)
            ");

            foreach ($cartItems as $item) {

                $itemStmt->execute([
                    $order_id,
                    $item['product_id'],
                    $item['name'],
                    $item['price'],
                    $item['quantity']
                ]);
            }

            // Clear customer's cart
            $deleteStmt = $conn->prepare("
                DELETE FROM cart
                WHERE user_id = ?
            ");

            $deleteStmt->execute([$user_id]);

            // Commit transaction
            $conn->commit();

            // Redirect to order confirmation
            header(
                "Location: order_success.php?id=" .
                urlencode($order_id)
            );

            exit();

        } catch (Throwable $e) {

            // Rollback if something goes wrong
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }

            error_log($e->getMessage());

            $error = "Unable to place your order. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Checkout | My Store</title>

    <link rel="stylesheet" href="../css/checkout.css">

</head>

<body>

    <nav>

        <h2>My Store</h2>

        <div>
            <a href="../index.php">Home</a>

            <a href="../index.php#products">Products</a>

            <a href="cart.php">Cart</a>

            <a href="logout.php">Logout</a>
        </div>

    </nav>

    <div class="checkout-header">

        <h1>Checkout</h1>

        <p>
            Enter your delivery information to complete your order.
        </p>

    </div>

    <div class="checkout-container">

        <div class="checkout-card">

            <h2>Delivery Information</h2>

            <p>
                Enter the details where you want your order delivered.
            </p>

            <?php if (!empty($error)): ?>

                <p style="color:red; margin-bottom:15px;">
                    <?php echo htmlspecialchars($error); ?>
                </p>

            <?php endif; ?>

            <form method="POST" action="checkout.php">

                <div class="form-group">

                    <label>Customer Name</label>

                    <input
                        type="text"
                        name="customer_name"
                        placeholder="Enter your full name"
                        value="<?php echo htmlspecialchars($_POST['customer_name'] ?? ''); ?>"
                        required
                    >

                </div>

                <div class="form-group">

                    <label>Phone Number</label>

                    <input
                        type="tel"
                        name="phone"
                        placeholder="Enter your phone number"
                        value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
                        required
                    >

                </div>

                <div class="form-group">

                    <label>Delivery Address</label>

                    <textarea
                        name="address"
                        placeholder="Enter your complete delivery address"
                        required
                    ><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>

                </div>

                <button type="submit" class="place-order-btn">
                    Place Order
                </button>

            </form>

        </div>

    </div>

    <footer>
        <p>© 2026 My Store. All rights reserved.</p>
    </footer>

</body>

</html>
