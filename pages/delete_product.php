
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
    // Get the image path before deleting the product
    $stmt = $conn->prepare("SELECT image FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($product) {
        // Delete product from database
        $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$productId]);

        // Remove uploaded image, if it belongs to our images folder
        $image = $product["image"] ?? "";

        if (strpos($image, "images/") === 0) {
            $fileName = basename($image);
            $imagePath = realpath(__DIR__ . "/../images/" . $fileName);
            $imageDirectory = realpath(__DIR__ . "/../images");

            if (
                $imagePath &&
                $imageDirectory &&
                dirname($imagePath) === $imageDirectory &&
                is_file($imagePath)
            ) {
                unlink($imagePath);
            }
        }
    }

    header("Location: ../index.php");
    exit;

} catch (PDOException $e) {
    error_log($e->getMessage());
    die("Could not delete the product. Please try again.");
}
?>
