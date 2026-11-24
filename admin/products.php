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

$message = "";

// Delete product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_product'])) {

    $product_id = (int)($_POST['product_id'] ?? 0);

    if ($product_id > 0) {

        try {
            $delete = $conn->prepare("
                DELETE FROM products
                WHERE id = ?
            ");

            $delete->execute([$product_id]);

            $message = $delete->rowCount() > 0
                ? "Product deleted successfully."
                : "Product not found.";

        } catch (PDOException $e) {
            error_log($e->getMessage());
            $message = "Unable to delete this product. It may be linked to existing cart or order records.";
        }

    } else {
        $message = "Invalid product.";
    }
}

// Fetch products
$stmt = $conn->query("
    SELECT id, name, price
    FROM products
    ORDER BY id DESC
");

$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products | My Store Admin</title>
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

    <h1>Manage Products</h1>
    <p>View and manage products in your store.</p>

    <div class="orders-section">

        <?php if ($message !== ''): ?>
            <p style="color:green; margin-bottom:15px;">
                <?php echo htmlspecialchars($message); ?>
            </p>
        <?php endif; ?>

        <a href="../pages/add_product.php"
           style="display:inline-block; background:#2563eb; color:white;
                  padding:12px 20px; text-decoration:none;
                  border-radius:6px; margin-bottom:20px;">
            + Add New Product
        </a>

        <h2>Total Products: <?php echo count($products); ?></h2>

        <table>
            <thead>
                <tr>
                    <th>Product ID</th>
                    <th>Product Name</th>
                    <th>Price</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>

                <?php if (empty($products)): ?>

                    <tr>
                        <td colspan="4">No products found.</td>
                    </tr>

                <?php else: ?>

                    <?php foreach ($products as $product): ?>

                        <tr>
                            <td>
                                <?php echo (int)$product['id']; ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($product['name']); ?>
                            </td>

                            <td>
                                ₹<?php echo number_format($product['price'], 2); ?>
                            </td>

                            <td>
                                <form method="POST"
                                      action="products.php"
                                      onsubmit="return confirm('Are you sure you want to delete this product?');">

                                    <input
                                        type="hidden"
                                        name="product_id"
                                        value="<?php echo (int)$product['id']; ?>"
                                    >

                                    <button
                                        type="submit"
                                        name="delete_product"
                                        value="1"
                                        style="background:#dc2626; color:white;
                                               border:none; padding:9px 14px;
                                               border-radius:5px; cursor:pointer;">
                                        Delete
                                    </button>

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