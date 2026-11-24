
<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once "../includes/db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../index.php");
    exit;
}

$token = $_POST["csrf_token"] ?? "";

if (
    empty($_SESSION["csrf_token"]) ||
    !hash_equals($_SESSION["csrf_token"], $token)
) {
    die("Invalid request. Please go back and try again.");
}

$productId = filter_input(INPUT_POST, "product_id", FILTER_VALIDATE_INT);

if (!$productId || $productId < 1) {
    header("Location: ../index.php");
    exit;
}

try {
    // Check that the product exists
    $stmt = $conn->prepare("SELECT id FROM products WHERE id = ?");
    $stmt->execute([$productId]);

    if (!$stmt->fetch()) {
        die("Product not found.");
    }

    // Check whether this product is already in the user's cart
    $stmt = $conn->prepare(
        "SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?"
    );
    $stmt->execute([$_SESSION['user_id'], $productId]);
    $cartItem = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($cartItem) {
        $stmt = $conn->prepare(
            "UPDATE cart SET quantity = quantity + 1 WHERE id = ? AND user_id = ?"
        );
        $stmt->execute([$cartItem['id'], $_SESSION['user_id']]);
    } else {
        $stmt = $conn->prepare(
            "INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, 1)"
        );
        $stmt->execute([$_SESSION['user_id'], $productId]);
    }

    header("Location: ../pages/cart.php?added=1");
    exit;

} catch (PDOException $e) {
    error_log($e->getMessage());
    die("Could not add the product to your cart.");
}
?>
