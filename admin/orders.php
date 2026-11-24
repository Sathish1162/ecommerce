<?php
session_start();
require_once "../includes/db.php";

// Admin access protection
if (!isset($_SESSION['user_id'])) {
    header("Location: ../pages/login.php");
    exit();
}

$check = $conn->prepare("SELECT role FROM users WHERE id = ?");
$check->execute([$_SESSION['user_id']]);
$admin = $check->fetch(PDO::FETCH_ASSOC);

if (!$admin || $admin['role'] !== 'admin') {
    http_response_code(403);
    exit("Access Denied!");
}

$allowedStatuses = [
    'Pending',
    'Processing',
    'Shipped',
    'Delivered',
    'Cancelled'
];

$message = "";

// Update order status
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $order_id = (int)($_POST['order_id'] ?? 0);
    $status = $_POST['status'] ?? '';

    if ($order_id > 0 && in_array($status, $allowedStatuses, true)) {

        $update = $conn->prepare("
            UPDATE orders
            SET status = ?
            WHERE id = ?
        ");

        $update->execute([$status, $order_id]);

        $message = "Order status updated successfully.";

    } else {
        $message = "Invalid order details.";
    }
}

// Fetch all orders
$stmt = $conn->query("
    SELECT id, customer_name, phone, address,
           total_amount, status, created_at
    FROM orders
    ORDER BY id DESC
");

$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders | My Store Admin</title>
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>

<div class="sidebar">
    <h2>My Store</h2>
    <p>Admin Panel</p>

    <a href="index.php">Dashboard</a>
    <a href="orders.php">Orders</a>
    <a href="products.php">Products</a>
    <a href="customers.php">Customers</a>
    <a href="../pages/logout.php">Logout</a>
</div>

<div class="main-content">
    <h1>Manage Orders</h1>
    <p>View customer orders and update delivery status.</p>

    <div class="orders-section">

        <?php if ($message !== ''): ?>
            <p style="color:green; margin-bottom:15px;">
                <?php echo htmlspecialchars($message); ?>
            </p>
        <?php endif; ?>

        <h2>Total Orders: <?php echo count($orders); ?></h2>

        <table>
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Phone</th>
                    <th>Address</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Update</th>
                </tr>
            </thead>

            <tbody>
                <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="8">No orders found.</td>
                    </tr>
                <?php else: ?>

                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>#<?php echo (int)$order['id']; ?></td>

                            <td>
                                <?php echo htmlspecialchars($order['customer_name']); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($order['phone']); ?>
                            </td>

                            <td>
                                <?php echo nl2br(htmlspecialchars($order['address'])); ?>
                            </td>

                            <td>
                                ₹<?php echo number_format($order['total_amount'], 2); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($order['status']); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($order['created_at']); ?>
                            </td>

                            <td>
                                <form method="POST" action="orders.php">
                                    <input
                                        type="hidden"
                                        name="order_id"
                                        value="<?php echo (int)$order['id']; ?>"
                                    >

                                    <select name="status" required>
                                        <?php foreach ($allowedStatuses as $status): ?>
                                            <option
                                                value="<?php echo htmlspecialchars($status); ?>"
                                                <?php echo ($order['status'] === $status) ? 'selected' : ''; ?>
                                            >
                                                <?php echo htmlspecialchars($status); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>

                                    <button type="submit">Update</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                <?php endif; ?>
            </tbody>
        </table>

    </div>
</div>

</body>
</html>