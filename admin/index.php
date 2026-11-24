
<?php
session_start();
require_once "../includes/db.php";

// Check login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../pages/login.php");
    exit();
}

// Verify admin role from database
$stmt = $conn->prepare("
    SELECT role FROM users WHERE id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || $user['role'] !== 'admin') {
    http_response_code(403);
    die("Access Denied! Admins only.");
}

// Dashboard statistics
$totalProducts = $conn->query(
    "SELECT COUNT(*) FROM products"
)->fetchColumn();

$totalOrders = $conn->query(
    "SELECT COUNT(*) FROM orders"
)->fetchColumn();

$totalCustomers = $conn->query(
    "SELECT COUNT(*) FROM users WHERE role = 'customer'"
)->fetchColumn();

$totalRevenue = $conn->query(
    "SELECT COALESCE(SUM(total_amount), 0) FROM orders"
)->fetchColumn();

// Recent orders
$recentOrders = $conn->query("
    SELECT id, customer_name, total_amount, status, created_at
    FROM orders
    ORDER BY id DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Dashboard | My Store</title>

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

    <h1>Admin Dashboard</h1>
    <p>Welcome to your store management panel.</p>

    <div class="dashboard-cards">

        <div class="card">
            <h3>Total Products</h3>
            <h2><?php echo $totalProducts; ?></h2>
        </div>

        <div class="card">
            <h3>Total Orders</h3>
            <h2><?php echo $totalOrders; ?></h2>
        </div>

        <div class="card">
            <h3>Total Customers</h3>
            <h2><?php echo $totalCustomers; ?></h2>
        </div>

        <div class="card">
            <h3>Total Revenue</h3>
            <h2>₹<?php echo number_format($totalRevenue, 2); ?></h2>
        </div>

    </div>

    <div class="orders-section">

        <h2>Recent Orders</h2>

        <table>

            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>

            <tbody>

                <?php foreach ($recentOrders as $order): ?>

                    <tr>
                        <td>#<?php echo $order['id']; ?></td>

                        <td>
                            <?php echo htmlspecialchars($order['customer_name']); ?>
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
                    </tr>

                <?php endforeach; ?>

                <?php if (empty($recentOrders)): ?>
                    <tr>
                        <td colspan="5">No orders found.</td>
                    </tr>
                <?php endif; ?>

            </tbody>

        </table>

    </div>

</div>

</body>
</html>
