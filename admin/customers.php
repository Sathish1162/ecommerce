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

// Get only safe customer columns that actually exist
$availableColumns = $conn->query("SHOW COLUMNS FROM users")
                         ->fetchAll(PDO::FETCH_COLUMN);

$allowedColumns = [
    'id',
    'name',
    'username',
    'email',
    'phone',
    'role',
    'created_at'
];

$columns = array_values(array_intersect($allowedColumns, $availableColumns));

if (empty($columns)) {
    $columns = ['id'];
}

$columnSQL = implode(', ', array_map(
    fn($column) => "`$column`",
    $columns
));

$stmt = $conn->query("
    SELECT $columnSQL
    FROM users
    WHERE role = 'customer'
    ORDER BY id DESC
");

$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customers | My Store Admin</title>
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
    <h1>Customers</h1>
    <p>View registered customers in your store.</p>

    <div class="orders-section">
        <h2>Total Customers: <?php echo count($customers); ?></h2>

        <table>
            <thead>
                <tr>
                    <?php foreach ($columns as $column): ?>
                        <th><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $column))); ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>

            <tbody>
                <?php if (empty($customers)): ?>
                    <tr>
                        <td colspan="<?php echo count($columns); ?>">
                            No customers found.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($customers as $customer): ?>
                        <tr>
                            <?php foreach ($columns as $column): ?>
                                <td>
                                    <?php echo htmlspecialchars((string)($customer[$column] ?? '')); ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>